<?php
/**
 * Plugin Name:       Operations Organizer
 * Plugin URI:        https://legworkmedia.ca/
 * Description:       Track job phases, employee KPIs, and stream-specific operational data.
 * Version:           1.4.4.0
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
define( 'OO_PLUGIN_VERSION', '1.4.4.0' ); // Added plugin version constant

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
            wp_enqueue_style( 'oo-admin-styles', OO_PLUGIN_URL . 'admin/css/admin-styles.css', array(), '1.4.2.3' );
            wp_enqueue_script( 'oo-admin-scripts', OO_PLUGIN_URL . 'admin/js/admin-scripts.js', array( 'jquery', 'jquery-ui-datepicker' ), '1.4.2.3', true );
            
            $localized_data = array(
                'ajax_url'        => admin_url( 'admin-ajax.php' ),
                'admin_url'       => admin_url(),
                'dashboard_url'   => admin_url( 'admin.php?page=oo_dashboard' ),
                'nonce_add_employee' => wp_create_nonce('oo_add_employee_nonce'),
                'nonce_edit_employee' => wp_create_nonce('oo_edit_employee_nonce'),
                'nonce_toggle_status' => wp_create_nonce('oo_toggle_status_nonce'),
                'nonce_add_phase'     => wp_create_nonce('oo_add_phase_nonce'),
                'nonce_edit_phase'    => wp_create_nonce('oo_edit_phase_nonce'),
                'nonce_add_stream' => wp_create_nonce('oo_add_stream_nonce'), 
                'nonce_edit_stream' => wp_create_nonce('oo_edit_stream_nonce'),
                'nonce_edit_log'      => wp_create_nonce('oo_edit_log_nonce'),      
                'nonce_delete_log'    => wp_create_nonce('oo_delete_log_nonce'),
                'nonce_dashboard'     => wp_create_nonce('oo_dashboard_nonce'),
                'nonce_get_phase_kpi_links' => wp_create_nonce('oo_get_phase_kpi_links_nonce'),
                'nonce_manage_phase_kpi_links' => wp_create_nonce('oo_manage_phase_kpi_links_nonce'),
                'nonce_get_derived_kpi_details' => wp_create_nonce('oo_get_derived_kpi_details_nonce'),
                'text_please_select_employee' => __('Please select an employee.', 'operations-organizer'),
                'text_please_enter_emp_no' => __('Please enter an employee number.', 'operations-organizer'),
                'text_add_derived_kpi' => __( 'Add Derived Calculation', 'operations-organizer' ),
                'text_edit_derived_kpi' => __( 'Edit Derived Calculation', 'operations-organizer' ),
                'text_select_calculation_type' => __( '-- Select Calculation Type --', 'operations-organizer' ),
                'text_select_secondary_kpi' => __( '-- Select Secondary KPI --', 'operations-organizer' ),
                'text_saving' => __( 'Saving...', 'operations-organizer' ),
                'text_error_generic' => __( 'An error occurred.', 'operations-organizer' ),
                'text_error_ajax' => __( 'AJAX request failed.', 'operations-organizer' ),
                'all_kpi_measures' => OO_DB::get_kpi_measures(array('is_active' => 1))
            );
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

// TODO: Update admin menu registration with new structure and OO_Admin_Menus class 

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