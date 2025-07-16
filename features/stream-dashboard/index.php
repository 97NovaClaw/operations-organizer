<?php
/**
 * Main entry point for the Stream Dashboard feature.
 *
 * This file will be responsible for:
 * 1. Including all other necessary files for this feature (views, ajax handlers, etc.).
 * 2. Displaying the main tab navigation for the Stream Dashboard.
 * 3. Including the view for the currently active tab.
 *
 * @package Operations_Organizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/*
Feature Tree: Stream Dashboard

stream-dashboard/
├── index.php              # Main entry point, loads dependencies, handles tab routing.
├── ajax.php               # Handles all AJAX requests for this feature.
├── helpers.php            # Helper functions specific to the Stream Dashboard.
├── assets/
│   ├── js/
│   │   └── main.js        # Feature-specific JavaScript.
│   └── css/
│       └── main.css       # Feature-specific CSS.
└── views/
    ├── tab-dashboard.php      # View for the main "Phase Dashboard" tab.
    ├── tab-log-actions.php    # View for the "Phase Log Actions" tab.
    ├── tab-settings.php       # View for the "Phase & KPI Settings" tab.
    └── modals/
        ├── edit-log-modal.php # Modal for editing a job log.
        └── ... (other modals)
*/

// Include feature dependencies
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/ajax.php';

// Initialize feature components
oo_log('[EXTREME_DEBUG] ========== INITIALIZING STREAM DASHBOARD COMPONENTS ==========');
OO_Stream_Dashboard_DB::init();
oo_log('[EXTREME_DEBUG] Stream Dashboard DB initialized');
// Note: AJAX handlers are initialized globally in operations-organizer.php
oo_log('[EXTREME_DEBUG] Stream Dashboard AJAX handlers already initialized globally');

// These globals are passed from class-oo-admin-pages.php and are available to this feature.
global $current_stream_id, $current_stream_name, $current_stream_tab_slug, $phases, $employees;

// --- FIX: Fetch the global data required by the views ---
// This data was previously fetched in class-oo-admin-pages.php. It now belongs here.
$phases = OO_Stream_Dashboard_DB::get_phases(array('is_active' => null, 'orderby' => 'stream_id, order_in_stream', 'number' => -1));
$employees = OO_Stream_Dashboard_DB::get_employees(array('is_active' => 1, 'orderby' => 'last_name', 'order' => 'ASC', 'number' => -1));
// --- END FIX ---

// Get feature set sub-tabs for this stream
$feature_set_tabs = oo_get_feature_set_sub_tabs($current_stream_tab_slug);

// Check if stream has Operational Tools feature set
$has_operational_tools = false;
foreach ($feature_set_tabs as $feature_tab) {
    if ($feature_tab['slug'] === 'operational_tools') {
        $has_operational_tools = true;
        break;
    }
}

// Determine the active sub-tab for this stream page
// Default to first available tab based on what feature sets are available
$default_tab = 'phase_log_actions'; // Default if operational tools available
if (!$has_operational_tools && !empty($feature_set_tabs)) {
    // If no operational tools, default to first feature set
    $default_tab = 'feature_set_' . $feature_set_tabs[0]['slug'];
} elseif (!$has_operational_tools && empty($feature_set_tabs)) {
    // No feature sets at all - show a message
    $default_tab = 'no_feature_sets';
}

$active_tab = isset( $_GET['sub_tab'] ) ? sanitize_key( $_GET['sub_tab'] ) : $default_tab;

?>
<div class="wrap oo-stream-page oo-stream-page-<?php echo esc_attr( $current_stream_tab_slug ); ?>">
    <h1><?php echo esc_html( $current_stream_name ); ?> <?php esc_html_e( 'Stream Management', 'operations-organizer' ); ?></h1>

    <h2 class="nav-tab-wrapper">
        <?php
        // Check if stream has Operational Tools feature set for core tabs
        $has_operational_tools = false;
        foreach ($feature_set_tabs as $feature_tab) {
            if ($feature_tab['slug'] === 'operational_tools') {
                $has_operational_tools = true;
                break;
            }
        }
        
        // Only show core tabs if stream has Operational Tools feature set
        if ($has_operational_tools): ?>
            <a href="?page=<?php echo esc_attr( $_REQUEST['page'] ); ?>&sub_tab=phase_log_actions" class="nav-tab <?php echo $active_tab == 'phase_log_actions' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Phase Log Actions', 'operations-organizer' ); ?>
            </a>
            <a href="?page=<?php echo esc_attr( $_REQUEST['page'] ); ?>&sub_tab=phase_dashboard" class="nav-tab <?php echo $active_tab == 'phase_dashboard' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Phase Dashboard', 'operations-organizer' ); ?>
            </a>
            <a href="?page=<?php echo esc_attr( $_REQUEST['page'] ); ?>&sub_tab=phase_kpi_settings" class="nav-tab <?php echo $active_tab == 'phase_kpi_settings' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Phase & KPI Settings', 'operations-organizer' ); ?>
            </a>
        <?php endif; ?>
        
        <?php if (!empty($feature_set_tabs)): ?>
            <?php foreach ($feature_set_tabs as $feature_tab): ?>
                <?php if ($feature_tab['slug'] !== 'operational_tools'): // Don't show operational_tools as a separate tab ?>
                    <a href="?page=<?php echo esc_attr( $_REQUEST['page'] ); ?>&sub_tab=feature_set_<?php echo esc_attr($feature_tab['slug']); ?>" class="nav-tab <?php echo $active_tab == 'feature_set_' . $feature_tab['slug'] ? 'nav-tab-active' : ''; ?>" title="<?php echo esc_attr($feature_tab['description']); ?>">
                        <?php echo esc_html($feature_tab['name']); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </h2>

	<div class="oo-stream-tab-content">
		<?php
		// TODO: Include the content for the active tab from the /views/ directory.
		$tab_view_path = OO_PLUGIN_DIR . 'features/stream-dashboard/views/';

		switch ( $active_tab ) {
			case 'phase_dashboard':
				if ($has_operational_tools) {
					include_once $tab_view_path . 'tab-phase-dashboard.php';
				} else {
					echo '<div class="notice notice-warning"><p>' . __('Phase Dashboard is not available. This stream does not have the Operational Tools feature set assigned.', 'operations-organizer') . '</p></div>';
				}
				break;
			case 'phase_kpi_settings':
				if ($has_operational_tools) {
					include_once $tab_view_path . 'tab-settings.php';
				} else {
					echo '<div class="notice notice-warning"><p>' . __('Phase & KPI Settings is not available. This stream does not have the Operational Tools feature set assigned.', 'operations-organizer') . '</p></div>';
				}
				break;
			case 'phase_log_actions':
				if ($has_operational_tools) {
					include_once $tab_view_path . 'tab-log-actions.php';
				} else {
					echo '<div class="notice notice-warning"><p>' . __('Phase Log Actions is not available. This stream does not have the Operational Tools feature set assigned.', 'operations-organizer') . '</p></div>';
				}
				break;
			case 'no_feature_sets':
				echo '<div class="wrap">';
				echo '<h2>' . __('No Feature Sets Assigned', 'operations-organizer') . '</h2>';
				echo '<div class="notice notice-info">';
				echo '<p>' . sprintf(
					__('This stream does not have any feature sets assigned. Please visit the %s to assign feature sets to this stream.', 'operations-organizer'),
					'<a href="' . admin_url('admin.php?page=oo_feature_sets_management') . '">' . __('Feature Sets Management page', 'operations-organizer') . '</a>'
				) . '</p>';
				echo '</div>';
				echo '</div>';
				break;
			default:
				// Check if this is a feature set tab
				if (strpos($active_tab, 'feature_set_') === 0) {
					$feature_set_slug = substr($active_tab, 12); // Remove 'feature_set_' prefix
					echo '<div class="oo-feature-set-content">';
					echo oo_render_feature_set_content($current_stream_tab_slug, $feature_set_slug);
					echo '</div>';
				} else {
					// Fallback - show appropriate message
					if ($has_operational_tools) {
						include_once $tab_view_path . 'tab-log-actions.php';
					} else {
						echo '<div class="notice notice-warning"><p>' . __('The requested functionality is not available for this stream.', 'operations-organizer') . '</p></div>';
					}
				}
				break;
		}
		?>
	</div>

</div> 