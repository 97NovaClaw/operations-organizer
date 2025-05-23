<?php
// /includes/class-oo-expense.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class OO_Expense
 * 
 * Represents an expense record in the Operations Organizer system.
 */
class OO_Expense {

    public $expense_id;
    public $job_id;
    public $stream_id; // This is the stream_id from oo_streams (type), not oo_job_streams.job_stream_id
    public $expense_type_id;
    public $employee_id;
    public $amount;
    public $expense_date;
    public $description;
    public $receipt_image_url;
    public $related_log_id;
    public $created_at;
    public $updated_at;

    /**
     * @var object Raw data from the database.
     */
    protected $data;

    // Cached related objects
    protected $_job = null;
    protected $_stream_type = null; // Represents the stream type definition
    protected $_expense_type = null;
    protected $_employee = null;
    protected $_job_log = null;

    /**
     * Constructor.
     *
     * @param int|object|array|null $data Expense ID, or an object/array of expense data.
     */
    public function __construct( $data = null ) {
        if ( is_numeric( $data ) ) {
            $this->expense_id = intval( $data );
            $this->load();
        } elseif ( is_object( $data ) || is_array( $data ) ) {
            $this->populate_from_data( (object) $data );
        }
    }

    protected function load() {
        if ( ! $this->expense_id ) {
            return false;
        }
        $db_data = OO_DB::get_expense( $this->expense_id );
        if ( $db_data ) {
            $this->populate_from_data( $db_data );
            return true;
        }
        return false;
    }

    protected function populate_from_data( $data ) {
        $this->data = $data;
        $this->expense_id = isset( $data->expense_id ) ? intval( $data->expense_id ) : null;
        $this->job_id = isset( $data->job_id ) ? intval( $data->job_id ) : null;
        $this->stream_id = isset( $data->stream_id ) ? intval( $data->stream_id ) : null;
        $this->expense_type_id = isset( $data->expense_type_id ) ? intval( $data->expense_type_id ) : null;
        $this->employee_id = isset( $data->employee_id ) ? intval( $data->employee_id ) : null;
        $this->amount = isset( $data->amount ) ? $data->amount : null; // Stored as DECIMAL, retrieve as string/float
        $this->expense_date = isset( $data->expense_date ) ? oo_sanitize_date( $data->expense_date ) : null;
        $this->description = isset( $data->description ) ? $data->description : null;
        $this->receipt_image_url = isset( $data->receipt_image_url ) ? $data->receipt_image_url : null;
        $this->related_log_id = isset( $data->related_log_id ) ? intval( $data->related_log_id ) : null;
        $this->created_at = isset( $data->created_at ) ? $data->created_at : null;
        $this->updated_at = isset( $data->updated_at ) ? $data->updated_at : null;
    }

    // Getters
    public function get_id() { return $this->expense_id; }
    public function get_job_id() { return $this->job_id; }
    public function get_stream_id() { return $this->stream_id; } // ID of the stream type
    public function get_expense_type_id() { return $this->expense_type_id; }
    public function get_employee_id() { return $this->employee_id; }
    public function get_amount() { return $this->amount; }
    public function get_expense_date() { return $this->expense_date; }
    public function get_description() { return $this->description; }
    public function get_receipt_image_url() { return $this->receipt_image_url; }
    public function get_related_log_id() { return $this->related_log_id; }
    public function get_created_at() { return $this->created_at; }
    public function get_updated_at() { return $this->updated_at; }

    // Setters
    public function set_job_id( $id ) { $this->job_id = intval( $id ); }
    public function set_stream_id( $id ) { $this->stream_id = $id ? intval( $id ) : null; } // Stream Type ID
    public function set_expense_type_id( $id ) { $this->expense_type_id = intval( $id ); }
    public function set_employee_id( $id ) { $this->employee_id = $id ? intval( $id ) : null; }
    public function set_amount( $amount ) { 
        // Assuming wc_format_decimal or similar is available, otherwise use number_format for precision.
        // Storing as it comes, OO_DB handles formatting for save.
        $this->amount = $amount; 
    }
    public function set_expense_date( $date ) { $this->expense_date = oo_sanitize_date( $date ); }
    public function set_description( $desc ) { $this->description = $desc ? sanitize_textarea_field( $desc ) : null; }
    public function set_receipt_image_url( $url ) { $this->receipt_image_url = $url ? esc_url_raw( $url ) : null; }
    public function set_related_log_id( $id ) { $this->related_log_id = $id ? intval( $id ) : null; }

    public function exists() {
        return !empty($this->expense_id) && !empty($this->created_at);
    }

    public function save() {
        $data_args = array(
            'job_id' => $this->job_id,
            'stream_id' => $this->stream_id,
            'expense_type_id' => $this->expense_type_id,
            'employee_id' => $this->employee_id,
            'amount' => $this->amount, // DB layer will format with wc_format_decimal
            'expense_date' => $this->expense_date,
            'description' => $this->description,
            'receipt_image_url' => $this->receipt_image_url,
            'related_log_id' => $this->related_log_id,
        );

        if ( empty($this->job_id) || empty($this->expense_type_id) || !isset($this->amount) || empty($this->expense_date) ) {
            return new WP_Error('missing_fields', 'Job ID, Expense Type ID, Amount, and Expense Date are required.');
        }

        if ( $this->exists() ) {
            $result = OO_DB::update_expense( $this->expense_id, $data_args );
            if ( is_wp_error( $result ) ) {
                return $result;
            }
            $this->load(); 
            return $this->expense_id;
        } else {
            $new_id = OO_DB::add_expense( $data_args );
            if ( is_wp_error( $new_id ) ) {
                return $new_id;
            }
            $this->expense_id = $new_id;
            $this->load(); 
            return $this->expense_id;
        }
    }

    public function delete() {
        if ( ! $this->exists() ) {
            return new WP_Error( 'expense_not_exists', 'Cannot delete an expense that does not exist.' );
        }
        $result = OO_DB::delete_expense( $this->expense_id );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        if ($result === false ) { 
            return new WP_Error('db_delete_failed', 'Could not delete expense from the database.');
        }
        $former_id = $this->expense_id;
        foreach (get_object_vars($this) as $key => $value) {
            $this->$key = null;
        }
        oo_log('Expense deleted successfully (Former ID: ' . $former_id . ')', __METHOD__);
        return true;
    }

    public static function get_by_id( $expense_id ) {
        $instance = new self( $expense_id );
        return $instance->exists() ? $instance : null;
    }

    // Methods to get related objects
    public function get_job() {
        if ( $this->_job === null && $this->job_id && class_exists('OO_Job') ) {
            $this->_job = OO_Job::get_by_id( $this->job_id );
        }
        return $this->_job;
    }

    /** 
     * Gets the Stream Type definition for this expense.
     * Note: An expense is linked to a job_id and optionally a stream_id (which is the stream_type_id).
     * It's not directly linked to a job_stream_id.
     */
    public function get_stream_type() {
        if ( $this->_stream_type === null && $this->stream_id && class_exists('OO_Stream') ) {
            $this->_stream_type = OO_Stream::get_by_id( $this->stream_id );
        }
        return $this->_stream_type;
    }

    public function get_expense_type() {
        if ( $this->_expense_type === null && $this->expense_type_id && class_exists('OO_Expense_Type') ) {
            $this->_expense_type = OO_Expense_Type::get_by_id( $this->expense_type_id );
        }
        return $this->_expense_type;
    }

    public function get_employee() {
        if ( $this->_employee === null && $this->employee_id && class_exists('OO_Employee') ) {
            $this->_employee = OO_Employee::get_by_id( $this->employee_id ); // Assumes OO_Employee::get_by_id exists
        }
        return $this->_employee;
    }

    public function get_job_log() {
        if ( $this->_job_log === null && $this->related_log_id && class_exists('OO_Job_Log') ) {
            $this->_job_log = OO_Job_Log::get_by_id( $this->related_log_id ); // Assumes OO_Job_Log::get_by_id exists
        }
        return $this->_job_log;
    }
    
    public static function get_expenses( $args = array() ) {
        $datas = OO_DB::get_expenses( $args );
        $instances = array();
        if (is_array($datas)) {
            foreach ( $datas as $data ) {
                $instances[] = new self( $data );
            }
        }
        return $instances;
    }

    public static function get_expenses_count( $args = array() ) {
        return OO_DB::get_expenses_count( $args );
    }
}

// Optional: A simple class for ExpenseType if it needs more logic than just data from DB
/*
class OO_Expense_Type {
    public $expense_type_id;
    public $type_name;
    public $default_unit;
    public $is_active;

    public function __construct($expense_type_id = null) {
        if ($expense_type_id) {
            $data = OO_DB::get_expense_type($expense_type_id); // Assuming this exists
            if ($data) {
                $this->expense_type_id = $data->expense_type_id;
                $this->type_name = $data->type_name;
                $this->default_unit = $data->default_unit;
                $this->is_active = $data->is_active;
            }
        }
    }
}
*/ 