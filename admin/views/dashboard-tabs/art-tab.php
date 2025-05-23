<?php
// /admin/views/dashboard-tabs/art-tab.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get the phases specific to the Art stream (ID 3)
$art_phases = array();
foreach ($phases as $phase) {
    // Only include phases that belong to Art stream (ID 3) AND have includes_kpi enabled
    if ($phase->stream_id == 3 && !empty($phase->includes_kpi)) {
        $art_phases[] = $phase;
    }
}
?>
<div class="oo-tab-content">
    <h2><?php esc_html_e('Art Stream', 'operations-organizer'); ?></h2>
    
    <div class="oo-dashboard-section">
        <h3><?php esc_html_e('Quick Phase Actions', 'operations-organizer'); ?></h3>
        <p><?php esc_html_e('Enter a Job Number and select a phase to start or stop.', 'operations-organizer'); ?></p>
        
        <?php if (!empty($art_phases)): ?>
            <table class="form-table">
                <?php foreach ($art_phases as $phase): ?>
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
            <p class="oo-notice"><?php esc_html_e('No phases have been configured for the Art stream. Please add some phases through the Phases management page.', 'operations-organizer'); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="oo-dashboard-section">
        <h3><?php esc_html_e('Checkpoint Progress', 'operations-organizer'); ?></h3>
        <p class="description"><?php esc_html_e('Art process flow: Pickup → Inventory → Purge → Estimate → Approval → Invoice → Payment → Cleaned → In Storage → Delivered', 'operations-organizer'); ?></p>
        
        <div class="oo-filter-section">
            <label for="art_filter_job_number"><?php esc_html_e('Filter by Job Number:', 'operations-organizer'); ?></label>
            <input type="text" id="art_filter_job_number" class="regular-text" placeholder="<?php esc_attr_e('Enter job number', 'operations-organizer'); ?>">
            <button id="art_apply_filter" class="button button-secondary"><?php esc_html_e('Apply', 'operations-organizer'); ?></button>
        </div>
        
        <div class="oo-placeholder-kanban">
            <p class="oo-notice oo-info">
                <?php esc_html_e('The Kanban board for Art stream management will be implemented here in a future update. It will display jobs in columns representing their current checkpoint in the workflow.', 'operations-organizer'); ?>
            </p>
        </div>
    </div>
    
    <div class="oo-dashboard-section">
        <h3><?php esc_html_e('Key Metrics', 'operations-organizer'); ?></h3>
        
        <div class="oo-kpi-cards">
            <div class="oo-kpi-card">
                <h4><?php esc_html_e('Art Pieces Processed', 'operations-organizer'); ?></h4>
                <div class="oo-kpi-value">-</div>
                <div class="oo-kpi-label"><?php esc_html_e('pieces (last 30 days)', 'operations-organizer'); ?></div>
            </div>
            
            <div class="oo-kpi-card">
                <h4><?php esc_html_e('Avg. Processing Time', 'operations-organizer'); ?></h4>
                <div class="oo-kpi-value">-</div>
                <div class="oo-kpi-label"><?php esc_html_e('per piece', 'operations-organizer'); ?></div>
            </div>
            
            <div class="oo-kpi-card">
                <h4><?php esc_html_e('Jobs In Progress', 'operations-organizer'); ?></h4>
                <div class="oo-kpi-value">-</div>
                <div class="oo-kpi-label"><?php esc_html_e('active jobs', 'operations-organizer'); ?></div>
            </div>
        </div>
        
        <div class="oo-placeholder-charts">
            <p class="oo-notice oo-info">
                <?php esc_html_e('Detailed metrics and visualizations for the Art stream will be implemented here in a future update. These will include processing time trends, art medium statistics, and more.', 'operations-organizer'); ?>
            </p>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // JS for Quick Phase Actions in Art tab
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
    $('#art_apply_filter').on('click', function() {
        var jobNumber = $('#art_filter_job_number').val();
        if (jobNumber) {
            alert('<?php echo esc_js(__("Filter functionality will be implemented in a future update. Job Number: ", "operations-organizer")); ?>' + jobNumber);
        } else {
            alert('<?php echo esc_js(__("Please enter a job number to filter.", "operations-organizer")); ?>');
        }
    });
});
</script> 