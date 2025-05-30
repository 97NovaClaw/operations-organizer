<?php
// /includes/class-oo-admin-pages.php // Renamed file

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Admin_Pages { // Renamed class

    private $admin_page_hooks = array();

    public function add_admin_menu_pages() {
        $main_menu_slug = 'oo_dashboard';

        $this->admin_page_hooks[] = add_menu_page(
            __( 'Operations Organizer', 'operations-organizer' ),
            __( 'Operations', 'operations-organizer' ),
            oo_get_capability(), 
            $main_menu_slug,
            array( 'OO_Dashboard', 'display_dashboard_page' ),
            'dashicons-analytics', 
            25 
        );

        // Add Jobs management page
        $this->admin_page_hooks[] = add_submenu_page(
            $main_menu_slug,
            __( 'Manage Jobs', 'operations-organizer' ),
            __( 'Jobs', 'operations-organizer' ),
            oo_get_capability(),
            'oo_jobs',
            array( 'OO_Job', 'display_job_management_page' )
        );
        
        // Employees management page
        $this->admin_page_hooks[] = add_submenu_page(
            $main_menu_slug, 
            __( 'Manage Employees', 'operations-organizer' ),
            __( 'Employees', 'operations-organizer' ),
            oo_get_capability(), 
            'oo_employees',
            array( 'OO_Employee', 'display_employee_management_page' )
        );
        
        // Phases management page (not specific to content stream)
        $this->admin_page_hooks[] = add_submenu_page(
            $main_menu_slug, 
            __( 'Manage Phases', 'operations-organizer' ),
            __( 'Phases', 'operations-organizer' ),
            oo_get_capability(), 
            'oo_phases',
            array( 'OO_Phase', 'display_phase_management_page' )
        );
        
        // Submenu: KPI Measure Management (NEW)
        // This page might be better as a tab under "Phases & KPIs" in the future, 
        // but for now, let's make it a separate page for clarity during development.
        $this->admin_page_hooks[] = add_submenu_page(
            $main_menu_slug,                                // Parent Slug
            __('KPI Measure Definitions', 'operations-organizer'),
            __('KPI Definitions', 'operations-organizer'),
            oo_get_capability(),
            'oo_kpi_measures', 
            array($this, 'display_kpi_measure_management_page')
        );
        
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
                $allowed_tabs = array('content', 'soft_content', 'electronics', 'art', 'overview'); 
                if (in_array($return_tab, $allowed_tabs)) {
                    $final_redirect_url = add_query_arg('tab', $return_tab, $base_redirect_url);
                }
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
                $allowed_tabs = array('content', 'soft_content', 'electronics', 'art', 'overview');
                if (in_array($return_tab, $allowed_tabs)) {
                    $final_redirect_url = add_query_arg('tab', $return_tab, $base_redirect_url);
                }
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
} 