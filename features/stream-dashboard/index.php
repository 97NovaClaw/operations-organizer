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
OO_Stream_Dashboard_DB::init();
OO_Stream_Dashboard_AJAX::init();

// These globals are passed from class-oo-admin-pages.php and are available to this feature.
global $current_stream_id, $current_stream_name, $current_stream_tab_slug, $phases, $employees;

// --- FIX: Fetch the global data required by the views ---
// This data was previously fetched in class-oo-admin-pages.php. It now belongs here.
$phases = OO_Stream_Dashboard_DB::get_phases(array('is_active' => null, 'orderby' => 'stream_id, order_in_stream', 'number' => -1));
$employees = OO_Stream_Dashboard_DB::get_employees(array('is_active' => 1, 'orderby' => 'last_name', 'order' => 'ASC', 'number' => -1));
// --- END FIX ---

// Determine the active sub-tab for this stream page.
$active_tab = isset( $_GET['sub_tab'] ) ? sanitize_key( $_GET['sub_tab'] ) : 'phase_log_actions';

?>
<div class="wrap oo-stream-page oo-stream-page-<?php echo esc_attr( $current_stream_tab_slug ); ?>">
    <h1><?php echo esc_html( $current_stream_name ); ?> <?php esc_html_e( 'Stream Management', 'operations-organizer' ); ?></h1>

    <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo esc_attr( $_REQUEST['page'] ); ?>&sub_tab=phase_log_actions" class="nav-tab <?php echo $active_tab == 'phase_log_actions' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Phase Log Actions', 'operations-organizer' ); ?>
        </a>
        <a href="?page=<?php echo esc_attr( $_REQUEST['page'] ); ?>&sub_tab=phase_dashboard" class="nav-tab <?php echo $active_tab == 'phase_dashboard' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Phase Dashboard', 'operations-organizer' ); ?>
        </a>
        <a href="?page=<?php echo esc_attr( $_REQUEST['page'] ); ?>&sub_tab=phase_kpi_settings" class="nav-tab <?php echo $active_tab == 'phase_kpi_settings' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Phase & KPI Settings', 'operations-organizer' ); ?>
        </a>
    </h2>

	<div class="oo-stream-tab-content">
		<?php
		// TODO: Include the content for the active tab from the /views/ directory.
		$tab_view_path = OO_PLUGIN_DIR . 'features/stream-dashboard/views/';

		switch ( $active_tab ) {
			case 'phase_dashboard':
				include_once $tab_view_path . 'tab-phase-dashboard.php';
				break;
			case 'phase_kpi_settings':
				include_once $tab_view_path . 'tab-settings.php';
				break;
			case 'phase_log_actions':
			default:
				include_once $tab_view_path . 'tab-log-actions.php';
				break;
		}
		?>
	</div>

</div> 