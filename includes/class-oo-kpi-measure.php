<?php
// /includes/class-oo-kpi-measure.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_KPI_Measure {

    /**
     * AJAX handler for adding a new KPI Measure.
     */
    public static function ajax_add_kpi_measure() {
        // Check nonce
        check_ajax_referer( 'oo_add_kpi_measure_nonce', '_ajax_nonce' ); // Matches JS: oo_data.nonce_add_kpi_measure

        // Check user capability
        if ( ! current_user_can( 'manage_options' ) ) { // Adjust capability as needed
            wp_send_json_error( array( 'message' => __( 'You do not have permission to add KPI measures.', 'operations-organizer' ) ) );
        }

        $args = array();
        if ( empty( $_POST['measure_name'] ) || empty( $_POST['measure_key'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Measure Name and Measure Key are required.', 'operations-organizer' ) ) );
        }

        $args['measure_name'] = sanitize_text_field( $_POST['measure_name'] );
        $args['measure_key'] = sanitize_key( $_POST['measure_key'] );
        $args['unit_type'] = isset( $_POST['unit_type'] ) ? sanitize_text_field( $_POST['unit_type'] ) : 'integer';
        $args['is_active'] = isset( $_POST['is_active'] ) ? 1 : 0;

        // Mandatory phase linking check
        $link_to_phases = isset($_POST['link_to_phases']) && is_array($_POST['link_to_phases']) ? array_map('intval', $_POST['link_to_phases']) : array();
        $stream_id_context = isset($_POST['stream_id_context']) ? intval($_POST['stream_id_context']) : 0;

        if (empty($link_to_phases)) {
            // This server-side check is a fallback; JS should prevent this.
            // However, we also need to consider if there were any phases available to link to for that stream.
            $phases_in_stream_for_linking = array();
            if ($stream_id_context > 0) {
                 $phases_in_stream_for_linking = OO_DB::get_phases(array(
                    'stream_id' => $stream_id_context,
                    'is_active' => 1, 
                    'number' => 1 // Just need to know if at least one exists
                ));
            }
            if (!empty($phases_in_stream_for_linking)) {
                wp_send_json_error( array( 'message' => __( 'Please link this KPI to at least one phase in the current stream.', 'operations-organizer' ) ) );
                return;
            }
            // If no active phases were available for linking in the stream, we might allow creation without links, or handle as a specific setup issue.
            // For now, if phases were available, linking is mandatory.
        }

        $result = OO_DB::add_kpi_measure( $args );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            $new_kpi_measure_id = $result;
            // Link to selected phases
            if ($new_kpi_measure_id > 0 && !empty($link_to_phases)) {
                foreach ($link_to_phases as $phase_id) {
                    if ($phase_id > 0) {
                        OO_DB::add_phase_kpi_link(array(
                            'phase_id' => $phase_id,
                            'kpi_measure_id' => $new_kpi_measure_id,
                            'is_mandatory' => 0, // Default for new links from here
                            'display_order' => 0   // Default for new links from here
                        ));
                        oo_log('[KPI Add] Attempted to link KPI ID ' . $new_kpi_measure_id . ' to Phase ID ' . $phase_id . ' in Stream ID ' . $stream_id_context, __METHOD__); // DEBUG
                        // Error handling for add_phase_kpi_link can be added if needed
                    }
                }
            }
            wp_send_json_success( array( 'message' => __( 'KPI Measure added and linked successfully.', 'operations-organizer' ), 'kpi_measure_id' => $new_kpi_measure_id ) );
        }
    }

    /**
     * AJAX handler for getting KPI Measure details.
     */
    public static function ajax_get_kpi_measure_details() {
        check_ajax_referer( 'oo_get_kpi_measure_details_nonce', '_ajax_nonce' ); // Matches JS: oo_data.nonce_get_kpi_measure_details

        if ( ! current_user_can( 'manage_options' ) ) { // Adjust capability
            wp_send_json_error( array( 'message' => __( 'You do not have permission to view KPI measure details.', 'operations-organizer' ) ) );
        }

        if ( empty( $_POST['kpi_measure_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'KPI Measure ID is required.', 'operations-organizer' ) ) );
        }

        $kpi_measure_id = intval( $_POST['kpi_measure_id'] );
        $kpi_measure = OO_DB::get_kpi_measure( $kpi_measure_id );

        if ( $kpi_measure ) {
            wp_send_json_success( array( 'kpi_measure' => $kpi_measure ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'KPI Measure not found.', 'operations-organizer' ) ) );
        }
    }

    /**
     * AJAX handler for updating an existing KPI Measure.
     */
    public static function ajax_update_kpi_measure() {
        check_ajax_referer( 'oo_edit_kpi_measure_nonce', '_ajax_nonce' ); // Matches JS: oo_data.nonce_edit_kpi_measure

        if ( ! current_user_can( 'manage_options' ) ) { // Adjust capability
            wp_send_json_error( array( 'message' => __( 'You do not have permission to update KPI measures.', 'operations-organizer' ) ) );
        }

        if ( empty( $_POST['kpi_measure_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'KPI Measure ID is required for update.', 'operations-organizer' ) ) );
        }
        if ( empty( $_POST['measure_name'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Measure Name is required.', 'operations-organizer' ) ) );
        }

        $kpi_measure_id = intval( $_POST['kpi_measure_id'] );
        $args = array();
        $args['measure_name'] = sanitize_text_field( $_POST['measure_name'] );
        // measure_key is not updated via this AJAX handler as it should be immutable after creation.
        $args['unit_type'] = isset( $_POST['unit_type'] ) ? sanitize_text_field( $_POST['unit_type'] ) : 'integer';
        $args['is_active'] = isset( $_POST['is_active'] ) ? 1 : 0;

        $result = OO_DB::update_kpi_measure( $kpi_measure_id, $args );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            // $result from OO_DB::update_kpi_measure is true/false or WP_Error.
            if ($result === true) {
                // Handle phase linking reconciliation for the current stream context
                $stream_id_context = isset($_POST['stream_id_context']) ? intval($_POST['stream_id_context']) : 0;
                $selected_phase_ids_for_linking = isset($_POST['link_to_phases']) && is_array($_POST['link_to_phases']) ? array_map('intval', $_POST['link_to_phases']) : array();

                if ($kpi_measure_id > 0 && $stream_id_context > 0) {
                    // Get currently linked phases for this KPI in this stream
                    $existing_links_raw = OO_DB::get_phase_kpi_links_for_phase($stream_id_context, array('join_measures' => true, 'active_only' => null));
                    $currently_linked_phase_ids_in_stream = array();
                    if (is_array($existing_links_raw)) {
                        foreach ($existing_links_raw as $link) {
                            if ($link->kpi_measure_id == $kpi_measure_id) {
                                $currently_linked_phase_ids_in_stream[] = $link->phase_id;
                            }
                        }
                    }
                    $currently_linked_phase_ids_in_stream = array_unique($currently_linked_phase_ids_in_stream);

                    // Phases to add: in selected_phase_ids_for_linking but not in currently_linked_phase_ids_in_stream
                    $phases_to_add_link = array_diff($selected_phase_ids_for_linking, $currently_linked_phase_ids_in_stream);
                    foreach ($phases_to_add_link as $phase_id_to_add) {
                        OO_DB::add_phase_kpi_link(array(
                            'phase_id' => $phase_id_to_add,
                            'kpi_measure_id' => $kpi_measure_id,
                            'is_mandatory' => 0, 
                            'display_order' => 0
                        ));
                    }

                    // Phases to remove: in currently_linked_phase_ids_in_stream but not in selected_phase_ids_for_linking
                    $phases_to_remove_link = array_diff($currently_linked_phase_ids_in_stream, $selected_phase_ids_for_linking);
                    if (!empty($phases_to_remove_link)) {
                        foreach ($existing_links_raw as $link) { // Need link_id to delete
                            if ($link->kpi_measure_id == $kpi_measure_id && in_array($link->phase_id, $phases_to_remove_link)) {
                                OO_DB::delete_phase_kpi_link($link->link_id);
                            }
                        }
                    }
                }

                // Auto-deactivation logic
                $all_links_for_kpi = OO_DB::get_phase_kpi_links_by_measure($kpi_measure_id, array('join_phases' => false)); // Get all links across all streams
                if (empty($all_links_for_kpi) && $args['is_active'] == 1) { // Only deactivate if it was active
                    OO_DB::toggle_kpi_measure_status($kpi_measure_id, 0);
                    wp_send_json_success( array( 'message' => __( 'KPI Measure updated. All phase links removed, KPI automatically deactivated.', 'operations-organizer' ) ) );
                    return;
                }

                 wp_send_json_success( array( 'message' => __( 'KPI Measure updated successfully.', 'operations-organizer' ) ) );
            } else {
                // This case might indicate 0 rows affected but no $wpdb->error, or an unhandled WP_Error.
                // For simplicity, if not WP_Error and not true, assume a generic failure.
                // OO_DB methods should consistently return WP_Error on actual DB failures.
                wp_send_json_error( array( 'message' => __( 'Could not update KPI Measure. No changes made or an unexpected issue occurred.', 'operations-organizer' ) ) );
            }
        }
    }

    /**
     * AJAX handler for toggling KPI Measure status.
     */
    public static function ajax_toggle_kpi_measure_status() {
        // Dynamic nonce check based on kpi_measure_id or a general toggle nonce
        $kpi_measure_id = isset($_POST['kpi_measure_id']) ? intval($_POST['kpi_measure_id']) : 0;
        // The JS sends the nonce value created with 'oo_toggle_kpi_measure_status_nonce' 
        // if oo_data.nonces[dynamic_action] is not found and it falls back to oo_data.nonce_toggle_kpi_status.
        // So, we verify against the action used for that general nonce.
        check_ajax_referer( 'oo_toggle_kpi_measure_status_nonce', '_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) { // Adjust capability
            wp_send_json_error( array( 'message' => __( 'You do not have permission to change KPI measure status.', 'operations-organizer' ) ) );
        }

        if ( empty( $kpi_measure_id ) ) {
            wp_send_json_error( array( 'message' => __( 'KPI Measure ID is required.', 'operations-organizer' ) ) );
        }

        $new_status = isset( $_POST['is_active'] ) ? intval( $_POST['is_active'] ) : 0;
        $result = OO_DB::toggle_kpi_measure_status( $kpi_measure_id, $new_status );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
             if ($result === true) {
                wp_send_json_success( array( 'message' => __( 'KPI Measure status updated successfully.', 'operations-organizer' ) ) );
             } else {
                 wp_send_json_error( array( 'message' => __( 'Could not update KPI Measure status. No changes made or an unexpected issue occurred.', 'operations-organizer' ) ) );
             }
        }
    }

    /**
     * AJAX handler for deleting a KPI Measure.
     */
    public static function ajax_delete_kpi_measure() {
        $kpi_measure_id = isset($_POST['kpi_measure_id']) ? intval($_POST['kpi_measure_id']) : 0;
        // The JS sends the nonce value created with 'oo_delete_kpi_measure_nonce'
        // if oo_data.nonces[dynamic_action] is not found and it falls back to oo_data.nonce_delete_kpi_measure.
        // So, we verify against the action used for that general nonce.
        check_ajax_referer( 'oo_delete_kpi_measure_nonce', '_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) { // Adjust capability
            wp_send_json_error( array( 'message' => __( 'You do not have permission to delete KPI measures.', 'operations-organizer' ) ) );
        }

        if ( empty( $kpi_measure_id ) ) {
            wp_send_json_error( array( 'message' => __( 'KPI Measure ID is required for deletion.', 'operations-organizer' ) ) );
        }

        // Before deleting, we might want to unlink it from phases.
        // OO_DB::delete_phase_kpi_links_by_measure($kpi_measure_id);
        // The OO_DB::delete_kpi_measure method should ideally handle this or related checks.

        $result = OO_DB::delete_kpi_measure( $kpi_measure_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            if ($result === true) {
                wp_send_json_success( array( 'message' => __( 'KPI Measure deleted successfully.', 'operations-organizer' ) ) );
            } elseif ($result === 0) {
                 wp_send_json_success( array( 'message' => __( 'KPI Measure already deleted or not found.', 'operations-organizer' ) ) );
            } else {
                 wp_send_json_error( array( 'message' => __( 'Could not delete KPI Measure. An unexpected issue occurred.', 'operations-organizer' ) ) );
            }
        }
    }

    /**
     * AJAX handler to get HTML for the KPI measures list for a specific stream.
     */
    public static function ajax_get_kpi_measures_for_stream_html() {
        // Use a general nonce for fetching data, or create a specific one.
        // Re-using 'oo_get_kpi_measures_nonce' as an example, ensure it's generated and available in oo_data.
        check_ajax_referer( 'oo_get_kpi_measures_nonce', '_ajax_nonce' ); 

        if ( ! current_user_can( 'manage_options' ) ) { // Adjust capability as needed
            wp_send_json_error( array( 'message' => __( 'You do not have permission to view this data.', 'operations-organizer' ) ) );
        }

        if ( empty( $_POST['stream_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Stream ID is required.', 'operations-organizer' ) ) );
        }
        $stream_id = intval( $_POST['stream_id'] );
        $stream_slug = isset($_POST['stream_slug']) ? sanitize_key($_POST['stream_slug']) : '';

        $kpis_from_db = OO_DB::get_kpi_measures_for_stream( $stream_id, array('is_active' => null) ); // Get all (active/inactive)
        $stream_kpi_measures_processed = array();
        if (!empty($kpis_from_db)) {
            foreach($kpis_from_db as $kpi) {
                $phase_names = OO_DB::get_phase_names_for_kpi_in_stream($kpi->kpi_measure_id, $stream_id);
                $kpi->used_in_phases_in_stream = !empty($phase_names) ? esc_html(implode(', ', $phase_names)) : 'N/A';
                $stream_kpi_measures_processed[] = $kpi;
            }
        }

        ob_start();
        if ( ! empty( $stream_kpi_measures_processed ) ) :
            foreach ( $stream_kpi_measures_processed as $kpi_measure ) :
                $row_classes = 'kpi-measure-row-' . esc_attr($kpi_measure->kpi_measure_id);
                $row_classes .= $kpi_measure->is_active ? ' active' : ' inactive';
                ?>
                <tr class="<?php echo $row_classes; ?>">
                    <td><strong><button type="button" class="button-link oo-edit-kpi-measure-stream" data-kpi-measure-id="<?php echo esc_attr( $kpi_measure->kpi_measure_id ); ?>"><?php echo esc_html( $kpi_measure->measure_name ); ?></button></strong></td>
                    <td><code><?php echo esc_html( $kpi_measure->measure_key ); ?></code></td>
                    <td><?php echo $kpi_measure->used_in_phases_in_stream; ?></td>
                    <td><?php echo esc_html( ucfirst( $kpi_measure->unit_type ) ); ?></td>
                    <td>
                        <?php echo $kpi_measure->is_active ? __( 'Active', 'operations-organizer' ) : __( 'Inactive', 'operations-organizer' ); ?>
                    </td>
                    <td class="actions column-actions">
                        <button type="button" class="button-secondary oo-edit-kpi-measure-stream" data-kpi-measure-id="<?php echo esc_attr( $kpi_measure->kpi_measure_id ); ?>"><?php esc_html_e( 'Edit', 'operations-organizer' ); ?></button>
                        <?php
                        $toggle_action_text = $kpi_measure->is_active ? __( 'Deactivate', 'operations-organizer' ) : __( 'Activate', 'operations-organizer' );
                        $new_status_val = $kpi_measure->is_active ? 0 : 1;
                        $nonce_action_toggle = 'oo_toggle_kpi_measure_status_' . $kpi_measure->kpi_measure_id;
                        // Ensure this nonce ($nonce_action_toggle) is added to oo_data.nonces in PHP for the JS to use it.
                        ?>
                        <button type="button"
                                class="button-secondary oo-toggle-kpi-measure-status-stream"
                                data-kpi-measure-id="<?php echo esc_attr($kpi_measure->kpi_measure_id); ?>"
                                data-new-status="<?php echo esc_attr($new_status_val); ?>"
                                data-nonce-action="<?php echo esc_attr($nonce_action_toggle); ?>">
                            <?php echo esc_html($toggle_action_text); ?>
                        </button>
                        <?php $nonce_action_delete = 'oo_delete_kpi_measure_' . $kpi_measure->kpi_measure_id; ?>
                        | <a href="#" 
                           class="oo-delete-kpi-measure-stream" 
                           data-kpi-measure-id="<?php echo esc_attr( $kpi_measure->kpi_measure_id ); ?>" 
                           data-nonce-action="<?php echo esc_attr($nonce_action_delete); ?>"
                           style="color:#b32d2e; text-decoration: none;"><?php esc_html_e( 'Delete', 'operations-organizer' ); ?></a>
                    </td>
                </tr>
            <?php endforeach;
        else :
            ?>
            <tr><td colspan="6"><?php esc_html_e( 'No KPI measures found specifically linked to phases in this stream yet, or no active KPI Measures defined globally.', 'operations-organizer' ); ?></td></tr>
            <?php
        endif;
        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * AJAX handler to get Derived KPI Definitions as JSON, optionally filtered by stream.
     * This is used for populating UI elements like the column selector modal.
     */
    public static function ajax_get_json_derived_kpi_definitions() {
        check_ajax_referer( 'oo_get_derived_kpis_nonce', '_ajax_nonce' ); // Use a general nonce for getting derived KPIs

        if ( ! current_user_can( 'manage_options' ) ) { // Or a more specific capability
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'operations-organizer' ) ), 403 );
            return;
        }

        $stream_id = isset( $_POST['stream_id'] ) ? intval( $_POST['stream_id'] ) : 0;
        $definitions = array();

        if ( $stream_id > 0 ) {
            // Get KPIs for the stream first
            $stream_kpi_measures = OO_DB::get_kpi_measures_for_stream( $stream_id, array( 'is_active' => null ) ); // Get all active/inactive for relevance
            if ( ! empty( $stream_kpi_measures ) ) {
                $stream_kpi_measure_ids = wp_list_pluck( $stream_kpi_measures, 'kpi_measure_id' );
                $all_derived_kpis = OO_DB::get_derived_kpi_definitions( array( 'is_active' => 1, 'number' => -1 ) ); // Only get active derived KPIs for selection
                
                foreach ( $all_derived_kpis as $dkpi ) {
                    if ( in_array( $dkpi->primary_kpi_measure_id, $stream_kpi_measure_ids ) ) {
                        $definitions[] = $dkpi;
                    }
                }
            }
        } else {
            // If no stream_id, get all active derived KPI definitions
            $definitions = OO_DB::get_derived_kpi_definitions( array( 'is_active' => 1, 'number' => -1 ) );
        }

        wp_send_json_success( array( 'definitions' => $definitions ) );
    }

    /**
     * AJAX handler to get KPI measures for a specific stream as JSON.
     * Used for populating UI elements that need a data object, not HTML.
     */
    public static function ajax_get_json_kpi_measures_for_stream() {
        check_ajax_referer( 'oo_get_kpi_measures_nonce', '_ajax_nonce' ); 

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to view this data.', 'operations-organizer' ) ) );
        }

        if ( empty( $_POST['stream_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Stream ID is required.', 'operations-organizer' ) ) );
        }
        $stream_id = intval( $_POST['stream_id'] );
        
        // Get all active KPIs linked to the stream's phases
        $kpis = OO_DB::get_kpi_measures_for_stream( $stream_id, array('is_active' => 1) );

        if ( is_array( $kpis ) ) {
            wp_send_json_success( array( 'kpis' => $kpis ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Could not retrieve KPI measures for the stream.', 'operations-organizer' ) ) );
        }
    }
}

?> 