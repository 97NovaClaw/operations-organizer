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
} 