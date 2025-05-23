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
     * @var string|null Start Date of the job (YYYY-MM-DD).
     */
    public $start_date;

    /**
     * @var string|null Due Date of the job (YYYY-MM-DD).
     */
    public $due_date;

    /**
     * @var string Overall status of the job.
     */
    public $overall_status;

    /**
     * @var string|null Notes for the job.
     */
    public $notes;

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
        $this->client_name = isset( $data->client_name ) ? $data->client_name : null;
        $this->client_contact = isset( $data->client_contact ) ? $data->client_contact : null;
        $this->start_date = isset( $data->start_date ) ? oo_sanitize_date( $data->start_date ) : null;
        $this->due_date = isset( $data->due_date ) ? oo_sanitize_date( $data->due_date ) : null;
        $this->overall_status = isset( $data->overall_status ) ? $data->overall_status : 'Pending';
        $this->notes = isset( $data->notes ) ? $data->notes : null;
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
     * Get Due Date.
     * @return string|null
     */
    public function get_due_date() {
        return $this->due_date;
    }

    /**
     * Set Due Date.
     * @param string|null $due_date
     */
    public function set_due_date( $due_date ) {
        $this->due_date = oo_sanitize_date( $due_date );
    }

    /**
     * Get Overall Status.
     * @return string
     */
    public function get_overall_status() {
        return $this->overall_status;
    }

    /**
     * Set Overall Status.
     * @param string $overall_status
     */
    public function set_overall_status( $overall_status ) {
        $this->overall_status = sanitize_text_field( $overall_status );
    }

    /**
     * Get Notes.
     * @return string|null
     */
    public function get_notes() {
        return $this->notes;
    }

    /**
     * Set Notes.
     * @param string|null $notes
     */
    public function set_notes( $notes ) {
        $this->notes = $notes ? sanitize_textarea_field( $notes ) : null;
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
            'client_name' => $this->client_name,
            'client_contact' => $this->client_contact,
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'overall_status' => $this->overall_status,
            'notes' => $this->notes,
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
            $job_data = array(
                'job_number' => isset($_POST['job_number']) ? sanitize_text_field($_POST['job_number']) : '',
                'client_name' => isset($_POST['client_name']) ? sanitize_text_field($_POST['client_name']) : '',
                'client_contact' => isset($_POST['client_contact']) ? sanitize_textarea_field($_POST['client_contact']) : '',
                'start_date' => isset($_POST['start_date']) ? oo_sanitize_date($_POST['start_date']) : null,
                'due_date' => isset($_POST['due_date']) ? oo_sanitize_date($_POST['due_date']) : null,
                'overall_status' => isset($_POST['overall_status']) ? sanitize_text_field($_POST['overall_status']) : 'Pending',
                'notes' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '',
            );

            $job = new self();
            $job->set_job_number($job_data['job_number']);
            $job->set_client_name($job_data['client_name']);
            $job->set_client_contact($job_data['client_contact']);
            $job->set_start_date($job_data['start_date']);
            $job->set_due_date($job_data['due_date']);
            $job->set_overall_status($job_data['overall_status']);
            $job->set_notes($job_data['notes']);

            $result = $job->save();

            if (is_wp_error($result)) {
                $GLOBALS['oo_job_error'] = $result->get_error_message();
            } else {
                $job_id = $result;
                
                // Associate streams with the job based on checkboxes
                $selected_streams = array();
                if (isset($_POST['stream_1'])) $selected_streams[] = 1; // Soft Content
                if (isset($_POST['stream_2'])) $selected_streams[] = 2; // Electronics
                if (isset($_POST['stream_3'])) $selected_streams[] = 3; // Art
                if (isset($_POST['stream_4'])) $selected_streams[] = 4; // Content
                
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
                
                $GLOBALS['oo_job_success'] = __('Job added successfully.', 'operations-organizer');
            }
        }

        // Prepare job data for display
        $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;
        $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';

        $args = array(
            'number' => $per_page,
            'offset' => $offset,
            'orderby' => isset($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby']) : 'created_at',
            'order' => isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC',
        );

        if ($search_term) {
            $args['search'] = $search_term;
        }

        if ($status_filter) {
            $args['overall_status'] = $status_filter;
        }

        $jobs = OO_DB::get_jobs($args);
        $total_jobs = OO_DB::get_jobs_count($args);
        
        // Get all hardcoded streams for the form
        $streams = oo_get_hardcoded_streams();

        // Pass data to the view
        $GLOBALS['jobs'] = $jobs;
        $GLOBALS['total_jobs'] = $total_jobs;
        $GLOBALS['current_page'] = $current_page;
        $GLOBALS['per_page'] = $per_page;
        $GLOBALS['search_term'] = $search_term;
        $GLOBALS['status_filter'] = $status_filter;
        $GLOBALS['streams'] = $streams;

        // Include the view
        include_once OO_PLUGIN_DIR . 'admin/views/job-management-page.php';
    }

    // TODO: Add more methods as needed, e.g., for validation, specific data retrieval.
} 