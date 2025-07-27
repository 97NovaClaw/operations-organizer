<?php
/**
 * Table View Manager Feature
 * 
 * Provides user-customizable table views throughout the plugin
 * 
 * Feature Tree:
 * - Database: Custom table for storing user preferences
 * - AJAX: Handlers for saving/loading preferences
 * - Assets: JavaScript for modal and state management
 * - Integration: Works with DataTables on any page
 * 
 * @package OperationsOrganizer
 * @subpackage Features/TableViewManager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include required files
require_once __DIR__ . '/migration.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/ajax.php';

class OO_Table_View_Manager_Feature {
    
    /**
     * Initialize the feature
     */
    public static function init() {
        // Run migration on activation
        register_activation_hook(OO_PLUGIN_FILE, array(__CLASS__, 'activate'));
        
        // Initialize AJAX handlers
        add_action('init', array('OO_Table_View_Manager_AJAX', 'init'));
        
        // Enqueue assets on admin pages
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        
        // Add nonce to admin footer for AJAX
        add_action('admin_footer', array(__CLASS__, 'add_nonce_to_footer'));
    }
    
    /**
     * Activation hook - run migration
     */
    public static function activate() {
        OO_Table_View_Manager_Migration::run();
    }
    
    /**
     * Enqueue CSS and JavaScript assets
     */
    public static function enqueue_assets($hook) {
        // Debug log the hook
        error_log('[Table View Manager] Current admin page hook: ' . $hook);
        
        // Only load on pages that have customizable tables
        $allowed_pages = array(
            'oo_jobs_page_oo_job_details',      // Job details page
            'toplevel_page_oo_jobs',            // Jobs management page
            'toplevel_page_oo_stream_',         // Stream pages
            'job-tracker_page_oo_stream_',      // Alternative stream page hook
            'toplevel_page_oo_dashboard'        // Main dashboard
        );
        
        $load_assets = false;
        foreach ($allowed_pages as $page) {
            if (strpos($hook, $page) !== false) {
                $load_assets = true;
                break;
            }
        }
        
        if (!$load_assets) {
            error_log('[Table View Manager] Assets not loaded - hook "' . $hook . '" not in allowed pages');
            return;
        }
        
        error_log('[Table View Manager] Loading assets for hook: ' . $hook);
        
        // Enqueue jQuery UI Sortable (included in WordPress)
        wp_enqueue_script('jquery-ui-sortable');
        
        // Enqueue DataTables ColReorder extension
        wp_enqueue_script(
            'datatables-colreorder',
            'https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js',
            array('jquery', 'datatables'),
            '1.7.0',
            true
        );
        
        wp_enqueue_style(
            'datatables-colreorder',
            'https://cdn.datatables.net/colreorder/1.7.0/css/colReorder.dataTables.min.css',
            array('datatables'),
            '1.7.0'
        );
        
        // Enqueue our custom CSS
        wp_enqueue_style(
            'oo-table-view-manager',
            OO_PLUGIN_URL . 'features/table-view-manager/assets/css/table-view-manager.css',
            array(),
            OO_PLUGIN_VERSION
        );
        
        // Enqueue our custom JavaScript
        wp_enqueue_script(
            'oo-table-view-manager',
            OO_PLUGIN_URL . 'features/table-view-manager/assets/js/table-view-manager.js',
            array('jquery', 'jquery-ui-sortable', 'datatables', 'datatables-colreorder'),
            OO_PLUGIN_VERSION,
            true
        );
        
        // Localize script with necessary data
        self::localize_script();
    }
    
    /**
     * Localize JavaScript with user preferences and available columns
     */
    private static function localize_script() {
        $current_user_id = get_current_user_id();
        
        // Get all preferences for current user
        // In production, this would be filtered to only tables on current page
        $preferences = OO_Table_View_Manager_Database::get_preferences($current_user_id);
        
        // Prepare localization data
        $localize_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('table_view_manager_nonce'),
            'user_preferences' => $preferences,
            'default_column_limit' => 20,
            'strings' => array(
                'configure_view' => __('Configure View', 'operations-organizer'),
                'visibility' => __('Visibility', 'operations-organizer'),
                'order' => __('Order', 'operations-organizer'),
                'apply' => __('Apply', 'operations-organizer'),
                'cancel' => __('Cancel', 'operations-organizer'),
                'reset' => __('Reset to Default', 'operations-organizer'),
                'saving' => __('Saving...', 'operations-organizer'),
                'saved' => __('Preferences saved', 'operations-organizer'),
                'error' => __('Error saving preferences', 'operations-organizer'),
                'no_columns_selected' => __('Please select at least one column', 'operations-organizer'),
                'drag_to_reorder' => __('Drag to reorder', 'operations-organizer')
            )
        );
        
        wp_localize_script('oo-table-view-manager', 'ooTableViewManager', $localize_data);
    }
    
    /**
     * Add nonce to admin footer for AJAX requests
     */
    public static function add_nonce_to_footer() {
        ?>
        <script type="text/javascript">
            window.ooTableViewManagerNonce = '<?php echo wp_create_nonce('table_view_manager_nonce'); ?>';
        </script>
        <?php
    }
    
    /**
     * Get available columns for a specific table
     * This method would be called by other features to register their columns
     */
    public static function get_available_columns($table_id, $context = array()) {
        // This would be implemented to call appropriate functions based on table_id
        // For now, return empty array - individual features will implement this
        return apply_filters('oo_table_view_available_columns', array(), $table_id, $context);
    }
    
    /**
     * Helper method to generate consistent table IDs
     */
    public static function generate_table_id($page, $content, $dynamic_part = '') {
        $parts = array($page, $content);
        if (!empty($dynamic_part)) {
            $parts[] = $dynamic_part;
        }
        return implode('_', $parts);
    }
} 