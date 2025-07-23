<?php
// /includes/class-oo-job.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class OO_Job
 * 
 * Represents a job in the Operations Organizer system.
 */
class OO_Job {

    /**
     * @var int The Job ID.
     */
    public $job_id;

    /**
     * @var string Job Number (unique identifier).
     */
    public $job_number;

    /**
     * @var string|null Client Name.
     */
    public $client_name;

    /**
     * @var string|null Client Contact Information.
     */
    public $client_contact;

    /**
     * @var string|null Claim Number.
     */
    public $claim_number;

    /**
     * @var int|null Customer ID.
     */
    public $customer_id;

    /**
     * @var string|null Client Phone Number.
     */
    public $client_phone;

    /**
     * @var string|null Client Email.
     */
    public $client_email;

    /**
     * @var string|null Job Address.
     */
    public $address;

    /**
     * @var string|null Job City.
     */
    public $city;

    /**
     * @var string|null Job Province.
     */
    public $province;

    /**
     * @var string|null Job Postal Code.
     */
    public $postal_code;

    /**
     * @var string|null Start Date of the job (YYYY-MM-DD).
     */
    public $start_date;

    /**
     * @var string Creation timestamp (YYYY-MM-DD HH:MM:SS).
     */
    public $created_at;

    /**
     * @var string Last updated timestamp (YYYY-MM-DD HH:MM:SS).
     */
    public $updated_at;

    /**
     * @var array Raw data from the database.
     */
    protected $data;


    /**
     * Constructor.
     *
     * @param int|object|array|null $job_data Job ID, or an object/array of job data.
     */
    public function __construct( $job_data = null ) {
        if ( is_numeric( $job_data ) ) {
            $this->job_id = intval( $job_data );
            $this->load();
        } elseif ( is_object( $job_data ) || is_array( $job_data ) ) {
            $this->populate_from_data( (object) $job_data );
        }
    }

    /**
     * Load job data from the database.
     */
    protected function load() {
        if ( ! $this->job_id ) {
            return false;
        }
        $data = OO_DB::get_job( $this->job_id );
        if ( $data ) {
            $this->populate_from_data( $data );
            return true;
        }
        return false;
    }

    /**
     * Populate object properties from a data object.
     *
     * @param object $data Data object (typically from database).
     */
    protected function populate_from_data( $data ) {
        $this->data = $data; // Store raw data
        $this->job_id = isset( $data->job_id ) ? intval( $data->job_id ) : null;
        $this->job_number = isset( $data->job_number ) ? $data->job_number : null;
        $this->claim_number = isset( $data->claim_number ) ? $data->claim_number : null;
        $this->customer_id = isset( $data->customer_id ) ? intval( $data->customer_id ) : null;
        $this->client_name = isset( $data->client_name ) ? $data->client_name : null;
        $this->client_phone = isset( $data->client_phone ) ? $data->client_phone : null;
        $this->client_email = isset( $data->client_email ) ? $data->client_email : null;
        $this->client_contact = isset( $data->client_contact ) ? $data->client_contact : null;
        $this->address = isset( $data->address ) ? $data->address : null;
        $this->city = isset( $data->city ) ? $data->city : null;
        $this->province = isset( $data->province ) ? $data->province : null;
        $this->postal_code = isset( $data->postal_code ) ? $data->postal_code : null;
        $this->start_date = isset( $data->start_date ) ? oo_sanitize_date( $data->start_date ) : null;
        $this->created_at = isset( $data->created_at ) ? $data->created_at : null;
        $this->updated_at = isset( $data->updated_at ) ? $data->updated_at : null;
    }

    /**
     * Get Job ID.
     * @return int|null
     */
    public function get_id() {
        return $this->job_id;
    }

    /**
     * Get Job Number.
     * @return string|null
     */
    public function get_job_number() {
        return $this->job_number;
    }
    
    /**
     * Set Job Number.
     * @param string $job_number
     */
    public function set_job_number( $job_number ) {
        $this->job_number = sanitize_text_field($job_number);
    }

    /**
     * Get Client Name.
     * @return string|null
     */
    public function get_client_name() {
        return $this->client_name;
    }

    /**
     * Set Client Name.
     * @param string|null $client_name
     */
    public function set_client_name( $client_name ) {
        $this->client_name = $client_name ? sanitize_text_field( $client_name ) : null;
    }
    
    /**
     * Get Client Contact.
     * @return string|null
     */
    public function get_client_contact() {
        return $this->client_contact;
    }

    /**
     * Set Client Contact.
     * @param string|null $client_contact
     */
    public function set_client_contact( $client_contact ) {
        $this->client_contact = $client_contact ? sanitize_textarea_field( $client_contact ) : null;
    }

    /**
     * Get Start Date.
     * @return string|null
     */
    public function get_start_date() {
        return $this->start_date;
    }

    /**
     * Set Start Date.
     * @param string|null $start_date
     */
    public function set_start_date( $start_date ) {
        $this->start_date = oo_sanitize_date( $start_date );
    }



    /**
     * Get Created At timestamp.
     * @return string|null
     */
    public function get_created_at() {
        return $this->created_at;
    }

    /**
     * Get Updated At timestamp.
     * @return string|null
     */
    public function get_updated_at() {
        return $this->updated_at;
    }

    /**
     * Check if the job data has been loaded from the database.
     * @return bool
     */
    public function exists() {
        return ! empty( $this->job_id ) && ! empty( $this->created_at );
    }

    /**
     * Save the job data to the database.
     * Creates a new job or updates an existing one.
     *
     * @return int|WP_Error Job ID on success, WP_Error on failure.
     */
    public function save() {
        $data = array(
            'job_number' => $this->job_number,
            'claim_number' => $this->claim_number,
            'customer_id' => $this->customer_id,
            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'client_email' => $this->client_email,
            'client_contact' => $this->client_contact,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'start_date' => $this->start_date,
        );

        if ( $this->exists() ) {
            // Update existing job
            $result = OO_DB::update_job( $this->job_id, $data );
            if ( is_wp_error( $result ) ) {
                oo_log('Error updating job (ID: ' . $this->job_id . '): ' . $result->get_error_message(), __METHOD__);
                return $result;
            }
            $this->load(); // Reload data to get updated_at timestamp and any other changes
            oo_log('Job updated successfully (ID: ' . $this->job_id . ')', __METHOD__);
            return $this->job_id;
        } else {
            // Create new job
            if ( empty( $this->job_number ) ) {
                return new WP_Error('missing_job_number', 'Job number is required to create a new job.');
            }
            $new_job_id = OO_DB::add_job( $data );
            if ( is_wp_error( $new_job_id ) ) {
                oo_log('Error adding new job: ' . $new_job_id->get_error_message(), __METHOD__);
                return $new_job_id;
            }
            $this->job_id = $new_job_id;
            $this->load(); // Load the newly created job's data including created_at, updated_at
            oo_log('New job added successfully (ID: ' . $this->job_id . ')', __METHOD__);
            return $this->job_id;
        }
    }

    /**
     * Delete the job from the database.
     *
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function delete() {
        if ( ! $this->exists() ) {
            return new WP_Error( 'job_not_exists', 'Cannot delete a job that does not exist or has not been saved.' );
        }
        $result = OO_DB::delete_job( $this->job_id );
        if ( is_wp_error( $result ) ) {
            oo_log('Error deleting job (ID: ' . $this->job_id . '): ' . $result->get_error_message(), __METHOD__);
            return $result;
        }
        // Clear object properties after deletion
        $this->job_id = null; 
        $this->data = null;
        // ... clear other properties or re-initialize
        oo_log('Job deleted successfully (Former ID: ' . $this->get_id() . ')', __METHOD__); // get_id will be null here if reset.
        return true;
    }

    /**
     * Get an OO_Job instance by its ID.
     *
     * @param int $job_id The Job ID.
     * @return OO_Job|null OO_Job object if found, null otherwise.
     */
    public static function get_by_id( $job_id ) {
        $job = new self( $job_id );
        return $job->exists() ? $job : null;
    }
    
    /**
     * Get an OO_Job instance by its Job Number.
     *
     * @param string $job_number The Job Number.
     * @return OO_Job|null OO_Job object if found, null otherwise.
     */
    public static function get_by_job_number( $job_number ) {
        $data = OO_DB::get_job_by_number( $job_number );
        if ( $data ) {
            return new self( $data );
        }
        return null;
    }

    /**
     * Get associated job streams for this job.
     * (Placeholder for now)
     *
     * @return array An array of OO_Job_Stream objects.
     */
    public function get_job_streams() {
        if ( ! $this->exists() ) {
            return array();
        }
        // TODO: Fetch from OO_DB::get_job_streams_for_job($this->job_id)
        // and map results to OO_Job_Stream objects.
        $job_stream_data = OO_DB::get_job_streams_for_job( $this->job_id );
        $job_streams = array();
        if ( !empty($job_stream_data) && is_array($job_stream_data) ) {
            foreach ( $job_stream_data as $js_data ) {
                // Assuming OO_Job_Stream class exists and has a similar constructor
                if (class_exists('OO_Job_Stream')) {
                    $job_streams[] = new OO_Job_Stream( $js_data );
                } else {
                    // Fallback or error if class not found yet
                    oo_log('OO_Job_Stream class not found while trying to load job streams for job ID: ' . $this->job_id, __METHOD__);
                }
            }
        }
        return $job_streams;
    }
    
    /**
     * Get all jobs matching criteria.
     *
     * @param array $args Arguments for filtering, sorting, pagination (passed to OO_DB::get_jobs).
     * @return OO_Job[] Array of OO_Job objects.
     */
    public static function get_jobs( $args = array() ) {
        $job_datas = OO_DB::get_jobs( $args );
        $jobs = array();
        foreach ( $job_datas as $job_data ) {
            $jobs[] = new self( $job_data );
        }
        return $jobs;
    }

    /**
     * Get the count of jobs matching criteria.
     *
     * @param array $args Arguments for filtering (passed to OO_DB::get_jobs_count).
     * @return int Count of jobs.
     */
    public static function get_jobs_count( $args = array() ) {
        return OO_DB::get_jobs_count( $args );
    }

    /**
     * Display the Job management page.
     */
    public static function display_job_management_page() {
        if (!current_user_can(oo_get_capability())) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'operations-organizer'));
        }

        // Process form submission for adding new job
        if (isset($_POST['submit_add_job']) && isset($_POST['oo_add_job_nonce']) && wp_verify_nonce($_POST['oo_add_job_nonce'], 'oo_add_job_nonce')) {
            $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
            $customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
            
            // Handle customer auto-creation if needed
            if ( empty( $customer_id ) && !empty( $customer_name ) ) {
                $customer = OO_Customer::find_or_create_by_name( $customer_name );
                if ( is_wp_error( $customer ) ) {
                    $GLOBALS['oo_job_error'] = 'Error with customer: ' . $customer->get_error_message();
                    return; // Early return to prevent job creation
                } else {
                    $customer_id = $customer->customer_id;
                    $GLOBALS['oo_customer_auto_created'] = $customer_name; // For success message
                }
            }

            $job_data = array(
                'job_number' => isset($_POST['job_number']) ? sanitize_text_field($_POST['job_number']) : '',
                'claim_number' => isset($_POST['claim_number']) ? sanitize_text_field($_POST['claim_number']) : '',
                'customer_id' => $customer_id,
                'client_name' => isset($_POST['client_name']) ? sanitize_text_field($_POST['client_name']) : '',
                'client_phone' => self::process_client_phone_data(),
                'client_email' => isset($_POST['client_email']) ? sanitize_email($_POST['client_email']) : '',
                'client_contact' => isset($_POST['client_contact']) ? sanitize_textarea_field($_POST['client_contact']) : '',
                'address' => isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '',
                'city' => isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '',
                'province' => isset($_POST['province']) ? sanitize_text_field($_POST['province']) : '',
                'postal_code' => isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '',
                'start_date' => isset($_POST['start_date']) ? oo_sanitize_date($_POST['start_date']) : current_time('Y-m-d'),
            );

            $job = new self();
            $job->set_job_number($job_data['job_number']);
            $job->claim_number = $job_data['claim_number'];
            $job->customer_id = $job_data['customer_id'];
            $job->set_client_name($job_data['client_name']);
            $job->client_phone = $job_data['client_phone'];
            $job->client_email = $job_data['client_email'];
            $job->set_client_contact($job_data['client_contact']);
            $job->address = $job_data['address'];
            $job->city = $job_data['city'];
            $job->province = $job_data['province'];
            $job->postal_code = $job_data['postal_code'];
            $job->set_start_date($job_data['start_date']);

            $result = $job->save();

            if (is_wp_error($result)) {
                $GLOBALS['oo_job_error'] = $result->get_error_message();
            } else {
                $job_id = $result;
                
                // Associate streams with the job based on checkboxes - DYNAMIC VERSION
                $selected_streams = array();
                
                // Get all available streams dynamically
                $all_streams = OO_DB::get_streams(array('is_active' => 1));
                
                foreach ($all_streams as $stream) {
                    $checkbox_name = 'stream_' . $stream->stream_id;
                    if (isset($_POST[$checkbox_name])) {
                        $selected_streams[] = intval($stream->stream_id);
                    }
                }
                
                foreach ($selected_streams as $stream_id) {
                    // Create job_stream_link association
                    $job_stream_data = array(
                        'job_id' => $job_id,
                        'stream_id' => $stream_id
                    );
                    
                    $link_result = OO_DB::add_job_stream($job_stream_data);
                    
                    if (!is_wp_error($link_result)) {
                        // Create stream-specific data record
                        $stream_data = array(
                            'job_id' => $job_id,
                            'status_in_stream' => 'Pending',
                            'last_updated_in_stream' => current_time('mysql', 1)
                        );
                        
                        // Create initial entry in the appropriate stream data table
                        oo_create_stream_data_for_job($job_id, $stream_id, $stream_data);
                    }
                }
                
                $success_message = __('Job added successfully.', 'operations-organizer');
                if ( isset( $GLOBALS['oo_customer_auto_created'] ) ) {
                    $success_message .= ' ' . sprintf( __('Customer "%s" was automatically created.', 'operations-organizer'), $GLOBALS['oo_customer_auto_created'] );
                }
                $GLOBALS['oo_job_success'] = $success_message;
            }
        }

        // Prepare job data for display
        $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;
        $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        $args = array(
            'number' => $per_page,
            'offset' => $offset,
            'orderby' => isset($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby']) : 'created_at',
            'order' => isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC',
        );

        if ($search_term) {
            $args['search_general'] = $search_term;
        }

        $jobs = OO_DB::get_jobs($args);
        $total_jobs = OO_DB::get_jobs_count($args);
        
        // Get all hardcoded streams for the form
        $streams = oo_get_streams();

        // Pass data to the view
        $GLOBALS['jobs'] = $jobs;
        $GLOBALS['total_jobs'] = $total_jobs;
        $GLOBALS['current_page'] = $current_page;
        $GLOBALS['per_page'] = $per_page;
        $GLOBALS['search_term'] = $search_term;
        $GLOBALS['streams'] = $streams;

        // Include the view
        include_once OO_PLUGIN_DIR . 'admin/views/job-management-page.php';
    }

    /**
     * Process client phone data from the phone repeater
     */
    private static function process_client_phone_data() {
        $phone_json = '';
        
        if ( ! empty( $_POST['client_phone_numbers_json'] ) ) {
            $phone_data = json_decode( stripslashes( $_POST['client_phone_numbers_json'] ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $validated_phone_data = oo_validate_phone_data( $phone_data );
                if ( ! is_wp_error( $validated_phone_data ) ) {
                    $phone_json = wp_json_encode( $validated_phone_data );
                }
            }
        }
        
        return $phone_json;
    }

    /**
     * AJAX handler for customer search autocomplete
     */
    public static function ajax_search_customers() {
        check_ajax_referer('oo_search_customers_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        $search_term = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : (isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '');
        
        if (strlen($search_term) < 2) {
            wp_send_json_success([]);
            return;
        }

        $customers = OO_DB::get_customers(array(
            'search' => $search_term,
            'number' => 10,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        $customer_data = array();
        foreach ($customers as $customer) {
            $display_name = $customer->name;
            if (!empty($customer->company_name)) {
                $display_name .= ' (' . $customer->company_name . ')';
            }
            
            $customer_data[] = array(
                'id' => $customer->customer_id,
                'name' => $customer->name,
                'phone_numbers' => $customer->phone_numbers,
                'email_addresses' => $customer->email_addresses,
                'company_name' => $customer->company_name,
                'display_name' => $display_name
            );
        }

        wp_send_json_success($customer_data);
    }

    /**
     * AJAX handler for adding a new customer
     */
    public static function ajax_add_customer() {
        check_ajax_referer('oo_add_customer_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        // Process phone numbers
        $phone_json = '';
        if ( ! empty( $_POST['phone_numbers_json'] ) ) {
            $phone_data = json_decode( stripslashes( $_POST['phone_numbers_json'] ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $validated_phone_data = oo_validate_phone_data( $phone_data );
                if ( ! is_wp_error( $validated_phone_data ) ) {
                    $phone_json = wp_json_encode( $validated_phone_data );
                }
            }
        }

        // Process email addresses
        $email_json = '';
        if ( ! empty( $_POST['email_addresses_json'] ) ) {
            $email_data = json_decode( stripslashes( $_POST['email_addresses_json'] ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $validated_email_data = oo_validate_email_data( $email_data );
                if ( ! is_wp_error( $validated_email_data ) ) {
                    $email_json = wp_json_encode( $validated_email_data );
                }
            }
        }

        $customer_data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'phone_numbers' => $phone_json,
            'email_addresses' => $email_json,
            'company_id' => isset($_POST['company_id']) ? intval($_POST['company_id']) : null,
        );

        if (empty($customer_data['name'])) {
            wp_send_json_error(['message' => 'Customer name is required.']);
            return;
        }

        $result = OO_DB::add_customer($customer_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }

        // Get the newly created customer with company info
        $customer = OO_DB::get_customer_with_company($result);

        if ($customer) {
            $display_name = $customer->name;
            if (!empty($customer->company_name)) {
                $display_name .= ' (' . $customer->company_name . ')';
            }
            
            wp_send_json_success([
                'message' => 'Customer added successfully.',
                'customer' => array(
                    'id' => $customer->customer_id,
                    'name' => $customer->name,
                    'phone_numbers' => $customer->phone_numbers,
                    'email_addresses' => $customer->email_addresses,
                    'company_name' => $customer->company_name,
                    'display_name' => $display_name
                )
            ]);
        } else {
            wp_send_json_error(['message' => 'Customer was created but could not be retrieved.']);
        }
    }

    /**
     * AJAX handler for adding a new company
     */
    public static function ajax_add_company() {
        check_ajax_referer('oo_add_company_nonce', 'nonce');
        
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        // Process phone numbers
        $phone_json = '';
        if ( ! empty( $_POST['phone_numbers_json'] ) ) {
            $phone_data = json_decode( stripslashes( $_POST['phone_numbers_json'] ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $validated_phone_data = oo_validate_phone_data( $phone_data );
                if ( ! is_wp_error( $validated_phone_data ) ) {
                    $phone_json = wp_json_encode( $validated_phone_data );
                }
            }
        }

        // Process email addresses
        $email_json = '';
        if ( ! empty( $_POST['email_addresses_json'] ) ) {
            $email_data = json_decode( stripslashes( $_POST['email_addresses_json'] ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $validated_email_data = oo_validate_email_data( $email_data );
                if ( ! is_wp_error( $validated_email_data ) ) {
                    $email_json = wp_json_encode( $validated_email_data );
                }
            }
        }

        $company_data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'address' => isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '',
            'city' => isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '',
            'province' => isset($_POST['province']) ? sanitize_text_field($_POST['province']) : '',
            'postal_code' => isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '',
            'phone_numbers' => $phone_json,
            'email_addresses' => $email_json,
        );

        if (empty($company_data['name'])) {
            wp_send_json_error(['message' => 'Company name is required.']);
            return;
        }

        $result = OO_DB::add_company($company_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }

        // Get the newly created company
        $new_company = OO_DB::get_company($result);
        
        if ($new_company) {
            wp_send_json_success([
                'message' => 'Company added successfully.',
                'company' => array(
                    'id' => $new_company->company_id,
                    'name' => $new_company->name,
                    'address' => $new_company->address,
                    'city' => $new_company->city,
                    'province' => $new_company->province,
                    'postal_code' => $new_company->postal_code,
                    'phone_numbers' => $new_company->phone_numbers,
                    'email_addresses' => $new_company->email_addresses,
                )
            ]);
        } else {
            wp_send_json_error(['message' => 'Company was created but could not be retrieved.']);
        }
    }

    // TODO: Add more methods as needed, e.g., for validation, specific data retrieval.
} 