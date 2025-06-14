<?php
/**
 * AJAX handlers for the Stream Dashboard feature.
 *
 * @package Operations_Organizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class OO_Stream_Dashboard_AJAX {

	public static function init() {
		$ajax_actions = array(
			'add_kpi_measure',
			'get_kpi_measure_details',
			'update_kpi_measure',
			'toggle_kpi_measure_status',
			'delete_kpi_measure',
			'get_kpi_measures_for_stream_html',
			'get_json_derived_kpi_definitions',
			'get_json_kpi_measures_for_stream',
			'add_phase_from_stream',
			'get_phase_for_stream_modal',
			'update_phase_from_stream',
			'toggle_phase_status_from_stream',
			'delete_phase_from_stream',
			'get_phase_kpi_links_for_stream',
			'add_phase_kpi_link_from_stream',
			'update_phase_kpi_link_from_stream',
			'delete_phase_kpi_link_from_stream',
			'save_phase_kpi_links_from_stream',
			'get_all_kpis_for_stream_linking',
			'get_phases_for_stream_linking',
			'get_phase_links_for_kpi_in_stream',
			'get_stream_job_logs',
		);

		foreach ( $ajax_actions as $action ) {
			add_action( 'wp_ajax_oo_' . $action, array( __CLASS__, 'ajax_' . $action ) );
		}

		$derived_kpi_actions = array(
			'add_derived_kpi_definition',
			'update_derived_kpi_definition',
			'toggle_derived_kpi_status',
			'delete_derived_kpi_definition',
			'get_derived_kpis_for_stream_html',
		);

		foreach ( $derived_kpi_actions as $action ) {
			add_action( 'wp_ajax_oo_' . $action, array( __CLASS__, 'ajax_' . $action ) );
		}
	}

    /**
     * AJAX handler for adding a new KPI Measure.
     */
    public static function ajax_add_kpi_measure() {
        check_ajax_referer( 'oo_add_kpi_measure_nonce', '_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
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

        $link_to_phases = isset($_POST['link_to_phases']) && is_array($_POST['link_to_phases']) ? array_map('intval', $_POST['link_to_phases']) : array();
        $stream_id_context = isset($_POST['stream_id_context']) ? intval($_POST['stream_id_context']) : 0;

        if (empty($link_to_phases)) {
            $phases_in_stream_for_linking = array();
            if ($stream_id_context > 0) {
                 $phases_in_stream_for_linking = OO_DB::get_phases(array(
                    'stream_id' => $stream_id_context,
                    'is_active' => 1, 
                    'number' => 1
                ));
            }
            if (!empty($phases_in_stream_for_linking)) {
                wp_send_json_error( array( 'message' => __( 'Please link this KPI to at least one phase in the current stream.', 'operations-organizer' ) ) );
                return;
            }
        }

        $result = OO_DB::add_kpi_measure( $args );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            $new_kpi_measure_id = $result;
            if ($new_kpi_measure_id > 0 && !empty($link_to_phases)) {
                foreach ($link_to_phases as $phase_id) {
                    if ($phase_id > 0) {
                        OO_DB::add_phase_kpi_link(array(
                            'phase_id' => $phase_id,
                            'kpi_measure_id' => $new_kpi_measure_id,
                            'is_mandatory' => 0,
                            'display_order' => 0
                        ));
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
        check_ajax_referer( 'oo_get_kpi_measure_details_nonce', '_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
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
        check_ajax_referer( 'oo_edit_kpi_measure_nonce', '_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
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
        $args['unit_type'] = isset( $_POST['unit_type'] ) ? sanitize_text_field( $_POST['unit_type'] ) : 'integer';
        $args['is_active'] = isset( $_POST['is_active'] ) ? 1 : 0;

        $result = OO_DB::update_kpi_measure( $kpi_measure_id, $args );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            if ($result === true) {
                $stream_id_context = isset($_POST['stream_id_context']) ? intval($_POST['stream_id_context']) : 0;
                $selected_phase_ids_for_linking = isset($_POST['link_to_phases']) && is_array($_POST['link_to_phases']) ? array_map('intval', $_POST['link_to_phases']) : array();

                if ($kpi_measure_id > 0 && $stream_id_context > 0) {
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

                    $phases_to_add_link = array_diff($selected_phase_ids_for_linking, $currently_linked_phase_ids_in_stream);
                    foreach ($phases_to_add_link as $phase_id_to_add) {
                        OO_DB::add_phase_kpi_link(array(
                            'phase_id' => $phase_id_to_add,
                            'kpi_measure_id' => $kpi_measure_id,
                            'is_mandatory' => 0, 
                            'display_order' => 0
                        ));
                    }

                    $phases_to_remove_link = array_diff($currently_linked_phase_ids_in_stream, $selected_phase_ids_for_linking);
                    if (!empty($phases_to_remove_link)) {
                        foreach ($existing_links_raw as $link) {
                            if ($link->kpi_measure_id == $kpi_measure_id && in_array($link->phase_id, $phases_to_remove_link)) {
                                OO_DB::delete_phase_kpi_link($link->link_id);
                            }
                        }
                    }
                }

                $all_links_for_kpi = OO_DB::get_phase_kpi_links_by_measure($kpi_measure_id, array('join_phases' => false));
                if (empty($all_links_for_kpi) && $args['is_active'] == 1) {
                    OO_DB::toggle_kpi_measure_status($kpi_measure_id, 0);
                    wp_send_json_success( array( 'message' => __( 'KPI Measure updated. All phase links removed, KPI automatically deactivated.', 'operations-organizer' ) ) );
                    return;
                }

                 wp_send_json_success( array( 'message' => __( 'KPI Measure updated successfully.', 'operations-organizer' ) ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'Could not update KPI Measure. No changes made or an unexpected issue occurred.', 'operations-organizer' ) ) );
            }
        }
    }

    public static function ajax_toggle_kpi_measure_status() {
        $kpi_measure_id = isset($_POST['kpi_measure_id']) ? intval($_POST['kpi_measure_id']) : 0;
        check_ajax_referer( 'oo_toggle_kpi_measure_status_nonce', '_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
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

    public static function ajax_delete_kpi_measure() {
        $kpi_measure_id = isset($_POST['kpi_measure_id']) ? intval($_POST['kpi_measure_id']) : 0;
        check_ajax_referer( 'oo_delete_kpi_measure_nonce', '_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to delete KPI measures.', 'operations-organizer' ) ) );
        }

        if ( empty( $kpi_measure_id ) ) {
            wp_send_json_error( array( 'message' => __( 'KPI Measure ID is required for deletion.', 'operations-organizer' ) ) );
        }

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

    public static function ajax_get_kpi_measures_for_stream_html() {
        check_ajax_referer( 'oo_get_kpi_measures_nonce', '_ajax_nonce' ); 

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to view this data.', 'operations-organizer' ) ) );
        }

        if ( empty( $_POST['stream_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Stream ID is required.', 'operations-organizer' ) ) );
        }
        $stream_id = intval( $_POST['stream_id'] );
        $stream_slug = isset($_POST['stream_slug']) ? sanitize_key($_POST['stream_slug']) : '';

        $kpis_from_db = OO_DB::get_kpi_measures_for_stream( $stream_id, array('is_active' => null) );
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

    public static function ajax_get_json_derived_kpi_definitions() {
        check_ajax_referer( 'oo_get_derived_kpis_nonce', '_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'operations-organizer' ) ), 403 );
            return;
        }

        $stream_id = isset( $_POST['stream_id'] ) ? intval( $_POST['stream_id'] ) : 0;
        $definitions = array();

        if ( $stream_id > 0 ) {
            $stream_kpi_measures = OO_DB::get_kpi_measures_for_stream( $stream_id, array( 'is_active' => null ) );
            if ( ! empty( $stream_kpi_measures ) ) {
                $stream_kpi_measure_ids = wp_list_pluck( $stream_kpi_measures, 'kpi_measure_id' );
                $all_derived_kpis = OO_DB::get_derived_kpi_definitions( array( 'is_active' => 1, 'number' => -1 ) );
                
                foreach ( $all_derived_kpis as $dkpi ) {
                    if ( in_array( $dkpi->primary_kpi_measure_id, $stream_kpi_measure_ids ) ) {
                        $definitions[] = $dkpi;
                    }
                }
            }
        } else {
            $definitions = OO_DB::get_derived_kpi_definitions( array( 'is_active' => 1, 'number' => -1 ) );
        }

        wp_send_json_success( array( 'definitions' => $definitions ) );
    }

    public static function ajax_get_json_kpi_measures_for_stream() {
        check_ajax_referer( 'oo_get_kpi_measures_nonce', '_ajax_nonce' ); 

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to view this data.', 'operations-organizer' ) ) );
        }

        if ( empty( $_POST['stream_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Stream ID is required.', 'operations-organizer' ) ) );
        }
        $stream_id = intval( $_POST['stream_id'] );
        
        $kpis = OO_DB::get_kpi_measures_for_stream( $stream_id, array('is_active' => 1) );

        if ( is_array( $kpis ) ) {
            wp_send_json_success( array( 'kpis' => $kpis ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Could not retrieve KPI measures for the stream.', 'operations-organizer' ) ) );
        }
    }

    /**
     * AJAX handler for adding a new Derived KPI Definition.
     */
    public static function ajax_add_derived_kpi_definition() {
        check_ajax_referer( 'oo_add_derived_kpi_nonce', '_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to add derived KPI definitions.', 'operations-organizer' ) ) );
        }

        $required_fields = array('derived_definition_name', 'primary_kpi_measure_id', 'derived_calculation_type');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error( array( 'message' => sprintf(__( 'Required field missing: %s', 'operations-organizer' ), $field) ) );
            }
        }

        $args = array(
            'definition_name'          => sanitize_text_field( $_POST['derived_definition_name'] ),
            'primary_kpi_measure_id'   => intval( $_POST['primary_kpi_measure_id'] ),
            'calculation_type'         => sanitize_text_field( $_POST['derived_calculation_type'] ),
            'secondary_kpi_measure_id' => isset( $_POST['derived_secondary_kpi_measure_id'] ) && !empty($_POST['derived_secondary_kpi_measure_id']) ? intval( $_POST['derived_secondary_kpi_measure_id'] ) : null,
            'time_unit_for_rate'       => isset( $_POST['derived_time_unit_for_rate'] ) && !empty($_POST['derived_time_unit_for_rate']) ? sanitize_text_field( $_POST['derived_time_unit_for_rate'] ) : null,
            'output_description'       => isset( $_POST['derived_output_description'] ) ? sanitize_textarea_field( $_POST['derived_output_description'] ) : '',
            'is_active'                => isset( $_POST['derived_is_active'] ) ? 1 : 0,
        );

        $result = OO_DB::add_derived_kpi_definition( $args );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            wp_send_json_success( array( 'message' => __( 'Derived KPI Definition added successfully.', 'operations-organizer' ), 'derived_definition_id' => $result ) );
        }
    }

    /**
     * AJAX handler for updating an existing Derived KPI Definition.
     */
    public static function ajax_update_derived_kpi_definition() {
        check_ajax_referer( 'oo_edit_derived_kpi_nonce', '_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to update derived KPI definitions.', 'operations-organizer' ) ) );
        }

        if ( empty( $_POST['derived_definition_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Derived KPI Definition ID is required for update.', 'operations-organizer' ) ) );
        }
        
        $required_fields = array('derived_definition_name', 'primary_kpi_measure_id', 'derived_calculation_type');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error( array( 'message' => sprintf(__( 'Required field missing: %s', 'operations-organizer' ), $field) ) );
            }
        }

        $derived_definition_id = intval( $_POST['derived_definition_id'] );
        $args = array(
            'definition_name'          => sanitize_text_field( $_POST['derived_definition_name'] ),
            'primary_kpi_measure_id'   => intval( $_POST['primary_kpi_measure_id'] ),
            'calculation_type'         => sanitize_text_field( $_POST['derived_calculation_type'] ),
            'secondary_kpi_measure_id' => isset( $_POST['derived_secondary_kpi_measure_id'] ) && !empty($_POST['derived_secondary_kpi_measure_id']) ? intval( $_POST['derived_secondary_kpi_measure_id'] ) : null,
            'time_unit_for_rate'       => isset( $_POST['derived_time_unit_for_rate'] ) && !empty($_POST['derived_time_unit_for_rate']) ? sanitize_text_field( $_POST['derived_time_unit_for_rate'] ) : null,
            'output_description'       => isset( $_POST['derived_output_description'] ) ? sanitize_textarea_field( $_POST['derived_output_description'] ) : '',
            'is_active'                => isset( $_POST['derived_is_active'] ) ? 1 : 0,
        );

        $result = OO_DB::update_derived_kpi_definition( $derived_definition_id, $args );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            if ($result === true) {
                 wp_send_json_success( array( 'message' => __( 'Derived KPI Definition updated successfully.', 'operations-organizer' ) ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'Could not update Derived KPI Definition. No changes made or an unexpected issue occurred.', 'operations-organizer' ) ) );
            }
        }
    }

    /**
     * AJAX handler for toggling Derived KPI Definition status.
     */
    public static function ajax_toggle_derived_kpi_status() {
        $derived_definition_id = isset($_POST['derived_definition_id']) ? intval($_POST['derived_definition_id']) : 0;
        check_ajax_referer( 'oo_toggle_derived_kpi_status_nonce', '_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to change derived KPI status.', 'operations-organizer' ) ) );
        }

        if ( empty( $derived_definition_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Derived KPI Definition ID is required.', 'operations-organizer' ) ) );
        }

        $new_status = isset( $_POST['is_active'] ) ? intval( $_POST['is_active'] ) : 0;
        $args = array( 'is_active' => $new_status ); 
        $result = OO_DB::update_derived_kpi_definition( $derived_definition_id, $args );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
             if ($result === true) {
                wp_send_json_success( array( 'message' => __( 'Derived KPI status updated successfully.', 'operations-organizer' ) ) );
             } else {
                 wp_send_json_error( array( 'message' => __( 'Could not update Derived KPI status. No changes made or an unexpected issue occurred.', 'operations-organizer' ) ) );
             }
        }
    }

    /**
     * AJAX handler for deleting a Derived KPI Definition.
     */
    public static function ajax_delete_derived_kpi_definition() {
        $derived_definition_id = isset($_POST['derived_definition_id']) ? intval($_POST['derived_definition_id']) : 0;
        check_ajax_referer( 'oo_delete_derived_kpi_nonce', '_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to delete derived KPI definitions.', 'operations-organizer' ) ) );
        }

        if ( empty( $derived_definition_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Derived KPI Definition ID is required for deletion.', 'operations-organizer' ) ) );
        }

        $result = OO_DB::delete_derived_kpi_definition( $derived_definition_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            if ($result === true) {
                wp_send_json_success( array( 'message' => __( 'Derived KPI Definition deleted successfully.', 'operations-organizer' ) ) );
            } elseif ($result === 0) {
                 wp_send_json_success( array( 'message' => __( 'Derived KPI Definition already deleted or not found.', 'operations-organizer' ) ) );
            } else {
                 wp_send_json_error( array( 'message' => __( 'Could not delete Derived KPI Definition. An unexpected issue occurred.', 'operations-organizer' ) ) );
            }
        }
    }

    /**
     * AJAX handler to get HTML for the Derived KPI list for a specific stream.
     */
    public static function ajax_get_derived_kpis_for_stream_html() {
        check_ajax_referer( 'oo_get_derived_kpi_definitions_nonce', '_ajax_nonce' ); 

        if ( ! current_user_can( 'manage_options' ) ) { 
            wp_send_json_error( array( 'message' => __( 'You do not have permission to view this data.', 'operations-organizer' ) ) );
        }

        if ( empty( $_POST['stream_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Stream ID is required.', 'operations-organizer' ) ) );
        }
        $stream_id = intval( $_POST['stream_id'] );
        
        $stream_derived_kpis = array();
        $stream_kpi_measures_in_db = OO_DB::get_kpi_measures_for_stream( $stream_id, array('is_active' => null));
        $stream_kpi_measure_ids = array();
        if (!empty($stream_kpi_measures_in_db)) {
            $stream_kpi_measure_ids = wp_list_pluck($stream_kpi_measures_in_db, 'kpi_measure_id');
        }

        if (!empty($stream_kpi_measure_ids)) {
            $all_derived_kpis = OO_DB::get_derived_kpi_definitions(array('is_active' => null, 'number' => -1)); 
            foreach ($all_derived_kpis as $dkpi) {
                if (in_array($dkpi->primary_kpi_measure_id, $stream_kpi_measure_ids)) {
                    $primary_kpi = OO_DB::get_kpi_measure($dkpi->primary_kpi_measure_id);
                    $dkpi->primary_kpi_measure_name = $primary_kpi ? esc_html($primary_kpi->measure_name) : 'Unknown KPI';
                    
                    $dkpi->secondary_kpi_measure_name = 'N/A';
                    if ($dkpi->calculation_type === 'ratio_to_kpi' && !empty($dkpi->secondary_kpi_measure_id)) {
                        $secondary_kpi = OO_DB::get_kpi_measure($dkpi->secondary_kpi_measure_id);
                        $dkpi->secondary_kpi_measure_name = $secondary_kpi ? esc_html($secondary_kpi->measure_name) : 'Unknown Secondary KPI';
                    }
                    $stream_derived_kpis[] = $dkpi;
                }
            }
        }

        ob_start();
        if ( ! empty( $stream_derived_kpis ) ) :
            foreach ( $stream_derived_kpis as $dkpi ) :
                $row_classes = 'derived-kpi-row-' . esc_attr($dkpi->derived_definition_id);
                $row_classes .= $dkpi->is_active ? ' active' : ' inactive';
                ?>
                <tr class="<?php echo $row_classes; ?>">
                    <td><strong><button type="button" class="button-link oo-edit-derived-kpi-stream" data-derived-kpi-id="<?php echo esc_attr( $dkpi->derived_definition_id ); ?>"><?php echo esc_html( $dkpi->definition_name ); ?></button></strong></td>
                    <td><?php echo $dkpi->primary_kpi_measure_name; ?></td>
                    <td><?php echo esc_html( ucfirst( str_replace('_', ' ', $dkpi->calculation_type ) ) ); ?></td>
                    <td><?php echo $dkpi->secondary_kpi_measure_name; ?></td>
                    <td><?php echo $dkpi->is_active ? __( 'Active', 'operations-organizer' ) : __( 'Inactive', 'operations-organizer' ); ?></td>
                    <td class="actions column-actions">
                        <button type="button" class="button-secondary oo-edit-derived-kpi-stream" data-derived-kpi-id="<?php echo esc_attr( $dkpi->derived_definition_id ); ?>"><?php esc_html_e( 'Edit', 'operations-organizer' ); ?></button>
                        <?php
                        $dkpi_toggle_text = $dkpi->is_active ? __( 'Deactivate', 'operations-organizer' ) : __( 'Activate', 'operations-organizer' );
                        $dkpi_new_status = $dkpi->is_active ? 0 : 1;
                        $nonce_action_toggle = 'oo_toggle_derived_kpi_status_' . $dkpi->derived_definition_id;
                        ?>
                        <button type="button"
                                class="button-secondary oo-toggle-derived-kpi-status-stream"
                                data-derived-kpi-id="<?php echo esc_attr($dkpi->derived_definition_id); ?>"
                                data-new-status="<?php echo esc_attr($dkpi_new_status); ?>"
                                data-nonce-action="<?php echo esc_attr($nonce_action_toggle); ?>">
                            <?php echo esc_html($dkpi_toggle_text); ?>
                        </button>
                        <?php $nonce_action_delete = 'oo_delete_derived_kpi_' . $dkpi->derived_definition_id; ?>
                        | <a href="#" 
                           class="oo-delete-derived-kpi-stream" 
                           data-derived-kpi-id="<?php echo esc_attr( $dkpi->derived_definition_id ); ?>" 
                           data-nonce-action="<?php echo esc_attr($nonce_action_delete); ?>"
                           style="color:#b32d2e; text-decoration: none;"><?php esc_html_e( 'Delete', 'operations-organizer' ); ?></a>
                    </td>
                </tr>
            <?php endforeach;
        else :
            ?>
            <tr><td colspan="6"><?php esc_html_e('No Derived KPI definitions found relevant to this stream, or their primary KPIs are not linked to any phase in this stream.', 'operations-organizer'); ?></td></tr>
            <?php
        endif;
        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html ) );
    }

    public static function ajax_add_phase_from_stream() {
        check_ajax_referer('oo_add_phase_nonce', 'oo_add_phase_nonce'); 

        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }

        $stream_id = isset( $_POST['stream_type_id'] ) ? intval( $_POST['stream_type_id'] ) : 0;
        $phase_name = isset( $_POST['phase_name'] ) ? sanitize_text_field( trim($_POST['phase_name']) ) : '';
        $phase_slug = isset( $_POST['phase_slug'] ) ? sanitize_title( trim($_POST['phase_slug']) ) : '';
        $phase_description = isset( $_POST['phase_description'] ) ? sanitize_textarea_field( trim($_POST['phase_description']) ) : '';
        $sort_order = isset( $_POST['sort_order'] ) ? intval( $_POST['sort_order'] ) : 0;
        $includes_kpi = isset( $_POST['includes_kpi'] ) ? 1 : 0;

        if ( empty($stream_id) || empty($phase_name) ) {
            wp_send_json_error( array( 'message' => 'Error: Stream and Phase Name are required.' ) ); return;
        }

        $result = OO_DB::add_phase( $stream_id, $phase_name, $phase_slug, $phase_description, $sort_order, null, null, 1, $includes_kpi );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            wp_send_json_success( array( 'message' => 'Phase added successfully.', 'phase_id' => $result ) );
        }
    }

    public static function ajax_get_phase_for_stream_modal() {
        check_ajax_referer('oo_edit_phase_nonce', '_ajax_nonce_get_phase'); 
        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }
        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;
        if ( $phase_id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid phase ID.' ) ); return;
        }
        $phase = OO_DB::get_phase( $phase_id );
        if ( $phase ) {
            wp_send_json_success( $phase );
        } else {
            wp_send_json_error( array( 'message' => 'Phase not found.' ) );
        }
    }

    public static function ajax_update_phase_from_stream() {
        check_ajax_referer('oo_edit_phase_nonce', 'oo_edit_phase_nonce');
        if ( ! current_user_can( oo_get_capability() ) ) { 
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }
        $phase_id = isset( $_POST['edit_phase_id'] ) ? intval( $_POST['edit_phase_id'] ) : 0;
        $stream_id = isset( $_POST['edit_stream_type_id'] ) ? intval( $_POST['edit_stream_type_id'] ) : 0;
        $phase_name = isset( $_POST['edit_phase_name'] ) ? sanitize_text_field( trim($_POST['edit_phase_name']) ) : '';
        $phase_slug = isset( $_POST['edit_phase_slug'] ) ? sanitize_title( trim($_POST['edit_phase_slug']) ) : '';
        $phase_description = isset( $_POST['edit_phase_description'] ) ? sanitize_textarea_field( trim($_POST['edit_phase_description']) ) : '';
        $sort_order = isset( $_POST['edit_sort_order'] ) ? intval( $_POST['edit_sort_order'] ) : null;
        $includes_kpi = isset( $_POST['edit_includes_kpi'] ) ? 1 : 0;
        
        if ( $phase_id <= 0 || empty($stream_id) || empty($phase_name) ) {
            wp_send_json_error( array( 'message' => 'Error: Phase ID, Stream and Name are required.' ) ); return;
        }
        
        $result = OO_DB::update_phase( $phase_id, $stream_id, $phase_name, $phase_slug, $phase_description, $sort_order, null, null, null, $includes_kpi );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            $return_to_stream_slug = isset($_POST['return_to_stream']) ? sanitize_key($_POST['return_to_stream']) : '';
            $return_sub_tab = isset($_POST['return_sub_tab']) ? sanitize_key($_POST['return_sub_tab']) : '';
            $redirect_url = admin_url('admin.php?page=oo_phases');

            if (!empty($return_to_stream_slug)) {
                $stream_configs = OO_Admin_Pages::get_stream_page_configs_for_redirect();
                $found_stream_page_slug = '';
                foreach ($stream_configs as $s_id => $config) {
                    if (isset($config['tab_slug']) && $config['tab_slug'] === $return_to_stream_slug) {
                        $found_stream_page_slug = $config['slug'];
                        break;
                    }
                }
                
                if (!empty($found_stream_page_slug)) {
                     $redirect_url = admin_url('admin.php?page=' . $found_stream_page_slug);
                     if (!empty($return_sub_tab)) {
                         $redirect_url = add_query_arg('sub_tab', $return_sub_tab, $redirect_url);
                     }
                }
            }
            $redirect_url = add_query_arg(array('message' => 'phase_updated'), $redirect_url);
            wp_send_json_success( array( 'message' => 'Phase updated successfully.', 'redirect_url' => $redirect_url ) );
        }
    }

    public static function ajax_toggle_phase_status_from_stream() {
        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;
        if ( $phase_id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid phase ID.' ) ); return;
        }
        check_ajax_referer( 'oo_toggle_phase_status_nonce_' . $phase_id, '_ajax_nonce' );
        if ( ! current_user_can( oo_get_capability() ) ) { 
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }
        $new_status = isset( $_POST['is_active'] ) ? intval( $_POST['is_active'] ) : 0;
        $result = OO_DB::toggle_phase_status( $phase_id, $new_status );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            // ... (redirect logic from class-oo-phase.php) ...
            wp_send_json_success( array( 'message' => 'Phase status updated.', 'new_status' => $new_status ) );
        }
    }

    public static function ajax_delete_phase_from_stream() {
        check_ajax_referer( 'oo_delete_phase_ajax_nonce', '_ajax_nonce' );
        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
            return;
        }
        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;
        $force_delete_logs = isset( $_POST['force_delete_logs'] ) && $_POST['force_delete_logs'] === 'true';
        if ( $phase_id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid Phase ID.' ) );
            return;
        }
        $job_logs_count = OO_DB::get_job_logs_count(array('phase_id' => $phase_id)); 
        if ( $job_logs_count > 0 && !$force_delete_logs ) {
            wp_send_json_success( array( 
                'message' => sprintf('This phase is associated with %d job log(s). Are you sure you want to delete this phase AND all its associated job logs?', $job_logs_count),
                'confirmation_needed' => true,
                'usage_count' => $job_logs_count
            ) );
            return;
        }
        if ($job_logs_count > 0 && $force_delete_logs) {
            OO_DB::delete_job_logs_for_phase($phase_id);
        }
        OO_DB::delete_phase_kpi_links_for_phase($phase_id);
        $result = OO_DB::delete_phase( $phase_id );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            wp_send_json_success( array( 'message' => 'Phase and associated data deleted successfully.' ) );
        }
    }

	public static function ajax_get_phase_kpi_links_for_stream() {
        check_ajax_referer('oo_get_phase_kpi_links_nonce', '_ajax_nonce');
        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_send_json_error( ['message' => 'Permission denied.'], 403 );
            return;
        }
        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;
        if ( $phase_id <= 0 ) {
            wp_send_json_error( ['message' => 'Invalid Phase ID.'] );
            return;
        }
        $kpi_links_raw = OO_DB::get_phase_kpi_links_for_phase( $phase_id, true );
        if ( is_wp_error( $kpi_links_raw ) ) {
            wp_send_json_error( ['message' => $kpi_links_raw->get_error_message()] );
            return;
        }
        wp_send_json_success( $kpi_links_raw );
	}

    public static function ajax_add_phase_kpi_link_from_stream() {
        check_ajax_referer('oo_manage_phase_kpi_links_nonce', '_ajax_nonce');
        // ... (logic from class-oo-phase) ...
    }
    
    // ... (other handlers copied from class-oo-phase.php) ...

    public static function ajax_get_stream_job_logs() {
        check_ajax_referer('oo_dashboard_nonce', 'nonce');

        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 25;
        if ($length === -1) { $length = 999999; }
        
        // Filters from the stream dashboard tab
        $filter_employee_id = isset($_POST['filter_employee_id']) && !empty($_POST['filter_employee_id']) ? intval($_POST['filter_employee_id']) : null;
        $filter_job_number = isset($_POST['filter_job_number']) && !empty($_POST['filter_job_number']) ? sanitize_text_field($_POST['filter_job_number']) : null;
        $filter_phase_id = isset($_POST['filter_phase_id']) && !empty($_POST['filter_phase_id']) ? intval($_POST['filter_phase_id']) : null;
        $filter_stream_id = isset($_POST['filter_stream_id']) && !empty($_POST['filter_stream_id']) ? intval($_POST['filter_stream_id']) : null;
        $filter_date_from = isset($_POST['filter_date_from']) && !empty($_POST['filter_date_from']) ? sanitize_text_field($_POST['filter_date_from']) : null;
        $filter_date_to = isset($_POST['filter_date_to']) && !empty($_POST['filter_date_to']) ? sanitize_text_field($_POST['filter_date_to']) : null;
        $filter_status = isset($_POST['filter_status']) && !empty($_POST['filter_status']) ? sanitize_text_field($_POST['filter_status']) : null;
        $selected_columns_config = isset($_POST['selected_columns_config']) && is_array($_POST['selected_columns_config']) ? $_POST['selected_columns_config'] : array();

        $args = array(
            'number'         => $length,
            'offset'         => $start,
            'orderby'        => 'jl.start_time', // Simplified for stream dashboard context
            'order'          => 'desc',
            'employee_id'    => $filter_employee_id,
            'job_number'     => $filter_job_number,
            'phase_id'       => $filter_phase_id,
            'stream_id'      => $filter_stream_id,
            'date_from'      => $filter_date_from,
            'date_to'        => $filter_date_to,
            'status'         => $filter_status,
        );

        $logs = OO_DB::get_job_logs($args);
        $count_args = $args;
        unset($count_args['number'], $count_args['offset'], $count_args['orderby'], $count_args['order']);
        $total_filtered_records = OO_DB::get_job_logs_count($count_args);
        $total_records = OO_DB::get_job_logs_count(array('stream_id' => $filter_stream_id));

        $data = array();
        // ... (The rest of the data processing logic from ajax_get_dashboard_data) ...
        
        wp_send_json_success(array(
            'draw'            => $draw,
            'recordsTotal'    => $total_records,
            'recordsFiltered' => $total_filtered_records,
            'data'            => $data,
        ));
    }
} 