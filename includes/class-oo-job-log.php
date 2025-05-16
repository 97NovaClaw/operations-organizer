<?php
// /includes/class-oo-job-log.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Job_Log {

    public $log_id;
    public $job_id;
    public $stream_id;
    public $phase_id;
    public $employee_id;
    public $start_time;
    public $stop_time;
    public $duration_minutes;
    public $kpi_data; // Should be an array or object after decoding JSON
    public $notes;
    public $log_date;

    // Properties from joined tables for convenience (populated by get methods)
    public $employee_first_name;
    public $employee_last_name;
    public $employee_number;
    public $phase_name;
    public $stream_name;
    // public $job_number; // If OO_DB::get_job_logs joins with jobs table

    public function __construct( $log_id = null ) {
        if ( $log_id ) {
            $this->load_log( $log_id );
        }
    }

    private function load_log( $log_id ) {
        // TODO: Implement method to load log details from wp_oo_job_logs
        // $data = OO_DB::get_job_log( $log_id ); (method to be updated in OO_DB)
        // if ($data) { $this->populate_properties($data); }
    }

    private function populate_properties( $data ) {
        // Cast basic properties
        $this->log_id = $data->log_id;
        $this->job_id = $data->job_id;
        $this->stream_id = $data->stream_id;
        $this->phase_id = $data->phase_id;
        $this->employee_id = $data->employee_id;
        $this->start_time = $data->start_time;
        $this->stop_time = $data->stop_time;
        $this->duration_minutes = $data->duration_minutes;
        $this->notes = $data->notes;
        $this->log_date = $data->log_date;

        // Decode KPI data
        if ( ! empty( $data->kpi_data ) ) {
            $decoded_kpi = json_decode( $data->kpi_data, true );
            $this->kpi_data = ( json_last_error() === JSON_ERROR_NONE ) ? $decoded_kpi : array();
        } else {
            $this->kpi_data = array();
        }
        
        // Populate joined data if available
        if (isset($data->first_name)) $this->employee_first_name = $data->first_name;
        if (isset($data->last_name)) $this->employee_last_name = $data->last_name;
        if (isset($data->employee_number)) $this->employee_number = $data->employee_number;
        if (isset($data->phase_name)) $this->phase_name = $data->phase_name;
        if (isset($data->stream_name)) $this->stream_name = $data->stream_name;
        // if (isset($data->job_number)) $this->job_number = $data->job_number;
    }
    
    public function is_valid() {
        return !empty($this->log_id);
    }

    // --- Static CRUD-like Methods ---

    public static function start( $employee_id, $job_id, $stream_id, $phase_id, $notes = '', $kpi_data_initial = null ) {
        // TODO: Call OO_DB::start_job_phase (which needs to be updated for job_id, stream_id etc.)
        // Returns new log_id or WP_Error
        return OO_DB::start_job_phase($employee_id, $job_id, $stream_id, $phase_id, $notes, $kpi_data_initial);
    }

    public static function stop( $employee_id, $job_id, $stream_id, $phase_id, $kpi_data_updates = array(), $notes = '' ) {
        // TODO: Call OO_DB::stop_job_phase (needs update for job_id, stream_id etc.)
        // Returns true or WP_Error
        return OO_DB::stop_job_phase($employee_id, $job_id, $stream_id, $phase_id, $kpi_data_updates, $notes);
    }

    public static function get( $log_id ) {
        // TODO: Call OO_DB::get_job_log($log_id) which should return comprehensive data
        // $data = OO_DB::get_job_log($log_id);
        // if ($data) { $obj = new self(); $obj->populate_properties($data); return $obj; }
        return null;
    }

    public static function update_details( $log_id, $args ) {
        // For updating notes, KPI data, start/stop times (carefully), etc.
        // TODO: Call OO_DB::update_job_log($log_id, $args)
        return new WP_Error('not_implemented', 'Update log details method not implemented yet.');
    }

    public static function delete( $log_id ) {
        // TODO: Call OO_DB::delete_job_log($log_id)
        return new WP_Error('not_implemented', 'Delete log method not implemented yet.');
    }

    public static function get_all( $params = array() ) {
        // TODO: Call OO_DB::get_job_logs($params)
        // which should return an array of stdClass objects with joined data
        // This method could then map these to OO_Job_Log instances
        $logs_data = OO_DB::get_job_logs( $params );
        $job_logs = array();
        foreach ( $logs_data as $log_data ) {
            $job_log = new self();
            $job_log->populate_properties( $log_data );
            $job_logs[] = $job_log;
        }
        return $job_logs;
    }
    
    public static function get_open_log_for_employee_phase($employee_id, $job_id, $stream_id, $phase_id) {
        // $data = OO_DB::get_open_job_log($employee_id, $job_id, $stream_id, $phase_id);
        // if ($data) { $obj = new self(); $obj->populate_properties($data); return $obj; }
        return null; // Placeholder
    }

    // --- Instance Methods ---

    public function get_duration_formatted() {
        if ( $this->duration_minutes === null ) return 'In Progress';
        $hours = floor( $this->duration_minutes / 60 );
        $minutes = $this->duration_minutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }
    
    public function get_kpi_value($key) {
        return isset($this->kpi_data[$key]) ? $this->kpi_data[$key] : null;
    }

    // Other log-specific methods, e.g., for calculating efficiency if targets are known.
} 