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
        add_action('wp_ajax_oo_get_stream_feature_sets', array(__CLASS__, 'ajax_get_stream_feature_sets'));
        add_action('wp_ajax_oo_update_stream_feature_sets', array(__CLASS__, 'ajax_update_stream_feature_sets'));
        add_action('wp_ajax_oo_fix_missing_slugs', array(__CLASS__, 'ajax_fix_missing_slugs'));
    }
    
    public static function ajax_add_stream() {
        oo_log('NEW_STREAM_DEBUG: Starting ajax_add_stream', __METHOD__);
        
        check_ajax_referer('oo_add_stream_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            oo_log('NEW_STREAM_DEBUG: Permission denied for user', __METHOD__);
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $stream_name = isset($_POST['stream_name']) ? sanitize_text_field($_POST['stream_name']) : '';
        $stream_description = isset($_POST['stream_description']) ? sanitize_textarea_field($_POST['stream_description']) : '';
        
        oo_log('NEW_STREAM_DEBUG: Received data - stream_name: ' . $stream_name . ', stream_description: ' . $stream_description, __METHOD__);
        
        if (empty($stream_name)) {
            oo_log('NEW_STREAM_DEBUG: Stream name is required', __METHOD__);
            wp_send_json_error(array('message' => __('Stream name is required.', 'operations-organizer')));
            return;
        }
        
        // Use the form handler to create the stream and its table
        oo_log('NEW_STREAM_DEBUG: Creating new stream via form handler', __METHOD__);
        $result = OO_Stream_Management_Form_Handler::create_new_stream($stream_name, $stream_description);
        
        if (is_wp_error($result)) {
            oo_log('NEW_STREAM_DEBUG: Error creating stream: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        $stream_id = $result;
        oo_log('NEW_STREAM_DEBUG: Stream created successfully with ID: ' . $stream_id, __METHOD__);
        
        // Automatically assign the "Operational Tools" feature set to the new stream
        oo_log('NEW_STREAM_DEBUG: Looking for Operational Tools feature set to assign automatically', __METHOD__);
        $operational_tools = OO_DB::get_feature_set_by_slug('operational_tools');
        
        if ($operational_tools && $operational_tools->is_active) {
            oo_log('NEW_STREAM_DEBUG: Found Operational Tools feature set (ID: ' . $operational_tools->feature_set_id . '), assigning to new stream', __METHOD__);
            $assignment_result = OO_DB::assign_feature_set_to_stream($stream_id, $operational_tools->feature_set_id);
            
            if (is_wp_error($assignment_result)) {
                oo_log('NEW_STREAM_DEBUG: Error assigning Operational Tools to new stream: ' . $assignment_result->get_error_message(), __METHOD__);
                // Don't fail the stream creation, just log the error
            } else {
                oo_log('NEW_STREAM_DEBUG: Successfully assigned Operational Tools to new stream, assignment ID: ' . $assignment_result, __METHOD__);
            }
        } else {
            oo_log('NEW_STREAM_DEBUG: Operational Tools feature set not found or inactive, skipping automatic assignment', __METHOD__);
        }
        
        oo_log('NEW_STREAM_DEBUG: Stream creation process completed successfully', __METHOD__);
        wp_send_json_success(array(
            'message' => __('Stream created successfully.', 'operations-organizer'),
            'stream_id' => $stream_id
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
    
    public static function ajax_get_stream_feature_sets() {
        check_ajax_referer('oo_get_stream_feature_sets_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $stream_id = isset($_POST['stream_id']) ? intval($_POST['stream_id']) : 0;
        
        if ($stream_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid stream ID.', 'operations-organizer')));
            return;
        }
        
        $feature_sets = OO_DB::get_feature_sets_for_stream($stream_id, 1);
        
        wp_send_json_success(array('feature_sets' => $feature_sets));
    }
    
    public static function ajax_update_stream_feature_sets() {
        oo_log('AJAX_DEBUG: Starting ajax_update_stream_feature_sets', __METHOD__);
        
        check_ajax_referer('oo_update_stream_feature_sets_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            oo_log('AJAX_DEBUG: Permission denied for user', __METHOD__);
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $stream_id = isset($_POST['stream_id']) ? intval($_POST['stream_id']) : 0;
        $feature_sets = isset($_POST['feature_sets']) ? array_map('intval', $_POST['feature_sets']) : array();
        
        oo_log('AJAX_DEBUG: Received data - stream_id: ' . $stream_id . ', feature_sets: ' . json_encode($feature_sets), __METHOD__);
        
        if ($stream_id <= 0) {
            oo_log('AJAX_DEBUG: Invalid stream ID: ' . $stream_id, __METHOD__);
            wp_send_json_error(array('message' => __('Invalid stream ID.', 'operations-organizer')));
            return;
        }
        
        // Get currently assigned feature sets
        oo_log('AJAX_DEBUG: Getting current feature sets for stream ' . $stream_id, __METHOD__);
        $current_feature_sets = OO_DB::get_feature_sets_for_stream($stream_id, 1);
        $current_ids = array_map(function($fs) { return $fs->feature_set_id; }, $current_feature_sets);
        
        oo_log('AJAX_DEBUG: Current feature set IDs: ' . json_encode($current_ids), __METHOD__);
        oo_log('AJAX_DEBUG: New feature set IDs: ' . json_encode($feature_sets), __METHOD__);
        
        // Remove feature sets that are no longer selected
        $to_remove = array_diff($current_ids, $feature_sets);
        oo_log('AJAX_DEBUG: Feature sets to remove: ' . json_encode($to_remove), __METHOD__);
        
        foreach ($to_remove as $remove_id) {
            oo_log('AJAX_DEBUG: Removing feature set ID: ' . $remove_id, __METHOD__);
            $result = OO_DB::remove_feature_set_from_stream($stream_id, $remove_id);
            if (is_wp_error($result)) {
                oo_log('AJAX_DEBUG: Error removing feature set ' . $remove_id . ': ' . $result->get_error_message(), __METHOD__);
                wp_send_json_error(array('message' => $result->get_error_message()));
                return;
            }
            oo_log('AJAX_DEBUG: Successfully removed feature set ID: ' . $remove_id, __METHOD__);
        }
        
        // Add new feature sets
        $to_add = array_diff($feature_sets, $current_ids);
        oo_log('AJAX_DEBUG: Feature sets to add: ' . json_encode($to_add), __METHOD__);
        
        foreach ($to_add as $add_id) {
            oo_log('AJAX_DEBUG: Adding feature set ID: ' . $add_id, __METHOD__);
            $result = OO_DB::assign_feature_set_to_stream($stream_id, $add_id);
            if (is_wp_error($result)) {
                oo_log('AJAX_DEBUG: Error adding feature set ' . $add_id . ': ' . $result->get_error_message(), __METHOD__);
                wp_send_json_error(array('message' => $result->get_error_message()));
                return;
            }
            oo_log('AJAX_DEBUG: Successfully added feature set ID: ' . $add_id . ', result: ' . $result, __METHOD__);
        }
        
        oo_log('AJAX_DEBUG: Feature set update completed successfully', __METHOD__);
        wp_send_json_success(array('message' => __('Feature sets updated successfully.', 'operations-organizer')));
    }
    
    /**
     * Handle AJAX request to fix missing stream slugs
     */
    public static function ajax_fix_missing_slugs() {
        check_ajax_referer('oo_fix_missing_slugs_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        // Use the slug regeneration utility
        if (!class_exists('OO_Stream_Slug_Regeneration')) {
            wp_send_json_error(array('message' => __('Slug regeneration utility not available.', 'operations-organizer')));
            return;
        }
        
        $results = OO_Stream_Slug_Regeneration::fix_missing_slugs();
        
        if ($results['fixed'] > 0) {
            $message = sprintf(
                __('Successfully fixed %d stream slug(s) out of %d that needed fixing.', 'operations-organizer'),
                $results['fixed'],
                $results['total_found']
            );
            
            if (!empty($results['errors'])) {
                $message .= ' ' . __('Some errors occurred:', 'operations-organizer') . ' ' . implode('; ', $results['errors']);
            }
            
            wp_send_json_success(array(
                'message' => $message,
                'results' => $results
            ));
        } else if ($results['total_found'] === 0) {
            wp_send_json_success(array(
                'message' => __('All streams already have valid slugs. No fixes needed.', 'operations-organizer'),
                'results' => $results
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to fix stream slugs.', 'operations-organizer') . ' ' . implode('; ', $results['errors']),
                'results' => $results
            ));
        }
    }
} 