<?php
// /features/stream-management/ajax.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Stream_Management_AJAX {
    
    public static function init() {
        // Register AJAX handlers
        add_action('wp_ajax_oo_add_stream', array(__CLASS__, 'ajax_add_stream'));
        add_action('wp_ajax_oo_get_stream', array(__CLASS__, 'ajax_get_stream'));
        add_action('wp_ajax_oo_update_stream', array(__CLASS__, 'ajax_update_stream'));
        add_action('wp_ajax_oo_toggle_stream_status', array(__CLASS__, 'ajax_toggle_stream_status'));
        add_action('wp_ajax_oo_delete_stream', array(__CLASS__, 'ajax_delete_stream'));
        add_action('wp_ajax_oo_migrate_stream_slugs', array(__CLASS__, 'ajax_migrate_stream_slugs'));
    }
    
    public static function ajax_add_stream() {
        check_ajax_referer('oo_add_stream_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $stream_name = isset($_POST['stream_name']) ? sanitize_text_field($_POST['stream_name']) : '';
        $stream_description = isset($_POST['stream_description']) ? sanitize_textarea_field($_POST['stream_description']) : '';
        
        if (empty($stream_name)) {
            wp_send_json_error(array('message' => __('Stream name is required.', 'operations-organizer')));
            return;
        }
        
        // Use the form handler to create the stream and its table
        $result = OO_Stream_Management_Form_Handler::create_new_stream($stream_name, $stream_description);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Stream created successfully.', 'operations-organizer'),
            'stream_id' => $result
        ));
    }
    
    public static function ajax_get_stream() {
        check_ajax_referer('oo_get_stream_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $stream_id = isset($_POST['stream_id']) ? intval($_POST['stream_id']) : 0;
        
        if ($stream_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid stream ID.', 'operations-organizer')));
            return;
        }
        
        $stream = OO_DB::get_stream($stream_id);
        
        if (!$stream) {
            wp_send_json_error(array('message' => __('Stream not found.', 'operations-organizer')));
            return;
        }
        
        wp_send_json_success(array('stream' => $stream));
    }
    
    public static function ajax_update_stream() {
        check_ajax_referer('oo_edit_stream_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $stream_id = isset($_POST['stream_id']) ? intval($_POST['stream_id']) : 0;
        $stream_name = isset($_POST['stream_name']) ? sanitize_text_field($_POST['stream_name']) : '';
        $stream_description = isset($_POST['stream_description']) ? sanitize_textarea_field($_POST['stream_description']) : '';
        
        if ($stream_id <= 0 || empty($stream_name)) {
            wp_send_json_error(array('message' => __('Stream ID and name are required.', 'operations-organizer')));
            return;
        }
        
        $result = OO_DB::update_stream($stream_id, $stream_name, $stream_description);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array('message' => __('Stream updated successfully.', 'operations-organizer')));
    }
    
    public static function ajax_toggle_stream_status() {
        check_ajax_referer('oo_toggle_stream_status_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $stream_id = isset($_POST['stream_id']) ? intval($_POST['stream_id']) : 0;
        $new_status = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
        
        if ($stream_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid stream ID.', 'operations-organizer')));
            return;
        }
        
        $result = OO_DB::toggle_stream_status($stream_id, $new_status);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array('message' => __('Stream status updated successfully.', 'operations-organizer')));
    }
    
    public static function ajax_delete_stream() {
        check_ajax_referer('oo_delete_stream_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $stream_id = isset($_POST['stream_id']) ? intval($_POST['stream_id']) : 0;
        
        if ($stream_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid stream ID.', 'operations-organizer')));
            return;
        }
        
        // Note: This is a soft delete - just marks as inactive
        $result = OO_DB::delete_stream($stream_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array('message' => __('Stream deleted successfully.', 'operations-organizer')));
    }
    
    /**
     * Handle AJAX request to migrate stream slugs
     */
    public static function ajax_migrate_stream_slugs() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'oo_migrate_stream_slugs_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'operations-organizer')));
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'operations-organizer')));
            return;
        }
        
        try {
            // Run the migration
            OO_DB::migrate_stream_slugs();
            
            wp_send_json_success(array('message' => __('Database migration completed successfully.', 'operations-organizer')));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Migration failed: ', 'operations-organizer') . $e->getMessage()));
        }
    }
} 