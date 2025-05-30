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

        $result = OO_DB::add_kpi_measure( $args );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            wp_send_json_success( array( 'message' => __( 'KPI Measure added successfully.', 'operations-organizer' ), 'kpi_measure_id' => $result ) );
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

        $stream_kpi_measures = OO_DB::get_kpi_measures_for_stream( $stream_id, array('is_active' => null) ); // Get all (active/inactive)

        ob_start();
        if ( ! empty( $stream_kpi_measures ) ) :
            foreach ( $stream_kpi_measures as $kpi_measure ) :
                $row_classes = 'kpi-measure-row-' . esc_attr($kpi_measure->kpi_measure_id);
                $row_classes .= $kpi_measure->is_active ? ' active' : ' inactive';
                ?>
                <tr class="<?php echo $row_classes; ?>">
                    <td><strong><button type="button" class="button-link oo-edit-kpi-measure-stream" data-kpi-measure-id="<?php echo esc_attr( $kpi_measure->kpi_measure_id ); ?>"><?php echo esc_html( $kpi_measure->measure_name ); ?></button></strong></td>
                    <td><code><?php echo esc_html( $kpi_measure->measure_key ); ?></code></td>
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
            <tr><td colspan="5"><?php esc_html_e( 'No KPI measures found specifically linked to phases in this stream yet, or no active KPI Measures defined globally.', 'operations-organizer' ); ?></td></tr>
            <?php
        endif;
        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html ) );
    }
}

?> 