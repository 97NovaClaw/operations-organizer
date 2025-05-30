<?php
// /includes/class-oo-derived-kpi.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Derived_KPI {

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
            'primary_kpi_measure_id'   => intval( $_POST['primary_kpi_measure_id'] ), // Primary KPI ID is part of the form, though not directly editable in the modal shown, it's submitted.
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
        // To toggle status, we just need to update the is_active field.
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
        // $stream_slug = isset($_POST['stream_slug']) ? sanitize_key($_POST['stream_slug']) : ''; // Not used in this HTML generation directly

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
                    $dkpi->primary_kpi_measure_name = $primary_kpi ? $primary_kpi->measure_name : 'Unknown KPI';
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
                    <td><?php echo esc_html( $dkpi->primary_kpi_measure_name ); ?></td>
                    <td><?php echo esc_html( ucfirst( str_replace('_', ' ', $dkpi->calculation_type ) ) ); ?></td>
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
            <tr><td colspan="5"><?php esc_html_e('No Derived KPI definitions found relevant to this stream, or their primary KPIs are not linked to any phase in this stream.', 'operations-organizer'); ?></td></tr>
            <?php
        endif;
        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html ) );
    }
}

?> 