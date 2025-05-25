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

// Handle form submissions for adding/editing/deleting DERIVED KPI definitions
if ( isset( $_POST['oo_action'] ) && isset($_POST['oo_derived_kpi_nonce']) && check_admin_referer( 'oo_manage_derived_kpi_nonce', 'oo_derived_kpi_nonce' ) ) {
    if ( $_POST['oo_action'] === 'add_derived_kpi' || $_POST['oo_action'] === 'edit_derived_kpi' ) {
        $primary_kpi_id = isset($_POST['primary_kpi_measure_id']) ? intval($_POST['primary_kpi_measure_id']) : 0;
        $derived_args = array(
            'definition_name'          => sanitize_text_field( $_POST['derived_definition_name'] ),
            'primary_kpi_measure_id'   => $primary_kpi_id,
            'calculation_type'         => sanitize_text_field( $_POST['derived_calculation_type'] ),
            'secondary_kpi_measure_id' => isset( $_POST['derived_secondary_kpi_measure_id'] ) && !empty($_POST['derived_secondary_kpi_measure_id']) ? intval( $_POST['derived_secondary_kpi_measure_id'] ) : null,
            'time_unit_for_rate'       => isset( $_POST['derived_time_unit_for_rate'] ) && !empty($_POST['derived_time_unit_for_rate']) ? sanitize_text_field( $_POST['derived_time_unit_for_rate'] ) : null,
            'output_description'       => sanitize_textarea_field( $_POST['derived_output_description'] ),
            'is_active'                => isset( $_POST['derived_is_active'] ) ? 1 : 0,
        );

        if ( $_POST['oo_action'] === 'add_derived_kpi' ) {
            $result = OO_DB::add_derived_kpi_definition( $derived_args );
            $message_verb = __( 'added', 'operations-organizer' );
        } else {
            $derived_definition_id = isset($_POST['derived_definition_id']) ? intval($_POST['derived_definition_id']) : 0;
            if ($derived_definition_id > 0) {
                $result = OO_DB::update_derived_kpi_definition( $derived_definition_id, $derived_args );
                $message_verb = __( 'updated', 'operations-organizer' );
            } else {
                $result = new WP_Error('missing_id', __( 'Derived calculation ID missing for update.', 'operations-organizer' ) );
            }
        }

        if ( is_wp_error( $result ) ) {
            $action_message = '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
        } else {
            $action_message = '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__( 'Derived KPI calculation %s successfully.', 'operations-organizer' ), $message_verb) . '</p></div>';
        }
        // To stay on the edit page of the primary KPI after action
        if ($primary_kpi_id > 0) {
            $_GET['action'] = 'edit_kpi_measure';
            $_GET['kpi_measure_id'] = $primary_kpi_id;
        }
    }
}

// Handle delete action for DERIVED KPI
if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_derived_kpi' && isset( $_GET['derived_definition_id'] ) ) {
    $derived_definition_id = intval( $_GET['derived_definition_id'] );
    $primary_kpi_id_redirect = isset($_GET['primary_kpi_id']) ? intval($_GET['primary_kpi_id']) : 0;
    if ( check_admin_referer( 'oo_delete_derived_kpi_nonce_' . $derived_definition_id ) ) {
        $result = OO_DB::delete_derived_kpi_definition( $derived_definition_id );
        if ( is_wp_error( $result ) ) {
            $action_message = '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
        } else {
            $action_message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Derived KPI calculation deleted successfully.', 'operations-organizer' ) . '</p></div>';
        }
        // To stay on the edit page of the primary KPI after action
        if ($primary_kpi_id_redirect > 0) {
             wp_redirect(admin_url('admin.php?page=oo_kpi_measures&action=edit_kpi_measure&kpi_measure_id=' . $primary_kpi_id_redirect . '&message=derived_deleted'));
             exit;
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

                <?php if ( $edit_measure ) : ?>
                <div class="derived-kpi-section" style="margin-top: 30px;">
                    <h3><?php printf( esc_html__( 'Derived Calculations for "%s"', 'operations-organizer' ), esc_html($edit_measure->measure_name) ); ?></h3>
                    <?php
                    $derived_definitions = OO_DB::get_derived_kpi_definitions( array( 'primary_kpi_measure_id' => $edit_measure->kpi_measure_id ) );
                    // Note: The get_derived_kpi_definitions in OO_DB needs to be updated to accept 'primary_kpi_measure_id' as a filter.
                    // For now, we'll assume it returns all and filter here, or this part needs the OO_DB method updated first.
                    // Placeholder: $derived_definitions = array_filter($all_derived_definitions, function($def) use ($edit_measure) { return $def->primary_kpi_measure_id == $edit_measure->kpi_measure_id; });
                    ?>
                    <table class="wp-list-table widefat striped" style="margin-bottom: 10px;">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Derived Name', 'operations-organizer' ); ?></th>
                                <th><?php esc_html_e( 'Calculation Type', 'operations-organizer' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'operations-organizer' ); ?></th>
                                <th><?php esc_html_e( 'Actions', 'operations-organizer' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $derived_definitions ) ) : ?>
                                <?php foreach ( $derived_definitions as $derived_def ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $derived_def->definition_name ); ?></td>
                                        <td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $derived_def->calculation_type ) ) ); ?></td>
                                        <td><?php echo $derived_def->is_active ? esc_html__('Active', 'operations-organizer') : esc_html__('Inactive', 'operations-organizer'); ?></td>
                                        <td>
                                            <a href="#" class="edit-derived-kpi-trigger" data-definition-id="<?php echo esc_attr($derived_def->derived_definition_id); ?>"><?php esc_html_e( 'Edit', 'operations-organizer' ); ?></a> |
                                            <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=oo_kpi_measures&action=delete_derived_kpi&derived_definition_id=' . $derived_def->derived_definition_id . '&primary_kpi_id=' . $edit_measure->kpi_measure_id ), 'oo_delete_derived_kpi_nonce_' . $derived_def->derived_definition_id ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this derived calculation?', 'operations-organizer' ); ?>');" style="color:red;"><?php esc_html_e( 'Delete', 'operations-organizer' ); ?></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr><td colspan="4"><?php esc_html_e( 'No derived calculations defined for this KPI yet.', 'operations-organizer' ); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button type="button" class="button" id="add-new-derived-kpi-trigger" data-primary-kpi-id="<?php echo esc_attr($edit_measure->kpi_measure_id); ?>" data-primary-kpi-name="<?php echo esc_attr($edit_measure->measure_name); ?>" data-primary-kpi-unit-type="<?php echo esc_attr($edit_measure->unit_type); ?>"><?php esc_html_e( 'Add New Derived Calculation', 'operations-organizer' ); ?></button>
                </div>
                <?php endif; // End $edit_measure check for derived KPIs ?>

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

<!-- Modal for Adding/Editing Derived KPI Definition -->
<div id="derived-kpi-modal" class="oo-modal" style="display:none;">
    <div class="oo-modal-content">
        <span class="oo-modal-close">&times;</span>
        <h2 id="derived-kpi-modal-title"><?php esc_html_e( 'Add Derived Calculation', 'operations-organizer' ); ?></h2>
        <form id="derived-kpi-form" method="post">
            <?php wp_nonce_field( 'oo_manage_derived_kpi_nonce', 'oo_derived_kpi_nonce' ); ?>
            <input type="hidden" name="oo_action" id="derived_kpi_oo_action" value="add_derived_kpi">
            <input type="hidden" name="derived_definition_id" id="derived_definition_id" value="">
            <input type="hidden" name="primary_kpi_measure_id" id="modal_primary_kpi_measure_id" value="">
            <input type="hidden" id="modal_primary_kpi_unit_type" value=""> <!-- Used by JS -->

            <div class="form-field">
                <label><?php esc_html_e( 'Primary KPI:', 'operations-organizer' ); ?></label>
                <span id="modal_primary_kpi_name_display"></span>
            </div>

            <div class="form-field">
                <label for="derived_definition_name"><?php esc_html_e( 'Derived Calculation Name', 'operations-organizer' ); ?></label>
                <input type="text" name="derived_definition_name" id="derived_definition_name" required>
                <p><?php esc_html_e( 'A descriptive name (e.g., "Items per Hour", "Cost per Item").', 'operations-organizer' ); ?></p>
            </div>

            <div class="form-field">
                <label for="derived_calculation_type"><?php esc_html_e( 'Calculation Type', 'operations-organizer' ); ?></label>
                <select name="derived_calculation_type" id="derived_calculation_type">
                    <!-- Options will be populated by JavaScript based on primary KPI unit type -->
                </select>
            </div>

            <div class="form-field" id="derived_secondary_kpi_field" style="display:none;">
                <label for="derived_secondary_kpi_measure_id"><?php esc_html_e( 'Secondary KPI (for Ratio)', 'operations-organizer' ); ?></label>
                <select name="derived_secondary_kpi_measure_id" id="derived_secondary_kpi_measure_id">
                    <option value=""><?php esc_html_e( '-- Select Secondary KPI --', 'operations-organizer' ); ?></option>
                    <?php
                    // $all_active_kpis = OO_DB::get_kpi_measures(array('is_active' => 1));
                    // if ($all_active_kpis) {
                    //     foreach ($all_active_kpis as $kpi) {
                    //         // Exclude the primary KPI itself if $edit_measure is set
                    //         if ($edit_measure && $kpi->kpi_measure_id == $edit_measure->kpi_measure_id) continue;
                    //         echo '<option value="' . esc_attr($kpi->kpi_measure_id) . '">' . esc_html($kpi->measure_name) . ' (' . esc_html($kpi->unit_type) . ')</option>';
                    //     }
                    // }
                    ?>
                     <!-- Options will be populated by JavaScript, excluding the primary KPI -->
                </select>
            </div>

            <div class="form-field" id="derived_time_unit_field" style="display:none;">
                <label for="derived_time_unit_for_rate"><?php esc_html_e( 'Time Unit (for Rate)', 'operations-organizer' ); ?></label>
                <select name="derived_time_unit_for_rate" id="derived_time_unit_for_rate">
                    <option value="hour"><?php esc_html_e( 'Hour', 'operations-organizer' ); ?></option>
                    <option value="minute"><?php esc_html_e( 'Minute', 'operations-organizer' ); ?></option>
                    <option value="day"><?php esc_html_e( 'Day', 'operations-organizer' ); ?></option>
                </select>
            </div>
            
            <div class="form-field">
                <label for="derived_output_description"><?php esc_html_e( 'Output Description (Optional)', 'operations-organizer' ); ?></label>
                <textarea name="derived_output_description" id="derived_output_description" rows="2"></textarea>
                <p><?php esc_html_e( 'Briefly describe what this calculation represents or its expected unit (e.g., "items/hr", "$/item").', 'operations-organizer' ); ?></p>
            </div>

             <div class="form-field">
                <label for="derived_is_active">
                    <input type="checkbox" name="derived_is_active" id="derived_is_active" value="1" checked>
                    <?php esc_html_e( 'Active', 'operations-organizer' ); ?>
                </label>
            </div>

            <?php submit_button( __( 'Save Derived Calculation', 'operations-organizer' ), 'primary', 'save_derived_kpi_submit' ); ?>
        </form>
    </div>
</div> 