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

        // Get phases with stream names included
        global $wpdb;
        $phases_table = $wpdb->prefix . 'oo_phases';
        $streams_table = $wpdb->prefix . 'oo_streams';
        
        $where_clauses = array();
        $query_params = array();
        
        if (!empty($phase_args['stream_id'])) {
            $where_clauses[] = "p.stream_id = %d";
            $query_params[] = intval($phase_args['stream_id']);
        }
        
        if (isset($phase_args['is_active'])) {
            $where_clauses[] = "p.is_active = %d";
            $query_params[] = intval($phase_args['is_active']);
        }
        
        if (!empty($phase_args['search'])) {
            $search_term = '%' . $wpdb->esc_like($phase_args['search']) . '%';
            $where_clauses[] = "(p.phase_name LIKE %s OR p.phase_description LIKE %s)";
            $query_params[] = $search_term;
            $query_params[] = $search_term;
        }
        
        $where_sql = "";
        if (!empty($where_clauses)) {
            $where_sql = "WHERE " . implode(" AND ", $where_clauses);
        }
        
        $orderby = sanitize_sql_orderby($phase_args['orderby']) ?: 'p.phase_name';
        $order = strtoupper($phase_args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        $limit_sql = $wpdb->prepare("LIMIT %d OFFSET %d", 
            intval($phase_args['number']), 
            intval($phase_args['offset'])
        );
        
        $sql = "SELECT p.*, s.stream_name 
                FROM {$phases_table} p
                LEFT JOIN {$streams_table} s ON p.stream_id = s.stream_id
                {$where_sql}
                ORDER BY {$orderby} {$order}
                {$limit_sql}";
        
        if (!empty($query_params)) {
            $sql = $wpdb->prepare($sql, $query_params);
        }
        
        $GLOBALS['phases'] = $wpdb->get_results($sql);
        
        // Count total for pagination
        $count_sql = "SELECT COUNT(*) 
                      FROM {$phases_table} p
                      {$where_sql}";
        
        if (!empty($query_params)) {
            $count_sql = $wpdb->prepare($count_sql, $query_params);
        }
        
        $GLOBALS['total_phases'] = $wpdb->get_var($count_sql);
        
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

        $stream_id = isset( $_POST['stream_type_id'] ) ? intval( $_POST['stream_type_id'] ) : 0; // Updated field name
        $phase_name = isset( $_POST['phase_name'] ) ? sanitize_text_field( trim($_POST['phase_name']) ) : '';
        $phase_slug = isset( $_POST['phase_slug'] ) ? sanitize_title( trim($_POST['phase_slug']) ) : ''; // Read phase_slug from POST
        $phase_description = isset( $_POST['phase_description'] ) ? sanitize_textarea_field( trim($_POST['phase_description']) ) : '';
        $sort_order = isset( $_POST['sort_order'] ) ? intval( $_POST['sort_order'] ) : 0;
        $phase_type = isset( $_POST['phase_type'] ) ? sanitize_text_field( trim($_POST['phase_type']) ) : null;
        $default_kpi_units = isset( $_POST['default_kpi_units'] ) ? sanitize_text_field( trim($_POST['default_kpi_units']) ) : null;
        $includes_kpi = isset( $_POST['includes_kpi'] ) ? 1 : 0; // Parse checkbox value

        if ( empty($stream_id) || empty($phase_name) ) {
            oo_log('AJAX Error: Stream and Phase Name are required.', $_POST);
            wp_send_json_error( array( 'message' => 'Error: Stream and Phase Name are required.' ) ); return;
        }

        // Call to OO_DB::add_phase updated with new parameters, including phase_slug
        $result = OO_DB::add_phase( $stream_id, $phase_name, $phase_slug, $phase_description, $sort_order, $phase_type, $default_kpi_units, 1, $includes_kpi );

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
        global $wpdb;
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_edit_phase_nonce', 'oo_edit_phase_nonce');
        if ( ! current_user_can( oo_get_capability() ) ) { 
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }
        $phase_id = isset( $_POST['edit_phase_id'] ) ? intval( $_POST['edit_phase_id'] ) : 0;
        $stream_id = isset( $_POST['edit_stream_type_id'] ) ? intval( $_POST['edit_stream_type_id'] ) : 0; // Updated field name
        $phase_name = isset( $_POST['edit_phase_name'] ) ? sanitize_text_field( trim($_POST['edit_phase_name']) ) : '';
        $phase_slug = isset( $_POST['edit_phase_slug'] ) ? sanitize_title( trim($_POST['edit_phase_slug']) ) : ''; // Read phase_slug from POST
        $phase_description = isset( $_POST['edit_phase_description'] ) ? sanitize_textarea_field( trim($_POST['edit_phase_description']) ) : '';
        $sort_order = isset( $_POST['edit_sort_order'] ) ? intval( $_POST['edit_sort_order'] ) : null;
        $phase_type = isset( $_POST['edit_phase_type'] ) ? sanitize_text_field( trim($_POST['edit_phase_type']) ) : null;
        $default_kpi_units = isset( $_POST['edit_default_kpi_units'] ) ? sanitize_text_field( trim($_POST['edit_default_kpi_units']) ) : null;
        $is_active = isset( $_POST['edit_is_active'] ) ? intval( $_POST['edit_is_active'] ) : null;
        
        // Fix for includes_kpi checkbox - explicitly set to 0 if not present in $_POST
        $includes_kpi = isset( $_POST['edit_includes_kpi'] ) ? 1 : 0;
        
        oo_log('Processing phase update with includes_kpi value: ' . $includes_kpi, __METHOD__);
        
        // Emergency direct debugging: Check column existence and structure 
        $phases_table = $wpdb->prefix . 'oo_phases';
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $phases_table");
        $column_info = array_map(function($col) { return ['Field' => $col->Field, 'Type' => $col->Type, 'Null' => $col->Null]; }, $columns);
        oo_log('Phase table structure: ', $column_info);
        
        // Try to verify if includes_kpi column physically exists
        $includes_kpi_column = array_filter($columns, function($col) { return $col->Field === 'includes_kpi'; });
        if (empty($includes_kpi_column)) {
            oo_log('CRITICAL: includes_kpi column does not exist in the database table!', __METHOD__);
            
            // Try to add it if missing
            $add_column_result = $wpdb->query("ALTER TABLE {$phases_table} ADD COLUMN `includes_kpi` BOOLEAN NOT NULL DEFAULT 1");
            if ($add_column_result === false) {
                oo_log('Failed to add missing includes_kpi column: ' . $wpdb->last_error, __METHOD__);
            } else {
                oo_log('Successfully added missing includes_kpi column to phases table', __METHOD__);
            }
        }

        if ( $phase_id <= 0 || empty($stream_id) || empty($phase_name) ) {
            oo_log('AJAX Error: Phase ID, Stream and Name are required.', $_POST);
            wp_send_json_error( array( 'message' => 'Error: Phase ID, Stream and Name are required.' ) ); return;
        }
        
        // Get current phase before updating to verify changes
        $before_phase = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$phases_table} WHERE phase_id = %d", $phase_id));
        oo_log('Current phase values BEFORE update: ', $before_phase);
        
        // Call to OO_DB::update_phase updated with new parameters, including phase_slug
        $result = OO_DB::update_phase( $phase_id, $stream_id, $phase_name, $phase_slug, $phase_description, $sort_order, $phase_type, $default_kpi_units, $is_active, $includes_kpi );
        
        // Get phase AFTER updating to verify changes
        $after_phase = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$phases_table} WHERE phase_id = %d", $phase_id));
        oo_log('Current phase values AFTER update: ', $after_phase);
        
        // If includes_kpi didn't update correctly, try a direct SQL update as last resort
        if (isset($after_phase->includes_kpi) && (int)$after_phase->includes_kpi !== $includes_kpi) {
            oo_log('includes_kpi did not update correctly. Attempting direct SQL update.', __METHOD__);
            $direct_update = $wpdb->query($wpdb->prepare(
                "UPDATE {$phases_table} SET includes_kpi = %d WHERE phase_id = %d",
                $includes_kpi,
                $phase_id
            ));
            if ($direct_update !== false) {
                oo_log('Direct SQL update of includes_kpi successful', __METHOD__);
                $after_direct_update = $wpdb->get_row($wpdb->prepare(
                    "SELECT phase_id, includes_kpi FROM {$phases_table} WHERE phase_id = %d", 
                    $phase_id
                ));
                oo_log('Phase includes_kpi after direct update: ' . $after_direct_update->includes_kpi, __METHOD__);
            } else {
                oo_log('Direct SQL update failed: ' . $wpdb->last_error, __METHOD__);
            }
        }
        
        if ( is_wp_error( $result ) ) {
            oo_log('AJAX Error updating phase: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            oo_log('AJAX Success: Phase updated. ID: ' . $phase_id, __METHOD__);
            $return_to_stream_slug = isset($_POST['return_to_stream']) ? sanitize_key($_POST['return_to_stream']) : '';
            $return_sub_tab = isset($_POST['return_sub_tab']) ? sanitize_key($_POST['return_sub_tab']) : '';
            $redirect_url = admin_url('admin.php?page=oo_phases'); // Default
            oo_log('[RedirectDebug] Initial redirect_url: ' . $redirect_url, __METHOD__);
            oo_log('[RedirectDebug] Received return_to_stream (tab_slug): ' . $return_to_stream_slug, __METHOD__);
            oo_log('[RedirectDebug] Received return_sub_tab: ' . $return_sub_tab, __METHOD__);

            if (!empty($return_to_stream_slug)) { // $return_to_stream_slug is the tab_slug like 'content'
                $stream_configs = OO_Admin_Pages::get_stream_page_configs_for_redirect();
                oo_log('[RedirectDebug] Stream Configs from OO_Admin_Pages: ', $stream_configs);
                $found_stream_page_slug = '';
                foreach ($stream_configs as $s_id => $config) {
                    oo_log('[RedirectDebug] Checking config for stream ID ' . $s_id . ': tab_slug = ' . (isset($config['tab_slug']) ? $config['tab_slug'] : 'N/A') . ', page_slug = ' . (isset($config['slug']) ? $config['slug'] : 'N/A'), __METHOD__);
                    if (isset($config['tab_slug']) && $config['tab_slug'] === $return_to_stream_slug) { // Comparing tab_slug with tab_slug
                        $found_stream_page_slug = $config['slug']; // This should be the page slug like 'oo_stream_content'
                        oo_log('[RedirectDebug] Match found! Page slug: ' . $found_stream_page_slug, __METHOD__);
                        break;
                    }
                }
                
                if (!empty($found_stream_page_slug)) {
                     $redirect_url = admin_url('admin.php?page=' . $found_stream_page_slug);
                     oo_log('[RedirectDebug] Updated redirect_url with page_slug: ' . $redirect_url, __METHOD__);
                     if (!empty($return_sub_tab)) {
                         $redirect_url = add_query_arg('sub_tab', $return_sub_tab, $redirect_url);
                         oo_log('[RedirectDebug] Appended sub_tab: ' . $redirect_url, __METHOD__);
                     }
                } else {
                    oo_log('[RedirectDebug] No matching stream page slug found for tab_slug: ' . $return_to_stream_slug, __METHOD__);
                    // Fallback if tab_slug didn't match, try if $return_to_stream_slug was the page slug itself (less likely for this specific call)
                    foreach ($stream_configs as $s_id => $config) {
                        if (isset($config['slug']) && $config['slug'] === $return_to_stream_slug) {
                            $found_stream_page_slug = $config['slug'];
                             oo_log('[RedirectDebug] Fallback match on page_slug directly: ' . $found_stream_page_slug, __METHOD__);
                            break;
                        }
                    }
                    if (!empty($found_stream_page_slug)) {
                        $redirect_url = admin_url('admin.php?page=' . $found_stream_page_slug);
                     if (!empty($return_sub_tab)) {
                         $redirect_url = add_query_arg('sub_tab', $return_sub_tab, $redirect_url);
                     }
                    } else {
                        oo_log('[RedirectDebug] Still no match, using default: ' . $redirect_url, __METHOD__);
                }
                }
            } else {
                oo_log('[RedirectDebug] return_to_stream_slug was empty. Using default: ' . $redirect_url, __METHOD__);
            }
            $redirect_url = add_query_arg(array('message' => 'phase_updated'), $redirect_url); // Add success message regardless
            oo_log('[RedirectDebug] Final redirect_url before sending: ' . $redirect_url, __METHOD__);

            wp_send_json_success( array( 'message' => 'Phase updated successfully.', 'redirect_url' => $redirect_url ) );
        }
    }

    public static function ajax_toggle_phase_status() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        
        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;
        if ( $phase_id <= 0 ) {
            oo_log('AJAX Error: Invalid phase ID: ' . $phase_id, __METHOD__);
            wp_send_json_error( array( 'message' => 'Invalid phase ID.' ) ); return;
        }

        // The nonce was created with 'oo_toggle_phase_status_nonce_' . $phase_id
        // The JS sends this specific nonce value via $(this).data('nonce') as _ajax_nonce
        check_ajax_referer( 'oo_toggle_phase_status_nonce_' . $phase_id, '_ajax_nonce' );

        if ( ! current_user_can( oo_get_capability() ) ) { 
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }

        $new_status = isset( $_POST['is_active'] ) ? intval( $_POST['is_active'] ) : 0;
        $result = OO_DB::toggle_phase_status( $phase_id, $new_status );
        if ( is_wp_error( $result ) ) {
            oo_log('AJAX Error toggling phase status: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            $message = $new_status ? 'Phase activated.' : 'Phase deactivated.';
            oo_log('AJAX Success: ' . $message . ' ID: ' . $phase_id, __METHOD__);
            
            $return_to_stream_slug = isset($_POST['return_to_stream']) ? sanitize_key($_POST['return_to_stream']) : '';
            $return_sub_tab = isset($_POST['return_sub_tab']) ? sanitize_key($_POST['return_sub_tab']) : '';
            $redirect_url = admin_url('admin.php?page=oo_phases'); // Default
            oo_log('[ToggleRedirectDebug] Initial redirect_url: ' . $redirect_url, __METHOD__);
            oo_log('[ToggleRedirectDebug] Received return_to_stream (tab_slug): ' . $return_to_stream_slug, __METHOD__);

            if (!empty($return_to_stream_slug)) {
                $stream_configs = OO_Admin_Pages::get_stream_page_configs_for_redirect();
                oo_log('[ToggleRedirectDebug] Stream Configs: ', $stream_configs);
                $found_stream_page_slug = '';
                if (is_array($stream_configs)) {
                    foreach ($stream_configs as $s_id => $config) {
                        if (isset($config['tab_slug']) && $config['tab_slug'] === $return_to_stream_slug) {
                            $found_stream_page_slug = $config['slug'];
                            break;
                        }
                    }
                }
                
                if (!empty($found_stream_page_slug)) {
                     $redirect_url = admin_url('admin.php?page=' . $found_stream_page_slug);
                     if (!empty($return_sub_tab)) {
                         $redirect_url = add_query_arg('sub_tab', $return_sub_tab, $redirect_url);
                     }
                } else {
                    oo_log('[ToggleRedirectDebug] No matching stream page slug for tab_slug: ' . $return_to_stream_slug . '. Using default.', __METHOD__);
                 }
            }
            $redirect_url = add_query_arg(array('message' => 'phase_status_updated'), $redirect_url);
            oo_log('[ToggleRedirectDebug] Final redirect_url: ' . $redirect_url, __METHOD__);

            wp_send_json_success( array( 'message' => $message, 'new_status' => $new_status, 'redirect_url' => $redirect_url ) );
        }
    }

    // --- AJAX Handlers for Phase KPI Links (NEW) ---

    public static function ajax_get_phase_kpi_links() {
        oo_log('AJAX call: ajax_get_phase_kpi_links -- Stop Form KPI Loader', __METHOD__);
        check_ajax_referer('oo_get_phase_kpi_links_nonce', '_ajax_nonce');

        if ( ! current_user_can( oo_get_capability() ) ) {
            oo_log('ajax_get_phase_kpi_links: Permission denied.', __METHOD__);
            wp_send_json_error( ['message' => 'Permission denied.'], 403 );
            return;
        }

        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;
        oo_log('ajax_get_phase_kpi_links: Received Phase ID: ' . $phase_id, __METHOD__);

        if ( $phase_id <= 0 ) {
            oo_log('ajax_get_phase_kpi_links: Invalid Phase ID received: ' . $phase_id, __METHOD__);
            wp_send_json_error( ['message' => 'Invalid Phase ID.'] );
            return;
        }

        // First check all links (including inactive) for debugging
        oo_log('ajax_get_phase_kpi_links: Checking all KPI links for phase ' . $phase_id . ' (including inactive)...', __METHOD__);
        $all_links = OO_DB::get_phase_kpi_links_for_phase( $phase_id, array('active_only' => false) );
        oo_log('ajax_get_phase_kpi_links: Found ' . count($all_links) . ' total KPI links for phase ' . $phase_id, __METHOD__);
        
        foreach ($all_links as $link) {
            $active_status = isset($link->is_active) ? ($link->is_active ? 'Active' : 'Inactive') : 'Unknown';
            oo_log('ajax_get_phase_kpi_links: Link found - KPI: ' . $link->measure_name . ' (Key: ' . $link->measure_key . ') - Status: ' . $active_status, __METHOD__);
        }

        // Fetch the active links from the database
        oo_log('ajax_get_phase_kpi_links: Now fetching ACTIVE ONLY KPI links for phase ' . $phase_id . '...', __METHOD__);
        $kpi_links_raw = OO_DB::get_phase_kpi_links_for_phase( $phase_id, true ); // Pass true for active_only
        oo_log('ajax_get_phase_kpi_links: Raw ACTIVE KPI links from DB for phase ' . $phase_id . ':', $kpi_links_raw);

        if ( is_wp_error( $kpi_links_raw ) ) {
            oo_log('ajax_get_phase_kpi_links: WP_Error getting KPI links: ' . $kpi_links_raw->get_error_message(), __METHOD__);
            wp_send_json_error( ['message' => $kpi_links_raw->get_error_message()] );
            return;
        }
        
        if ( empty( $kpi_links_raw ) ) {
            oo_log('ajax_get_phase_kpi_links: No active KPI links found in DB for phase ' . $phase_id . '. This likely means all linked KPIs are inactive.', __METHOD__);
            // Send success with empty data, as this is not an error, but a valid state.
            wp_send_json_success( [] ); 
            return;
        }

        // Process links if necessary (e.g., ensuring all required fields are present for the form)
        $processed_links = array();
        foreach ( $kpi_links_raw as $link ) {
            // Ensure the link has the necessary properties expected by the stop job form
            if ( isset( $link->measure_key, $link->measure_name, $link->unit_type, $link->is_mandatory ) ) {
                $processed_links[] = array(
                    'link_id' => isset($link->link_id) ? $link->link_id : null,
                    'kpi_measure_id' => isset($link->kpi_measure_id) ? $link->kpi_measure_id : null,
                    'measure_name' => $link->measure_name,
                    'measure_key' => $link->measure_key,
                    'unit_type' => $link->unit_type,
                    'is_mandatory' => (int) $link->is_mandatory, // Ensure it's an integer 0 or 1
                    'display_order' => isset($link->display_order) ? (int) $link->display_order : 0,
                );
                oo_log('ajax_get_phase_kpi_links: Successfully processed KPI link - ' . $link->measure_name . ' (Key: ' . $link->measure_key . ')', __METHOD__);
            } else {
                oo_log('ajax_get_phase_kpi_links: Skipping a KPI link due to missing critical data (measure_key, measure_name, unit_type, or is_mandatory). Link data:', $link);
            }
        }
        
        oo_log('ajax_get_phase_kpi_links: Final processed KPI links for phase ' . $phase_id . ' - count: ' . count($processed_links), $processed_links);

        if ( empty( $processed_links ) ) {
             oo_log('ajax_get_phase_kpi_links: No KPI links remained after processing for phase ' . $phase_id . '. This might happen if linked KPIs are inactive or missing required data for the form.', __METHOD__);
        }

        wp_send_json_success( $processed_links );
    }

    public static function ajax_add_phase_kpi_link() {
        oo_log('AJAX call: ajax_add_phase_kpi_link', __METHOD__);
        check_ajax_referer('oo_manage_phase_kpi_links_nonce', '_ajax_nonce');

        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_send_json_error( ['message' => 'Permission denied.'], 403 );
            return;
        }

        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;
        $kpi_measure_id = isset( $_POST['kpi_measure_id'] ) ? intval( $_POST['kpi_measure_id'] ) : 0;
        $is_mandatory = isset( $_POST['is_mandatory'] ) ? intval( $_POST['is_mandatory'] ) : 0;
        $display_order = isset( $_POST['display_order'] ) ? intval( $_POST['display_order'] ) : 0;

        if ( $phase_id <= 0 || $kpi_measure_id <= 0 ) {
            wp_send_json_error( ['message' => 'Invalid Phase ID or KPI Measure ID.'] );
            return;
        }

        $args = [
            'phase_id' => $phase_id,
            'kpi_measure_id' => $kpi_measure_id,
            'is_mandatory' => $is_mandatory,
            'display_order' => $display_order,
        ];
        $result = OO_DB::add_phase_kpi_link( $args );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( ['message' => $result->get_error_message()] );
        } else {
            wp_send_json_success( ['message' => 'KPI measure linked successfully.', 'link_id' => $result] );
        }
    }

    public static function ajax_update_phase_kpi_link() {
        oo_log('AJAX call: ajax_update_phase_kpi_link', __METHOD__);
        check_ajax_referer('oo_manage_phase_kpi_links_nonce', '_ajax_nonce');

        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_send_json_error( ['message' => 'Permission denied.'], 403 );
            return;
        }

        $link_id = isset( $_POST['link_id'] ) ? intval( $_POST['link_id'] ) : 0;
        if ( $link_id <= 0 ) {
            wp_send_json_error( ['message' => 'Invalid Link ID.'] );
            return;
        }
        
        $args = [];
        if (isset($_POST['is_mandatory'])) {
            $args['is_mandatory'] = intval($_POST['is_mandatory']);
        }
        if (isset($_POST['display_order'])) {
            $args['display_order'] = intval($_POST['display_order']);
        }

        if (empty($args)) {
            wp_send_json_error( ['message' => 'No data to update.'] );
            return;
        }

        $result = OO_DB::update_phase_kpi_link( $link_id, $args );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( ['message' => $result->get_error_message()] );
        } else {
            wp_send_json_success( ['message' => 'KPI link updated successfully.'] );
        }
    }

    public static function ajax_delete_phase_kpi_link() {
        oo_log('AJAX call: ajax_delete_phase_kpi_link', __METHOD__);
        check_ajax_referer('oo_manage_phase_kpi_links_nonce', '_ajax_nonce');

        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_send_json_error( ['message' => 'Permission denied.'], 403 );
            return;
        }

        $link_id = isset( $_POST['link_id'] ) ? intval( $_POST['link_id'] ) : 0;
        if ( $link_id <= 0 ) {
            wp_send_json_error( ['message' => 'Invalid Link ID.'] );
            return;
        }

        $result = OO_DB::delete_phase_kpi_link( $link_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( ['message' => $result->get_error_message()] );
        } else {
            wp_send_json_success( ['message' => 'KPI measure unlinked successfully.'] );
        }
    }

    // AJAX handler to get all active KPI measures for populating select lists etc.
    public static function ajax_get_kpi_measures() {
        oo_log('AJAX call: ajax_get_kpi_measures', __METHOD__);
        // Using specific nonce for this action.
        check_ajax_referer('oo_get_kpi_measures_nonce', '_ajax_nonce');

        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_send_json_error( ['message' => 'Permission denied.'], 403 );
            return;
        }

        $args = array(
            'is_active' => isset($_POST['is_active']) ? intval($_POST['is_active']) : null,
            'number'    => isset($_POST['number']) ? intval($_POST['number']) : -1, // Default to all
            'orderby'   => isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'measure_name',
            'order'     => isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'ASC',
        );

        $kpis = OO_DB::get_kpi_measures( array_filter($args) ); // array_filter to remove nulls if not set

        if ( is_array( $kpis ) ) {
            wp_send_json_success( $kpis );
        } else {
            wp_send_json_error( ['message' => 'Could not retrieve KPI measures.'] );
        }
    }

    public static function ajax_save_phase_kpi_links() {
        oo_log('AJAX call: ajax_save_phase_kpi_links', __METHOD__);
        check_ajax_referer('oo_manage_phase_kpi_links_nonce', '_ajax_nonce');

        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_send_json_error( ['message' => 'Permission denied.'], 403 );
            return;
        }

        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;
        $links_data = isset( $_POST['links'] ) && is_array( $_POST['links'] ) ? $_POST['links'] : array();

        if ( $phase_id <= 0 ) {
            wp_send_json_error( ['message' => 'Invalid Phase ID.'] );
            return;
        }

        // Start a transaction if your DB engine supports it and $wpdb allows easy transaction handling.
        // For now, we'll do delete then insert.
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        // 1. Delete all existing links for this phase
        $delete_result = OO_DB::delete_phase_kpi_links_for_phase( $phase_id );
        if ( is_wp_error( $delete_result ) ) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error( ['message' => 'Error clearing existing KPI links: ' . $delete_result->get_error_message()] );
            return;
        }

        // 2. Add new links
        if ( !empty($links_data) ) {
            foreach ($links_data as $link_info) {
                $add_args = array(
                    'phase_id'        => $phase_id,
                    'kpi_measure_id'  => isset($link_info['kpi_measure_id']) ? intval($link_info['kpi_measure_id']) : 0,
                    'is_mandatory'    => isset($link_info['is_mandatory']) ? intval($link_info['is_mandatory']) : 0,
                    'display_order'   => isset($link_info['display_order']) ? intval($link_info['display_order']) : 0,
                );
                if (empty($add_args['kpi_measure_id'])) continue; // Skip if kpi_measure_id is invalid

                $add_result = OO_DB::add_phase_kpi_link( $add_args );
                if ( is_wp_error( $add_result ) ) {
                    $wpdb->query('ROLLBACK');
                    wp_send_json_error( ['message' => 'Error adding KPI link (' . $add_args['kpi_measure_id'] . '): ' . $add_result->get_error_message()] );
                    return;
                }
            }
        }
        
        $wpdb->query('COMMIT');
        wp_send_json_success( ['message' => 'KPI links saved successfully.'] );
    }

    /**
     * AJAX handler to get active phases for a specific stream.
     * Used to populate checklists in modals.
     */
    public static function ajax_get_phases_for_stream() {
        check_ajax_referer( 'oo_get_phases_nonce', '_ajax_nonce' ); // Assumes a general 'get_phases_nonce'

        if ( ! current_user_can( 'manage_options' ) ) { // Or a capability to view phases
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'operations-organizer' ) ), 403 );
            return;
        }

        $stream_id = isset( $_POST['stream_id'] ) ? intval( $_POST['stream_id'] ) : 0;
        if ( $stream_id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid Stream ID.', 'operations-organizer' ) ) );
            return;
        }

        $phases = OO_DB::get_phases(array(
            'stream_id' => $stream_id,
            'is_active' => 1, // Only active phases for selection
            'number'    => -1,
            'orderby'   => 'order_in_stream',
            'order'     => 'ASC'
        ));

        if (is_array($phases)) {
            wp_send_json_success( array( 'phases' => $phases ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Could not retrieve phases for the stream.', 'operations-organizer' ) ) );
        }
    }

    /**
     * AJAX handler to get phase IDs a specific KPI is linked to within a specific stream.
     */
    public static function ajax_get_phase_links_for_kpi_in_stream() {
        check_ajax_referer( 'oo_get_phase_kpi_links_nonce', '_ajax_nonce' ); // Reuse existing nonce

        if ( ! current_user_can( 'manage_options' ) ) { // Or a capability to view links
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'operations-organizer' ) ), 403 );
            return;
        }

        $kpi_measure_id = isset( $_POST['kpi_measure_id'] ) ? intval( $_POST['kpi_measure_id'] ) : 0;
        $stream_id = isset( $_POST['stream_id'] ) ? intval( $_POST['stream_id'] ) : 0;

        if ( $kpi_measure_id <= 0 || $stream_id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid KPI Measure ID or Stream ID.', 'operations-organizer' ) ) );
            return;
        }

        $linked_phases_data = OO_DB::get_phase_kpi_links_for_phase( $stream_id, array(
            'join_measures' => true, // We need kpi_measure_id from the join to filter
            'active_only' => null // Get all links, regardless of KPI active status, just for the specific KPI ID
        ) ); 
        
        $linked_phase_ids = array();
        if (is_array($linked_phases_data)) {
            foreach ($linked_phases_data as $link) {
                if ($link->kpi_measure_id == $kpi_measure_id) {
                    $linked_phase_ids[] = $link->phase_id;
                }
            }
        }
        // Ensure unique IDs, though DISTINCT in SQL might be better if query was direct
        $linked_phase_ids = array_unique($linked_phase_ids);

        wp_send_json_success( array( 'linked_phase_ids' => array_values($linked_phase_ids) ) ); // Re-index array
    }

    public static function ajax_delete_phase() {
        check_ajax_referer( 'oo_delete_phase_ajax_nonce', '_ajax_nonce' );

        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'operations-organizer' ) ), 403 );
            return;
        }

        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;
        $force_delete_logs = isset( $_POST['force_delete_logs'] ) && $_POST['force_delete_logs'] === 'true';

        if ( $phase_id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid Phase ID.', 'operations-organizer' ) ) );
            return;
        }

        // Check if the phase is used in any job logs
        $job_logs_count = OO_DB::get_job_logs_count(array('phase_id' => $phase_id)); 

        if ( $job_logs_count > 0 && !$force_delete_logs ) {
            wp_send_json_success( array( 
                'message' => sprintf(
                    esc_html__('This phase is associated with %d job log(s). Are you sure you want to delete this phase AND all its associated job logs? This action cannot be undone.', 'operations-organizer'), 
                    $job_logs_count
                ),
                'confirmation_needed' => true, // Flag for JS to show second confirm
                'usage_count' => $job_logs_count
            ) );
            return;
        }

        // If force_delete_logs is true (and logs exist), or if no logs exist in the first place
        if ($job_logs_count > 0 && $force_delete_logs) {
            oo_log('Force deleting job logs for phase ID: ' . $phase_id, __METHOD__);
            $delete_logs_result = OO_DB::delete_job_logs_for_phase($phase_id);
            if (is_wp_error($delete_logs_result)) {
                wp_send_json_error(array(
                    'message' => sprintf(esc_html__('Could not delete associated job logs for phase %d. Error: %s', 'operations-organizer'), $phase_id, $delete_logs_result->get_error_message())
                ));
                return;
            }
            oo_log('Successfully deleted ' . $delete_logs_result . ' job logs for phase ID: ' . $phase_id, __METHOD__);
        }

        // Clean up linked KPI measures for this phase
        OO_DB::delete_phase_kpi_links_for_phase($phase_id);
        
        // Placeholder for deleting derived KPI definitions if they become phase-specific in the future
        // OO_DB::delete_derived_kpi_definitions_for_phase($phase_id);

        $result = OO_DB::delete_phase( $phase_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            if ($result === true || $result === 0) { // $result can be 0 if already deleted but links were cleaned
                 wp_send_json_success( array( 'message' => __( 'Phase and associated data deleted successfully.', 'operations-organizer' ) ) );
            } else {
                 wp_send_json_error( array( 'message' => __( 'Could not delete phase. An unexpected error occurred.', 'operations-organizer' ) ) );
            }
        }
    }
} 