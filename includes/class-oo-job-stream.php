<?php
// /includes/class-oo-job-stream.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class OO_Job_Stream
 * 
 * Represents an instance of a stream type associated with a specific job.
 */
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
    public $created_at;
    public $updated_at;

    /**
     * @var object Raw data from the database.
     */
    protected $data;

    /**
     * @var OO_Job|null Cached Job object.
     */
    protected $_job = null;

    /**
     * @var OO_Stream|null Cached Stream (type) object.
     */
    protected $_stream_type = null;

    /**
     * @var OO_Building|null Cached Building object.
     */
    protected $_building = null;

    /**
     * Constructor.
     *
     * @param int|object|array|null $data Job Stream ID, or an object/array of job stream data.
     */
    public function __construct( $data = null ) {
        if ( is_numeric( $data ) ) {
            $this->job_stream_id = intval( $data );
            $this->load();
        } elseif ( is_object( $data ) || is_array( $data ) ) {
            $this->populate_from_data( (object) $data );
        }
    }

    protected function load() {
        if ( ! $this->job_stream_id ) {
            return false;
        }
        $db_data = OO_DB::get_job_stream( $this->job_stream_id );
        if ( $db_data ) {
            $this->populate_from_data( $db_data );
            return true;
        }
        return false;
    }

    protected function populate_from_data( $data ) {
        $this->data = $data;
        $this->job_stream_id = isset( $data->job_stream_id ) ? intval( $data->job_stream_id ) : null;
        $this->job_id = isset( $data->job_id ) ? intval( $data->job_id ) : null;
        $this->stream_id = isset( $data->stream_id ) ? intval( $data->stream_id ) : null;
        $this->status_in_stream = isset( $data->status_in_stream ) ? $data->status_in_stream : 'Not Started';
        $this->assigned_manager_id = isset( $data->assigned_manager_id ) ? intval( $data->assigned_manager_id ) : null;
        $this->start_date_stream = isset( $data->start_date_stream ) ? oo_sanitize_date( $data->start_date_stream ) : null;
        $this->due_date_stream = isset( $data->due_date_stream ) ? oo_sanitize_date( $data->due_date_stream ) : null;
        $this->building_id = isset( $data->building_id ) ? intval( $data->building_id ) : null;
        $this->notes = isset( $data->notes ) ? $data->notes : null;
        $this->created_at = isset( $data->created_at ) ? $data->created_at : null;
        $this->updated_at = isset( $data->updated_at ) ? $data->updated_at : null;
    }

    public function get_id() { return $this->job_stream_id; }
    public function get_job_id() { return $this->job_id; }
    public function get_stream_id() { return $this->stream_id; }
    public function get_status_in_stream() { return $this->status_in_stream; }
    public function get_assigned_manager_id() { return $this->assigned_manager_id; }
    public function get_start_date_stream() { return $this->start_date_stream; }
    public function get_due_date_stream() { return $this->due_date_stream; }
    public function get_building_id() { return $this->building_id; }
    public function get_notes() { return $this->notes; }
    public function get_created_at() { return $this->created_at; }
    public function get_updated_at() { return $this->updated_at; }

    public function set_job_id( $job_id ) { $this->job_id = intval( $job_id ); }
    public function set_stream_id( $stream_id ) { $this->stream_id = intval( $stream_id ); }
    public function set_status_in_stream( $status ) { $this->status_in_stream = sanitize_text_field( $status ); }
    public function set_assigned_manager_id( $manager_id ) { $this->assigned_manager_id = $manager_id ? intval( $manager_id ) : null; }
    public function set_start_date_stream( $date ) { $this->start_date_stream = oo_sanitize_date( $date ); }
    public function set_due_date_stream( $date ) { $this->due_date_stream = oo_sanitize_date( $date ); }
    public function set_building_id( $building_id ) { $this->building_id = $building_id ? intval( $building_id ) : null; }
    public function set_notes( $notes ) { $this->notes = $notes ? sanitize_textarea_field( $notes ) : null; }
    
    public function exists() {
        return !empty($this->job_stream_id) && !empty($this->created_at);
    }

    public function save() {
        $data = array(
            'job_id' => $this->job_id,
            'stream_id' => $this->stream_id,
            'status_in_stream' => $this->status_in_stream,
            'assigned_manager_id' => $this->assigned_manager_id,
            'start_date_stream' => $this->start_date_stream,
            'due_date_stream' => $this->due_date_stream,
            'building_id' => $this->building_id,
            'notes' => $this->notes,
        );

        if ( empty( $this->job_id ) || empty( $this->stream_id ) ) {
            return new WP_Error('missing_ids', 'Job ID and Stream ID are required to save a job stream.');
        }

        if ( $this->exists() ) {
            $result = OO_DB::update_job_stream( $this->job_stream_id, $data );
            if ( is_wp_error( $result ) ) {
                oo_log('Error updating job stream (ID: ' . $this->job_stream_id . '): ' . $result->get_error_message(), __METHOD__);
                return $result;
            }
            $this->load(); 
            oo_log('Job stream updated successfully (ID: ' . $this->job_stream_id . ')', __METHOD__);
            return $this->job_stream_id;
        } else {
            $new_id = OO_DB::add_job_stream( $data );
            if ( is_wp_error( $new_id ) ) {
                oo_log('Error adding new job stream: ' . $new_id->get_error_message(), __METHOD__);
                return $new_id;
            }
            $this->job_stream_id = $new_id;
            $this->load(); 
            oo_log('New job stream added successfully (ID: ' . $this->job_stream_id . ')', __METHOD__);
            return $this->job_stream_id;
        }
    }

    public function delete() {
        if ( ! $this->exists() ) {
            return new WP_Error( 'job_stream_not_exists', 'Cannot delete a job stream that does not exist.' );
        }
        $result = OO_DB::delete_job_stream( $this->job_stream_id );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        if ($result === false ) { // OO_DB::delete_job_stream might return false on wpdb error
            oo_log('Failed to delete job stream (ID: ' . $this->job_stream_id . ') from database.', __METHOD__);
            return new WP_Error('db_delete_failed', 'Could not delete job stream from the database.');
        }
        $former_id = $this->job_stream_id;
        foreach (get_object_vars($this) as $key => $value) {
            $this->$key = null;
        }
        oo_log('Job stream deleted successfully (Former ID: ' . $former_id . ')', __METHOD__);
        return true;
    }

    public static function get_by_id( $job_stream_id ) {
        $instance = new self( $job_stream_id );
        return $instance->exists() ? $instance : null;
    }
    
    public static function get_by_job_and_stream( $job_id, $stream_id ) {
        $data = OO_DB::get_job_stream_by_job_and_stream( $job_id, $stream_id );
        if ( $data ) {
            return new self( $data );
        }
        return null;
    }

    public function get_job() {
        if ($this->_job === null && $this->job_id && class_exists('OO_Job')) {
            $this->_job = OO_Job::get_by_id($this->job_id);
        }
        return $this->_job;
    }

    public function get_stream_type() {
        if ($this->_stream_type === null && $this->stream_id && class_exists('OO_Stream')) {
            $this->_stream_type = OO_Stream::get_by_id($this->stream_id);
        }
        return $this->_stream_type;
    }

    public function get_building() {
        if ($this->_building === null && $this->building_id && class_exists('OO_Building')) {
            $this->_building = OO_Building::get_by_id($this->building_id);
        }
        return $this->_building;
    }
    
    /**
     * Get all job streams for a specific job ID.
     */
    public static function get_for_job( $job_id, $args = array() ) {
        $datas = OO_DB::get_job_streams_for_job( $job_id, $args );
        $instances = array();
        foreach ( $datas as $data ) {
            $instances[] = new self( $data );
        }
        return $instances;
    }

    /**
     * Get all job streams for a specific stream type ID.
     */
    public static function get_for_stream_type( $stream_id, $args = array() ) {
        $datas = OO_DB::get_job_streams_by_stream( $stream_id, $args );
        $instances = array();
        foreach ( $datas as $data ) {
            $instances[] = new self( $data );
        }
        return $instances;
    }
    
    public static function get_job_streams( $args = array() ) {
        $datas = OO_DB::get_job_streams( $args );
        $instances = array();
        foreach ( $datas as $data ) {
            $instances[] = new self( $data );
        }
        return $instances;
    }

    public static function get_job_streams_count( $args = array() ) {
        return OO_DB::get_job_streams_count( $args );
    }
} 