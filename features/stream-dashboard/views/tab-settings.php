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
	$stream_kpi_measures = array();
	if (isset($current_stream_id)) {
		$kpis_from_db = OO_Stream_Dashboard_DB::get_kpi_measures_for_stream($current_stream_id, array('is_active' => null));
		if (!empty($kpis_from_db)) {
			foreach($kpis_from_db as $kpi) {
				$phase_names = OO_Stream_Dashboard_DB::get_phase_names_for_kpi_in_stream($kpi->kpi_measure_id, $current_stream_id);
				$kpi->used_in_phases_in_stream = !empty($phase_names) ? implode(', ', $phase_names) : 'N/A';
				$stream_kpi_measures[] = $kpi;
			}
		}
	}
	oo_log('[Stream Settings Tab] Fetched KPI Measures for Stream ' . $current_stream_id . ': ' . count($stream_kpi_measures), 'StreamDashboardView');
	?>
	<table class="wp-list-table widefat fixed striped table-view-list kpi-measures-stream" style="margin-top:20px;">
		<thead>
			<tr>
				<th><?php esc_html_e('Measure Name', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Key', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Phases Used In (This Stream)', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Unit Type', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
			</tr>
		</thead>
		<tbody id="kpi-measures-list-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
			<?php if ( ! empty( $stream_kpi_measures ) ) : ?>
				<?php foreach ( $stream_kpi_measures as $kpi_measure ) : ?>
					<tr class="kpi-measure-row-<?php echo esc_attr($kpi_measure->kpi_measure_id); ?> <?php echo $kpi_measure->is_active ? 'active' : 'inactive'; ?>">
						<td><strong><button type="button" class="button-link oo-edit-kpi-measure-stream" data-kpi-measure-id="<?php echo esc_attr( $kpi_measure->kpi_measure_id ); ?>"><?php echo esc_html( $kpi_measure->measure_name ); ?></button></strong></td>
						<td><code><?php echo esc_html( $kpi_measure->measure_key ); ?></code></td>
						<td><?php echo esc_html( $kpi_measure->used_in_phases_in_stream ); ?></td>
						<td><?php echo esc_html( ucfirst( $kpi_measure->unit_type ) ); ?></td>
						<td>
							<?php echo $kpi_measure->is_active ? __('Active', 'operations-organizer') : __('Inactive', 'operations-organizer'); ?>
						</td>
						<td class="actions column-actions">
							<button type="button" class="button-secondary oo-edit-kpi-measure-stream" data-kpi-measure-id="<?php echo esc_attr( $kpi_measure->kpi_measure_id ); ?>"><?php esc_html_e('Edit', 'operations-organizer'); ?></button>
							<?php
							$toggle_action_text = $kpi_measure->is_active ? __('Deactivate', 'operations-organizer') : __('Activate', 'operations-organizer');
							$new_status_val = $kpi_measure->is_active ? 0 : 1;
							?>
							<button type="button" class="button-secondary oo-toggle-kpi-measure-status-stream" data-kpi-measure-id="<?php echo esc_attr($kpi_measure->kpi_measure_id); ?>" data-new-status="<?php echo esc_attr($new_status_val); ?>" data-nonce-action="oo_toggle_kpi_measure_status_<?php echo esc_attr($kpi_measure->kpi_measure_id); ?>">
								<?php echo esc_html($toggle_action_text); ?>
							</button>
							| <a href="#" class="oo-delete-kpi-measure-stream" data-kpi-measure-id="<?php echo esc_attr( $kpi_measure->kpi_measure_id ); ?>" data-nonce-action="oo_delete_kpi_measure_<?php echo esc_attr($kpi_measure->kpi_measure_id); ?>" style="color:#b32d2e; text-decoration: none;"><?php esc_html_e('Delete', 'operations-organizer'); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="6"><?php esc_html_e('No KPI measures found for this stream.', 'operations-organizer'); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>

	<h4 style="margin-top: 40px;"><?php esc_html_e('Derived KPI Definitions relevant to this Stream', 'operations-organizer'); ?></h4>
	<button type="button" id="openAddDerivedKpiModalBtn-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="page-title-action">
		<?php esc_html_e('Add New Derived KPI Definition', 'operations-organizer'); ?>
	</button>
	<?php
	$stream_derived_kpis = array();
	$stream_kpi_measure_ids = array();
	if (!empty($stream_kpi_measures)) {
		$stream_kpi_measure_ids = wp_list_pluck($stream_kpi_measures, 'kpi_measure_id');
	}

	if (!empty($stream_kpi_measure_ids)) {
		$all_derived_kpis = OO_Stream_Dashboard_DB::get_derived_kpi_definitions(array('is_active' => null, 'number' => -1)); 
		foreach ($all_derived_kpis as $dkpi) {
			if (in_array($dkpi->primary_kpi_measure_id, $stream_kpi_measure_ids)) {
				$primary_kpi = OO_Stream_Dashboard_DB::get_kpi_measure($dkpi->primary_kpi_measure_id);
				$dkpi->primary_kpi_measure_name = $primary_kpi ? esc_html($primary_kpi->measure_name) : 'Unknown KPI';
				
				$dkpi->secondary_kpi_measure_name = 'N/A';
				if ($dkpi->calculation_type === 'ratio_to_kpi' && !empty($dkpi->secondary_kpi_measure_id)) {
					$secondary_kpi = OO_Stream_Dashboard_DB::get_kpi_measure($dkpi->secondary_kpi_measure_id);
					$dkpi->secondary_kpi_measure_name = $secondary_kpi ? esc_html($secondary_kpi->measure_name) : 'Unknown Secondary KPI';
				}
				$stream_derived_kpis[] = $dkpi;
			}
		}
	}
	oo_log('[Stream Settings Tab] Fetched Derived KPIs for Stream ' . $current_stream_id . ': ' . count($stream_derived_kpis), 'StreamDashboardView');
	?>
	<table class="wp-list-table widefat fixed striped table-view-list derived-kpi-definitions-stream" style="margin-top:20px;">
		<thead>
			<tr>
				<th><?php esc_html_e('Definition Name', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Primary KPI', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Calculation Type', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Secondary KPI (if Ratio)', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
				<th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
			</tr>
		</thead>
		<tbody id="derived-kpi-definitions-list-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
			<?php if ( ! empty( $stream_derived_kpis ) ) : ?>
				<?php foreach ( $stream_derived_kpis as $dkpi ) : ?>
					<tr class="derived-kpi-row-<?php echo esc_attr($dkpi->derived_definition_id); ?> <?php echo $dkpi->is_active ? 'active' : 'inactive'; ?>">
						<td><strong><button type="button" class="button-link oo-edit-derived-kpi-stream" data-derived-kpi-id="<?php echo esc_attr( $dkpi->derived_definition_id ); ?>"><?php echo esc_html( $dkpi->definition_name ); ?></button></strong></td>
						<td><?php echo $dkpi->primary_kpi_measure_name; ?></td>
						<td><?php echo esc_html( ucfirst( str_replace('_', ' ', $dkpi->calculation_type ) ) ); ?></td>
						<td><?php echo $dkpi->secondary_kpi_measure_name; ?></td>
						<td><?php echo $dkpi->is_active ? __('Active', 'operations-organizer') : __('Inactive', 'operations-organizer'); ?></td>
						<td class="actions column-actions">
							<button type="button" class="button-secondary oo-edit-derived-kpi-stream" data-derived-kpi-id="<?php echo esc_attr( $dkpi->derived_definition_id ); ?>"><?php esc_html_e('Edit', 'operations-organizer'); ?></button>
							<?php
							$dkpi_toggle_text = $dkpi->is_active ? __('Deactivate', 'operations-organizer') : __('Activate', 'operations-organizer');
							$dkpi_new_status = $dkpi->is_active ? 0 : 1;
							?>
							<button type="button" class="button-secondary oo-toggle-derived-kpi-status-stream" data-derived-kpi-id="<?php echo esc_attr($dkpi->derived_definition_id); ?>" data-new-status="<?php echo esc_attr($dkpi_new_status); ?>" data-nonce-action="oo_toggle_derived_kpi_status_<?php echo esc_attr($dkpi->derived_definition_id); ?>">
								<?php echo esc_html($dkpi_toggle_text); ?>
							</button>
							| <a href="#" class="oo-delete-derived-kpi-stream" data-derived-kpi-id="<?php echo esc_attr( $dkpi->derived_definition_id ); ?>" data-nonce-action="oo_delete_derived_kpi_<?php echo esc_attr($dkpi->derived_definition_id); ?>" style="color:#b32d2e; text-decoration: none;"><?php esc_html_e('Delete', 'operations-organizer'); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="6"><?php esc_html_e('No Derived KPI definitions found for this stream.', 'operations-organizer'); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>

	<!-- ... All the modals for adding/editing phases, KPIs, and Derived KPIs for the stream page ... -->

</div> 