<?php
// /includes/class-oo-expense.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Expense {

    public $expense_id;
    public $job_id;
    public $stream_id; // Optional
    public $expense_type_id;
    public $employee_id; // Optional
    public $amount;
    public $expense_date;
    public $description;
    public $receipt_image_url;
    public $related_log_id; // Optional

    // public $expense_type_object; // Could hold OO_Expense_Type object

    public function __construct( $expense_id = null ) {
        if ( $expense_id ) {
            $this->load_expense( $expense_id );
        }
    }

    private function load_expense( $expense_id ) {
        // TODO: Implement method to load expense details from wp_oo_expenses
        // $data = OO_DB::get_expense( $expense_id ); (method to be created in OO_DB)
        // if ($data) { $this->populate_properties($data); }
    }

    private function populate_properties( $data ) {
        $this->expense_id = $data->expense_id;
        $this->job_id = $data->job_id;
        $this->stream_id = $data->stream_id;
        $this->expense_type_id = $data->expense_type_id;
        $this->employee_id = $data->employee_id;
        $this->amount = $data->amount;
        $this->expense_date = $data->expense_date;
        $this->description = $data->description;
        $this->receipt_image_url = $data->receipt_image_url;
        $this->related_log_id = $data->related_log_id;
        // Optionally load OO_Expense_Type object
        // $this->expense_type_object = new OO_Expense_Type($this->expense_type_id);
    }
    
    public function is_valid() {
        return !empty($this->expense_id);
    }

    // --- Static CRUD-like Methods for Expenses ---

    public static function create( $args ) {
        // TODO: Call OO_DB::add_expense($args)
        // $args includes job_id, expense_type_id, amount, expense_date etc.
        // Returns new expense_id or WP_Error
        return new WP_Error('not_implemented', 'Create expense method not implemented yet.');
    }

    public static function get( $expense_id ) {
        // TODO: Call OO_DB::get_expense($expense_id)
        // Returns OO_Expense object or null
        return null;
    }

    public static function update( $expense_id, $args ) {
        // TODO: Call OO_DB::update_expense($expense_id, $args)
        // Returns true or WP_Error
        return new WP_Error('not_implemented', 'Update expense method not implemented yet.');
    }

    public static function delete( $expense_id ) {
        // TODO: Call OO_DB::delete_expense($expense_id)
        // Returns true or WP_Error
        return new WP_Error('not_implemented', 'Delete expense method not implemented yet.');
    }

    public static function get_for_job( $job_id, $params = array() ) {
        // TODO: Call OO_DB::get_expenses($params with job_id)
        // Returns array of OO_Expense objects
        return array();
    }

    public static function get_for_stream( $job_id, $stream_id, $params = array() ) {
        // TODO: Call OO_DB::get_expenses($params with job_id and stream_id)
        // Returns array of OO_Expense objects
        return array();
    }

    // --- Static CRUD-like Methods for Expense Types ---

    public static function create_type( $type_name, $default_unit = null, $is_active = 1 ) {
        // TODO: Call OO_DB::add_expense_type($type_name, $default_unit, $is_active)
        // Returns new expense_type_id or WP_Error
        return new WP_Error('not_implemented', 'Create expense type method not implemented yet.');
    }

    public static function get_type( $expense_type_id ) {
        // TODO: Call OO_DB::get_expense_type($expense_type_id)
        // Returns expense type object/array or null
        return null;
    }

    public static function update_type( $expense_type_id, $type_name, $default_unit = null, $is_active = null ) {
        // TODO: Call OO_DB::update_expense_type($expense_type_id, ...)
        // Returns true or WP_Error
        return new WP_Error('not_implemented', 'Update expense type method not implemented yet.');
    }

    public static function get_all_types( $args = array() ) {
        // TODO: Call OO_DB::get_expense_types($args)
        // Returns array of expense type objects
        return array();
    }
    
    public static function get_active_expense_types_for_select() {
        // TODO: Fetch active expense types from OO_DB::get_expense_types()
        // Format as ID => Name array for dropdowns
        return array(); // Placeholder
    }

    // --- Instance Methods ---
    public function get_amount_formatted() {
        // return number_format($this->amount, 2);
        return '0.00'; // Placeholder
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