<?php
/**
 * Plugin Name:       Operations Organizer
 * Plugin URI:        https://legworkmedia.ca/
 * Description:       Track job phases, employee KPIs, and stream-specific operational data.
 * Version:           1.4.5.4
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Legwork Media
 * Author URI:        https://legworkmedia.ca/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       operations-organizer
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants with new prefix
define( 'OO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OO_PLUGIN_FILE', __FILE__ ); // Define the main plugin file path
define( 'OO_PLUGIN_VERSION', '1.4.5.4' ); // Updated plugin version constant

// Include core files (will be renamed)
require_once OO_PLUGIN_DIR . 'includes/class-oo-db.php';
require_once OO_PLUGIN_DIR . 'includes/class-oo-employee.php';
require_once OO_PLUGIN_DIR . 'includes/class-oo-job.php';
require_once OO_PLUGIN_DIR . 'includes/class-oo-phase.php';
require_once OO_PLUGIN_DIR . 'includes/class-oo-stream.php'; // Renamed class for Streams
require_once OO_PLUGIN_DIR . 'includes/class-oo-admin-pages.php';
require_once OO_PLUGIN_DIR . 'includes/class-oo-dashboard.php';
require_once OO_PLUGIN_DIR . 'includes/functions.php'; // Functions will also be prefixed
require_once OO_PLUGIN_DIR . 'fix-database.php'; // Load database fix utilities

// Activation hook
register_activation_hook( OO_PLUGIN_FILE, 'oo_activate_plugin' );
function oo_activate_plugin() {
    // Create database tables
    OO_DB::create_tables();
    
    // Initialize hardcoded streams
    initialize_hardcoded_streams();
    
    // Set a transient for activation redirect (e.g., to a welcome page or dashboard)
    set_transient('oo_activated', true, 30);
    
    // Hook for post-database creation actions
    do_action('oo_after_db_create');
}

// Plugin upgrade routine
add_action( 'plugins_loaded', 'oo_plugin_update_check' );
function oo_plugin_update_check() {
    $current_db_version = get_option( 'oo_plugin_db_version', '0' );
    if ( version_compare( $current_db_version, OO_PLUGIN_VERSION, '<' ) ) {
        // If the DB version is older than the plugin version, run create_tables
        OO_DB::init(); // Ensure DB class is initialized with table names
        OO_DB::create_tables();
        update_option( 'oo_plugin_db_version', OO_PLUGIN_VERSION );
        // Optionally, add an admin notice about the update
        // add_action( 'admin_notices', function() {
        //     echo '<div class="notice notice-success is-dismissible"><p>Operations Organizer database has been updated to version ' . OO_PLUGIN_VERSION . '.</p></div>';
        // });
    }
}

// Instantiate classes and hook into WordPress
if ( is_admin() ) {
    $oo_admin_pages = new OO_Admin_Pages();
    add_action( 'admin_menu', array( $oo_admin_pages, 'add_admin_menu_pages' ) );

    // Enqueue admin scripts and styles
    add_action( 'admin_enqueue_scripts', 'oo_enqueue_admin_assets' );
    function oo_enqueue_admin_assets($hook_suffix) {
        global $oo_admin_pages; // Relies on $oo_admin_pages being instantiated above
        $plugin_page_hooks = array();
        if ($oo_admin_pages && method_exists($oo_admin_pages, 'get_admin_page_hooks')) {
            $plugin_page_hooks = $oo_admin_pages->get_admin_page_hooks();
        }
        // Fallback or additional checks for specific pages if get_admin_page_hooks is not comprehensive yet
        $is_oo_page = false;
        if (in_array($hook_suffix, $plugin_page_hooks)) {
            $is_oo_page = true;
        } else {
            // Check for our specific page slugs if not caught by hooks (e.g. for pages added with null parent)
            $page_query_var = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
            if (strpos($page_query_var, 'oo_') === 0) {
                $is_oo_page = true;
            }
        }

        if ( $is_oo_page ) {
            wp_enqueue_style( 'oo-admin-styles', OO_PLUGIN_URL . 'admin/css/admin-styles.css', array(), OO_PLUGIN_VERSION );
            wp_enqueue_script( 'oo-admin-scripts', OO_PLUGIN_URL . 'admin/js/admin-scripts.js', array( 'jquery', 'jquery-ui-datepicker' ), OO_PLUGIN_VERSION, true );
            
            $localized_data = array(
                'ajax_url'        => admin_url( 'admin-ajax.php' ),
                'admin_url'       => admin_url(),
                'dashboard_url'   => admin_url( 'admin.php?page=oo_dashboard' ),
                'nonce_add_employee' => wp_create_nonce('oo_add_employee_nonce'),
                'nonce_edit_employee' => wp_create_nonce('oo_edit_employee_nonce'),
                'nonce_toggle_status' => wp_create_nonce('oo_toggle_status_nonce'), // General toggle, might be overridden by specific ones
                'nonce_add_phase'     => wp_create_nonce('oo_add_phase_nonce'),
                'nonce_edit_phase'    => wp_create_nonce('oo_edit_phase_nonce'),
                'nonce_add_stream' => wp_create_nonce('oo_add_stream_nonce'), 
                'nonce_edit_stream' => wp_create_nonce('oo_edit_stream_nonce'),
                'nonce_edit_log'      => wp_create_nonce('oo_edit_log_nonce'),      
                'nonce_delete_log'    => wp_create_nonce('oo_delete_log_nonce'),
                'nonce_dashboard'     => wp_create_nonce('oo_dashboard_nonce'),
                'nonce_get_kpi_measures' => wp_create_nonce('oo_get_kpi_measures_nonce'), // Used for ajax_get_kpi_measures_for_stream_html
                'nonce_get_phase_kpi_links' => wp_create_nonce('oo_get_phase_kpi_links_nonce'),
                'nonce_manage_phase_kpi_links' => wp_create_nonce('oo_manage_phase_kpi_links_nonce'),
                'nonce_get_derived_kpi_details' => wp_create_nonce('oo_get_derived_kpi_details_nonce'),
                'nonce_get_current_site_time' => wp_create_nonce('oo_get_current_site_time_nonce'),
                'nonce_delete_phase_ajax' => wp_create_nonce('oo_delete_phase_ajax_nonce'),
                'nonce_save_column_prefs' => wp_create_nonce('oo_save_column_prefs_nonce'),
                'nonce_get_stream_jobs' => wp_create_nonce('oo_get_stream_jobs_nonce'),
                
                // Nonces for Stream Page KPI Management
                'nonce_add_kpi_measure' => wp_create_nonce('oo_add_kpi_measure_nonce'),
                'nonce_edit_kpi_measure' => wp_create_nonce('oo_edit_kpi_measure_nonce'),
                'nonce_get_kpi_measure_details' => wp_create_nonce('oo_get_kpi_measure_details_nonce'),
                'nonce_toggle_kpi_status' => wp_create_nonce('oo_toggle_kpi_measure_status_nonce'), 
                'nonce_delete_kpi_measure' => wp_create_nonce('oo_delete_kpi_measure_nonce'),
                'nonce_get_kpi_measures_for_stream' => wp_create_nonce('oo_get_kpi_measures_for_stream_nonce'),

                // Nonces for Stream Page Derived KPI Management
                'nonce_add_derived_kpi' => wp_create_nonce('oo_add_derived_kpi_nonce'),
                'nonce_edit_derived_kpi' => wp_create_nonce('oo_update_derived_kpi_nonce'),
                'nonce_get_derived_kpi_details' => wp_create_nonce('oo_get_derived_kpi_details_stream_nonce'),
                'nonce_toggle_derived_kpi_status' => wp_create_nonce('oo_toggle_derived_kpi_status_nonce'),
                'nonce_delete_derived_kpi' => wp_create_nonce('oo_delete_derived_kpi_nonce'),
                'nonce_get_derived_kpis_for_stream_html' => wp_create_nonce('oo_get_derived_kpis_for_stream_html_nonce'),
                
                // Column Preferences Nonce
                'nonce_save_user_meta' => wp_create_nonce('oo_save_user_meta_nonce'),
                'nonce_get_user_meta' => wp_create_nonce('oo_get_user_meta_nonce'),

                'text_please_select_employee' => __('Please select an employee.', 'operations-organizer'),
                'text_please_enter_emp_no' => __('Please enter an employee number.', 'operations-organizer'),
                'text_add_derived_kpi' => __( 'Add Derived Calculation', 'operations-organizer' ),
                'text_edit_derived_kpi' => __( 'Edit Derived Calculation', 'operations-organizer' ),
                'text_select_calculation_type' => __( '-- Select Calculation Type --', 'operations-organizer' ),
                'text_select_secondary_kpi' => __( '-- Select Secondary KPI --', 'operations-organizer' ),
                'text_saving' => __( 'Saving...', 'operations-organizer' ),
                'text_error_generic' => __( 'An error occurred.', 'operations-organizer' ),
                'text_error_ajax' => __( 'AJAX request failed.', 'operations-organizer' ),
                'text_kpi_values' => __( 'KPI Values', 'operations-organizer' ),
                'all_kpi_measures' => OO_DB::get_kpi_measures(array('is_active' => 1, 'number' => -1)),
                'user_content_default_columns' => get_user_meta(get_current_user_id(), 'oo_content_dashboard_columns', true) ?: array(),
                'user_stream_default_columns' => array(), // Default to empty, will be populated below for specific stream pages
                'nonces' => array(), // Placeholder for dynamically generated nonces if needed later by JS
                'nonce_get_phases' => wp_create_nonce('oo_get_phases_nonce'), // Nonce for getting phases for a stream
                'current_stream_tab_slug' => '' // Placeholder for current stream tab slug
            );

            // If on a stream page, load the specific column preferences for that stream
            if (isset($_GET['page']) && strpos($_GET['page'], 'oo_stream_') === 0) {
                $page_param = sanitize_key($_GET['page']);
                $stream_name = str_replace('oo_stream_', '', $page_param);

                // Add the stream data for our new JS file
                $localized_data['current_stream_tab_slug'] = $stream_name;
                
                // Get the Stream ID from the configuration saved in OO_Admin_Pages
                $stream_configs = OO_Admin_Pages::get_stream_page_configs_for_redirect();
                $current_stream_id = null;
                foreach($stream_configs as $id => $config) {
                    if ($config['slug'] === $page_param) {
                        $current_stream_id = $id;
                        break;
                    }
                }
                $localized_data['current_stream_id'] = $current_stream_id;

                // To get the correct 'tab_slug', we need to look up the stream config.
                // This is a bit tricky here. A simpler, more robust way is to ensure the JS and PHP use the same source for the slug.
                // The JS uses `esc_js($current_stream_tab_slug)`. In this file, we don't have that global yet.
                // The most reliable key is just using the stream name from the URL parameter.
                $meta_key = 'oo_stream_dashboard_columns_' . $stream_name;
                $user_stream_columns = get_user_meta(get_current_user_id(), $meta_key, true);
                
                if (!empty($user_stream_columns) && is_array($user_stream_columns)) {
                    $localized_data['user_stream_default_columns'] = $user_stream_columns;
                }

                // Enqueue the new feature-specific script for the Stream Dashboard
                wp_enqueue_script(
                    'oo-stream-dashboard-script',
                    OO_PLUGIN_URL . 'features/stream-dashboard/assets/js/main.js',
                    array('oo-admin-scripts', 'jquery-ui-dialog'), // Ensure it depends on main scripts
                    OO_PLUGIN_VERSION,
                    true
                );
            }

            // Dynamically generate nonces for individual KPI measure toggle/delete actions if needed by JS
            // This is an example if we decide to pre-generate all possible nonces.
            // However, the current JS for toggle/delete relies on nonce_action_check passed to PHP, 
            // and PHP creating those specific nonces for buttons or oo_data.nonces if populated here.
            // For now, the AJAX handlers in OO_KPI_Measure use check_ajax_referer with the specific nonce action string passed from JS.
            // The $localized_data['nonces'] array can be populated with specific nonces if a different strategy is chosen.
            // Example: if $all_kpis = OO_DB::get_kpi_measures(); foreach($all_kpis as $kpi){ $localized_data['nonces']['oo_toggle_kpi_measure_status_'.$kpi->kpi_measure_id] = wp_create_nonce('oo_toggle_kpi_measure_status_'.$kpi->kpi_measure_id); }

            wp_localize_script( 'oo-admin-scripts', 'oo_data', $localized_data );

            wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), '1.13.6', true);
            wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
        }
    }
}

// Load plugin textdomain for internationalization
add_action( 'plugins_loaded', 'oo_load_textdomain' );
function oo_load_textdomain() {
    load_plugin_textdomain( 'operations-organizer', false, dirname( plugin_basename( OO_PLUGIN_FILE ) ) . '/languages/' );
}

// Register AJAX handlers with new OO_ prefixes
add_action('wp_ajax_oo_start_job_action', array('OO_Admin_Pages', 'handle_start_job_form'));
add_action('wp_ajax_oo_stop_job_action', array('OO_Admin_Pages', 'handle_stop_job_form'));

add_action('wp_ajax_oo_add_employee', array('OO_Employee', 'ajax_add_employee'));
add_action('wp_ajax_oo_get_employee', array('OO_Employee', 'ajax_get_employee'));
add_action('wp_ajax_oo_update_employee', array('OO_Employee', 'ajax_update_employee'));
add_action('wp_ajax_oo_toggle_employee_status', array('OO_Employee', 'ajax_toggle_employee_status'));

add_action('wp_ajax_oo_add_phase', array('OO_Phase', 'ajax_add_phase'));
add_action('wp_ajax_oo_get_phase', array('OO_Phase', 'ajax_get_phase'));
add_action('wp_ajax_oo_update_phase', array('OO_Phase', 'ajax_update_phase'));
add_action('wp_ajax_oo_toggle_phase_status', array('OO_Phase', 'ajax_toggle_phase_status'));

add_action('wp_ajax_oo_add_stream', array('OO_Stream', 'ajax_add_stream'));
add_action('wp_ajax_oo_get_stream', array('OO_Stream', 'ajax_get_stream'));
add_action('wp_ajax_oo_update_stream', array('OO_Stream', 'ajax_update_stream'));
add_action('wp_ajax_oo_toggle_stream_status', array('OO_Stream', 'ajax_toggle_stream_status'));

add_action('wp_ajax_oo_get_dashboard_data', array('OO_Dashboard', 'ajax_get_dashboard_data'));
add_action('wp_ajax_oo_get_job_log_details', array('OO_Dashboard', 'ajax_get_job_log_details'));
add_action('wp_ajax_oo_update_job_log', array('OO_Dashboard', 'ajax_update_job_log'));
add_action('wp_ajax_oo_delete_job_log', array('OO_Dashboard', 'ajax_delete_job_log'));

// Phase KPI Link AJAX Actions (NEW)
add_action('wp_ajax_oo_get_phase_kpi_links', array('OO_Phase', 'ajax_get_phase_kpi_links'));
add_action('wp_ajax_oo_add_phase_kpi_link', array('OO_Phase', 'ajax_add_phase_kpi_link'));
add_action('wp_ajax_oo_update_phase_kpi_link', array('OO_Phase', 'ajax_update_phase_kpi_link'));
add_action('wp_ajax_oo_delete_phase_kpi_link', array('OO_Phase', 'ajax_delete_phase_kpi_link'));
add_action('wp_ajax_oo_save_phase_kpi_links', array('OO_Phase', 'ajax_save_phase_kpi_links'));
add_action('wp_ajax_oo_get_kpi_measures', array('OO_Phase', 'ajax_get_kpi_measures'));
add_action('wp_ajax_oo_get_kpis_for_phase_form', array('OO_Phase', 'ajax_get_kpis_for_phase_form'));

// AJAX for Derived KPI Definitions (NEW)
add_action('wp_ajax_oo_get_derived_kpi_definition_details', array('OO_Admin_Pages', 'ajax_get_derived_kpi_definition_details'));
// AJAX for getting current site time (NEW)
add_action('wp_ajax_oo_get_current_site_time', array('OO_Admin_Pages', 'ajax_get_current_site_time'));
// AJAX for deleting a phase (NEW)
add_action('wp_ajax_oo_delete_phase_ajax', array('OO_Phase', 'ajax_delete_phase'));
// AJAX for saving user column preferences (NEW)
add_action('wp_ajax_oo_save_user_column_preference', array('OO_Admin_Pages', 'ajax_save_user_column_preference'));
// AJAX for getting jobs for a stream (NEW)
add_action('wp_ajax_oo_get_stream_jobs', array('OO_Admin_Pages', 'ajax_get_stream_jobs'));

// New AJAX handlers for phase linking in KPI modals
add_action('wp_ajax_oo_get_phases_for_stream', array('OO_Phase', 'ajax_get_phases_for_stream'));
add_action('wp_ajax_oo_get_phase_links_for_kpi_in_stream', array('OO_Phase', 'ajax_get_phase_links_for_kpi_in_stream'));

/**
 * Initialize hardcoded streams in the database.
 * This ensures the 4 specific streams are in the database as specified.
 */
function initialize_hardcoded_streams() {
    // Get the hardcoded stream definitions
    $streams = oo_get_hardcoded_streams();
    
    // For each hardcoded stream, check if it exists and add if needed
    foreach ($streams as $stream) {
        $existing_stream = OO_DB::get_stream($stream->stream_id);
        
        if (!$existing_stream) {
            // Stream doesn't exist with this ID, try to add it
            $result = OO_DB::add_stream(
                $stream->stream_name,
                $stream->stream_description,
                $stream->is_active
            );
            
            if (is_wp_error($result)) {
                // Log the error if logging function exists
                if (function_exists('oo_log')) {
                    oo_log('Error creating hardcoded stream: ' . $result->get_error_message(), 'initialize_hardcoded_streams');
                }
            }
        } else {
            // Stream exists, but ensure name and description match
            OO_DB::update_stream(
                $stream->stream_id,
                $stream->stream_name,
                $stream->stream_description,
                $stream->is_active
            );
        }
    }
} 