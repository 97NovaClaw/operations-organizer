<?php
// /includes/class-oo-job-log.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class OO_Job_Log
 * 
 * Represents a job log entry in the Operations Organizer system.
 */
class OO_Job_Log {

    public $log_id;
    public $job_id;
    public $stream_id; // Stream Type ID from oo_streams
    public $phase_id;
    public $employee_id;
    public $start_time; // YYYY-MM-DD HH:MM:SS
    public $stop_time;  // YYYY-MM-DD HH:MM:SS or NULL
    public $duration_minutes; // Calculated, INT UNSIGNED or NULL
    public $kpi_data; // Stored as JSON, handled as array/object in class
    public $notes;
    public $log_date; // YYYY-MM-DD, often the date of start_time
    public $created_at;
    public $updated_at;

    protected $data; // Raw data from DB

    // Cached related objects
    protected $_job = null;
    protected $_stream_type = null;
    protected $_phase = null;
    protected $_employee = null;

    /**
     * Constructor.
     *
     * @param int|object|array|null $logData Log ID, or an object/array of log data.
     */
    public function __construct( $logData = null ) {
        if ( is_numeric( $logData ) ) {
            $this->log_id = intval( $logData );
            $this->load();
        } elseif ( is_object( $logData ) || is_array( $logData ) ) {
            $this->populate_from_data( (object) $logData );
        }
    }

    protected function load() {
        if ( ! $this->log_id ) {
            return false;
        }
        $db_data = OO_DB::get_job_log( $this->log_id );
        if ( $db_data ) {
            $this->populate_from_data( $db_data );
            return true;
        }
        return false;
    }

    protected function populate_from_data( $data ) {
        $this->data = $data;
        $this->log_id = isset( $data->log_id ) ? intval( $data->log_id ) : null;
        $this->job_id = isset( $data->job_id ) ? intval( $data->job_id ) : null;
        $this->stream_id = isset( $data->stream_id ) ? intval( $data->stream_id ) : null;
        $this->phase_id = isset( $data->phase_id ) ? intval( $data->phase_id ) : null;
        $this->employee_id = isset( $data->employee_id ) ? intval( $data->employee_id ) : null;
        $this->start_time = isset( $data->start_time ) ? $data->start_time : null;
        $this->stop_time = isset( $data->stop_time ) ? $data->stop_time : null;
        $this->duration_minutes = isset( $data->duration_minutes ) ? intval($data->duration_minutes) : null;
        
        if (isset($data->kpi_data)) {
            $decoded_kpi = json_decode($data->kpi_data, true);
            $this->kpi_data = (json_last_error() === JSON_ERROR_NONE) ? $decoded_kpi : array();
        } else {
            $this->kpi_data = array();
        }

        $this->notes = isset( $data->notes ) ? $data->notes : null;
        $this->log_date = isset( $data->log_date ) ? oo_sanitize_date($data->log_date) : ($this->start_time ? date('Y-m-d', strtotime($this->start_time)) : null) ;
        $this->created_at = isset( $data->created_at ) ? $data->created_at : null;
        $this->updated_at = isset( $data->updated_at ) ? $data->updated_at : null;

        if ($this->start_time && $this->stop_time && is_null($this->duration_minutes)) {
            $this->calculate_duration();
        }
    }

    // Getters
    public function get_id() { return $this->log_id; }
    public function get_job_id() { return $this->job_id; }
    public function get_stream_id() { return $this->stream_id; }
    public function get_phase_id() { return $this->phase_id; }
    public function get_employee_id() { return $this->employee_id; }
    public function get_start_time( $format = null ) { return $format ? date($format, strtotime($this->start_time)) : $this->start_time; }
    public function get_stop_time( $format = null ) { return $this->stop_time ? ($format ? date($format, strtotime($this->stop_time)) : $this->stop_time) : null; }
    public function get_duration_minutes() { return $this->duration_minutes; }
    public function get_kpi_data() { return is_array($this->kpi_data) ? $this->kpi_data : array() ; }
    public function get_kpi_value( $key, $default = null) { return isset($this->kpi_data[$key]) ? $this->kpi_data[$key] : $default; }
    public function get_notes() { return $this->notes; }
    public function get_log_date() { return $this->log_date; }
    public function get_created_at() { return $this->created_at; }
    public function get_updated_at() { return $this->updated_at; }

    // Setters
    public function set_job_id( $id ) { $this->job_id = intval( $id ); $this->_job = null; }
    public function set_stream_id( $id ) { $this->stream_id = intval( $id ); $this->_stream_type = null; }
    public function set_phase_id( $id ) { $this->phase_id = intval( $id ); $this->_phase = null; }
    public function set_employee_id( $id ) { $this->employee_id = intval( $id ); $this->_employee = null; }
    public function set_start_time( $datetime_str ) { $this->start_time = $datetime_str; $this->log_date = date('Y-m-d', strtotime($datetime_str)); $this->calculate_duration(); }
    public function set_stop_time( $datetime_str ) { $this->stop_time = $datetime_str; $this->calculate_duration(); }
    public function set_kpi_data( array $data ) { $this->kpi_data = $data; }
    public function add_kpi_value( $key, $value ) { $this->kpi_data[$key] = $value; }
    public function set_notes( $notes ) { $this->notes = $notes ? sanitize_textarea_field( $notes ) : null; }
    public function set_log_date($date_str) { $this->log_date = oo_sanitize_date($date_str); }

    public function calculate_duration() {
        if ( $this->start_time && $this->stop_time ) {
            $start = strtotime( $this->start_time );
            $stop = strtotime( $this->stop_time );
            if ( $stop > $start ) {
                $this->duration_minutes = round( ( $stop - $start ) / 60 );
            } else {
                $this->duration_minutes = 0; // Or null if preferred for invalid range
            }
        } else {
            $this->duration_minutes = null;
        }
        return $this->duration_minutes;
    }

    public function exists() {
        return !empty($this->log_id) && !empty($this->created_at);
    }

    /**
     * Save the job log (primarily for updates).
     * Creation is typically handled by OO_DB::start_job_phase().
     */
    public function save() {
        if ( !$this->exists() ) {
            // For creating new logs, a more specific method like `start()` or direct use of OO_DB::start_job_phase is preferred.
            // This save method will focus on updates.
            // However, if we need a generic add, we would call a new OO_DB::add_job_log() here.
            // For now, let's assume save is for update.
            return new WP_Error('log_not_exists', 'Log does not exist. Use a start method to create new logs.');
        }

        $this->calculate_duration(); // Ensure duration is up-to-date
        if(empty($this->log_date) && $this->start_time) {
            $this->log_date = date('Y-m-d', strtotime($this->start_time));
        }

        $data_args = array(
            'job_id' => $this->job_id,
            'stream_id' => $this->stream_id,
            'phase_id' => $this->phase_id,
            'employee_id' => $this->employee_id,
            'start_time' => $this->start_time,
            'stop_time' => $this->stop_time,
            'duration_minutes' => $this->duration_minutes,
            'kpi_data' => !empty($this->kpi_data) ? wp_json_encode($this->kpi_data) : null,
            'notes' => $this->notes,
            'log_date' => $this->log_date,
            // created_at is not updated, updated_at is handled by DB
        );

        $result = OO_DB::update_job_log( $this->log_id, $data_args );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        $this->load(); // Reload to get new updated_at and confirm changes
        return $this->log_id;
    }
    
    /**
     * Stop an active log entry.
     * Populates stop_time, calculates duration, and saves.
     * @param array $kpi_updates Key-value pairs to update/add to kpi_data.
     * @param string $notes Additional notes for stopping.
     * @return int|WP_Error Log ID on success, WP_Error on failure.
     */
    public function stop(array $kpi_updates = [], string $notes = '') {
        if (!$this->exists() || $this->stop_time !== null) {
            return new WP_Error('log_not_active', 'Log is not active or already stopped.');
        }
        $this->set_stop_time(current_time('mysql', 1));
        if (!empty($kpi_updates)) {
            $current_kpis = $this->get_kpi_data();
            $this->set_kpi_data(array_merge($current_kpis, $kpi_updates));
        }
        if (!empty($notes)) {
            $this->set_notes(trim($this->get_notes() . "\nStop Notes: " . $notes));
        }
        return $this->save();
    }

    public function delete() {
        if ( ! $this->exists() ) {
            return new WP_Error( 'log_not_exists', 'Cannot delete a log that does not exist.' );
        }
        $result = OO_DB::delete_job_log( $this->log_id );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        if ($result === false ) { 
            return new WP_Error('db_delete_failed', 'Could not delete job log from the database.');
        }
        $former_id = $this->log_id;
        foreach (get_object_vars($this) as $key => $value) {
            if ($key !== 'data') $this->$key = null; // Keep data property for a bit if needed, or null it too
        }
        $this->data = null;
        oo_log('Job Log deleted successfully (Former ID: ' . $former_id . ')', __METHOD__);
        return true;
    }

    public static function get_by_id( $log_id ) {
        $instance = new self( $log_id );
        return $instance->exists() ? $instance : null;
    }

    // Methods to get related objects
    public function get_job() {
        if ( $this->_job === null && $this->job_id && class_exists('OO_Job') ) {
            $this->_job = OO_Job::get_by_id( $this->job_id );
        }
        return $this->_job;
    }

    public function get_stream_type() {
        if ( $this->_stream_type === null && $this->stream_id && class_exists('OO_Stream') ) {
            $this->_stream_type = OO_Stream::get_by_id( $this->stream_id );
        }
        return $this->_stream_type;
    }

    public function get_phase() {
        if ( $this->_phase === null && $this->phase_id && class_exists('OO_Phase') ) {
            $this->_phase = OO_Phase::get_by_id( $this->phase_id );
        }
        return $this->_phase;
    }

    public function get_employee() {
        if ( $this->_employee === null && $this->employee_id && class_exists('OO_Employee') ) {
            $this->_employee = OO_Employee::get_by_id( $this->employee_id );
        }
        return $this->_employee;
    }
    
    public static function get_job_logs( $args = array() ) {
        $datas = OO_DB::get_job_logs( $args );
        $instances = array();
        if (is_array($datas)){
            foreach ( $datas as $data ) {
                $instances[] = new self( $data );
            }
        }
        return $instances;
    }

    public static function get_job_logs_count( $args = array() ) {
        return OO_DB::get_job_logs_count( $args );
    }
    
    /**
     * Static method to start a new job log entry.
     * This is a convenience wrapper around OO_DB::start_job_phase and returns an OO_Job_Log object.
     */
    public static function start_new_log( $employee_id, $job_id, $stream_id, $phase_id, $notes = '', $kpi_data = null ) {
        $log_id = OO_DB::start_job_phase( $employee_id, $job_id, $stream_id, $phase_id, $notes, $kpi_data );
        if (is_wp_error($log_id)) {
            return $log_id;
        }
        if ($log_id) {
            return new self($log_id);
        }
        return null;
    }
} 