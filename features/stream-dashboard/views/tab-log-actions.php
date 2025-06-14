<?php
/**
 * View for the "Phase Log Actions" tab in the Stream Dashboard.
 *
 * @package Operations_Organizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// These globals are inherited from the main `index.php` of the feature.
global $current_stream_id, $current_stream_name, $phases;

// Prepare phases for the current stream for Quick Actions
$stream_phases = array();
if (isset($current_stream_id) && !empty($phases)) {
    foreach ($phases as $phase_item) {
        if ($phase_item->stream_id == $current_stream_id && !empty($phase_item->includes_kpi)) {
            $stream_phases[] = $phase_item;
        }
    }
}
?>
<div id="phase-log-actions-content">
	<div class="oo-dashboard-section">
		<h3><?php esc_html_e('Quick Phase Actions', 'operations-organizer'); ?></h3>
		<p><?php esc_html_e('Enter a Job Number and select a phase to start or stop.', 'operations-organizer'); ?></p>
		
		<?php if (!empty($stream_phases)): ?>
			<table class="form-table">
				<?php foreach ($stream_phases as $phase): ?>
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
		<?php else:
			$stream_name_for_notice = isset($current_stream_name) ? esc_html($current_stream_name) : 'this stream';
		?>
			<p class="oo-notice"><?php printf(esc_html__('No phases (with KPIs enabled) have been configured for %s. Please add or update phases through the Phases management page.', 'operations-organizer'), $stream_name_for_notice); ?></p>
		<?php endif; ?>
	</div>
</div> 