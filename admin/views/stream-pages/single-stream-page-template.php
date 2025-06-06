<?php
// admin/views/stream-pages/content-stream-page.php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// These globals would be set by the calling function in OO_Admin_Pages
global $current_stream_id, $current_stream_name, $current_stream_tab_slug, $phases, $employees;

$active_tab = isset( $_GET['sub_tab'] ) ? sanitize_key( $_GET['sub_tab'] ) : 'phase_log_actions';

oo_log('[Content Stream Page] Current Stream ID: ' . (isset($current_stream_id) ? $current_stream_id : 'Not Set'), 'ContentStreamPage');
oo_log('[Content Stream Page] Global Phases available: ' . (isset($phases) ? count($phases) : 'Not Set or Empty'), 'ContentStreamPage');

// Prepare phases for the current stream for Quick Actions
$stream_phases = array();
if (isset($current_stream_id) && !empty($phases)) {
    foreach ($phases as $phase_item) { // Renamed to avoid conflict with $phase in content-tab snippet
        oo_log('[Content Stream Page] Checking phase: ' . $phase_item->phase_name . ' (ID: ' . $phase_item->phase_id . ', StreamID: ' . $phase_item->stream_id . ', IncludesKPI: ' . $phase_item->includes_kpi . ')', 'ContentStreamPage');
        if ($phase_item->stream_id == $current_stream_id && !empty($phase_item->includes_kpi)) {
            $stream_phases[] = $phase_item;
            oo_log('[Content Stream Page] ----> Added phase: ' . $phase_item->phase_name, 'ContentStreamPage');
        }
    }
}
oo_log('[Content Stream Page] Filtered Stream Phases for Quick Actions: ' . count($stream_phases), 'ContentStreamPage');

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
        <!-- Add more tabs here as needed -->
    </h2>

    <div class="oo-stream-tab-content">
        <?php if ( $active_tab == 'phase_log_actions' ) : ?>
            <div id="phase-log-actions-content">
                <div class="oo-dashboard-section">
                    <h3><?php esc_html_e('Quick Phase Actions', 'operations-organizer'); ?></h3>
                    <p><?php esc_html_e('Enter a Job Number and select a phase to start or stop.', 'operations-organizer'); ?></p>
                    
                    <?php if (!empty($stream_phases)): ?>
                        <table class="form-table">
                            <?php foreach ($stream_phases as $phase): ?>
                                <tr valign="top" class="oo-phase-action-row">
                                    <th scope="row" style="width: 200px;">
                                        <?php echo esc_html($phase->phase_name); ?>
                                    </th>
                                    <td>
                                        <input type="text" class="oo-job-number-input" placeholder="<?php esc_attr_e('Enter Job Number', 'operations-organizer'); ?>" style="width: 200px; margin-right: 10px;">
                                        <button class="button button-primary oo-start-link-btn" data-phase-id="<?php echo esc_attr($phase->phase_id); ?>"><?php esc_html_e('Start', 'operations-organizer'); ?></button>
                                        <button class="button oo-stop-link-btn" data-phase-id="<?php echo esc_attr($phase->phase_id); ?>"><?php esc_html_e('Stop', 'operations-organizer'); ?></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else:
                        $stream_name_for_notice = isset($current_stream_name) ? esc_html($current_stream_name) : 'this stream';
                    ?>
                        <p class="oo-notice"><?php printf(esc_html__('No phases (with KPIs enabled) have been configured for %s. Please add or update phases through the Phases management page.', 'operations-organizer'), $stream_name_for_notice); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ( $active_tab == 'phase_dashboard' ) : ?>
            <div id="phase-dashboard-content">
                <h3><?php esc_html_e('Phase Dashboard', 'operations-organizer'); ?></h3>
                
                <div class="oo-dashboard-section" id="kanban-section">
                    <h3><?php esc_html_e('Checkpoint Progress', 'operations-organizer'); ?></h3>
                    <p class="description"><?php esc_html_e('Content process flow: Pickup & Inventory → Purge → Estimate → Approval → Invoice → Payment → Cleaned → In Storage → Delivered', 'operations-organizer'); ?></p>
                    
                    <div class="oo-filter-section" style="/* display:none; */"> <!-- This filter section might be for the Kanban itself later -->
                        <label for="stream_page_kanban_filter_job_number"><?php esc_html_e('Filter Kanban by Job Number:', 'operations-organizer'); ?></label>
                        <input type="text" id="stream_page_kanban_filter_job_number" class="regular-text" placeholder="<?php esc_attr_e('Enter job number', 'operations-organizer'); ?>">
                        <button id="stream_page_kanban_apply_filter" class="button button-secondary"><?php esc_html_e('Apply', 'operations-organizer'); ?></button>
                    </div>
                    
                    <div class="oo-placeholder-kanban">
                        <p class="oo-notice oo-info">
                            <?php esc_html_e('The Kanban board for this stream will be implemented here. It will display jobs in columns representing their current checkpoint in the workflow.', 'operations-organizer'); ?>
                        </p>
                    </div>
                </div>

                <div class="oo-dashboard-section" id="stream-jobs-list-section">
                     <h4><?php esc_html_e('Jobs in this Stream', 'operations-organizer'); ?></h4>
                     <!-- New Jobs List Table for this stream will go here -->
                     <table id="stream-jobs-table-<?php echo esc_attr($current_stream_tab_slug); ?>" class="display wp-list-table widefat fixed striped" style="width:100%">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Job No.', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Client Name', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Overall Status', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Due Date', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="oo-dashboard-section" id="stream-job-logs-section">
                    <h4><?php esc_html_e('Detailed Job Logs for Stream', 'operations-organizer'); ?></h4>
                    <!-- Content from old content-tab.php (filters, KPI selector, table) will go here -->
                    
                    <div class="oo-filter-section">
                        <div class="filter-row">
                            <div class="filter-item">
                                <label for="content_filter_employee_id"><?php esc_html_e('Employee:', 'operations-organizer');?></label>
                                <select id="content_filter_employee_id" name="content_filter_employee_id">
                                    <option value=""><?php esc_html_e('All Employees', 'operations-organizer');?></option>
                                    <?php foreach ($employees as $employee): // Assumes $employees is globally available or passed to this view ?>
                                        <option value="<?php echo esc_attr($employee->employee_id); ?>">
                                            <?php echo esc_html($employee->first_name . ' ' . $employee->last_name); ?>
                                        </option>
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
                                     <?php if (!empty($stream_phases)): foreach ($stream_phases as $phase): // Use $stream_phases prepared earlier ?>
                                        <option value="<?php echo esc_attr($phase->phase_id); ?>">
                                            <?php echo esc_html($phase->phase_name); ?>
                                        </option>
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
                    
                    <div id="content-export-options" style="margin-bottom: 20px;">
                        <button id="content_export_csv_button" class="button"><?php esc_html_e('Export to CSV', 'operations-organizer');?></button>
                    </div>
            
                    <table id="content-dashboard-table" class="display wp-list-table widefat fixed striped" style="width:100%">
                        <thead>
                            <!-- Headers are generated by JS -->
                        </thead>
                        <tbody>
                            <!-- Data will be loaded by DataTables via AJAX -->
                        </tbody>
                    </table>
            
                    <!-- KPI Column Selector Modal for Stream Page -->
                    <div id="kpi-column-selector-modal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;">
                        <div class="oo-modal-content" style="width: 600px; max-width: 90%;">
                            <span class="oo-modal-close">&times;</span>
                            <h2><?php esc_html_e('Choose Columns to Display', 'operations-organizer'); ?></h2>
                            <div id="kpi-column-list-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" style="max-height: 400px; overflow-y: auto; margin-bottom: 15px; border: 1px solid #ddd; padding: 10px;">
                                <!-- Checkboxes will be populated here by JavaScript -->
                                <p><?php esc_html_e('Loading columns...', 'operations-organizer'); ?></p>
                            </div>
                            <button id="apply_selected_kpi_columns_stream_<?php echo esc_attr($current_stream_tab_slug); ?>" class="button button-primary"><?php esc_html_e('Apply Columns', 'operations-organizer'); ?></button>
                            <button id="kpi_selector_select_all_stream_<?php echo esc_attr($current_stream_tab_slug); ?>" class="button button-secondary" style="margin-left:10px;"><?php esc_html_e('Select All', 'operations-organizer'); ?></button>
                            <button id="kpi_selector_deselect_all_stream_<?php echo esc_attr($current_stream_tab_slug); ?>" class="button button-secondary" style="margin-left:5px;"><?php esc_html_e('Deselect All', 'operations-organizer'); ?></button>
                        </div>
                    </div>
                    <!-- End KPI Column Selector Modal for Stream Page -->

                    <!-- Add modal dialog for editing job logs -->
                    <div id="edit-log-modal" class="oo-modal">
                        <div class="oo-modal-content">
                            <span class="oo-modal-close">&times;</span>
                            <h2><?php esc_html_e('Edit Job Log', 'operations-organizer'); ?></h2>
                            <form id="edit-log-form">
                                <input type="hidden" id="edit_log_id" name="edit_log_id">
                                <input type="hidden" id="edit_log_employee_id" name="edit_log_employee_id">
                                <input type="hidden" id="edit_log_job_id" name="edit_log_job_id">
                                <input type="hidden" id="edit_log_phase_id" name="edit_log_phase_id">
                                <input type="hidden" id="edit_log_stream_id" name="edit_log_stream_id" value="<?php echo esc_attr($current_stream_id); ?>">
                                <input type="hidden" id="edit_log_status" name="edit_log_status">
                                <input type="hidden" name="action" value="oo_update_job_log">
                                <input type="hidden" name="oo_edit_log_nonce_field" value="<?php echo wp_create_nonce('oo_edit_log_nonce'); ?>">
                                
                                <div class="form-field">
                                    <label for="edit_log_job_number"><?php esc_html_e('Job Number', 'operations-organizer'); ?></label>
                                    <div class="job-number-container" style="display: flex; align-items: center;">
                                        <input type="text" id="edit_log_job_number" name="edit_log_job_number" readonly style="flex-grow: 1;">
                                        <div style="margin-left: 10px;">
                                            <input type="checkbox" id="enable_job_number_edit" name="enable_job_number_edit">
                                            <label for="enable_job_number_edit"><?php esc_html_e('Allow Edit', 'operations-organizer'); ?></label>
                                        </div>
                                    </div>
                                    <div class="form-description" style="color: #d63638; display: none;" id="job_number_warning">
                                        <?php esc_html_e('Warning: Changing the job number may affect related records. Only change if absolutely necessary.', 'operations-organizer'); ?>
                                    </div>
                                </div>
                                
                                <div class="form-field">
                                    <label for="edit_log_start_time_editable" id="edit_log_start_time_label"><?php esc_html_e('Start Time', 'operations-organizer'); ?></label>
                                    <div style="display: flex; align-items: center;">
                                        <input type="datetime-local" id="edit_log_start_time_editable" name="edit_log_start_time" style="flex-grow: 1;">
                                        <button type="button" id="set_start_time_now" class="button" style="margin-left: 10px;"><?php esc_html_e('Now', 'operations-organizer'); ?></button>
                                    </div>
                                     <p class="description"><?php esc_html_e('Adjust start time if necessary. Ensure this is accurate as it affects duration and derived metrics.', 'operations-organizer'); ?></p>
                                </div>
                                
                                <div class="form-field">
                                    <label for="edit_log_end_time" id="edit_log_end_time_label"><?php esc_html_e('End Time', 'operations-organizer'); ?></label>
                                    <div style="display: flex; align-items: center;">
                                        <input type="datetime-local" id="edit_log_end_time" name="edit_log_end_time" style="flex-grow: 1;">
                                        <button type="button" id="set_end_time_now" class="button" style="margin-left: 10px;"><?php esc_html_e('Now', 'operations-organizer'); ?></button>
                                    </div>
                                    <div class="form-description" id="edit_log_end_time_description" style="display:none;">
                                        <?php esc_html_e('Leave blank to keep job running, or set date/time to stop the job.', 'operations-organizer'); ?>
                                    </div>
                                </div>

                                <!-- Dynamic KPI fields will be injected here -->
                                <div id="edit-log-dynamic-kpi-fields" class="form-field" style="padding-top:10px; margin-top:15px; border-top: 1px solid #eee;">
                                    <!-- Fields are generated by JS -->
                                </div>
                                <!-- End Dynamic KPI Fields -->
                                
                                <div class="form-field">
                                    <label for="edit_log_notes"><?php esc_html_e('Notes', 'operations-organizer'); ?></label>
                                    <textarea id="edit_log_notes" name="edit_log_notes" rows="3"></textarea>
                                </div>
                                
                                <div class="form-field">
                                    <button type="submit" id="save_as_running" class="button button-primary" style="display:none;"><?php esc_html_e('Save Changes (Keep Running)', 'operations-organizer'); ?></button>
                                    <button type="submit" id="save_as_completed" class="button button-primary" style="display:none;"><?php esc_html_e('Save Changes & Stop Job', 'operations-organizer'); ?></button>
                                    <button type="submit" id="save_changes" class="button button-primary" style="display:none;"><?php esc_html_e('Save Changes', 'operations-organizer'); ?></button>
                                    <button type="button" class="button oo-modal-cancel"><?php esc_html_e('Cancel', 'operations-organizer'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
            
                    <!-- Add modal dialog for delete confirmation -->
                    <div id="delete-log-modal" class="oo-modal">
                        <div class="oo-modal-content">
                            <span class="oo-modal-close">&times;</span>
                            <h2><?php esc_html_e('Confirm Deletion', 'operations-organizer'); ?></h2>
                            <p><?php esc_html_e('Are you sure you want to delete this job log?', 'operations-organizer'); ?></p>
                            <form id="delete-log-form">
                                <input type="hidden" id="delete_log_id" name="log_id">
                                <input type="hidden" name="action" value="oo_delete_job_log">
                                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('oo_delete_log_nonce'); ?>">
                                
                                <div class="form-field">
                                    <button type="submit" class="button button-primary"><?php esc_html_e('Delete', 'operations-organizer'); ?></button>
                                    <button type="button" class="button oo-modal-cancel"><?php esc_html_e('Cancel', 'operations-organizer'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        <?php elseif ( $active_tab == 'phase_kpi_settings' ) : ?>
            <div id="phase-kpi-settings-content">
                <h3><?php printf(esc_html__('Phase & KPI Settings for %s Stream', 'operations-organizer'), esc_html($current_stream_name)); ?></h3>
                
                <h4><?php esc_html_e('Phases in this Stream', 'operations-organizer'); ?></h4>
                <button type="button" id="openAddOOPhaseModalBtn-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="page-title-action">
                    <?php esc_html_e('Add New Phase to this Stream', 'operations-organizer'); ?>
                </button>
                <?php 
                $current_stream_phases_for_table = array(); 
                if (isset($current_stream_id)) { // Ensure current_stream_id is set
                    // Always fetch fresh, unfiltered (by active status) phases for this specific table display
                    oo_log('[Content Stream Page - Manage Tab] Fetching all phases (active and inactive) for stream ID: ' . $current_stream_id . ' for table display.', 'ContentStreamPage');
                    $current_stream_phases_for_table = OO_DB::get_phases(array(
                        'stream_id' => $current_stream_id, 
                        'is_active' => null, // Explicitly get all statuses
                        'orderby' => 'order_in_stream', 
                        'order' => 'ASC', 
                        'number' => -1
                    ));
                }
                oo_log('[Content Stream Page - Manage Tab] Filtered Phases for Table: ' . count($current_stream_phases_for_table), 'ContentStreamPage');
                
                // For pagination, we'd need a count specific to this stream as well.
                // $total_stream_phases = OO_DB::get_phases_count(array('stream_id' => $current_stream_id));
                // For now, no pagination on this sub-tab table.
                ?>
                <table class="wp-list-table widefat fixed striped table-view-list phases" style="margin-top:20px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Phase Name', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Slug', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Description', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Order', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Includes KPIs', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $current_stream_phases_for_table ) ) : ?>
                            <?php foreach ( $current_stream_phases_for_table as $phase ) : ?>
                                <tr class="<?php echo $phase->is_active ? 'active' : 'inactive'; ?>">
                                    <td><strong><button type="button" class="button-link oo-edit-phase-button-stream" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>" data-phase-name="<?php echo esc_attr( $phase->phase_name ); ?>"><?php echo esc_html( $phase->phase_name ); ?></button></strong></td>
                                    <td><code><?php echo esc_html( $phase->phase_slug ); ?></code></td>
                                    <td><?php echo esc_html( $phase->phase_description ); ?></td>
                                    <td><?php echo intval( $phase->order_in_stream ); ?></td>
                                    <td><?php echo $phase->includes_kpi ? 'Yes' : 'No'; ?></td>
                                    <td>
                                        <?php 
                                        echo $phase->is_active ? __( 'Active', 'operations-organizer' ) : __( 'Inactive', 'operations-organizer' );
                                        ?>
                                    </td>
                                    <td class="actions column-actions">
                                        <button type="button" class="button-secondary oo-edit-phase-button-stream" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>"><?php esc_html_e( 'Edit', 'operations-organizer' ); ?></button>
                                        <?php 
                                        $toggle_nonce = wp_create_nonce('oo_toggle_phase_status_nonce_' . $phase->phase_id);
                                        if ($phase->is_active) {
                                            echo '<button type="button" class="button-secondary oo-toggle-status-phase-button-stream oo-deactivate" data-phase-id="' . esc_attr($phase->phase_id) . '" data-new-status="0" data-nonce="' . esc_attr($toggle_nonce) . '">' . esc_html__('Deactivate', 'operations-organizer') . '</button>';
                                        } else {
                                            echo '<button type="button" class="button-secondary oo-toggle-status-phase-button-stream oo-activate" data-phase-id="' . esc_attr($phase->phase_id) . '" data-new-status="1" data-nonce="' . esc_attr($toggle_nonce) . '">' . esc_html__('Activate', 'operations-organizer') . '</button>';
                                        }
                                        ?>
                                        | <a href="#" class="oo-delete-phase-button-stream" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>" style="color:#b32d2e; text-decoration: none;"><?php esc_html_e( 'Delete', 'operations-organizer' ); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="7"><?php esc_html_e( 'No phases found for this stream.', 'operations-organizer' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h4 style="margin-top: 40px;"><?php esc_html_e('KPI Measures in this Stream', 'operations-organizer'); ?></h4>
                <button type="button" id="openAddKpiMeasureModalBtn-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="page-title-action">
                    <?php esc_html_e('Add New KPI Measure to this Stream', 'operations-organizer'); ?>
                </button>
                <?php
                $stream_kpi_measures = array();
                if (isset($current_stream_id)) {
                    $kpis_from_db = OO_DB::get_kpi_measures_for_stream($current_stream_id, array('is_active' => 1));
                    if (!empty($kpis_from_db)) {
                        foreach($kpis_from_db as $kpi) {
                            $phase_names = OO_DB::get_phase_names_for_kpi_in_stream($kpi->kpi_measure_id, $current_stream_id);
                            $kpi->used_in_phases_in_stream = !empty($phase_names) ? implode(', ', $phase_names) : 'N/A';
                            $stream_kpi_measures[] = $kpi;
                        }
                    }
                }
                oo_log('[Content Stream Page - KPI Tab] Fetched KPI Measures for Stream ' . $current_stream_id . ': ' . count($stream_kpi_measures), 'ContentStreamPageKPI');
                ?>
                <table class="wp-list-table widefat fixed striped table-view-list kpi-measures-stream" style="margin-top:20px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Measure Name', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Key', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Phases Used In (This Stream)', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Unit Type', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="kpi-measures-list-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                        <?php if ( ! empty( $stream_kpi_measures ) ) : ?>
                            <?php foreach ( $stream_kpi_measures as $kpi_measure ) : ?>
                                <tr class="kpi-measure-row-<?php echo esc_attr($kpi_measure->kpi_measure_id); ?> <?php echo $kpi_measure->is_active ? 'active' : 'inactive'; ?>">
                                    <td><strong><button type="button" class="button-link oo-edit-kpi-measure-stream" data-kpi-measure-id="<?php echo esc_attr( $kpi_measure->kpi_measure_id ); ?>"><?php echo esc_html( $kpi_measure->measure_name ); ?></button></strong></td>
                                    <td><code><?php echo esc_html( $kpi_measure->measure_key ); ?></code></td>
                                    <td><?php echo esc_html( $kpi_measure->used_in_phases_in_stream ); ?></td>
                                    <td><?php echo esc_html( ucfirst( $kpi_measure->unit_type ) ); ?></td>
                                    <td>
                                        <?php echo $kpi_measure->is_active ? __('Active', 'operations-organizer') : __('Inactive', 'operations-organizer'); ?>
                                    </td>
                                    <td class="actions column-actions">
                                        <button type="button" class="button-secondary oo-edit-kpi-measure-stream" data-kpi-measure-id="<?php echo esc_attr( $kpi_measure->kpi_measure_id ); ?>"><?php esc_html_e('Edit', 'operations-organizer'); ?></button>
                                        <?php
                                        // Nonce for toggle status will be generated and added to oo_data if not present
                                        $toggle_action_text = $kpi_measure->is_active ? __('Deactivate', 'operations-organizer') : __('Activate', 'operations-organizer');
                                        $new_status_val = $kpi_measure->is_active ? 0 : 1;
                                        ?>
                                        <button type="button" 
                                                class="button-secondary oo-toggle-kpi-measure-status-stream" 
                                                data-kpi-measure-id="<?php echo esc_attr($kpi_measure->kpi_measure_id); ?>" 
                                                data-new-status="<?php echo esc_attr($new_status_val); ?>"
                                                data-nonce-action="oo_toggle_kpi_measure_status_<?php echo esc_attr($kpi_measure->kpi_measure_id); ?>">
                                            <?php echo esc_html($toggle_action_text); ?>
                                        </button>
                                        | <a href="#" class="oo-delete-kpi-measure-stream" data-kpi-measure-id="<?php echo esc_attr( $kpi_measure->kpi_measure_id ); ?>" data-nonce-action="oo_delete_kpi_measure_<?php echo esc_attr($kpi_measure->kpi_measure_id); ?>" style="color:#b32d2e; text-decoration: none;"><?php esc_html_e('Delete', 'operations-organizer'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="6"><?php esc_html_e('No KPI measures found specifically linked to phases in this stream yet, or no active KPI Measures defined globally.', 'operations-organizer'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h4 style="margin-top: 40px;"><?php esc_html_e('Derived KPI Definitions relevant to this Stream', 'operations-organizer'); ?></h4>
                <button type="button" id="openAddDerivedKpiModalBtn-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="page-title-action">
                    <?php esc_html_e('Add New Derived KPI Definition', 'operations-organizer'); ?>
                </button>
                <?php
                $stream_derived_kpis = array();
                $stream_kpi_measure_ids = array();
                if (!empty($stream_kpi_measures)) { // $stream_kpi_measures is populated for the Primary KPI table above
                    $stream_kpi_measure_ids = wp_list_pluck($stream_kpi_measures, 'kpi_measure_id');
                }

                if (!empty($stream_kpi_measure_ids)) {
                    $all_derived_kpis = OO_DB::get_derived_kpi_definitions(array('is_active' => null, 'number' => -1)); 
                    foreach ($all_derived_kpis as $dkpi) {
                        if (in_array($dkpi->primary_kpi_measure_id, $stream_kpi_measure_ids)) {
                            // For display, we need the primary KPI measure name
                            $primary_kpi = OO_DB::get_kpi_measure($dkpi->primary_kpi_measure_id);
                            $dkpi->primary_kpi_measure_name = $primary_kpi ? esc_html($primary_kpi->measure_name) : 'Unknown KPI';
                            
                            // Fetch Secondary KPI Name if applicable
                            $dkpi->secondary_kpi_measure_name = 'N/A';
                            if ($dkpi->calculation_type === 'ratio_to_kpi' && !empty($dkpi->secondary_kpi_measure_id)) {
                                $secondary_kpi = OO_DB::get_kpi_measure($dkpi->secondary_kpi_measure_id);
                                $dkpi->secondary_kpi_measure_name = $secondary_kpi ? esc_html($secondary_kpi->measure_name) : 'Unknown Secondary KPI';
                            }
                            $stream_derived_kpis[] = $dkpi;
                        }
                    }
                }
                oo_log('[Content Stream Page - Derived KPI Tab] Fetched Derived KPIs for Stream ' . $current_stream_id . ': ' . count($stream_derived_kpis), 'ContentStreamPageDerivedKPI');
                ?>
                <table class="wp-list-table widefat fixed striped table-view-list derived-kpi-definitions-stream" style="margin-top:20px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Definition Name', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Primary KPI', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Calculation Type', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Secondary KPI (if Ratio)', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                            <th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="derived-kpi-definitions-list-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                        <?php if ( ! empty( $stream_derived_kpis ) ) : ?>
                            <?php foreach ( $stream_derived_kpis as $dkpi ) : ?>
                                <tr class="derived-kpi-row-<?php echo esc_attr($dkpi->derived_definition_id); ?> <?php echo $dkpi->is_active ? 'active' : 'inactive'; ?>">
                                    <td><strong><button type="button" class="button-link oo-edit-derived-kpi-stream" data-derived-kpi-id="<?php echo esc_attr( $dkpi->derived_definition_id ); ?>"><?php echo esc_html( $dkpi->definition_name ); ?></button></strong></td>
                                    <td><?php echo $dkpi->primary_kpi_measure_name; // Already escaped ?></td>
                                    <td><?php echo esc_html( ucfirst( str_replace('_', ' ', $dkpi->calculation_type ) ) ); ?></td>
                                    <td><?php echo $dkpi->secondary_kpi_measure_name; // Escaped during fetch or N/A ?></td>
                                    <td><?php echo $dkpi->is_active ? __('Active', 'operations-organizer') : __('Inactive', 'operations-organizer'); ?></td>
                                    <td class="actions column-actions">
                                        <button type="button" class="button-secondary oo-edit-derived-kpi-stream" data-derived-kpi-id="<?php echo esc_attr( $dkpi->derived_definition_id ); ?>"><?php esc_html_e('Edit', 'operations-organizer'); ?></button>
                                        <?php
                                        $dkpi_toggle_text = $dkpi->is_active ? __('Deactivate', 'operations-organizer') : __('Activate', 'operations-organizer');
                                        $dkpi_new_status = $dkpi->is_active ? 0 : 1;
                                        ?>
                                        <button type="button" 
                                                class="button-secondary oo-toggle-derived-kpi-status-stream" 
                                                data-derived-kpi-id="<?php echo esc_attr($dkpi->derived_definition_id); ?>" 
                                                data-new-status="<?php echo esc_attr($dkpi_new_status); ?>"
                                                data-nonce-action="oo_toggle_derived_kpi_status_<?php echo esc_attr($dkpi->derived_definition_id); ?>">
                                            <?php echo esc_html($dkpi_toggle_text); ?>
                                        </button>
                                        | <a href="#" class="oo-delete-derived-kpi-stream" data-derived-kpi-id="<?php echo esc_attr( $dkpi->derived_definition_id ); ?>" data-nonce-action="oo_delete_derived_kpi_<?php echo esc_attr($dkpi->derived_definition_id); ?>" style="color:#b32d2e; text-decoration: none;"><?php esc_html_e('Delete', 'operations-organizer'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="6"><?php esc_html_e('No Derived KPI definitions found relevant to this stream, or their primary KPIs are not linked to any phase in this stream.', 'operations-organizer'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        <?php endif; ?>
    </div>

</div> 

<!-- Add Phase Modal for Stream Page -->
<div id="addOOPhaseModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;">
    <div class="oo-modal-content">
        <span class="oo-modal-close">&times;</span>
        <h2><?php esc_html_e( 'Add New Phase to', 'operations-organizer' ); ?> <?php echo esc_html($current_stream_name); ?></h2>
        <form id="oo-add-phase-form-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
            <?php wp_nonce_field( 'oo_add_phase_nonce', 'oo_add_phase_nonce' ); // Can reuse existing nonce for add_phase action ?>
            <input type="hidden" name="stream_type_id" value="<?php echo esc_attr($current_stream_id); ?>" />
            <input type="hidden" id="add_phase_slug-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="phase_slug" value="">
            <table class="form-table oo-form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Stream Type', 'operations-organizer' ); ?></th>
                    <td><strong><?php echo esc_html($current_stream_name); ?></strong></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="add_phase_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Phase Name', 'operations-organizer' ); ?></label></th>
                    <td><input type="text" id="add_phase_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="phase_name" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="add_phase_description-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Description', 'operations-organizer' ); ?></label></th>
                    <td><textarea id="add_phase_description-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="phase_description"></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="add_sort_order-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Sort Order', 'operations-organizer' ); ?></label></th>
                    <td><input type="number" id="add_sort_order-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="sort_order" value="0" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Operations KPIs', 'operations-organizer' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" id="add_includes_kpi-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="includes_kpi" value="1" checked>
                            <?php esc_html_e( 'Includes operations KPIs', 'operations-organizer' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e('If checked, this phase will appear in the stream page and users can input tracking data.', 'operations-organizer'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Add Phase', 'operations-organizer' ), 'primary', 'submit_add_phase-stream-' . $current_stream_tab_slug ); ?>
        </form>
    </div>
</div>

<!-- Edit Phase Modal for Stream Page - To be enhanced -->
<div id="editOOPhaseModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;">
    <div class="oo-modal-content" style="width: 800px; max-width: 90%;">
        <span class="oo-modal-close">&times;</span>
        <h2><?php esc_html_e( 'Edit Phase for', 'operations-organizer' ); ?> <?php echo esc_html($current_stream_name); ?>: <span id="editModalPhaseNameDisplay-<?php echo esc_attr($current_stream_tab_slug); ?>"></span></h2>
        <form id="oo-edit-phase-form-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
            <?php wp_nonce_field( 'oo_edit_phase_nonce', 'oo_edit_phase_nonce' ); ?>
            <input type="hidden" id="edit_phase_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="edit_phase_id" value="" />
            <input type="hidden" id="edit_modal_phase_slug-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="edit_phase_slug" value="" />
            <input type="hidden" name="edit_stream_type_id" value="<?php echo esc_attr($current_stream_id); ?>" /> 
            
            <table class="form-table oo-form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Stream Type', 'operations-organizer' ); ?></th>
                    <td><strong><?php echo esc_html($current_stream_name); ?></strong></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_modal_phase_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Phase Name', 'operations-organizer' ); ?></label></th>
                    <td><input type="text" id="edit_modal_phase_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="edit_phase_name" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_modal_phase_description-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Description', 'operations-organizer' ); ?></label></th>
                    <td><textarea id="edit_modal_phase_description-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="edit_phase_description"></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_modal_sort_order-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Sort Order', 'operations-organizer' ); ?></label></th>
                    <td><input type="number" id="edit_modal_sort_order-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="edit_sort_order" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Operations KPIs', 'operations-organizer' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" id="edit_modal_includes_kpi-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="edit_includes_kpi" value="1">
                            <?php esc_html_e( 'Includes operations KPIs', 'operations-organizer' ); ?>
                        </label>
                    </td>
                </tr>
                <!-- Section for Managing Linked KPIs (Align with global phase edit modal) -->
                <tr valign="top" class="oo-kpi-linking-section-stream">
                    <th scope="row"><?php esc_html_e( 'Linked KPI Measures', 'operations-organizer' ); ?></th>
                    <td>
                        <div id="linked-kpi-measures-list-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                            <!-- Linked KPIs table will be loaded here by JavaScript -->
                            <p><?php esc_html_e( 'Loading linked KPIs...', 'operations-organizer' ); ?></p>
                        </div>
                        <div style="margin-top: 15px;">
                            <label for="add_kpi_measure_to_phase-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Add KPI Measure to this Phase:', 'operations-organizer' ); ?></label>
                            <select id="add_kpi_measure_to_phase-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="add_kpi_measure_to_phase_stream" style="width: 70%;">
                                <option value=""><?php esc_html_e( '-- Select KPI Measure --', 'operations-organizer' ); ?></option>
                                <?php
                                // This will be populated by JS to show *available (not yet linked)* KPIs for the current phase
                                // For initial setup, it's empty. JS will fill it based on all active KPIs minus already linked ones.
                                ?>
                            </select>
                            <button type="button" id="btn-add-kpi-to-phase-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="button"><?php esc_html_e( 'Add Selected KPI', 'operations-organizer' ); ?></button>
                        </div>
                        <p class="description"><?php esc_html_e( 'Select a predefined KPI measure and add it to this phase. You can then set if it\'s mandatory and its display order.', 'operations-organizer' ); ?></p>
                    </td>
                </tr>
                <!-- End Section for Managing Linked KPIs -->
            </table>
            <?php submit_button( __( 'Save Phase Changes', 'operations-organizer' ), 'primary', 'submit_edit_phase-stream-' . $current_stream_tab_slug ); ?>
            <!-- Delete button for phase is now in the table row, not modal -->
        </form>
    </div>
</div>

<!-- Add KPI Measure Modal for Stream Page -->
<div id="addKpiMeasureModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;">
    <div class="oo-modal-content">
        <span class="oo-modal-close">&times;</span>
        <h2><?php esc_html_e( 'Add New KPI Measure (Stream Specific Context)', 'operations-organizer' ); ?></h2>
        <form id="oo-add-kpi-measure-form-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
            <?php // Nonce will be added via JS from oo_data ?>
            <input type="hidden" name="oo_action" value="add_kpi_measure">
            <input type="hidden" name="context" value="stream_page">
            <input type="hidden" name="stream_id_context" value="<?php echo esc_attr($current_stream_id); ?>">


            <div class="form-field form-required">
                <label for="add_kpi_measure_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Measure Name', 'operations-organizer' ); ?></label>
                <input type="text" name="measure_name" id="add_kpi_measure_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required>
                <p><?php esc_html_e( 'The human-readable name for this KPI (e.g., "Boxes Packed", "Items Scanned").', 'operations-organizer' ); ?></p>
            </div>

            <div class="form-field form-required" style="display:none;"> <!-- Hiding the measure key field -->
                <label for="add_kpi_measure_key-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Measure Key', 'operations-organizer' ); ?></label>
                <input type="text" name="measure_key" id="add_kpi_measure_key-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required>
                <p><?php esc_html_e( 'A unique key for this KPI, used internally (e.g., "boxes_packed", "items_scanned"). Lowercase, underscores, no spaces. Cannot be changed after creation.', 'operations-organizer' ); ?></p>
            </div>

            <div class="form-field">
                <label for="add_kpi_unit_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Unit Type', 'operations-organizer' ); ?></label>
                <select name="unit_type" id="add_kpi_unit_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                    <?php 
                    $unit_types = array( 'integer', 'decimal', 'text', 'boolean' ); // Define available unit types
                    foreach ( $unit_types as $type ) : ?>
                        <option value="<?php echo esc_attr( $type ); ?>" <?php selected( 'integer', $type ); ?>>
                            <?php echo esc_html( ucfirst( $type ) ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p><?php esc_html_e( 'The type of data this KPI represents (e.g., Integer for counts, Decimal for amounts, Text for notes).', 'operations-organizer' ); ?></p>
            </div>
            
            <div class="form-field">
                <label for="add_kpi_is_active-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                    <input type="checkbox" name="is_active" id="add_kpi_is_active-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" value="1" checked>
                    <?php esc_html_e( 'Active', 'operations-organizer' ); ?>
                </label>
                <p><?php esc_html_e( 'Inactive measures will not be available for new phase assignments.', 'operations-organizer' ); ?></p>
            </div>

            <div class="form-field form-required kpi-phase-linking-section-stream">
                <label><?php printf(esc_html__('Link to Phases in %s (at least one required)', 'operations-organizer'), esc_html($current_stream_name)); ?></label>
                <div id="add-kpi-link-to-phases-list-<?php echo esc_attr($current_stream_tab_slug); ?>" class="phase-checkbox-group" style="max-height: 150px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 5px;">
                    <?php
                    // Fetch phases for the current stream to populate checkboxes
                    // This assumes $current_stream_id is available and correct here.
                    $phases_in_current_stream = array();
                    if (isset($current_stream_id)) {
                        $phases_in_current_stream = OO_DB::get_phases(array(
                            'stream_id' => $current_stream_id,
                            'is_active' => 1, // Only offer to link to active phases
                            'orderby' => 'order_in_stream',
                            'order' => 'ASC',
                            'number' => -1
                        ));
                    }
                    if (!empty($phases_in_current_stream)) {
                        foreach ($phases_in_current_stream as $phase) {
                            echo '<label style="display: block;"><input type="checkbox" name="link_to_phases[]" value="' . esc_attr($phase->phase_id) . '"> ' . esc_html($phase->phase_name) . '</label>';
                        }
                    } else {
                        echo '<p>' . esc_html__('No active phases found in this stream to link to.', 'operations-organizer') . '</p>';
                    }
                    ?>
                </div>
                <p class="description"><?php esc_html_e('This KPI measure will be associated with the selected phases in the current stream.', 'operations-organizer'); ?></p>
            </div>

            <?php submit_button( __( 'Add KPI Measure', 'operations-organizer' ), 'primary', 'submit_add_kpi_measure-stream-' . $current_stream_tab_slug ); ?>
        </form>
    </div>
</div>

<!-- Edit KPI Measure Modal for Stream Page -->
<div id="editKpiMeasureModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;">
    <div class="oo-modal-content">
        <span class="oo-modal-close">&times;</span>
        <h2><?php esc_html_e( 'Edit KPI Measure (Stream Specific Context)', 'operations-organizer' ); ?>: <span id="editKpiMeasureNameDisplay-<?php echo esc_attr($current_stream_tab_slug); ?>"></span></h2>
        <form id="oo-edit-kpi-measure-form-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
            <?php // Nonce will be added via JS from oo_data ?>
            <input type="hidden" name="oo_action" value="edit_kpi_measure">
            <input type="hidden" name="context" value="stream_page">
            <input type="hidden" name="stream_id_context" value="<?php echo esc_attr($current_stream_id); ?>">
            <input type="hidden" id="edit_kpi_measure_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="kpi_measure_id" value="">

            <div class="form-field form-required">
                <label for="edit_kpi_measure_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Measure Name', 'operations-organizer' ); ?></label>
                <input type="text" name="measure_name" id="edit_kpi_measure_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required>
                <p><?php esc_html_e( 'The human-readable name for this KPI.', 'operations-organizer' ); ?></p>
            </div>

            <div class="form-field form-required">
                <label for="edit_kpi_measure_key-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Measure Key', 'operations-organizer' ); ?></label>
                <input type="text" name="measure_key" id="edit_kpi_measure_key-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" readonly>
                <p><?php esc_html_e( 'The unique key for this KPI. Cannot be changed after creation.', 'operations-organizer' ); ?></p>
            </div>

            <div class="form-field">
                <label for="edit_kpi_unit_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Unit Type', 'operations-organizer' ); ?></label>
                <select name="unit_type" id="edit_kpi_unit_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                    <?php 
                    // $unit_types is already defined above for the Add modal, can reuse
                    foreach ( $unit_types as $type ) : ?>
                        <option value="<?php echo esc_attr( $type ); ?>">
                            <?php echo esc_html( ucfirst( $type ) ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p><?php esc_html_e( 'The type of data this KPI represents.', 'operations-organizer' ); ?></p>
            </div>
            
            <div class="form-field">
                <label for="edit_kpi_is_active-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                    <input type="checkbox" name="is_active" id="edit_kpi_is_active-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" value="1">
                    <?php esc_html_e( 'Active', 'operations-organizer' ); ?>
                </label>
                <p><?php esc_html_e( 'Inactive measures will not be available for new phase assignments.', 'operations-organizer' ); ?></p>
            </div>

            <div class="form-field kpi-phase-linking-section-stream">
                <label><?php printf(esc_html__('Linked to Phases in %s', 'operations-organizer'), esc_html($current_stream_name)); ?></label>
                <div id="edit-kpi-link-to-phases-list-<?php echo esc_attr($current_stream_tab_slug); ?>" class="phase-checkbox-group" style="max-height: 150px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 5px;">
                    <!-- Checkboxes will be populated by JavaScript -->
                    <p><?php esc_html_e('Loading phases...', 'operations-organizer'); ?></p>
                </div>
                <p class="description"><?php esc_html_e('Select phases in the current stream to associate with this KPI measure.', 'operations-organizer'); ?></p>
            </div>

            <?php submit_button( __( 'Save KPI Measure Changes', 'operations-organizer' ), 'primary', 'submit_edit_kpi_measure-stream-' . $current_stream_tab_slug ); ?>
        </form>
    </div>
</div>

<!-- Add Derived KPI Modal for Stream Page -->
<div id="addDerivedKpiModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;">
    <div class="oo-modal-content">
        <span class="oo-modal-close">&times;</span>
        <h2><?php esc_html_e( 'Add New Derived KPI Definition (Stream Context)', 'operations-organizer' ); ?></h2>
        <form id="oo-add-derived-kpi-form-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
            <input type="hidden" name="oo_action" value="add_derived_kpi_definition"> <!-- Will be an AJAX action -->
            <input type="hidden" name="context" value="stream_page">
            <input type="hidden" name="stream_id_context" value="<?php echo esc_attr($current_stream_id); ?>">

            <div class="form-field form-required">
                <label for="add_derived_definition_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Definition Name', 'operations-organizer' ); ?></label>
                <input type="text" name="derived_definition_name" id="add_derived_definition_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required>
                <p><?php esc_html_e( 'A descriptive name (e.g., "Items per Hour", "Cost per Item").', 'operations-organizer' ); ?></p>
            </div>

            <div class="form-field form-required">
                <label for="add_derived_primary_kpi_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Primary KPI Measure', 'operations-organizer' ); ?></label>
                <select name="primary_kpi_measure_id" id="add_derived_primary_kpi_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required>
                    <option value=""><?php esc_html_e( '-- Select Primary KPI --', 'operations-organizer' ); ?></option>
                    <?php 
                    if (!empty($stream_kpi_measures)) {
                        foreach ($stream_kpi_measures as $kpi) {
                            echo '<option value="' . esc_attr($kpi->kpi_measure_id) . '" data-unit-type="' . esc_attr($kpi->unit_type) . '">' . esc_html($kpi->measure_name) . ' (' . esc_html($kpi->unit_type) . ')</option>';
                        }
                    } else {
                        echo '<option value="" disabled>' . esc_html__('No KPIs available in this stream. Add primary KPIs first.', 'operations-organizer') . '</option>';
                    }
                    ?>
                </select>
                <p><?php esc_html_e( 'Select the main KPI this calculation is based on. Must be a KPI present in this stream.', 'operations-organizer' ); ?></p>
            </div>

            <div class="form-field form-required">
                <label for="add_derived_calculation_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Calculation Type', 'operations-organizer' ); ?></label>
                <select name="derived_calculation_type" id="add_derived_calculation_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required>
                    <!-- Options populated by JS based on primary KPI unit type -->
                </select>
                 <p><?php esc_html_e( 'The method of calculation (e.g., Rate per Time, Ratio to another KPI).', 'operations-organizer' ); ?></p>
            </div>

            <div class="form-field derived-secondary-kpi-field-stream" style="display:none;">
                <label for="add_derived_secondary_kpi_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Secondary KPI (for Ratio)', 'operations-organizer' ); ?></label>
                <select name="derived_secondary_kpi_measure_id" id="add_derived_secondary_kpi_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                    <option value=""><?php esc_html_e( '-- Select Secondary KPI --', 'operations-organizer' ); ?></option>
                     <?php 
                    // Options populated by JS: all active KPIs in the system, excluding the chosen primary KPI.
                    ?> 
                </select>
            </div>

            <div class="form-field derived-time-unit-field-stream" style="display:none;">
                <label for="add_derived_time_unit-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Time Unit (for Rate)', 'operations-organizer' ); ?></label>
                <select name="derived_time_unit_for_rate" id="add_derived_time_unit-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                    <option value="hour"><?php esc_html_e( 'Hour', 'operations-organizer' ); ?></option>
                    <option value="minute"><?php esc_html_e( 'Minute', 'operations-organizer' ); ?></option>
                    <option value="day"><?php esc_html_e( 'Day', 'operations-organizer' ); ?></option>
                </select>
            </div>
           
            <div class="form-field">
                <label for="add_derived_output_description-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Output Description (Optional)', 'operations-organizer' ); ?></label>
                <textarea name="derived_output_description" id="add_derived_output_description-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" rows="2"></textarea>
                <p><?php esc_html_e( 'Briefly describe what this calculation represents or its expected unit (e.g., "items/hr", "$/item").', 'operations-organizer' ); ?></p>
            </div>

             <div class="form-field">
                <label for="add_derived_is_active-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                    <input type="checkbox" name="derived_is_active" id="add_derived_is_active-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" value="1" checked>
                    <?php esc_html_e( 'Active', 'operations-organizer' ); ?>
                </label>
            </div>

            <?php submit_button( __( 'Add Derived KPI', 'operations-organizer' ), 'primary', 'submit_add_derived_kpi-stream-' . $current_stream_tab_slug ); ?>
        </form>
    </div>
</div>

<!-- Edit Derived KPI Modal for Stream Page -->
<div id="editDerivedKpiModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;">
    <div class="oo-modal-content">
        <span class="oo-modal-close">&times;</span>
        <h2><?php esc_html_e( 'Edit Derived KPI Definition (Stream Context)', 'operations-organizer' ); ?>: <span id="editDerivedKpiNameDisplay-<?php echo esc_attr($current_stream_tab_slug); ?>"></span></h2>
        <form id="oo-edit-derived-kpi-form-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
            <input type="hidden" name="oo_action" value="update_derived_kpi_definition">
            <input type="hidden" name="context" value="stream_page">
            <input type="hidden" name="stream_id_context" value="<?php echo esc_attr($current_stream_id); ?>">
            <input type="hidden" id="edit_derived_definition_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="derived_definition_id" value="">
            
            <div class="form-field form-required">
                <label for="edit_derived_definition_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Definition Name', 'operations-organizer' ); ?></label>
                <input type="text" name="derived_definition_name" id="edit_derived_definition_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required>
            </div>

            <div class="form-field form-required">
                <label><?php esc_html_e( 'Primary KPI Measure', 'operations-organizer' ); ?></label>
                <span id="edit_derived_primary_kpi_name_display-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"></span> <!-- Display only, not editable -->
                <input type="hidden" id="edit_derived_primary_kpi_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="primary_kpi_measure_id" value="">
                <input type="hidden" id="edit_derived_primary_kpi_unit_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" value="">
            </div>

            <div class="form-field form-required">
                <label for="edit_derived_calculation_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Calculation Type', 'operations-organizer' ); ?></label>
                <select name="derived_calculation_type" id="edit_derived_calculation_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required>
                    <!-- Options populated by JS -->
                </select>
            </div>

            <div class="form-field derived-secondary-kpi-field-stream" style="display:none;">
                <label for="edit_derived_secondary_kpi_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Secondary KPI (for Ratio)', 'operations-organizer' ); ?></label>
                <select name="derived_secondary_kpi_measure_id" id="edit_derived_secondary_kpi_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                    <option value=""><?php esc_html_e( '-- Select Secondary KPI --', 'operations-organizer' ); ?></option>
                     <?php 
                    // Options populated by JS
                    ?> 
                </select>
            </div>

            <div class="form-field derived-time-unit-field-stream" style="display:none;">
                <label for="edit_derived_time_unit-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Time Unit (for Rate)', 'operations-organizer' ); ?></label>
                <select name="derived_time_unit_for_rate" id="edit_derived_time_unit-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                    <option value="hour"><?php esc_html_e( 'Hour', 'operations-organizer' ); ?></option>
                    <option value="minute"><?php esc_html_e( 'Minute', 'operations-organizer' ); ?></option>
                    <option value="day"><?php esc_html_e( 'Day', 'operations-organizer' ); ?></option>
                </select>
            </div>
           
            <div class="form-field">
                <label for="edit_derived_output_description-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Output Description (Optional)', 'operations-organizer' ); ?></label>
                <textarea name="derived_output_description" id="edit_derived_output_description-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" rows="2"></textarea>
            </div>

             <div class="form-field">
                <label for="edit_derived_is_active-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
                    <input type="checkbox" name="derived_is_active" id="edit_derived_is_active-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" value="1">
                    <?php esc_html_e( 'Active', 'operations-organizer' ); ?>
                </label>
            </div>

            <?php submit_button( __( 'Save Derived KPI Changes', 'operations-organizer' ), 'primary', 'submit_edit_derived_kpi-stream-' . $current_stream_tab_slug ); ?>
        </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Helper function for escaping HTML in JS (moved to top of ready block)
    function esc_html(str) {
        if (str === null || typeof str === 'undefined') return '';
        var p = document.createElement("p");
        p.appendChild(document.createTextNode(String(str)));
        return p.innerHTML;
    }

    // JS for Quick Phase Actions in this Stream tab
    $('.oo-stream-page .oo-start-link-btn, .oo-stream-page .oo-stop-link-btn').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $row = $button.closest('.oo-phase-action-row');
        var jobNumber = $row.find('.oo-job-number-input').val();
        var phaseId = $button.data('phase-id');
        var isAdminUrl = '<?php echo admin_url("admin.php"); ?>';
        var returnTabSlug = '<?php echo esc_js($current_stream_tab_slug); ?>'; 

        if (!jobNumber) {
            alert('<?php echo esc_js(__("Please enter a Job Number first.", "operations-organizer")); ?>');
            return;
        }

        var actionPage = $button.hasClass('oo-start-link-btn') ? 'oo_start_job' : 'oo_stop_job';
        var url = isAdminUrl + '?page=' + actionPage + '&job_number=' + encodeURIComponent(jobNumber) + '&phase_id=' + encodeURIComponent(phaseId) + '&return_tab=' + returnTabSlug;
        
        window.location.href = url;
    });

    // --- Initialize Jobs Table for this Stream --- 
    var streamSlugForTable = '<?php echo esc_js($current_stream_tab_slug); ?>';
    var streamJobsTable = $('#stream-jobs-table-' + streamSlugForTable).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: oo_data.ajax_url,
            type: 'POST',
            data: function(d) {
                d.action = 'oo_get_stream_jobs'; // New AJAX action
                d.nonce = oo_data.nonce_get_stream_jobs; // New Nonce
                d.stream_id = <?php echo intval($current_stream_id); ?>;
                // Add any other specific filters for this table if needed later
            },
            dataSrc: function(json) {
                if (json.data && json.data.data) { // Check for wp_send_json_success structure
                    return json.data.data;
                }
                return json.data; // Fallback for direct array
            }
        },
        columns: [
            { data: 'job_number' },
            { data: 'client_name' },
            { data: 'overall_status' },
            { data: 'due_date' },
            {
                data: null, 
                orderable: false, 
                searchable: false,
                render: function(data, type, row) {
                    // Example action - link to job edit page or view logs
                    return '<a href="#job-' + row.job_id + '">View Details</a>'; 
                }
            }
        ],
        order: [[0, 'desc']], // Default order by Job No.
        pageLength: 10,
        language: {
            search: "<?php esc_attr_e('Search Jobs:', 'operations-organizer'); ?>",
            emptyTable: "<?php esc_attr_e('No jobs found for this stream.', 'operations-organizer'); ?>",
            zeroRecords: "<?php esc_attr_e('No matching jobs found', 'operations-organizer'); ?>",
            processing: "<?php esc_attr_e('Loading jobs...', 'operations-organizer'); ?>"
        }
    });

    // --- Phase Management for this Stream Page (NEW/MODIFIED) ---
    var streamSlug = '<?php echo esc_js($current_stream_tab_slug); ?>';
    var addPhaseModal_Stream = $('#addOOPhaseModal-stream-' + streamSlug);
    var editPhaseModal_Stream = $('#editOOPhaseModal-stream-' + streamSlug);

    // Open Add Phase Modal for Stream
    $('#openAddOOPhaseModalBtn-stream-' + streamSlug).on('click', function() {
        addPhaseModal_Stream.find('form')[0].reset();
        addPhaseModal_Stream.find('#add_phase_slug-stream-' + streamSlug).val('');
        addPhaseModal_Stream.show();
    });

    // Auto-generate slug for Add Phase Modal on Stream Page
    $('#add_phase_name-stream-' + streamSlug).on('input', function() {
        var slug = $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        $('#add_phase_slug-stream-' + streamSlug).val(slug);
    });

    // Handle Add Phase Form Submission for Stream Page
    $('#oo-add-phase-form-stream-' + streamSlug).on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('#submit_add_phase-stream-' + streamSlug); // Corrected selector
        $submitButton.prop('disabled', true).val('<?php echo esc_js(__("Adding...", "operations-organizer")); ?>');
        var formData = $form.serialize() + '&action=oo_add_phase&return_to_stream=' + streamSlug + '&return_sub_tab=phase_kpi_settings'; 

        $.post(oo_data.ajax_url, formData, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                addPhaseModal_Stream.hide();
                if(response.data && response.data.redirect_url) { window.location.href = response.data.redirect_url; } else { window.location.reload(); }
            } else {
                showNotice('error', response.data.message || 'An unknown error occurred.');
            }
        }).fail(function() {
            showNotice('error', 'Request failed. Please try again.');
        }).always(function() {
            $submitButton.prop('disabled', false).val('<?php echo esc_js(__("Add Phase", "operations-organizer")); ?>');
        });
    });

    // Handle Edit Phase Button Click on Stream Page table
    $('#phase-kpi-settings-content').on('click', '.oo-edit-phase-button-stream', function() { 
        var phaseId = $(this).data('phase-id');
        var phaseName = $(this).closest('tr').find('td:first-child .oo-edit-phase-button-stream').text().trim() || $(this).data('phase-name'); // More robust phase name fetching
        
        editPhaseModal_Stream.data('current-phase-id', phaseId); 
        editPhaseModal_Stream.find('#editModalPhaseNameDisplay-' + streamSlug).text(phaseName);

        // Clear previous KPI linking info before fetching new data
        $('#linked-kpi-measures-list-stream-' + streamSlug).html('<p><?php echo esc_js( __( "Loading linked KPIs...", "operations-organizer" ) ); ?></p>');
        $('#add_kpi_measure_to_phase-stream-' + streamSlug)
            .empty()
            .append('<option value="">' + '<?php echo esc_js( __( "-- Select KPI Measure --", "operations-organizer" ) ); ?>' + '</option>')
            .prop('disabled', true); // Disable dropdown until populated

        $.post(oo_data.ajax_url, {
            action: 'oo_get_phase',
            phase_id: phaseId,
            _ajax_nonce_get_phase: oo_data.nonce_edit_phase 
        }, function(response) {
            if (response.success) {
                editPhaseModal_Stream.find('#edit_phase_id-stream-' + streamSlug).val(response.data.phase_id);
                editPhaseModal_Stream.find('#edit_modal_phase_slug-stream-' + streamSlug).val(response.data.phase_slug);
                editPhaseModal_Stream.find('#edit_modal_phase_name-stream-' + streamSlug).val(response.data.phase_name);
                editPhaseModal_Stream.find('#edit_modal_phase_description-stream-' + streamSlug).val(response.data.phase_description);
                editPhaseModal_Stream.find('#edit_modal_sort_order-stream-' + streamSlug).val(response.data.order_in_stream);
                editPhaseModal_Stream.find('#edit_modal_includes_kpi-stream-' + streamSlug).prop('checked', parseInt(response.data.includes_kpi) === 1);
                
                // Load linked KPIs and populate the "Add KPI" dropdown
                loadAndDisplayPhaseKpis_StreamPage(phaseId, streamSlug); // Changed from loadAvailableAndLinkedKPIsForPhase_StreamPage
                
                editPhaseModal_Stream.show();
            } else {
                 showNotice('error', response.data.message || 'Could not load phase data.');
            }
        }).fail(function() { showNotice('error', 'Request to load phase data failed.'); });
    });

    // Handle Edit Phase Form Submission for Stream Page
    $('#oo-edit-phase-form-stream-' + streamSlug).on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('#submit_edit_phase-stream-' + streamSlug); // Corrected selector
        $submitButton.prop('disabled', true).val('<?php echo esc_js(__("Saving...", "operations-organizer")); ?>');
        var formData = $form.serialize() + '&action=oo_update_phase'; 
         formData += '&return_to_stream=' + streamSlug; 
         formData += '&return_sub_tab=phase_kpi_settings';

        $.post(oo_data.ajax_url, formData, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                editPhaseModal_Stream.hide();
                if(response.data && response.data.redirect_url) { window.location.href = response.data.redirect_url; } else { window.location.reload(); }
            } else {
                showNotice('error', response.data.message || 'An unknown error occurred.');
            }
        }).fail(function() {
            showNotice('error', 'Request failed. Please try again.');
        }).always(function() {
            $submitButton.prop('disabled', false).val('<?php echo esc_js(__("Save Phase Changes", "operations-organizer")); ?>');
        });
    });

    // AJAX Delete Phase from table on Stream Page
    $('#phase-kpi-settings-content').on('click', '.oo-delete-phase-button-stream', function() { 
        var phaseId = $(this).data('phase-id');
        if (!phaseId) {
            alert('Error: Phase ID not found for deletion.');
            return;
        }

        // Initial confirmation
        if (!confirm('<?php echo esc_js( __("Are you sure you want to delete this phase? Associated job logs will be checked.", "operations-organizer") ); ?>')) {
            return;
        }

        var ajaxData = {
            action: 'oo_delete_phase_ajax',
            phase_id: phaseId,
            _ajax_nonce: oo_data.nonce_delete_phase_ajax,
            return_to_stream: streamSlug,      
            return_sub_tab: 'phase_kpi_settings' 
        };

        function performDeleteRequest(data) {
            $.post(oo_data.ajax_url, data, function(response) {
            if (response.success) {
                    if (response.data && response.data.confirmation_needed) {
                        // Second confirmation needed
                        if (confirm(response.data.message)) {
                            // User confirmed to delete logs as well
                            var forceDeleteData = $.extend({}, data); // Create a new object
                            forceDeleteData.force_delete_logs = 'true';
                            performDeleteRequest(forceDeleteData); // Call recursively with force flag
                        } else {
                            // User cancelled the second confirmation
                            showNotice('info', '<?php echo esc_js( __("Phase deletion cancelled.", "operations-organizer") ); ?>');
                        }
                    } else {
                        // Deletion was successful (either no logs, or force delete was successful)
                showNotice('success', response.data.message);
                window.location.reload(); // Reload to update the phase list
                    }
            } else {
                    showNotice('error', response.data.message || '<?php echo esc_js( __("Could not delete phase.", "operations-organizer") ); ?>');
            }
        }).fail(function() {
                showNotice('error', '<?php echo esc_js( __("Request to delete phase failed.", "operations-organizer") ); ?>');
        });
        }

        performDeleteRequest(ajaxData); // Initial delete request
    });

    // Handle Toggle Status Button Click on Stream Page table (AJAX)
    $('#phase-kpi-settings-content').on('click', '.oo-toggle-status-phase-button-stream', function() { 
        var phaseId = $(this).data('phase-id');
        var newStatus = $(this).data('new-status');
        var confirmMessage = newStatus == 1 ? 
            '<?php echo esc_js(__("Are you sure you want to activate this phase?", "operations-organizer")); ?>' : 
            '<?php echo esc_js(__("Are you sure you want to deactivate this phase?", "operations-organizer")); ?>';

        if (!confirm(confirmMessage)) { return; }
        
        $.post(oo_data.ajax_url, {
            action: 'oo_toggle_phase_status', // Reuses global AJAX action
            phase_id: phaseId,
            is_active: newStatus,
            _ajax_nonce: $(this).data('nonce'),
            return_to_stream: streamSlug, // For potential PHP redirect if needed
            return_sub_tab: 'phase_kpi_settings'
        }, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                if(response.data && response.data.redirect_url) { 
                    window.location.href = response.data.redirect_url; 
                } else { 
                    window.location.reload(); // Fallback if redirect_url not in response
                }
            } else {
                showNotice('error', response.data.message || 'Could not change phase status.');
            }
        }).fail(function() { showNotice('error', 'Request to change phase status failed.'); });
    });

    // Function to load and display linked KPI measures and populate Add KPI dropdown (Stream Page Edit Modal)
    function loadAndDisplayPhaseKpis_StreamPage(phaseId, streamSlugContext) {
        var linkedKpisContainer = $('#linked-kpi-measures-list-stream-' + streamSlugContext);
        var addKpiDropdown = $('#add_kpi_measure_to_phase-stream-' + streamSlugContext);

        linkedKpisContainer.html('<p><?php echo esc_js( __( "Loading linked KPIs...", "operations-organizer" ) ); ?></p>');
        addKpiDropdown.empty().append('<option value="">' + '<?php echo esc_js( __( "-- Select KPI Measure --", "operations-organizer" ) ); ?>' + '</option>').prop('disabled', true);

        // Fetch all active KPI measures and then the phase-specific KPI links
        $.when(
            $.post(oo_data.ajax_url, { 
                action: 'oo_get_kpi_measures', 
                _ajax_nonce: oo_data.nonce_get_kpi_measures, // Assumes a general nonce for getting kpis
                is_active: 1, 
                number: -1 
            }),
            $.post(oo_data.ajax_url, { 
                action: 'oo_get_phase_kpi_links', 
                phase_id: phaseId, 
                _ajax_nonce: oo_data.nonce_get_phase_kpi_links 
            })
        ).done(function(allKpisResponse, linkedKpisResponse) {
            var allKpis = (allKpisResponse[0] && allKpisResponse[0].success) ? allKpisResponse[0].data : [];
            var linkedKpis = (linkedKpisResponse[0] && linkedKpisResponse[0].success) ? linkedKpisResponse[0].data : [];
            var linkedKpiIds = linkedKpis.map(function(link) { return link.kpi_measure_id.toString(); });

            // Populate the "Add KPI" dropdown with available (not yet linked) KPIs
            addKpiDropdown.prop('disabled', false);
            if (allKpis.length > 0) {
                var availableKpisCount = 0;
                $.each(allKpis, function(index, kpi) {
                    if (linkedKpiIds.indexOf(kpi.kpi_measure_id.toString()) === -1) {
                        addKpiDropdown.append(
                            $('<option>', { 
                                value: kpi.kpi_measure_id, 
                                text: esc_html(kpi.measure_name) + ' (' + esc_html(kpi.measure_key) + ')' 
                            })
                        );
                        availableKpisCount++;
                    }
                });
                if (availableKpisCount === 0 && addKpiDropdown.children().length <=1 ) { // Still only the default "--Select--"
                     addKpiDropdown.append('<option value="" disabled><?php echo esc_js( __( "All KPIs linked or none available", "operations-organizer" ) ); ?></option>');
                }
            } else {
                 addKpiDropdown.append('<option value="" disabled><?php echo esc_js( __( "No active KPIs available", "operations-organizer" ) ); ?></option>');
            }
            // If only the default "-- Select --" option is present, add a disabled placeholder
            if (addKpiDropdown.children('option:not([value=""])').length === 0 && addKpiDropdown.children().length === 1) {
                addKpiDropdown.append('<option value="" disabled><?php echo esc_js( __( "No KPIs to add", "operations-organizer" ) ); ?></option>');
            }

            // Display the table of currently linked KPIs
            linkedKpisContainer.empty();
            if (linkedKpis.length > 0) {
                var table = $('<table class="wp-list-table widefat striped oo-linked-kpis-table-stream"><thead><tr><th><?php echo esc_js( __("Measure Name", "operations-organizer") ); ?></th><th><?php echo esc_js( __("Mandatory", "operations-organizer") ); ?></th><th><?php echo esc_js( __("Order", "operations-organizer") ); ?></th><th><?php echo esc_js( __("Actions", "operations-organizer") ); ?></th></tr></thead><tbody></tbody></table>');
                var tbody = table.find('tbody');
                $.each(linkedKpis, function(index, link) {
                    var row = $('<tr>');
                    row.append('<td>' + esc_html(link.measure_name) + ' (<code>' + esc_html(link.measure_key) + '</code>)</td>');
                    row.append('<td><input type="checkbox" class="is-mandatory-kpi-stream" data-link-id="' + link.link_id + '" ' + (link.is_mandatory == 1 ? 'checked' : '') + '></td>');
                    row.append('<td><input type="number" class="display-order-kpi-stream" data-link-id="' + link.link_id + '" value="' + link.display_order + '" style="width: 60px;" min="0"></td>');
                    row.append('<td><button type="button" class="button button-link-delete oo-remove-kpi-link-stream" data-link-id="' + link.link_id + '"><?php echo esc_js( __("Remove", "operations-organizer") ); ?></button></td>');
                    tbody.append(row);
                });
                linkedKpisContainer.append(table);
            } else {
                linkedKpisContainer.html('<p><?php echo esc_js( __( "No KPI measures are currently linked to this phase.", "operations-organizer" ) ); ?></p>');
            }

        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX Error in loadAndDisplayPhaseKpis_StreamPage:", textStatus, errorThrown, jqXHR.responseText);
            linkedKpisContainer.html('<p><?php echo esc_js( __( "Error loading KPI data. Check browser console.", "operations-organizer" ) ); ?></p>');
            addKpiDropdown.prop('disabled', true).empty().append('<option value=""><?php echo esc_js( __( "Error loading KPIs", "operations-organizer" ) ); ?></option>');
        });
    }

    // Add Selected KPI to Phase (Stream Page)
    $('#btn-add-kpi-to-phase-stream-' + streamSlug).on('click', function() {
        var phaseId = editPhaseModal_Stream.data('current-phase-id');
        var kpiMeasureId = $('#add_kpi_measure_to_phase-stream-' + streamSlug).val();

        if (!phaseId || !kpiMeasureId) {
            alert('<?php echo esc_js( __("Please select a KPI measure to add.", "operations-organizer" ) ); ?>');
            return;
        }

        $.post(oo_data.ajax_url, {
            action: 'oo_add_phase_kpi_link',
            phase_id: phaseId,
            kpi_measure_id: kpiMeasureId,
            is_mandatory: 0, 
            display_order: 0,  
            _ajax_nonce: oo_data.nonce_manage_phase_kpi_links 
        }, function(response) {
            if (response.success) {
                showNotice('success', '<?php echo esc_js( __("KPI measure linked.", "operations-organizer" ) ); ?>');
                loadAndDisplayPhaseKpis_StreamPage(phaseId, streamSlug); // Reload KPI list and dropdown
            } else {
                showNotice('error', response.data.message || '<?php echo esc_js( __( "Could not link KPI measure.", "operations-organizer" ) ); ?>');
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js( __( "Request to link KPI measure failed.", "operations-organizer" ) ); ?>');
        });
    });

    // Remove KPI Link from Phase (Stream Page - Event Delegation)
    editPhaseModal_Stream.on('click', '.oo-remove-kpi-link-stream', function() {
        var linkId = $(this).data('link-id'); 
        var phaseId = editPhaseModal_Stream.data('current-phase-id');
        if (!confirm('<?php echo esc_js( __("Are you sure you want to remove this KPI measure from the phase?", "operations-organizer" ) ); ?>')) { return; }

        $.post(oo_data.ajax_url, {
            action: 'oo_delete_phase_kpi_link',
            link_id: linkId,
            _ajax_nonce: oo_data.nonce_manage_phase_kpi_links 
        }, function(response) {
            if (response.success) {
                showNotice('success', '<?php echo esc_js( __("KPI measure unlinked.", "operations-organizer" ) ); ?>');
                loadAndDisplayPhaseKpis_StreamPage(phaseId, streamSlug);
            } else { 
                showNotice('error', response.data.message || '<?php echo esc_js( __( "Could not unlink KPI measure.", "operations-organizer" ) ); ?>'); 
            }
        }).fail(function() { 
            showNotice('error', '<?php echo esc_js( __( "Request to unlink KPI measure failed.", "operations-organizer" ) ); ?>'); 
        });
    });
    
    // Update KPI Link (Mandatory/Order) on change (Stream Page - Event Delegation)
    editPhaseModal_Stream.on('change', '.is-mandatory-kpi-stream, .display-order-kpi-stream', function() {
        var $changedElement = $(this);
        var linkId = $changedElement.data('link-id');
        var isMandatory = $changedElement.closest('tr').find('.is-mandatory-kpi-stream').is(':checked') ? 1 : 0;
        var displayOrder = $changedElement.closest('tr').find('.display-order-kpi-stream').val();
        var phaseId = editPhaseModal_Stream.data('current-phase-id'); // For potential reload on error if needed

        $.post(oo_data.ajax_url, {
            action: 'oo_update_phase_kpi_link',
            link_id: linkId,
            is_mandatory: isMandatory,
            display_order: displayOrder,
            _ajax_nonce: oo_data.nonce_manage_phase_kpi_links
        }, function(response) {
            if (response.success) {
                showNotice('success', '<?php echo esc_js( __("KPI link updated.", "operations-organizer") ); ?>');
                // Small update, no full list reload usually needed unless there's an error or complex dependency
            } else {
                showNotice('error', response.data.message || '<?php echo esc_js( __( "Could not update KPI link.", "operations-organizer") ); ?>');
                // Optionally reload the list to revert visual change if save failed, uncomment if desired:
                // loadAndDisplayPhaseKpis_StreamPage(phaseId, streamSlug);
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js( __( "Request to update KPI link failed.", "operations-organizer") ); ?>');
            // Optionally reload the list to revert visual change if save failed, uncomment if desired:
            // loadAndDisplayPhaseKpis_StreamPage(phaseId, streamSlug);
        });
    });

    // Close stream-specific modals
    $('.oo-modal-close').on('click', function() {
        $(this).closest('.oo-modal').hide();
    });

    // --- End Phase Management for Stream Page ---

    // --- Manage Phase KPIs Modal Logic (Old - to be removed) ---
    /*
    var $manageKPIsModal = $('#manageOOPhaseKPIsModal-stream-' + streamSlugForKPIModal);
    var $manageKPIsPhaseName = $('#manageKPIsPhaseName-stream-' + streamSlugForKPIModal);
    // ... other selectors and functions for the old separate modal ... 
    */

    // --- Start of Job Logs Table JS (already moved and adapted) ---
    if ($('#stream-job-logs-section').length > 0 && $('#content-dashboard-table').length > 0) {
        
        var initialContentColumns = getInitialContentColumns_StreamPage(); // Renamed for clarity
        var contentDashboardTable; 

        var initialDefaultColumns = (oo_data.user_content_default_columns && Array.isArray(oo_data.user_content_default_columns)) ? 
                                    oo_data.user_content_default_columns : [];
        window.contentSelectedKpiObjects = JSON.parse(JSON.stringify(initialDefaultColumns)); 
        var $saveDefaultButton = $('#save_content_columns_as_default');
        var $defaultSavedMsg = $('#content_columns_default_saved_msg');

        function checkColumnChangesAndToggleSaveButton() {
            var currentSelectionJson = JSON.stringify(window.contentSelectedKpiObjects.map(o => ({type: o.type, key: o.key, id: o.id, original_value_string: o.original_value_string })).sort((a,b) => (a.key || a.id) > (b.key || b.id) ? 1 : -1));
            var defaultSelectionJson = JSON.stringify(initialDefaultColumns.map(o => ({type: o.type, key: o.key, id: o.id, original_value_string: o.original_value_string })).sort((a,b) => (a.key || a.id) > (b.key || b.id) ? 1 : -1));
            
            if (currentSelectionJson !== defaultSelectionJson) {
                $saveDefaultButton.show();
            } else {
                $saveDefaultButton.hide();
            }
            $defaultSavedMsg.hide(); 
        }

        function initializeContentDashboardTable_StreamPage(dynamicColumnObjects) { 
            var columnsConfig = [].concat(initialContentColumns);
            
            if (dynamicColumnObjects && dynamicColumnObjects.length > 0) {
                dynamicColumnObjects.forEach(function(kpi) {
                    if (kpi.type === 'primary') {
                        columnsConfig.push({
                            data: 'kpi_' + kpi.key,
                            title: kpi.name,
                            defaultContent: 'N/A',
                            orderable: true,
                            searchable: true
                        });
                    } else if (kpi.type === 'derived') {
                        columnsConfig.push({
                            data: 'derived_metric_val_' + kpi.id, 
                            title: kpi.name,
                            defaultContent: 'N/A', // Changed from 'N/A (Calc Pending)'
                            orderable: true, 
                            searchable: true 
                        });
                    } else if (kpi.type === 'raw_json') {
                         columnsConfig.push({
                            data: 'kpi_data',
                            title: kpi.name,
                            defaultContent: 'N/A',
                            orderable: false, 
                            searchable: true 
                        });
                    }
                });
            }
            columnsConfig.push(getActionsColumnDefinition_StreamPage()); // Renamed

            var $table = $('#content-dashboard-table');
            $table.find('thead').empty(); 
            $table.find('tbody').empty(); 

            var $theadTr = $('<tr>');
            columnsConfig.forEach(function(col) {
                $theadTr.append($('<th>').text(col.title));
            });
            $table.find('thead').append($theadTr);

            if ($.fn.DataTable.isDataTable('#content-dashboard-table')) {
                contentDashboardTable.destroy();
            }

            contentDashboardTable = $table.DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: oo_data.ajax_url,
                    type: 'POST',
                    data: function(d) { 
                        d.action = 'oo_get_dashboard_data';
                        d.nonce = oo_data.nonce_dashboard; 
                        d.filter_employee_id = $('#content_filter_employee_id').val();
                        d.filter_job_number = $('#content_filter_job_number').val();
                        d.filter_phase_id = $('#content_filter_phase_id').val();
                        d.filter_date_from = $('#content_filter_date_from').val();
                        d.filter_date_to = $('#content_filter_date_to').val();
                        d.filter_status = $('#content_filter_status').val();
                        d.filter_stream_id = <?php echo intval($current_stream_id); ?>; // Use current stream ID
                        d.selected_columns_config = window.contentSelectedKpiObjects || [];
                        
                        if (!d.order || d.order.length === 0) {
                             d.order = [{ "column": getColumnIndexByData_StreamPage('start_time', columnsConfig), "dir": "desc" }];
                        }
                        console.log('Sending stream page job logs request data:', d);
                        return d;
                    },
                    dataSrc: function(json) {
                        console.log('Received stream page job logs response:', json);
                        if (json && json.success === true && json.data && json.data.data) {
                            return json.data.data;
                        }
                        return json.data || [];
                    },
                    error: function(xhr, error, thrown) {
                        console.error('Stream Page DataTables AJAX error:', error, thrown, xhr.responseText);
                        alert('Error loading job log data for stream: ' + error);
                    }
                },
                columns: columnsConfig, 
                pageLength: 10,
                language: {
                    search: "<?php esc_attr_e('Search Logs:', 'operations-organizer'); ?>",
                    emptyTable: "<?php esc_attr_e('No job logs found for this stream.', 'operations-organizer'); ?>",
                    zeroRecords: "<?php esc_attr_e('No matching job logs found', 'operations-organizer'); ?>",
                    processing: "<?php esc_attr_e('Loading logs...', 'operations-organizer'); ?>"
                },
                 drawCallback: function(settings) {
                    var api = this.api();
                    var pageInfo = api.page.info();
                    var displayedRows = pageInfo.recordsDisplay;
                    if (displayedRows === 0) {
                        var columnCount = api.columns().header().length;
                        $('#content-dashboard-table tbody').html(
                            '<tr><td colspan="' + columnCount + '" class="dataTables_empty" style="padding: 20px; text-align: center;">' +
                            '<?php esc_html_e('No job logs found matching your criteria.', 'operations-organizer'); ?>' +
                            '</td></tr>'
                        );
                    }
                },
                initComplete: function(settings, json) {
                    var api = this.api();
                    var allDataOnInit = api.rows().data().toArray();
                    if (allDataOnInit.length > 0 && api.rows( { page: 'current' } ).count() === 0) {
                        setTimeout(function() { api.draw(false); }, 200);
                    }
                }
            });
        }

        function getInitialContentColumns_StreamPage() {
            return [
                { data: "employee_name", title: "<?php esc_html_e('Employee Name', 'operations-organizer'); ?>" },
                { data: "job_number", title: "<?php esc_html_e('Job No.', 'operations-organizer'); ?>" },
                { data: "phase_name", title: "<?php esc_html_e('Phase', 'operations-organizer'); ?>" },
                { data: "start_time", title: "<?php esc_html_e('Start Time', 'operations-organizer'); ?>" },
                { data: "end_time", title: "<?php esc_html_e('End Time', 'operations-organizer'); ?>" },
                { data: "duration", title: "<?php esc_html_e('Duration', 'operations-organizer'); ?>" },
                { 
                    data: "status", title: "<?php esc_html_e('Status', 'operations-organizer'); ?>",
                    render: function(data, type, row) {
                        if (type === 'display' && data) return data;
                        return data || '';
                    }
                },
                { data: "notes", title: "<?php esc_html_e('Notes', 'operations-organizer'); ?>" }
            ];
        }

        function getActionsColumnDefinition_StreamPage(){
            return {
                data: null, 
                title: "<?php esc_html_e('Actions', 'operations-organizer'); ?>",
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    if (!row || !row.log_id) return '<div class="row-actions"><span>Error: No log ID</span></div>';
                    var actionButtons = '<div class="row-actions">';
                    if (row.status && (row.status.toLowerCase().includes('running') || row.status.toLowerCase().includes('started'))) {
                        actionButtons += '<span class="edit"><a href="' + 
                            oo_data.admin_url + 'admin.php?page=oo_stop_job&log_id=' + row.log_id + '&return_tab=<?php echo esc_js($current_stream_tab_slug); ?>' + 
                            '" class="oo-stop-job-action"><?php esc_html_e('Stop', 'operations-organizer'); ?></a> | </span>';
                    }
                    actionButtons += '<span class="edit"><a href="#" class="oo-edit-log-action" data-log-id="' + 
                        row.log_id + '"><?php esc_html_e('Edit', 'operations-organizer'); ?></a> | </span>';
                    actionButtons += '<span class="trash"><a href="#" class="oo-delete-log-action" data-log-id="' + 
                        row.log_id + '"><?php esc_html_e('Delete', 'operations-organizer'); ?></a></span>';
                    actionButtons += '</div>';
                    return actionButtons;
                }
            };
        }
        
        function getColumnIndexByData_StreamPage(dataProperty, columnsArray) {
            for (var i = 0; i < columnsArray.length; i++) {
                if (columnsArray[i].data === dataProperty) return i;
            }
            return 0; 
        }

        function reinitializeContentDashboardTable_StreamPage(){
            initializeContentDashboardTable_StreamPage(window.contentSelectedKpiObjects || []);
            checkColumnChangesAndToggleSaveButton();
        }
        
        reinitializeContentDashboardTable_StreamPage(); // Initial load

        $('#content_apply_filters_button').on('click', function(e) { e.preventDefault(); contentDashboardTable.ajax.reload(); });
        $('#content_clear_filters_button').on('click', function(e) { 
            e.preventDefault(); 
            $('#content_filter_employee_id, #content_filter_job_number, #content_filter_phase_id, #content_filter_date_from, #content_filter_date_to, #content_filter_status').val(''); 
            contentDashboardTable.ajax.reload(); 
        });

        // Export, Modals, KPI Selector logic (needs to be moved here too)
        // ... (This will be a large chunk of JS from content-tab.php)
        // Ensure all selectors like #content_open_kpi_selector_modal, #apply_selected_kpi_columns, etc. are unique if this code is duplicated or make them class-based and scoped to the current tab/table.
        // For now, assuming IDs like 'content_open_kpi_selector_modal' will still work as we are on the "Content" stream page.

        // --- Start: Copied from content-tab.php (needs review and adaptation) --- 
        if ($.fn.datepicker) {
            $('#content_filter_date_from, #content_filter_date_to').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
        }
        
        $(document).on('click', '.oo-edit-log-action', function(e) { /* ... same as content-tab ... */ });
        $('#set_end_time_now').on('click', function() { /* ... same as content-tab ... */ });
        $('#set_start_time_now').on('click', function() { /* ... same as content-tab ... */ });
        function resetButtonStates() { /* ... same as content-tab ... */ }
        $('#enable_job_number_edit').on('change', function() { /* ... same as content-tab ... */ });
        $('#edit_log_job_number').on('change', function() { /* ... same as content-tab ... */ });
        $('#save_as_running, #save_as_completed, #save_changes').on('click', function(e){ /* ... Needs submitEditLogForm_StreamPage ... */ });
        function padNumber(num) { return num.toString().padStart(2, '0'); }
        function submitEditLogForm_StreamPage() { /* ... Adapt from content-tab, use contentDashboardTable.ajax.reload(); ... */ }
        $(document).on('click', '.oo-delete-log-action', function(e) { /* ... same as content-tab ... */ });
        $('#delete-log-form').on('submit', function(e) { /* ... Adapt, use contentDashboardTable.ajax.reload(); ... */ });
        $('.oo-modal-close, .oo-modal-cancel').on('click', function() { $('.oo-modal').css('display', 'none'); });
        $(window).on('click', function(event) { if ($(event.target).hasClass('oo-modal')) { $('.oo-modal').css('display', 'none'); } });

        var kpiColumnModal_StreamPage = $('<div id="kpi-column-selector-modal-stream" class="oo-modal">...</div>'); // Needs to be adapted, unique ID, and correct PHP for kpi list if different
        // All KPI selector modal JS needs to be scoped to this new modal ID and related elements.
        // For simplicity, I will assume the content-tab.php's complex KPI selector HTML and JS will be fully moved and adapted here.
        // This includes: #kpi-column-selector-modal construction, #content_open_kpi_selector_modal click,
        // updateSelectedKpiCount, #apply_selected_kpi_columns click.

        // For now, much of the complex modal JS from content-tab.php is omitted here for brevity
        // but would need to be carefully moved and adapted, ensuring IDs are unique if necessary.
        
        // The core logic for Save Default button
        $saveDefaultButton.on('click', function(){ /* ... same AJAX call logic ... */ });

        // --- End: Copied from content-tab.php --- 

    } // End if ($('#stream-job-logs-section').length)

    // --- KPI Measure Management for this Stream Page (NEW) ---
    var addKpiMeasureModal_Stream = $('#addKpiMeasureModal-stream-' + streamSlug);
    var editKpiMeasureModal_Stream = $('#editKpiMeasureModal-stream-' + streamSlug);
    var kpiMeasuresListContainer_Stream = $('#kpi-measures-list-stream-' + streamSlug);

    // Function to refresh the KPI Measures list in the tab via AJAX
    function refreshKpiMeasuresList_Stream(streamId, streamSlugContext) {
        kpiMeasuresListContainer_Stream.html('<tr><td colspan="5"><?php echo esc_js( __("Loading KPI Measures...", "operations-organizer") ); ?></td></tr>');
        
        $.post(oo_data.ajax_url, {
            action: 'oo_get_kpi_measures_for_stream_html', // New AJAX action to get HTML
            stream_id: streamId,
            stream_slug: streamSlugContext, // Pass slug for button/modal IDs in refreshed HTML
            _ajax_nonce: oo_data.nonce_get_kpi_measures // Re-use a generic nonce or create a specific one
        }, function(response) {
            if (response.success) {
                kpiMeasuresListContainer_Stream.html(response.data.html);
            } else {
                kpiMeasuresListContainer_Stream.html('<tr><td colspan="5">' + (response.data.message || '<?php echo esc_js( __("Error loading KPI Measures.", "operations-organizer") ); ?>') + '</td></tr>');
                showNotice('error', response.data.message || '<?php echo esc_js( __("Could not refresh KPI list.", "operations-organizer") ); ?>');
            }
        }).fail(function() {
            kpiMeasuresListContainer_Stream.html('<tr><td colspan="5"><?php echo esc_js( __("Request failed while refreshing KPI Measures.", "operations-organizer") ); ?></td></tr>');
            showNotice('error', '<?php echo esc_js( __("Request failed. Please try again.", "operations-organizer") ); ?>');
        });
    }

    // Open Add KPI Measure Modal
    $('#openAddKpiMeasureModalBtn-stream-' + streamSlug).on('click', function() {
        addKpiMeasureModal_Stream.find('form')[0].reset();
        addKpiMeasureModal_Stream.find('#add_kpi_measure_key-stream-' + streamSlug).prop('readonly', false); // Ensure key is editable for new
        addKpiMeasureModal_Stream.show();
    });

    // Handle Add KPI Measure Form Submission
    $('#oo-add-kpi-measure-form-stream-' + streamSlug).on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);

        // Validate at least one phase is checked
        if ($form.find('.kpi-phase-linking-section-stream input[name="link_to_phases[]"]:checked').length === 0) {
            // Check if there were any phases to link to in the first place
            if ($form.find('#add-kpi-link-to-phases-list-' + streamSlug).find('input[type="checkbox"]').length === 0) {
                // No phases were available to link, this might be an edge case or setup issue.
                // Depending on desired behavior, either allow submission or show a specific error.
                // For now, let's assume if no phases, it shouldn't block if the section itself says "No active phases".
                // However, the section label says "at least one required", so this is a conflict.
                // Let's enforce that if phases *were* available, one must be checked.
                if ($form.find('#add-kpi-link-to-phases-list-' + streamSlug + ' p').text().indexOf('No active phases') === -1) {
                    alert('<?php echo esc_js(__("Please link this KPI to at least one phase in the current stream.", "operations-organizer")); ?>');
                    return false;
                }
            } else {
                 alert('<?php echo esc_js(__("Please link this KPI to at least one phase in the current stream.", "operations-organizer")); ?>');
                 return false;
            }
        }

        var $submitButton = $form.find('#submit_add_kpi_measure-stream-' + streamSlug);
        $submitButton.prop('disabled', true).val('<?php echo esc_js(__("Adding...", "operations-organizer")); ?>');
        
        var formData = $form.serializeArray();
        formData.push({ name: 'action', value: 'oo_add_kpi_measure' }); // Ensure correct main action
        formData.push({ name: '_ajax_nonce', value: oo_data.nonce_add_kpi_measure }); // Use nonce from oo_data

        $.post(oo_data.ajax_url, $.param(formData), function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                addKpiMeasureModal_Stream.hide();
                refreshKpiMeasuresList_Stream(<?php echo intval($current_stream_id); ?>, streamSlug);
            } else {
                showNotice('error', response.data.message || '<?php echo esc_js(__("An unknown error occurred.", "operations-organizer")); ?>');
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js(__("Request failed. Please try again.", "operations-organizer")); ?>');
        }).always(function() {
            $submitButton.prop('disabled', false).val('<?php echo esc_js(__("Add KPI Measure", "operations-organizer")); ?>');
        });
    });

    // Handle Edit KPI Measure Button Click
    kpiMeasuresListContainer_Stream.on('click', '.oo-edit-kpi-measure-stream', function() {
        var kpiMeasureId = $(this).data('kpi-measure-id');
        editKpiMeasureModal_Stream.find('#editKpiMeasureNameDisplay-' + streamSlug).text('<?php echo esc_js(__("Loading...", "operations-organizer")); ?>');
        var $phaseChecklistContainer = editKpiMeasureModal_Stream.find('#edit-kpi-link-to-phases-list-' + streamSlug);
        $phaseChecklistContainer.html('<p><?php echo esc_js(__("Loading phases...", "operations-organizer")); ?></p>');

        // Fetch KPI details and also the phases for the current stream to populate checkboxes
        $.when(
            $.post(oo_data.ajax_url, { // Get KPI details
                action: 'oo_get_kpi_measure_details', 
                kpi_measure_id: kpiMeasureId,
                _ajax_nonce: oo_data.nonce_get_kpi_measure_details
            }),
            $.post(oo_data.ajax_url, { // Get phases for current stream
                action: 'oo_get_phases_for_stream', // New AJAX action needed
                stream_id: <?php echo intval($current_stream_id); ?>, 
                _ajax_nonce: oo_data.nonce_get_phases // A general nonce for getting phases
            }),
            $.post(oo_data.ajax_url, { // Get existing links for this KPI in this stream
                action: 'oo_get_phase_links_for_kpi_in_stream', // New AJAX action needed
                kpi_measure_id: kpiMeasureId,
                stream_id: <?php echo intval($current_stream_id); ?>, 
                _ajax_nonce: oo_data.nonce_get_phase_kpi_links // Can reuse existing or create new
            })
        ).done(function(kpiDetailsResponse, phasesResponse, existingLinksResponse) {
            if (kpiDetailsResponse[0].success && phasesResponse[0].success && existingLinksResponse[0].success) {
                var kpi = kpiDetailsResponse[0].data.kpi_measure;
                var phasesInStream = phasesResponse[0].data.phases;
                var existingPhaseIdsLinked = existingLinksResponse[0].data.linked_phase_ids; // Expect an array of phase IDs

                editKpiMeasureModal_Stream.find('#edit_kpi_measure_id-stream-' + streamSlug).val(kpi.kpi_measure_id);
                editKpiMeasureModal_Stream.find('#editKpiMeasureNameDisplay-' + streamSlug).text(esc_html(kpi.measure_name));
                editKpiMeasureModal_Stream.find('#edit_kpi_measure_name-stream-' + streamSlug).val(kpi.measure_name);
                editKpiMeasureModal_Stream.find('#edit_kpi_measure_key-stream-' + streamSlug).val(kpi.measure_key); 
                editKpiMeasureModal_Stream.find('#edit_kpi_unit_type-stream-' + streamSlug).val(kpi.unit_type);
                editKpiMeasureModal_Stream.find('#edit_kpi_is_active-stream-' + streamSlug).prop('checked', parseInt(kpi.is_active) === 1);

                // Populate phase checklist
                $phaseChecklistContainer.empty();
                if (phasesInStream && phasesInStream.length > 0) {
                    $.each(phasesInStream, function(index, phase) {
                        var isChecked = $.inArray(phase.phase_id.toString(), existingPhaseIdsLinked.map(String)) !== -1;
                        var checkbox = '<label style="display: block;"><input type="checkbox" name="link_to_phases[]" value="' + phase.phase_id + '" ' + (isChecked ? 'checked' : '') + '> ' + esc_html(phase.phase_name) + '</label>';
                        $phaseChecklistContainer.append(checkbox);
                    });
                } else {
                    $phaseChecklistContainer.html('<p><?php echo esc_js(__("No active phases found in this stream to link to.", "operations-organizer")); ?></p>');
                }
                editKpiMeasureModal_Stream.show();
            } else {
                var errorMsg = kpiDetailsResponse[0].data.message || phasesResponse[0].data.message || existingLinksResponse[0].data.message || '<?php echo esc_js(__("Could not load KPI Measure data or phase list.", "operations-organizer")); ?>';
                showNotice('error', errorMsg);
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js(__("Request to load KPI Measure data or phase list failed.", "operations-organizer")); ?>');
            $phaseChecklistContainer.html('<p><?php echo esc_js(__("Error loading phases.", "operations-organizer")); ?></p>');
        });
    });

    // Handle Edit KPI Measure Form Submission
    $('#oo-edit-kpi-measure-form-stream-' + streamSlug).on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('#submit_edit_kpi_measure-stream-' + streamSlug);
        $submitButton.prop('disabled', true).val('<?php echo esc_js(__("Saving...", "operations-organizer")); ?>');
        
        var formData = $form.serializeArray();
        // measure_key is readonly and should not be submitted for update,
        // but the oo_update_kpi_measure AJAX handler should ideally ignore it if sent.
        // We already set it as readonly in the form.
        formData.push({ name: 'action', value: 'oo_update_kpi_measure' });
        formData.push({ name: '_ajax_nonce', value: oo_data.nonce_edit_kpi_measure });

        $.post(oo_data.ajax_url, $.param(formData), function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                editKpiMeasureModal_Stream.hide();
                refreshKpiMeasuresList_Stream(<?php echo intval($current_stream_id); ?>, streamSlug);
            } else {
                showNotice('error', response.data.message || '<?php echo esc_js(__("An unknown error occurred.", "operations-organizer")); ?>');
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js(__("Request failed. Please try again.", "operations-organizer")); ?>');
        }).always(function() {
            $submitButton.prop('disabled', false).val('<?php echo esc_js(__("Save KPI Measure Changes", "operations-organizer")); ?>');
        });
    });
    
    // Handle Toggle KPI Measure Status Button Click
    kpiMeasuresListContainer_Stream.on('click', '.oo-toggle-kpi-measure-status-stream', function() {
        var $button = $(this);
        var kpiMeasureId = $button.data('kpi-measure-id');
        var newStatus = $button.data('new-status');
        var nonceAction = 'oo_toggle_kpi_measure_status_' + kpiMeasureId; // Specific nonce action
        // Assuming nonce is available in oo_data.nonces[nonceAction] or similar, or use a general toggle nonce
        // For now, let's assume a general nonce 'nonce_toggle_kpi_status' is available in oo_data
        // If not, this needs adjustment for how nonces are passed.
        var nonceValue = oo_data.nonces && oo_data.nonces[nonceAction] ? oo_data.nonces[nonceAction] : oo_data.nonce_toggle_kpi_status;


        var confirmMessage = newStatus == 1 ?
            '<?php echo esc_js(__("Are you sure you want to activate this KPI Measure?", "operations-organizer")); ?>' :
            '<?php echo esc_js(__("Are you sure you want to deactivate this KPI Measure? Deactivating might affect its usage in phase configurations.", "operations-organizer")); ?>';

        if (!confirm(confirmMessage)) { return; }
        
        $button.prop('disabled', true);

        $.post(oo_data.ajax_url, {
            action: 'oo_toggle_kpi_measure_status',
            kpi_measure_id: kpiMeasureId,
            is_active: newStatus,
            _ajax_nonce: nonceValue, // Use the retrieved nonce
            nonce_action_check: nonceAction // Send for server-side verification against this specific action
        }, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                refreshKpiMeasuresList_Stream(<?php echo intval($current_stream_id); ?>, streamSlug);
            } else {
                showNotice('error', response.data.message || '<?php echo esc_js(__("Could not change KPI Measure status.", "operations-organizer")); ?>');
                 $button.prop('disabled', false);
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js(__("Request to change KPI Measure status failed.", "operations-organizer")); ?>');
            $button.prop('disabled', false);
        });
    });

    // Handle Delete KPI Measure Button Click
    kpiMeasuresListContainer_Stream.on('click', '.oo-delete-kpi-measure-stream', function(e) {
        e.preventDefault();
        var $link = $(this);
        var kpiMeasureId = $link.data('kpi-measure-id');
        var nonceAction = 'oo_delete_kpi_measure_' + kpiMeasureId;
        // Similar to toggle, retrieve nonce. Assuming a general delete nonce for now if specific one not found.
        var nonceValue = oo_data.nonces && oo_data.nonces[nonceAction] ? oo_data.nonces[nonceAction] : oo_data.nonce_delete_kpi_measure;


        if (!confirm('<?php echo esc_js(__("Are you sure you want to permanently delete this KPI Measure? This action cannot be undone and might affect phases currently using it. The system will attempt to unlink it from phases first.", "operations-organizer")); ?>')) {
            return;
        }
        
        $link.css('color', '#ccc'); // Visually indicate processing

        $.post(oo_data.ajax_url, {
            action: 'oo_delete_kpi_measure',
            kpi_measure_id: kpiMeasureId,
            _ajax_nonce: nonceValue,
            nonce_action_check: nonceAction, // Send for server-side verification
            context: 'stream_page' // Inform the backend about the context if needed for specific handling
        }, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                refreshKpiMeasuresList_Stream(<?php echo intval($current_stream_id); ?>, streamSlug);
            } else {
                showNotice('error', response.data.message || '<?php echo esc_js(__("Could not delete KPI Measure.", "operations-organizer")); ?>');
                $link.css('color', '#b32d2e'); // Reset color on failure
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js(__("Request to delete KPI Measure failed.", "operations-organizer")); ?>');
            $link.css('color', '#b32d2e'); // Reset color on failure
        });
    });
    
    // Auto-generate kpi measure key from name in Add Modal
    $('#add_kpi_measure_name-stream-' + streamSlug).on('input', function() {
        var slug = $(this).val().toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]+/g, '').replace(/__+/g, '_').replace(/^_|_$/g, '');
        $('#add_kpi_measure_key-stream-' + streamSlug).val(slug);
    });

    // --- End KPI Measure Management for Stream Page ---

    // --- Derived KPI Definition Management for this Stream Page (NEW) ---
    var addDerivedKpiModal_Stream = $('#addDerivedKpiModal-stream-' + streamSlug);
    var editDerivedKpiModal_Stream = $('#editDerivedKpiModal-stream-' + streamSlug);
    var derivedKpiListContainer_Stream = $('#derived-kpi-definitions-list-stream-' + streamSlug);
    
    // Helper: Populate calculation type options based on primary KPI unit type
    function populateCalculationTypes_Stream(primaryKpiUnitType, $selectElement) {
        $selectElement.empty();
        $selectElement.append($('<option>', { value: '', text: '<?php echo esc_js(__("-- Select Calculation Type --", "operations-organizer")); ?>' }));

        // Define available calculation types (can be extended)
        var calcTypes = {
            all: [
                { value: 'sum_value', text: '<?php echo esc_js(__("Sum of Primary KPI Value")); ?>' },
                { value: 'average_value', text: '<?php echo esc_js(__("Average of Primary KPI Value")); ?>' },
            ],
            numeric: [
                { value: 'rate_per_time', text: '<?php echo esc_js(__("Rate: Primary KPI per Time Unit")); ?>' },
                { value: 'ratio_to_kpi', text: '<?php echo esc_js(__("Ratio: Primary KPI to Secondary KPI")); ?>' },
            ],
            boolean: [
                { value: 'count_if_true', text: '<?php echo esc_js(__("Count occurrences if Primary KPI is TRUE")); ?>' },
                { value: 'count_if_false', text: '<?php echo esc_js(__("Count occurrences if Primary KPI is FALSE")); ?>' },
            ]
        };

        var options = [].concat(calcTypes.all);
        if (primaryKpiUnitType === 'integer' || primaryKpiUnitType === 'decimal') {
            options = options.concat(calcTypes.numeric);
        } else if (primaryKpiUnitType === 'boolean') {
            options = options.concat(calcTypes.boolean);
        }
        // Add more specific types for 'text' if needed later

        $.each(options, function(i, type) {
            $selectElement.append($('<option>', { value: type.value, text: type.text }));
        });
        $selectElement.trigger('change'); // Trigger change to show/hide conditional fields
    }

    // Helper: Show/hide conditional fields based on calculation type
    function handleDerivedKpiCalcTypeChange_Stream($modal) {
        var calcType = $modal.find('select[name="derived_calculation_type"]').val();
        var $secondaryKpiField = $modal.find('.derived-secondary-kpi-field-stream');
        var $timeUnitField = $modal.find('.derived-time-unit-field-stream');

        $secondaryKpiField.hide().find('select').prop('required', false);
        $timeUnitField.hide().find('select').prop('required', false);

        if (calcType === 'rate_per_time') {
            $timeUnitField.show().find('select').prop('required', true);
        } else if (calcType === 'ratio_to_kpi') {
            $secondaryKpiField.show().find('select').prop('required', true);
        }
    }

    // Populate Secondary KPI dropdown (all active KPIs excluding primary)
    function populateSecondaryKpis_Stream($selectElement, primaryKpiIdToExclude) {
        $selectElement.empty().append($('<option>', { value: '', text: '<?php echo esc_js(__("-- Select Secondary KPI --", "operations-organizer")); ?>' }));
        if (oo_data.all_kpi_measures && oo_data.all_kpi_measures.length > 0) {
            $.each(oo_data.all_kpi_measures, function(i, kpi) {
                if (kpi.kpi_measure_id.toString() !== primaryKpiIdToExclude.toString()) {
                    $selectElement.append($('<option>', { 
                        value: kpi.kpi_measure_id, 
                        text: esc_html(kpi.measure_name) + ' (' + esc_html(kpi.unit_type) + ')' 
                    }));
                }
            });
        }
        if ($selectElement.children().length === 1) { // Only the default option
             $selectElement.append('<option value="" disabled><?php echo esc_js(__( "No other KPIs available", "operations-organizer" )); ?></option>');
        }
    }

    // Open Add Derived KPI Modal
    $('#openAddDerivedKpiModalBtn-stream-' + streamSlug).on('click', function() {
        addDerivedKpiModal_Stream.find('form')[0].reset();
        var $primaryKpiSelect = addDerivedKpiModal_Stream.find('#add_derived_primary_kpi_id-stream-' + streamSlug);
        var $calcTypeSelect = addDerivedKpiModal_Stream.find('#add_derived_calculation_type-stream-' + streamSlug);
        
        // Reset and repopulate Primary KPI dropdown (it is static in the modal HTML but good practice if it could change)
        // $primaryKpiSelect.val(''); // Already reset by form[0].reset()
        
        // Initial population of calc types based on no selection for primary KPI (or first option)
        var initialPrimaryKpiUnit = $primaryKpiSelect.find('option:selected').data('unit-type') || '';
        populateCalculationTypes_Stream(initialPrimaryKpiUnit, $calcTypeSelect);
        handleDerivedKpiCalcTypeChange_Stream(addDerivedKpiModal_Stream);
        populateSecondaryKpis_Stream(addDerivedKpiModal_Stream.find('#add_derived_secondary_kpi_id-stream-' + streamSlug), $primaryKpiSelect.val());
        addDerivedKpiModal_Stream.show();
    });

    // Handle Primary KPI change in Add Derived KPI Modal
    addDerivedKpiModal_Stream.on('change', '#add_derived_primary_kpi_id-stream-' + streamSlug, function() {
        var unitType = $(this).find('option:selected').data('unit-type') || '';
        var primaryKpiId = $(this).val();
        populateCalculationTypes_Stream(unitType, addDerivedKpiModal_Stream.find('#add_derived_calculation_type-stream-' + streamSlug));
        populateSecondaryKpis_Stream(addDerivedKpiModal_Stream.find('#add_derived_secondary_kpi_id-stream-' + streamSlug), primaryKpiId);
    });

    // Handle Calculation Type change in Add Derived KPI Modal
    addDerivedKpiModal_Stream.on('change', '#add_derived_calculation_type-stream-' + streamSlug, function() {
        handleDerivedKpiCalcTypeChange_Stream(addDerivedKpiModal_Stream);
    });

    // Function to refresh the Derived KPI list in the tab via AJAX
    function refreshDerivedKpiList_Stream(streamId, streamSlugContext) {
        derivedKpiListContainer_Stream.html('<tr><td colspan="5"><?php echo esc_js( __("Loading Derived KPIs...", "operations-organizer") ); ?></td></tr>');
        
        $.post(oo_data.ajax_url, {
            action: 'oo_get_derived_kpis_for_stream_html', // New AJAX action
            stream_id: streamId,
            stream_slug: streamSlugContext,
            _ajax_nonce: oo_data.nonce_get_derived_kpi_definitions // New or existing nonce
        }, function(response) {
            if (response.success) {
                derivedKpiListContainer_Stream.html(response.data.html);
            } else {
                derivedKpiListContainer_Stream.html('<tr><td colspan="5">' + (response.data.message || '<?php echo esc_js( __("Error loading Derived KPIs.", "operations-organizer") ); ?>') + '</td></tr>');
                showNotice('error', response.data.message || '<?php echo esc_js( __("Could not refresh Derived KPI list.", "operations-organizer") ); ?>');
            }
        }).fail(function() {
            derivedKpiListContainer_Stream.html('<tr><td colspan="5"><?php echo esc_js( __("Request failed while refreshing Derived KPIs.", "operations-organizer") ); ?></td></tr>');
            showNotice('error', '<?php echo esc_js( __("Request failed. Please try again.", "operations-organizer") ); ?>');
        });
    }

    // Handle Add Derived KPI Form Submission
    $('#oo-add-derived-kpi-form-stream-' + streamSlug).on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('#submit_add_derived_kpi-stream-' + streamSlug);
        $submitButton.prop('disabled', true).val('<?php echo esc_js(__("Adding...", "operations-organizer")); ?>');
        
        var formData = $form.serializeArray();
        formData.push({ name: 'action', value: 'oo_add_derived_kpi_definition' });
        formData.push({ name: '_ajax_nonce', value: oo_data.nonce_add_derived_kpi }); // New Nonce

        $.post(oo_data.ajax_url, $.param(formData), function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                addDerivedKpiModal_Stream.hide();
                refreshDerivedKpiList_Stream(<?php echo intval($current_stream_id); ?>, streamSlug);
            } else {
                showNotice('error', response.data.message || '<?php echo esc_js(__("An unknown error occurred.", "operations-organizer")); ?>');
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js(__("Request failed. Please try again.", "operations-organizer")); ?>');
        }).always(function() {
            $submitButton.prop('disabled', false).val('<?php echo esc_js(__("Add Derived KPI", "operations-organizer")); ?>');
        });
    });

    // Handle Edit Derived KPI Button Click
    derivedKpiListContainer_Stream.on('click', '.oo-edit-derived-kpi-stream', function() {
        var derivedKpiId = $(this).data('derived-kpi-id');
        editDerivedKpiModal_Stream.find('#editDerivedKpiNameDisplay-' + streamSlug).text('<?php echo esc_js(__("Loading...", "operations-organizer")); ?>');
        
        $.post(oo_data.ajax_url, {
            action: 'oo_get_derived_kpi_definition_details', // Existing global action
            derived_definition_id: derivedKpiId,
            _ajax_nonce: oo_data.nonce_get_derived_kpi_details // Existing global nonce
        }, function(response) {
            // console.log('[Derived KPI Edit Modal Populate] Response from oo_get_derived_kpi_definition_details:', response); // DEBUG REMOVED
            if (response.success) {
                var dkpi = response.data.definition;
                var primary_kpi = response.data.primary_kpi; // Expecting this from backend now
                // console.log('[Derived KPI Edit Modal Populate] dkpi object:', dkpi); // DEBUG REMOVED
                // console.log('[Derived KPI Edit Modal Populate] primary_kpi object:', primary_kpi); // DEBUG REMOVED

                editDerivedKpiModal_Stream.find('#edit_derived_definition_id-stream-' + streamSlug).val(dkpi.derived_definition_id);
                editDerivedKpiModal_Stream.find('#editDerivedKpiNameDisplay-' + streamSlug).text(esc_html(dkpi.definition_name));
                editDerivedKpiModal_Stream.find('#edit_derived_definition_name-stream-' + streamSlug).val(dkpi.definition_name);
                
                // Primary KPI display (name and unit type)
                editDerivedKpiModal_Stream.find('#edit_derived_primary_kpi_name_display-stream-' + streamSlug).text(primary_kpi ? esc_html(primary_kpi.measure_name) : 'Unknown KPI');
                editDerivedKpiModal_Stream.find('#edit_derived_primary_kpi_unit_type-stream-' + streamSlug).val(primary_kpi ? primary_kpi.unit_type : ''); // For JS logic
                
                // Populate dynamic dropdowns and handle conditional visibility FIRST
                populateCalculationTypes_Stream(primary_kpi ? primary_kpi.unit_type : '', editDerivedKpiModal_Stream.find('#edit_derived_calculation_type-stream-' + streamSlug));
                editDerivedKpiModal_Stream.find('#edit_derived_calculation_type-stream-' + streamSlug).val(dkpi.calculation_type);
                editDerivedKpiModal_Stream.find('#edit_derived_calculation_type-stream-' + streamSlug).trigger('change'); // This might re-render sections

                populateSecondaryKpis_Stream(editDerivedKpiModal_Stream.find('#edit_derived_secondary_kpi_id-stream-' + streamSlug), dkpi.primary_kpi_measure_id);
                if (dkpi.calculation_type === 'ratio_to_kpi' && dkpi.secondary_kpi_measure_id) {
                    editDerivedKpiModal_Stream.find('#edit_derived_secondary_kpi_id-stream-' + streamSlug).val(dkpi.secondary_kpi_measure_id);
                }
                if (dkpi.calculation_type === 'rate_per_time' && dkpi.time_unit_for_rate) {
                    editDerivedKpiModal_Stream.find('#edit_derived_time_unit-stream-' + streamSlug).val(dkpi.time_unit_for_rate);
                }
                
                editDerivedKpiModal_Stream.find('#edit_derived_output_description-stream-' + streamSlug).val(dkpi.output_description);
                editDerivedKpiModal_Stream.find('#edit_derived_is_active-stream-' + streamSlug).prop('checked', parseInt(dkpi.is_active) === 1);

                // Set the critical hidden primary_kpi_measure_id field LAST
                if (dkpi && typeof dkpi.primary_kpi_measure_id !== 'undefined') {
                    // console.log('[Derived KPI Edit Modal Populate] Setting primary_kpi_measure_id to:', dkpi.primary_kpi_measure_id, 'as the final step.'); // DEBUG REMOVED
                    editDerivedKpiModal_Stream.find('#edit_derived_primary_kpi_id-stream-' + streamSlug).val(dkpi.primary_kpi_measure_id);
                } else {
                    // console.warn('[Derived KPI Edit Modal Populate] dkpi.primary_kpi_measure_id is undefined before final set.'); // DEBUG REMOVED
                }
                
                editDerivedKpiModal_Stream.show();
            } else {
                showNotice('error', response.data.message || '<?php echo esc_js(__("Could not load Derived KPI data.", "operations-organizer")); ?>');
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js(__("Request to load Derived KPI data failed.", "operations-organizer")); ?>');
        });
    });

    // Handle Calculation Type change in Edit Derived KPI Modal
    editDerivedKpiModal_Stream.on('change', '#edit_derived_calculation_type-stream-' + streamSlug, function() {
        handleDerivedKpiCalcTypeChange_Stream(editDerivedKpiModal_Stream);
        // If changing to a type that doesn\'t need secondary KPI, clear its value
        if ($(this).val() !== 'ratio_to_kpi') {
            editDerivedKpiModal_Stream.find('#edit_derived_secondary_kpi_id-stream-' + streamSlug).val('');
        }
        // If changing to a type that doesn\'t need time unit, clear its value (though it defaults)
        if ($(this).val() !== 'rate_per_time') {
             editDerivedKpiModal_Stream.find('#edit_derived_time_unit-stream-' + streamSlug).val('hour'); // Reset to default
        }
    });

    // Handle Edit Derived KPI Form Submission
    $('#oo-edit-derived-kpi-form-stream-' + streamSlug).on('submit', function(e) {
        e.preventDefault();
        // console.log('[Derived KPI Edit] Form submitted. Current streamSlug:', streamSlug); // DEBUG REMOVED
        var $form = $(this);
        var $submitButton = $form.find('#submit_edit_derived_kpi-stream-' + streamSlug);
        $submitButton.prop('disabled', true).val('<?php echo esc_js(__("Saving...", "operations-organizer")); ?>');
        
        var targetHiddenInputIdString = 'edit_derived_primary_kpi_id-stream-' + streamSlug; 
        var $targetHiddenInputObject = $('#' + targetHiddenInputIdString); 

        // console.log('[Derived KPI Edit] Looking for input with ID:', targetHiddenInputIdString); // DEBUG REMOVED
        // console.log('[Derived KPI Edit] jQuery object for target input ($targetHiddenInputObject). Length:', $targetHiddenInputObject.length); // DEBUG REMOVED
        // if ($targetHiddenInputObject.length > 0) { // DEBUG REMOVED
        //     console.log('[Derived KPI Edit] HTML of found target input:', $targetHiddenInputObject[0].outerHTML); // DEBUG REMOVED
        // } // DEBUG REMOVED

        var primaryKpiIdVal = $targetHiddenInputObject.val(); 
        // console.log('[Derived KPI Edit] Value of hidden input #' + targetHiddenInputIdString + ' (globally selected) before serialize:', primaryKpiIdVal); // DEBUG REMOVED

        var formData = $form.serializeArray();
        
        formData = formData.filter(function(item) {
            return item.name !== 'primary_kpi_measure_id';
        });
        if (typeof primaryKpiIdVal !== 'undefined' && primaryKpiIdVal !== null && primaryKpiIdVal !== '') {
            formData.push({ name: 'primary_kpi_measure_id', value: primaryKpiIdVal });
        } else {
            // console.warn('[Derived KPI Edit] primary_kpi_measure_id is still empty or undefined before adding to formData. Value:', primaryKpiIdVal); // DEBUG REMOVED
        }

        formData.push({ name: 'action', value: 'oo_update_derived_kpi_definition' });
        formData.push({ name: '_ajax_nonce', value: oo_data.nonce_edit_derived_kpi }); 
        // console.log('[Derived KPI Edit] FormData to be sent:', $.param(formData)); // DEBUG REMOVED

        $.post(oo_data.ajax_url, $.param(formData), function(response) {
            // console.log('[Derived KPI Edit] AJAX success response:', response); // DEBUG REMOVED
            if (response.success) {
                showNotice('success', response.data.message);
                editDerivedKpiModal_Stream.hide();
                refreshDerivedKpiList_Stream(<?php echo intval($current_stream_id); ?>, streamSlug);
            } else {
                showNotice('error', response.data.message || '<?php echo esc_js(__("An unknown error occurred.", "operations-organizer")); ?>');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // console.error('[Derived KPI Edit] AJAX fail. Status: ' + textStatus + ', Error: ' + errorThrown, jqXHR); // DEBUG REMOVED
            showNotice('error', '<?php echo esc_js(__("Request failed. Please try again.", "operations-organizer")); ?>');
        }).always(function() {
            // console.log('[Derived KPI Edit] AJAX always finished.'); // DEBUG REMOVED
            $submitButton.prop('disabled', false).val('<?php echo esc_js(__("Save Derived KPI Changes", "operations-organizer")); ?>');
        });
    });

    // Handle Toggle Derived KPI Status Button Click
    derivedKpiListContainer_Stream.on('click', '.oo-toggle-derived-kpi-status-stream', function() {
        var $button = $(this);
        var derivedKpiId = $button.data('derived-kpi-id');
        var newStatus = $button.data('new-status');
        var nonceAction = 'oo_toggle_derived_kpi_status_' + derivedKpiId;
        var nonceValue = oo_data.nonces && oo_data.nonces[nonceAction] ? oo_data.nonces[nonceAction] : oo_data.nonce_toggle_derived_kpi_status; // General fallback

        if (!confirm(newStatus == 1 ? '<?php echo esc_js(__("Activate this Derived KPI?", "operations-organizer")); ?>' : '<?php echo esc_js(__("Deactivate this Derived KPI?", "operations-organizer")); ?>')) return;
        
        $button.prop('disabled', true);
        $.post(oo_data.ajax_url, {
            action: 'oo_toggle_derived_kpi_status', // New AJAX action
            derived_definition_id: derivedKpiId,
            is_active: newStatus,
            _ajax_nonce: nonceValue, // Use appropriate nonce
            nonce_action_check: nonceAction
        }, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                refreshDerivedKpiList_Stream(<?php echo intval($current_stream_id); ?>, streamSlug);
            } else {
                showNotice('error', response.data.message || 'Error toggling status.');
                $button.prop('disabled', false);
            }
        }).fail(function() {
            showNotice('error', 'Request failed.');
            $button.prop('disabled', false);
        });
    });

    // Handle Delete Derived KPI Button Click
    derivedKpiListContainer_Stream.on('click', '.oo-delete-derived-kpi-stream', function(e) {
        e.preventDefault();
        var $link = $(this);
        var derivedKpiId = $link.data('derived-kpi-id');
        var nonceAction = 'oo_delete_derived_kpi_' + derivedKpiId;
        var nonceValue = oo_data.nonces && oo_data.nonces[nonceAction] ? oo_data.nonces[nonceAction] : oo_data.nonce_delete_derived_kpi; // General fallback

        if (!confirm('<?php echo esc_js(__("Are you sure you want to permanently delete this Derived KPI definition? This cannot be undone.", "operations-organizer")); ?>')) return;
        
        $link.css('color', '#ccc');
        $.post(oo_data.ajax_url, {
            action: 'oo_delete_derived_kpi_definition', // New AJAX action
            derived_definition_id: derivedKpiId,
            _ajax_nonce: nonceValue, // Use appropriate nonce
            nonce_action_check: nonceAction
        }, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                refreshDerivedKpiList_Stream(<?php echo intval($current_stream_id); ?>, streamSlug);
            } else {
                showNotice('error', response.data.message || 'Could not delete Derived KPI.');
                $link.css('color', '#b32d2e');
            }
        }).fail(function() {
            showNotice('error', 'Request to delete Derived KPI failed.');
            $link.css('color', '#b32d2e');
        });
    });

    // --- End Derived KPI Definition Management ---

    // --- Start of Job Logs Table JS (already moved and adapted) ---
// ... existing code ...

    // --- KPI Column Selector Modal - Open/Close Logic for Stream Page ---
    // This block is specifically for the modal added for column selection on the stream dashboard.
    (function() { // IIFE for dashboard logic
        var streamSlugForModal = '<?php echo esc_js($current_stream_tab_slug); ?>';
        var $modal = $('#kpi-column-selector-modal-stream-' + streamSlugForModal);
        var $listContainer = $('#kpi-column-list-stream-' + streamSlugForModal);
        var $saveDefaultButton = $('#save_content_columns_as_default');
        var $defaultSavedMsg = $('#content_columns_default_saved_msg');
        var contentDashboardTable; // To hold the DataTable instance

        // --- Start: Column Preferences Logic ---
        var streamColumnMetaKey = 'oo_stream_dashboard_columns_<?php echo esc_js($current_stream_tab_slug); ?>';
        <?php
            $stream_column_meta_key = 'oo_stream_dashboard_columns_' . $current_stream_tab_slug;
            $user_stream_default_columns = get_user_meta(get_current_user_id(), $stream_column_meta_key, true);
            if (empty($user_stream_default_columns) || !is_array($user_stream_default_columns)) {
                $user_stream_default_columns = array(); 
            }
        ?>
        let initialDefaultColumns = <?php echo json_encode($user_stream_default_columns); ?>;

        function getFactoryDefaultColumns() {
            return getInitialContentColumns_StreamPage().map(function(col) {
                return { type: 'standard', key: col.data, name: col.title };
            });
        }

        if (!initialDefaultColumns || initialDefaultColumns.length === 0) {
            window.contentSelectedKpiObjects = getFactoryDefaultColumns();
            initialDefaultColumns = getFactoryDefaultColumns(); // The baseline for comparison is now the factory default
        } else {
            window.contentSelectedKpiObjects = JSON.parse(JSON.stringify(initialDefaultColumns));
        }

        function checkColumnChangesAndToggleSaveButton() {
            const normalize = (arr) => JSON.stringify(
                arr.map(o => ({
                    t: o.type, // t for type
                    v: o.type === 'derived' ? o.id.toString() : o.key // v for value/key/id
                })).sort((a, b) => a.v.localeCompare(b.v))
            );

            var currentSelectionNormalized = normalize(window.contentSelectedKpiObjects);
            var defaultSelectionNormalized = normalize(initialDefaultColumns);
            
            if (currentSelectionNormalized !== defaultSelectionNormalized) {
                $saveDefaultButton.show();
            } else {
                $saveDefaultButton.hide();
            }
            $defaultSavedMsg.hide(); 
        }

        $saveDefaultButton.on('click', function(){
            var $button = $(this);
            $button.prop('disabled', true).text('<?php echo esc_js(__("Saving...", "operations-organizer")); ?>');

            $.post(oo_data.ajax_url, {
                action: 'oo_save_user_column_preference',
                _ajax_nonce: oo_data.nonce_save_column_prefs,
                meta_key: streamColumnMetaKey,
                columns: window.contentSelectedKpiObjects
            }, function(response) {
                if (response.success) {
                    $defaultSavedMsg.fadeIn().delay(2000).fadeOut();
                    $button.hide(); // Hide after successful save
                    initialDefaultColumns = JSON.parse(JSON.stringify(window.contentSelectedKpiObjects));
                } else {
                    showNotice('error', response.data.message || '<?php echo esc_js(__("Could not save preference.", "operations-organizer")); ?>');
                }
            }).fail(function() {
                showNotice('error', '<?php echo esc_js(__("Request to save preference failed.", "operations-organizer")); ?>');
            }).always(function() {
                $button.prop('disabled', false).text('<?php echo esc_js(__("Save as Default", "operations-organizer")); ?>');
            });
        });
        // --- End: Column Preferences Logic ---


        if ($modal.length === 0) {
            return; // Modal HTML not present, do nothing.
        }

        function populateKpiColumnSelectorModal() {
            $listContainer.html('<p><?php echo esc_js(__("Loading columns...", "operations-organizer")); ?></p>');

            $.when(
                $.post(oo_data.ajax_url, { 
                    action: 'oo_get_json_kpi_measures_for_stream', 
                    stream_id: <?php echo intval($current_stream_id); ?>,
                    _ajax_nonce: oo_data.nonce_get_kpi_measures
                }),
                $.post(oo_data.ajax_url, { 
                    action: 'oo_get_json_derived_kpi_definitions',
                    stream_id: <?php echo intval($current_stream_id); ?>,
                    _ajax_nonce: oo_data.nonce_get_derived_kpis
                })
            ).done(function(primaryKpisResponse, derivedKpisResponse) {
                var streamPrimaryKpis = (primaryKpisResponse[0] && primaryKpisResponse[0].success) ? primaryKpisResponse[0].data.kpis : [];
                var streamDerivedKpis = (derivedKpisResponse[0] && derivedKpisResponse[0].success) ? derivedKpisResponse[0].data.definitions : [];
                
                var kpiHierarchy = {};
                streamPrimaryKpis.forEach(function(p_kpi) {
                    kpiHierarchy[p_kpi.kpi_measure_id] = {
                        primary: p_kpi,
                        derived: []
                    };
                });
                streamDerivedKpis.forEach(function(d_kpi) {
                    if (kpiHierarchy[d_kpi.primary_kpi_measure_id]) {
                        kpiHierarchy[d_kpi.primary_kpi_measure_id].derived.push(d_kpi);
                    }
                });

                $listContainer.empty();
                var columnsHtml = '<h4><?php echo esc_js(__("Standard Columns", "operations-organizer")); ?></h4>';
                
                var standardColumns = getInitialContentColumns_StreamPage();
                standardColumns.forEach(function(col) {
                    var isChecked = window.contentSelectedKpiObjects.some(selCol => selCol.key === col.data && selCol.type === 'standard');
                    columnsHtml += `<div><label><input type="checkbox" name="kpi_column_select_stream" value="${col.data}" data-col-name="${esc_html(col.title)}" data-col-type="standard" ${isChecked ? 'checked' : ''}> ${esc_html(col.title)}</label></div>`;
                });

                if (Object.keys(kpiHierarchy).length > 0) {
                    columnsHtml += '<h4 style="margin-top:15px;"><?php echo esc_js(__("KPI Columns (Stream Relevant)", "operations-organizer")); ?></h4>';
                    for (var kpiId in kpiHierarchy) {
                        if (kpiHierarchy.hasOwnProperty(kpiId)) {
                            var item = kpiHierarchy[kpiId];
                            var p_kpi = item.primary;
                            var isPChecked = window.contentSelectedKpiObjects.some(selCol => selCol.key === p_kpi.measure_key && selCol.type === 'primary');
                            columnsHtml += `<div style="font-weight: bold;"><label><input type="checkbox" name="kpi_column_select_stream" value="${p_kpi.measure_key}" data-kpi-id="${p_kpi.kpi_measure_id}" data-col-name="${esc_html(p_kpi.measure_name)}" data-col-type="primary" ${isPChecked ? 'checked' : ''}> ${esc_html(p_kpi.measure_name)} (<code>${esc_html(p_kpi.measure_key)}</code>)</label></div>`;

                            if (item.derived.length > 0) {
                                item.derived.forEach(function(d_kpi) {
                                    var isDChecked = window.contentSelectedKpiObjects.some(selCol => selCol.id === d_kpi.derived_definition_id.toString() && selCol.type === 'derived');
                                    columnsHtml += `<div style="margin-left: 25px;"><label><input type="checkbox" name="kpi_column_select_stream" value="${d_kpi.derived_definition_id}" data-col-name="${esc_html(d_kpi.definition_name)}" data-col-type="derived" ${isDChecked ? 'checked' : ''}> ${esc_html(d_kpi.definition_name)}</label></div>`;
                                });
                            }
                        }
                    }
                }

                var rawJsonChecked = window.contentSelectedKpiObjects.some(selCol => selCol.key === 'kpi_data_raw' && selCol.type === 'raw_json');
                columnsHtml += '<h4 style="margin-top: 15px;"><?php echo esc_js(__("Advanced", "operations-organizer")); ?></h4>';
                columnsHtml += `<div><label><input type="checkbox" name="kpi_column_select_stream" value="kpi_data_raw" data-col-name="<?php echo esc_js(__("Raw KPI Data (JSON)", "operations-organizer")); ?>" data-col-type="raw_json" ${rawJsonChecked ? 'checked' : ''}> <?php echo esc_js(__("Raw KPI Data (JSON)", "operations-organizer")); ?></label></div>`;

                $listContainer.html(columnsHtml);
                $listContainer.css('padding-bottom', '20px');
                updateSelectedKpiCount_StreamPage();
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error("Error loading KPI/column data for modal:", textStatus, errorThrown, jqXHR.responseText);
                $listContainer.html('<p style="color:red;"><?php echo esc_js(__("Error loading column options. Check console and ensure AJAX handlers exist and return JSON.", "operations-organizer")); ?></p>');
            });
        }

        // Open Modal
        $('#content_open_kpi_selector_modal').on('click', function() {
            populateKpiColumnSelectorModal();
            $modal.show();
        });

        // Apply selected columns
        $('#apply_selected_kpi_columns_stream_' + streamSlugForModal).on('click', function() {
            window.contentSelectedKpiObjects = []; 
            $listContainer.find('input[name="kpi_column_select_stream"]:checked').each(function() {
                var $cb = $(this);
                var colType = $cb.data('col-type');
                var val = $cb.val();
                var name = $cb.data('col-name');
                var kpiId = $cb.data('kpi-id');
                
                if (colType === 'standard') window.contentSelectedKpiObjects.push({ type: 'standard', key: val, name: name });
                else if (colType === 'primary') window.contentSelectedKpiObjects.push({ type: 'primary', key: val, id: kpiId, name: name });
                else if (colType === 'derived') window.contentSelectedKpiObjects.push({ type: 'derived', id: val, name: name });
                else if (colType === 'raw_json') window.contentSelectedKpiObjects.push({ type: 'raw_json', key: 'kpi_data_raw', name: name});
            });
            $modal.hide();
            reinitializeContentDashboardTable_StreamPage();
            checkColumnChangesAndToggleSaveButton();
        });

        // Select/Deselect All
        $('#kpi_selector_select_all_stream_' + streamSlugForModal).on('click', function() {
            $listContainer.find('input[type="checkbox"]').prop('checked', true).trigger('change');
        });
        $('#kpi_selector_deselect_all_stream_' + streamSlugForModal).on('click', function() {
            $listContainer.find('input[type="checkbox"]').prop('checked', false).trigger('change');
        });

        // Update count on change
        $listContainer.on('change', 'input[type="checkbox"]', function() {
            updateSelectedKpiCount_StreamPage();
        });

        function updateSelectedKpiCount_StreamPage() {
            var count = $listContainer.find('input[name="kpi_column_select_stream"]:checked').length;
            $('#content_selected_kpi_count').text(count + ' columns selected');
        }

        // Close Modal via X button
        $modal.on('click', '.oo-modal-close', function() {
            $modal.hide();
        });

        // Close Modal by clicking on the backdrop
        $(window).on('click', function(event) {
            if ($(event.target).is($modal)) {
                $modal.hide();
            }
        });

        reinitializeContentDashboardTable_StreamPage(); // Initial load of the table
    })();
    // --- End KPI Column Selector Modal - Open/Close Logic ---

});
</script> 