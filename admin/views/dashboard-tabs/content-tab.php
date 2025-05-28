<?php
// /admin/views/dashboard-tabs/content-tab.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get the phases specific to the Content stream (ID 4)
$content_phases = array();
foreach ($phases as $phase) {
    // Only include phases that belong to Content stream (ID 4) AND have includes_kpi enabled
    if ($phase->stream_id == 4 && !empty($phase->includes_kpi)) {
        $content_phases[] = $phase;
    }
}
?>
<div class="oo-tab-content">
    <h2><?php esc_html_e('Content Stream', 'operations-organizer'); ?></h2>
    
    <div class="oo-dashboard-section">
        <h3><?php esc_html_e('Quick Phase Actions', 'operations-organizer'); ?></h3>
        <p><?php esc_html_e('Enter a Job Number and select a phase to start or stop.', 'operations-organizer'); ?></p>
        
        <?php if (!empty($content_phases)): ?>
            <table class="form-table">
                <?php foreach ($content_phases as $phase): ?>
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
        <?php else: ?>
            <p class="oo-notice"><?php esc_html_e('No phases have been configured for the Content stream. Please add some phases through the Phases management page.', 'operations-organizer'); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="oo-dashboard-section">
        <h3><?php esc_html_e('Checkpoint Progress', 'operations-organizer'); ?></h3>
        <p class="description"><?php esc_html_e('Content process flow: Pickup & Inventory → Purge → Estimate → Approval → Invoice → Payment → Cleaned → In Storage → Delivered', 'operations-organizer'); ?></p>
        
        <div class="oo-filter-section">
            <label for="ct_filter_job_number"><?php esc_html_e('Filter by Job Number:', 'operations-organizer'); ?></label>
            <input type="text" id="ct_filter_job_number" class="regular-text" placeholder="<?php esc_attr_e('Enter job number', 'operations-organizer'); ?>">
            <button id="ct_apply_filter" class="button button-secondary"><?php esc_html_e('Apply', 'operations-organizer'); ?></button>
        </div>
        
        <div class="oo-placeholder-kanban">
            <p class="oo-notice oo-info">
                <?php esc_html_e('The Kanban board for Content stream management will be implemented here in a future update. It will display jobs in columns representing their current checkpoint in the workflow.', 'operations-organizer'); ?>
            </p>
        </div>
    </div>
    
    <div class="oo-dashboard-section">
        <h3><?php esc_html_e('Key Metrics', 'operations-organizer'); ?></h3>
        
        <div class="oo-kpi-cards">
            <div class="oo-kpi-card">
                <h4><?php esc_html_e('Content Items Processed', 'operations-organizer'); ?></h4>
                <div class="oo-kpi-value">-</div>
                <div class="oo-kpi-label"><?php esc_html_e('items (last 30 days)', 'operations-organizer'); ?></div>
            </div>
            
            <div class="oo-kpi-card">
                <h4><?php esc_html_e('Avg. Processing Time', 'operations-organizer'); ?></h4>
                <div class="oo-kpi-value">-</div>
                <div class="oo-kpi-label"><?php esc_html_e('per item', 'operations-organizer'); ?></div>
            </div>
            
            <div class="oo-kpi-card">
                <h4><?php esc_html_e('Jobs In Progress', 'operations-organizer'); ?></h4>
                <div class="oo-kpi-value">-</div>
                <div class="oo-kpi-label"><?php esc_html_e('active jobs', 'operations-organizer'); ?></div>
            </div>
        </div>
        
        <div class="oo-placeholder-charts">
            <p class="oo-notice oo-info">
                <?php esc_html_e('Detailed metrics and visualizations for the Content stream will be implemented here in a future update. These will include processing time trends, content type statistics, and more.', 'operations-organizer'); ?>
            </p>
        </div>
    </div>

    <div class="oo-dashboard-section">
        <h3><?php esc_html_e('Content Stream Jobs Data', 'operations-organizer'); ?></h3>
        <p><?php esc_html_e('This table shows job logs specific to the Content stream.', 'operations-organizer'); ?></p>
        
        <div class="notice notice-info" style="padding: 10px; margin: 10px 0 20px;">
            <p>
                <strong><?php esc_html_e('Running Job Found:', 'operations-organizer'); ?></strong> 
                <?php esc_html_e('To stop the running job (Log ID: 1), click here:', 'operations-organizer'); ?>
                <a href="<?php echo admin_url('admin.php?page=oo_stop_job&log_id=1'); ?>" class="button button-primary"><?php esc_html_e('Stop Job ID: 1', 'operations-organizer'); ?></a>
            </p>
        </div>
        
        <div class="oo-filter-section">
            <div class="filter-row">
                <div class="filter-item">
                    <label for="content_filter_employee_id"><?php esc_html_e('Employee:', 'operations-organizer');?></label>
                    <select id="content_filter_employee_id" name="content_filter_employee_id">
                        <option value=""><?php esc_html_e('All Employees', 'operations-organizer');?></option>
                        <?php foreach ($employees as $employee): ?>
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
                        <option value=""><?php esc_html_e('All Phases', 'operations-organizer');?></option>
                         <?php if (!empty($content_phases)): foreach ($content_phases as $phase): ?>
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
                 <label style="font-weight: bold;"><?php esc_html_e('Select KPI Columns to Display:', 'operations-organizer'); ?></label>
                 <button type="button" id="content_open_kpi_selector_modal" class="button"><?php esc_html_e('Choose KPIs', 'operations-organizer'); ?></button>
                 <span id="content_selected_kpi_count" style="margin-left: 10px;"></span>
            </div>
        </div>
        
        <div id="content-export-options" style="margin-bottom: 20px;">
            <button id="content_export_csv_button" class="button"><?php esc_html_e('Export to CSV', 'operations-organizer');?></button>
        </div>

        <table id="content-dashboard-table" class="display wp-list-table widefat fixed striped" style="width:100%">
            <thead>
                <tr>
                    <th><?php esc_html_e('Employee Name', 'operations-organizer'); ?></th>
                    <th><?php esc_html_e('Job No.', 'operations-organizer'); ?></th>
                    <th><?php esc_html_e('Phase', 'operations-organizer'); ?></th>
                    <th><?php esc_html_e('Start Time', 'operations-organizer'); ?></th>
                    <th><?php esc_html_e('End Time', 'operations-organizer'); ?></th>
                    <th><?php esc_html_e('Duration', 'operations-organizer'); ?></th>
                    <th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                    <th><?php esc_html_e('Notes', 'operations-organizer'); ?></th>
                    <th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
                </tr>
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
                    <!-- Hidden fields for required data -->
                    <input type="hidden" id="edit_log_id" name="edit_log_id">
                    <input type="hidden" id="edit_log_employee_id" name="edit_log_employee_id">
                    <input type="hidden" id="edit_log_job_id" name="edit_log_job_id">
                    <input type="hidden" id="edit_log_phase_id" name="edit_log_phase_id">
                    <input type="hidden" id="edit_log_stream_id" name="edit_log_stream_id" value="4"> <!-- Content stream -->
                    <input type="hidden" id="edit_log_start_time" name="edit_log_start_time">
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
                        <label for="edit_log_end_time" id="edit_log_end_time_label"><?php esc_html_e('End Time', 'operations-organizer'); ?></label>
                        <div style="display: flex; align-items: center;">
                            <input type="datetime-local" id="edit_log_end_time" name="edit_log_end_time" style="flex-grow: 1;">
                            <button type="button" id="set_end_time_now" class="button" style="margin-left: 10px;"><?php esc_html_e('Now', 'operations-organizer'); ?></button>
                        </div>
                        <div class="form-description" id="edit_log_end_time_description" style="display:none;">
                            <?php esc_html_e('Leave blank to keep job running, or set date/time to stop the job.', 'operations-organizer'); ?>
                        </div>
                    </div>
                    
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

<style>
/* Modal styles */
.oo-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.oo-modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 20px;
    border: 1px solid #ddd;
    width: 50%;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    position: relative;
}

.oo-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.oo-modal-close:hover,
.oo-modal-close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.form-field {
    margin-bottom: 15px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-field input[type="text"],
.form-field input[type="number"],
.form-field textarea {
    width: 100%;
    padding: 8px;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // JS for Quick Phase Actions in Content tab
    $('.oo-tab-content .oo-start-link-btn, .oo-tab-content .oo-stop-link-btn').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $row = $button.closest('.oo-phase-action-row');
        var jobNumber = $row.find('.oo-job-number-input').val();
        var phaseId = $button.data('phase-id');
        var isAdminUrl = '<?php echo admin_url("admin.php"); ?>';

        if (!jobNumber) {
            alert('<?php echo esc_js(__("Please enter a Job Number first.", "operations-organizer")); ?>');
            return;
        }

        var actionPage = $button.hasClass('oo-start-link-btn') ? 'oo_start_job' : 'oo_stop_job';
        var url = isAdminUrl + '?page=' + actionPage + '&job_number=' + encodeURIComponent(jobNumber) + '&phase_id=' + encodeURIComponent(phaseId);
        
        window.location.href = url;
    });
    
    // Placeholder for filter functionality
    $('#ct_apply_filter').on('click', function() {
        var jobNumber = $('#ct_filter_job_number').val();
        if (jobNumber) {
            alert('<?php echo esc_js(__("Filter functionality will be implemented in a future update. Job Number: ", "operations-organizer")); ?>' + jobNumber);
        } else {
            alert('<?php echo esc_js(__("Please enter a job number to filter.", "operations-organizer")); ?>');
        }
    });

    // Initialize the Content Stream-specific DataTable
    var initialContentColumns = getInitialContentColumns(); // Get base columns
    var contentDashboardTable; // Declare globally for this scope

    function initializeContentDashboardTable(dynamicColumnObjects) {
        var columnsConfig = [].concat(initialContentColumns); // Start with base columns
        
        // Add dynamic KPI columns if any are selected
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
                        defaultContent: 'N/A (Calc Pending)',
                        orderable: true, 
                        searchable: true 
                    });
                } else if (kpi.type === 'raw_json') {
                     columnsConfig.push({
                        data: 'kpi_data',
                        title: kpi.name,
                        defaultContent: 'N/A',
                        orderable: false, // JSON data not easily sortable
                        searchable: true 
                    });
                }
            });
        }
        // Add the ثابت Actions column at the end
        columnsConfig.push(getActionsColumnDefinition());

        if ($.fn.DataTable.isDataTable('#content-dashboard-table')) {
            contentDashboardTable.destroy();
            $('#content-dashboard-table').empty(); // Clear headers and tbody to avoid duplication
        }

        contentDashboardTable = $('#content-dashboard-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: oo_data.ajax_url,
                type: 'POST',
                data: function(d) { 
                    d.action = 'oo_get_dashboard_data';
                    d.nonce = '<?php echo wp_create_nonce("oo_dashboard_nonce"); ?>';
                    d.filter_employee_id = $('#content_filter_employee_id').val();
                    d.filter_job_number = $('#content_filter_job_number').val();
                    d.filter_phase_id = $('#content_filter_phase_id').val();
                    d.filter_date_from = $('#content_filter_date_from').val();
                    d.filter_date_to = $('#content_filter_date_to').val();
                    d.filter_status = $('#content_filter_status').val();
                    d.filter_stream_id = 4; // Always filter by Content stream (ID 4)
                    d.selected_columns_config = window.contentSelectedKpiObjects || []; // Changed parameter name
                    
                    // Default order if not provided by DataTable (e.g. initial load)
                    if (!d.order || d.order.length === 0) {
                         d.order = [{ "column": getColumnIndexByData('start_time', columnsConfig), "dir": "desc" }];
                    }
                    console.log('Sending content table request data:', d);
                    return d;
                },
                dataSrc: function(json) {
                    console.log('Received content table response:', json);
                    
                    // For complete response debugging
                    console.log('Raw JSON Response:', JSON.stringify(json).substring(0, 1000) + '...');
                    
                    // Get the data from the response
                    var dataArray = [];
                    
                    try {
                        // Handle WordPress-style AJAX response (wp_send_json_success)
                        if (json && json.success === true && json.data) {
                            if (json.data.data && Array.isArray(json.data.data)) {
                                dataArray = json.data.data;
                                console.log('Found data array in json.data.data, length:', dataArray.length);
                            } else if (Array.isArray(json.data)) {
                                dataArray = json.data;
                                console.log('Found data array in json.data, length:', dataArray.length);
                            } else if (typeof json.data === 'object' && json.data !== null) {
                                // Handle case where json.data is an object with recordsTotal, recordsFiltered, and data properties
                                if (json.data.data && Array.isArray(json.data.data)) {
                                    dataArray = json.data.data;
                                    console.log('Found data array in json.data.data (object format), length:', dataArray.length);
                                }
                            }
                        } 
                        // Direct data response
                        else if (json && json.data && Array.isArray(json.data)) {
                            dataArray = json.data;
                            console.log('Found data array in json.data, length:', dataArray.length);
                        }
                        // Try to handle other formats
                        else if (json && Array.isArray(json)) {
                            dataArray = json;
                            console.log('Found data directly in json array, length:', dataArray.length);
                        }
                        
                        // Default to empty array if no data found
                        if (!dataArray || !Array.isArray(dataArray)) {
                            console.warn('Could not find data array in response, defaulting to empty array');
                            dataArray = [];
                        }
                        
                        // Log the first item for debugging
                        if (dataArray && dataArray.length > 0) {
                            console.log('First data record:', dataArray[0]);
                            
                            // Verify data has required properties
                            var missingProps = [];
                            var requiredProps = ['employee_name', 'job_number', 'phase_name', 'start_time', 'end_time', 'duration'];
                            for (var i = 0; i < requiredProps.length; i++) {
                                if (typeof dataArray[0][requiredProps[i]] === 'undefined') {
                                    missingProps.push(requiredProps[i]);
                                }
                            }
                            
                            if (missingProps.length > 0) {
                                console.error('Data missing required properties:', missingProps.join(', '));
                            } else {
                                console.log('Data has all required properties');
                            }
                        } else {
                            console.log('No data records found in response');
                        }
                        
                        return dataArray;
                    } catch (e) {
                        console.error('Error parsing data from AJAX response:', e);
                        return [];
                    }
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTables AJAX error:', error, thrown);
                    console.log('XHR Response:', xhr.responseText);
                    alert('Error loading data: ' + error);
                }
            },
            columns: columnsConfig, // Use the combined columns configuration
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            order: [[getColumnIndexByData('start_time', columnsConfig), 'desc']], // Initial order by start_time
            pageLength: 10,
            language: {
                search: "<?php esc_attr_e('Search:', 'operations-organizer'); ?>",
                emptyTable: "<?php esc_attr_e('No job logs found for the Content stream', 'operations-organizer'); ?>",
                zeroRecords: "<?php esc_attr_e('No matching records found', 'operations-organizer'); ?>",
                processing: "<?php esc_attr_e('Loading data...', 'operations-organizer'); ?>"
            },
            drawCallback: function(settings) {
                var api = this.api();
                var pageInfo = api.page.info();
                var displayedRows = pageInfo.recordsDisplay; // Total records matching current filter
                var onPageRows = api.rows( { page: 'current' } ).count(); // Rows on the current page

                console.log('Table draw complete. Displayed/Filtered rows:', displayedRows, 'Rows on current page:', onPageRows, 'Total in table:', pageInfo.recordsTotal);
                
                if (displayedRows === 0) {
                    console.log('No records to display after filtering (or no data at all).');
                    var columnCount = api.columns().header().length; // Get current number of columns
                    $('#content-dashboard-table tbody').html(
                        '<tr><td colspan="' + columnCount + '" class="dataTables_empty" style="padding: 20px; text-align: center;">' +
                        '<?php esc_html_e('No job logs found matching your criteria. Try clearing filters or adding job logs.', 'operations-organizer'); ?>' +
                        '</td></tr>'
                    );
                }
            },
            initComplete: function(settings, json) {
                console.log('DataTable initialization complete');
                var api = this.api();
                
                var allDataOnInit = api.rows().data().toArray();
                console.log('Data array on init:', allDataOnInit);
                console.log('Data array length on init:', allDataOnInit.length);
                
                if (allDataOnInit.length > 0 && api.rows( { page: 'current' } ).count() === 0) {
                    console.log('DataTable has data but is not displaying rows on current page. Forcing redraw.');
                    setTimeout(function() {
                        api.draw(false); 
                        console.log('Forced redraw completed on init.');
                    }, 200);
                } else if(allDataOnInit.length === 0 && json && json.data && json.data.recordsFiltered > 0){
                    console.warn('DataTables received records but data array is empty. Check dataSrc function or response format.');
                }
            }
        });
    }

    // Store selected KPI keys globally for DataTable AJAX call
    window.contentSelectedKpiKeys = []; // Default selected KPIs (empty)
    window.contentSelectedKpiObjects = []; // Default selected KPI objects for titles (empty)

    function getInitialContentColumns() {
        // Define base columns that are always present
        // IMPORTANT: The 'KPI Data' (generic) and 'Actions' columns will be handled separately or at the end
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
            // { data: "kpi_data", title: "<?php esc_html_e('All KPI Data (JSON)', 'operations-organizer'); ?>" } // Removed, now optional via selector
        ];
    }

    function getActionsColumnDefinition(){
        return {
            data: null, 
            title: "<?php esc_html_e('Actions', 'operations-organizer'); ?>",
            orderable: false,
            searchable: false,
            render: function (data, type, row) {
                if (!row || !row.log_id) {
                    console.error('Missing log_id for row:', row);
                    return '<div class="row-actions"><span>Error: No log ID</span></div>';
                }
                var actionButtons = '<div class="row-actions">';
                if (row.status && (row.status.toLowerCase().includes('running') || row.status.toLowerCase().includes('started'))) {
                    actionButtons += '<span class="edit"><a href="' + 
                        oo_data.admin_url + 'admin.php?page=oo_stop_job&log_id=' + row.log_id + 
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
    
    function getColumnIndexByData(dataProperty, columnsArray) {
        for (var i = 0; i < columnsArray.length; i++) {
            if (columnsArray[i].data === dataProperty) {
                return i;
            }
        }
        return 0; // Default to first column if not found (e.g. for start_time)
    }

    function reinitializeContentDashboardTable(){
        initializeContentDashboardTable(window.contentSelectedKpiObjects || []);
    }
    
    // Initial load of the table with default KPI columns
    reinitializeContentDashboardTable();

    // Apply filters when clicking the apply button
    $('#content_apply_filters_button').on('click', function(e) {
        e.preventDefault();
        contentDashboardTable.ajax.reload();
    });

    // Clear filters when clicking the clear button
    $('#content_clear_filters_button').on('click', function(e) {
        e.preventDefault();
        $('#content_filter_employee_id, #content_filter_job_number, #content_filter_phase_id, #content_filter_date_from, #content_filter_date_to, #content_filter_status').val('');
        contentDashboardTable.ajax.reload();
    });

    // Export to CSV functionality
    $('#content_export_csv_button').on('click', function(e) {
        e.preventDefault();
        var exportParams = {
            action: 'oo_export_dashboard_csv',
            nonce: '<?php echo wp_create_nonce("oo_export_dashboard_csv_nonce"); ?>',
            filter_employee_id: $('#content_filter_employee_id').val(),
            filter_job_number: $('#content_filter_job_number').val(),
            filter_phase_id: $('#content_filter_phase_id').val(),
            filter_date_from: $('#content_filter_date_from').val(),
            filter_date_to: $('#content_filter_date_to').val(),
            filter_status: $('#content_filter_status').val(),
            filter_stream_id: 4, // Always export Content stream data
        };

        // Create a form to submit the export request
        var $form = $('<form>', {
            action: oo_data.ajax_url,
            method: 'POST',
            target: '_blank'
        });

        // Add all export parameters as hidden fields
        $.each(exportParams, function(key, value) {
            $('<input>').attr({
                type: 'hidden',
                name: key,
                value: value
            }).appendTo($form);
        });

        // Append the form to the body and submit it
        $form.appendTo('body').submit().remove();
    });

    // Set up datepickers if not already done
    if ($.fn.datepicker) {
        $('#content_filter_date_from, #content_filter_date_to').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    }
    
    // Edit job log functionality
    $(document).on('click', '.oo-edit-log-action', function(e) {
        e.preventDefault();
        var logId = $(this).data('log-id');
        
        // Clear form
        $('#edit-log-form')[0].reset();
        $('#edit_log_id').val(logId);
        
        // Reset all buttons to their original state
        resetButtonStates();
        
        // Get the job number directly from the table row if possible
        var row = $(this).closest('tr');
        var tableJobNumber = '';
        if (row.length) {
            // Find job number from the row data (second cell in the row)
            tableJobNumber = row.find('td:eq(1)').text().trim();
            console.log("Found job number from table row:", tableJobNumber);
        }
        
        // Load job log details via AJAX
        $.ajax({
            url: oo_data.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'oo_get_job_log_details',
                log_id: logId,
                nonce: oo_data.nonce_edit_log
            },
            success: function(response) {
                if (response.success && response.data) {
                    var logData = response.data;
                    console.log("Log data received:", logData);
                    
                    // Populate form fields with all required data
                    $('#edit_log_employee_id').val(logData.employee_id || '');
                    $('#edit_log_job_id').val(logData.job_id || '');
                    
                    // Make sure job number is properly displayed - use multiple sources in order of preference
                    var jobNumber = '';
                    if (logData.job_number) {
                        // Use job_number from AJAX response if available
                        jobNumber = logData.job_number;
                        console.log("Using job number from AJAX response:", jobNumber);
                    } else if (tableJobNumber) {
                        // Use job number from table row as fallback
                        jobNumber = tableJobNumber;
                        console.log("Using job number from table row:", jobNumber);
                    } else if (logData.job_id) {
                        console.log("No job_number found, but job_id exists:", logData.job_id);
                    }
                    
                    // Set the job number field
                    if (jobNumber) {
                        $('#edit_log_job_number').val(jobNumber);
                    }
                    
                    $('#edit_log_phase_id').val(logData.phase_id || '');
                    $('#edit_log_start_time').val(logData.start_time || '');
                    $('#edit_log_status').val(logData.status || 'started');
                    $('#edit_log_notes').val(logData.notes || '');
                    
                    // Reset job number edit checkbox
                    $('#enable_job_number_edit').prop('checked', false);
                    $('#edit_log_job_number').attr('readonly', true);
                    $('#job_number_warning').hide();
                    
                    // Check if this is a running job or a completed job
                    var isRunning = logData.status === 'started' || !logData.end_time;
                    
                    if (isRunning) {
                        // This is a running job - show options to keep running or stop
                        $('#save_as_running').show();
                        $('#save_as_completed').show();
                        $('#save_changes').hide();
                        
                        // Show end time field and description
                        $('#edit_log_end_time_label').show();
                        $('#edit_log_end_time').show();
                        $('#edit_log_end_time_description').show();
                        $('#set_end_time_now').show();
                    } else {
                        // This is a completed job - just show save changes button
                        $('#save_as_running').hide();
                        $('#save_as_completed').hide();
                        $('#save_changes').show();
                        
                        // Set end time if available
                        if (logData.end_time) {
                            $('#edit_log_end_time').val(logData.end_time);
                        }
                        $('#set_end_time_now').show();
                    }
                    
                    // Show modal
                    $('#edit-log-modal').css('display', 'block');
                } else {
                    alert('<?php echo esc_js(__("Error loading job log details.", "operations-organizer")); ?>');
                    console.error('Error loading job log details:', response);
                }
            },
            error: function(xhr, status, error) {
                alert('<?php echo esc_js(__("Error loading job log details.", "operations-organizer")); ?>');
                console.error('AJAX error:', status, error);
            }
        });
    });
    
    // Set current time for end_time field
    $('#set_end_time_now').on('click', function() {
        var now = new Date();
        var formattedDateTime = now.getFullYear() + '-' + 
                               padNumber(now.getMonth() + 1) + '-' + 
                               padNumber(now.getDate()) + 'T' + 
                               padNumber(now.getHours()) + ':' + 
                               padNumber(now.getMinutes());
        $('#edit_log_end_time').val(formattedDateTime);
    });
    
    // Function to reset all button states
    function resetButtonStates() {
        var submitButtons = $('#save_as_running, #save_as_completed, #save_changes');
        submitButtons.prop('disabled', false).each(function() {
            // Reset to original text if it was saved
            if ($(this).data('original-text')) {
                $(this).text($(this).data('original-text'));
            } else {
                // Or use default text based on button ID
                if (this.id === 'save_as_running') {
                    $(this).text('<?php echo esc_js(__("Save Changes (Keep Running)", "operations-organizer")); ?>');
                } else if (this.id === 'save_as_completed') {
                    $(this).text('<?php echo esc_js(__("Save Changes & Stop Job", "operations-organizer")); ?>');
                } else if (this.id === 'save_changes') {
                    $(this).text('<?php echo esc_js(__("Save Changes", "operations-organizer")); ?>');
                }
            }
        });
    }
    
    // Toggle job number field editability
    $('#enable_job_number_edit').on('change', function() {
        if ($(this).is(':checked')) {
            // If checked, make the job number field editable
            $('#edit_log_job_number').attr('readonly', false);
            $('#job_number_warning').show();
            
            // Confirm the user wants to make this change
            if (!confirm('<?php echo esc_js(__("Are you sure you want to edit the job number? This should only be done if the job number is incorrect.", "operations-organizer")); ?>')) {
                // If user cancels, uncheck the box and keep the field readonly
                $(this).prop('checked', false);
                $('#edit_log_job_number').attr('readonly', true);
                $('#job_number_warning').hide();
            } else {
                // Store original job number for comparison later
                $('#edit_log_job_number').data('original-value', $('#edit_log_job_number').val());
            }
        } else {
            // If unchecked, make the job number field readonly again
            $('#edit_log_job_number').attr('readonly', true);
            $('#job_number_warning').hide();
        }
    });
    
    // Add change handler for job number field
    $('#edit_log_job_number').on('change', function() {
        if ($('#enable_job_number_edit').is(':checked')) {
            var newJobNumber = $(this).val().trim();
            var originalJobNumber = $(this).data('original-value');
            
            // Only check if the job number has actually changed
            if (newJobNumber !== originalJobNumber) {
                // Display a message informing the user about the validation that will happen
                $('#job_number_warning').html('<?php echo esc_js(__("The job number will be validated when you save. If the job doesn't exist, the change will be rejected.", "operations-organizer")); ?>');
            }
        }
    });
    
    // Handle edit form submission for different button types
    $('#save_as_running').on('click', function(e) {
        e.preventDefault();
        // Set status to started and clear end_time
        $('#edit_log_status').val('started');
        $('#edit_log_end_time').val('');
        submitEditLogForm();
    });
    
    $('#save_as_completed').on('click', function(e) {
        e.preventDefault();
        // Set status to completed and set end_time to now if not provided
        $('#edit_log_status').val('completed');
        if (!$('#edit_log_end_time').val()) {
            var now = new Date();
            var formattedDateTime = now.getFullYear() + '-' + 
                                   padNumber(now.getMonth() + 1) + '-' + 
                                   padNumber(now.getDate()) + 'T' + 
                                   padNumber(now.getHours()) + ':' + 
                                   padNumber(now.getMinutes());
            $('#edit_log_end_time').val(formattedDateTime);
        }
        submitEditLogForm();
    });
    
    $('#save_changes').on('click', function(e) {
        e.preventDefault();
        // Just submit the form with existing values
        submitEditLogForm();
    });
    
    // Helper function to pad numbers with leading zero
    function padNumber(num) {
        return num.toString().padStart(2, '0');
    }
    
    // Function to submit the edit log form
    function submitEditLogForm() {
        // Show loading indicator or disable submit button
        var submitButtons = $('#save_as_running, #save_as_completed, #save_changes');
        submitButtons.prop('disabled', true).each(function() {
            $(this).data('original-text', $(this).text());
            $(this).text('<?php echo esc_js(__("Saving...", "operations-organizer")); ?>');
        });
        
        $.ajax({
            url: oo_data.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: $('#edit-log-form').serialize(),
            success: function(response) {
                if (response.success) {
                    // Close modal and reload table
                    $('#edit-log-modal').css('display', 'none');
                    
                    // Force a complete reload of the data, not just a redraw
                    contentDashboardTable.ajax.reload(function() {
                        console.log('DataTable fully reloaded after edit');
                    }, false);
                    
                    alert('<?php echo esc_js(__("Job log updated successfully.", "operations-organizer")); ?>');
                    // Reset button states
                    resetButtonStates();
                } else {
                    alert('<?php echo esc_js(__("Error updating job log: ", "operations-organizer")); ?>' + (response.data ? response.data.message : ''));
                    console.error('Error updating job log:', response);
                    
                    // Re-enable buttons
                    resetButtonStates();
                }
            },
            error: function(xhr, status, error) {
                alert('<?php echo esc_js(__("Error updating job log.", "operations-organizer")); ?>');
                console.error('AJAX error:', status, error);
                
                // Re-enable buttons
                resetButtonStates();
            }
        });
    }
    
    // Delete job log functionality
    $(document).on('click', '.oo-delete-log-action', function(e) {
        e.preventDefault();
        var logId = $(this).data('log-id');
        
        // Set log ID in delete form
        $('#delete_log_id').val(logId);
        
        // Show delete confirmation modal
        $('#delete-log-modal').css('display', 'block');
    });
    
    // Handle delete form submission
    $('#delete-log-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: oo_data.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    // Close modal and reload table
                    $('#delete-log-modal').css('display', 'none');
                    contentDashboardTable.ajax.reload();
                    alert('<?php echo esc_js(__("Job log deleted successfully.", "operations-organizer")); ?>');
                } else {
                    alert('<?php echo esc_js(__("Error deleting job log: ", "operations-organizer")); ?>' + (response.data ? response.data.message : ''));
                    console.error('Error deleting job log:', response);
                }
            },
            error: function(xhr, status, error) {
                alert('<?php echo esc_js(__("Error deleting job log.", "operations-organizer")); ?>');
                console.error('AJAX error:', status, error);
            }
        });
    });
    
    // Close modal when clicking the X or Cancel button
    $('.oo-modal-close, .oo-modal-cancel').on('click', function() {
        $('.oo-modal').css('display', 'none');
    });
    
    // Close modal when clicking outside the modal content
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('oo-modal')) {
            $('.oo-modal').css('display', 'none');
        }
    });

    // --- KPI Column Selector Modal --- 
    var kpiColumnModal = $('<div id="kpi-column-selector-modal" class="oo-modal"><div class="oo-modal-content" style="width: 600px; max-height: 80vh; overflow-y: auto;"><span class="oo-modal-close">&times;</span><h2><?php esc_html_e('Select KPI Columns', 'operations-organizer'); ?></h2><p><?php esc_html_e('Choose which KPI measures you want to see as columns in the table. Standard columns will always be shown.', 'operations-organizer'); ?></p><div id="kpi-measures-checkbox-list" style="margin-bottom: 20px; max-height: 40vh; overflow-y: auto; border: 1px solid #eee; padding: 10px;"><?php 
                    $all_kpi_measures = OO_DB::get_kpi_measures(array('is_active' => 1, 'orderby' => 'measure_name', 'order' => 'ASC'));
                    if (!empty($all_kpi_measures)) {
                        foreach ($all_kpi_measures as $measure) {
                            echo '<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="selected_kpi_columns[]" value="' . esc_attr($measure->measure_key) . '" data-measure-name="' . esc_attr($measure->measure_name) . '" data-kpi-type="primary"> ' . esc_html($measure->measure_name) . ' (<code>' . esc_html($measure->measure_key) . '</code>)</label>';
                            // Fetch and display derived KPIs for this primary KPI
                            $derived_definitions = OO_DB::get_derived_kpi_definitions(array('primary_kpi_measure_id' => $measure->kpi_measure_id, 'is_active' => 1));
                            if (!empty($derived_definitions)) {
                                echo '<div style="margin-left: 20px; padding-left: 10px; border-left: 1px solid #eee;">';
                                foreach ($derived_definitions as $derived_def) {
                                    $derived_value_attr = 'derived::' . esc_attr($derived_def->derived_definition_id);
                                    echo '<label style="display: block; margin-bottom: 3px; font-weight:normal;">';
                                    echo '<input type="checkbox" name="selected_kpi_columns[]" value="' . $derived_value_attr . '" ';
                                    echo 'data-measure-name="' . esc_attr($derived_def->definition_name) . '" ';
                                    echo 'data-kpi-type="derived" ';
                                    echo 'data-primary-kpi-key="' . esc_attr($measure->measure_key) . '" ';
                                    echo 'data-derived-id="' . esc_attr($derived_def->derived_definition_id) . '">';
                                    echo ' ' . esc_html($derived_def->definition_name) . ' <em style=\"color:#555; font-size:0.9em;\"> (Derived from ' . esc_html($measure->measure_name) . ')</em>';
                                    echo '</label>';
                                }
                                echo '</div>';
                            }
                        }
                    } else {
                        echo '<p>' . esc_html__('No active KPI measures defined yet. Please define them under KPI Definitions.', 'operations-organizer') . '</p>';
                    }
                    echo '<hr style="margin: 10px 0;">';
                    echo '<div style="margin-top:10px;">';
                    echo '<strong>' . esc_html__('Advanced/Debug Columns:', 'operations-organizer') . '</strong>';
                    echo '<label style="display: block; margin-top: 5px;">';
                    echo '<input type="checkbox" name="selected_kpi_columns[]" value="raw_kpi_data_json" data-measure-name="' . esc_attr__('All KPI Data (JSON)', 'operations-organizer') . '" data-kpi-type="raw_json">';
                    echo ' ' . esc_html__('All KPI Data (JSON)', 'operations-organizer');
                    echo '</label>';
                    ?></div><button type="button" id="apply_selected_kpi_columns" class="button button-primary"><?php esc_html_e('Apply Columns', 'operations-organizer'); ?></button><button type="button" class="button oo-modal-cancel"><?php esc_html_e('Cancel', 'operations-organizer'); ?></button></div></div>');
    $('body').append(kpiColumnModal); // Append modal to body once

    // Open KPI Column Selector Modal
    $('#content_open_kpi_selector_modal').on('click', function() {
        var currentlySelectedValues = [];
        if (window.contentSelectedKpiObjects && window.contentSelectedKpiObjects.length > 0) {
            window.contentSelectedKpiObjects.forEach(function(kpiObj) {
                if (kpiObj.type === 'primary') {
                    currentlySelectedValues.push(kpiObj.key);
                } else if (kpiObj.type === 'derived') {
                    currentlySelectedValues.push(kpiObj.original_value_string);
                } else if (kpiObj.type === 'raw_json') {
                    currentlySelectedValues.push(kpiObj.key); 
                }
            });
        }
        $('#kpi-measures-checkbox-list input[type="checkbox"]').each(function() {
            $(this).prop('checked', currentlySelectedValues.includes($(this).val()));
        });
        $('#kpi-column-selector-modal').css('display', 'block');
    });

    function updateSelectedKpiCount() {
        var count = (window.contentSelectedKpiObjects && window.contentSelectedKpiObjects.length) ? window.contentSelectedKpiObjects.length : 0;
        $('#content_selected_kpi_count').text(count + ' column(s) selected.'); // Simpler message
    }
    updateSelectedKpiCount(); // Initial count

    // Apply Selected KPI Columns
    $('#apply_selected_kpi_columns').on('click', function() {
        var selectedObjects = [];
        $('#kpi-measures-checkbox-list input[type="checkbox"]:checked').each(function() {
            var $checkbox = $(this);
            var kpiType = $checkbox.data('kpi-type');
            var kpiValue = $checkbox.val();
            var kpiName = $checkbox.data('measure-name');

            if (kpiType === 'primary') {
                selectedObjects.push({
                    type: 'primary',
                    key: kpiValue, 
                    name: kpiName
                });
            } else if (kpiType === 'derived') {
                selectedObjects.push({
                    type: 'derived',
                    id: $checkbox.data('derived-id'), 
                    name: kpiName,
                    primary_key: $checkbox.data('primary-kpi-key'),
                    original_value_string: kpiValue 
                });
            } else if (kpiType === 'raw_json') {
                selectedObjects.push({
                    type: 'raw_json',
                    key: kpiValue, // e.g. 'raw_kpi_data_json'
                    name: kpiName  // e.g. 'All KPI Data (JSON)'
                });
            }
        });
        window.contentSelectedKpiObjects = selectedObjects; 
        
        updateSelectedKpiCount();
        $('#kpi-column-selector-modal').css('display', 'none');
        
        reinitializeContentDashboardTable();
    });

    // Close modal (specific for this new modal)
    kpiColumnModal.find('.oo-modal-close, .oo-modal-cancel').on('click', function() {
        kpiColumnModal.css('display', 'none');
    });
    
    $(window).on('click', function(event) {
        if ($(event.target)[0] == kpiColumnModal[0]) { // Check if the click is directly on the modal backdrop
            kpiColumnModal.css('display', 'none');
        }
    });
   
    // --- End KPI Column Selector Modal --- 

});
</script> 