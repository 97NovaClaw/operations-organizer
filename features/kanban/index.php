<?php
/*
Feature Tree: Kanban Board

kanban/
├── index.php             # Entry point, hooks, and asset loading
├── ajax.php              # AJAX handler for phase changes
├── schema.php            # Database table definitions
├── migration.php         # Database migration logic
├── database.php          # Database operations specific to kanban
└── views/
    └── board-view.php    # Kanban board UI template
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Kanban Feature Module
 * 
 * Provides drag-and-drop Kanban board functionality for tracking
 * jobs through stream phases with full activity logging
 */
class OO_Kanban_Feature {
    
    /**
     * Initialize the Kanban feature
     */
    public static function init() {
        error_log('KANBAN DEBUG: OO_Kanban_Feature::init() is running.');

        // Include dependencies
        require_once __DIR__ . '/schema.php';
        require_once __DIR__ . '/migration.php';
        require_once __DIR__ . '/database.php';
        require_once __DIR__ . '/ajax.php';
        
        // Run migrations
        self::run_migration();
        
        // Register AJAX handlers
        OO_Kanban_AJAX::init();
        
        // Hook into stream dashboard to render Kanban in Phase Dashboard tab
        add_action('oo_render_stream_kanban', array(__CLASS__, 'render_stream_kanban'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));

        error_log('KANBAN DEBUG: oo_render_stream_kanban action has been added.');
    }
    
    /**
     * Render Kanban board for stream dashboard
     */
    public static function render_stream_kanban($stream_id, $stream_slug) {
        // Get all jobs for this stream with their current phases
        global $wpdb;
        $job_streams_table = $wpdb->prefix . 'oo_job_streams_link';
        $jobs_table = $wpdb->prefix . 'oo_jobs';
        
        // Get all job streams for this stream
        $job_streams = $wpdb->get_results($wpdb->prepare("
            SELECT js.*, j.job_number, j.client_name, j.overall_status
            FROM {$job_streams_table} js
            INNER JOIN {$jobs_table} j ON js.job_id = j.job_id
            WHERE js.stream_id = %d
            ORDER BY j.job_number DESC
        ", $stream_id));
        
        // Include the Kanban board view
        include __DIR__ . '/views/stream-kanban-view.php';
    }
    
    /**
     * Enqueue JavaScript and CSS assets
     */
    public static function enqueue_assets($hook) {
        // Only load on relevant pages
        if (!isset($_GET['page']) || strpos($_GET['page'], 'oo_') !== 0) {
            return;
        }
        
        // Check if we're on a stream dashboard page with phase_dashboard sub_tab
        $is_stream_page = strpos($_GET['page'], 'oo_stream_') === 0;
        $is_phase_dashboard = isset($_GET['sub_tab']) && $_GET['sub_tab'] === 'phase_dashboard';
        
        if ($is_stream_page && ($is_phase_dashboard || !isset($_GET['sub_tab']))) {
            // Enqueue CSS
            wp_enqueue_style(
                'oo-kanban-styles',
                OO_PLUGIN_URL . 'assets/css/features/kanban/kanban.css',
                array(),
                OO_PLUGIN_VERSION
            );
            
            // Enqueue JavaScript
            wp_enqueue_script(
                'oo-kanban-script',
                OO_PLUGIN_URL . 'assets/js/features/kanban/main.js',
                array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'),
                OO_PLUGIN_VERSION,
                true
            );
            
            // Get stream info from page parameter
            $stream_slug = str_replace('oo_stream_', '', $_GET['page']);
            
            // Localize script with necessary data
            wp_localize_script('oo-kanban-script', 'oo_kanban_data', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('oo_kanban_phase_change_nonce'),
                'stream_slug' => $stream_slug,
                'strings' => array(
                    'error_updating' => __('Error: Could not update phase.', 'operations-organizer'),
                    'server_error' => __('A server error occurred.', 'operations-organizer'),
                    'confirm_move' => __('Are you sure you want to move this job to {phase}?', 'operations-organizer')
                )
            ));
        }
    }

    /**
     * Run database migration
     */
    private static function run_migration() {
        // Implementation of run_migration method
    }
}

// Initialize the feature
OO_Kanban_Feature::init(); 