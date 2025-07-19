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
        <!-- Overview Tab (Always Present) -->
        <a href="?page=oo_dashboard&tab=overview" class="nav-tab <?php echo $active_tab == 'overview' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Overview', 'operations-organizer'); ?></a>
        
        <!-- Dynamic Stream Tabs -->
        <?php if (!empty($streams)) : ?>
            <?php foreach ($streams as $stream) : ?>
                <?php 
                $stream_tab_slug = sanitize_key(strtolower(str_replace(' ', '-', $stream->stream_name))); 
                $is_active = ($active_tab == $stream_tab_slug) ? 'nav-tab-active' : '';
                ?>
                <a href="?page=oo_dashboard&tab=<?php echo esc_attr($stream_tab_slug); ?>" class="nav-tab <?php echo $is_active; ?>">
                    <?php echo esc_html($stream->stream_name); ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </h2>

    <?php
    // Display the appropriate tab content
    if ($active_tab == 'overview') {
        // Overview tab (special case)
            include_once OO_PLUGIN_DIR . 'admin/views/dashboard-tabs/overview-tab.php';
    } else {
        // Check if this is a dynamic stream tab
        $current_stream = null;
        if (!empty($streams)) {
            foreach ($streams as $stream) {
                $stream_tab_slug = sanitize_key(strtolower(str_replace(' ', '-', $stream->stream_name)));
                if ($active_tab == $stream_tab_slug) {
                    $current_stream = $stream;
            break;
                }
            }
        }
        
        if ($current_stream) {
            // Set global variables for the generic stream tab
            $GLOBALS['current_stream'] = $current_stream;
            $GLOBALS['current_stream_tab_slug'] = $active_tab;
            
            // Check if a specific template exists for backward compatibility
            $legacy_template_map = array(
                'soft-content' => 'soft-content-tab.php',
                'electronics' => 'electronics-tab.php', 
                'art' => 'art-tab.php',
                'content' => 'content-tab.php'
            );
            
            $legacy_template_path = null;
            if (isset($legacy_template_map[$active_tab])) {
                $legacy_template_path = OO_PLUGIN_DIR . 'admin/views/dashboard-tabs/' . $legacy_template_map[$active_tab];
            }
            
            if ($legacy_template_path && file_exists($legacy_template_path)) {
                // Use legacy template for backward compatibility
                include_once $legacy_template_path;
            } else {
                // Use generic stream template
                include_once OO_PLUGIN_DIR . 'admin/views/dashboard-tabs/generic-stream-tab.php';
            }
        } else {
            // Unknown tab, default to overview
            include_once OO_PLUGIN_DIR . 'admin/views/dashboard-tabs/overview-tab.php';
        }
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
                    <th scope="row"><label for="edit_log_boxes_completed"><?php esc_html_e( 'Boxes Completed', 'operations-organizer' ); ?></label></th>
                    <td><input type="number" id="edit_log_boxes_completed" name="edit_log_boxes_completed" min="0" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_items_completed"><?php esc_html_e( 'Items Completed', 'operations-organizer' ); ?></label></th>
                    <td><input type="number" id="edit_log_items_completed" name="edit_log_items_completed" min="0" /></td>
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