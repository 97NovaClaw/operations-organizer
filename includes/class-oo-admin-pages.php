<?php
// /includes/class-oo-admin-pages.php // Renamed file

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Admin_Pages { // Renamed class

    private $admin_page_hooks = array();
    private static $stream_pages_config = array(); // Store stream page slugs and names

    public function add_admin_menu_pages() {
        $streams = oo_get_hardcoded_streams(); 
        self::$stream_pages_config = array(); 

        $main_menu_icon = 'dashicons-analytics'; // A general icon for the plugin group
        $main_menu_position = 25;

        // Top-Level: Jobs
        $this->admin_page_hooks[] = add_menu_page(
            __( 'Manage Jobs', 'operations-organizer' ),
            __( 'Jobs', 'operations-organizer' ),
            oo_get_capability(),
            'oo_jobs',
            array( 'OO_Job', 'display_job_management_page' ),
            'dashicons-list-view', 
            $main_menu_position + 1
        );
        
        // Top-Level: Employees
        $this->admin_page_hooks[] = add_menu_page(
            __( 'Manage Employees', 'operations-organizer' ),
            __( 'Employees', 'operations-organizer' ),
            oo_get_capability(), 
            'oo_employees',
            array( 'OO_Employee', 'display_employee_management_page' ),
            'dashicons-groups',
            $main_menu_position + 2
        );
        
        // Top-Level: Phases (Global View)
        $this->admin_page_hooks[] = add_menu_page(
            __( 'Manage All Phases', 'operations-organizer' ), // Title clarifies it's global
            __( 'Phases (All)', 'operations-organizer' ),
            oo_get_capability(), 
            'oo_phases',
            array( 'OO_Phase', 'display_phase_management_page' ),
            'dashicons-networking',
            $main_menu_position + 3
        );
        
        // Top-Level: KPI Definitions (Global View)
        $this->admin_page_hooks[] = add_menu_page(
            __('KPI Measure Definitions', 'operations-organizer'),
            __('KPI Definitions', 'operations-organizer'),
            oo_get_capability(),
            'oo_kpi_measures', 
            array($this, 'display_kpi_measure_management_page'),
            'dashicons-performance',
            $main_menu_position + 4
        );

        // Create Top-Level Menu Pages for each Stream
        $stream_menu_position = $main_menu_position + 10; // Start streams after main management pages
        if (!empty($streams)) {
            foreach ($streams as $stream) {
                if (!$stream || !isset($stream->stream_id) || !isset($stream->stream_name)) continue;
                $page_slug = 'oo_stream_' . sanitize_key($stream->stream_name); 
                self::$stream_pages_config[$stream->stream_id] = array(
                    'slug' => $page_slug,
                    'name' => $stream->stream_name,
                    'tab_slug' => sanitize_key($stream->stream_name) 
                );

                $this->admin_page_hooks[] = add_menu_page(
                    sprintf(__( '%s Stream', 'operations-organizer' ), $stream->stream_name),
                    $stream->stream_name, 
                    oo_get_capability(), 
                    $page_slug,
                    array( $this, 'display_single_stream_page' ), 
                    'dashicons-chart-line', 
                    $stream_menu_position++ 
                );
            }
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

    // Getter for stream page configurations, primarily for redirect logic
    public static function get_stream_page_configs_for_redirect() {
        // Ensure $stream_pages_config is populated if accessed before add_admin_menu_pages (e.g. during an early hook)
        // However, for redirects from admin pages, add_admin_menu_pages should have already run.
        if (empty(self::$stream_pages_config)) {
            // Potentially re-populate if called very early, though less likely for this specific use case.
            // For robustness, ensure oo_get_hardcoded_streams() is available here if needed.
            // $streams = oo_get_hardcoded_streams(); 
            // foreach ($streams as $stream) { ... fill self::$stream_pages_config ... }
            // For now, assuming it's populated by the time redirects occur from within admin pages.
        }
        return self::$stream_pages_config;
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

    public static function ajax_get_stream_jobs() {
        check_ajax_referer('oo_get_stream_jobs_nonce', 'nonce');
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(['message' => __('Permission denied.', 'operations-organizer')], 403);
            return;
        }

        $stream_id = isset($_POST['stream_id']) ? intval($_POST['stream_id']) : 0;
        if ($stream_id <= 0) {
            wp_send_json_error(['message' => __('Invalid Stream ID.', 'operations-organizer')]);
            return;
        }

        // Basic DataTables server-side parameters
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        if ($length === -1) $length = 99999; 

        $search_value = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';
        
        // Column ordering (simplified for now, can be expanded)
        $order_column_index = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
        $order_dir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'desc';
        
        $dt_columns = isset($_POST['columns']) ? $_POST['columns'] : array();
        $orderby_db_col = 'j.job_number'; // Default
        if(isset($dt_columns[$order_column_index]['data'])) {
            $order_col_name = sanitize_text_field($dt_columns[$order_column_index]['data']);
            // Map DataTables column data name to DB column name
            $column_map = [
                'job_number' => 'j.job_number',
                'client_name' => 'j.client_name',
                'overall_status' => 'j.overall_status',
                'due_date' => 'j.due_date'
            ];
            if (array_key_exists($order_col_name, $column_map)) {
                $orderby_db_col = $column_map[$order_col_name];
            }
        }

        $params = array(
            'stream_id' => $stream_id,
            'number' => $length,
            'offset' => $start,
            'orderby' => $orderby_db_col,
            'order' => $order_dir,
            'search_general' => $search_value // Assuming get_jobs_for_stream handles this
        );

        $jobs = OO_DB::get_jobs_for_stream($params); // We need this new DB method
        $total_records = OO_DB::get_jobs_for_stream_count(array('stream_id' => $stream_id)); // Total for this stream
        $total_filtered_records = OO_DB::get_jobs_for_stream_count($params); // Filtered for this stream

        $data_array = array();
        if (!empty($jobs)) {
            foreach ($jobs as $job) {
                // Format data as needed for the table
                $data_array[] = array(
                    'job_id' => $job->job_id,
                    'job_number' => esc_html($job->job_number),
                    'client_name' => esc_html($job->client_name),
                    'overall_status' => esc_html($job->overall_status),
                    'due_date' => !empty($job->due_date) ? esc_html(date_i18n(get_option('date_format'), strtotime($job->due_date))) : 'N/A',
                );
            }
        }

        wp_send_json_success(array(
            'draw'            => $draw,
            'recordsTotal'    => $total_records,
            'recordsFiltered' => $total_filtered_records,
            'data'            => $data_array,
        ));
    }
} 