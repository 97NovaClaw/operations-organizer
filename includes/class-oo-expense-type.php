<?php
// /includes/class-oo-expense-type.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class OO_Expense_Type
 * 
 * Represents an expense type in the Operations Organizer system.
 */
class OO_Expense_Type {

    public $expense_type_id;
    public $type_name;
    public $default_unit;
    public $is_active;
    public $created_at;
    public $updated_at;

    /**
     * @var object Raw data from the database.
     */
    protected $data;

    /**
     * Constructor.
     *
     * @param int|object|array|null $data Expense Type ID, or an object/array of expense type data.
     */
    public function __construct( $data = null ) {
        if ( is_numeric( $data ) ) {
            $this->expense_type_id = intval( $data );
            $this->load();
        } elseif ( is_object( $data ) || is_array( $data ) ) {
            $this->populate_from_data( (object) $data );
        }
    }

    protected function load() {
        if ( ! $this->expense_type_id ) {
            return false;
        }
        $db_data = OO_DB::get_expense_type( $this->expense_type_id );
        if ( $db_data ) {
            $this->populate_from_data( $db_data );
            return true;
        }
        return false;
    }

    protected function populate_from_data( $data ) {
        $this->data = $data;
        $this->expense_type_id = isset( $data->expense_type_id ) ? intval( $data->expense_type_id ) : null;
        $this->type_name = isset( $data->type_name ) ? $data->type_name : null;
        $this->default_unit = isset( $data->default_unit ) ? $data->default_unit : null;
        $this->is_active = isset( $data->is_active ) ? (bool) $data->is_active : true;
        $this->created_at = isset( $data->created_at ) ? $data->created_at : null;
        $this->updated_at = isset( $data->updated_at ) ? $data->updated_at : null;
    }

    // Getters
    public function get_id() { return $this->expense_type_id; }
    public function get_type_name() { return $this->type_name; }
    public function get_default_unit() { return $this->default_unit; }
    public function is_active() { return (bool) $this->is_active; }
    public function get_created_at() { return $this->created_at; }
    public function get_updated_at() { return $this->updated_at; }

    // Setters
    public function set_type_name( $name ) { $this->type_name = sanitize_text_field( $name ); }
    public function set_default_unit( $unit ) { $this->default_unit = $unit ? sanitize_text_field( $unit ) : null; }
    public function set_active( $is_active ) { $this->is_active = (bool) $is_active; }
    
    public function exists() {
        return !empty($this->expense_type_id) && !empty($this->created_at);
    }

    public function save() {
        $data_args = array(
            'type_name' => $this->type_name,
            'default_unit' => $this->default_unit,
            'is_active' => $this->is_active ? 1 : 0,
        );

        if ( empty( $this->type_name ) ) {
            return new WP_Error('missing_type_name', 'Expense Type Name is required.');
        }

        if ( $this->exists() ) {
            $result = OO_DB::update_expense_type( $this->expense_type_id, $data_args );
            if ( is_wp_error( $result ) ) {
                return $result;
            }
            $this->load(); 
            return $this->expense_type_id;
        } else {
            $new_id = OO_DB::add_expense_type( $data_args );
            if ( is_wp_error( $new_id ) ) {
                return $new_id;
            }
            $this->expense_type_id = $new_id;
            $this->load(); 
            return $this->expense_type_id;
        }
    }

    public function delete() {
        if ( ! $this->exists() ) {
            return new WP_Error( 'expense_type_not_exists', 'Cannot delete an expense type that does not exist.' );
        }
        // OO_DB::delete_expense_type already checks for usage due to ON DELETE RESTRICT logic implemented there.
        $result = OO_DB::delete_expense_type( $this->expense_type_id );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        if ($result === false ) { 
            oo_log('Failed to delete expense type (ID: ' . $this->expense_type_id . ') from database.', __METHOD__);
            return new WP_Error('db_delete_failed', 'Could not delete expense type from the database.');
        }
        $former_id = $this->expense_type_id;
        foreach (get_object_vars($this) as $key => $value) {
            $this->$key = null;
        }
        oo_log('Expense Type deleted successfully (Former ID: ' . $former_id . ')', __METHOD__);
        return true;
    }

    public function toggle_status( $is_active ) {
        if ( ! $this->exists() ) {
            return new WP_Error( 'expense_type_not_exists', 'Expense type must exist to toggle status.' );
        }
        $result = OO_DB::toggle_expense_type_status( $this->expense_type_id, $is_active );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        $this->is_active = (bool) $is_active;
        $this->load(); // Refresh updated_at
        return true;
    }

    public static function get_by_id( $expense_type_id ) {
        $instance = new self( $expense_type_id );
        return $instance->exists() ? $instance : null;
    }

    public static function get_by_name( $type_name ) {
        $data = OO_DB::get_expense_type_by_name( $type_name );
        if ( $data ) {
            return new self( $data );
        }
        return null;
    }

    public static function get_expense_types( $args = array() ) {
        $datas = OO_DB::get_expense_types( $args );
        $instances = array();
        foreach ( $datas as $data ) {
            $instances[] = new self( $data );
        }
        return $instances;
    }

    public static function get_expense_types_count( $args = array() ) {
        return OO_DB::get_expense_types_count( $args );
    }
} 