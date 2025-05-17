<?php
// /includes/class-oo-employee.php // Renamed file

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Employee { // Renamed class

    public $employee_id;
    public $wp_user_id;
    public $employee_number;
    public $employee_pin; // Should be stored hashed
    public $first_name;
    public $last_name;
    public $job_title;
    public $is_active;
    public $created_at;
    public $updated_at;

    protected $data;

    public function __construct( $employee_data = null ) {
        if ( is_numeric( $employee_data ) ) {
            $this->employee_id = intval( $employee_data );
            $this->load();
        } elseif ( is_object( $employee_data ) || is_array( $employee_data ) ) {
            $this->populate_from_data( (object) $employee_data );
        }
    }

    protected function load() {
        if ( ! $this->employee_id ) {
            return false;
        }
        $db_data = OO_DB::get_employee( $this->employee_id );
        if ( $db_data ) {
            $this->populate_from_data( $db_data );
            return true;
        }
        return false;
    }

    protected function populate_from_data( $data ) {
        $this->data = $data;
        $this->employee_id = isset( $data->employee_id ) ? intval( $data->employee_id ) : null;
        $this->wp_user_id = isset( $data->wp_user_id ) ? intval( $data->wp_user_id ) : null;
        $this->employee_number = isset( $data->employee_number ) ? $data->employee_number : null;
        $this->employee_pin = isset( $data->employee_pin ) ? $data->employee_pin : null; // Raw from DB (already hashed)
        $this->first_name = isset( $data->first_name ) ? $data->first_name : null;
        $this->last_name = isset( $data->last_name ) ? $data->last_name : null;
        $this->job_title = isset( $data->job_title ) ? $data->job_title : null;
        $this->is_active = isset( $data->is_active ) ? (bool) $data->is_active : true;
        $this->created_at = isset( $data->created_at ) ? $data->created_at : null;
        $this->updated_at = isset( $data->updated_at ) ? $data->updated_at : null;
    }

    // Getters
    public function get_id() { return $this->employee_id; }
    public function get_wp_user_id() { return $this->wp_user_id; }
    public function get_employee_number() { return $this->employee_number; }
    // public function get_employee_pin() { return $this->employee_pin; } // Avoid exposing hashed pin directly
    public function get_first_name() { return $this->first_name; }
    public function get_last_name() { return $this->last_name; }
    public function get_full_name() { return trim( $this->first_name . ' ' . $this->last_name ); }
    public function get_job_title() { return $this->job_title; }
    public function is_active() { return (bool) $this->is_active; }
    public function get_created_at() { return $this->created_at; }
    public function get_updated_at() { return $this->updated_at; }

    // Setters
    public function set_wp_user_id( $id ) { $this->wp_user_id = $id ? intval( $id ) : null; }
    public function set_employee_number( $number ) { $this->employee_number = $number ? sanitize_text_field( $number ) : null; }
    
    /**
     * Set the employee PIN. The PIN should be hashed before calling this method or handled within.
     * For now, this directly sets the (presumably pre-hashed) PIN.
     */
    public function set_employee_pin( $hashed_pin ) { 
        $this->employee_pin = $hashed_pin; 
    }
    // TODO: Add a method like set_raw_pin($raw_pin) that handles hashing with wp_hash_password() or similar.

    public function set_first_name( $name ) { $this->first_name = sanitize_text_field( $name ); }
    public function set_last_name( $name ) { $this->last_name = sanitize_text_field( $name ); }
    public function set_job_title( $title ) { $this->job_title = $title ? sanitize_text_field( $title ) : null; }
    public function set_active( $is_active ) { $this->is_active = (bool) $is_active; }

    public function exists() {
        return !empty($this->employee_id) && !empty($this->created_at);
    }

    public function save() {
        // Consolidate all settable fields from the DB schema.
        // OO_DB::add_employee and update_employee need to be updated to accept all these.
        $data = array(
            'wp_user_id' => $this->wp_user_id,
            'employee_number' => $this->employee_number,
            // 'employee_pin' should be handled carefully. If it's already hashed, pass it.
            // If a raw PIN was set and needs hashing, hash it here before sending to DB.
            'employee_pin' => $this->employee_pin, 
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'job_title' => $this->job_title,
            'is_active' => $this->is_active ? 1 : 0,
        );

        if ( empty( $this->first_name ) || empty( $this->last_name ) ) {
            return new WP_Error('missing_name', 'First Name and Last Name are required.');
        }
        // Either employee_number or wp_user_id should be present ideally.
        if ( empty( $this->employee_number ) && empty( $this->wp_user_id ) ) {
            // This logic might need adjustment based on exact requirements.
            // return new WP_Error('missing_identifier', 'Either Employee Number or WP User ID must be provided.');
        }

        if ( $this->exists() ) {
            // OO_DB::update_employee needs to be able to handle all fields.
            // The existing OO_DB::update_employee takes: $employee_id, $employee_number, $first_name, $last_name, $is_active
            // It needs to be extended or a new comprehensive update method created.
            // For now, we'll assume OO_DB::update_employee is updated or we pass what it accepts.
            $update_data = array(
                'employee_number' => $this->employee_number,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'is_active' => $this->is_active ? 1 : 0,
                // Add other fields if OO_DB::update_employee is extended
                'wp_user_id' => $this->wp_user_id,
                'employee_pin' => $this->employee_pin, // Pass if OO_DB handles it
                'job_title' => $this->job_title,
            );
            $result = OO_DB::update_employee( $this->employee_id, $update_data ); // MODIFIED: Pass $update_data array
            
            if ( is_wp_error( $result ) ) {
                return $result;
            }
            $this->load(); 
            return $this->employee_id;
        } else {
            // OO_DB::add_employee also needs to handle all fields.
            // Currently takes: $employee_number, $first_name, $last_name (implicitly sets is_active=1)
            // It needs to be extended.
            // For now, passing all available data in $data.
            $new_id = OO_DB::add_employee( $data ); // MODIFIED: Pass $data array
            if ( is_wp_error( $new_id ) ) {
                return $new_id;
            }
            $this->employee_id = $new_id;
            $this->load(); 
            return $this->employee_id;
        }
    }

    /**
     * Delete the employee.
     * Consider implications: job logs, expenses etc. use employee_id (mostly with ON DELETE RESTRICT or SET NULL).
     */
    public function delete() {
        if ( ! $this->exists() ) {
            return new WP_Error( 'employee_not_exists', 'Cannot delete an employee that does not exist.' );
        }
        
        // TODO: Add checks here if this employee is referenced in critical places with RESTRICT before calling DB delete.
        // For example, check active job logs or assignments.
        // $job_logs_count = OO_DB::get_job_logs_count(['employee_id' => $this->employee_id, 'status' => 'started']);
        // if ($job_logs_count > 0) { return new WP_Error('employee_has_active_logs', ...); }

        $result = OO_DB::delete_employee( $this->employee_id ); // This method needs to be created in OO_DB
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        if ($result === false ) { 
            return new WP_Error('db_delete_failed', 'Could not delete employee from the database.');
        }
        $former_id = $this->employee_id;
        foreach (get_object_vars($this) as $key => $value) {
            $this->$key = null;
        }
        oo_log('Employee deleted successfully (Former ID: ' . $former_id . ')', __METHOD__);
        return true;
    }

    public function toggle_status( $is_active = null ) {
        if ( ! $this->exists() ) {
            return new WP_Error( 'employee_not_exists', 'Employee must exist to toggle status.' );
        }
        $new_status = is_null($is_active) ? !$this->is_active : (bool) $is_active;
        $result = OO_DB::toggle_employee_status( $this->employee_id, $new_status );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        $this->is_active = $new_status;
        $this->load(); // to refresh updated_at
        return true;
    }

    public static function get_by_id( $employee_id ) {
        $instance = new self( $employee_id );
        return $instance->exists() ? $instance : null;
    }

    public static function get_by_number( $employee_number ) {
        $data = OO_DB::get_employee_by_number( $employee_number );
        if ( $data ) {
            return new self( $data );
        }
        return null;
    }
    
    public static function get_by_wp_user_id( $wp_user_id ) {
        if (empty($wp_user_id) || !is_numeric($wp_user_id)) return null;
        // This requires a new method in OO_DB: get_employee_by_wp_user_id()
        $data = OO_DB::get_employee_by_wp_user_id( intval($wp_user_id) ); 
        if ( $data ) {
            return new self( $data );
        }
        return null;
    }

    public static function get_employees( $args = array() ) {
        $datas = OO_DB::get_employees( $args );
        $instances = array();
        if (is_array($datas)) {
            foreach ( $datas as $data ) {
                $instances[] = new self( $data );
            }
        }
        return $instances;
    }

    public static function get_employees_count( $args = array() ) {
        return OO_DB::get_employees_count( $args );
    }
    
    // TODO: Method for verifying PIN: public function verify_pin($raw_pin) { return wp_check_password($raw_pin, $this->employee_pin); }
} 