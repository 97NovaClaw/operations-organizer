<?php
// /includes/class-oo-building.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Building {

    public $building_id;
    public $building_name;
    public $address;
    public $storage_capacity_notes;
    public $primary_contact_id;
    public $is_active;

    public function __construct( $building_id = null ) {
        if ( $building_id ) {
            $this->load_building( $building_id );
        }
    }

    private function load_building( $building_id ) {
        // TODO: Implement method to load building details from wp_oo_buildings
        // $data = OO_DB::get_building( $building_id ); (method to be created in OO_DB)
        // if ($data) { $this->populate_properties($data); }
    }

    private function populate_properties( $data ) {
        $this->building_id = $data->building_id;
        $this->building_name = $data->building_name;
        $this->address = $data->address;
        $this->storage_capacity_notes = $data->storage_capacity_notes;
        $this->primary_contact_id = $data->primary_contact_id;
        $this->is_active = $data->is_active;
    }
    
    public function is_valid() {
        return !empty($this->building_id);
    }

    // --- Static CRUD-like Methods ---

    public static function create( $args ) {
        // TODO: Call OO_DB::add_building($args)
        // $args contains building_name, address, etc.
        // Returns new building_id or WP_Error
        return new WP_Error('not_implemented', 'Create building method not implemented yet.');
    }

    public static function get( $building_id ) {
        // TODO: Call OO_DB::get_building($building_id)
        // Returns OO_Building object or null
        return null;
    }

    public static function update( $building_id, $args ) {
        // TODO: Call OO_DB::update_building($building_id, $args)
        // Returns true or WP_Error
        return new WP_Error('not_implemented', 'Update building method not implemented yet.');
    }

    public static function delete( $building_id ) {
        // TODO: Call OO_DB::delete_building($building_id)
        // Returns true or WP_Error
        return new WP_Error('not_implemented', 'Delete building method not implemented yet.');
    }

    public static function get_all( $params = array() ) {
        // TODO: Call OO_DB::get_buildings($params)
        // Returns array of OO_Building objects or empty array
        return array();
    }
    
    public static function get_active_buildings_for_select() {
        // TODO: Fetch active buildings from OO_DB::get_buildings()
        // Format as ID => Name array for dropdowns
        return array(); // Placeholder
    }

    // --- Instance Methods ---

    public function get_name() {
        return $this->building_name;
    }

    // Other building-specific methods.

} 