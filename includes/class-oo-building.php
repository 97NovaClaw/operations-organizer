<?php
// /includes/class-oo-building.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class OO_Building
 * 
 * Represents a storage building in the Operations Organizer system.
 */
class OO_Building {

    public $building_id;
    public $building_name;
    public $address;
    public $storage_capacity_notes;
    public $primary_contact_id; // Could be an employee ID or WP User ID - handle accordingly if needed
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
     * @param int|object|array|null $data Building ID, or an object/array of building data.
     */
    public function __construct( $data = null ) {
        if ( is_numeric( $data ) ) {
            $this->building_id = intval( $data );
            $this->load();
        } elseif ( is_object( $data ) || is_array( $data ) ) {
            $this->populate_from_data( (object) $data );
        }
    }

    protected function load() {
        if ( ! $this->building_id ) {
            return false;
        }
        $db_data = OO_DB::get_building( $this->building_id );
        if ( $db_data ) {
            $this->populate_from_data( $db_data );
            return true;
        }
        return false;
    }

    protected function populate_from_data( $data ) {
        $this->data = $data;
        $this->building_id = isset( $data->building_id ) ? intval( $data->building_id ) : null;
        $this->building_name = isset( $data->building_name ) ? $data->building_name : null;
        $this->address = isset( $data->address ) ? $data->address : null;
        $this->storage_capacity_notes = isset( $data->storage_capacity_notes ) ? $data->storage_capacity_notes : null;
        $this->primary_contact_id = isset( $data->primary_contact_id ) ? intval( $data->primary_contact_id ) : null;
        $this->is_active = isset( $data->is_active ) ? (bool) $data->is_active : true;
        $this->created_at = isset( $data->created_at ) ? $data->created_at : null;
        $this->updated_at = isset( $data->updated_at ) ? $data->updated_at : null;
    }

    // Getters
    public function get_id() { return $this->building_id; }
    public function get_name() { return $this->building_name; }
    public function get_address() { return $this->address; }
    public function get_storage_capacity_notes() { return $this->storage_capacity_notes; }
    public function get_primary_contact_id() { return $this->primary_contact_id; }
    public function is_active() { return (bool) $this->is_active; }
    public function get_created_at() { return $this->created_at; }
    public function get_updated_at() { return $this->updated_at; }

    // Setters
    public function set_name( $name ) { $this->building_name = sanitize_text_field( $name ); }
    public function set_address( $address ) { $this->address = $address ? sanitize_textarea_field( $address ) : null; }
    public function set_storage_capacity_notes( $notes ) { $this->storage_capacity_notes = $notes ? sanitize_textarea_field( $notes ) : null; }
    public function set_primary_contact_id( $contact_id ) { $this->primary_contact_id = $contact_id ? intval( $contact_id ) : null; }
    public function set_active( $is_active ) { $this->is_active = (bool) $is_active; }

    public function exists() {
        return !empty($this->building_id) && !empty($this->created_at);
    }

    public function save() {
        $data = array(
            'building_name' => $this->building_name,
            'address' => $this->address,
            'storage_capacity_notes' => $this->storage_capacity_notes,
            'primary_contact_id' => $this->primary_contact_id,
            'is_active' => $this->is_active ? 1 : 0,
        );

        if ( empty( $this->building_name ) ) {
            return new WP_Error('missing_building_name', 'Building Name is required.');
        }

        if ( $this->exists() ) {
            $result = OO_DB::update_building( $this->building_id, $data );
            if ( is_wp_error( $result ) ) {
                oo_log('Error updating building (ID: ' . $this->building_id . '): ' . $result->get_error_message(), __METHOD__);
                return $result;
            }
            $this->load(); 
            oo_log('Building updated successfully (ID: ' . $this->building_id . ')', __METHOD__);
            return $this->building_id;
        } else {
            $new_id = OO_DB::add_building( $data );
            if ( is_wp_error( $new_id ) ) {
                oo_log('Error adding new building: ' . $new_id->get_error_message(), __METHOD__);
                return $new_id;
            }
            $this->building_id = $new_id;
            $this->load(); 
            oo_log('New building added successfully (ID: ' . $this->building_id . ')', __METHOD__);
            return $this->building_id;
        }
    }

    public function delete() {
        if ( ! $this->exists() ) {
            return new WP_Error( 'building_not_exists', 'Cannot delete a building that does not exist.' );
        }
        // Note: `wp_oo_job_streams.building_id` is ON DELETE SET NULL, so direct deletion is okay.
        $result = OO_DB::delete_building( $this->building_id );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
         if ($result === false ) { 
            oo_log('Failed to delete building (ID: ' . $this->building_id . ') from database.', __METHOD__);
            return new WP_Error('db_delete_failed', 'Could not delete building from the database.');
        }
        $former_id = $this->building_id;
        foreach (get_object_vars($this) as $key => $value) {
            $this->$key = null;
        }
        oo_log('Building deleted successfully (Former ID: ' . $former_id . ')', __METHOD__);
        return true;
    }
    
    public function toggle_status( $is_active ) {
        if ( ! $this->exists() ) {
            return new WP_Error( 'building_not_exists', 'Building must exist to toggle status.' );
        }
        $result = OO_DB::toggle_building_status( $this->building_id, $is_active );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        $this->is_active = (bool) $is_active;
        $this->load(); // to refresh updated_at
        return true;
    }

    public static function get_by_id( $building_id ) {
        $instance = new self( $building_id );
        return $instance->exists() ? $instance : null;
    }

    public static function get_by_name( $building_name ) {
        $data = OO_DB::get_building_by_name( $building_name );
        if ( $data ) {
            return new self( $data );
        }
        return null;
    }

    public static function get_buildings( $args = array() ) {
        $datas = OO_DB::get_buildings( $args );
        $instances = array();
        foreach ( $datas as $data ) {
            $instances[] = new self( $data );
        }
        return $instances;
    }

    public static function get_buildings_count( $args = array() ) {
        return OO_DB::get_buildings_count( $args );
    }
    
    // Method to get primary contact details (e.g., employee name or WP user display name)
    // public function get_primary_contact_display() { ... }
} 