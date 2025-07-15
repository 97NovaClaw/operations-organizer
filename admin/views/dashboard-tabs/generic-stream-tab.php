<?php
// /admin/views/dashboard-tabs/generic-stream-tab.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get current stream data from globals
$current_stream = isset($GLOBALS['current_stream']) ? $GLOBALS['current_stream'] : null;
$current_stream_tab_slug = isset($GLOBALS['current_stream_tab_slug']) ? $GLOBALS['current_stream_tab_slug'] : '';

if (!$current_stream) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Error: Stream data not found.', 'operations-organizer') . '</p></div>';
    return;
}

// Get phases specific to this stream
$stream_phases = array();
if (isset($phases) && is_array($phases)) {
    foreach ($phases as $phase) {
        if ($phase->stream_id == $current_stream->stream_id) {
            $stream_phases[] = $phase;
        }
    }
}
?>
<div class="oo-tab-content">
    <h2><?php echo esc_html(sprintf(__('%s Stream', 'operations-organizer'), $current_stream->stream_name)); ?></h2>
    
    <?php if (!empty($current_stream->stream_description)): ?>
        <p class="stream-description"><?php echo esc_html($current_stream->stream_description); ?></p>
    <?php endif; ?>
    
    <div class="notice notice-info" style="padding: 10px; margin: 10px 0 20px;">
        <p>
            <strong><?php esc_html_e('Enhanced Stream Experience:', 'operations-organizer'); ?></strong> 
            <?php esc_html_e('For advanced features and detailed management, visit the dedicated ', 'operations-organizer'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=oo_stream_' . sanitize_key($current_stream->stream_name))); ?>">
                <?php echo esc_html(sprintf(__('%s Stream Page', 'operations-organizer'), $current_stream->stream_name)); ?>
            </a>.
        </p>
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
                <?php if (!empty($stream_phases)): foreach ($stream_phases as $phase): ?>
                    <option value="<?php echo esc_attr($phase->phase_id); ?>">
                        <?php echo esc_html($phase->phase_name); ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="filter_stream_id" style="display:none;"><?php esc_html_e('Stream:', 'operations-organizer');?></label>
            <!-- Hidden field to filter by current stream -->
            <input type="hidden" id="filter_stream_id" name="filter_stream_id" value="<?php echo esc_attr($current_stream->stream_id); ?>">
        </div>
        <div class="filter-actions">
            <button type="button" id="apply_filters"><?php esc_html_e('Apply Filters', 'operations-organizer');?></button>
            <button type="button" id="clear_filters"><?php esc_html_e('Clear', 'operations-organizer');?></button>
        </div>
    </div>

    <!-- Stream Statistics -->
    <div class="oo-dashboard-section">
        <h3><?php echo esc_html(sprintf(__('%s Stream Statistics', 'operations-organizer'), $current_stream->stream_name)); ?></h3>
        <div class="oo-stats-grid">
            <div class="oo-stat-card">
                <h4><?php esc_html_e('Active Jobs', 'operations-organizer'); ?></h4>
                <div class="stat-value" id="stat-active-jobs">--</div>
                <div class="stat-label"><?php esc_html_e('Jobs in this stream', 'operations-organizer'); ?></div>
            </div>
            <div class="oo-stat-card">
                <h4><?php esc_html_e('Total Phases', 'operations-organizer'); ?></h4>
                <div class="stat-value"><?php echo count($stream_phases); ?></div>
                <div class="stat-label"><?php esc_html_e('Phases defined', 'operations-organizer'); ?></div>
            </div>
            <div class="oo-stat-card">
                <h4><?php esc_html_e('Recent Activity', 'operations-organizer'); ?></h4>
                <div class="stat-value" id="stat-recent-logs">--</div>
                <div class="stat-label"><?php esc_html_e('Logs today', 'operations-organizer'); ?></div>
            </div>
        </div>
    </div>

    <!-- Job Logs Table -->
    <div class="oo-dashboard-section">
        <h3><?php echo esc_html(sprintf(__('%s Stream Job Logs', 'operations-organizer'), $current_stream->stream_name)); ?></h3>
        <div class="table-responsive">
            <table id="oo-job-logs-table" class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date/Time', 'operations-organizer'); ?></th>
                        <th><?php esc_html_e('Employee', 'operations-organizer'); ?></th>
                        <th><?php esc_html_e('Job #', 'operations-organizer'); ?></th>
                        <th><?php esc_html_e('Phase', 'operations-organizer'); ?></th>
                        <th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                        <th><?php esc_html_e('Duration', 'operations-organizer'); ?></th>
                        <th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>
        <div id="oo-job-logs-pagination"></div>
    </div>

</div>

<style>
.stream-description {
    font-style: italic;
    color: #666;
    margin-bottom: 15px;
}

.oo-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.oo-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.oo-stat-card h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #23282d;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #0073aa;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 12px;
    color: #666;
}

#oo-dashboard-filters {
    background: #f1f1f1;
    padding: 20px;
    border-radius: 4px;
    margin-bottom: 30px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.filter-item {
    display: flex;
    flex-direction: column;
}

.filter-item label {
    font-weight: 600;
    margin-bottom: 5px;
}

.filter-actions {
    display: flex;
    gap: 10px;
    align-items: end;
}

.filter-actions button {
    padding: 8px 16px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

#apply_filters {
    background: #0073aa;
    color: white;
}

#clear_filters {
    background: #666;
    color: white;
}

.table-responsive {
    overflow-x: auto;
}

#oo-job-logs-table {
    margin-top: 20px;
}

#oo-job-logs-table th,
#oo-job-logs-table td {
    padding: 12px;
    text-align: left;
}

#oo-job-logs-table tbody tr:hover {
    background-color: #f9f9f9;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Store current stream info for JavaScript
    window.ooCurrentStream = {
        id: <?php echo json_encode($current_stream->stream_id); ?>,
        name: <?php echo json_encode($current_stream->stream_name); ?>,
        tabSlug: <?php echo json_encode($current_stream_tab_slug); ?>
    };
    
    // Initialize datepickers
    if ($.fn.datepicker) {
        $('.oo-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    }
    
    // Load initial statistics
    loadStreamStatistics();
    
    // Load initial job logs
    loadJobLogs();
    
    // Filter handlers
    $('#apply_filters').on('click', function() {
        loadJobLogs();
        loadStreamStatistics();
    });
    
    $('#clear_filters').on('click', function() {
        $('#oo-dashboard-filters input, #oo-dashboard-filters select').not('#filter_stream_id').val('');
        loadJobLogs();
        loadStreamStatistics();
    });
    
    function loadStreamStatistics() {
        // Get active jobs count for this stream
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'oo_get_stream_statistics',
                stream_id: window.ooCurrentStream.id,
                nonce: '<?php echo wp_create_nonce('oo_get_stream_stats_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#stat-active-jobs').text(response.data.active_jobs || 0);
                    $('#stat-recent-logs').text(response.data.recent_logs || 0);
                }
            }
        });
    }
    
    function loadJobLogs() {
        // Show loading state
        $('#oo-job-logs-table tbody').html('<tr><td colspan="7"><?php esc_html_e('Loading...', 'operations-organizer'); ?></td></tr>');
        
        // Collect filter values
        var filters = {
            action: 'oo_get_dashboard_data',
            stream_id: $('#filter_stream_id').val(),
            date_from: $('#filter_date_from').val(),
            date_to: $('#filter_date_to').val(),
            employee_id: $('#filter_employee_id').val(),
            job_number: $('#filter_job_number').val(),
            phase_id: $('#filter_phase_id').val(),
            nonce: '<?php echo wp_create_nonce('oo_get_dashboard_data_nonce'); ?>'
        };
        
        // Load filtered job logs
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: filters,
            success: function(response) {
                if (response.success && response.data.logs) {
                    var tbody = '';
                    $.each(response.data.logs, function(index, log) {
                        tbody += '<tr>';
                        tbody += '<td>' + log.formatted_date + '</td>';
                        tbody += '<td>' + log.employee_name + '</td>';
                        tbody += '<td>' + log.job_number + '</td>';
                        tbody += '<td>' + log.phase_name + '</td>';
                        tbody += '<td>' + log.status + '</td>';
                        tbody += '<td>' + (log.duration || '--') + '</td>';
                        tbody += '<td><button class="button-small view-log" data-log-id="' + log.log_id + '"><?php esc_html_e('View', 'operations-organizer'); ?></button></td>';
                        tbody += '</tr>';
                    });
                    $('#oo-job-logs-table tbody').html(tbody || '<tr><td colspan="7"><?php esc_html_e('No logs found.', 'operations-organizer'); ?></td></tr>');
                } else {
                    $('#oo-job-logs-table tbody').html('<tr><td colspan="7"><?php esc_html_e('No logs found.', 'operations-organizer'); ?></td></tr>');
                }
            },
            error: function() {
                $('#oo-job-logs-table tbody').html('<tr><td colspan="7"><?php esc_html_e('Error loading data.', 'operations-organizer'); ?></td></tr>');
            }
        });
    }
});
</script> 