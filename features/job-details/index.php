<?php
/**
 * Job Details Feature
 * 
 * Provides a comprehensive single job view with all streams and details
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/*
Feature Tree: Job Details

job-details/
├── index.php          # Entry point
├── ajax.php           # AJAX handlers
├── views/
│   ├── main-view.php         # Main job details page
│   ├── tab-content-view.php  # Operational tools template (phase selector, logs, etc.)
│   └── stream-templates/     # DEPRECATED - No longer used

Stream Tab Display Logic (v1.5.3.12+):
1. Get feature sets assigned to the stream
2. If stream has "operational_tools" feature set, display tab-content-view.php
3. Render all other feature sets in designated areas
4. Use hooks for custom extensions

Available Hooks:
- oo_job_details_tab_start_{stream_slug} - Add content at tab beginning
- oo_job_details_tab_end_{stream_slug} - Add content at tab end
- oo_job_details_before_logs_{stream_slug} - Add content before logs (if operational tools enabled)
- oo_job_details_after_content_{stream_slug} - Add content after operational tools
- oo_job_details_feature_set_{feature_set_slug} - Fallback for feature set rendering

Key Benefits:
- Streams automatically inherit their feature sets configuration
- No manual template creation needed for new streams
- Consistent with stream dashboard pages
- Plugin update safe - no custom templates to maintain
*/

class OO_Job_Details_Feature {
    
    /**
     * Initialize the feature
     */
    public static function init() {
        // Include dependencies
        require_once __DIR__ . '/ajax.php';
        
        // Register AJAX handlers
        OO_Job_Details_AJAX::init();
        
        // Add admin menu
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'), 20);
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }
    
    /**
     * Add admin menu for job details
     */
    public static function add_admin_menu() {
        // Check if oo_get_capability function exists
        $capability = function_exists('oo_get_capability') ? oo_get_capability() : 'manage_options';
        
        // Add as a submenu under Jobs
        add_submenu_page(
            'oo_jobs', // Parent slug - Jobs page
            __('Job Details', 'operations-organizer'),
            __('Job Details', 'operations-organizer'),
            $capability,
            'oo_job_details',
            array(__CLASS__, 'render_job_details_page')
        );
    }
    
    /**
     * Render job details page
     */
    public static function render_job_details_page() {
        // Check permissions
        $capability = function_exists('oo_get_capability') ? oo_get_capability() : 'manage_options';
        if (!current_user_can($capability)) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'operations-organizer'));
        }
        
        // Get job ID from URL
        $job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
        
        if (!$job_id) {
            echo '<div class="wrap">';
            echo '<h1>' . __('Job Details', 'operations-organizer') . '</h1>';
            echo '<div class="notice notice-error"><p>' . __('No job ID provided. Please select a job from the jobs list.', 'operations-organizer') . '</p></div>';
            echo '<p><a href="' . esc_url(admin_url('admin.php?page=oo_jobs')) . '" class="button button-primary">' . __('Go to Jobs', 'operations-organizer') . '</a></p>';
            echo '</div>';
            return;
        }
        
        // Get job data
        $job = OO_DB::get_job($job_id);
        
        if (!$job) {
            echo '<div class="wrap">';
            echo '<h1>' . __('Job Details', 'operations-organizer') . '</h1>';
            echo '<div class="notice notice-error"><p>' . sprintf(__('Job #%d not found.', 'operations-organizer'), $job_id) . '</p></div>';
            echo '<p><a href="' . esc_url(admin_url('admin.php?page=oo_jobs')) . '" class="button button-primary">' . __('Go to Jobs', 'operations-organizer') . '</a></p>';
            echo '</div>';
            return;
        }
        
        // Get all streams for this job
        global $wpdb;
        $job_streams_table = $wpdb->prefix . 'oo_job_streams_link';
        $streams_table = $wpdb->prefix . 'oo_streams';
        
        $job_streams_query = $wpdb->prepare("
            SELECT js.*, s.stream_name, s.stream_slug
            FROM {$job_streams_table} js
            INNER JOIN {$streams_table} s ON js.stream_id = s.stream_id
            WHERE js.job_id = %d AND s.is_active = 1
            ORDER BY s.stream_name ASC
        ", $job_id);
        
        error_log('[JOB_DETAILS_DEBUG] Job streams query: ' . $job_streams_query);

        $job_streams = $wpdb->get_results($job_streams_query);

        if (empty($job_streams)) {
            echo '<p>' . esc_html__('No streams are currently assigned to this job.', 'operations-organizer') . '</p>';
            return;
        }
        
        // Include the main view
        include __DIR__ . '/views/main-view.php';
    }
    
    /**
     * Enqueue JavaScript and CSS assets
     */
    public static function enqueue_assets($hook) {
        // Only load on job details page
        if (!isset($_GET['page']) || $_GET['page'] !== 'oo_job_details') {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'oo-job-details-styles',
            OO_PLUGIN_URL . 'assets/css/features/job-details/styles.css',
            array(),
            OO_PLUGIN_VERSION
        );
        
        // Enqueue DataTables for advanced table functionality
        wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), '1.13.6', true);
        wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'oo-job-details-script',
            OO_PLUGIN_URL . 'assets/js/features/job-details/main.js',
            array('jquery', 'datatables'),
            OO_PLUGIN_VERSION,
            true
        );
        
        // Localize script with necessary data for job details
        wp_localize_script('oo-job-details-script', 'oo_job_details_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('oo_job_details_nonce'),
            'dashboard_nonce' => wp_create_nonce('oo_dashboard_nonce'),
            'job_id' => isset($_GET['job_id']) ? intval($_GET['job_id']) : 0,
            'strings' => array(
                'error_updating' => __('Error: Could not update phase.', 'operations-organizer'),
                'phase_updated' => __('Phase updated successfully.', 'operations-organizer'),
                'confirm_phase_change' => __('Are you sure you want to change the phase to {phase}?', 'operations-organizer')
            )
        ));
        
        // Also provide stream dashboard nonces for activity log compatibility
        wp_localize_script('oo-job-details-script', 'oo_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce_activity_log' => wp_create_nonce('oo_activity_log_nonce'),
            'admin_url' => admin_url()
        ));
    }
} 