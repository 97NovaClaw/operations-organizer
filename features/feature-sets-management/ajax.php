<?php
// /features/feature-sets-management/ajax.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Feature_Sets_Management_AJAX {
    
    public static function init() {
        // Register AJAX handlers
        add_action('wp_ajax_oo_add_feature_set', array(__CLASS__, 'ajax_add_feature_set'));
        add_action('wp_ajax_oo_get_feature_set', array(__CLASS__, 'ajax_get_feature_set'));
        add_action('wp_ajax_oo_update_feature_set', array(__CLASS__, 'ajax_update_feature_set'));
        add_action('wp_ajax_oo_toggle_feature_set_status', array(__CLASS__, 'ajax_toggle_feature_set_status'));
        add_action('wp_ajax_oo_delete_feature_set', array(__CLASS__, 'ajax_delete_feature_set'));
        add_action('wp_ajax_oo_run_feature_sets_migration', array(__CLASS__, 'ajax_run_feature_sets_migration'));
        add_action('wp_ajax_oo_get_feature_set_stream_assignments', array(__CLASS__, 'ajax_get_feature_set_stream_assignments'));
    }
    
    public static function ajax_add_feature_set() {
        check_ajax_referer('oo_add_feature_set_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
        $assign_to_streams = isset($_POST['assign_to_streams']) && is_array($_POST['assign_to_streams']) ? array_map('intval', $_POST['assign_to_streams']) : array();
        
        if (empty($name)) {
            wp_send_json_error(array('message' => __('Feature set name is required.', 'operations-organizer')));
            return;
        }
        
        // Generate slug from name if not provided
        $slug = sanitize_key($name);
        $result = OO_DB::add_feature_set($name, $slug, $description, 1, $sort_order);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Assign to selected streams
        $feature_set_id = $result;
        foreach ($assign_to_streams as $stream_id) {
            $assignment_result = OO_DB::assign_feature_set_to_stream($stream_id, $feature_set_id);
            if (is_wp_error($assignment_result)) {
                // Log error but don't fail the whole operation
                error_log('Failed to assign feature set to stream: ' . $assignment_result->get_error_message());
            }
        }
        
        wp_send_json_success(array('message' => __('Feature set added successfully.', 'operations-organizer')));
    }
    
    public static function ajax_get_feature_set() {
        check_ajax_referer('oo_get_feature_set_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $feature_set_id = isset($_POST['feature_set_id']) ? intval($_POST['feature_set_id']) : 0;
        
        if ($feature_set_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid feature set ID.', 'operations-organizer')));
            return;
        }
        
        $feature_set = OO_DB::get_feature_set($feature_set_id);
        
        if (!$feature_set) {
            wp_send_json_error(array('message' => __('Feature set not found.', 'operations-organizer')));
            return;
        }
        
        wp_send_json_success(array('feature_set' => $feature_set));
    }
    
    public static function ajax_update_feature_set() {
        check_ajax_referer('oo_update_feature_set_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $feature_set_id = isset($_POST['feature_set_id']) ? intval($_POST['feature_set_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
        $assign_to_streams = isset($_POST['assign_to_streams']) && is_array($_POST['assign_to_streams']) ? array_map('intval', $_POST['assign_to_streams']) : array();
        
        if ($feature_set_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid feature set ID.', 'operations-organizer')));
            return;
        }
        
        if (empty($name)) {
            wp_send_json_error(array('message' => __('Feature set name is required.', 'operations-organizer')));
            return;
        }
        
        $result = OO_DB::update_feature_set($feature_set_id, $name, $description, 1, $sort_order);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Update stream assignments
        // First, get current assignments
        $current_assignments = OO_DB::get_streams_for_feature_set($feature_set_id, 1);
        $current_stream_ids = array_map(function($stream) { return $stream->stream_id; }, $current_assignments);
        
        // Remove assignments that are no longer selected
        foreach ($current_stream_ids as $current_stream_id) {
            if (!in_array($current_stream_id, $assign_to_streams)) {
                OO_DB::remove_feature_set_from_stream($current_stream_id, $feature_set_id);
            }
        }
        
        // Add new assignments
        foreach ($assign_to_streams as $stream_id) {
            if (!in_array($stream_id, $current_stream_ids)) {
                $assignment_result = OO_DB::assign_feature_set_to_stream($stream_id, $feature_set_id);
                if (is_wp_error($assignment_result)) {
                    error_log('Failed to assign feature set to stream: ' . $assignment_result->get_error_message());
                }
            }
        }
        
        wp_send_json_success(array('message' => __('Feature set updated successfully.', 'operations-organizer')));
    }
    
    public static function ajax_toggle_feature_set_status() {
        check_ajax_referer('oo_toggle_feature_set_status_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $feature_set_id = isset($_POST['feature_set_id']) ? intval($_POST['feature_set_id']) : 0;
        $new_status = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
        
        if ($feature_set_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid feature set ID.', 'operations-organizer')));
            return;
        }
        
        $result = OO_DB::toggle_feature_set_status($feature_set_id, $new_status);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array('message' => __('Feature set status updated successfully.', 'operations-organizer')));
    }
    
    public static function ajax_delete_feature_set() {
        check_ajax_referer('oo_delete_feature_set_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $feature_set_id = isset($_POST['feature_set_id']) ? intval($_POST['feature_set_id']) : 0;
        
        if ($feature_set_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid feature set ID.', 'operations-organizer')));
            return;
        }
        
        $result = OO_DB::delete_feature_set($feature_set_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array('message' => __('Feature set deleted successfully.', 'operations-organizer')));
    }
    
    public static function ajax_run_feature_sets_migration() {
        check_ajax_referer('oo_run_feature_sets_migration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $result = OO_Feature_Sets_Migration::run_initial_migration();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success($result);
    }
    
    public static function ajax_get_feature_set_stream_assignments() {
        check_ajax_referer('oo_get_feature_set_stream_assignments_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(array('message' => __('Permission denied.', 'operations-organizer')), 403);
            return;
        }
        
        $feature_set_id = isset($_POST['feature_set_id']) ? intval($_POST['feature_set_id']) : 0;
        
        if ($feature_set_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid feature set ID.', 'operations-organizer')));
            return;
        }
        
        // Get all streams
        $all_streams = oo_get_streams(array('is_active' => 1));
        
        // Get streams assigned to this feature set
        $assigned_streams = OO_DB::get_streams_for_feature_set($feature_set_id, 1);
        
        wp_send_json_success(array(
            'all_streams' => $all_streams,
            'assigned_streams' => $assigned_streams
        ));
    }
} 