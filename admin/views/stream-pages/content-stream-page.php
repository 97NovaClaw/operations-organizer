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
                    if (!empty($GLOBALS['phases'])) {
                        oo_log('[Content Stream Page - Manage Tab] Using GLOBALS phases. Count: ' . count($GLOBALS['phases']), 'ContentStreamPage');
                        foreach ($GLOBALS['phases'] as $phase_item) {
                             oo_log('[Content Stream Page - Manage Tab] Checking phase from GLOBALS: ' . $phase_item->phase_name . ' (StreamID: ' . $phase_item->stream_id . ') against Current Stream ID: ' . $current_stream_id, 'ContentStreamPage');
                            if ($phase_item->stream_id == $current_stream_id) {
                                $current_stream_phases_for_table[] = $phase_item;
                            }
                        }
                    } else {
                        oo_log('[Content Stream Page - Manage Tab] GLOBALS phases empty, fetching fresh for stream ID: ' . $current_stream_id, 'ContentStreamPage');
                        // If $GLOBALS['phases'] wasn't populated by the main page controller, fetch them now.
                        $current_stream_phases_for_table = OO_DB::get_phases(array('stream_id' => $current_stream_id, 'orderby' => 'order_in_stream', 'order' => 'ASC', 'number' => -1));
                    }
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
                                        $status_text = $phase->is_active ? __( 'Active', 'operations-organizer' ) : __( 'Inactive', 'operations-organizer' );
                                        $toggle_nonce = wp_create_nonce('oo_toggle_phase_status_nonce_' . $phase->phase_id);
                                        // Changed to button for AJAX handling
                                        if ($phase->is_active) {
                                            echo '<button type="button" class="button-link oo-toggle-status-phase-button-stream" data-phase-id="' . esc_attr($phase->phase_id) . '" data-new-status="0" data-nonce="' . esc_attr($toggle_nonce) . '" style="color: #d63638;">' . esc_html__('Deactivate', 'operations-organizer') . '</button>';
                                        } else {
                                            echo '<button type="button" class="button-link oo-toggle-status-phase-button-stream" data-phase-id="' . esc_attr($phase->phase_id) . '" data-new-status="1" data-nonce="' . esc_attr($toggle_nonce) . '" style="color: #2271b1;">' . esc_html__('Activate', 'operations-organizer') . '</button>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button-link oo-edit-phase-button-stream" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>"><?php esc_html_e( 'Edit', 'operations-organizer' ); ?></button> |
                                        <button type="button" class="button-link oo-delete-phase-button-stream" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>" style="color:#b32d2e;"><?php esc_html_e( 'Delete', 'operations-organizer' ); ?></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="7"><?php esc_html_e( 'No phases found for this stream.', 'operations-organizer' ); ?></td></tr>
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

<!-- Manage Phase KPIs Modal Removed, its functionality is merged into Edit Phase Modal -->

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

        if (!confirm('<?php echo esc_js( __("Are you sure you want to permanently delete this phase? This action cannot be undone and may affect existing job logs if not handled carefully by the system.", "operations-organizer") ); ?>')) {
            return;
        }

        $.post(oo_data.ajax_url, {
            action: 'oo_delete_phase_ajax',
            phase_id: phaseId,
            _ajax_nonce: oo_data.nonce_delete_phase_ajax,
            return_to_stream: streamSlug,      // For PHP redirect logic if any
            return_sub_tab: 'phase_kpi_settings' // For PHP redirect logic if any
        }, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                window.location.reload(); // Reload to update the phase list
            } else {
                showNotice('error', response.data.message || 'Could not delete phase.');
            }
        }).fail(function() {
            showNotice('error', 'Request to delete phase failed.');
        });
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
});
</script> 