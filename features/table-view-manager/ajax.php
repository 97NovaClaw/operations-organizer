<?php
/**
 * Table View Manager - AJAX Handlers
 * 
 * Handles AJAX requests for saving and managing table preferences
 * 
 * @package OperationsOrganizer
 * @subpackage Features/TableViewManager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class OO_Table_View_Manager_AJAX {
    
    /**
     * Initialize AJAX handlers
     */
    public static function init() {
        // Save table preferences
        add_action('wp_ajax_oo_save_table_view_preference', array(__CLASS__, 'save_preference'));
        
        // Delete table preferences (reset)
        add_action('wp_ajax_oo_delete_table_view_preference', array(__CLASS__, 'delete_preference'));
        
        // Get available columns for a table
        add_action('wp_ajax_oo_get_table_available_columns', array(__CLASS__, 'get_available_columns'));
    }
    
    /**
     * Save user's table view preference
     */
    public static function save_preference() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'table_view_manager_nonce')) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Get current user
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        // Validate required fields
        if (empty($_POST['table_id']) || empty($_POST['preferences'])) {
            wp_send_json_error('Missing required fields');
            return;
        }
        
        $table_id = sanitize_key($_POST['table_id']);
        
        // Decode and validate preferences
        $preferences = json_decode(stripslashes($_POST['preferences']), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid preferences format');
            return;
        }
        
        // Save to database
        $result = OO_Table_View_Manager_Database::save_preference($user_id, $table_id, $preferences);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Table preferences saved successfully', 'operations-organizer'),
                'table_id' => $table_id
            ));
        } else {
            wp_send_json_error('Failed to save preferences');
        }
    }
    
    /**
     * Delete user's table view preference
     */
    public static function delete_preference() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'table_view_manager_nonce')) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Get current user
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        // Validate required fields
        if (empty($_POST['table_id'])) {
            wp_send_json_error('Missing table ID');
            return;
        }
        
        $table_id = sanitize_key($_POST['table_id']);
        
        // Delete from database
        $result = OO_Table_View_Manager_Database::delete_preference($user_id, $table_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Table preferences reset successfully', 'operations-organizer'),
                'table_id' => $table_id
            ));
        } else {
            wp_send_json_error('Failed to reset preferences');
        }
    }
    
    /**
     * Get available columns for a specific table
     * This is a placeholder that will be implemented based on table type
     */
    public static function get_available_columns() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'table_view_manager_nonce')) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Validate required fields
        if (empty($_POST['table_id'])) {
            wp_send_json_error('Missing table ID');
            return;
        }
        
        $table_id = sanitize_key($_POST['table_id']);
        $columns = array();
        
        // Parse table_id to determine type and context
        $parts = explode('_', $table_id);
        
        // Example implementation for different table types
        if (strpos($table_id, 'job_details_activity_log') === 0) {
            // Job activity log columns
            $columns = self::get_job_activity_log_columns();
        } elseif (strpos($table_id, 'phase_dashboard_logs_') === 0) {
            // Phase dashboard logs - extract stream slug
            $stream_slug = str_replace('phase_dashboard_logs_', '', $table_id);
            $columns = self::get_stream_dashboard_columns($stream_slug);
        } elseif (strpos($table_id, 'job_details_stream_logs_') === 0) {
            // Job details stream logs - extract stream slug
            $stream_slug = str_replace('job_details_stream_logs_', '', $table_id);
            $columns = self::get_job_stream_logs_columns($stream_slug);
        }
        
        wp_send_json_success(array(
            'table_id' => $table_id,
            'columns' => $columns
        ));
    }
    
    /**
     * Get columns for job activity log
     */
    private static function get_job_activity_log_columns() {
        return array(
            array('id' => 'created_at', 'title' => __('Date/Time', 'operations-organizer')),
            array('id' => 'activity_type', 'title' => __('Activity Type', 'operations-organizer')),
            array('id' => 'activity_level', 'title' => __('Level', 'operations-organizer')),
            array('id' => 'user_display_name', 'title' => __('User', 'operations-organizer')),
            array('id' => 'stream_name', 'title' => __('Stream', 'operations-organizer')),
            array('id' => 'field_name', 'title' => __('Field', 'operations-organizer')),
            array('id' => 'old_value', 'title' => __('Old Value', 'operations-organizer')),
            array('id' => 'new_value', 'title' => __('New Value', 'operations-organizer')),
            array('id' => 'user_notes', 'title' => __('Notes', 'operations-organizer')),
            array('id' => 'activity_category', 'title' => __('Category', 'operations-organizer'))
        );
    }
    
    /**
     * Get columns for stream dashboard
     * This would typically call the existing function that generates available columns
     */
    private static function get_stream_dashboard_columns($stream_slug) {
        // This is a placeholder - in real implementation, this would call
        // the existing function that generates columns based on stream configuration
        $columns = array(
            // Core columns
            array('id' => 'job_number', 'title' => __('Job Number', 'operations-organizer')),
            array('id' => 'client_name', 'title' => __('Client Name', 'operations-organizer')),
            array('id' => 'start_date', 'title' => __('Start Date', 'operations-organizer')),
            array('id' => 'due_date', 'title' => __('Due Date', 'operations-organizer')),
        );
        
        // Add stream-specific phases and KPIs here
        // This would be dynamically generated based on the stream
        
        return $columns;
    }
    
    /**
     * Get columns for job stream logs
     */
    private static function get_job_stream_logs_columns($stream_slug) {
        // Similar to dashboard columns but for job-specific view
        return self::get_stream_dashboard_columns($stream_slug);
    }
} 