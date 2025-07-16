<?php
/*
Feature Tree: Feature Sets Management

feature-sets-management/
├── index.php              # Entry point and admin menu setup
├── ajax.php               # AJAX handlers for feature set operations
├── form-handler.php       # Form processing and validation
└── views/
    └── management-page.php # UI for feature sets management
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Feature_Sets_Management {
    
    public static function init() {
        // Include required files
        require_once __DIR__ . '/ajax.php';
        require_once __DIR__ . '/form-handler.php';
        require_once __DIR__ . '/migration.php';
        
        // Register admin menu
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        
        // Register AJAX handlers
        OO_Feature_Sets_Management_AJAX::init();
    }
    
    public static function add_admin_menu() {
        add_menu_page(
            __('Feature Sets Management', 'operations-organizer'),
            __('Feature Sets', 'operations-organizer'),
            oo_get_capability(),
            'oo_feature_sets_management',
            array(__CLASS__, 'display_management_page'),
            'dashicons-admin-tools',
            27 // Position after stream management
        );
    }
    
    public static function display_management_page() {
        if (!current_user_can(oo_get_capability())) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'operations-organizer'));
        }
        
        include_once __DIR__ . '/views/management-page.php';
    }
}

// Initialize the feature
OO_Feature_Sets_Management::init(); 