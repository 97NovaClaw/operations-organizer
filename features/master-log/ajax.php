<?php
/**
 * Master Log AJAX Handlers
 * 
 * Processes AJAX requests for the activity log admin interface
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Master_Log_AJAX {
    
    /**
     * Initialize AJAX handlers
     */
    public static function init() {
        // DataTables server-side processing
        add_action('wp_ajax_oo_get_master_logs', array(__CLASS__, 'get_master_logs'));
        
        // Get activity details
        add_action('wp_ajax_oo_get_activity_details', array(__CLASS__, 'get_activity_details'));
        
        // Export logs
        add_action('wp_ajax_oo_export_master_logs', array(__CLASS__, 'export_logs'));
    }
    
    /**
     * Get master logs for DataTables
     */
    public static function get_master_logs() {
        // Verify nonce
        if (!check_ajax_referer('oo_master_log_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check capability
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        $table_name = OO_Master_Log_Database::get_table_name();
        
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
            1 => 'job_id',
            2 => 'activity_type',
            3 => 'user_id',
            4 => 'user_notes'
        );
        
        $order_by = isset($columns[$order_column]) ? $columns[$order_column] : 'created_at';
        
        // Build base query
        $where_clauses = array();
        $where_values = array();
        
        // Filter by job if specified
        if (!empty($_POST['job_id'])) {
            $where_clauses[] = "l.job_id = %d";
            $where_values[] = intval($_POST['job_id']);
        }
        
        // Filter by stream if specified
        if (!empty($_POST['stream_id'])) {
            $where_clauses[] = "l.stream_id = %d";
            $where_values[] = intval($_POST['stream_id']);
        }
        
        // Filter by activity type
        if (!empty($_POST['activity_type'])) {
            $where_clauses[] = "l.activity_type = %s";
            $where_values[] = sanitize_text_field($_POST['activity_type']);
        }
        
        // Search filter
        if (!empty($search)) {
            $where_clauses[] = "(
                j.job_number LIKE %s OR 
                j.client_name LIKE %s OR 
                l.user_notes LIKE %s OR
                u.display_name LIKE %s
            )";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        // Get total count
        $count_query = "
            SELECT COUNT(*)
            FROM {$table_name} l
            LEFT JOIN {$wpdb->prefix}oo_jobs j ON l.job_id = j.job_id
            LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
            {$where_sql}
        ";
        
        if (!empty($where_values)) {
            $total_count = $wpdb->get_var($wpdb->prepare($count_query, $where_values));
        } else {
            $total_count = $wpdb->get_var($count_query);
        }
        
        // Get filtered data
        $data_query = "
            SELECT 
                l.*,
                j.job_number,
                j.client_name,
                s.stream_name,
                u.display_name as user_display_name
            FROM {$table_name} l
            LEFT JOIN {$wpdb->prefix}oo_jobs j ON l.job_id = j.job_id
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
            // Decode metadata
            if ($log->metadata) {
                $log->metadata = json_decode($log->metadata, true);
            }
            
            // Format the row
            $data[] = array(
                'DT_RowId' => 'log-' . $log->log_id,
                'created_at' => OO_Master_Log_Formatters::format_timestamp($log->created_at),
                'job' => sprintf(
                    '<a href="%s">%s - %s</a>',
                    admin_url('admin.php?page=oo_job_details&job_id=' . $log->job_id),
                    esc_html($log->job_number),
                    esc_html($log->client_name)
                ),
                'activity' => sprintf(
                    '<span class="activity-type %s">%s %s</span>',
                    OO_Master_Log::get_activity_color_class($log->activity_type),
                    OO_Master_Log::get_activity_icon($log->activity_type),
                    esc_html(str_replace('_', ' ', $log->activity_type))
                ),
                'user' => esc_html($log->user_display_name),
                'description' => OO_Master_Log_Formatters::format_activity($log),
                'stream' => $log->stream_name ? esc_html($log->stream_name) : '-',
                'actions' => sprintf(
                    '<button class="button button-small view-details" data-log-id="%d">View Details</button>',
                    $log->log_id
                )
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
    
    /**
     * Get activity details
     */
    public static function get_activity_details() {
        // Verify nonce
        if (!check_ajax_referer('oo_master_log_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check capability
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $log_id = intval($_POST['log_id']);
        
        global $wpdb;
        $table_name = OO_Master_Log_Database::get_table_name();
        
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT l.*, u.display_name, u.user_email
             FROM {$table_name} l
             LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
             WHERE l.log_id = %d",
            $log_id
        ));
        
        if (!$log) {
            wp_send_json_error('Log entry not found');
            return;
        }
        
        // Decode metadata
        if ($log->metadata) {
            $log->metadata = json_decode($log->metadata, true);
        }
        
        // Format response
        $details = array(
            'log_id' => $log->log_id,
            'activity_type' => $log->activity_type,
            'created_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at)),
            'user' => array(
                'id' => $log->user_id,
                'name' => $log->display_name,
                'email' => $log->user_email
            ),
            'job_id' => $log->job_id,
            'stream_id' => $log->stream_id,
            'job_stream_id' => $log->job_stream_id,
            'field_name' => $log->field_name,
            'old_value' => $log->old_value,
            'new_value' => $log->new_value,
            'user_notes' => $log->user_notes,
            'metadata' => $log->metadata
        );
        
        wp_send_json_success($details);
    }
    
    /**
     * Export logs to CSV
     */
    public static function export_logs() {
        // Verify nonce
        if (!check_ajax_referer('oo_master_log_nonce', 'nonce', false)) {
            wp_die('Invalid nonce');
        }
        
        // Check capability
        if (!current_user_can(oo_get_capability())) {
            wp_die('Insufficient permissions');
        }
        
        // Get filters
        $job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : null;
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : null;
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : null;
        
        // Get logs
        $args = array(
            'limit' => 10000, // Reasonable limit for CSV export
            'date_from' => $date_from,
            'date_to' => $date_to
        );
        
        if ($job_id) {
            $logs = OO_Master_Log_Database::get_logs_for_job($job_id, $args);
        } else {
            // Get all logs with filters
            global $wpdb;
            $table_name = OO_Master_Log_Database::get_table_name();
            
            $where_clauses = array();
            $where_values = array();
            
            if ($date_from) {
                $where_clauses[] = "created_at >= %s";
                $where_values[] = $date_from . ' 00:00:00';
            }
            
            if ($date_to) {
                $where_clauses[] = "created_at <= %s";
                $where_values[] = $date_to . ' 23:59:59';
            }
            
            $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
            
            $query = "SELECT * FROM {$table_name} {$where_sql} ORDER BY created_at DESC LIMIT 10000";
            
            if (!empty($where_values)) {
                $logs = $wpdb->get_results($wpdb->prepare($query, $where_values));
            } else {
                $logs = $wpdb->get_results($query);
            }
        }
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="activity-log-' . date('Y-m-d') . '.csv"');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, array(
            'Date/Time',
            'Job ID',
            'Activity Type',
            'User',
            'Description',
            'Notes'
        ));
        
        // Write data
        foreach ($logs as $log) {
            // Get user
            $user = get_user_by('id', $log->user_id);
            
            fputcsv($output, array(
                $log->created_at,
                $log->job_id,
                $log->activity_type,
                $user ? $user->display_name : 'User #' . $log->user_id,
                strip_tags(OO_Master_Log_Formatters::format_activity($log)),
                $log->user_notes
            ));
        }
        
        fclose($output);
        exit;
    }
} 