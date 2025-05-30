<?php
// /admin/views/dashboard-tabs/content-tab.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get the phases specific to the Content stream (ID 4)
// This logic is now primarily for the Job Logs table filters on this tab, if it remains.
// Or, this whole tab might be deprecated by the new stream-specific pages.
$content_phases = array();
if (isset($phases) && is_array($phases)) { // Check if $phases is set and is an array
    foreach ($phases as $phase) {
        if ($phase->stream_id == 4 && !empty($phase->includes_kpi)) {
            $content_phases[] = $phase;
        }
    }
}
?>
<div class="oo-tab-content">
    <h2><?php esc_html_e('Content Stream (Legacy View - Functionality Moved)', 'operations-organizer'); ?></h2>
    
    <p class="oo-notice oo-warning">
        <?php esc_html_e('The main functionality for the Content Stream, including Quick Actions and the detailed Job Logs table, has been moved to its own dedicated admin page: ', 'operations-organizer'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=oo_stream_content')); // Assuming oo_stream_content is the slug ?>"><?php esc_html_e('Content Stream Page', 'operations-organizer'); ?></a>.
        <?php esc_html_e('This tab will likely be removed in a future update.', 'operations-organizer'); ?>
    </p>

    <!-- Quick Phase Actions Section Removed - Moved to stream-specific page -->

    <!-- Checkpoint Progress Section Removed - Moved to stream-specific page -->
    
    <div class="oo-dashboard-section">
        <h3><?php esc_html_e('Key Metrics (Placeholder)', 'operations-organizer'); ?></h3>
        <p><em><?php esc_html_e('Key metrics specific to the Content stream would be displayed here. This section is a placeholder as functionality moves to dedicated stream pages.', 'operations-organizer'); ?></em></p>
        <!-- Original Key Metrics cards could remain here temporarily or be removed -->
    </div>

    <!-- Entire Job Logs Data Table Section (Filters, KPI Selector, Table, Modals) Removed - Moved to stream-specific page -->
    <!-- The HTML for filters, table, modals, and KPI selector has been removed from here. -->

</div>

<style>
/* Styles specific to the old content-tab table and modals can be removed if no longer used elsewhere */
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // All JavaScript related to the Content Stream Jobs Data table,
    // its filters, modals, and KPI selector has been moved to 
    // admin/views/stream-pages/content-stream-page.php
    console.log('Content-tab.php is now a legacy view. Main functionality moved.');
});
</script> 