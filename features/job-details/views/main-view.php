<?php
/**
 * Job Details Main View
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get customer information if available
$customer = null;
if (!empty($job->customer_id)) {
    $customer = OO_DB::get_customer($job->customer_id);
}

// Get company information if available
$company = null;
if (!empty($job->company_id)) {
    $company = OO_DB::get_company($job->company_id);
}
?>

<div class="wrap oo-job-details-wrap">
    <h1>
        <?php echo esc_html(sprintf(__('Job Details: #%s', 'operations-organizer'), $job->job_number)); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=oo_jobs')); ?>" class="page-title-action">
            <?php esc_html_e('Back to Jobs', 'operations-organizer'); ?>
        </a>
    </h1>

    <!-- Job Information Section -->
    <div class="oo-job-info-section">
        <h2><?php esc_html_e('Job Information', 'operations-organizer'); ?></h2>
        
        <div class="oo-info-grid">
            <div class="oo-info-row">
                <div class="oo-info-item">
                    <label><?php esc_html_e('Job Number:', 'operations-organizer'); ?></label>
                    <span>#<?php echo esc_html($job->job_number); ?></span>
                </div>
                <div class="oo-info-item">
                    <label><?php esc_html_e('Overall Status:', 'operations-organizer'); ?></label>
                    <span class="oo-status-badge oo-status-<?php echo esc_attr($job->overall_status); ?>">
                        <?php echo esc_html(ucfirst($job->overall_status)); ?>
                    </span>
                </div>
            </div>
            
            <div class="oo-info-row">
                <div class="oo-info-item">
                    <label><?php esc_html_e('Client Name:', 'operations-organizer'); ?></label>
                    <span><?php echo esc_html($job->client_name ?: __('Not specified', 'operations-organizer')); ?></span>
                </div>
                <div class="oo-info-item">
                    <label><?php esc_html_e('Client Address:', 'operations-organizer'); ?></label>
                    <span><?php echo esc_html($job->address ?: __('Not specified', 'operations-organizer')); ?></span>
                </div>
            </div>
            
            <?php if ($customer || $company): ?>
            <div class="oo-info-row">
                <?php if ($customer): ?>
                <div class="oo-info-item">
                    <label><?php esc_html_e('Customer:', 'operations-organizer'); ?></label>
                    <span><?php echo esc_html($customer->customer_name); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($company): ?>
                <div class="oo-info-item">
                    <label><?php esc_html_e('Company:', 'operations-organizer'); ?></label>
                    <span><?php echo esc_html($company->company_name); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="oo-info-row">
                <div class="oo-info-item">
                    <label><?php esc_html_e('Start Date:', 'operations-organizer'); ?></label>
                    <span>
                        <?php 
                        echo $job->start_date 
                            ? esc_html(date_i18n(get_option('date_format'), strtotime($job->start_date)))
                            : esc_html__('Not set', 'operations-organizer');
                        ?>
                    </span>
                </div>
                <div class="oo-info-item">
                    <label><?php esc_html_e('Due Date:', 'operations-organizer'); ?></label>
                    <span>
                        <?php 
                        echo $job->due_date 
                            ? esc_html(date_i18n(get_option('date_format'), strtotime($job->due_date)))
                            : esc_html__('Not set', 'operations-organizer');
                        ?>
                    </span>
                </div>
            </div>
            
            <?php if (!empty($job->notes)): ?>
            <div class="oo-info-row oo-info-full-width">
                <div class="oo-info-item">
                    <label><?php esc_html_e('Notes:', 'operations-organizer'); ?></label>
                    <div class="oo-notes-content"><?php echo nl2br(esc_html($job->notes)); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Two-column layout container -->
    <div class="oo-job-details-layout">
        
        <!-- Left Column: Stream Details (2/3 width) -->
        <div class="oo-job-stream-column">
            <!-- Stream Tabs Section -->
            <div class="oo-stream-tabs-section">
                <h2><?php esc_html_e('Stream Details', 'operations-organizer'); ?></h2>
        
        <?php if (empty($job_streams)): ?>
            <p class="oo-notice oo-info">
                <?php esc_html_e('No streams have been assigned to this job yet.', 'operations-organizer'); ?>
            </p>
        <?php else: ?>
            <!-- Tab Navigation -->
            <div class="oo-tabs-nav">
                <?php foreach ($job_streams as $index => $job_stream): ?>
                    <button class="oo-tab-button <?php echo $index === 0 ? 'active' : ''; ?>" 
                            data-tab="stream-<?php echo esc_attr($job_stream->stream_id); ?>">
                        <?php echo esc_html($job_stream->stream_name); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <!-- Tab Content -->
            <div class="oo-tabs-content">
                <?php foreach ($job_streams as $index => $job_stream): ?>
                    <div class="oo-tab-panel <?php echo $index === 0 ? 'active' : ''; ?>" 
                         id="stream-<?php echo esc_attr($job_stream->stream_id); ?>"
                         data-stream-id="<?php echo esc_attr($job_stream->stream_id); ?>"
                         data-job-stream-id="<?php echo esc_attr($job_stream->job_stream_id); ?>">
                        <?php 
                        // Set up variables for the tab content
                        $stream_id = $job_stream->stream_id;
                        $stream_slug = $job_stream->stream_slug;
                        
                        // Get feature sets for this stream
                        $stream_feature_sets = function_exists('oo_get_feature_sets_for_stream') ? 
                            oo_get_feature_sets_for_stream($stream_id, 1) : array();
                        
                        // Check if stream has operational tools feature set
                        $has_operational_tools = false;
                        foreach ($stream_feature_sets as $fs) {
                            if ($fs->slug === 'operational_tools') {
                                $has_operational_tools = true;
                                break;
                            }
                        }
                        ?>
                        
                        <div class="oo-stream-tab-wrapper" data-stream-slug="<?php echo esc_attr($stream_slug); ?>">
                            <?php 
                            // Allow features to add content at the very beginning
                            do_action('oo_job_details_tab_start_' . $stream_slug, $job, $job_stream, $stream_id);
                            
                            // If stream has operational tools, show the standard job management interface
                            if ($has_operational_tools) {
                                include __DIR__ . '/tab-content-view.php';
                            } else {
                                // No operational tools - show a message
                                ?>
                                <div class="oo-notice oo-info">
                                    <p><?php echo sprintf(
                                        __('The %s stream does not have operational tools enabled. Job tracking features are not available for this stream.', 'operations-organizer'),
                                        esc_html($job_stream->stream_name)
                                    ); ?></p>
                                </div>
                                <?php
                            }
                            
                            // Render feature set content for this stream
                            if (!empty($stream_feature_sets)) {
                                ?>
                                <div class="oo-feature-sets-content">
                                    <?php
                                    foreach ($stream_feature_sets as $feature_set) {
                                        if ($feature_set->slug !== 'operational_tools') {
                                            // Render non-operational feature sets
                                            echo '<div class="oo-feature-set-section" data-feature-set="' . esc_attr($feature_set->slug) . '">';
                                            
                                            // Use the feature set rendering function if available
                                            if (function_exists('oo_render_feature_set_content')) {
                                                echo oo_render_feature_set_content($stream_slug, $feature_set->slug, array(
                                                    'context' => 'job_details',
                                                    'job' => $job,
                                                    'job_stream' => $job_stream
                                                ));
                                            } else {
                                                // Fallback hook for feature sets
                                                do_action('oo_job_details_feature_set_' . $feature_set->slug, $job, $job_stream, $stream_id);
                                            }
                                            
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>
                                <?php
                            }
                            
                            // Allow features to add content at the end
                            do_action('oo_job_details_tab_end_' . $stream_slug, $job, $job_stream, $stream_id);
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
            </div>
        </div>
        
        <!-- Right Column: Job Activity Log (1/3 width) -->
        <div class="oo-job-activity-column">
            <div class="oo-activity-log-header">
                <h2><?php esc_html_e('Job Activity Log', 'operations-organizer'); ?></h2>
                <button type="button" id="oo-toggle-activity-log" class="button button-secondary">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                    <?php esc_html_e('Collapse', 'operations-organizer'); ?>
                </button>
            </div>
            
            <div id="oo-activity-log-content" class="oo-activity-log-content">
                <div class="oo-activity-log-container">
                                            <table id="oo-job-activity-log-table" class="wp-list-table widefat fixed striped" style="width:100%" data-table-id="job_details_activity_log">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Date/Time', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Activity Type', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Level', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('User', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Stream', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Field', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Old Value', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('New Value', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Notes', 'operations-organizer'); ?></th>
                                <th><?php esc_html_e('Category', 'operations-organizer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<!-- Activity Log Modal (single modal for all streams) -->
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

<?php 
// Include Table View Manager modal if the feature exists
if (file_exists(OO_PLUGIN_DIR . 'features/table-view-manager/views/configure-view-modal.php')) {
    echo '<!-- Table View Manager Modal Included -->';
    include OO_PLUGIN_DIR . 'features/table-view-manager/views/configure-view-modal.php';
} else {
    echo '<!-- Table View Manager Modal NOT FOUND -->';
}
?> 