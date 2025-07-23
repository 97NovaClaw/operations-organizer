<?php
/**
 * Master Activity Log Admin Page
 * 
 * Displays a comprehensive view of all system activities
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get filter parameters
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$stream_id = isset($_GET['stream_id']) ? intval($_GET['stream_id']) : 0;

// Get activity types for filter
$activity_types = OO_Master_Log_Database::get_activity_types();
?>

<div class="wrap">
    <h1><?php _e('Activity Log', 'operations-organizer'); ?></h1>
    
    <div class="oo-master-log-container">
        
        <!-- Filters -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <?php if ($job_id): 
                    $job = OO_DB::get_job($job_id);
                    if ($job):
                ?>
                    <h2><?php printf(__('Activities for Job: %s - %s', 'operations-organizer'), esc_html($job->job_number), esc_html($job->client_name)); ?></h2>
                <?php 
                    endif;
                endif; ?>
                
                <label for="filter-activity-type"><?php _e('Activity Type:', 'operations-organizer'); ?></label>
                <select id="filter-activity-type" class="activity-filter">
                    <option value=""><?php _e('All Activities', 'operations-organizer'); ?></option>
                    <?php foreach ($activity_types as $type): ?>
                        <option value="<?php echo esc_attr($type); ?>">
                            <?php echo esc_html(str_replace('_', ' ', $type)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <label for="filter-date-from"><?php _e('From:', 'operations-organizer'); ?></label>
                <input type="date" id="filter-date-from" class="activity-filter" />
                
                <label for="filter-date-to"><?php _e('To:', 'operations-organizer'); ?></label>
                <input type="date" id="filter-date-to" class="activity-filter" />
                
                <button class="button" id="apply-filters"><?php _e('Apply Filters', 'operations-organizer'); ?></button>
                <button class="button" id="clear-filters"><?php _e('Clear', 'operations-organizer'); ?></button>
                
                <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=oo_export_master_logs'), 'oo_master_log_nonce', 'nonce'); ?>" 
                   class="button" id="export-logs">
                    <?php _e('Export to CSV', 'operations-organizer'); ?>
                </a>
            </div>
        </div>
        
        <!-- Activity Log Table -->
        <table id="master-activity-log-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Date/Time', 'operations-organizer'); ?></th>
                    <th><?php _e('Job', 'operations-organizer'); ?></th>
                    <th><?php _e('Activity', 'operations-organizer'); ?></th>
                    <th><?php _e('User', 'operations-organizer'); ?></th>
                    <th><?php _e('Description', 'operations-organizer'); ?></th>
                    <th><?php _e('User Note', 'operations-organizer'); ?></th>
                    <th><?php _e('Stream', 'operations-organizer'); ?></th>
                    <th><?php _e('Actions', 'operations-organizer'); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- DataTables will populate this -->
            </tbody>
        </table>
    </div>
    
    <!-- Activity Details Modal -->
    <div id="activity-details-modal" class="oo-modal" style="display: none;">
        <div class="oo-modal-content">
            <span class="oo-modal-close">&times;</span>
            <h2><?php _e('Activity Details', 'operations-organizer'); ?></h2>
            <div id="activity-details-content">
                <!-- Details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
/* Activity Log Styles */
.oo-master-log-container {
    margin-top: 20px;
}

.activity-filter {
    margin-right: 10px;
}

.activity-type {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

/* Activity type colors */
.activity-blue { background-color: #e3f2fd; color: #1976d2; }
.activity-green { background-color: #e8f5e9; color: #388e3c; }
.activity-yellow { background-color: #fff8e1; color: #f57c00; }
.activity-red { background-color: #ffebee; color: #d32f2f; }
.activity-purple { background-color: #f3e5f5; color: #7b1fa2; }
.activity-orange { background-color: #fff3e0; color: #f57c00; }
.activity-teal { background-color: #e0f2f1; color: #00796b; }
.activity-gray { background-color: #f5f5f5; color: #616161; }

/* Modal styles */
.oo-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.oo-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    border-radius: 4px;
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
    color: #000;
}

#activity-details-content {
    margin-top: 20px;
}

.detail-row {
    margin-bottom: 10px;
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}

.detail-label {
    font-weight: 600;
    display: inline-block;
    width: 120px;
}

.detail-value {
    display: inline-block;
}

.metadata-json {
    background: #f5f5f5;
    padding: 10px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 12px;
    white-space: pre-wrap;
    word-wrap: break-word;
}

/* User note styles */
.no-note {
    color: #999;
    font-style: italic;
}

.truncated-note {
    cursor: help;
    border-bottom: 1px dotted #666;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize DataTable
    var table = $('#master-activity-log-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: ajaxurl,
            type: 'POST',
            data: function(d) {
                d.action = 'oo_get_master_logs';
                d.nonce = '<?php echo wp_create_nonce('oo_master_log_nonce'); ?>';
                d.job_id = <?php echo $job_id; ?>;
                d.stream_id = <?php echo $stream_id; ?>;
                d.activity_type = $('#filter-activity-type').val();
                d.date_from = $('#filter-date-from').val();
                d.date_to = $('#filter-date-to').val();
            }
        },
        columns: [
            { data: 'created_at' },
            { data: 'job' },
            { data: 'activity' },
            { data: 'user' },
            { data: 'description' },
            { data: 'user_note' },
            { data: 'stream' },
            { data: 'actions' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            search: "<?php _e('Search:', 'operations-organizer'); ?>",
            lengthMenu: "<?php _e('Show _MENU_ entries', 'operations-organizer'); ?>",
            info: "<?php _e('Showing _START_ to _END_ of _TOTAL_ entries', 'operations-organizer'); ?>",
            paginate: {
                first: "<?php _e('First', 'operations-organizer'); ?>",
                last: "<?php _e('Last', 'operations-organizer'); ?>",
                next: "<?php _e('Next', 'operations-organizer'); ?>",
                previous: "<?php _e('Previous', 'operations-organizer'); ?>"
            }
        }
    });
    
    // Apply filters
    $('#apply-filters').on('click', function() {
        table.ajax.reload();
    });
    
    // Clear filters
    $('#clear-filters').on('click', function() {
        $('#filter-activity-type').val('');
        $('#filter-date-from').val('');
        $('#filter-date-to').val('');
        table.ajax.reload();
    });
    
    // Update export link with filters
    $('#export-logs').on('click', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        var params = {
            job_id: <?php echo $job_id; ?>,
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val()
        };
        
        // Build query string
        var queryString = $.param(params);
        window.location.href = url + '&' + queryString;
    });
    
    // View details
    $(document).on('click', '.view-details', function() {
        var logId = $(this).data('log-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'oo_get_activity_details',
                nonce: '<?php echo wp_create_nonce('oo_master_log_nonce'); ?>',
                log_id: logId
            },
            success: function(response) {
                if (response.success) {
                    var details = response.data;
                    var html = '';
                    
                    html += '<div class="detail-row"><span class="detail-label">Date/Time:</span> <span class="detail-value">' + details.created_at + '</span></div>';
                    html += '<div class="detail-row"><span class="detail-label">Activity Type:</span> <span class="detail-value">' + details.activity_type + '</span></div>';
                    html += '<div class="detail-row"><span class="detail-label">User:</span> <span class="detail-value">' + details.user.name + ' (' + details.user.email + ')</span></div>';
                    html += '<div class="detail-row"><span class="detail-label">Job ID:</span> <span class="detail-value">' + details.job_id + '</span></div>';
                    
                    if (details.stream_id) {
                        html += '<div class="detail-row"><span class="detail-label">Stream ID:</span> <span class="detail-value">' + details.stream_id + '</span></div>';
                    }
                    
                    if (details.field_name) {
                        html += '<div class="detail-row"><span class="detail-label">Field:</span> <span class="detail-value">' + details.field_name + '</span></div>';
                        
                        if (details.old_value) {
                            html += '<div class="detail-row"><span class="detail-label">Old Value:</span> <span class="detail-value">' + details.old_value + '</span></div>';
                        }
                        
                        if (details.new_value) {
                            html += '<div class="detail-row"><span class="detail-label">New Value:</span> <span class="detail-value">' + details.new_value + '</span></div>';
                        }
                    }
                    
                    if (details.user_notes) {
                        html += '<div class="detail-row"><span class="detail-label">Notes:</span> <span class="detail-value">' + details.user_notes + '</span></div>';
                    }
                    
                    if (details.metadata && Object.keys(details.metadata).length > 0) {
                        html += '<div class="detail-row"><span class="detail-label">Metadata:</span></div>';
                        html += '<div class="metadata-json">' + JSON.stringify(details.metadata, null, 2) + '</div>';
                    }
                    
                    $('#activity-details-content').html(html);
                    $('#activity-details-modal').show();
                }
            }
        });
    });
    
    // Close modal
    $('.oo-modal-close, .oo-modal').on('click', function(e) {
        if (e.target === this) {
            $('#activity-details-modal').hide();
        }
    });
});
</script> 