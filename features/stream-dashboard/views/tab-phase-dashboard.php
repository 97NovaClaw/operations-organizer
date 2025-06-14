<?php
/**
 * View for the "Phase Dashboard" tab in the Stream Dashboard.
 *
 * @package Operations_Organizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// These globals are inherited from the main `index.php` of the feature.
global $current_stream_id, $current_stream_name, $current_stream_tab_slug, $phases, $employees;

// The $stream_phases variable is also needed here for the filters.
$stream_phases = array();
if (isset($current_stream_id) && !empty($phases)) {
    foreach ($phases as $phase_item) {
        if ($phase_item->stream_id == $current_stream_id && !empty($phase_item->includes_kpi)) {
            $stream_phases[] = $phase_item;
        }
    }
}
?>
<div id="phase-dashboard-content">
	<h3><?php esc_html_e('Phase Dashboard', 'operations-organizer'); ?></h3>
	
	<div class="oo-dashboard-section" id="kanban-section">
		<h3><?php esc_html_e('Checkpoint Progress', 'operations-organizer'); ?></h3>
		<p class="description"><?php esc_html_e('Content process flow: Pickup & Inventory → Purge → Estimate → Approval → Invoice → Payment → Cleaned → In Storage → Delivered', 'operations-organizer'); ?></p>
		
		<div class="oo-filter-section" style="/* display:none; */"> <!-- This filter section might be for the Kanban itself later -->
			<label for="stream_page_kanban_filter_job_number"><?php esc_html_e('Filter Kanban by Job Number:', 'operations-organizer'); ?></label>
			<input type="text" id="stream_page_kanban_filter_job_number" class="regular-text" placeholder="<?php esc_attr_e('Enter job number', 'operations-organizer'); ?>">
			<button id="stream_page_kanban_apply_filter" class="button button-secondary"><?php esc_html_e('Apply', 'operations-organizer'); ?></button>
		</div>
		
		<div class="oo-placeholder-kanban">
			<p class="oo-notice oo-info">
				<?php esc_html_e('The Kanban board for this stream will be implemented here. It will display jobs in columns representing their current checkpoint in the workflow.', 'operations-organizer'); ?>
			</p>
		</div>
	</div>

	<div class="oo-dashboard-section" id="stream-jobs-list-section">
		 <h4><?php esc_html_e('Jobs in this Stream', 'operations-organizer'); ?></h4>
		 <!-- New Jobs List Table for this stream will go here -->
		 <table id="stream-jobs-table-<?php echo esc_attr($current_stream_tab_slug); ?>" class="display wp-list-table widefat fixed striped" style="width:100%">
			<thead>
				<tr>
					<th><?php esc_html_e('Job No.', 'operations-organizer'); ?></th>
					<th><?php esc_html_e('Client Name', 'operations-organizer'); ?></th>
					<th><?php esc_html_e('Overall Status', 'operations-organizer'); ?></th>
					<th><?php esc_html_e('Due Date', 'operations-organizer'); ?></th>
					<th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>

	<div class="oo-dashboard-section" id="stream-job-logs-section">
		<h4><?php esc_html_e('Detailed Job Logs for Stream', 'operations-organizer'); ?></h4>
		
		<div class="oo-filter-section">
			<div class="filter-row">
				<div class="filter-item">
					<label for="content_filter_employee_id"><?php esc_html_e('Employee:', 'operations-organizer');?></label>
					<select id="content_filter_employee_id" name="content_filter_employee_id">
						<option value=""><?php esc_html_e('All Employees', 'operations-organizer');?></option>
						<?php foreach ($employees as $employee): ?>
							<option value="<?php echo esc_attr($employee->employee_id); ?>">
								<?php echo esc_html($employee->first_name . ' ' . $employee->last_name); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="filter-item">
					<label for="content_filter_job_number"><?php esc_html_e('Job Number:', 'operations-organizer');?></label>
					<input type="text" id="content_filter_job_number" name="content_filter_job_number" placeholder="<?php esc_attr_e('Enter Job No.', 'operations-organizer');?>">
				</div>

				<div class="filter-item">
					<label for="content_filter_phase_id"><?php esc_html_e('Phase:', 'operations-organizer');?></label>
					<select id="content_filter_phase_id" name="content_filter_phase_id">
						<option value=""><?php esc_html_e('All Phases in this Stream', 'operations-organizer');?></option>
						 <?php if (!empty($stream_phases)): foreach ($stream_phases as $phase): ?>
							<option value="<?php echo esc_attr($phase->phase_id); ?>">
								<?php echo esc_html($phase->phase_name); ?>
							</option>
						<?php endforeach; endif; ?>
					</select>
				</div>

				<div class="filter-item">
					<label for="content_filter_status"><?php esc_html_e('Status:', 'operations-organizer');?></label>
					<select id="content_filter_status" name="content_filter_status">
						<option value=""><?php esc_html_e('All Statuses', 'operations-organizer');?></option>
						<option value="started"><?php esc_html_e('Running', 'operations-organizer');?></option>
						<option value="completed"><?php esc_html_e('Completed', 'operations-organizer');?></option>
					</select>
				</div>
			</div>

			<div class="filter-row">
				<div class="filter-item">
					<label for="content_filter_date_from"><?php esc_html_e('Date From:', 'operations-organizer');?></label>
					<input type="text" id="content_filter_date_from" name="content_filter_date_from" class="oo-datepicker" placeholder="YYYY-MM-DD">
				</div>

				<div class="filter-item">
					<label for="content_filter_date_to"><?php esc_html_e('Date To:', 'operations-organizer');?></label>
					<input type="text" id="content_filter_date_to" name="content_filter_date_to" class="oo-datepicker" placeholder="YYYY-MM-DD">
				</div>

				<div class="filter-item oo-filter-buttons">
					<button id="content_apply_filters_button" class="button button-primary"><?php esc_html_e('Apply Filters', 'operations-organizer');?></button>
					<button id="content_clear_filters_button" class="button"><?php esc_html_e('Clear Filters', 'operations-organizer');?></button>
				</div>
			</div>
		</div>
		
		<div class="oo-kpi-column-selector-section filter-row" style="margin-top: 10px; margin-bottom: 20px; padding: 10px; background-color: #f9f9f9; border: 1px solid #ccd0d4;">
			<div class="filter-item">
				 <label style="font-weight: bold;"><?php esc_html_e('Select Columns to Display:', 'operations-organizer'); ?></label>
				 <button type="button" id="content_open_kpi_selector_modal" class="button"><?php esc_html_e('Choose Columns', 'operations-organizer'); ?></button>
				 <span id="content_selected_kpi_count" style="margin-left: 10px;"></span>
				 <button type="button" id="save_content_columns_as_default" class="button button-secondary" style="margin-left: 15px; display:none;"><?php esc_html_e('Save as Default', 'operations-organizer'); ?></button>
				 <span id="content_columns_default_saved_msg" style="margin-left: 10px; color: green; font-style: italic; display:none;"><?php esc_html_e('Default saved!', 'operations-organizer'); ?></span>
			</div>
		</div>
		
		<div id="content-export-options" style="margin-bottom: 20px;">
			<button id="content_export_csv_button" class="button"><?php esc_html_e('Export to CSV', 'operations-organizer');?></button>
		</div>

		<table id="content-dashboard-table" class="display wp-list-table widefat fixed striped" style="width:100%">
			<thead>
				<!-- Headers are generated by JS -->
			</thead>
			<tbody>
				<!-- Data will be loaded by DataTables via AJAX -->
			</tbody>
		</table>

		<!-- KPI Column Selector Modal for Stream Page -->
		<div id="kpi-column-selector-modal-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" class="oo-modal" style="display:none;">
			<div class="oo-modal-content" style="width: 600px; max-width: 90%;">
				<span class="oo-modal-close">&times;</span>
				<h2><?php esc_html_e('Choose Columns to Display', 'operations-organizer'); ?></h2>
				<div id="kpi-column-list-stream-<?php echo esc_attr($current_stream_tab_slug); ?>" style="max-height: 400px; overflow-y: auto; margin-bottom: 15px; border: 1px solid #ddd; padding: 10px;">
					<!-- Checkboxes will be populated here by JavaScript -->
					<p><?php esc_html_e('Loading columns...', 'operations-organizer'); ?></p>
				</div>
				<button id="apply_selected_kpi_columns_stream_<?php echo esc_attr($current_stream_tab_slug); ?>" class="button button-primary"><?php esc_html_e('Apply Columns', 'operations-organizer'); ?></button>
				<button id="kpi_selector_select_all_stream_<?php echo esc_attr($current_stream_tab_slug); ?>" class="button button-secondary" style="margin-left:10px;"><?php esc_html_e('Select All', 'operations-organizer'); ?></button>
				<button id="kpi_selector_deselect_all_stream_<?php echo esc_attr($current_stream_tab_slug); ?>" class="button button-secondary" style="margin-left:5px;"><?php esc_html_e('Deselect All', 'operations-organizer'); ?></button>
			</div>
		</div>

		<!-- Add modal dialog for editing job logs -->
		<div id="edit-log-modal" class="oo-modal">
			<div class="oo-modal-content">
				<span class="oo-modal-close">&times;</span>
				<h2><?php esc_html_e('Edit Job Log', 'operations-organizer'); ?></h2>
				<form id="edit-log-form">
					<input type="hidden" id="edit_log_id" name="edit_log_id">
					<input type="hidden" id="edit_log_employee_id" name="edit_log_employee_id">
					<input type="hidden" id="edit_log_job_id" name="edit_log_job_id">
					<input type="hidden" id="edit_log_phase_id" name="edit_log_phase_id">
					<input type="hidden" id="edit_log_stream_id" name="edit_log_stream_id" value="<?php echo esc_attr($current_stream_id); ?>">
					<input type="hidden" id="edit_log_status" name="edit_log_status">
					<input type="hidden" name="action" value="oo_update_job_log">
					<input type="hidden" name="oo_edit_log_nonce_field" value="<?php echo wp_create_nonce('oo_edit_log_nonce'); ?>">
					
					<div class="form-field">
						<label for="edit_log_job_number"><?php esc_html_e('Job Number', 'operations-organizer'); ?></label>
						<div class="job-number-container" style="display: flex; align-items: center;">
							<input type="text" id="edit_log_job_number" name="edit_log_job_number" readonly style="flex-grow: 1;">
							<div style="margin-left: 10px;">
								<input type="checkbox" id="enable_job_number_edit" name="enable_job_number_edit">
								<label for="enable_job_number_edit"><?php esc_html_e('Allow Edit', 'operations-organizer'); ?></label>
							</div>
						</div>
						<div class="form-description" style="color: #d63638; display: none;" id="job_number_warning">
							<?php esc_html_e('Warning: Changing the job number may affect related records. Only change if absolutely necessary.', 'operations-organizer'); ?>
						</div>
					</div>
					
					<div class="form-field">
						<label for="edit_log_start_time_editable" id="edit_log_start_time_label"><?php esc_html_e('Start Time', 'operations-organizer'); ?></label>
						<div style="display: flex; align-items: center;">
							<input type="datetime-local" id="edit_log_start_time_editable" name="edit_log_start_time" style="flex-grow: 1;">
							<button type="button" id="set_start_time_now" class="button" style="margin-left: 10px;"><?php esc_html_e('Now', 'operations-organizer'); ?></button>
						</div>
						 <p class="description"><?php esc_html_e('Adjust start time if necessary. Ensure this is accurate as it affects duration and derived metrics.', 'operations-organizer'); ?></p>
					</div>
					
					<div class="form-field">
						<label for="edit_log_end_time" id="edit_log_end_time_label"><?php esc_html_e('End Time', 'operations-organizer'); ?></label>
						<div style="display: flex; align-items: center;">
							<input type="datetime-local" id="edit_log_end_time" name="edit_log_end_time" style="flex-grow: 1;">
							<button type="button" id="set_end_time_now" class="button" style="margin-left: 10px;"><?php esc_html_e('Now', 'operations-organizer'); ?></button>
						</div>
						<div class="form-description" id="edit_log_end_time_description" style="display:none;">
							<?php esc_html_e('Leave blank to keep job running, or set date/time to stop the job.', 'operations-organizer'); ?>
						</div>
					</div>

					<!-- Dynamic KPI fields will be injected here -->
					<div id="edit-log-dynamic-kpi-fields" class="form-field" style="padding-top:10px; margin-top:15px; border-top: 1px solid #eee;">
						<!-- Fields are generated by JS -->
					</div>
					
					<div class="form-field">
						<label for="edit_log_notes"><?php esc_html_e('Notes', 'operations-organizer'); ?></label>
						<textarea id="edit_log_notes" name="edit_log_notes" rows="3"></textarea>
					</div>
					
					<div class="form-field">
						<button type="submit" id="save_as_running" class="button button-primary" style="display:none;"><?php esc_html_e('Save Changes (Keep Running)', 'operations-organizer'); ?></button>
						<button type="submit" id="save_as_completed" class="button button-primary" style="display:none;"><?php esc_html_e('Save Changes & Stop Job', 'operations-organizer'); ?></button>
						<button type="submit" id="save_changes" class="button button-primary" style="display:none;"><?php esc_html_e('Save Changes', 'operations-organizer'); ?></button>
						<button type="button" class="button oo-modal-cancel"><?php esc_html_e('Cancel', 'operations-organizer'); ?></button>
					</div>
				</form>
			</div>
		</div>

		<!-- Add modal dialog for delete confirmation -->
		<div id="delete-log-modal" class="oo-modal">
			<div class="oo-modal-content">
				<span class="oo-modal-close">&times;</span>
				<h2><?php esc_html_e('Confirm Deletion', 'operations-organizer'); ?></h2>
				<p><?php esc_html_e('Are you sure you want to delete this job log?', 'operations-organizer'); ?></p>
				<form id="delete-log-form">
					<input type="hidden" id="delete_log_id" name="log_id">
					<input type="hidden" name="action" value="oo_delete_job_log">
					<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('oo_delete_log_nonce'); ?>">
					
					<div class="form-field">
						<button type="submit" class="button button-primary"><?php esc_html_e('Delete', 'operations-organizer'); ?></button>
						<button type="button" class="button oo-modal-cancel"><?php esc_html_e('Cancel', 'operations-organizer'); ?></button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div> 