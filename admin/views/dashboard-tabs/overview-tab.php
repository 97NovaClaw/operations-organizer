<?php
// /admin/views/dashboard-tabs/overview-tab.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
?>
<div class="oo-tab-content">
    <h2><?php esc_html_e('Overview', 'operations-organizer'); ?></h2>
    
    <div class="notice notice-info" style="padding: 10px; margin: 10px 0 20px;">
        <p><strong><?php esc_html_e('Stream-specific data:', 'operations-organizer'); ?></strong> <?php esc_html_e('For a better experience, please use the stream-specific tabs (Soft Content, Electronics, Art, Content) to view job logs filtered by stream type.', 'operations-organizer'); ?></p>
    </div>
    
    <div id="oo-dashboard-filters">
        <div class="filter-item">
            <label for="filter_date_from"><?php esc_html_e('Date From:', 'operations-organizer');?></label>
            <input type="text" id="filter_date_from" name="filter_date_from" class="oo-datepicker" placeholder="YYYY-MM-DD">
        </div>
        <div class="filter-item">
            <label for="filter_date_to"><?php esc_html_e('Date To:', 'operations-organizer');?></label>
            <input type="text" id="filter_date_to" name="filter_date_to" class="oo-datepicker" placeholder="YYYY-MM-DD">
        </div>
        <div class="filter-item">
            <label for="filter_employee_id"><?php esc_html_e('Employee:', 'operations-organizer');?></label>
            <select id="filter_employee_id" name="filter_employee_id">
                <option value=""><?php esc_html_e('All Employees', 'operations-organizer');?></option>
                <?php if (!empty($employees)): foreach ($employees as $employee): ?>
                    <option value="<?php echo esc_attr($employee->employee_id); ?>">
                        <?php echo esc_html($employee->first_name . ' ' . $employee->last_name . ' (' . $employee->employee_number . ')'); ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="filter_job_number"><?php esc_html_e('Job Number:', 'operations-organizer');?></label>
            <input type="text" id="filter_job_number" name="filter_job_number" placeholder="<?php esc_attr_e('Enter Job No.', 'operations-organizer'); ?>">
        </div>
        <div class="filter-item">
            <label for="filter_phase_id"><?php esc_html_e('Phase:', 'operations-organizer');?></label>
            <select id="filter_phase_id" name="filter_phase_id">
                <option value=""><?php esc_html_e('All Phases', 'operations-organizer');?></option>
                 <?php if (!empty($phases)): foreach ($phases as $phase): ?>
                    <option value="<?php echo esc_attr($phase->phase_id); ?>">
                        <?php echo esc_html($phase->phase_name); ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="filter_status"><?php esc_html_e('Status:', 'operations-organizer');?></label>
            <select id="filter_status" name="filter_status">
                <option value=""><?php esc_html_e('All Statuses', 'operations-organizer');?></option>
                <option value="started"><?php esc_html_e('Running', 'operations-organizer');?></option>
                <option value="completed"><?php esc_html_e('Completed', 'operations-organizer');?></option>
            </select>
        </div>
        <div class="filter-item">
            <label for="filter_stream_id"><?php esc_html_e('Stream Type:', 'operations-organizer');?></label>
            <select id="filter_stream_id" name="filter_stream_id">
                <option value=""><?php esc_html_e('All Stream Types', 'operations-organizer');?></option>
                <?php foreach ($GLOBALS['streams'] as $st): ?>
                    <option value="<?php echo esc_attr($st->stream_id); ?>"><?php echo esc_html($st->stream_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-item">
            <button id="apply_filters_button" class="button button-primary"><?php esc_html_e('Apply Filters', 'operations-organizer');?></button>
            <button id="clear_filters_button" class="button"><?php esc_html_e('Clear Filters', 'operations-organizer');?></button>
        </div>
    </div>
    
    <div id="oo-export-options" style="margin-bottom: 20px;">
        <button id="export_csv_button" class="button"><?php esc_html_e('Export to CSV', 'operations-organizer');?></button>
    </div>

    <table id="oo-dashboard-table" class="display wp-list-table widefat fixed striped" style="width:100%">
        <thead>
            <tr>
                <th><?php esc_html_e('Employee Name', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Job No.', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Stream Type', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Phase', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Start Time', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('End Time', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Duration', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Notes', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('KPI Data', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- Data will be loaded by DataTables via AJAX -->
        </tbody>
        <tfoot>
            <tr>
                <th><?php esc_html_e('Employee Name', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Job No.', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Stream Type', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Phase', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Start Time', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('End Time', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Duration', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Notes', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('KPI Data', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Datepicker init is in admin-scripts.js

    var dashboardTable = $('#oo-dashboard-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: oo_data.ajax_url,
            type: 'POST',
            data: function(d) { 
                d.action = 'oo_get_dashboard_data';
                d.nonce = '<?php echo wp_create_nonce("oo_dashboard_nonce"); ?>';
                d.filter_employee_id = $('#filter_employee_id').val();
                d.filter_job_number = $('#filter_job_number').val();
                d.filter_phase_id = $('#filter_phase_id').val();
                d.filter_date_from = $('#filter_date_from').val();
                d.filter_date_to = $('#filter_date_to').val();
                d.filter_status = $('#filter_status').val();
                d.filter_stream_id = $('#filter_stream_id').val();
            },
            // Handle WordPress's wp_send_json_success response format
            dataSrc: function(json) {
                return json.data;
            }
        },
        columns: [
            { data: 'employee_name' },
            { data: 'job_number' },
            { data: 'stream_name' },
            { data: 'phase_name' },
            { data: 'start_time' },
            { data: 'end_time' },
            { data: 'duration', orderable: false }, 
            { data: 'status' },
            { data: 'notes', orderable: false, render: function(data, type, row) {
                var escData = $('<div>').text(data).html(); 
                if (type === 'display' && escData && escData.length > 50) {
                    return '<span title="'+escData.replace(/"/g, '&quot;')+'">' + escData.substr(0, 50) + '...</span>';
                }
                return escData;
              } 
            },
            { data: 'kpi_data' },
            {
                data: null, // Using null for custom content
                orderable: false,
                searchable: false,
                className: 'oo-actions-column',
                render: function(data, type, row) {
                    let buttons = '<button class="button button-secondary oo-edit-log-button" data-log-id="' + row.log_id + '"><?php echo esc_js(__("Edit", "operations-organizer")); ?></button>';
                    buttons += ' <button class="button button-link-delete oo-delete-log-button" data-log-id="' + row.log_id + '" style="color:#b32d2e;margin-left:5px;"><?php echo esc_js(__("Delete", "operations-organizer")); ?></button>';
                    return buttons;
              } 
            }
        ],
        order: [[4, 'desc']], // Default order by start_time descending
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        responsive: true,
        language: {
            search: "<?php esc_attr_e('Search table:', 'operations-organizer'); ?>",
            lengthMenu: "<?php esc_attr_e('Show _MENU_ entries', 'operations-organizer'); ?>",
            info: "<?php esc_attr_e('Showing _START_ to _END_ of _TOTAL_ entries', 'operations-organizer'); ?>",
            paginate: {
                first: "<?php esc_attr_e('First', 'operations-organizer'); ?>",
                last: "<?php esc_attr_e('Last', 'operations-organizer'); ?>",
                next: "<?php esc_attr_e('Next', 'operations-organizer'); ?>",
                previous: "<?php esc_attr_e('Previous', 'operations-organizer'); ?>"
            }
        }
    });

    // Apply filters button
    $('#apply_filters_button').on('click', function() {
        dashboardTable.ajax.reload(); 
    });

    // Clear filters button
    $('#clear_filters_button').on('click', function() {
        $('#oo-dashboard-filters input[type="text"]').val('');
        $('#oo-dashboard-filters input[type="date"]').val('');
        $('#oo-dashboard-filters select').val('');
        dashboardTable.search('').columns().search('').draw();
        // dashboardTable.ajax.reload(); // draw() above should trigger reload with cleared filters.
    });
    
    // Export to CSV
    $('#export_csv_button').on('click', function() {
        var currentAjaxParams = dashboardTable.ajax.params();
        var exportParams = $.extend({}, currentAjaxParams, {
            length: -1, // Fetch all records for export
            action: 'oo_get_dashboard_data', // Ensure action and nonce are correctly set for export
            nonce: '<?php echo wp_create_nonce("oo_dashboard_nonce"); ?>'
        });

        // We need to add custom filters to the exportParams if they are not already part of currentAjaxParams.data
        exportParams.filter_employee_id = $('#filter_employee_id').val();
        exportParams.filter_job_number = $('#filter_job_number').val();
        exportParams.filter_phase_id = $('#filter_phase_id').val();
        exportParams.filter_date_from = $('#filter_date_from').val();
        exportParams.filter_date_to = $('#filter_date_to').val();
        exportParams.filter_status = $('#filter_status').val();
        exportParams.filter_stream_id = $('#filter_stream_id').val();

        // Remove DataTables specific parameters not needed for our AJAX handler or that might conflict
        delete exportParams.draw;
        delete exportParams.columns;
        delete exportParams.order;
        delete exportParams.start;
        delete exportParams.search;

        $.post(oo_data.ajax_url, exportParams, function(response) {
            if (response.data && response.data.length > 0) {
                var csvData = [];
                var headers = [
                    "Employee Name", "Job No.", "Stream Type", "Phase", 
                    "Start Time", "End Time", "Duration", 
                    "Status", "Notes", "KPI Data"
                ];
                csvData.push(headers.join(','));

                response.data.forEach(function(row) {
                    var statusText = $($.parseHTML(row.status)).text().trim(); // Strip HTML from status for CSV
                    var notesText = row.notes ? row.notes.replace(/"/g, '""').replace(/\r\n|\n|\r/g, ' ') : '';
                    var kpiDataText = (typeof row.kpi_data === 'string') ? row.kpi_data : JSON.stringify(row.kpi_data || {});

                    var csvRow = [
                        '"' + row.employee_name.replace(/"/g, '""') + '"',
                        '"' + row.job_number.replace(/"/g, '""') + '"',
                        '"' + row.stream_name.replace(/"/g, '""') + '"',
                        '"' + row.phase_name.replace(/"/g, '""') + '"',
                        '"' + row.start_time.replace(/"/g, '""') + '"',
                        '"' + (row.end_time !== 'N/A' ? row.end_time.replace(/"/g, '""') : 'N/A') + '"',
                        '"' + row.duration.replace(/"/g, '""') + '"',
                        '"' + statusText + '"',
                        '"' + notesText + '"',
                        '"' + kpiDataText + '"'
                    ];
                    csvData.push(csvRow.join(','));
                });

                var csvContent = csvData.join("\n");
                var universalBOM = "\uFEFF"; // Universal BOM for UTF-8 for Excel
                var encodedUri = encodeURI("data:text/csv;charset=utf-8," + universalBOM + csvContent);
                var link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "job_logs_export_" + new Date().toISOString().slice(0,10) + ".csv");
                document.body.appendChild(link); 
                link.click();
                document.body.removeChild(link);
            } else {
                alert('<?php echo esc_js(__("No data to export based on current filters or an error occurred.", "operations-organizer")); ?>');
            }
        }).fail(function() {
            alert('<?php echo esc_js(__("Failed to fetch data for CSV export.", "operations-organizer")); ?>');
        });
    });

    // Handle click on "Edit" button in DataTable
    $('#oo-dashboard-table tbody').on('click', '.oo-edit-log-button', function () {
        console.log('Dashboard JS: Edit button event triggered.'); 
        var logId = $(this).data('log-id');
        console.log('Dashboard JS: Edit button clicked for log ID:', logId);

        if (!logId) {
            console.error('Dashboard JS: Log ID is missing from button data attribute.');
            alert('<?php echo esc_js(__("Error: Log ID is missing.", "operations-organizer")); ?>');
            return;
        }

        // Disable button to prevent multiple clicks
        $(this).prop('disabled', true).text('<?php echo esc_js(__("Loading...", "operations-organizer")); ?>');
        var $clickedButton = $(this);

        $.ajax({
            url: oo_data.ajax_url,
            type: 'POST',
            data: {
                action: 'oo_get_job_log_details',
                nonce: oo_data.nonce_edit_log,
                log_id: logId
            },
            dataType: 'json',
            success: function(response) {
                console.log('Dashboard JS: AJAX success for get_job_log_details. Response:', response);
                if (response.success) {
                    console.log('Dashboard JS: Successfully fetched log details (data to populate modal):', response.data);
                    var log = response.data;
                    $('#oo-edit-log-form').find('#edit_log_id').val(log.log_id);
                    $('#oo-edit-log-form').find('#edit_log_employee_id').val(log.employee_id);
                    $('#oo-edit-log-form').find('#edit_log_job_number').val(log.job_number);
                    $('#oo-edit-log-form').find('#edit_log_phase_id').val(log.phase_id);
                    $('#oo-edit-log-form').find('#edit_log_start_time').val(log.start_time); 
                    $('#oo-edit-log-form').find('#edit_log_end_time').val(log.end_time);     
                    $('#oo-edit-log-form').find('#edit_log_status').val(log.status);
                    $('#oo-edit-log-form').find('#edit_log_notes').val(log.notes);
                    $('#oo-edit-log-form').find('#edit_log_kpi_data').val( (typeof log.kpi_data === 'string') ? log.kpi_data : JSON.stringify(log.kpi_data || {}) );
                    $('#ooEditLogModal').show();
                } else {
                    alert(response.data.message || '<?php echo esc_js(__("Could not fetch log details.", "operations-organizer")); ?>');
                }
                $clickedButton.prop('disabled', false).text('<?php echo esc_js(__("Edit", "operations-organizer")); ?>');
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Dashboard JS: AJAX error for get_job_log_details:', textStatus, errorThrown, jqXHR.responseText);
                alert('<?php echo esc_js(__("AJAX request failed: ", "operations-organizer")); ?>' + textStatus + ' - ' + errorThrown);
                $clickedButton.prop('disabled', false).text('<?php echo esc_js(__("Edit", "operations-organizer")); ?>');
            }
        });
    });

    // Handle Edit Job Log form submission
    $('#oo-edit-log-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        $submitButton.prop('disabled', true).val('<?php echo esc_js(__("Saving...", "operations-organizer")); ?>');
        
        var formData = $form.serialize(); 
        formData += '&action=oo_update_job_log';

        $.post(oo_data.ajax_url, formData)
            .done(function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#ooEditLogModal').hide();
                    if (typeof dashboardTable !== 'undefined') {
                        dashboardTable.ajax.reload(null, false);
                    }
                } else {
                    alert(response.data.message || '<?php echo esc_js(__("Error updating job log.", "operations-organizer")); ?>');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                alert('<?php echo esc_js(__("AJAX request failed: ", "operations-organizer")); ?>' + textStatus + ' - ' + errorThrown);
            })
            .always(function() {
                $submitButton.prop('disabled', false).val('<?php echo esc_js(__("Save Log Changes", "operations-organizer")); ?>');
            });
    });

    // Handle click on "Delete" button in DataTable
    $('#oo-dashboard-table tbody').on('click', '.oo-delete-log-button', function () {
        var logId = $(this).data('log-id');
        console.log('Dashboard JS: Delete button clicked for log ID:', logId);

        if (!logId) {
            alert('<?php echo esc_js(__("Error: Log ID is missing for delete.", "operations-organizer")); ?>');
            return;
        }

        if (!confirm('<?php echo esc_js(__("Are you sure you want to permanently delete this job log entry? This action cannot be undone.", "operations-organizer")); ?>')) {
            return;
        }

        // Disable button to prevent multiple clicks
        $(this).prop('disabled', true).text('<?php echo esc_js(__("Deleting...", "operations-organizer")); ?>');
        var $clickedButton = $(this);

        $.ajax({
            url: oo_data.ajax_url,
            type: 'POST',
            data: {
                action: 'oo_delete_job_log',
                nonce: oo_data.nonce_delete_log,
                log_id: logId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    dashboardTable.ajax.reload(null, false);
                } else {
                    alert(response.data.message || '<?php echo esc_js(__("Could not delete log entry.", "operations-organizer")); ?>');
                    $clickedButton.prop('disabled', false).text('<?php echo esc_js(__("Delete", "operations-organizer")); ?>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert('<?php echo esc_js(__("AJAX request failed: ", "operations-organizer")); ?>' + textStatus + ' - ' + errorThrown);
                $clickedButton.prop('disabled', false).text('<?php echo esc_js(__("Delete", "operations-organizer")); ?>');
            }
        });
    });

    // Close button for modal
    $('.oo-close-button').on('click', function() {
        $('#ooEditLogModal').hide();
    });

    // Close modal when clicking outside of it
    $(window).on('click', function(event) {
        if ($(event.target).is('#ooEditLogModal')) {
            $('#ooEditLogModal').hide();
        }
    });
});
</script> 