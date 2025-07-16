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
        
        if (empty($name)) {
            wp_send_json_error(array('message' => __('Feature set name is required.', 'operations-organizer')));
            return;
        }
        
        $result = OO_DB::add_feature_set($name, '', $description, 1, $sort_order);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
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
} 