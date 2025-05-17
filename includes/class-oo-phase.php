<?php
// /includes/class-oo-phase.php // Renamed file

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class OO_Phase
 * 
 * Represents a phase within a stream type in the Operations Organizer system.
 */
class OO_Phase {

    public $phase_id;
    public $stream_id;
    public $phase_name;
    public $phase_description;
    public $order_in_stream;
    public $phase_type;
    public $default_kpi_units;
    public $is_active;
    public $created_at;
    public $updated_at;

    protected $data;
    protected $_stream_type = null;

    /**
     * Constructor.
     *
     * @param int|object|array|null $data Phase ID, or an object/array of phase data.
     */
    public function __construct( $data = null ) {
        if ( is_numeric( $data ) ) {
            $this->phase_id = intval( $data );
            $this->load();
        } elseif ( is_object( $data ) || is_array( $data ) ) {
            $this->populate_from_data( (object) $data );
        }
    }

    protected function load() {
        if ( ! $this->phase_id ) {
            return false;
        }
        $db_data = OO_DB::get_phase( $this->phase_id );
        if ( $db_data ) {
            $this->populate_from_data( $db_data );
            return true;
        }
        return false;
    }

    protected function populate_from_data( $data ) {
        $this->data = $data;
        $this->phase_id = isset( $data->phase_id ) ? intval( $data->phase_id ) : null;
        $this->stream_id = isset( $data->stream_id ) ? intval( $data->stream_id ) : null;
        $this->phase_name = isset( $data->phase_name ) ? $data->phase_name : null;
        $this->phase_description = isset( $data->phase_description ) ? $data->phase_description : null;
        $this->order_in_stream = isset( $data->order_in_stream ) ? intval( $data->order_in_stream ) : 0;
        $this->phase_type = isset( $data->phase_type ) ? $data->phase_type : null;
        $this->default_kpi_units = isset( $data->default_kpi_units ) ? $data->default_kpi_units : null;
        $this->is_active = isset( $data->is_active ) ? (bool) $data->is_active : true;
        $this->created_at = isset( $data->created_at ) ? $data->created_at : null;
        $this->updated_at = isset( $data->updated_at ) ? $data->updated_at : null;
    }

    // Getters
    public function get_id() { return $this->phase_id; }
    public function get_stream_id() { return $this->stream_id; }
    public function get_name() { return $this->phase_name; }
    public function get_description() { return $this->phase_description; }
    public function get_order_in_stream() { return $this->order_in_stream; }
    public function get_phase_type() { return $this->phase_type; }
    public function get_default_kpi_units() { return $this->default_kpi_units; }
    public function is_active() { return (bool) $this->is_active; }
    public function get_created_at() { return $this->created_at; }
    public function get_updated_at() { return $this->updated_at; }

    // Setters
    public function set_stream_id( $id ) { $this->stream_id = intval( $id ); }
    public function set_name( $name ) { $this->phase_name = sanitize_text_field( $name ); }
    public function set_description( $desc ) { $this->phase_description = $desc ? sanitize_textarea_field( $desc ) : null; }
    public function set_order_in_stream( $order ) { $this->order_in_stream = intval( $order ); }
    public function set_phase_type( $type ) { $this->phase_type = $type ? sanitize_text_field( $type ) : null; }
    public function set_default_kpi_units( $units ) { $this->default_kpi_units = $units ? sanitize_text_field( $units ) : null; }
    public function set_active( $is_active ) { $this->is_active = (bool) $is_active; }

    public function exists() {
        return !empty($this->phase_id) && !empty($this->created_at);
    }

    public function save() {
        if ( empty( $this->stream_id ) || empty( $this->phase_name ) ) {
            return new WP_Error('missing_fields', 'Stream ID and Phase Name are required.');
        }

        if ( $this->exists() ) {
            $result = OO_DB::update_phase(
                $this->phase_id,
                $this->stream_id,
                $this->phase_name,
                $this->phase_description,
                $this->order_in_stream,
                $this->phase_type,
                $this->default_kpi_units,
                $this->is_active ? 1 : 0
            );
            if ( is_wp_error( $result ) ) {
                return $result;
            }
            $this->load(); 
            return $this->phase_id;
        } else {
            $new_id = OO_DB::add_phase(
                $this->stream_id,
                $this->phase_name,
                $this->phase_description,
                $this->order_in_stream,
                $this->phase_type,
                $this->default_kpi_units,
                $this->is_active ? 1 : 0
            );
            if ( is_wp_error( $new_id ) ) {
                return $new_id;
            }
            $this->phase_id = $new_id;
            $this->load(); 
            return $this->phase_id;
        }
    }

    public function delete() {
        if ( ! $this->exists() ) {
            return new WP_Error( 'phase_not_exists', 'Cannot delete a phase that does not exist.' );
        }
        // Check for usage in job_logs (ON DELETE RESTRICT)
        $usage_count = OO_DB::get_job_logs_count(['phase_id' => $this->phase_id]);
        if ( $usage_count > 0) {
            return new WP_Error('phase_in_use', 'This phase cannot be deleted as it is used in job logs.');
        }

        $result = OO_DB::delete_phase( $this->phase_id ); // Requires OO_DB::delete_phase()
        if ( is_wp_error( $result ) ) {
            return $result;
        }
         if ($result === false ) { 
            return new WP_Error('db_delete_failed', 'Could not delete phase from the database.');
        }
        $former_id = $this->phase_id;
        foreach (get_object_vars($this) as $key => $value) {
            $this->$key = null;
        }
        oo_log('Phase deleted successfully (Former ID: ' . $former_id . ')', __METHOD__);
        return true;
    }

    public function toggle_status( $is_active = null ) {
        if ( ! $this->exists() ) {
            return new WP_Error( 'phase_not_exists', 'Phase must exist to toggle status.' );
        }
        $new_status = is_null($is_active) ? !$this->is_active : (bool) $is_active;
        $result = OO_DB::toggle_phase_status( $this->phase_id, $new_status );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        $this->is_active = $new_status;
        $this->load(); // refresh updated_at
        return true;
    }

    public static function get_by_id( $phase_id ) {
        $instance = new self( $phase_id );
        return $instance->exists() ? $instance : null;
    }

    public function get_stream() {
        if ($this->_stream_type === null && $this->stream_id && class_exists('OO_Stream')) {
            $this->_stream_type = OO_Stream::get_by_id($this->stream_id);
        }
        return $this->_stream_type;
    }

    public static function get_phases( $args = array() ) {
        $datas = OO_DB::get_phases( $args );
        $instances = array();
        if (is_array($datas)){
            foreach ( $datas as $data ) {
                $instances[] = new self( $data );
            }
        }
        return $instances;
    }

    public static function get_phases_count( $args = array() ) {
        return OO_DB::get_phases_count( $args );
    }

    public static function display_phase_management_page() {
        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        global $phases, $total_phases, $current_page, $per_page, $search_term, $active_filter, $streams, $selected_stream_id;

        // Get all streams for a filter dropdown
        $streams = OO_DB::get_streams(array('is_active' => 1, 'orderby' => 'stream_name', 'order' => 'ASC')); // Renamed
        $selected_stream_id = isset($_REQUEST['stream_filter']) ? intval($_REQUEST['stream_filter']) : null; // Renamed

        $search_term = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $active_filter = isset($_REQUEST['status_filter']) ? sanitize_text_field($_REQUEST['status_filter']) : 'all';
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;
        
        $GLOBALS['orderby'] = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'phase_name';
        $GLOBALS['order'] = isset($_GET['order']) ? strtoupper(sanitize_key($_GET['order'])) : 'ASC';
        if(!in_array(strtolower($GLOBALS['order']), ['asc', 'desc'])) {
            $GLOBALS['order'] = 'ASC';
        }

        $phase_args = array(
            'number' => $per_page,
            'offset' => $offset,
            'search' => $search_term,
            'orderby' => $GLOBALS['orderby'], // Should be order_in_stream or phase_name
            'order' => $GLOBALS['order'],
        );
        if ($selected_stream_id) {
            $phase_args['stream_id'] = $selected_stream_id; // Renamed
        }
        if ($active_filter === 'active') {
            $phase_args['is_active'] = 1;
        } elseif ($active_filter === 'inactive') {
            $phase_args['is_active'] = 0;
        }

        $GLOBALS['phases'] = OO_DB::get_phases( $phase_args );
        $GLOBALS['total_phases'] = OO_DB::get_phases_count($phase_args); 
        
        $GLOBALS['current_page'] = $current_page;
        $GLOBALS['per_page'] = $per_page;
        $GLOBALS['search_term'] = $search_term;
        $GLOBALS['active_filter'] = $active_filter;
        $GLOBALS['streams'] = $streams; // Renamed
        $GLOBALS['selected_stream_id'] = $selected_stream_id; // Renamed

        include_once OO_PLUGIN_DIR . 'admin/views/phase-management-page.php';
    }

    public static function ajax_add_phase() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_add_phase_nonce', 'oo_add_phase_nonce'); 

        if ( ! current_user_can( oo_get_capability() ) ) {
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }

        $stream_id = isset( $_POST['stream_id'] ) ? intval( $_POST['stream_id'] ) : 0; // Renamed
        $phase_name = isset( $_POST['phase_name'] ) ? sanitize_text_field( trim($_POST['phase_name']) ) : '';
        $phase_description = isset( $_POST['phase_description'] ) ? sanitize_textarea_field( trim($_POST['phase_description']) ) : '';
        $order_in_stream = isset( $_POST['order_in_stream'] ) ? intval( $_POST['order_in_stream'] ) : 0; // Renamed from sort_order
        $phase_type = isset( $_POST['phase_type'] ) ? sanitize_text_field( trim($_POST['phase_type']) ) : null;
        $default_kpi_units = isset( $_POST['default_kpi_units'] ) ? sanitize_text_field( trim($_POST['default_kpi_units']) ) : null;

        if ( empty($stream_id) || empty($phase_name) ) { // Phase slug removed from check
            oo_log('AJAX Error: Stream and Phase Name are required.', $_POST);
            wp_send_json_error( array( 'message' => 'Error: Stream and Phase Name are required.' ) ); return;
        }

        // Call to OO_DB::add_phase updated with new parameters
        $result = OO_DB::add_phase( $stream_id, $phase_name, $phase_description, $order_in_stream, $phase_type, $default_kpi_units );

        if ( is_wp_error( $result ) ) {
            oo_log('AJAX Error adding phase: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            oo_log('AJAX Success: Phase added. ID: ' . $result, __METHOD__);
            wp_send_json_success( array( 'message' => 'Phase added successfully.', 'phase_id' => $result ) );
        }
    }

    public static function ajax_get_phase() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_edit_phase_nonce', '_ajax_nonce_get_phase'); 
        if ( ! current_user_can( oo_get_capability() ) ) {
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }
        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;
        if ( $phase_id <= 0 ) {
            oo_log('AJAX Error: Invalid phase ID: ' . $phase_id, __METHOD__);
            wp_send_json_error( array( 'message' => 'Invalid phase ID.' ) ); return;
        }
        $phase = OO_DB::get_phase( $phase_id ); // OO_DB::get_phase will need to return new fields too
        if ( $phase ) {
            oo_log('AJAX Success: Phase found.', $phase);
            wp_send_json_success( $phase );
        } else {
            oo_log('AJAX Error: Phase not found for ID: ' . $phase_id, __METHOD__);
            wp_send_json_error( array( 'message' => 'Phase not found.' ) );
        }
    }

    public static function ajax_update_phase() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_edit_phase_nonce', 'oo_edit_phase_nonce');
        if ( ! current_user_can( oo_get_capability() ) ) { 
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }
        $phase_id = isset( $_POST['edit_phase_id'] ) ? intval( $_POST['edit_phase_id'] ) : 0;
        $stream_id = isset( $_POST['edit_stream_id'] ) ? intval( $_POST['edit_stream_id'] ) : 0; // Renamed
        $phase_name = isset( $_POST['edit_phase_name'] ) ? sanitize_text_field( trim($_POST['edit_phase_name']) ) : '';
        $phase_description = isset( $_POST['edit_phase_description'] ) ? sanitize_textarea_field( trim($_POST['edit_phase_description']) ) : '';
        $order_in_stream = isset( $_POST['edit_order_in_stream'] ) ? intval( $_POST['edit_order_in_stream'] ) : null; // Renamed
        $phase_type = isset( $_POST['edit_phase_type'] ) ? sanitize_text_field( trim($_POST['edit_phase_type']) ) : null;
        $default_kpi_units = isset( $_POST['edit_default_kpi_units'] ) ? sanitize_text_field( trim($_POST['edit_default_kpi_units']) ) : null;
        $is_active = isset( $_POST['edit_is_active'] ) ? intval( $_POST['edit_is_active'] ) : null;

        if ( $phase_id <= 0 || empty($stream_id) || empty($phase_name) ) { // Phase slug removed
            oo_log('AJAX Error: Phase ID, Stream and Name are required.', $_POST);
            wp_send_json_error( array( 'message' => 'Error: Phase ID, Stream and Name are required.' ) ); return;
        }
        // Call to OO_DB::update_phase updated with new parameters
        $result = OO_DB::update_phase( $phase_id, $stream_id, $phase_name, $phase_description, $order_in_stream, $phase_type, $default_kpi_units, $is_active );
        if ( is_wp_error( $result ) ) {
            oo_log('AJAX Error updating phase: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            oo_log('AJAX Success: Phase updated. ID: ' . $phase_id, __METHOD__);
            wp_send_json_success( array( 'message' => 'Phase updated successfully.' ) );
        }
    }

    public static function ajax_toggle_phase_status() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_toggle_status_nonce', '_ajax_nonce');
        if ( ! current_user_can( oo_get_capability() ) ) { 
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }
        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;
        $new_status = isset( $_POST['is_active'] ) ? intval( $_POST['is_active'] ) : 0;
        if ( $phase_id <= 0 ) {
            oo_log('AJAX Error: Invalid phase ID: ' . $phase_id, __METHOD__);
            wp_send_json_error( array( 'message' => 'Invalid phase ID.' ) ); return;
        }
        $result = OO_DB::toggle_phase_status( $phase_id, $new_status );
        if ( is_wp_error( $result ) ) {
            oo_log('AJAX Error toggling phase status: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            $message = $new_status ? 'Phase activated.' : 'Phase deactivated.';
            oo_log('AJAX Success: ' . $message . ' ID: ' . $phase_id, __METHOD__);
            wp_send_json_success( array( 'message' => $message, 'new_status' => $new_status ) );
        }
    }
} 