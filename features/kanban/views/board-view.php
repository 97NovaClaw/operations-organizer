<?php
/**
 * Kanban Board View Template
 * 
 * Renders the drag-and-drop Kanban board for a specific job stream
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get stream information
$stream = oo_get_stream_by_slug($stream_slug);
if (!$stream) {
    echo '<div class="notice notice-error"><p>' . __('Stream not found.', 'operations-organizer') . '</p></div>';
    return;
}

// Get job information
$job = OO_DB::get_job($job_id);
if (!$job) {
    echo '<div class="notice notice-error"><p>' . __('Job not found.', 'operations-organizer') . '</p></div>';
    return;
}

// Get job stream link
$job_stream = OO_DB::get_job_stream_by_job_and_stream($job_id, $stream->stream_id);
if (!$job_stream) {
    echo '<div class="notice notice-error"><p>' . __('This job is not assigned to this stream.', 'operations-organizer') . '</p></div>';
    return;
}

// Get all phases for this stream
$phases = OO_DB::get_phases(array(
    'stream_id' => $stream->stream_id,
    'is_active' => 1,
    'orderby' => 'order_in_stream',
    'order' => 'ASC'
));

if (empty($phases)) {
    echo '<div class="notice notice-warning"><p>' . __('No active phases found for this stream.', 'operations-organizer') . '</p></div>';
    return;
}

// Get phase statistics
$phase_stats = OO_Kanban_Database::get_phase_statistics($stream->stream_id);
$phase_counts = array();
foreach ($phase_stats as $stat) {
    $phase_counts[$stat->phase_id] = $stat->job_count;
}
?>

<div class="oo-kanban-board-wrapper">
    <div class="oo-kanban-header">
        <h3><?php echo esc_html(sprintf(__('Phase Progress for Job #%s', 'operations-organizer'), $job->job_number)); ?></h3>
        <p class="description">
            <?php echo esc_html(sprintf(__('Stream: %s', 'operations-organizer'), $stream->stream_name)); ?>
            <?php if (!empty($job->client_name)): ?>
                | <?php echo esc_html(sprintf(__('Client: %s', 'operations-organizer'), $job->client_name)); ?>
            <?php endif; ?>
        </p>
    </div>
    
    <div class="oo-kanban-board" id="kanban-board-<?php echo esc_attr($job_stream->job_stream_id); ?>">
        <?php foreach ($phases as $phase): ?>
            <div class="kanban-block" 
                 data-phase-id="<?php echo esc_attr($phase->phase_id); ?>"
                 data-phase-name="<?php echo esc_attr($phase->phase_name); ?>">
                
                <div class="kanban-block-header">
                    <h4><?php echo esc_html($phase->phase_name); ?></h4>
                    <span class="phase-count" title="<?php esc_attr_e('Total jobs in this phase', 'operations-organizer'); ?>">
                        <?php echo isset($phase_counts[$phase->phase_id]) ? intval($phase_counts[$phase->phase_id]) : 0; ?>
                    </span>
                </div>
                
                <div class="kanban-block-body">
                    <?php if ($phase->phase_id == $job_stream->current_phase_id): ?>
                        <div class="kanban-task" 
                             draggable="true" 
                             data-job-stream-id="<?php echo esc_attr($job_stream->job_stream_id); ?>"
                             data-job-id="<?php echo esc_attr($job_id); ?>"
                             data-current-phase-id="<?php echo esc_attr($phase->phase_id); ?>">
                            
                            <div class="task-header">
                                <span class="task-number">#<?php echo esc_html($job->job_number); ?></span>
                                <?php if ($job_stream->assigned_manager_id): 
                                    $manager = get_userdata($job_stream->assigned_manager_id);
                                    if ($manager):
                                ?>
                                    <span class="task-assignee" title="<?php esc_attr_e('Assigned to', 'operations-organizer'); ?>">
                                        <?php echo esc_html($manager->display_name); ?>
                                    </span>
                                <?php endif; endif; ?>
                            </div>
                            
                            <div class="task-content">
                                <p class="task-client"><?php echo esc_html($job->client_name ?: __('No client', 'operations-organizer')); ?></p>
                                <?php if (!empty($job_stream->notes)): ?>
                                    <p class="task-notes"><?php echo esc_html(wp_trim_words($job_stream->notes, 10)); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="task-footer">
                                <?php if ($job_stream->start_date_stream || $job_stream->due_date_stream): ?>
                                    <div class="task-dates">
                                        <?php if ($job_stream->due_date_stream): 
                                            $due_date = strtotime($job_stream->due_date_stream);
                                            $is_overdue = $due_date < time();
                                        ?>
                                            <span class="task-due-date <?php echo $is_overdue ? 'overdue' : ''; ?>">
                                                <span class="dashicons dashicons-calendar-alt"></span>
                                                <?php echo esc_html(date_i18n(get_option('date_format'), $due_date)); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="task-actions">
                                    <button type="button" class="button-link view-activity-log" 
                                            data-job-stream-id="<?php echo esc_attr($job_stream->job_stream_id); ?>"
                                            title="<?php esc_attr_e('View activity log', 'operations-organizer'); ?>">
                                        <span class="dashicons dashicons-backup"></span>
                                    </button>
                                    <button type="button" class="button-link add-note" 
                                            data-job-stream-id="<?php echo esc_attr($job_stream->job_stream_id); ?>"
                                            title="<?php esc_attr_e('Add note', 'operations-organizer'); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="kanban-block-drop-zone"></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="oo-kanban-legend">
        <p class="description">
            <span class="dashicons dashicons-move"></span> 
            <?php esc_html_e('Drag and drop the job card between phases to update its progress.', 'operations-organizer'); ?>
        </p>
    </div>
</div>

<!-- Activity Log Modal -->
<div id="kanban-activity-log-modal" class="oo-modal" style="display:none;">
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

<!-- Add Note Modal -->
<div id="kanban-add-note-modal" class="oo-modal" style="display:none;">
    <div class="oo-modal-content">
        <span class="oo-close-modal">&times;</span>
        <h2><?php esc_html_e('Add Note', 'operations-organizer'); ?></h2>
        <form id="kanban-add-note-form">
            <input type="hidden" id="note-job-stream-id" value="">
            <div class="form-field">
                <label for="note-content"><?php esc_html_e('Note:', 'operations-organizer'); ?></label>
                <textarea id="note-content" rows="5" class="large-text" required></textarea>
            </div>
            <div class="form-field">
                <label for="note-type"><?php esc_html_e('Note Type:', 'operations-organizer'); ?></label>
                <select id="note-type">
                    <option value="general"><?php esc_html_e('General', 'operations-organizer'); ?></option>
                    <option value="progress"><?php esc_html_e('Progress Update', 'operations-organizer'); ?></option>
                    <option value="issue"><?php esc_html_e('Issue/Problem', 'operations-organizer'); ?></option>
                    <option value="resolution"><?php esc_html_e('Resolution', 'operations-organizer'); ?></option>
                </select>
            </div>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php esc_html_e('Add Note', 'operations-organizer'); ?></button>
                <button type="button" class="button cancel-note"><?php esc_html_e('Cancel', 'operations-organizer'); ?></button>
            </p>
        </form>
    </div>
</div> 