<?php
/*
Feature Tree: Stream Management

stream-management/
├── index.php              # Entry point and admin menu setup
├── ajax.php               # AJAX handlers for stream operations
├── form-handler.php       # Dynamic table creation and validation
└── views/
    └── management-page.php # UI for stream management
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Stream_Management {
    
    public static function init() {
        // Include required files
        require_once __DIR__ . '/ajax.php';
        require_once __DIR__ . '/form-handler.php';
        
        // Register admin menu
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        
        // Register AJAX handlers
        OO_Stream_Management_AJAX::init();
    }
    
    public static function add_admin_menu() {
        add_menu_page(
            __('Stream Management', 'operations-organizer'),
            __('Stream Management', 'operations-organizer'),
            oo_get_capability(),
            'oo_stream_management',
            array(__CLASS__, 'display_management_page'),
            'dashicons-networking',
            26 // Position after main plugin menus
        );
        
        // Add debug log viewer as submenu
        add_submenu_page(
            'oo_stream_management',
            __('Stream Debug Log', 'operations-organizer'),
            __('Debug Log', 'operations-organizer'),
            oo_get_capability(),
            'oo_stream_debug_log',
            array(__CLASS__, 'display_debug_log_page')
        );
    }
    
    public static function display_management_page() {
        if (!current_user_can(oo_get_capability())) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'operations-organizer'));
        }
        
        include_once __DIR__ . '/views/management-page.php';
    }
    
    public static function display_debug_log_page() {
        if (!current_user_can(oo_get_capability())) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'operations-organizer'));
        }
        
        // Include the debug log viewer
        $debug_viewer_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'debug/view-stream-debug-log.php';
        if (file_exists($debug_viewer_path)) {
            // The viewer handles its own output, so we just include it
            include_once $debug_viewer_path;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . __('Stream Debug Log', 'operations-organizer') . '</h1>';
            echo '<div class="notice notice-error"><p>' . __('Debug log viewer not found.', 'operations-organizer') . '</p></div>';
            echo '</div>';
        }
    }
}

// Initialize the feature
OO_Stream_Management::init(); 