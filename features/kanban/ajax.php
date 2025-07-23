<?php
/**
 * Kanban AJAX Handlers
 * 
 * Processes all AJAX requests for the Kanban board functionality
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Include the database class
require_once dirname(__FILE__) . '/database.php';

class OO_Kanban_AJAX {
    
    /**
     * Initialize AJAX handlers
     */
    public static function init() {
        // Phase change handler
        add_action('wp_ajax_oo_kanban_phase_change', array(__CLASS__, 'handle_phase_change'));
        
        // Activity log retrieval
        add_action('wp_ajax_oo_kanban_get_activity_log', array(__CLASS__, 'handle_get_activity_log'));
        
        // Add note handler
        add_action('wp_ajax_oo_kanban_add_note', array(__CLASS__, 'handle_add_note'));
    }
    
    /**
     * Handle phase change requests
     */
    public static function handle_phase_change() {
        oo_log('[KANBAN_AJAX] Phase change request received', __METHOD__);
        
        // Verify nonce
        check_ajax_referer('oo_kanban_phase_change_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can(oo_get_capability())) {
            oo_log('[KANBAN_AJAX] Permission denied for user', __METHOD__);
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        // Sanitize input
        $job_stream_id = isset($_POST['job_stream_id']) ? intval($_POST['job_stream_id']) : 0;
        $from_phase_id = isset($_POST['from_phase_id']) ? intval($_POST['from_phase_id']) : null;
        $to_phase_id = isset($_POST['to_phase_id']) ? intval($_POST['to_phase_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        // Validate required fields
        if (empty($job_stream_id) || empty($to_phase_id)) {
            oo_log('[KANBAN_AJAX] Missing required fields - job_stream_id: ' . $job_stream_id . ', to_phase_id: ' . $to_phase_id, __METHOD__);
            wp_send_json_error(array('message' => __('Missing required fields.', 'operations-organizer')));
            return;
        }
        
        // Validate that notes are provided
        if (empty(trim($notes))) {
            oo_log('[KANBAN_AJAX] Missing required notes field', __METHOD__);
            wp_send_json_error(array('message' => __('A note is required for phase changes.', 'operations-organizer')));
            return;
        }
        
        // Don't process if moving to same phase
        if ($from_phase_id == $to_phase_id) {
            oo_log('[KANBAN_AJAX] Attempted to move to same phase, ignoring', __METHOD__);
            wp_send_json_error(array('message' => __('Cannot move to the same phase.', 'operations-organizer')));
            return;
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Prepare metadata
        $metadata = array(
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '',
            'timestamp' => current_time('mysql')
        );
        
        // Record the phase change
        $result = OO_Kanban_Database::record_phase_change(
            $job_stream_id,
            $from_phase_id,
            $to_phase_id,
            $user_id,
            $notes,
            $metadata
        );
        
        if (is_wp_error($result)) {
            oo_log('[KANBAN_AJAX] Phase change failed: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(array(
                'message' => __('Failed to update phase: ', 'operations-organizer') . $result->get_error_message()
            ));
            return;
        }
        
        // Get updated phase information
        $current_phase = OO_Kanban_Database::get_current_phase($job_stream_id);
        
        oo_log('[KANBAN_AJAX] Phase change successful', __METHOD__);
        
        wp_send_json_success(array(
            'message' => __('Phase updated successfully.', 'operations-organizer'),
            'current_phase' => $current_phase
        ));
    }
    
    /**
     * Handle activity log retrieval requests
     */
    public static function handle_get_activity_log() {
        // Verify nonce
        check_ajax_referer('oo_kanban_phase_change_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        // Get parameters
        $job_stream_id = isset($_POST['job_stream_id']) ? intval($_POST['job_stream_id']) : 0;
        
        if (empty($job_stream_id)) {
            wp_send_json_error(array('message' => __('Invalid job stream ID.', 'operations-organizer')));
            return;
        }
        
        // Prepare filter arguments
        $args = array(
            'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 50,
            'offset' => isset($_POST['offset']) ? intval($_POST['offset']) : 0,
            'activity_type' => isset($_POST['activity_type']) ? sanitize_text_field($_POST['activity_type']) : null,
            'user_id' => isset($_POST['user_id']) ? intval($_POST['user_id']) : null,
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : null,
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : null
        );
        
        // Get activity log
        $activity_log = OO_Kanban_Database::get_activity_log($job_stream_id, $args);
        
        // Format dates for display
        foreach ($activity_log as &$entry) {
            $entry->created_at_formatted = date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                strtotime($entry->created_at)
            );
        }
        
        wp_send_json_success(array(
            'activity_log' => $activity_log,
            'count' => count($activity_log)
        ));
    }
    
    /**
     * Handle add note requests
     */
    public static function handle_add_note() {
        // Verify nonce
        check_ajax_referer('oo_kanban_phase_change_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        // Get parameters
        $job_stream_id = isset($_POST['job_stream_id']) ? intval($_POST['job_stream_id']) : 0;
        $note = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';
        
        if (empty($job_stream_id) || empty($note)) {
            wp_send_json_error(array('message' => __('Job stream ID and note are required.', 'operations-organizer')));
            return;
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Add metadata
        $metadata = array(
            'note_type' => isset($_POST['note_type']) ? sanitize_text_field($_POST['note_type']) : 'general',
            'timestamp' => current_time('mysql')
        );
        
        // Add the note
        $result = OO_Kanban_Database::add_stream_note($job_stream_id, $user_id, $note, $metadata);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => __('Failed to add note: ', 'operations-organizer') . $result->get_error_message()
            ));
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Note added successfully.', 'operations-organizer'),
            'note_id' => $result
        ));
    }
} 