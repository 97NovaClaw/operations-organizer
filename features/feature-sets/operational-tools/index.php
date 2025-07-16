<?php
/*
Feature Set: Operational Tools

This is the default feature set that provides core operational functionality
for streams. It includes tools for inventory management, job tracking, 
quality control, and basic reporting.

operational-tools/
├── index.php              # Entry point and hook registration
├── views/
│   ├── inventory-tab.php  # Inventory management interface
│   ├── tracking-tab.php   # Job tracking tools
│   ├── quality-tab.php    # Quality control interface
│   └── reports-tab.php    # Basic reporting tools
└── assets/
    ├── css/
    │   └── operational-tools.css
    └── js/
        └── operational-tools.js
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Feature_Set_Operational_Tools {
    
    public static function init() {
        // Register the content renderer for this feature set
        oo_register_feature_set_renderer('operational_tools', array(__CLASS__, 'render_content'));
        
        // Enqueue assets when needed
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }
    
    public static function render_content($stream, $feature_set) {
        // Include the main view
        include_once __DIR__ . '/views/main-view.php';
    }
    
    public static function enqueue_assets($hook) {
        // Only enqueue on stream dashboard pages
        if (strpos($hook, 'oo_stream_') === false) {
            return;
        }
        
        // Check if this feature set is being displayed
        $active_tab = isset($_GET['sub_tab']) ? sanitize_key($_GET['sub_tab']) : '';
        if ($active_tab !== 'feature_set_operational_tools') {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'oo-operational-tools', 
            OO_PLUGIN_URL . 'features/feature-sets/operational-tools/assets/css/operational-tools.css',
            array(),
            OO_PLUGIN_VERSION
        );
        
        // Enqueue JS
        wp_enqueue_script(
            'oo-operational-tools',
            OO_PLUGIN_URL . 'features/feature-sets/operational-tools/assets/js/operational-tools.js',
            array('jquery'),
            OO_PLUGIN_VERSION,
            true
        );
        
        // Localize script with data
        wp_localize_script('oo-operational-tools', 'oo_operational_tools_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('oo_operational_tools_nonce'),
            'stream_id' => isset($_GET['stream_id']) ? intval($_GET['stream_id']) : 0,
            'strings' => array(
                'loading' => __('Loading...', 'operations-organizer'),
                'error' => __('An error occurred', 'operations-organizer'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'operations-organizer')
            )
        ));
    }
}

// Initialize the feature set
OO_Feature_Set_Operational_Tools::init(); 