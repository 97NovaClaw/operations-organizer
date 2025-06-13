<?php
// admin/views/stream-pages/single-stream-page-template.php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// These globals would be set by the calling function in OO_Admin_Pages
global $current_stream_id, $current_stream_name, $current_stream_tab_slug, $phases, $employees;

$db = new OO_DB();
// Get data specific to this stream for the modals and lists
$stream_phases_all = $db->get_phases(array('stream_id' => $current_stream_id, 'is_active' => null, 'orderby' => 'order_in_stream'));
$stream_kpi_measures = $db->get_kpi_measures_for_stream($current_stream_id);
$stream_derived_kpis = $db->get_derived_kpi_definitions_for_stream($current_stream_id);
$stream_phases_with_kpi = array_filter($stream_phases_all, function($p) { return !empty($p->includes_kpi) && $p->is_active; });

$active_tab = isset( $_GET['sub_tab'] ) ? sanitize_key( $_GET['sub_tab'] ) : 'phase_log_actions';

?>
<div class="wrap oo-stream-page oo-stream-page-<?php echo esc_attr($current_stream_tab_slug); ?>">
    <h1><?php echo esc_html($current_stream_name); ?> <?php esc_html_e('Stream Management', 'operations-organizer'); ?></h1>

    <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo esc_attr($_REQUEST['page']); ?>&sub_tab=phase_log_actions" class="nav-tab <?php echo $active_tab == 'phase_log_actions' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Phase Log Actions', 'operations-organizer'); ?>
        </a>
        <a href="?page=<?php echo esc_attr($_REQUEST['page']); ?>&sub_tab=phase_dashboard" class="nav-tab <?php echo $active_tab == 'phase_dashboard' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Phase Dashboard', 'operations-organizer'); ?>
        </a>
        <a href="?page=<?php echo esc_attr($_REQUEST['page']); ?>&sub_tab=phase_kpi_settings" class="nav-tab <?php echo $active_tab == 'phase_kpi_settings' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Phase & KPI Settings', 'operations-organizer'); ?>
        </a>
    </h2>

    <div class="oo-stream-tab-content">
        <?php if ( $active_tab == 'phase_log_actions' ) : ?>
            <div id="phase-log-actions-content">
                <div class="oo-dashboard-section">
                    <h3><?php esc_html_e('Quick Phase Actions', 'operations-organizer'); ?></h3>
                    <p><?php esc_html_e('Enter a Job Number and select a phase to start or stop.', 'operations-organizer'); ?></p>
                    
                    <?php if (!empty($stream_phases_with_kpi)): ?>
                        <table class="form-table">
                            <?php foreach ($stream_phases_with_kpi as $phase): ?>
                                <tr valign="top" class="oo-phase-action-row">
                                    <th scope="row" style="width: 200px;"><?php echo esc_html($phase->phase_name); ?></th>
                                    <td>
                                        <input type="text" class="oo-job-number-input" placeholder="<?php esc_attr_e('Enter Job Number', 'operations-organizer'); ?>" style="width: 200px; margin-right: 10px;">
                                        <button class="button button-primary oo-start-link-btn" data-phase-id="<?php echo esc_attr($phase->phase_id); ?>"><?php esc_html_e('Start', 'operations-organizer'); ?></button>
                                        <button class="button oo-stop-link-btn" data-phase-id="<?php echo esc_attr($phase->phase_id); ?>"><?php esc_html_e('Stop', 'operations-organizer'); ?></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p class="oo-notice"><?php printf(esc_html__('No active phases (with KPIs enabled) have been configured for %s.', 'operations-organizer'), esc_html($current_stream_name)); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ( $active_tab == 'phase_dashboard' ) : ?>
            <div id="phase-dashboard-content">
                <div class="oo-dashboard-section" id="stream-job-logs-section">
                    <h4><?php esc_html_e('Detailed Job Logs for Stream', 'operations-organizer'); ?></h4>
                    <div class="oo-filter-section">
                        <div class="filter-row">
                            <div class="filter-item">
                                <label for="content_filter_employee_id"><?php esc_html_e('Employee:', 'operations-organizer');?></label>
                                <select id="content_filter_employee_id" name="content_filter_employee_id">
                                    <option value=""><?php esc_html_e('All Employees', 'operations-organizer');?></option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo esc_attr($employee->employee_id); ?>"><?php echo esc_html($employee->first_name . ' ' . $employee->last_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-item">
                                <label for="content_filter_job_number"><?php esc_html_e('Job Number:', 'operations-organizer');?></label>
                                <input type="text" id="content_filter_job_number" name="content_filter_job_number" placeholder="<?php esc_attr_e('Enter Job No.', 'operations-organizer');?>">
                            </div>
                            <div class="filter-item">
                                <label for="content_filter_phase_id"><?php esc_html_e('Phase:', 'operations-organizer');?></label>
                                <select id="content_filter_phase_id" name="content_filter_phase_id">
                                    <option value=""><?php esc_html_e('All Phases in this Stream', 'operations-organizer');?></option>
                                     <?php if (!empty($stream_phases_with_kpi)): foreach ($stream_phases_with_kpi as $phase): ?>
                                        <option value="<?php echo esc_attr($phase->phase_id); ?>"><?php echo esc_html($phase->phase_name); ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="filter-item">
                                <label for="content_filter_status"><?php esc_html_e('Status:', 'operations-organizer');?></label>
                                <select id="content_filter_status" name="content_filter_status">
                                    <option value=""><?php esc_html_e('All Statuses', 'operations-organizer');?></option>
                                    <option value="started"><?php esc_html_e('Running', 'operations-organizer');?></option>
                                    <option value="completed"><?php esc_html_e('Completed', 'operations-organizer');?></option>
                                </select>
                            </div>
                        </div>
                        <div class="filter-row">
                            <div class="filter-item">
                                <label for="content_filter_date_from"><?php esc_html_e('Date From:', 'operations-organizer');?></label>
                                <input type="text" id="content_filter_date_from" name="content_filter_date_from" class="oo-datepicker" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="filter-item">
                                <label for="content_filter_date_to"><?php esc_html_e('Date To:', 'operations-organizer');?></label>
                                <input type="text" id="content_filter_date_to" name="content_filter_date_to" class="oo-datepicker" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="filter-item oo-filter-buttons">
                                <button id="content_apply_filters_button" class="button button-primary"><?php esc_html_e('Apply Filters', 'operations-organizer');?></button>
                                <button id="content_clear_filters_button" class="button"><?php esc_html_e('Clear Filters', 'operations-organizer');?></button>
                            </div>
                        </div>
                    </div>
                    <div class="oo-kpi-column-selector-section filter-row" style="margin-top: 10px; margin-bottom: 20px; padding: 10px; background-color: #f9f9f9; border: 1px solid #ccd0d4;">
                        <div class="filter-item">
                             <label style="font-weight: bold;"><?php esc_html_e('Select Columns to Display:', 'operations-organizer'); ?></label>
                             <button type="button" id="content_open_kpi_selector_modal" class="button"><?php esc_html_e('Choose Columns', 'operations-organizer'); ?></button>
                             <span id="content_selected_kpi_count" style="margin-left: 10px;"></span>
                             <button type="button" id="save_content_columns_as_default" class="button button-secondary" style="margin-left: 15px; display:none;"><?php esc_html_e('Save as Default', 'operations-organizer'); ?></button>
                             <span id="content_columns_default_saved_msg" style="margin-left: 10px; color: green; font-style: italic; display:none;"><?php esc_html_e('Default saved!', 'operations-organizer'); ?></span>
                        </div>
                    </div>
                    <table id="content-dashboard-table" class="display wp-list-table widefat fixed striped" style="width:100%"></table>
                </div>
            </div>
        <?php elseif ( $active_tab == 'phase_kpi_settings' ) : ?>
            <div id="phase-kpi-settings-content">
                <div class="oo-settings-section">
                    <h3><?php esc_html_e('Phases in this Stream', 'operations-organizer'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Phase Name', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Order', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('KPIs Included?', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stream_phases_all)): foreach ($stream_phases_all as $phase): ?>
                                <tr>
                                    <td><?php echo esc_html($phase->phase_name); ?></td>
                                    <td><?php echo esc_html($phase->order_in_stream); ?></td>
                                    <td><?php echo !empty($phase->includes_kpi) ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo !empty($phase->is_active) ? 'Active' : 'Inactive'; ?></td>
                                    <td><button class="button button-secondary oo-edit-phase-stream" data-phase-id="<?php echo esc_attr($phase->phase_id); ?>"><?php esc_html_e('Edit', 'operations-organizer'); ?></button></td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="5"><?php esc_html_e('No phases found for this stream.', 'operations-organizer'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button id="openAddPhaseModalBtn-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="button button-primary" style="margin-top:15px;"><?php esc_html_e('Add New Phase to this Stream', 'operations-organizer'); ?></button>
                </div>
                <div class="oo-settings-section">
                    <h3><?php esc_html_e('KPI Measures in this Stream', 'operations-organizer'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Measure Name', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Unit Type', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Linked Phases', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="kpi-measures-list-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                            <?php
                            if (!empty($stream_kpi_measures)) {
                                $kpi_html = '';
                                foreach ($stream_kpi_measures as $kpi) {
                                    $linked_phases_names = !empty($kpi->linked_phases) ? esc_html(implode(', ', wp_list_pluck($kpi->linked_phases, 'phase_name'))) : 'None';
                                    $status_text = $kpi->is_active ? 'Active' : 'Inactive';
                                    $toggle_text = $kpi->is_active ? 'Deactivate' : 'Activate';
                                    $toggle_status = $kpi->is_active ? 0 : 1;
                                    
                                    $kpi_html .= '<tr>';
                                    $kpi_html .= '<td>' . esc_html($kpi->measure_name) . '</td>';
                                    $kpi_html .= '<td>' . esc_html($kpi->unit_type) . '</td>';
                                    $kpi_html .= '<td>' . $linked_phases_names . '</td>';
                                    $kpi_html .= '<td>' . $status_text . '</td>';
                                    $kpi_html .= '<td>
                                        <button class="button button-secondary oo-edit-kpi-measure-stream" data-kpi-id="' . esc_attr($kpi->kpi_measure_id) . '">Edit</button>
                                        <button class="button button-secondary oo-toggle-kpi-status-stream" data-kpi-id="' . esc_attr($kpi->kpi_measure_id) . '" data-new-status="' . $toggle_status . '">' . $toggle_text . '</button>
                                        <a href="#" class="oo-delete-kpi-measure-stream" data-kpi-id="' . esc_attr($kpi->kpi_measure_id) . '">Delete</a>
                                    </td>';
                                    $kpi_html .= '</tr>';
                                }
                                echo $kpi_html;
                            } else {
                                echo '<tr><td colspan="5">' . esc_html__('No KPI Measures have been assigned to phases in this stream yet.', 'operations-organizer') . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    <button id="openAddKpiMeasureModalBtn-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="button button-primary" style="margin-top:15px;"><?php esc_html_e('Add New KPI Measure', 'operations-organizer'); ?></button>
                </div>
                <div class="oo-settings-section">
                    <h3><?php esc_html_e('Derived KPI Definitions', 'operations-organizer'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                         <thead>
                            <tr>
                                <th><?php esc_html_e('Definition Name', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Calculation', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="derived-kpi-definitions-list-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                            <?php
                            if (!empty($stream_derived_kpis)) {
                                $derived_kpi_html = '';
                                foreach ($stream_derived_kpis as $dkpi) {
                                    $status_text = $dkpi->is_active ? 'Active' : 'Inactive';
                                    $toggle_text = $dkpi->is_active ? 'Deactivate' : 'Activate';
                                    $toggle_status = $dkpi->is_active ? 0 : 1;
                                    $calculation_display = $dkpi->calculation_type; // Simplified display. Can be enhanced later.
                                    
                                    $derived_kpi_html .= '<tr>';
                                    $derived_kpi_html .= '<td>' . esc_html($dkpi->definition_name) . '</td>';
                                    $derived_kpi_html .= '<td>' . esc_html($calculation_display) . '</td>';
                                    $derived_kpi_html .= '<td>' . $status_text . '</td>';
                                    $derived_kpi_html .= '<td>
                                        <button class="button button-secondary oo-edit-derived-kpi-stream" data-derived-kpi-id="' . esc_attr($dkpi->derived_definition_id) . '">Edit</button>
                                        <button class="button button-secondary oo-toggle-derived-kpi-status-stream" data-derived-kpi-id="' . esc_attr($dkpi->derived_definition_id) . '" data-new-status="' . $toggle_status . '">' . $toggle_text . '</button>
                                        <a href="#" class="oo-delete-derived-kpi-stream" data-derived-kpi-id="' . esc_attr($dkpi->derived_definition_id) . '">Delete</a>
                                    </td>';
                                    $derived_kpi_html .= '</tr>';
                                }
                                echo $derived_kpi_html;
                            } else {
                                echo '<tr><td colspan="4">' . esc_html__('No Derived KPI Definitions have been created for this stream yet.', 'operations-organizer') . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    <button id="openAddDerivedKpiModalBtn-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="button button-primary" style="margin-top:15px;"><?php esc_html_e('Add New Derived KPI Definition', 'operations-organizer'); ?></button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- All modals for the stream page -->

<!-- KPI Column Selector Modal -->
<div id="kpi-column-selector-modal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;">
    <div class="oo-modal-content" style="width: 600px; max-width: 90%;">
        <span class="oo-modal-close">&times;</span>
        <h2><?php esc_html_e('Choose Columns to Display', 'operations-organizer'); ?></h2>
        <div id="kpi-column-list-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" style="max-height: 400px; overflow-y: auto; margin-bottom: 15px; border: 1px solid #ddd; padding: 10px;">
            <p><?php esc_html_e('Loading columns...', 'operations-organizer'); ?></p>
        </div>
        <button id="apply_selected_kpi_columns_stream_<?php echo esc_attr($current_stream_tab_slug); ?>" class="button button-primary"><?php esc_html_e('Apply Columns', 'operations-organizer'); ?></button>
        <button id="kpi_selector_select_all_stream_<?php echo esc_attr($current_stream_tab_slug); ?>" class="button button-secondary" style="margin-left:10px;"><?php esc_html_e('Select All', 'operations-organizer'); ?></button>
        <button id="kpi_selector_deselect_all_stream_<?php echo esc_attr($current_stream_tab_slug); ?>" class="button button-secondary" style="margin-left:5px;"><?php esc_html_e('Deselect All', 'operations-organizer'); ?></button>
    </div>
</div>

<!-- Edit Log Modal -->
<div id="edit-log-modal" class="oo-modal" style="display:none;"><div class="oo-modal-content"><span class="oo-modal-close">&times;</span>
    <h2><?php esc_html_e('Edit Job Log', 'operations-organizer'); ?></h2>
    <form id="edit-log-form">
        <input type="hidden" id="edit_log_id" name="log_id">
        <input type="hidden" id="edit_log_job_id" name="job_id">
        <input type="hidden" id="edit_log_phase_id" name="phase_id">
        <input type="hidden" name="action" value="oo_update_job_log_and_kpis">
        <?php wp_nonce_field( 'oo_update_job_log_nonce', '_ajax_nonce' ); ?>
        <div class="form-field"><label for="edit_log_job_number"><?php esc_html_e('Job Number', 'operations-organizer'); ?></label><input type="text" id="edit_log_job_number" name="job_number" readonly></div>
        <div class="form-field"><label for="edit_log_start_time"><?php esc_html_e('Start Time', 'operations-organizer'); ?></label><input type="datetime-local" id="edit_log_start_time" name="start_time"></div>
        <div class="form-field"><label for="edit_log_end_time"><?php esc_html_e('End Time', 'operations-organizer'); ?></label><input type="datetime-local" id="edit_log_end_time" name="end_time"></div>
        <div id="edit_kpi_values_container"></div>
        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'operations-organizer'); ?>">
    </form>
</div></div>

<!-- Add Phase Modal -->
<div id="addPhaseModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;"><div class="oo-modal-content"><span class="oo-modal-close">&times;</span>
    <h2><?php esc_html_e('Add New Phase to Stream', 'operations-organizer'); ?></h2>
    <form id="oo-add-phase-form-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
        <input type="hidden" name="action" value="oo_add_phase">
        <input type="hidden" name="stream_id" value="<?php echo esc_attr($current_stream_id); ?>">
        <input type="hidden" name="context" value="stream_page">
        <?php wp_nonce_field( 'oo_add_phase_nonce', '_ajax_nonce' ); ?>
        <div class="form-field"><label for="add_phase_name_stream_<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Phase Name', 'operations-organizer'); ?></label><input type="text" name="phase_name" id="add_phase_name_stream_<?php echo esc_attr($current_stream_tab_slug); ?>" required></div>
        <div class="form-field"><label for="add_order_in_stream_<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Order in Stream', 'operations-organizer'); ?></label><input type="number" name="order_in_stream" id="add_order_in_stream_<?php echo esc_attr($current_stream_tab_slug); ?>" value="10"></div>
        <div class="form-field"><label><input type="checkbox" name="includes_kpi" value="1" checked> <?php esc_html_e('Includes KPIs', 'operations-organizer'); ?></label></div>
        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Add Phase', 'operations-organizer'); ?>">
    </form>
</div></div>

<!-- Edit Phase Modal -->
<div id="editPhaseModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;"><div class="oo-modal-content"><span class="oo-modal-close">&times;</span>
    <h2 id="editPhaseNameDisplay-<?php echo esc_attr($current_stream_tab_slug); ?>"></h2>
    <form id="oo-edit-phase-form-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
        <input type="hidden" name="action" value="oo_update_phase">
        <input type="hidden" name="phase_id" id="edit_phase_id_stream_<?php echo esc_attr($current_stream_tab_slug); ?>">
        <?php wp_nonce_field( 'oo_update_phase_nonce', '_ajax_nonce' ); ?>
        <div class="form-field"><label for="edit_phase_name_stream_<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Phase Name', 'operations-organizer'); ?></label><input type="text" name="phase_name" id="edit_phase_name_stream_<?php echo esc_attr($current_stream_tab_slug); ?>" required></div>
        <div class="form-field"><label for="edit_order_in_stream_<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Order in Stream', 'operations-organizer'); ?></label><input type="number" name="order_in_stream" id="edit_order_in_stream_<?php echo esc_attr($current_stream_tab_slug); ?>"></div>
        <div class="form-field"><label><input type="checkbox" name="includes_kpi" id="edit_includes_kpi_stream_<?php echo esc_attr($current_stream_tab_slug); ?>" value="1"> <?php esc_html_e('Includes KPIs', 'operations-organizer'); ?></label></div>
        <div class="form-field"><label><input type="checkbox" name="is_active" id="edit_is_active_stream_<?php echo esc_attr($current_stream_tab_slug); ?>" value="1"> <?php esc_html_e('Is Active', 'operations-organizer'); ?></label></div>
        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'operations-organizer'); ?>">
    </form>
</div></div>

<!-- Add KPI Measure Modal -->
<div id="addKpiMeasureModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;"><div class="oo-modal-content"><span class="oo-modal-close">&times;</span>
    <h2><?php esc_html_e('Add New KPI Measure to Stream', 'operations-organizer'); ?></h2>
    <form id="oo-add-kpi-measure-form-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
        <input type="hidden" name="action" value="oo_add_kpi_measure_stream">
        <div class="form-field"><label for="add_measure_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Measure Name', 'operations-organizer'); ?></label><input type="text" name="measure_name" id="add_measure_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required></div>
        <div class="form-field"><label for="add_unit_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Unit Type', 'operations-organizer'); ?></label><select name="unit_type" id="add_unit_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><option value="count">Count</option><option value="time">Time</option><option value="monetary">Monetary</option></select></div>
        <div class="form-field"><label><?php esc_html_e('Link to Phases in this Stream', 'operations-organizer'); ?></label><div class="kpi-phase-checkboxes"><?php foreach ($stream_phases_all as $phase) { if($phase->includes_kpi) echo '<label><input type="checkbox" name="phase_ids[]" value="' . esc_attr($phase->phase_id) . '"> ' . esc_html($phase->phase_name) . '</label><br>'; } ?></div></div>
        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Add KPI Measure', 'operations-organizer'); ?>">
    </form>
</div></div>

<!-- Edit KPI Measure Modal -->
<div id="editKpiMeasureModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;"><div class="oo-modal-content"><span class="oo-modal-close">&times;</span>
    <h2><?php esc_html_e('Edit KPI Measure:', 'operations-organizer'); ?> <span id="editKpiMeasureNameDisplay-<?php echo esc_attr($current_stream_tab_slug); ?>"></span></h2>
    <form id="oo-edit-kpi-measure-form-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
        <input type="hidden" name="action" value="oo_update_kpi_measure_stream">
        <input type="hidden" name="kpi_measure_id" id="edit_kpi_measure_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
        <div class="form-field"><label for="edit_measure_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Measure Name', 'operations-organizer'); ?></label><input type="text" name="measure_name" id="edit_measure_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required></div>
        <div class="form-field"><label for="edit_unit_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Unit Type', 'operations-organizer'); ?></label><select name="unit_type" id="edit_unit_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><option value="count">Count</option><option value="time">Time</option><option value="monetary">Monetary</option></select></div>
        <div class="form-field"><label><?php esc_html_e('Link to Phases in this Stream', 'operations-organizer'); ?></label><div class="kpi-phase-checkboxes" id="edit_kpi_phase_checkboxes-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"></div></div>
        <div class="form-field"><label><input type="checkbox" name="is_active" id="edit_is_active-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" value="1"> <?php esc_html_e('Is Active', 'operations-organizer'); ?></label></div>
        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'operations-organizer'); ?>">
    </form>
</div></div>

<!-- Add Derived KPI Modal -->
<div id="addDerivedKpiModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;"><div class="oo-modal-content"><span class="oo-modal-close">&times;</span>
    <h2><?php esc_html_e('Add New Derived KPI Definition', 'operations-organizer'); ?></h2>
    <form id="oo-add-derived-kpi-form-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
        <input type="hidden" name="action" value="oo_add_derived_kpi_definition">
        <input type="hidden" name="stream_id_context" value="<?php echo esc_attr($current_stream_id); ?>">
        <div class="form-field"><label for="add_derived_definition_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Definition Name', 'operations-organizer'); ?></label><input type="text" name="derived_definition_name" id="add_derived_definition_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required></div>
        <div class="form-field"><label for="add_derived_primary_kpi_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Primary KPI Measure', 'operations-organizer'); ?></label><select name="primary_kpi_measure_id" id="add_derived_primary_kpi_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required><?php echo '<option value="">' . esc_html__('Select a Primary KPI', 'operations-organizer') . '</option>'; foreach ($stream_kpi_measures as $kpi) { echo '<option value="' . esc_attr($kpi->kpi_measure_id) . '" data-unit-type="' . esc_attr($kpi->unit_type) . '">' . esc_html($kpi->measure_name . ' (' . $kpi->unit_type . ')') . '</option>'; } ?></select></div>
        <div class="form-field"><label for="add_derived_calculation_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Calculation Type', 'operations-organizer'); ?></label><select name="calculation_type" id="add_derived_calculation_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" disabled><option value=""><?php esc_html_e('Select Primary KPI First', 'operations-organizer'); ?></option></select></div>
        <div id="add_derived_secondary_kpi_container-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="form-field" style="display:none;"><label for="add_derived_secondary_kpi_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Secondary KPI Measure (for Ratio)', 'operations-organizer'); ?></label><select name="secondary_kpi_measure_id" id="add_derived_secondary_kpi_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"></select></div>
        <div id="add_derived_time_unit_container-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="form-field" style="display:none;"><label for="add_derived_time_unit-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Time Unit (for Rate)', 'operations-organizer'); ?></label><select name="time_unit_for_rate" id="add_derived_time_unit-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><option value="minute">Minute</option><option value="hour">Hour</option><option value="day">Day</option></select></div>
        <div class="form-field"><label for="add_derived_output_description-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Output Description (e.g., "boxes/hr")', 'operations-organizer'); ?></label><input type="text" name="output_description" id="add_derived_output_description-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"></div>
        <div class="form-field"><label><input type="checkbox" name="is_active" value="1" checked> <?php esc_html_e('Is Active', 'operations-organizer'); ?></label></div>
        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Add Derived KPI', 'operations-organizer'); ?>">
    </form>
</div></div>

<!-- Edit Derived KPI Modal -->
<div id="editDerivedKpiModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;"><div class="oo-modal-content"><span class="oo-modal-close">&times;</span>
    <h2><?php esc_html_e('Edit Derived KPI:', 'operations-organizer'); ?> <span id="editDerivedKpiNameDisplay-<?php echo esc_attr($current_stream_tab_slug); ?>"></span></h2>
    <form id="oo-edit-derived-kpi-form-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
        <input type="hidden" name="action" value="oo_update_derived_kpi_definition">
        <input type="hidden" name="derived_definition_id" id="edit_derived_definition_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
        <div class="form-field"><label for="edit_derived_definition_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Definition Name', 'operations-organizer'); ?></label><input type="text" name="derived_definition_name" id="edit_derived_definition_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required></div>
        <div class="form-field"><label><?php esc_html_e('Primary KPI Measure', 'operations-organizer'); ?></label><p id="edit_derived_primary_kpi_name_display-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="readonly-field"></p><input type="hidden" name="primary_kpi_measure_id" id="edit_derived_primary_kpi_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><input type="hidden" id="edit_derived_primary_kpi_unit_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"></div>
        <div class="form-field"><label for="edit_derived_calculation_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Calculation Type', 'operations-organizer'); ?></label><select name="calculation_type" id="edit_derived_calculation_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"></select></div>
        <div id="edit_derived_secondary_kpi_container-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="form-field" style="display:none;"><label for="edit_derived_secondary_kpi_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Secondary KPI Measure (for Ratio)', 'operations-organizer'); ?></label><select name="secondary_kpi_measure_id" id="edit_derived_secondary_kpi_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"></select></div>
        <div id="edit_derived_time_unit_container-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="form-field" style="display:none;"><label for="edit_derived_time_unit-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Time Unit (for Rate)', 'operations-organizer'); ?></label><select name="time_unit_for_rate" id="edit_derived_time_unit-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><option value="minute">Minute</option><option value="hour">Hour</option><option value="day">Day</option></select></div>
        <div class="form-field"><label for="edit_derived_output_description-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e('Output Description (e.g., "boxes/hr")', 'operations-organizer'); ?></label><input type="text" name="output_description" id="edit_derived_output_description-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"></div>
        <div class="form-field"><label><input type="checkbox" name="is_active" id="edit_derived_is_active-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" value="1"> <?php esc_html_e('Is Active', 'operations-organizer'); ?></label></div>
        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Derived KPI Changes', 'operations-organizer'); ?>">
    </form>
</div></div>

<script>
jQuery(document).ready(function($) {
    var streamSlug = '<?php echo esc_js($current_stream_tab_slug); ?>';
    var allStreamKpis = <?php echo json_encode($stream_kpi_measures); ?>;
    
    // Helper function for escaping HTML in JS
    function esc_html(str) {
        if (str === null || typeof str === 'undefined') return '';
        var p = document.createElement("p");
        p.appendChild(document.createTextNode(String(str)));
        return p.innerHTML;
    }
    
    // --- General Modal Close Logic ---
    $('.oo-modal .oo-modal-close').on('click', function() { $(this).closest('.oo-modal').hide(); });
    $(window).on('click', function(event) { if ($(event.target).is('.oo-modal')) { $(event.target).hide(); } });

    // --- Tab: Phase Log Actions ---
    $('.oo-start-link-btn, .oo-stop-link-btn').on('click', function() {
        var $button = $(this), isStart = $button.hasClass('oo-start-link-btn'), phaseId = $button.data('phase-id'), $input = $button.siblings('.oo-job-number-input'), jobNumber = $input.val();
        if (!jobNumber) { alert('<?php echo esc_js(__("Please enter a Job Number.", "operations-organizer")); ?>'); $input.focus(); return; }
        var action = isStart ? 'oo_start_phase' : 'oo_stop_phase', nonce = isStart ? oo_data.start_phase_nonce : oo_data.stop_phase_nonce;
        $button.prop('disabled', true); $input.prop('disabled', true);
        $.post(oo_data.ajax_url, { action: action, phase_id: phaseId, job_number: jobNumber, employee_id: oo_data.current_user_id, _ajax_nonce: nonce })
        .done(function(r) { if(r.success) { showNotice('success', r.data.message); $input.val(''); } else { showNotice('error', r.data.message); } })
        .fail(function() { showNotice('error', '<?php echo esc_js(__("Request failed.", "operations-organizer")); ?>'); })
        .always(function() { $button.prop('disabled', false); $input.prop('disabled', false); });
    });

    // --- Tab: Phase Dashboard ---
    if ('<?php echo esc_js($active_tab); ?>' === 'phase_dashboard') {
        $('.oo-datepicker').datepicker({ dateFormat: 'yy-mm-dd' });
        var contentTable;
        var initialColumns = [
            { data: 'log_id', title: 'Log ID', visible: false }, { data: 'job_number', title: 'Job Number' }, { data: 'phase_name', title: 'Phase' }, { data: 'employee_name', title: 'Employee' },
            { data: 'start_time', title: 'Start Time', render: function(d) { return d ? new Date(d).toLocaleString() : 'N/A'; } },
            { data: 'end_time', title: 'End Time', render: function(d) { return d ? new Date(d).toLocaleString() : 'N/A'; } }, { data: 'status', title: 'Status' },
            { data: null, title: 'Actions', orderable: false, render: function(d,t,r) { return '<button class="button button-small oo-edit-log" data-log-id="'+r.log_id+'">Edit</button>'; } }
        ];
        var allAvailableColumns = [];

        function initContentDashboardTable(columns) {
            if ($.fn.DataTable.isDataTable('#content-dashboard-table')) { $('#content-dashboard-table').DataTable().destroy(); }
            contentTable = $('#content-dashboard-table').DataTable({
                processing: true, serverSide: true,
                ajax: { url: oo_data.ajax_url, type: 'POST', data: function(d) {
                    d.action = 'oo_get_job_logs_for_stream'; d.stream_id = <?php echo intval($current_stream_id); ?>; d._ajax_nonce = oo_data.get_job_logs_nonce;
                    d.employee_id = $('#content_filter_employee_id').val(); d.job_number = $('#content_filter_job_number').val(); d.phase_id = $('#content_filter_phase_id').val();
                    d.status = $('#content_filter_status').val(); d.date_from = $('#content_filter_date_from').val(); d.date_to = $('#content_filter_date_to').val();
                }},
                columns: columns, order: [[ 4, "desc" ]]
            });
        }
        
        function populateKpiColumnSelector(selectedColumns, allColumns) {
            var list = $('#kpi-column-list-stream-' + streamSlug).empty();
            var selectedKeys = selectedColumns.map(function(c) { return c.data; });
            allColumns.forEach(function(c) {
                list.append('<label><input type="checkbox" name="kpi_columns" value="'+c.data+'" '+(selectedKeys.includes(c.data)?'checked':'')+'> '+c.title+'</label><br>');
            });
            $('#content_selected_kpi_count').text(selectedKeys.length + ' of ' + allColumns.length + ' columns selected.');
        }

        $.post(oo_data.ajax_url, { action: 'oo_get_kpi_columns_for_stream', stream_id: <?php echo intval($current_stream_id); ?>, _ajax_nonce: oo_data.get_kpi_columns_nonce })
        .done(function(response) {
            if (response.success) {
                allAvailableColumns = initialColumns.concat(response.data);
                var userSavedKeys = oo_data.user_stream_default_columns || [];
                var finalColumns = userSavedKeys.length > 0 ? allAvailableColumns.filter(function(c) { return userSavedKeys.includes(c.data); }) : allAvailableColumns;
                initContentDashboardTable(finalColumns);
                populateKpiColumnSelector(finalColumns, allAvailableColumns);
            } else { initContentDashboardTable(initialColumns); populateKpiColumnSelector(initialColumns, initialColumns); }
        }).fail(function() { initContentDashboardTable(initialColumns); populateKpiColumnSelector(initialColumns, initialColumns); });

        $('#content_apply_filters_button').on('click', function() { if(contentTable) contentTable.ajax.reload(); });
        $('#content_clear_filters_button').on('click', function() { $('#content-dashboard-section form select, #content-dashboard-section form input').val(''); if(contentTable) contentTable.ajax.reload(); });
        
        $('#content_open_kpi_selector_modal').on('click', function() { $('#kpi-column-selector-modal-stream-' + streamSlug).show(); });
        $('#kpi_selector_select_all_stream_' + streamSlug).on('click', function() { $('#kpi-column-list-stream-' + streamSlug + ' input').prop('checked', true); });
        $('#kpi_selector_deselect_all_stream_' + streamSlug).on('click', function() { $('#kpi-column-list-stream-' + streamSlug + ' input').prop('checked', false); });

        $('#apply_selected_kpi_columns_stream_' + streamSlug).on('click', function() {
            var selectedKeys = $('#kpi-column-list-stream-' + streamSlug + ' input:checked').map(function() { return $(this).val(); }).get();
            var newColumns = allAvailableColumns.filter(function(c) { return selectedKeys.includes(c.data); });
            initContentDashboardTable(newColumns);
            populateKpiColumnSelector(newColumns, allAvailableColumns);
            $('#kpi-column-selector-modal-stream-' + streamSlug).hide();
            $('#save_content_columns_as_default').show();
        });
        
        $('#save_content_columns_as_default').on('click', function() {
            var selected = $('#kpi-column-list-stream-' + streamSlug + ' input:checked').map(function(){ return $(this).val(); }).get();
            $.post(oo_data.ajax_url, { action: 'oo_save_user_meta', _ajax_nonce: oo_data.nonce_save_user_meta, meta_key: 'oo_stream_dashboard_columns_' + streamSlug, meta_value: selected})
            .done(function() { $('#content_columns_default_saved_msg').fadeIn().delay(2000).fadeOut(); });
        });
        
        $('#content-dashboard-table').on('click', '.oo-edit-log', function() {
            var logId = $(this).data('log-id');
            $.post(oo_data.ajax_url, { action: 'oo_get_job_log_details', log_id: logId, _ajax_nonce: oo_data.get_job_log_details_nonce })
            .done(function(r) {
                if (r.success) {
                    var d = r.data, f = $('#edit-log-form');
                    f[0].reset();
                    f.find('#edit_log_id').val(d.log_id);
                    f.find('#edit_log_job_number').val(d.job_number);
                    f.find('#edit_log_start_time').val(d.start_time.replace(' ','T'));
                    f.find('#edit_log_end_time').val(d.end_time ? d.end_time.replace(' ','T') : '');
                    var kpiContainer = f.find('#edit_kpi_values_container').empty();
                    if (d.kpi_values && d.kpi_values.length) {
                        d.kpi_values.forEach(function(k) {
                            kpiContainer.append('<div class="form-field"><label>'+k.measure_name+'</label><input type="number" step="any" name="kpi_values['+k.kpi_measure_id+']" value="'+k.value+'"></div>');
                        });
                    }
                    $('#edit-log-modal').show();
                } else { showNotice('error', r.data.message); }
            });
        });

        $('#edit-log-form').on('submit', function(e) {
            e.preventDefault();
            $.post(oo_data.ajax_url, $(this).serialize())
            .done(function(r) {
                if (r.success) { showNotice('success', r.data.message); $('#edit-log-modal').hide(); if(contentTable) contentTable.ajax.reload(null, false); }
                else { showNotice('error', r.data.message); }
            });
        });
    }

    // --- Tab: Phase & KPI Settings ---
    if ('<?php echo esc_js($active_tab); ?>' === 'phase_kpi_settings') {
        // --- Phase Management ---
        $('#openAddPhaseModalBtn-stream-' + streamSlug).on('click', function() { $('#addPhaseModal-stream-' + streamSlug).show().find('form')[0].reset(); });
        $('#oo-add-phase-form-stream-' + streamSlug).on('submit', function(e) { e.preventDefault(); $.post(oo_data.ajax_url, $(this).serialize()).done(function() { location.reload(); }); });
        
        $('#phase-kpi-settings-content').on('click', '.oo-edit-phase-stream', function() {
            var phaseId = $(this).data('phase-id');
            $.post(oo_data.ajax_url, { action: 'oo_get_phase_details', phase_id: phaseId, _ajax_nonce: oo_data.nonce_get_phase_details })
            .done(function(r) {
                if (r.success) {
                    var d=r.data, f=$('#oo-edit-phase-form-stream-'+streamSlug);
                    f.find('#edit_phase_id_stream_'+streamSlug).val(d.phase_id);
                    f.find('#edit_phase_name_stream_'+streamSlug).val(d.phase_name);
                    f.find('#edit_order_in_stream_'+streamSlug).val(d.order_in_stream);
                    f.find('#edit_includes_kpi_stream_'+streamSlug).prop('checked', d.includes_kpi == 1);
                    f.find('#edit_is_active_stream_'+streamSlug).prop('checked', d.is_active == 1);
                    $('#editPhaseNameDisplay-'+streamSlug).text(d.phase_name);
                    $('#editPhaseModal-stream-'+streamSlug).show();
                }
            });
        });
        $('#oo-edit-phase-form-stream-' + streamSlug).on('submit', function(e) { e.preventDefault(); $.post(oo_data.ajax_url, $(this).serialize()).done(function() { location.reload(); }); });

        // --- KPI Measure Management ---
        function refreshKpiList_Stream() {
            var list = $('#kpi-measures-list-stream-' + streamSlug).html('<tr><td colspan="5">Loading...</td></tr>');
            $.post(oo_data.ajax_url, { action: 'oo_get_kpi_measures_for_stream_html', _ajax_nonce: oo_data.nonce_get_kpi_measures_for_stream, stream_id: <?php echo intval($current_stream_id); ?> })
            .done(function(r) { list.html(r.success ? r.data.html : '<tr><td colspan="5">Error loading KPIs.</td></tr>'); });
        }
        $('#openAddKpiMeasureModalBtn-stream-' + streamSlug).on('click', function() { $('#addKpiMeasureModal-stream-' + streamSlug).show().find('form')[0].reset(); });
        $('#phase-kpi-settings-content').on('submit', '#oo-add-kpi-measure-form-stream-' + streamSlug, function(e) {
            e.preventDefault(); var data = $(this).serializeArray(); data.push({name: '_ajax_nonce', value: oo_data.nonce_add_kpi_measure});
            $.post(oo_data.ajax_url, $.param(data)).done(function(r) { if(r.success){ showNotice('success', r.data.message); $('#addKpiMeasureModal-stream-'+streamSlug).hide(); refreshKpiList_Stream(); } else { showNotice('error', r.data.message); }});
        });
        $('#phase-kpi-settings-content').on('click', '.oo-edit-kpi-measure-stream', function() {
            var kpiId = $(this).data('kpi-id');
            $.post(oo_data.ajax_url, { action: 'oo_get_kpi_measure_details', _ajax_nonce: oo_data.nonce_get_kpi_measure_details, kpi_measure_id: kpiId })
            .done(function(r) {
                if(r.success) {
                    var d=r.data, m=$('#editKpiMeasureModal-stream-'+streamSlug);
                    m.find('#edit_kpi_measure_id-stream-'+streamSlug).val(d.kpi_measure_id);
                    m.find('#edit_measure_name-stream-'+streamSlug).val(d.measure_name);
                    m.find('#editKpiMeasureNameDisplay-'+streamSlug).text(d.measure_name);
                    m.find('#edit_unit_type-stream-'+streamSlug).val(d.unit_type);
                    m.find('#edit_is_active-stream-'+streamSlug).prop('checked', d.is_active==1);
                    var boxes=m.find('.kpi-phase-checkboxes').empty();
                    if(d.all_stream_phases && d.all_stream_phases.length) {
                        d.all_stream_phases.forEach(function(p) {
                            boxes.append('<label><input type="checkbox" name="phase_ids[]" value="'+p.phase_id+'"'+(d.linked_phase_ids.includes(p.phase_id)?' checked':'')+'> '+esc_html(p.phase_name)+'</label><br>');
                        });
                    }
                    m.show();
                }
            });
        });
        $('#phase-kpi-settings-content').on('submit', '#oo-edit-kpi-measure-form-stream-' + streamSlug, function(e) {
            e.preventDefault(); var data = $(this).serializeArray(); data.push({name: '_ajax_nonce', value: oo_data.nonce_edit_kpi_measure});
            $.post(oo_data.ajax_url, $.param(data)).done(function(r) { if(r.success){ showNotice('success', r.data.message); $('#editKpiMeasureModal-stream-'+streamSlug).hide(); refreshKpiList_Stream(); } else { showNotice('error', r.data.message); }});
        });
        $('#phase-kpi-settings-content').on('click', '.oo-toggle-kpi-status-stream', function() {
            var btn=$(this), id=btn.data('kpi-id'), status=btn.data('new-status'); if(!confirm('Are you sure?')) return;
            $.post(oo_data.ajax_url, { action: 'oo_toggle_kpi_measure_status', _ajax_nonce: oo_data.nonce_toggle_kpi_status, kpi_measure_id: id, is_active: status})
            .done(function(){ refreshKpiList_Stream(); });
        });
        $('#phase-kpi-settings-content').on('click', '.oo-delete-kpi-measure-stream', function(e) {
            e.preventDefault(); var id=$(this).data('kpi-id'); if(!confirm('Are you sure?')) return;
            $.post(oo_data.ajax_url, { action: 'oo_delete_kpi_measure', _ajax_nonce: oo_data.nonce_delete_kpi_measure, kpi_measure_id: id})
            .done(function(){ refreshKpiList_Stream(); });
        });

        // --- Derived KPI Management ---
        function populateCalculationTypes($select, unitType) {
            $select.empty().append('<option value="">Select Calculation</option>');
            if (unitType === 'count') { $select.append('<option value="rate_per_time">Rate per Time</option>'); }
            $select.append('<option value="ratio_to_kpi">Ratio to another KPI</option>');
        }
        function populateSecondaryKpis($select, primaryKpiId) {
            $select.empty().append('<option value="">Select Secondary KPI</option>');
            allStreamKpis.forEach(function(k) { if (k.kpi_measure_id != primaryKpiId) { $select.append($('<option>', { value: k.kpi_measure_id, text: k.measure_name + ' (' + k.unit_type + ')' })); } });
        }
        function refreshDerivedKpiList_Stream() {
            var list = $('#derived-kpi-definitions-list-stream-' + streamSlug).html('<tr><td colspan="4">Loading...</td></tr>');
            $.post(oo_data.ajax_url, { action: 'oo_get_derived_kpis_for_stream_html', _ajax_nonce: oo_data.nonce_get_derived_kpis_for_stream_html, stream_id: <?php echo intval($current_stream_id); ?> })
            .done(function(r) { list.html(r.success ? r.data.html : '<tr><td colspan="4">Error loading derived KPIs.</td></tr>'); });
        }

        $('#openAddDerivedKpiModalBtn-stream-' + streamSlug).on('click', function() { $('#addDerivedKpiModal-stream-' + streamSlug).show().find('form')[0].reset(); });
        
        $('#add_derived_primary_kpi_id-stream-' + streamSlug).on('change', function() {
            var opt = $(this).find('option:selected'), unitType = opt.data('unit-type'), calcSelect = $('#add_derived_calculation_type-stream-' + streamSlug);
            populateCalculationTypes(calcSelect, unitType); calcSelect.prop('disabled', false).trigger('change');
        });
        $('#add_derived_calculation_type-stream-' + streamSlug).on('change', function() {
            var type = $(this).val(), primaryId = $('#add_derived_primary_kpi_id-stream-'+streamSlug).val();
            $('#add_derived_time_unit_container-stream-'+streamSlug).toggle(type === 'rate_per_time');
            $('#add_derived_secondary_kpi_container-stream-'+streamSlug).toggle(type === 'ratio_to_kpi');
            if(type === 'ratio_to_kpi') { populateSecondaryKpis($('#add_derived_secondary_kpi_id-stream-'+streamSlug), primaryId); }
        });
        $('#phase-kpi-settings-content').on('submit', '#oo-add-derived-kpi-form-stream-' + streamSlug, function(e) {
            e.preventDefault(); 
            var $form = $(this);
            var data = $form.serializeArray(); 
            data.push({name: '_ajax_nonce', value: oo_data.nonce_add_derived_kpi});
            $form.find('input[type="submit"]').prop('disabled', true);

            $.post(oo_data.ajax_url, $.param(data))
            .done(function(r) { 
                if(r.success){ 
                    showNotice('success', r.data.message);
                    $('#addDerivedKpiModal-stream-'+streamSlug).hide(); 
                    refreshDerivedKpiList_Stream(); 
                } else { 
                    showNotice('error', r.data.message || "An unknown error occurred."); 
                }
            })
            .fail(function() { showNotice('error', "Request failed. Please try again."); })
            .always(function() { $form.find('input[type="submit"]').prop('disabled', false); });
        });

        $('#phase-kpi-settings-content').on('click', '.oo-edit-derived-kpi-stream', function() {
            var id = $(this).data('derived-kpi-id');
            $.post(oo_data.ajax_url, { action: 'oo_get_derived_kpi_definition_details_stream', _ajax_nonce: oo_data.nonce_get_derived_kpi_details, derived_kpi_id: id })
            .done(function(r) {
                if(r.success) {
                    var d=r.data, m=$('#editDerivedKpiModal-stream-'+streamSlug);
                    m.find('form')[0].reset();
                    m.find('#edit_derived_definition_id-stream-'+streamSlug).val(d.derived_definition_id);
                    m.find('#edit_derived_definition_name-stream-'+streamSlug).val(d.definition_name);
                    m.find('#editDerivedKpiNameDisplay-'+streamSlug).text(d.definition_name);
                    m.find('#edit_derived_primary_kpi_name_display-stream-'+streamSlug).text(d.primary_kpi_measure_name + ' ('+d.primary_kpi_unit_type+')');
                    m.find('input[name="primary_kpi_measure_id"]').val(d.primary_kpi_measure_id);
                    m.find('#edit_derived_primary_kpi_unit_type-stream-'+streamSlug).val(d.primary_kpi_unit_type);
                    var calcSelect = m.find('#edit_derived_calculation_type-stream-'+streamSlug);
                    populateCalculationTypes(calcSelect, d.primary_kpi_unit_type);
                    calcSelect.val(d.calculation_type).trigger('change');
                    if (d.calculation_type === 'ratio_to_kpi') {
                        var secSelect = m.find('#edit_derived_secondary_kpi_id-stream-'+streamSlug);
                        populateSecondaryKpis(secSelect, d.primary_kpi_measure_id);
                        secSelect.val(d.secondary_kpi_measure_id);
                    }
                    if (d.calculation_type === 'rate_per_time') { m.find('#edit_derived_time_unit-stream-'+streamSlug).val(d.time_unit_for_rate); }
                    m.find('#edit_derived_output_description-stream-'+streamSlug).val(d.output_description);
                    m.find('#edit_derived_is_active-stream-'+streamSlug).prop('checked', d.is_active == 1);
                    m.show();
                }
            });
        });

        $('#edit_derived_calculation_type-stream-' + streamSlug).on('change', function() {
            var type = $(this).val(), primaryId = $('#editDerivedKpiModal-stream-'+streamSlug).find('input[name="primary_kpi_measure_id"]').val();
            $('#edit_derived_time_unit_container-stream-'+streamSlug).toggle(type === 'rate_per_time');
            $('#edit_derived_secondary_kpi_container-stream-'+streamSlug).toggle(type === 'ratio_to_kpi');
            if(type === 'ratio_to_kpi') { populateSecondaryKpis($('#edit_derived_secondary_kpi_id-stream-'+streamSlug), primaryId); }
        });

        $('#phase-kpi-settings-content').on('submit', '#oo-edit-derived-kpi-form-stream-' + streamSlug, function(e) {
            e.preventDefault(); 
            var $form = $(this);
            var data = $form.serializeArray(); 
            data.push({name: '_ajax_nonce', value: oo_data.nonce_edit_derived_kpi});
            $form.find('input[type="submit"]').prop('disabled', true);
            
            $.post(oo_data.ajax_url, $.param(data))
            .done(function(r) { 
                if(r.success){
                    showNotice('success', r.data.message);
                    $('#editDerivedKpiModal-stream-'+streamSlug).hide(); 
                    refreshDerivedKpiList_Stream(); 
                } else { 
                    showNotice('error', r.data.message || "An unknown error occurred."); 
                }
            })
            .fail(function() { showNotice('error', "Request failed. Please try again."); })
            .always(function() { $form.find('input[type="submit"]').prop('disabled', false); });
        });
        
        $('#phase-kpi-settings-content').on('click', '.oo-toggle-derived-kpi-status-stream', function() {
            var btn=$(this), id=btn.data('derived-kpi-id'), status=btn.data('new-status'); if(!confirm('Are you sure?')) return;
            $.post(oo_data.ajax_url, { action: 'oo_toggle_derived_kpi_status', _ajax_nonce: oo_data.nonce_toggle_derived_kpi_status, derived_definition_id: id, is_active: status})
            .done(function(){ refreshDerivedKpiList_Stream(); });
        });
        
        $('#phase-kpi-settings-content').on('click', '.oo-delete-derived-kpi-stream', function(e) {
            e.preventDefault(); var id=$(this).data('derived-kpi-id'); if(!confirm('Are you sure?')) return;
            $.post(oo_data.ajax_url, { action: 'oo_delete_derived_kpi_definition', _ajax_nonce: oo_data.nonce_delete_derived_kpi, derived_definition_id: id})
            .done(function(){ refreshDerivedKpiList_Stream(); });
        });
    }
});
</script>