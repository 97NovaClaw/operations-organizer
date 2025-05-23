<?php
// /includes/class-oo-stream.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class OO_Stream
 * 
 * Represents a stream type/definition in the Operations Organizer system.
 */
class OO_Stream {

    /**
     * @var int The Stream ID.
     */
    public $stream_id;

    /**
     * @var string Stream Name.
     */
    public $stream_name;

    /**
     * @var string|null Stream Description.
     */
    public $stream_description;

    /**
     * @var bool Whether the stream type is active.
     */
    public $is_active;

    /**
     * @var string Creation timestamp (YYYY-MM-DD HH:MM:SS).
     */
    public $created_at;

    /**
     * @var string Last updated timestamp (YYYY-MM-DD HH:MM:SS).
     */
    public $updated_at;

    /**
     * @var object Raw data from the database.
     */
    protected $data;

    /**
     * Constructor.
     *
     * @param int|object|array|null $stream_data Stream ID, or an object/array of stream data.
     */
    public function __construct( $stream_data = null ) {
        if ( is_numeric( $stream_data ) ) {
            $this->stream_id = intval( $stream_data );
            $this->load();
        } elseif ( is_object( $stream_data ) || is_array( $stream_data ) ) {
            $this->populate_from_data( (object) $stream_data );
        }
    }

    /**
     * Load stream data from the database.
     */
    protected function load() {
        if ( ! $this->stream_id ) {
            return false;
        }
        $data = OO_DB::get_stream( $this->stream_id );
        if ( $data ) {
            $this->populate_from_data( $data );
            return true;
        }
        return false;
    }

    /**
     * Populate object properties from a data object.
     *
     * @param object $data Data object (typically from database).
     */
    protected function populate_from_data( $data ) {
        $this->data = $data;
        $this->stream_id = isset( $data->stream_id ) ? intval( $data->stream_id ) : null;
        $this->stream_name = isset( $data->stream_name ) ? $data->stream_name : null;
        $this->stream_description = isset( $data->stream_description ) ? $data->stream_description : null;
        $this->is_active = isset( $data->is_active ) ? (bool) $data->is_active : true;
        $this->created_at = isset( $data->created_at ) ? $data->created_at : null;
        $this->updated_at = isset( $data->updated_at ) ? $data->updated_at : null;
    }

    /**
     * Get Stream ID.
     * @return int|null
     */
    public function get_id() {
        return $this->stream_id;
    }

    /**
     * Get Stream Name.
     * @return string|null
     */
    public function get_name() {
        return $this->stream_name;
    }

    /**
     * Set Stream Name.
     * @param string $stream_name
     */
    public function set_name( $stream_name ) {
        $this->stream_name = sanitize_text_field( $stream_name );
    }

    /**
     * Get Stream Description.
     * @return string|null
     */
    public function get_description() {
        return $this->stream_description;
    }

    /**
     * Set Stream Description.
     * @param string|null $stream_description
     */
    public function set_description( $stream_description ) {
        $this->stream_description = $stream_description ? sanitize_textarea_field( $stream_description ) : null;
    }

    /**
     * Check if stream is active.
     * @return bool
     */
    public function is_active() {
        return (bool) $this->is_active;
    }

    /**
     * Set active status.
     * @param bool $is_active
     */
    public function set_active( $is_active ) {
        $this->is_active = (bool) $is_active;
    }

    /**
     * Get Created At timestamp.
     * @return string|null
     */
    public function get_created_at() {
        return $this->created_at;
    }

    /**
     * Get Updated At timestamp.
     * @return string|null
     */
    public function get_updated_at() {
        return $this->updated_at;
    }
    
    /**
     * Check if the stream data exists and is loaded.
     * @return bool
     */
    public function exists() {
        return !empty($this->stream_id) && !empty($this->created_at);
    }

    /**
     * Save the stream data to the database.
     * Creates a new stream or updates an existing one.
     *
     * @return int|WP_Error Stream ID on success, WP_Error on failure.
     */
    public function save() {
        $data = array(
            'stream_name' => $this->stream_name,
            'stream_description' => $this->stream_description,
            'is_active' => $this->is_active ? 1 : 0,
        );

        if ( empty( $this->stream_name ) ) {
            return new WP_Error('missing_stream_name', 'Stream Name is required.');
        }

        if ( $this->exists() ) {
            // Update existing stream
            $result = OO_DB::update_stream( $this->stream_id, $this->stream_name, $this->stream_description, $this->is_active );
            if ( is_wp_error( $result ) ) {
                oo_log('Error updating stream (ID: ' . $this->stream_id . '): ' . $result->get_error_message(), __METHOD__);
                return $result;
            }
            $this->load(); // Reload data
            oo_log('Stream updated successfully (ID: ' . $this->stream_id . ')', __METHOD__);
            return $this->stream_id;
        } else {
            // Create new stream
            $new_stream_id = OO_DB::add_stream( $this->stream_name, $this->stream_description, $this->is_active );
            if ( is_wp_error( $new_stream_id ) ) {
                oo_log('Error adding new stream: ' . $new_stream_id->get_error_message(), __METHOD__);
                return $new_stream_id;
            }
            $this->stream_id = $new_stream_id;
            $this->load(); // Load the newly created stream data
            oo_log('New stream added successfully (ID: ' . $this->stream_id . ')', __METHOD__);
            return $this->stream_id;
        }
    }

    /**
     * Delete the stream from the database.
     * Note: Foreign key constraints on oo_phases (CASCADE) and oo_job_streams (RESTRICT) apply.
     *
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function delete() {
        if ( ! $this->exists() ) {
            return new WP_Error( 'stream_not_exists', 'Cannot delete a stream that does not exist or has not been saved.' );
        }

        // Check if any job_streams use this stream type (due to ON DELETE RESTRICT)
        $job_streams_using_this = OO_DB::get_job_streams_count( ['stream_id' => $this->stream_id] );
        if ( $job_streams_using_this > 0 ) {
            oo_log('Attempt to delete stream ID ' . $this->stream_id . ' failed. It is used by ' . $job_streams_using_this . ' job stream(s).', __METHOD__);
            return new WP_Error('stream_in_use_job_streams', 'This stream type cannot be deleted because it is currently assigned to active job streams.');
        }
        
        // Phases will be deleted by ON DELETE CASCADE if the DB command succeeds.

        $result = OO_DB::delete_stream( $this->stream_id ); // This method needs to be created in OO_DB
        if ( is_wp_error( $result ) ) {
            oo_log('Error deleting stream (ID: ' . $this->stream_id . '): ' . $result->get_error_message(), __METHOD__);
            return $result;
        }
        if ( $result === false ) { // delete_stream in OO_DB might return false on wpdb error
             oo_log('Failed to delete stream (ID: ' . $this->stream_id . ') from database - OO_DB::delete_stream returned false.', __METHOD__);
             return new WP_Error('db_delete_failed', 'Could not delete stream from the database.');
        }

        $former_id = $this->stream_id;
        // Clear object properties
        foreach (get_object_vars($this) as $key => $value) {
            $this->$key = null;
        }
        oo_log('Stream deleted successfully (Former ID: ' . $former_id . ')', __METHOD__);
        return true;
    }

    /**
     * Get an OO_Stream instance by its ID.
     *
     * @param int $stream_id The Stream ID.
     * @return OO_Stream|null OO_Stream object if found, null otherwise.
     */
    public static function get_by_id( $stream_id ) {
        $stream = new self( $stream_id );
        return $stream->exists() ? $stream : null;
    }

    /**
     * Get an OO_Stream instance by its name.
     *
     * @param string $stream_name The Stream Name.
     * @return OO_Stream|null OO_Stream object if found, null otherwise.
     */
    public static function get_by_name( $stream_name ) {
        $data = OO_DB::get_stream_by_name( $stream_name );
        if ( $data ) {
            return new self( $data );
        }
        return null;
    }

    /**
     * Get associated phases for this stream type.
     *
     * @param array $args Arguments to filter phases (e.g., ['is_active' => 1]).
     * @return OO_Phase[] An array of OO_Phase objects.
     */
    public function get_phases( $args = array() ) {
        if ( ! $this->exists() ) {
            return array();
        }
        $phase_args = array_merge( $args, ['stream_id' => $this->stream_id] );
        $phase_data_list = OO_DB::get_phases( $phase_args );
        $phases = array();
        if ( !empty($phase_data_list) && is_array($phase_data_list) ) {
            foreach ( $phase_data_list as $phase_data ) {
                if (class_exists('OO_Phase')) {
                    $phases[] = new OO_Phase( $phase_data );
                } else {
                    oo_log('OO_Phase class not found while trying to load phases for stream ID: ' . $this->stream_id, __METHOD__);
                }
            }
        }
        return $phases;
    }

    /**
     * Get all stream types matching criteria.
     *
     * @param array $args Arguments for filtering, sorting, pagination (passed to OO_DB::get_streams).
     * @return OO_Stream[] Array of OO_Stream objects.
     */
    public static function get_streams( $args = array() ) {
        $stream_datas = OO_DB::get_streams( $args );
        $streams = array();
        foreach ( $stream_datas as $stream_data ) {
            $streams[] = new self( $stream_data );
        }
        return $streams;
    }

    /**
     * Get the count of stream types matching criteria.
     *
     * @param array $args Arguments for filtering (passed to OO_DB::get_streams_count).
     * @return int Count of stream types.
     */
    public static function get_streams_count( $args = array() ) {
        return OO_DB::get_streams_count( $args );
    }

    /**
     * Display the Stream management page.
     */
    public static function display_stream_management_page() {
        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'operations-organizer' ) );
        }

        // Fetch streams for listing
        // global $streams, $total_streams, ... etc. for view
        $GLOBALS['streams'] = OO_DB::get_streams(array('number' => 20, 'offset' => 0 /* add pagination args */));
        // $GLOBALS['total_streams'] = OO_DB::get_streams_count();

        // TODO: Create admin/views/stream-management-page.php
        // include_once OO_PLUGIN_DIR . 'admin/views/stream-management-page.php';
        echo "<div class=\"wrap\"><h1>Stream Management (Placeholder)</h1><p>Functionality to list, add, edit streams and their configurations will be here.</p></div>";
    }

    /**
     * Handle AJAX request to add a stream.
     */
    public static function ajax_add_stream() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_add_stream_nonce', 'oo_add_stream_nonce'); 

        if ( ! current_user_can( oo_get_capability() ) ) {
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }

        $name = isset($_POST['stream_name']) ? sanitize_text_field($_POST['stream_name']) : '';
        $description = isset($_POST['stream_description']) ? sanitize_textarea_field($_POST['stream_description']) : '';

        if (empty($name)) {
            oo_log('AJAX Error: Stream Name is required.', $_POST);
            wp_send_json_error( array( 'message' => 'Error: Stream Name is required.' ) ); return;
        }

        $result = OO_DB::add_stream($name, $description);

        if (is_wp_error($result)) {
            oo_log('AJAX Error adding stream: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            oo_log('AJAX Success: Stream added. ID: ' . $result, __METHOD__);
            wp_send_json_success( array( 'message' => 'Stream added successfully.', 'stream_id' => $result ) );
        }
    }
    
    /**
     * Handle AJAX request to get a stream's details.
     */
    public static function ajax_get_stream() {
        oo_log('AJAX call received.', __METHOD__);
        check_ajax_referer('oo_edit_stream_nonce', '_ajax_nonce_get_stream');

        if (!current_user_can(oo_get_capability())) {
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        $stream_id = isset($_POST['stream_id']) ? intval($_POST['stream_id']) : 0;
        if ($stream_id <= 0) {
            oo_log('AJAX Error: Invalid Stream ID.', __METHOD__);
            wp_send_json_error(['message' => 'Invalid Stream ID.']);
            return;
        }

        $stream = OO_DB::get_stream($stream_id);
        if (!$stream) {
            oo_log('AJAX Error: Stream not found.', __METHOD__);
            wp_send_json_error(['message' => 'Stream not found.']);
            return;
        }

        oo_log('AJAX Success: Stream data found and returned.', __METHOD__);
        wp_send_json_success($stream);
    }

    /**
     * Handle AJAX request to update a stream.
     */
    public static function ajax_update_stream() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_edit_stream_nonce', 'oo_edit_stream_nonce');

        if (!current_user_can(oo_get_capability())) {
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        $stream_id = isset($_POST['edit_stream_id']) ? intval($_POST['edit_stream_id']) : 0;
        $stream_name = isset($_POST['edit_stream_name']) ? sanitize_text_field($_POST['edit_stream_name']) : '';
        $stream_description = isset($_POST['edit_stream_description']) ? sanitize_textarea_field($_POST['edit_stream_description']) : '';

        if (empty($stream_id) || empty($stream_name)) {
            oo_log('AJAX Error: Missing required field(s).', __METHOD__);
            wp_send_json_error(['message' => 'Stream ID and Stream Name are required.']);
            return;
        }

        $result = OO_DB::update_stream($stream_id, $stream_name, $stream_description);
        if (is_wp_error($result)) {
            oo_log('AJAX Error updating stream: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(['message' => 'Error: ' . $result->get_error_message()]);
        } else {
            oo_log('AJAX Success: Stream updated. ID: ' . $stream_id, __METHOD__);
            wp_send_json_success(['message' => 'Stream updated successfully.']);
        }
    }

    /**
     * Handle AJAX request to toggle a stream's active status.
     */
    public static function ajax_toggle_stream_status() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_toggle_status_nonce', '_ajax_nonce');

        if (!current_user_can(oo_get_capability())) {
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        $stream_id = isset($_POST['stream_id']) ? intval($_POST['stream_id']) : 0;
        $is_active = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : null;

        if (empty($stream_id) || is_null($is_active)) {
            oo_log('AJAX Error: Missing required field(s).', __METHOD__);
            wp_send_json_error(['message' => 'Stream ID and active status are required.']);
            return;
        }

        $stream = self::get_by_id($stream_id);
        if (!$stream) {
            oo_log('AJAX Error: Stream not found.', __METHOD__);
            wp_send_json_error(['message' => 'Stream not found.']);
            return;
        }

        $stream->set_active($is_active);
        $result = $stream->save();

        if (is_wp_error($result)) {
            oo_log('AJAX Error toggling stream status: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(['message' => 'Error: ' . $result->get_error_message()]);
        } else {
            $status_text = $is_active ? 'activated' : 'deactivated';
            oo_log('AJAX Success: Stream ' . $status_text . '. ID: ' . $stream_id, __METHOD__);
            wp_send_json_success(['message' => 'Stream ' . $status_text . ' successfully.']);
        }
    }
} 