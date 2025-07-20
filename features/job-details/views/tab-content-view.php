<?php
/**
 * Job Details Stream Tab Content
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get phases for this stream
$phases = OO_DB::get_phases(array(
    'stream_id' => $stream_id,
    'is_active' => 1,
    'orderby' => 'order_in_stream',
    'order' => 'ASC'
));

// Find current phase ID based on status_in_stream
$current_phase_id = null;
foreach ($phases as $phase) {
    if ($phase->phase_name === $job_stream->status_in_stream) {
        $current_phase_id = $phase->phase_id;
        break;
    }
}

// Get employees for the filter
$employees = OO_Employee::get_employees();
?>

<div class="oo-stream-tab-content">
    <!-- Stream Info Bar -->
    <div class="oo-stream-info-bar">
        <div class="oo-stream-info-item">
            <label><?php esc_html_e('Current Phase:', 'operations-organizer'); ?></label>
            <select class="oo-phase-selector" data-job-stream-id="<?php echo esc_attr($job_stream->job_stream_id); ?>">
                <option value=""><?php esc_html_e('Select phase...', 'operations-organizer'); ?></option>
                <?php foreach ($phases as $phase): ?>
                    <option value="<?php echo esc_attr($phase->phase_id); ?>" 
                            <?php selected($current_phase_id, $phase->phase_id); ?>>
                        <?php echo esc_html($phase->phase_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="oo-stream-info-item">
            <label><?php esc_html_e('Status:', 'operations-organizer'); ?></label>
            <span class="oo-status-badge oo-status-<?php echo esc_attr($job_stream->status_in_stream); ?>">
                <?php echo esc_html(ucfirst($job_stream->status_in_stream)); ?>
            </span>
        </div>
        
        <?php if ($job_stream->assigned_manager_id): 
            $manager = get_userdata($job_stream->assigned_manager_id);
        ?>
        <div class="oo-stream-info-item">
            <label><?php esc_html_e('Manager:', 'operations-organizer'); ?></label>
            <span><?php echo $manager ? esc_html($manager->display_name) : esc_html__('Unknown', 'operations-organizer'); ?></span>
        </div>
        <?php endif; ?>
        
        <div class="oo-stream-info-item">
            <button type="button" class="button button-secondary oo-view-activity-log" 
                    data-job-stream-id="<?php echo esc_attr($job_stream->job_stream_id); ?>">
                <span class="dashicons dashicons-backup"></span>
                <?php esc_html_e('View Activity Log', 'operations-organizer'); ?>
            </button>
        </div>
    </div>
    
    <?php if ($job_stream->start_date_stream || $job_stream->due_date_stream): ?>
    <div class="oo-stream-dates">
        <?php if ($job_stream->start_date_stream): ?>
        <div class="oo-date-item">
            <label><?php esc_html_e('Stream Start:', 'operations-organizer'); ?></label>
            <span><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($job_stream->start_date_stream))); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($job_stream->due_date_stream): ?>
        <div class="oo-date-item">
            <label><?php esc_html_e('Stream Due:', 'operations-organizer'); ?></label>
            <span class="<?php echo strtotime($job_stream->due_date_stream) < time() ? 'overdue' : ''; ?>">
                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($job_stream->due_date_stream))); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Job Logs Section -->
    <div class="oo-job-logs-section">
        <h3><?php esc_html_e('Detailed Job Logs', 'operations-organizer'); ?></h3>
        
        <!-- Filters -->
        <div class="oo-logs-filters">
            <div class="oo-filter-row">
                <div class="oo-filter-item">
                    <label for="filter-employee-<?php echo esc_attr($stream_id); ?>">
                        <?php esc_html_e('Employee:', 'operations-organizer'); ?>
                    </label>
                    <select id="filter-employee-<?php echo esc_attr($stream_id); ?>" class="oo-log-filter" data-filter="employee">
                        <option value=""><?php esc_html_e('All Employees', 'operations-organizer'); ?></option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo esc_attr($employee->employee_id); ?>">
                                <?php echo esc_html($employee->first_name . ' ' . $employee->last_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="oo-filter-item">
                    <label for="filter-phase-<?php echo esc_attr($stream_id); ?>">
                        <?php esc_html_e('Phase:', 'operations-organizer'); ?>
                    </label>
                    <select id="filter-phase-<?php echo esc_attr($stream_id); ?>" class="oo-log-filter" data-filter="phase">
                        <option value=""><?php esc_html_e('All Phases', 'operations-organizer'); ?></option>
                        <?php foreach ($phases as $phase): ?>
                            <option value="<?php echo esc_attr($phase->phase_id); ?>">
                                <?php echo esc_html($phase->phase_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="oo-filter-item">
                    <label for="filter-date-from-<?php echo esc_attr($stream_id); ?>">
                        <?php esc_html_e('From:', 'operations-organizer'); ?>
                    </label>
                    <input type="date" id="filter-date-from-<?php echo esc_attr($stream_id); ?>" 
                           class="oo-log-filter" data-filter="date_from">
                </div>
                
                <div class="oo-filter-item">
                    <label for="filter-date-to-<?php echo esc_attr($stream_id); ?>">
                        <?php esc_html_e('To:', 'operations-organizer'); ?>
                    </label>
                    <input type="date" id="filter-date-to-<?php echo esc_attr($stream_id); ?>" 
                           class="oo-log-filter" data-filter="date_to">
                </div>
                
                <div class="oo-filter-item">
                    <button type="button" class="button button-primary oo-apply-log-filters">
                        <?php esc_html_e('Apply Filters', 'operations-organizer'); ?>
                    </button>
                    <button type="button" class="button oo-reset-log-filters">
                        <?php esc_html_e('Reset', 'operations-organizer'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Logs Table -->
        <div class="oo-logs-table-wrapper">
            <table class="wp-list-table widefat fixed striped oo-logs-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Employee', 'operations-organizer'); ?></th>
                        <th><?php esc_html_e('Phase', 'operations-organizer'); ?></th>
                        <th><?php esc_html_e('Start Time', 'operations-organizer'); ?></th>
                        <th><?php esc_html_e('End Time', 'operations-organizer'); ?></th>
                        <th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                        <th><?php esc_html_e('Notes', 'operations-organizer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="oo-loading-message">
                            <?php esc_html_e('Loading logs...', 'operations-organizer'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Phase Change Note Modal -->
<div id="job-details-phase-change-modal" class="oo-modal" style="display:none;">
    <div class="oo-modal-content oo-modal-small">
        <span class="oo-close-modal">&times;</span>
        <h2><?php esc_html_e('Phase Change Note', 'operations-organizer'); ?></h2>
        <form id="job-details-phase-change-form">
            <p class="phase-change-info">
                <?php esc_html_e('Changing phase to:', 'operations-organizer'); ?> 
                <strong id="phase-change-target"></strong>
            </p>
            <div class="form-field">
                <label for="phase-change-note"><?php esc_html_e('Note (required):', 'operations-organizer'); ?></label>
                <textarea id="phase-change-note" rows="4" required placeholder="<?php esc_attr_e('Please describe the reason for this phase change...', 'operations-organizer'); ?>"></textarea>
            </div>
            <div class="modal-buttons">
                <button type="submit" class="button button-primary"><?php esc_html_e('Confirm Change', 'operations-organizer'); ?></button>
                <button type="button" class="button cancel-phase-change"><?php esc_html_e('Cancel', 'operations-organizer'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Activity Log Modal (shared across all tabs) -->
<div id="job-details-activity-log-modal" class="oo-modal" style="display:none;">
    <div class="oo-modal-content">
        <span class="oo-close-modal">&times;</span>
        <h2><?php esc_html_e('Activity Log', 'operations-organizer'); ?></h2>
        <div class="activity-log-container">
            <div class="activity-log-loading">
                <span class="spinner is-active"></span>
                <p><?php esc_html_e('Loading activity log...', 'operations-organizer'); ?></p>
            </div>
            <div class="activity-log-content" style="display:none;"></div>
        </div>
    </div>
</div> 