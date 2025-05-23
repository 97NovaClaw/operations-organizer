<?php
// admin/views/kpi-measure-management-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure OO_DB class is available
if ( ! class_exists( 'OO_DB' ) ) {
    echo '<div class="error"><p>Operations Organizer: OO_DB class not found. Please ensure the plugin is installed correctly.</p></div>';
    return;
}

// Handle form submissions for adding/editing KPI measures
$action_message = '';
if ( isset( $_POST['oo_action'] ) && check_admin_referer( 'oo_manage_kpi_measures_nonce' ) ) {
    if ( $_POST['oo_action'] === 'add_kpi_measure' ) {
        $args = array(
            'measure_name' => sanitize_text_field( $_POST['measure_name'] ),
            'measure_key' => sanitize_key( $_POST['measure_key'] ),
            'unit_type' => sanitize_text_field( $_POST['unit_type'] ),
            'is_active' => isset( $_POST['is_active'] ) ? 1 : 0,
        );
        $result = OO_DB::add_kpi_measure( $args );
        if ( is_wp_error( $result ) ) {
            $action_message = '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
        } else {
            $action_message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'KPI Measure added successfully.', 'operations-organizer' ) . '</p></div>';
        }
    } elseif ( $_POST['oo_action'] === 'edit_kpi_measure' && isset( $_POST['kpi_measure_id'] ) ) {
        $kpi_measure_id = intval( $_POST['kpi_measure_id'] );
        $args = array(
            'measure_name' => sanitize_text_field( $_POST['measure_name'] ),
            'measure_key' => sanitize_key( $_POST['measure_key'] ),
            'unit_type' => sanitize_text_field( $_POST['unit_type'] ),
            'is_active' => isset( $_POST['is_active'] ) ? 1 : 0,
        );
        $result = OO_DB::update_kpi_measure( $kpi_measure_id, $args );
        if ( is_wp_error( $result ) ) {
            $action_message = '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
        } else {
            $action_message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'KPI Measure updated successfully.', 'operations-organizer' ) . '</p></div>';
        }
    }
}

// Handle delete action
if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_kpi_measure' && isset( $_GET['kpi_measure_id'] ) && check_admin_referer( 'oo_delete_kpi_measure_nonce_' . $_GET['kpi_measure_id'] ) ) {
    $kpi_measure_id = intval( $_GET['kpi_measure_id'] );
    $result = OO_DB::delete_kpi_measure( $kpi_measure_id );
    if ( is_wp_error( $result ) ) {
        $action_message = '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
    } else {
        $action_message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'KPI Measure deleted successfully.', 'operations-organizer' ) . '</p></div>';
    }
}

// Handle toggle status action
if ( isset( $_GET['action'] ) && $_GET['action'] === 'toggle_kpi_measure_status' && isset( $_GET['kpi_measure_id'] ) && check_admin_referer( 'oo_toggle_kpi_measure_status_nonce_' . $_GET['kpi_measure_id'] ) ) {
    $kpi_measure_id = intval( $_GET['kpi_measure_id'] );
    $measure = OO_DB::get_kpi_measure( $kpi_measure_id );
    if ( $measure ) {
        $new_status = $measure->is_active ? 0 : 1;
        OO_DB::toggle_kpi_measure_status( $kpi_measure_id, $new_status );
        $action_message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'KPI Measure status updated.', 'operations-organizer' ) . '</p></div>';
    }
}


$edit_measure = null;
if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit_kpi_measure' && isset( $_GET['kpi_measure_id'] ) ) {
    $edit_measure_id = intval( $_GET['kpi_measure_id'] );
    $edit_measure = OO_DB::get_kpi_measure( $edit_measure_id );
}

$kpi_measures = OO_DB::get_kpi_measures();
$unit_types = array( 'integer', 'decimal', 'text', 'boolean' ); // Define available unit types

?>
<div class="wrap oo-wrap">
    <h1><?php esc_html_e( 'KPI Measure Management', 'operations-organizer' ); ?></h1>

    <?php echo $action_message; // Display any action messages ?>

    <div id="col-container" class="wp-clearfix">
        <div id="col-left">
            <div class="col-wrap">
                <h2><?php echo $edit_measure ? esc_html__( 'Edit KPI Measure', 'operations-organizer' ) : esc_html__( 'Add New KPI Measure', 'operations-organizer' ); ?></h2>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=oo_kpi_measures' ) ); ?>">
                    <?php wp_nonce_field( 'oo_manage_kpi_measures_nonce' ); ?>
                    <input type="hidden" name="oo_action" value="<?php echo $edit_measure ? 'edit_kpi_measure' : 'add_kpi_measure'; ?>">
                    <?php if ( $edit_measure ) : ?>
                        <input type="hidden" name="kpi_measure_id" value="<?php echo esc_attr( $edit_measure->kpi_measure_id ); ?>">
                    <?php endif; ?>

                    <div class="form-field">
                        <label for="measure_name"><?php esc_html_e( 'Measure Name', 'operations-organizer' ); ?></label>
                        <input type="text" name="measure_name" id="measure_name" value="<?php echo $edit_measure ? esc_attr( $edit_measure->measure_name ) : ''; ?>" required>
                        <p><?php esc_html_e( 'The human-readable name for this KPI (e.g., "Boxes Packed", "Items Scanned").', 'operations-organizer' ); ?></p>
                    </div>

                    <div class="form-field">
                        <label for="measure_key"><?php esc_html_e( 'Measure Key', 'operations-organizer' ); ?></label>
                        <input type="text" name="measure_key" id="measure_key" value="<?php echo $edit_measure ? esc_attr( $edit_measure->measure_key ) : ''; ?>" <?php echo $edit_measure ? 'readonly' : ''; ?> required>
                        <p><?php esc_html_e( 'A unique key for this KPI, used internally (e.g., "boxes_packed", "items_scanned"). Lowercase, underscores, no spaces. Cannot be changed after creation.', 'operations-organizer' ); ?></p>
                    </div>

                    <div class="form-field">
                        <label for="unit_type"><?php esc_html_e( 'Unit Type', 'operations-organizer' ); ?></label>
                        <select name="unit_type" id="unit_type">
                            <?php foreach ( $unit_types as $type ) : ?>
                                <option value="<?php echo esc_attr( $type ); ?>" <?php selected( $edit_measure ? $edit_measure->unit_type : 'integer', $type ); ?>>
                                    <?php echo esc_html( ucfirst( $type ) ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p><?php esc_html_e( 'The type of data this KPI represents (e.g., Integer for counts, Decimal for amounts, Text for notes).', 'operations-organizer' ); ?></p>
                    </div>
                    
                    <div class="form-field">
                        <label for="is_active">
                            <input type="checkbox" name="is_active" id="is_active" value="1" <?php checked( $edit_measure ? $edit_measure->is_active : 1 ); ?>>
                            <?php esc_html_e( 'Active', 'operations-organizer' ); ?>
                        </label>
                        <p><?php esc_html_e( 'Inactive measures will not be available for new phase assignments.', 'operations-organizer' ); ?></p>
                    </div>

                    <?php if ( $edit_measure ) : ?>
                        <?php submit_button( __( 'Update KPI Measure', 'operations-organizer' ) ); ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=oo_kpi_measures' ) ); ?>" class="button"><?php esc_html_e( 'Cancel Edit', 'operations-organizer' ); ?></a>
                    <?php else : ?>
                        <?php submit_button( __( 'Add KPI Measure', 'operations-organizer' ) ); ?>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div id="col-right">
            <div class="col-wrap">
                <h2><?php esc_html_e( 'Existing KPI Measures', 'operations-organizer' ); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e( 'Name', 'operations-organizer' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Key', 'operations-organizer' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Unit Type', 'operations-organizer' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Status', 'operations-organizer' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Actions', 'operations-organizer' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $kpi_measures ) ) : ?>
                            <?php foreach ( $kpi_measures as $measure ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $measure->measure_name ); ?></td>
                                    <td><code><?php echo esc_html( $measure->measure_key ); ?></code></td>
                                    <td><?php echo esc_html( ucfirst( $measure->unit_type ) ); ?></td>
                                    <td>
                                        <?php 
                                        $status_text = $measure->is_active ? __( 'Active', 'operations-organizer' ) : __( 'Inactive', 'operations-organizer' );
                                        $toggle_action_url = wp_nonce_url(
                                            admin_url( 'admin.php?page=oo_kpi_measures&action=toggle_kpi_measure_status&kpi_measure_id=' . $measure->kpi_measure_id ),
                                            'oo_toggle_kpi_measure_status_nonce_' . $measure->kpi_measure_id
                                        );
                                        echo '<a href="' . esc_url( $toggle_action_url ) . '">' . esc_html( $status_text ) . '</a>';
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=oo_kpi_measures&action=edit_kpi_measure&kpi_measure_id=' . $measure->kpi_measure_id ) ); ?>"><?php esc_html_e( 'Edit', 'operations-organizer' ); ?></a> | 
                                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=oo_kpi_measures&action=delete_kpi_measure&kpi_measure_id=' . $measure->kpi_measure_id ), 'oo_delete_kpi_measure_nonce_' . $measure->kpi_measure_id ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this KPI measure? This action cannot be undone.', 'operations-organizer' ); ?>');" style="color:red;"><?php esc_html_e( 'Delete', 'operations-organizer' ); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5"><?php esc_html_e( 'No KPI measures found.', 'operations-organizer' ); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> 