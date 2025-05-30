<?php
// /includes/class-oo-admin-pages.php // Renamed file

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Admin_Pages { // Renamed class

    private $admin_page_hooks = array();
    private static $stream_pages_config = array(); // Store stream page slugs and names

    public function add_admin_menu_pages() {
        // Define streams (could also be fetched from DB or a config file)
        // For now, using the hardcoded streams from functions.php
        $streams = oo_get_hardcoded_streams(); // Ensure this function is available or define locally
        self::$stream_pages_config = array(); // Clear it for fresh population

        $settings_parent_slug = 'oo_operations_settings';

        // New Top-Level Menu for general settings and management
        $this->admin_page_hooks[] = add_menu_page(
            __( 'Operations Settings', 'operations-organizer' ),
            __( 'Settings', 'operations-organizer' ), // Shorter menu title
            oo_get_capability(), 
            $settings_parent_slug,
            array( $this, 'display_jobs_management_page_redirect' ), // Redirect to Jobs by default
            'dashicons-admin-settings', 
            26 // Position after default Operations/Streams
        );

        // Add Jobs management page under Settings
        $this->admin_page_hooks[] = add_submenu_page(
            $settings_parent_slug,
            __( 'Manage Jobs', 'operations-organizer' ),
            __( 'Jobs', 'operations-organizer' ),
            oo_get_capability(),
            'oo_jobs',
            array( 'OO_Job', 'display_job_management_page' )
        );
        
        // Employees management page under Settings
        $this->admin_page_hooks[] = add_submenu_page(
            $settings_parent_slug, 
            __( 'Manage Employees', 'operations-organizer' ),
            __( 'Employees', 'operations-organizer' ),
            oo_get_capability(), 
            'oo_employees',
            array( 'OO_Employee', 'display_employee_management_page' )
        );
        
        // Phases management page under Settings
        $this->admin_page_hooks[] = add_submenu_page(
            $settings_parent_slug, 
            __( 'Manage Phases', 'operations-organizer' ),
            __( 'Phases', 'operations-organizer' ),
            oo_get_capability(), 
            'oo_phases',
            array( 'OO_Phase', 'display_phase_management_page' )
        );
        
        // KPI Measure Management under Settings
        $this->admin_page_hooks[] = add_submenu_page(
            $settings_parent_slug,
            __('KPI Measure Definitions', 'operations-organizer'),
            __('KPI Definitions', 'operations-organizer'),
            oo_get_capability(),
            'oo_kpi_measures', 
            array($this, 'display_kpi_measure_management_page')
        );

        // Create Top-Level Menu Pages for each Stream
        $stream_menu_position = 20;
        foreach ($streams as $stream) {
            if (!$stream || !isset($stream->stream_id) || !isset($stream->stream_name)) continue;
            $page_slug = 'oo_stream_' . sanitize_key($stream->stream_name); // e.g., oo_stream_content
            self::$stream_pages_config[$stream->stream_id] = array(
                'slug' => $page_slug,
                'name' => $stream->stream_name,
                'tab_slug' => sanitize_key($stream->stream_name) // e.g. 'content' for return_tab
            );

            $this->admin_page_hooks[] = add_menu_page(
                sprintf(__( '%s Stream', 'operations-organizer' ), $stream->stream_name),
                $stream->stream_name, // Menu Title
                oo_get_capability(), 
                $page_slug,
                array( $this, 'display_single_stream_page' ), 
                'dashicons-chart-line', // Example icon, can be stream-specific if desired
                $stream_menu_position++ 
            );
        }
        
        // Hidden pages for Start/Stop forms
        $this->admin_page_hooks[] = add_submenu_page(
            null, 
            __( 'Start Job Phase', 'operations-organizer' ),
            __( 'Start Job Phase', 'operations-organizer' ),
            oo_get_form_access_capability(), 
            'oo_start_job',
            array( $this, 'display_start_job_form_page' )
        );

        $this->admin_page_hooks[] = add_submenu_page(
            null, 
            __( 'Stop Job Phase', 'operations-organizer' ),
            __( 'Stop Job Phase', 'operations-organizer' ),
            oo_get_form_access_capability(), 
            'oo_stop_job',
            array( $this, 'display_stop_job_form_page' )
        );
    }
    
    public function get_admin_page_hooks() {
        $actual_hooks = $this->admin_page_hooks;
        if (!in_array('admin_page_oo_start_job', $actual_hooks)) {
            $actual_hooks[] = 'admin_page_oo_start_job';
        }
        if (!in_array('admin_page_oo_stop_job', $actual_hooks)) {
            $actual_hooks[] = 'admin_page_oo_stop_job';
        }
        return $actual_hooks;
    }

    public function display_start_job_form_page() {
        if (!current_user_can(oo_get_form_access_capability())) { 
            wp_die(__( 'You do not have sufficient permissions to access this page.', 'operations-organizer' ));
        }
        // TODO: This form will need to handle stream_type to show correct phases and KPI fields.
        include_once OO_PLUGIN_DIR . 'admin/views/start-job-form-page.php'; // Use new constant
    }

    public function display_stop_job_form_page() {
        if (!current_user_can(oo_get_form_access_capability())) { 
            wp_die(__( 'You do not have sufficient permissions to access this page.', 'operations-organizer' ));
        }
        // TODO: This form will need to handle stream_type.
        include_once OO_PLUGIN_DIR . 'admin/views/stop-job-form-page.php'; // Use new constant
    }

    public function display_kpi_measure_management_page() {
        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'operations-organizer' ) );
        }
        include_once OO_PLUGIN_DIR . 'admin/views/kpi-measure-management-page.php';
    }

    // New method to redirect the main settings page to the Jobs page
    public function display_jobs_management_page_redirect() {
        // This function will be the callback for the main settings page.
        // We can simply display the jobs page content here, or do a redirect.
        // For a cleaner URL, a redirect is better if the Jobs page is the true "default".
        // However, to avoid an extra redirect, we can also just call the Jobs display method.
        // For now, let's keep it simple and call the method directly.
        // If a redirect is preferred: wp_redirect(admin_url('admin.php?page=oo_jobs')); exit;
        OO_Job::display_job_management_page();
    }

    // New generic display method for stream pages
    public function display_single_stream_page() {
        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'operations-organizer' ) );
        }
        $current_screen = get_current_screen();
        $page_slug = $current_screen->id; // e.g., toplevel_page_oo_stream_content or operations_page_oo_stream_content etc.
        
        // Extract the stream slug part from the page_slug
        $stream_slug_from_page = str_replace(array('toplevel_page_', 'operations_page_'), '', $page_slug);
        // $stream_slug_from_page should now be like oo_stream_content

        $current_stream_id = null;
        $current_stream_name = 'Unknown Stream';
        $current_stream_tab_slug = '';

        foreach (self::$stream_pages_config as $id => $config) {
            if ($config['slug'] === $stream_slug_from_page) {
                $current_stream_id = $id;
                $current_stream_name = $config['name'];
                $current_stream_tab_slug = $config['tab_slug'];
                break;
            }
        }

        if (!$current_stream_id) {
            wp_die('Error: Could not determine the current stream.');
            return;
        }

        // Pass $current_stream_id, $current_stream_name, $current_stream_tab_slug to the view
        // The view will handle the internal tabs
        // Example: include_once OO_PLUGIN_DIR . 'admin/views/stream-pages/stream-page-template.php';
        // For now, let's load the specific content stream page if it's content
        // This will be refactored to a generic template.
        if ($current_stream_tab_slug === 'content') {
            // Need to make sure $phases and $employees are available for content-tab.php
            // This is a temporary measure until a proper generic stream page template is built.
            $GLOBALS['phases'] = OO_DB::get_phases(array('is_active' => 1, 'orderby' => 'stream_id, order_in_stream'));
            $GLOBALS['employees'] = OO_DB::get_employees(array('is_active' => 1, 'orderby' => 'last_name', 'order' => 'ASC', 'number' => -1));
            include_once OO_PLUGIN_DIR . 'admin/views/stream-pages/content-stream-page.php';
        } else {
            // Placeholder for other stream pages
            echo '<div class="wrap"><h1>' . esc_html($current_stream_name) . ' Stream Page</h1><p>Content for this stream page (ID: ' . esc_html($current_stream_id) . ') will be built here.</p></div>';
        }
    }

    public static function handle_start_job_form() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_start_job_nonce', 'oo_start_job_nonce');

        if (!current_user_can(oo_get_form_access_capability())) { 
            oo_log('AJAX Error: Permission denied to start job.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied to start job.'], 403);
            return;
        }

        $employee_number_input = isset($_POST['employee_number']) ? sanitize_text_field(trim($_POST['employee_number'])) : '';
        $job_number = isset($_POST['job_number']) ? sanitize_text_field($_POST['job_number']) : '';
        $phase_id = isset($_POST['phase_id']) ? intval($_POST['phase_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        // KPI values submitted from the form
        $kpi_values = isset($_POST['kpi_values']) && is_array($_POST['kpi_values']) ? $_POST['kpi_values'] : array();
        $sanitized_kpi_values = array();
        if (!empty($kpi_values)){
            foreach($kpi_values as $key => $value){
                // Basic sanitization, further validation based on unit_type should happen in DB layer or before
                $sanitized_kpi_values[sanitize_key($key)] = sanitize_text_field($value); 
            }
        }

        if (empty($employee_number_input) || empty($job_number) || empty($phase_id)) {
            oo_log('AJAX Error: Missing required fields for start job (EmpNo, JobNo, PhaseID).', $_POST);
            wp_send_json_error(['message' => 'Missing required fields: Employee Number, Job Number, or Phase ID.']);
            return;
        }
        
        // Validate employee exists and is active
        $employee = OO_DB::get_employee_by_number($employee_number_input);
        if (!$employee || !$employee->is_active) {
            oo_log('AJAX Error: Invalid or inactive employee number: ' . $employee_number_input, __METHOD__);
            wp_send_json_error(['message' => 'Invalid or inactive Employee Number.']);
            return;
        }
        $employee_id = $employee->employee_id;

        // Validate job exists
        $job = OO_DB::get_job_by_number($job_number);
        if (!$job) {
            oo_log('AJAX Error: Invalid job number: ' . $job_number, __METHOD__);
            wp_send_json_error(['message' => 'Invalid Job Number. This job does not exist.']);
            return;
        }
        $job_id = $job->job_id;

        // Validate phase exists and is active
        $phase = OO_DB::get_phase($phase_id);
        if (!$phase || !$phase->is_active) {
            oo_log('AJAX Error: Selected phase not active or does not exist for start job.', $phase_id);
            wp_send_json_error(['message' => 'Selected phase is not active or does not exist.']);
            return;
        }
        
        // Get stream_id from the phase object
        $stream_id = $phase->stream_id;
        if (empty($stream_id)) {
            oo_log('AJAX Error: Phase is missing stream_id.', $phase);
            wp_send_json_error(['message' => 'Invalid phase configuration (missing stream).']);
            return;
        }

        $result = OO_DB::start_job_phase($employee_id, $job_id, $stream_id, $phase_id, $notes, $sanitized_kpi_values);

        if (is_wp_Error($result)) {
            oo_log('AJAX Error starting job: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(['message' => 'Error starting job: ' . $result->get_error_message()]);
        } else {
            oo_log('AJAX Success: Job phase started. Log ID: ' . $result, __METHOD__);
            $return_tab = isset($_POST['return_tab']) ? sanitize_key($_POST['return_tab']) : '';
            $base_redirect_url = admin_url('admin.php?page=oo_dashboard');
            $final_redirect_url = $base_redirect_url;
            if (!empty($return_tab)) {
                // $allowed_tabs = array('content', 'soft_content', 'electronics', 'art', 'overview'); 
                // Instead of hardcoded tabs, use stream page slugs if possible
                $redirect_slug = '';
                foreach (self::$stream_pages_config as $stream_id_cfg => $cfg) {
                    if ($cfg['tab_slug'] === $return_tab) {
                        $redirect_slug = $cfg['slug'];
                        break;
                    }
                }
                if (!empty($redirect_slug)) {
                    $final_redirect_url = admin_url('admin.php?page=' . $redirect_slug);
                } // else, it defaults to $base_redirect_url (which itself is problematic now)
                // Fallback if tab not found, or make base_redirect_url more generic like settings page
                 else { $final_redirect_url = admin_url('admin.php?page=' . $settings_parent_slug); }
            }
            wp_send_json_success(['message' => 'Job phase started successfully. Log ID: ' . $result, 'redirect_url' => $final_redirect_url]);
        }
    }

    public static function handle_stop_job_form() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_stop_job_nonce', 'oo_stop_job_nonce');

        if (!current_user_can(oo_get_form_access_capability())) { 
            oo_log('AJAX Error: Permission denied to stop job.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied to stop job.'], 403);
            return;
        }

        $employee_number_input = isset($_POST['employee_number']) ? sanitize_text_field(trim($_POST['employee_number'])) : '';
        $job_number = isset($_POST['job_number']) ? sanitize_text_field($_POST['job_number']) : '';
        $phase_id = isset($_POST['phase_id']) ? intval($_POST['phase_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        // KPI values submitted from the form (new name: kpi_values)
        $kpi_values = isset($_POST['kpi_values']) && is_array($_POST['kpi_values']) ? $_POST['kpi_values'] : array();
        $sanitized_kpi_values = array();
         if (!empty($kpi_values)){
            foreach($kpi_values as $key => $value){
                // Basic sanitization, further validation based on unit_type should happen in DB layer or before
                $sanitized_kpi_values[sanitize_key($key)] = sanitize_text_field($value); 
            }
        }

        if (empty($employee_number_input) || empty($job_number) || empty($phase_id)) {
            oo_log('AJAX Error: Missing required fields for stop job (EmpNo, JobNo, PhaseID).', $_POST);
            wp_send_json_error(['message' => 'Missing required fields: Employee Number, Job Number, or Phase ID.']);
            return;
        }

        // Validate employee exists
        $employee = OO_DB::get_employee_by_number($employee_number_input);
        if (!$employee) { 
            oo_log('AJAX Error: Invalid employee number for stop job: ' . $employee_number_input, __METHOD__);
            wp_send_json_error(['message' => 'Invalid Employee Number.']);
            return;
        }
        $employee_id = $employee->employee_id;
        
        // Validate job exists
        $job = OO_DB::get_job_by_number($job_number);
        if (!$job) {
            oo_log('AJAX Error: Invalid job number: ' . $job_number, __METHOD__);
            wp_send_json_error(['message' => 'Invalid Job Number. This job does not exist.']);
            return;
        }
        $job_id = $job->job_id;
        
        // Validate phase exists
        $phase = OO_DB::get_phase($phase_id);
        if (!$phase) {
            oo_log('AJAX Error: Selected phase does not exist for stop job.', $phase_id);
            wp_send_json_error(['message' => 'Selected phase does not exist.']);
            return;
        }
        
        // Get stream_id from the phase object
        $stream_id = $phase->stream_id;
        if (empty($stream_id)) {
            oo_log('AJAX Error: Phase is missing stream_id.', $phase);
            wp_send_json_error(['message' => 'Invalid phase configuration (missing stream).']);
            return;
        }
        
        // Basic validation for KPI data can be expanded based on unit_type later
        // For now, specific validation for legacy keys is removed.
        // foreach ($sanitized_kpi_values as $key => $value) {
        //     // Example: if a kpi named 'items_completed' should be numeric and non-negative
        //     // This requires fetching KPI definitions here or in OO_DB::stop_job_phase
        //     // For now, this example is simplified.
        //     if ( ($key === 'boxes_completed' || $key === 'items_completed') && !empty($value) && !is_numeric($value) || (is_numeric($value) && intval($value) < 0) ) {
        //         oo_log( 'AJAX Error: ' . $key . ' must be a non-negative number: ' . $value, __METHOD__ );
        //         wp_send_json_error( [ 'message' => esc_html( ucfirst( str_replace('_', ' ', $key) ) ) . ' must be a non-negative number.'] );
        //         return;
        //     }
        // }

        $result = OO_DB::stop_job_phase($employee_id, $job_id, $stream_id, $phase_id, $sanitized_kpi_values, $notes);

        if (is_wp_Error($result)) {
            oo_log('AJAX Error stopping job: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(['message' => 'Error stopping job: ' . $result->get_error_message()]);
        } else {
            oo_log('AJAX Success: Job phase stopped and KPIs recorded.', __METHOD__);
            $return_tab = isset($_POST['return_tab']) ? sanitize_key($_POST['return_tab']) : '';
            $base_redirect_url = admin_url('admin.php?page=oo_dashboard');
            $final_redirect_url = $base_redirect_url;
            if (!empty($return_tab)) {
                // $allowed_tabs = array('content', 'soft_content', 'electronics', 'art', 'overview');
                $redirect_slug = '';
                foreach (self::$stream_pages_config as $stream_id_cfg => $cfg) {
                    if ($cfg['tab_slug'] === $return_tab) {
                        $redirect_slug = $cfg['slug'];
                        break;
                    }
                }
                 if (!empty($redirect_slug)) {
                    $final_redirect_url = admin_url('admin.php?page=' . $redirect_slug);
                } else { $final_redirect_url = admin_url('admin.php?page=' . $settings_parent_slug); }
            }
            wp_send_json_success(['message' => 'Job phase stopped and KPIs recorded successfully.', 'redirect_url' => $final_redirect_url]);
        }
    }

    public static function ajax_get_derived_kpi_definition_details() {
        check_ajax_referer('oo_get_derived_kpi_details_nonce', '_ajax_nonce');
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(['message' => __('Permission denied.', 'operations-organizer')], 403);
            return;
        }

        $definition_id = isset($_POST['definition_id']) ? intval($_POST['definition_id']) : 0;
        if ($definition_id <= 0) {
            wp_send_json_error(['message' => __('Invalid Derived KPI Definition ID.', 'operations-organizer')]);
            return;
        }

        $definition = OO_DB::get_derived_kpi_definition($definition_id);
        if (!$definition) {
            wp_send_json_error(['message' => __('Derived KPI Definition not found.', 'operations-organizer')]);
            return;
        }

        // Also fetch the primary KPI details to get its name and unit type for the modal
        $primary_kpi = null;
        if ($definition->primary_kpi_measure_id > 0) {
            $primary_kpi = OO_DB::get_kpi_measure($definition->primary_kpi_measure_id);
        }

        wp_send_json_success(array(
            'definition' => $definition,
            'primary_kpi' => $primary_kpi
        ));
    }

    public static function ajax_get_current_site_time() {
        check_ajax_referer('oo_get_current_site_time_nonce', '_ajax_nonce');
        if (!current_user_can(oo_get_form_access_capability())) { // Or a more general capability
            wp_send_json_error(['message' => __('Permission denied.', 'operations-organizer')], 403);
            return;
        }

        // Get current time based on WordPress timezone settings
        // 'mysql' format is YYYY-MM-DD HH:MM:SS. We need YYYY-MM-DDTHH:MM for datetime-local.
        $site_time_mysql = current_time('mysql'); // This is already in site's configured timezone
        try {
            $datetime_obj = new DateTime($site_time_mysql, new DateTimeZone(wp_timezone_string()));
            $formatted_time = $datetime_obj->format('Y-m-d\TH:i');
            wp_send_json_success(['formatted_time' => $formatted_time]);
        } catch (Exception $e) {
            oo_log('Error formatting site time: ' . $e->getMessage(), __METHOD__);
            wp_send_json_error(['message' => __('Error fetching server time.', 'operations-organizer')]);
        }
    }

    public static function get_user_table_column_defaults($context) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return array();
        }
        $meta_key = '';
        if ($context === 'content_stream_table') {
            $meta_key = 'oo_content_stream_table_default_columns';
        } // Add other contexts here if needed in the future
        
        if (empty($meta_key)) {
            return array();
        }

        $default_columns_json = get_user_meta($user_id, $meta_key, true);
        $default_columns = !empty($default_columns_json) ? json_decode($default_columns_json, true) : array();
        return is_array($default_columns) ? $default_columns : array();
    }

    public static function ajax_save_user_column_preference() {
        check_ajax_referer('oo_save_column_prefs_nonce', '_ajax_nonce');
        if (!current_user_can(oo_get_capability())) { // Ensure user has general capability
            wp_send_json_error(['message' => __('Permission denied.', 'operations-organizer')], 403);
            return;
        }

        $user_id = get_current_user_id();
        $context = isset($_POST['context']) ? sanitize_key($_POST['context']) : '';
        $columns_config_json = isset($_POST['columns_config']) ? stripslashes($_POST['columns_config']) : '';

        if (empty($user_id) || empty($context) || empty($columns_config_json)) {
            wp_send_json_error(['message' => __('Missing required data to save preference.', 'operations-organizer')]);
            return;
        }

        // Validate JSON structure before saving (basic check)
        $decoded_config = json_decode($columns_config_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded_config)) {
            wp_send_json_error(['message' => __('Invalid column configuration format.', 'operations-organizer')]);
            return;
        }

        $meta_key = '';
        if ($context === 'content_stream_table') {
            $meta_key = 'oo_content_stream_table_default_columns';
        } // Add other contexts here

        if (empty($meta_key)) {
            wp_send_json_error(['message' => __('Invalid context for saving preference.', 'operations-organizer')]);
            return;
        }

        update_user_meta($user_id, $meta_key, $columns_config_json); // Store as JSON string
        wp_send_json_success(['message' => __('Default column settings saved.', 'operations-organizer')]);
    }
} 