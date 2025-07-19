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
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'oo_job_details_nonce')) {
            wp_send_json_error('Nonce verification failed');
        }
        
        // Check capabilities
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get and validate data
        $job_stream_id = isset($_POST['job_stream_id']) ? intval($_POST['job_stream_id']) : 0;
        $new_phase_id = isset($_POST['new_phase_id']) ? intval($_POST['new_phase_id']) : 0;
        
        if (!$job_stream_id) {
            wp_send_json_error('Invalid job stream ID');
        }
        
        // Use the Kanban database class to handle the phase change
        require_once OO_PLUGIN_DIR . 'features/kanban/database.php';
        
        $result = OO_Kanban_Database::change_job_phase($job_stream_id, $new_phase_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Phase updated successfully.', 'operations-organizer')
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
        if (!current_user_can(oo_get_capability())) {
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
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get job stream ID
        $job_stream_id = isset($_POST['job_stream_id']) ? intval($_POST['job_stream_id']) : 0;
        
        if (!$job_stream_id) {
            wp_send_json_error('Invalid job stream ID');
        }
        
        // Use the Kanban database class to get the activity log
        require_once OO_PLUGIN_DIR . 'features/kanban/database.php';
        
        $activity_log_html = OO_Kanban_Database::get_activity_log($job_stream_id);
        
        wp_send_json_success(array('html' => $activity_log_html));
    }
} 