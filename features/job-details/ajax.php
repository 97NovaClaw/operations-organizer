<?php
/**
 * Job Details AJAX Handlers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Job_Details_AJAX {
    
    /**
     * Initialize AJAX handlers
     */
    public static function init() {
        // Change stream phase
        add_action('wp_ajax_oo_job_details_change_phase', array(__CLASS__, 'handle_change_phase'));
        
        // Get job logs for a stream
        add_action('wp_ajax_oo_job_details_get_logs', array(__CLASS__, 'handle_get_logs'));
        
        // Get activity log for a job stream
        add_action('wp_ajax_oo_job_details_get_activity_log', array(__CLASS__, 'handle_get_activity_log'));
        
        // Get master activity log for a job
        add_action('wp_ajax_oo_job_details_get_master_activity_log', array(__CLASS__, 'handle_get_master_activity_log'));
    }
    
    /**
     * Handle phase change for a job stream
     */
    public static function handle_change_phase() {
        error_log('[JOB_DETAILS_DEBUG] handle_change_phase() called');
        error_log('[JOB_DETAILS_DEBUG] POST data: ' . print_r($_POST, true));
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'oo_job_details_nonce')) {
            error_log('[JOB_DETAILS_DEBUG] Nonce verification FAILED');
            wp_send_json_error('Nonce verification failed');
        }
        error_log('[JOB_DETAILS_DEBUG] Nonce verification PASSED');
        
        // Check capabilities
        $capability = function_exists('oo_get_capability') ? oo_get_capability() : 'manage_options';
        error_log('[JOB_DETAILS_DEBUG] Required capability: ' . $capability);
        error_log('[JOB_DETAILS_DEBUG] User can: ' . (current_user_can($capability) ? 'YES' : 'NO'));
        if (!current_user_can($capability)) {
            error_log('[JOB_DETAILS_DEBUG] Capability check FAILED');
            wp_send_json_error('Insufficient permissions');
        }
        error_log('[JOB_DETAILS_DEBUG] Capability check PASSED');
        
        // Get and validate data
        $job_stream_id = isset($_POST['job_stream_id']) ? intval($_POST['job_stream_id']) : 0;
        $new_phase_id = isset($_POST['new_phase_id']) ? intval($_POST['new_phase_id']) : 0;
        $note = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';
        
        error_log('[JOB_DETAILS_DEBUG] job_stream_id: ' . $job_stream_id);
        error_log('[JOB_DETAILS_DEBUG] new_phase_id: ' . $new_phase_id);
        error_log('[JOB_DETAILS_DEBUG] note: ' . $note);
        
        if (!$job_stream_id) {
            error_log('[JOB_DETAILS_DEBUG] INVALID job_stream_id');
            wp_send_json_error('Invalid job stream ID');
        }
        
        if (!$new_phase_id) {
            error_log('[JOB_DETAILS_DEBUG] INVALID new_phase_id');
            wp_send_json_error('Invalid phase ID');
        }
        
        if (empty(trim($note))) {
            error_log('[JOB_DETAILS_DEBUG] EMPTY note');
            wp_send_json_error('A note is required for phase changes');
        }
        
        // Get the job stream to find current phase
        error_log('[JOB_DETAILS_DEBUG] Getting job stream...');
        $job_stream = OO_DB::get_job_stream($job_stream_id);
        error_log('[JOB_DETAILS_DEBUG] Job stream result: ' . print_r($job_stream, true));
        if (!$job_stream) {
            error_log('[JOB_DETAILS_DEBUG] Job stream NOT FOUND');
            wp_send_json_error('Job stream not found');
        }
        
        // Get the new phase details
        error_log('[JOB_DETAILS_DEBUG] Getting new phase...');
        $new_phase = OO_DB::get_phase($new_phase_id);
        error_log('[JOB_DETAILS_DEBUG] New phase result: ' . print_r($new_phase, true));
        if (!$new_phase) {
            error_log('[JOB_DETAILS_DEBUG] Phase NOT FOUND');
            wp_send_json_error('Phase not found');
        }
        
        // Update the job stream status to the new phase name
        global $wpdb;
        $job_streams_table = $wpdb->prefix . 'oo_job_streams_link';
        
        error_log('[JOB_DETAILS_DEBUG] Updating database...');
        error_log('[JOB_DETAILS_DEBUG] Table: ' . $job_streams_table);
        error_log('[JOB_DETAILS_DEBUG] New status: ' . $new_phase->phase_name);
        error_log('[JOB_DETAILS_DEBUG] Current status: ' . $job_stream->status_in_stream);
        
        $result = $wpdb->update(
            $job_streams_table,
            array(
                'status_in_stream' => $new_phase->phase_name,
                'current_phase_id' => $new_phase_id
            ),
            array('job_stream_id' => $job_stream_id),
            array('%s', '%d'),
            array('%d')
        );
        
        error_log('[JOB_DETAILS_DEBUG] Update result: ' . $result);
        if ($result === false) {
            error_log('[JOB_DETAILS_DEBUG] Database ERROR: ' . $wpdb->last_error);
        }
        
        if ($result !== false) {
            // Record this change in activity log if Kanban feature is available
            if (class_exists('OO_Kanban_Database')) {
                require_once OO_PLUGIN_DIR . 'features/kanban/database.php';
                
                // Use current_phase_id if available, otherwise try to find it by phase name
                $current_phase_id = $job_stream->current_phase_id;
                
                if (!$current_phase_id && !empty($job_stream->status_in_stream)) {
                    // Try to find the current phase ID by matching phase name
                    $phases = OO_DB::get_phases(array('stream_id' => $job_stream->stream_id));
                    foreach ($phases as $phase) {
                        if ($phase->phase_name === $job_stream->status_in_stream) {
                            $current_phase_id = $phase->phase_id;
                            break;
                        }
                    }
                }
                
                OO_Kanban_Database::record_phase_change(
                    $job_stream_id,
                    $current_phase_id,
                    $new_phase_id,
                    get_current_user_id(),
                    $note
                );
                
                // Fire action hook for job details phase changes
                do_action('oo_job_details_phase_changed', $job_stream_id, $current_phase_id, $new_phase_id, get_current_user_id());
            }
            
            wp_send_json_success(array(
                'message' => __('Phase updated successfully.', 'operations-organizer'),
                'new_phase_name' => $new_phase->phase_name
            ));
        } else {
            wp_send_json_error(__('Failed to update phase.', 'operations-organizer'));
        }
    }
    
    /**
     * Get job logs for a specific stream
     */
    public static function handle_get_logs() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'oo_job_details_nonce')) {
            wp_send_json_error('Nonce verification failed');
        }
        
        // Check capabilities
        $capability = function_exists('oo_get_capability') ? oo_get_capability() : 'manage_options';
        if (!current_user_can($capability)) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get parameters
        $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
        $stream_id = isset($_POST['stream_id']) ? intval($_POST['stream_id']) : 0;
        $phase_id = isset($_POST['phase_id']) ? intval($_POST['phase_id']) : 0;
        $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        if (!$job_id || !$stream_id) {
            wp_send_json_error('Invalid job or stream ID');
        }
        
        global $wpdb;
        $job_logs_table = $wpdb->prefix . 'oo_job_logs';
        $phases_table = $wpdb->prefix . 'oo_phases';
        
        // Build query
        $query = "
            SELECT jl.*, p.phase_name, u.display_name as employee_name
            FROM {$job_logs_table} jl
            LEFT JOIN {$phases_table} p ON jl.phase_id = p.phase_id
            LEFT JOIN {$wpdb->users} u ON jl.employee_id = u.ID
            WHERE jl.job_id = %d AND jl.stream_id = %d
        ";
        
        $params = array($job_id, $stream_id);
        
        // Add filters
        if ($phase_id) {
            $query .= " AND jl.phase_id = %d";
            $params[] = $phase_id;
        }
        
        if ($employee_id) {
            $query .= " AND jl.employee_id = %d";
            $params[] = $employee_id;
        }
        
        if ($date_from) {
            $query .= " AND DATE(jl.start_time) >= %s";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $query .= " AND DATE(jl.start_time) <= %s";
            $params[] = $date_to;
        }
        
        $query .= " ORDER BY jl.start_time DESC";
        
        $logs = $wpdb->get_results($wpdb->prepare($query, $params));
        
        // Format the logs
        $formatted_logs = array();
        foreach ($logs as $log) {
            $formatted_logs[] = array(
                'log_id' => $log->log_id,
                'employee_name' => $log->employee_name ?: __('Unknown', 'operations-organizer'),
                'phase_name' => $log->phase_name ?: __('Unknown', 'operations-organizer'),
                'start_time' => $log->start_time,
                'end_time' => $log->end_time,
                'status' => $log->status,
                'notes' => $log->notes
            );
        }
        
        wp_send_json_success(array('logs' => $formatted_logs));
    }
    
    /**
     * Get activity log for a job stream
     */
    public static function handle_get_activity_log() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'oo_job_details_nonce')) {
            wp_send_json_error('Nonce verification failed');
        }
        
        // Check capabilities
        $capability = function_exists('oo_get_capability') ? oo_get_capability() : 'manage_options';
        if (!current_user_can($capability)) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get job stream ID
        $job_stream_id = isset($_POST['job_stream_id']) ? intval($_POST['job_stream_id']) : 0;
        
        if (!$job_stream_id) {
            wp_send_json_error('Invalid job stream ID');
        }
        
        // Use the Kanban database class to get the activity log
        require_once OO_PLUGIN_DIR . 'features/kanban/database.php';
        
        // Get activity log data
        $activity_log_entries = OO_Kanban_Database::get_activity_log($job_stream_id);
        
        // Format as HTML
        $activity_log_html = self::format_activity_log_html($activity_log_entries);
        
        wp_send_json_success(array('html' => $activity_log_html));
    }
    
    /**
     * Format activity log entries as HTML
     * 
     * @param array $entries Activity log entries
     * @return string HTML formatted activity log
     */
    private static function format_activity_log_html($entries) {
        if (empty($entries)) {
            return '<p class="no-activity">' . __('No activity recorded for this job stream yet.', 'operations-organizer') . '</p>';
        }
        
        $html = '<div class="activity-log-entries">';
        
        foreach ($entries as $entry) {
            $html .= '<div class="activity-entry">';
            
            // Activity header
            $html .= '<div class="activity-header">';
            
            // Activity type icon and description
            switch ($entry->activity_type) {
                case 'PHASE_CHANGE':
                    $icon = 'dashicons-randomize';
                    $description = sprintf(
                        __('Phase changed from <strong>%s</strong> to <strong>%s</strong>', 'operations-organizer'),
                        esc_html($entry->from_phase_name ?: __('(unassigned)', 'operations-organizer')),
                        esc_html($entry->to_phase_name ?: __('(unassigned)', 'operations-organizer'))
                    );
                    break;
                    
                case 'JOB_CREATED':
                    $icon = 'dashicons-plus-alt';
                    $description = sprintf(
                        __('Job assigned to stream with initial phase <strong>%s</strong>', 'operations-organizer'),
                        esc_html($entry->to_phase_name ?: __('(unassigned)', 'operations-organizer'))
                    );
                    break;
                    
                case 'NOTE_ADDED':
                    $icon = 'dashicons-edit';
                    $description = __('Note added', 'operations-organizer');
                    break;
                    
                default:
                    $icon = 'dashicons-info';
                    $description = esc_html($entry->activity_type);
            }
            
            $html .= '<span class="dashicons ' . $icon . '"></span>';
            $html .= '<span class="activity-description">' . $description . '</span>';
            $html .= '</div>'; // .activity-header
            
            // Activity metadata
            $html .= '<div class="activity-meta">';
            
            // User info
            $user_name = $entry->user_name ?: __('System', 'operations-organizer');
            if ($entry->user_id == 0) {
                $user_name = __('System', 'operations-organizer');
            }
            $html .= '<span class="activity-user">' . esc_html($user_name) . '</span>';
            
            // Date/time
            $html .= '<span class="activity-date">' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($entry->created_at))) . '</span>';
            
            $html .= '</div>'; // .activity-meta
            
            // Notes if any
            if (!empty($entry->notes)) {
                $html .= '<div class="activity-notes">';
                $html .= '<em>' . esc_html($entry->notes) . '</em>';
                $html .= '</div>';
            }
            
            $html .= '</div>'; // .activity-entry
        }
        
        $html .= '</div>'; // .activity-log-entries
        
        return $html;
    }
    
    /**
     * Handle getting master activity log for a job
     */
    public static function handle_get_master_activity_log() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'oo_master_log_nonce')) {
            wp_send_json_error('Nonce verification failed');
        }
        
        // Check capabilities
        $capability = function_exists('oo_get_capability') ? oo_get_capability() : 'manage_options';
        if (!current_user_can($capability)) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // DataTables parameters
        $draw = intval($_POST['draw']);
        $start = intval($_POST['start']);
        $length = intval($_POST['length']);
        $search = $_POST['search']['value'];
        $order_column = intval($_POST['order'][0]['column']);
        $order_dir = $_POST['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';
        
        // Column mapping
        $columns = array(
            0 => 'created_at',
            1 => 'activity_type',
            2 => 'activity_level',
            3 => 'user_id',
            4 => 'stream_id',
            5 => 'field_name',
            6 => 'old_value',
            7 => 'new_value',
            8 => 'user_notes',
            9 => 'activity_category'
        );
        
        $order_by = isset($columns[$order_column]) ? $columns[$order_column] : 'created_at';
        
        // Get job ID
        $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
        if (!$job_id) {
            wp_send_json_error('Job ID is required');
        }
        
        // Build query
        $table_name = $wpdb->prefix . 'oo_master_activity_log';
        
        // Base where clause for job filtering
        $where_clauses = array("l.job_id = %d");
        $where_values = array($job_id);
        
        // Search filter
        if (!empty($search)) {
            $where_clauses[] = "(
                l.activity_type LIKE %s OR 
                l.user_notes LIKE %s OR
                l.field_name LIKE %s OR
                l.old_value LIKE %s OR
                l.new_value LIKE %s
            )";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            for ($i = 0; $i < 5; $i++) {
                $where_values[] = $search_term;
            }
        }
        
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        
        // Get total count
        $count_query = "
            SELECT COUNT(*)
            FROM {$table_name} l
            {$where_sql}
        ";
        
        $total_count = $wpdb->get_var($wpdb->prepare($count_query, $where_values));
        
        // Get filtered data with joins
        $data_query = "
            SELECT 
                l.*,
                s.stream_name,
                u.display_name as user_display_name
            FROM {$table_name} l
            LEFT JOIN {$wpdb->prefix}oo_streams s ON l.stream_id = s.stream_id
            LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
            {$where_sql}
            ORDER BY l.{$order_by} {$order_dir}
            LIMIT %d OFFSET %d
        ";
        
        $query_values = array_merge($where_values, array($length, $start));
        $results = $wpdb->get_results($wpdb->prepare($data_query, $query_values));
        
        // Format data for DataTables
        $data = array();
        foreach ($results as $log) {
            $data[] = array(
                'created_at' => $log->created_at,
                'activity_type' => $log->activity_type,
                'activity_level' => $log->activity_level,
                'user_display_name' => $log->user_display_name ? $log->user_display_name : 'Unknown',
                'stream_name' => $log->stream_name ? $log->stream_name : '-',
                'field_name' => $log->field_name ? $log->field_name : '-',
                'old_value' => $log->old_value ? $log->old_value : '-',
                'new_value' => $log->new_value ? $log->new_value : '-',
                'user_notes' => $log->user_notes ? $log->user_notes : '-',
                'activity_category' => $log->activity_category ? $log->activity_category : '-'
            );
        }
        
        // Send response
        wp_send_json(array(
            'draw' => $draw,
            'recordsTotal' => $total_count,
            'recordsFiltered' => $total_count,
            'data' => $data
        ));
    }
} 