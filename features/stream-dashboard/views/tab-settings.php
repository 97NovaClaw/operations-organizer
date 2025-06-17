<?php
/**
 * View for the "Phase & KPI Settings" tab in the Stream Dashboard.
 * This file is a direct copy of the working logic from the original monolithic template.
 *
 * @package Operations_Organizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// These globals are inherited from the main `index.php` of the feature.
global $current_stream_id, $current_stream_name, $current_stream_tab_slug;
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
		$current_stream_phases_for_table = OO_DB::get_phases(array(
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
				<th style="width: 30px;"><?php esc_html_e('Order', 'operations-organizer'); ?></th>
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
					<tr class="<?php echo $phase->is_active ? 'active' : 'inactive'; ?>" data-phase-id="<?php echo esc_attr($phase->phase_id); ?>">
						<td><span class="oo-phase-drag-handle dashicons dashicons-menu" style="cursor: move;" title="Drag to reorder"></span></td>
						<td><strong><button type="button" class="button-link oo-edit-phase-button-stream" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>" data-phase-name="<?php echo esc_attr( $phase->phase_name ); ?>"><?php echo esc_html( $phase->phase_name ); ?></button></strong></td>
						<td><code><?php echo esc_html( $phase->phase_slug ); ?></code></td>
						<td><?php echo esc_html( $phase->phase_description ); ?></td>
						<td><?php echo intval( $phase->order_in_stream ); ?></td>
						<td><?php echo $phase->includes_kpi ? 'Yes' : 'No'; ?></td>
						<td><?php echo $phase->is_active ? __( 'Active', 'operations-organizer' ) : __( 'Inactive', 'operations-organizer' ); ?></td>
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
				<tr><td colspan="8"><?php esc_html_e( 'No phases found for this stream.', 'operations-organizer' ); ?></td></tr>
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
		$kpis_from_db = OO_DB::get_kpi_measures_for_stream($current_stream_id, array('is_active' => null));
		if (!empty($kpis_from_db)) {
			foreach($kpis_from_db as $kpi) {
				$phase_names = OO_DB::get_phase_names_for_kpi_in_stream($kpi->kpi_measure_id, $current_stream_id);
				$kpi->used_in_phases_in_stream = !empty($phase_names) ? implode(', ', $phase_names) : 'N/A';
				$stream_kpi_measures[] = $kpi;
			}
		}
	}
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
						<td><?php echo $kpi_measure->is_active ? __('Active', 'operations-organizer') : __('Inactive', 'operations-organizer'); ?></td>
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
		$all_derived_kpis = OO_DB::get_derived_kpi_definitions(array('is_active' => null, 'number' => -1)); 
		foreach ($all_derived_kpis as $dkpi) {
			if (in_array($dkpi->primary_kpi_measure_id, $stream_kpi_measure_ids)) {
				$primary_kpi = OO_DB::get_kpi_measure($dkpi->primary_kpi_measure_id);
				$dkpi->primary_kpi_measure_name = $primary_kpi ? esc_html($primary_kpi->measure_name) : 'Unknown KPI';
				
				$dkpi->secondary_kpi_measure_name = 'N/A';
				if ($dkpi->calculation_type === 'ratio_to_kpi' && !empty($dkpi->secondary_kpi_measure_id)) {
					$secondary_kpi = OO_DB::get_kpi_measure($dkpi->secondary_kpi_measure_id);
					$dkpi->secondary_kpi_measure_name = $secondary_kpi ? esc_html($secondary_kpi->measure_name) : 'Unknown Secondary KPI';
				}
				$stream_derived_kpis[] = $dkpi;
			}
		}
	}
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

	<!-- ALL MODALS FROM THE ORIGINAL FILE -->
	<div id="addOOPhaseModal" class="oo-modal" style="display:none;">
		<div class="oo-modal-content">
			<span class="oo-modal-close">&times;</span>
			<h2><?php esc_html_e( 'Add New Phase to', 'operations-organizer' ); ?> <?php echo esc_html($current_stream_name); ?></h2>
			<form id="add-phase-form">
				<table class="form-table oo-form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Phase Name', 'operations-organizer' ); ?></th>
						<td><input type="text" id="add_phase_name" name="phase_name" required /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Description', 'operations-organizer' ); ?></th>
						<td><textarea id="add_phase_description" name="phase_description"></textarea></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Operations KPIs', 'operations-organizer' ); ?></th>
						<td>
							<label>
								<input type="checkbox" id="add_includes_kpi" name="includes_kpi" value="1" checked>
								<?php esc_html_e( 'Includes operations KPIs', 'operations-organizer' ); ?>
							</label>
							<p class="description"><?php esc_html_e('If checked, this phase will appear in the stream page and users can input tracking data.', 'operations-organizer'); ?></p>
						</td>
					</tr>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Phase', 'operations-organizer' ); ?></button>
					<span class="spinner"></span>
				</p>
			</form>
		</div>
	</div>

	<div id="editOOPhaseModal" class="oo-modal" style="display:none;">
		<div class="oo-modal-content">
			<span class="oo-modal-close">&times;</span>
			<h2><?php esc_html_e( 'Edit Phase', 'operations-organizer' ); ?></h2>
			<form id="edit-phase-form">
				<input type="hidden" id="edit_phase_id" name="edit_phase_id" value="" />
				<table class="form-table oo-form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Phase Name', 'operations-organizer' ); ?></th>
						<td><input type="text" id="edit_phase_name" name="edit_phase_name" required /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Description', 'operations-organizer' ); ?></th>
						<td><textarea id="edit_phase_description" name="edit_phase_description"></textarea></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Operations KPIs', 'operations-organizer' ); ?></th>
						<td>
							<label>
								<input type="checkbox" id="edit_includes_kpi" name="edit_includes_kpi" value="1">
								<?php esc_html_e( 'Includes operations KPIs', 'operations-organizer' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'operations-organizer' ); ?></button>
					<span class="spinner"></span>
				</p>
			</form>
		</div>
	</div>
	<!-- Add KPI Measure Modal for Stream Page -->
	<div id="addKpiMeasureModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;">
		<div class="oo-modal-content">
			<span class="oo-modal-close">&times;</span>
			<h2><?php esc_html_e( 'Add New KPI Measure (Stream Specific Context)', 'operations-organizer' ); ?></h2>
			<form id="oo-add-kpi-measure-form-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
				<input type="hidden" name="oo_action" value="add_kpi_measure">
				<input type="hidden" name="context" value="stream_page">
				<input type="hidden" name="stream_id_context" value="<?php echo esc_attr($current_stream_id); ?>">

				<div class="form-field form-required">
					<label for="add_kpi_measure_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Measure Name', 'operations-organizer' ); ?></label>
					<input type="text" name="measure_name" id="add_kpi_measure_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required>
					<p><?php esc_html_e( 'The human-readable name for this KPI (e.g., "Boxes Packed", "Items Scanned").', 'operations-organizer' ); ?></p>
				</div>

				<div class="form-field form-required" style="display:none;">
					<label for="add_kpi_measure_key-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Measure Key', 'operations-organizer' ); ?></label>
					<input type="text" name="measure_key" id="add_kpi_measure_key-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
					<p><?php esc_html_e( 'A unique key for this KPI, used internally (e.g., "boxes_packed", "items_scanned"). Lowercase, underscores, no spaces. Cannot be changed after creation.', 'operations-organizer' ); ?></p>
				</div>

				<div class="form-field">
					<label for="add_kpi_unit_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Unit Type', 'operations-organizer' ); ?></label>
					<select name="unit_type" id="add_kpi_unit_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
						<?php 
						$unit_types = array( 'integer', 'decimal', 'text', 'boolean' );
						foreach ( $unit_types as $type ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>" <?php selected( 'integer', $type ); ?>>
								<?php echo esc_html( ucfirst( $type ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p><?php esc_html_e( 'The type of data this KPI represents (e.g., Integer for counts, Decimal for amounts, Text for notes).', 'operations-organizer' ); ?></p>
				</div>
				
				<div class="form-field">
					<label for="add_kpi_is_active-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
						<input type="checkbox" name="is_active" id="add_kpi_is_active-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" value="1" checked>
						<?php esc_html_e( 'Active', 'operations-organizer' ); ?>
					</label>
					<p><?php esc_html_e( 'Inactive measures will not be available for new phase assignments.', 'operations-organizer' ); ?></p>
				</div>

				<div class="form-field form-required kpi-phase-linking-section-stream">
					<label><?php printf(esc_html__('Link to Phases in %s (at least one required)', 'operations-organizer'), esc_html($current_stream_name)); ?></label>
					<div id="add-kpi-link-to-phases-list-<?php echo esc_attr($current_stream_tab_slug); ?>" class="phase-checkbox-group" style="max-height: 150px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 5px;">
						<?php
						// Fetch phases for the current stream to populate checkboxes
						$phases_in_current_stream = array();
						if (isset($current_stream_id)) {
							$phases_in_current_stream = OO_DB::get_phases(array(
								'stream_id' => $current_stream_id,
								'is_active' => 1, // Only offer to link to active phases
								'orderby' => 'order_in_stream',
								'order' => 'ASC',
								'number' => -1
							));
						}
						if (!empty($phases_in_current_stream)) {
							foreach ($phases_in_current_stream as $phase) {
								echo '<label style="display: block;"><input type="checkbox" name="link_to_phases[]" value="' . esc_attr($phase->phase_id) . '"> ' . esc_html($phase->phase_name) . '</label>';
							}
						} else {
							echo '<p>' . esc_html__('No active phases found in this stream to link to.', 'operations-organizer') . '</p>';
						}
						?>
					</div>
					<p class="description"><?php esc_html_e('This KPI measure will be associated with the selected phases in the current stream.', 'operations-organizer'); ?></p>
				</div>

				<?php submit_button( __( 'Add KPI Measure', 'operations-organizer' ), 'primary', 'submit_add_kpi_measure-stream-' . $current_stream_tab_slug ); ?>
			</form>
		</div>
	</div>

	<!-- Edit KPI Measure Modal for Stream Page -->
	<div id="editKpiMeasureModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;">
		<div class="oo-modal-content">
			<span class="oo-modal-close">&times;</span>
			<h2><?php esc_html_e( 'Edit KPI Measure (Stream Specific Context)', 'operations-organizer' ); ?>: <span id="editKpiMeasureNameDisplay-<?php echo esc_attr($current_stream_tab_slug); ?>"></span></h2>
			<form id="oo-edit-kpi-measure-form-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
				<input type="hidden" name="oo_action" value="edit_kpi_measure">
				<input type="hidden" name="context" value="stream_page">
				<input type="hidden" name="stream_id_context" value="<?php echo esc_attr($current_stream_id); ?>">
				<input type="hidden" id="edit_kpi_measure_id-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" name="kpi_measure_id" value="">

				<div class="form-field form-required">
					<label for="edit_kpi_measure_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Measure Name', 'operations-organizer' ); ?></label>
					<input type="text" name="measure_name" id="edit_kpi_measure_name-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" required>
					<p><?php esc_html_e( 'The human-readable name for this KPI.', 'operations-organizer' ); ?></p>
				</div>

				<div class="form-field form-required">
					<label for="edit_kpi_measure_key-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Measure Key', 'operations-organizer' ); ?></label>
					<input type="text" name="measure_key" id="edit_kpi_measure_key-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" readonly>
					<p><?php esc_html_e( 'The unique key for this KPI. Cannot be changed after creation.', 'operations-organizer' ); ?></p>
				</div>

				<div class="form-field">
					<label for="edit_kpi_unit_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>"><?php esc_html_e( 'Unit Type', 'operations-organizer' ); ?></label>
					<select name="unit_type" id="edit_kpi_unit_type-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
						<?php 
						foreach ( $unit_types as $type ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>">
								<?php echo esc_html( ucfirst( $type ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p><?php esc_html_e( 'The type of data this KPI represents.', 'operations-organizer' ); ?></p>
				</div>
				
				<div class="form-field">
					<label for="edit_kpi_is_active-stream-<?php echo esc_attr($current_stream_tab_slug); ?>">
						<input type="checkbox" name="is_active" id="edit_kpi_is_active-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" value="1">
						<?php esc_html_e( 'Active', 'operations-organizer' ); ?>
					</label>
					<p><?php esc_html_e( 'Inactive measures will not be available for new phase assignments.', 'operations-organizer' ); ?></p>
				</div>

				<div class="form-field kpi-phase-linking-section-stream">
					<label><?php printf(esc_html__('Linked to Phases in %s', 'operations-organizer'), esc_html($current_stream_name)); ?></label>
					<div id="edit-kpi-link-to-phases-list-<?php echo esc_attr($current_stream_tab_slug); ?>" class="phase-checkbox-group" style="max-height: 150px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 5px;">
						<!-- Checkboxes will be populated by JavaScript -->
						<p><?php esc_html_e('Loading phases...', 'operations-organizer'); ?></p>
					</div>
					<p class="description"><?php esc_html_e('Select phases in the current stream to associate with this KPI measure.', 'operations-organizer'); ?></p>
				</div>

				<?php submit_button( __( 'Save KPI Measure Changes', 'operations-organizer' ), 'primary', 'submit_edit_kpi_measure-stream-' . $current_stream_tab_slug ); ?>
			</form>
		</div>
	</div>

	<!-- Derived KPI Modals (simplified for now) -->
	<div id="addDerivedKpiModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;">
		<div class="oo-modal-content">
			<span class="oo-modal-close">&times;</span>
			<h2><?php esc_html_e( 'Add New Derived KPI Definition', 'operations-organizer' ); ?></h2>
			<p><?php esc_html_e( 'Derived KPI functionality coming soon...', 'operations-organizer' ); ?></p>
		</div>
	</div>
	<div id="editDerivedKpiModal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;">
		<div class="oo-modal-content">
			<span class="oo-modal-close">&times;</span>
			<h2><?php esc_html_e( 'Edit Derived KPI Definition', 'operations-organizer' ); ?></h2>
			<p><?php esc_html_e( 'Derived KPI functionality coming soon...', 'operations-organizer' ); ?></p>
		</div>
	</div>
</div> 