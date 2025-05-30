<?php
// admin/views/stream-pages/content-stream-page.php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// These globals would be set by the calling function in OO_Admin_Pages
global $current_stream_id, $current_stream_name, $current_stream_tab_slug, $phases, $employees;

$active_tab = isset( $_GET['sub_tab'] ) ? sanitize_key( $_GET['sub_tab'] ) : 'phase_log_actions';

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
        <!-- Add more tabs here as needed -->
    </h2>

    <div class="oo-stream-tab-content">
        <?php if ( $active_tab == 'phase_log_actions' ) : ?>
            <div id="phase-log-actions-content">
                <h3><?php esc_html_e('Quick Phase Actions', 'operations-organizer'); ?></h3>
                <!-- Content from old content-tab.php Quick Phase Actions will go here -->
                <p><em><?php esc_html_e('Quick Phase Actions section to be populated here.', 'operations-organizer'); ?></em></p>
            </div>
        <?php elseif ( $active_tab == 'phase_dashboard' ) : ?>
            <div id="phase-dashboard-content">
                <h3><?php esc_html_e('Phase Dashboard', 'operations-organizer'); ?></h3>
                
                <div class="oo-dashboard-section" id="kanban-section">
                    <h4><?php esc_html_e('Checkpoint Progress (Kanban)', 'operations-organizer'); ?></h4>
                     <!-- Content from old content-tab.php Kanban placeholder will go here -->
                    <p><em><?php esc_html_e('Kanban board section to be populated here.', 'operations-organizer'); ?></em></p>
                </div>

                <div class="oo-dashboard-section" id="stream-jobs-list-section">
                     <h4><?php esc_html_e('Jobs in this Stream', 'operations-organizer'); ?></h4>
                     <!-- New Jobs List Table for this stream will go here -->
                    <p><em><?php esc_html_e('Jobs list table for this stream to be populated here.', 'operations-organizer'); ?></em></p>
                </div>

                <div class="oo-dashboard-section" id="stream-job-logs-section">
                    <h4><?php esc_html_e('Detailed Job Logs for Stream', 'operations-organizer'); ?></h4>
                    <!-- Content from old content-tab.php (filters, KPI selector, table) will go here -->
                    <p><em><?php esc_html_e('Job logs table (with filters and KPI selector) for this stream to be populated here.', 'operations-organizer'); ?></em></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div> 