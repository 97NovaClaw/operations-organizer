<?php
// /admin/views/dashboard-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
// Data for this page ($employees, $phases) is prepared in OO_Dashboard::display_dashboard_page()
// and passed via global variables.
global $employees, $phases, $streams; 

// Get active tab - default to 'overview'
$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
?>
<div class="wrap oo-dashboard-page">
    <h1><?php esc_html_e( 'Operations Dashboard', 'operations-organizer' ); ?></h1>

    <h2 class="nav-tab-wrapper">
        <a href="?page=oo_dashboard&tab=overview" class="nav-tab <?php echo $active_tab == 'overview' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Overview', 'operations-organizer'); ?></a>
        <a href="?page=oo_dashboard&tab=soft-content" class="nav-tab <?php echo $active_tab == 'soft-content' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Soft Content', 'operations-organizer'); ?></a>
        <a href="?page=oo_dashboard&tab=electronics" class="nav-tab <?php echo $active_tab == 'electronics' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Electronics', 'operations-organizer'); ?></a>
        <a href="?page=oo_dashboard&tab=art" class="nav-tab <?php echo $active_tab == 'art' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Art', 'operations-organizer'); ?></a>
        <a href="?page=oo_dashboard&tab=content" class="nav-tab <?php echo $active_tab == 'content' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Content', 'operations-organizer'); ?></a>
    </h2>

    <?php
    // Display the appropriate tab content
    switch ($active_tab) {
        case 'overview':
            include_once OO_PLUGIN_DIR . 'admin/views/dashboard-tabs/overview-tab.php';
            break;
        case 'soft-content':
            include_once OO_PLUGIN_DIR . 'admin/views/dashboard-tabs/soft-content-tab.php';
            break;
        case 'electronics':
            include_once OO_PLUGIN_DIR . 'admin/views/dashboard-tabs/electronics-tab.php';
            break;
        case 'art':
            include_once OO_PLUGIN_DIR . 'admin/views/dashboard-tabs/art-tab.php';
            break;
        case 'content':
            include_once OO_PLUGIN_DIR . 'admin/views/dashboard-tabs/content-tab.php';
            break;
        default:
            include_once OO_PLUGIN_DIR . 'admin/views/dashboard-tabs/overview-tab.php';
            break;
    }
    ?>
</div>

<!-- Edit Job Log Modal -->
<div id="ooEditLogModal" class="oo-modal" style="display:none;">
    <div class="oo-modal-content">
        <span class="oo-close-button">&times;</span>
        <h2><?php esc_html_e( 'Edit Job Log Entry', 'operations-organizer' ); ?></h2>
        <form id="oo-edit-log-form">
            <?php wp_nonce_field( 'oo_edit_log_nonce', 'oo_edit_log_nonce_field' ); ?>
            <input type="hidden" id="edit_log_id" name="edit_log_id" value="" />
            <table class="form-table oo-form-table">
                <tr valign="top">
                    <th scope="row"><label for="edit_log_employee_id"><?php esc_html_e( 'Employee', 'operations-organizer' ); ?></label></th>
                    <td>
                        <select id="edit_log_employee_id" name="edit_log_employee_id" required>
                            <option value=""><?php esc_html_e('-- Select Employee --', 'operations-organizer'); ?></option>
                            <?php 
                            // Use the already available $GLOBALS['employees'] if populated, otherwise fetch them.
                            // This assumes $GLOBALS['employees'] has active employees for the dashboard filters.
                            $modal_employees = isset($GLOBALS['employees']) ? $GLOBALS['employees'] : oo_get_active_employees_for_select();
                            if (!empty($modal_employees)) {
                                foreach ( $modal_employees as $emp_obj_or_arr ) {
                                    // Handle if $emp_obj_or_arr is object from DB or array from oo_get_active_employees_for_select
                                    $emp_id = is_object($emp_obj_or_arr) ? $emp_obj_or_arr->employee_id : $emp_obj_or_arr['id'];
                                    $emp_name = is_object($emp_obj_or_arr) ? esc_html( $emp_obj_or_arr->first_name . ' ' . $emp_obj_or_arr->last_name . ' (' . $emp_obj_or_arr->employee_number . ')' ) : $emp_obj_or_arr['name'];
                                    echo '<option value="' . esc_attr( $emp_id ) . '">' . $emp_name . '</option>';
                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_job_number"><?php esc_html_e( 'Job Number', 'operations-organizer' ); ?></label></th>
                    <td><input type="text" id="edit_log_job_number" name="edit_log_job_number" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_phase_id"><?php esc_html_e( 'Phase', 'operations-organizer' ); ?></label></th>
                    <td>
                        <select id="edit_log_phase_id" name="edit_log_phase_id" required>
                            <option value=""><?php esc_html_e('-- Select Phase --', 'operations-organizer'); ?></option>
                            <?php 
                            $modal_phases = isset($GLOBALS['phases']) && is_array($GLOBALS['phases']) ? $GLOBALS['phases'] : oo_get_active_phases_for_select();
                            if (!empty($modal_phases)) {
                                foreach ( $modal_phases as $phase_obj_or_arr ) {
                                    $phase_item_id = is_object($phase_obj_or_arr) ? $phase_obj_or_arr->phase_id : $phase_obj_or_arr['id'];
                                    $phase_item_name = is_object($phase_obj_or_arr) ? esc_html($phase_obj_or_arr->phase_name) : $phase_obj_or_arr['name'];
                                    echo '<option value="' . esc_attr( $phase_item_id ) . '">' . $phase_item_name . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_start_time"><?php esc_html_e( 'Start Time', 'operations-organizer' ); ?></label></th>
                    <td><input type="datetime-local" id="edit_log_start_time" name="edit_log_start_time" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_end_time"><?php esc_html_e( 'End Time', 'operations-organizer' ); ?></label></th>
                    <td><input type="datetime-local" id="edit_log_end_time" name="edit_log_end_time" /></td>
                </tr>
                 <tr valign="top">
                    <th scope="row"><label for="edit_log_status"><?php esc_html_e( 'Status', 'operations-organizer' ); ?></label></th>
                    <td>
                        <select id="edit_log_status" name="edit_log_status">
                            <option value="started"><?php esc_html_e('Started', 'operations-organizer'); ?></option>
                            <option value="completed"><?php esc_html_e('Completed', 'operations-organizer'); ?></option>
                            <!-- Add other statuses if implemented, e.g., Paused -->
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_notes"><?php esc_html_e( 'Notes', 'operations-organizer' ); ?></label></th>
                    <td><textarea id="edit_log_notes" name="edit_log_notes" rows="3"></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_kpi_data"><?php esc_html_e( 'KPI Data (JSON)', 'operations-organizer' ); ?></label></th>
                    <td><textarea id="edit_log_kpi_data" name="edit_log_kpi_data" rows="4" class="widefat"></textarea><p class="description"><?php esc_html_e('Enter additional KPIs as a valid JSON string. E.g. {"bags":5, "weight_kg":22.5}','operations-organizer');?></p></td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Log Changes', 'operations-organizer' ), 'primary', 'submit_edit_log' ); ?>
        </form>
    </div>
</div> 