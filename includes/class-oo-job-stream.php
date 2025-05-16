<?php
// /includes/class-oo-job-stream.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Job_Stream {

    public $job_stream_id;
    public $job_id;
    public $stream_id;
    public $status_in_stream;
    public $assigned_manager_id;
    public $start_date_stream;
    public $due_date_stream;
    public $building_id;
    public $notes;
    // อาจมี property สำหรับ object OO_Stream และ OO_Job ที่โหลดมา
    // public $stream_object;
    // public $job_object;

    public function __construct( $job_stream_id = null ) {
        if ( $job_stream_id ) {
            $this->load_job_stream( $job_stream_id );
        }
    }

    private function load_job_stream( $job_stream_id ) {
        // TODO: Implement method to load job stream details from wp_oo_job_streams
        // $data = OO_DB::get_job_stream( $job_stream_id ); (method to be created in OO_DB)
        // if ($data) { $this->populate_properties($data); }
    }

    private function populate_properties( $data ) {
        $this->job_stream_id = $data->job_stream_id;
        $this->job_id = $data->job_id;
        $this->stream_id = $data->stream_id;
        $this->status_in_stream = $data->status_in_stream;
        $this->assigned_manager_id = $data->assigned_manager_id;
        $this->start_date_stream = $data->start_date_stream;
        $this->due_date_stream = $data->due_date_stream;
        $this->building_id = $data->building_id;
        $this->notes = $data->notes;
        // Optionally load OO_Stream and OO_Job objects
        // $this->stream_object = new OO_Stream($this->stream_id);
        // $this->job_object = new OO_Job($this->job_id);
    }
    
    public function is_valid() {
        return !empty($this->job_stream_id);
    }

    // --- Static CRUD-like Methods ---

    public static function assign_stream_to_job( $job_id, $stream_id, $args = array() ) {
        // TODO: Call OO_DB::add_job_stream($job_id, $stream_id, $args)
        // $args includes status_in_stream, assigned_manager_id, etc.
        // Returns new job_stream_id or WP_Error
        return new WP_Error('not_implemented', 'Assign stream to job method not implemented yet.');
    }

    public static function get( $job_stream_id ) {
        // TODO: Call OO_DB::get_job_stream($job_stream_id)
        // Returns OO_Job_Stream object or null
        // $data = OO_DB::get_job_stream($job_stream_id);
        // if ($data) { $obj = new self(); $obj->populate_properties($data); return $obj; }
        return null;
    }

    public static function update( $job_stream_id, $args ) {
        // TODO: Call OO_DB::update_job_stream($job_stream_id, $args)
        // Returns true or WP_Error
        return new WP_Error('not_implemented', 'Update job stream method not implemented yet.');
    }

    public static function delete( $job_stream_id ) {
        // TODO: Call OO_DB::delete_job_stream($job_stream_id)
        // Returns true or WP_Error
        return new WP_Error('not_implemented', 'Delete job stream method not implemented yet.');
    }

    public static function get_for_job( $job_id, $params = array() ) {
        // TODO: Call OO_DB::get_job_streams_for_job($job_id, $params)
        // Returns array of OO_Job_Stream objects
        return array();
    }

    public static function get_for_stream_type( $stream_id, $params = array() ) {
        // TODO: Call OO_DB::get_job_streams_for_stream_type($stream_id, $params)
        // Returns array of OO_Job_Stream objects
        return array();
    }

    // --- Instance Methods ---

    public function get_status() {
        return $this->status_in_stream;
    }

    public function update_status( $new_status ) {
        // TODO: Call self::update($this->job_stream_id, array('status_in_stream' => $new_status))
        // Update $this->status_in_stream on success
        return new WP_Error('not_implemented', 'Update status method not implemented yet.');
    }

    public function get_job_logs( $args = array() ) {
        if (! $this->is_valid()) return array();
        $default_args = array(
            'job_id' => $this->job_id,
            'stream_id' => $this->stream_id
            // Add other filters like date range, employee_id etc.
        );
        $merged_args = wp_parse_args($args, $default_args);
        // Calls OO_Job_Log::get_all($merged_args) or OO_DB::get_job_logs($merged_args)
        return OO_DB::get_job_logs($merged_args);
    }

    // Other specific methods related to a job's particular stream instance.

} 