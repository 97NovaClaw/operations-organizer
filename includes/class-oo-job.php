<?php
// /includes/class-oo-job.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Job {

    // Properties related to a job (e.g., job_id, job_number, client_name)

    public function __construct( $job_id = null ) {
        if ( $job_id ) {
            // Load job data from DB if an ID is provided
            $this->load_job( $job_id );
        }
    }

    private function load_job( $job_id ) {
        // TODO: Implement method to load job details from wp_oo_jobs table
        // using OO_DB::get_job($job_id) - (method to be created in OO_DB)
    }

    // --- Static CRUD-like Methods (interacting with OO_DB) ---

    public static function create( $args ) {
        // TODO: Call OO_DB::add_job($args) - (method to be created in OO_DB)
        // $args should contain job_number, client_name, etc.
        // Returns new job_id or WP_Error
        return new WP_Error('not_implemented', 'Create job method not implemented yet.');
    }

    public static function get( $job_id ) {
        // TODO: Call OO_DB::get_job($job_id)
        // Returns job object/array or null
        return null;
    }

    public static function update( $job_id, $args ) {
        // TODO: Call OO_DB::update_job($job_id, $args)
        // Returns true or WP_Error
        return new WP_Error('not_implemented', 'Update job method not implemented yet.');
    }

    public static function delete( $job_id ) {
        // TODO: Call OO_DB::delete_job($job_id)
        // Returns true or WP_Error
        return new WP_Error('not_implemented', 'Delete job method not implemented yet.');
    }

    public static function get_all( $params = array() ) {
        // TODO: Call OO_DB::get_jobs($params)
        // $params for filtering, pagination, sorting
        // Returns array of job objects/arrays or empty array
        return array();
    }

    // --- Instance Methods (operating on a loaded job) ---

    public function get_job_number() {
        // return $this->job_number;
        return ''; // Placeholder
    }

    public function get_streams() {
        // TODO: Implement method to get all job_streams associated with this job
        // using OO_DB::get_job_streams_for_job($this->job_id)
        return array();
    }

    public function add_stream_to_job( $stream_id, $details = array() ) {
        // TODO: Call OO_DB::add_job_stream($this->job_id, $stream_id, $details)
        return new WP_Error('not_implemented', 'Add stream to job method not implemented yet.');
    }

    // Other job-specific methods like update_status, get_total_expenses, etc.

} 