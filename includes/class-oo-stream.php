<?php
// /includes/class-oo-stream.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Stream {

    // Properties related to a stream type (e.g., stream_id, stream_name)
    public $stream_id;
    public $stream_name;
    public $stream_description;
    public $is_active;
    // Potentially other properties like default phases, kpi configurations etc. can be loaded.

    public function __construct( $stream_identifier = null ) {
        if ( $stream_identifier ) {
            if ( is_numeric( $stream_identifier ) ) {
                $this->load_stream_by_id( $stream_identifier );
            } else {
                $this->load_stream_by_name( $stream_identifier );
            }
        }
    }

    private function load_stream_by_id( $stream_id ) {
        $stream_data = OO_DB::get_stream( $stream_id ); // Assumes get_stream is in OO_DB
        if ( $stream_data ) {
            $this->populate_properties( $stream_data );
        }
    }

    private function load_stream_by_name( $stream_name ) {
        $stream_data = OO_DB::get_stream_by_name( $stream_name ); // Assumes get_stream_by_name is in OO_DB
        if ( $stream_data ) {
            $this->populate_properties( $stream_data );
        }
    }

    private function populate_properties( $data ) {
        $this->stream_id = $data->stream_id;
        $this->stream_name = $data->stream_name;
        $this->stream_description = $data->stream_description;
        $this->is_active = $data->is_active;
        // Populate other properties as needed
    }

    public function is_valid() {
        return ! empty( $this->stream_id );
    }

    // --- Static CRUD-like Methods (interacting with OO_DB) ---
    // These might largely replace the direct OO_DB::add_stream calls from other classes eventually

    public static function create( $name, $description = '', $is_active = 1 ) {
        return OO_DB::add_stream( $name, $description, $is_active );
    }

    public static function get( $stream_id ) {
        $stream_data = OO_DB::get_stream( $stream_id );
        if ( $stream_data ) {
            $stream = new self();
            $stream->populate_properties( $stream_data );
            return $stream;
        }
        return null;
    }

    public static function get_by_name( $stream_name ) {
        $stream_data = OO_DB::get_stream_by_name( $stream_name );
        if ( $stream_data ) {
            $stream = new self();
            $stream->populate_properties( $stream_data );
            return $stream;
        }
        return null;
    }

    public static function update( $stream_id, $name, $description = null, $is_active = null ) {
        return OO_DB::update_stream( $stream_id, $name, $description, $is_active );
    }

    public static function toggle_status( $stream_id, $is_active ) {
        return OO_DB::toggle_stream_status( $stream_id, $is_active );
    }

    public static function get_all( $args = array() ) {
        // $args for filtering (e.g., is_active), sorting
        return OO_DB::get_streams( $args ); // Returns array of stdClass objects from DB
    }
    
    public static function get_active_streams_for_select() {
        $streams = self::get_all(array('is_active' => 1, 'orderby' => 'stream_name', 'order' => 'ASC'));
        $options = array();
        foreach ($streams as $stream) {
            $options[$stream->stream_id] = $stream->stream_name;
        }
        return $options;
    }

    // --- Instance Methods ---

    public function get_phases( $args = array() ) {
        if ( ! $this->is_valid() ) return array();
        $default_args = array(
            'stream_id' => $this->stream_id,
            'is_active' => 1, // Default to active phases
            'orderby'   => 'order_in_stream',
            'order'     => 'ASC'
        );
        $merged_args = wp_parse_args( $args, $default_args );
        // TODO: Call OO_Phase::get_all_for_stream($merged_args) or OO_DB::get_phases($merged_args)
        return OO_DB::get_phases($merged_args); // Assuming OO_DB::get_phases is updated for stream_id
    }

    // Potentially methods to manage default KPI structures for this stream type, etc.

} 