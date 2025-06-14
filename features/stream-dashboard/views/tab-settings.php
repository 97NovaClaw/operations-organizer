<?php
/**
 * View for the "Phase & KPI Settings" tab in the Stream Dashboard.
 *
 * @package Operations_Organizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// These globals are inherited from the main `index.php` of the feature.
global $current_stream_id, $current_stream_name, $current_stream_tab_slug, $phases;

?>
<div id="phase-kpi-settings-content">
	<h3><?php printf(esc_html__('Phase & KPI Settings for %s Stream', 'operations-organizer'), esc_html($current_stream_name)); ?></h3>
	
	<h4><?php esc_html_e('Phases in this Stream', 'operations-organizer'); ?></h4>
	<button type="button" id="openAddOOPhaseModalBtn-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="page-title-action">
		<?php esc_html_e('Add New Phase to this Stream', 'operations-organizer'); ?>
	</button>
	<?php 
	$current_stream_phases_for_table = array(); 
	if (isset($current_stream_id)) {
		$current_stream_phases_for_table = OO_Stream_Dashboard_DB::get_phases(array(
			'stream_id' => $current_stream_id, 
			'is_active' => null,
			'orderby' => 'order_in_stream', 
			'order' => 'ASC', 
			'number' => -1
		));
	}
	?>
	<table class="wp-list-table widefat fixed striped table-view-list phases" style="margin-top:20px;">
		<thead>
			<tr>
				<th><?php esc_html_e('Phase Name', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Slug', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Description', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Order', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Includes KPIs', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $current_stream_phases_for_table ) ) : ?>
				<?php foreach ( $current_stream_phases_for_table as $phase ) : ?>
					<tr class="<?php echo $phase->is_active ? 'active' : 'inactive'; ?>">
						<td><strong><button type="button" class="button-link oo-edit-phase-button-stream" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>" data-phase-name="<?php echo esc_attr( $phase->phase_name ); ?>"><?php echo esc_html( $phase->phase_name ); ?></button></strong></td>
						<td><code><?php echo esc_html( $phase->phase_slug ); ?></code></td>
						<td><?php echo esc_html( $phase->phase_description ); ?></td>
						<td><?php echo intval( $phase->order_in_stream ); ?></td>
						<td><?php echo $phase->includes_kpi ? 'Yes' : 'No'; ?></td>
						<td>
							<?php 
							echo $phase->is_active ? __( 'Active', 'operations-organizer' ) : __( 'Inactive', 'operations-organizer' );
							?>
						</td>
						<td class="actions column-actions">
							<button type="button" class="button-secondary oo-edit-phase-button-stream" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>"><?php esc_html_e( 'Edit', 'operations-organizer' ); ?></button>
							<?php 
							$toggle_nonce = wp_create_nonce('oo_toggle_phase_status_nonce_' . $phase->phase_id);
							if ($phase->is_active) {
								echo '<button type="button" class="button-secondary oo-toggle-status-phase-button-stream oo-deactivate" data-phase-id="' . esc_attr($phase->phase_id) . '" data-new-status="0" data-nonce="' . esc_attr($toggle_nonce) . '">' . esc_html__('Deactivate', 'operations-organizer') . '</button>';
							} else {
								echo '<button type="button" class="button-secondary oo-toggle-status-phase-button-stream oo-activate" data-phase-id="' . esc_attr($phase->phase_id) . '" data-new-status="1" data-nonce="' . esc_attr($toggle_nonce) . '">' . esc_html__('Activate', 'operations-organizer') . '</button>';
							}
							?>
							| <a href="#" class="oo-delete-phase-button-stream" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>" style="color:#b32d2e; text-decoration: none;"><?php esc_html_e( 'Delete', 'operations-organizer' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="7"><?php esc_html_e( 'No phases found for this stream.', 'operations-organizer' ); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>

	<h4 style="margin-top: 40px;"><?php esc_html_e('KPI Measures in this Stream', 'operations-organizer'); ?></h4>
	<button type="button" id="openAddKpiMeasureModalBtn-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="page-title-action">
		<?php esc_html_e('Add New KPI Measure to this Stream', 'operations-organizer'); ?>
	</button>
	<?php
	// ... PHP logic to fetch and prepare KPI measures ...
	?>
	<table class="wp-list-table widefat fixed striped table-view-list kpi-measures-stream" style="margin-top:20px;">
		<!-- ... table head and body for KPI measures ... -->
	</table>

	<h4 style="margin-top: 40px;"><?php esc_html_e('Derived KPI Definitions relevant to this Stream', 'operations-organizer'); ?></h4>
	<button type="button" id="openAddDerivedKpiModalBtn-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="page-title-action">
		<?php esc_html_e('Add New Derived KPI Definition', 'operations-organizer'); ?>
	</button>
	<?php
	// ... PHP logic to fetch and prepare Derived KPIs ...
	?>
	<table class="wp-list-table widefat fixed striped table-view-list derived-kpi-definitions-stream" style="margin-top:20px;">
		<!-- ... table head and body for Derived KPIs ... -->
	</table>

	<!-- ... All the modals for adding/editing phases, KPIs, and Derived KPIs for the stream page ... -->

</div> 