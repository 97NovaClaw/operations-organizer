<?php
// /admin/views/start-job-form-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get pre-filled data from URL parameters
$job_number_get = isset( $_GET['job_number'] ) ? sanitize_text_field( $_GET['job_number'] ) : '';
$phase_id_get = isset( $_GET['phase_id'] ) ? intval( $_GET['phase_id'] ) : 0;
$return_tab_get = isset( $_GET['return_tab'] ) ? sanitize_key( $_GET['return_tab'] ) : '';

$phase_name_display = 'N/A';
$phase_valid = false;
if ( $phase_id_get > 0 ) {
    $phase = OO_DB::get_phase( $phase_id_get );
    if ( $phase && $phase->is_active) {
        $phase_name_display = esc_html( $phase->phase_name );
        $phase_valid = true;
    } else if ($phase && !$phase->is_active) {
        $phase_name_display = '<span style="color:orange;">' . esc_html($phase->phase_name) . ' (' . __('Inactive', 'operations-organizer') . ')</span>';
        // Allow starting an inactive phase if explicitly linked? For now, we allow, but can be restricted.
        $phase_valid = true; // Or set to false to prevent starting inactive phases via QR
    } else {
        $phase_name_display = '<span style="color:red;">' . __('Error: Invalid Phase ID', 'operations-organizer') . '</span>';
    }
}

$active_employees = oo_get_active_employees_for_select();
$current_time_display = oo_get_current_timestamp_display();
$form_disabled = empty( $job_number_get ) || !$phase_valid;

// --- START JOB FORM DEBUGGING ---
if (defined('WP_DEBUG') && WP_DEBUG === true) {
    oo_log(array(
        'GET_job_number' => isset($_GET['job_number']) ? $_GET['job_number'] : 'Not Set',
        'GET_phase_id' => isset($_GET['phase_id']) ? $_GET['phase_id'] : 'Not Set',
        'job_number_get' => $job_number_get,
        'phase_id_get' => $phase_id_get,
        'phase_object' => isset($phase) ? $phase : 'Not Fetched',
        'phase_valid' => $phase_valid,
        'form_disabled' => $form_disabled
    ), 'Start Job Form - Initial Params');
}
// --- END START JOB FORM DEBUGGING ---

?>
<div class="wrap oo-start-job-form-page">
    <h1><?php esc_html_e( 'Start Job Phase', 'operations-organizer' ); ?></h1>

    <?php if ( empty( $job_number_get ) || empty( $phase_id_get ) ): ?>
        <div class="notice notice-error oo-notice"><p>
            <?php esc_html_e( 'Error: Job Number and Phase ID must be provided in the URL and the Phase ID must be valid.', 'operations-organizer' ); ?>
            <br>
            <?php esc_html_e( 'Example QR Code URL:', 'operations-organizer' ); ?>
            <code><?php echo esc_url( admin_url('admin.php?page=oo_start_job&job_number=YOUR_JOB_ID&phase_id=YOUR_PHASE_ID') ); ?></code>
        </p></div>
        <?php 
        // Do not return; allow form to show but be disabled if phase_id was bad but job_number was okay.
        // The $form_disabled variable will handle disabling submit.
        if (empty( $job_number_get ) || empty( $phase_id_get )) return; // Hard return if core params missing
        ?>
    <?php elseif (!$phase_valid && $phase_id_get > 0): // phase_id was given but invalid/not found ?>
         <div class="notice notice-error oo-notice"><p>
            <?php esc_html_e( 'Error: The specified Phase ID is invalid or the phase could not be found.', 'operations-organizer' ); ?>
        </p></div>
    <?php endif; ?>


    <form id="oo-start-job-form" method="post">
        <?php wp_nonce_field( 'oo_start_job_nonce', 'oo_start_job_nonce' ); ?>
        <input type="hidden" name="job_number" value="<?php echo esc_attr( $job_number_get ); ?>" />
        <input type="hidden" name="phase_id" value="<?php echo esc_attr( $phase_id_get ); ?>" />
        <?php if ( !empty($return_tab_get) ) : ?>
            <input type="hidden" name="return_tab" value="<?php echo esc_attr( $return_tab_get ); ?>" />
        <?php endif; ?>

        <table class="form-table oo-form-table">
            <tr valign="top">
                <th scope="row"><label for="employee_number_start"><?php esc_html_e( 'Employee Number', 'operations-organizer' ); ?></label></th>
                <td>
                    <input type="text" id="employee_number_start" name="employee_number" required <?php disabled($form_disabled); ?> placeholder="<?php esc_attr_e('Enter your Employee No.', 'operations-organizer'); ?>" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Job Number', 'operations-organizer' ); ?></th>
                <td><span class="oo-readonly-field"><?php echo esc_html( $job_number_get ); ?></span></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Phase', 'operations-organizer' ); ?></th>
                <td><span class="oo-readonly-field"><?php echo $phase_name_display; // Already escaped or marked safe ?></span></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="notes_start"><?php esc_html_e( 'Notes (Optional)', 'operations-organizer' ); ?></label></th>
                <td><textarea id="notes_start" name="notes" rows="3" class="widefat" <?php disabled($form_disabled); ?>></textarea></td>
            </tr>

            <!-- KPI Fields Placeholder -->
            <tr valign="top" class="kpi-fields-row" style="display: none;">
                <th scope="row"><?php esc_html_e( 'KPIs for this Phase', 'operations-organizer' ); ?></th>
                <td>
                    <div id="phase-kpi-fields-container-start">
                        <!-- Dynamic KPI fields will be injected here -->
                    </div>
                </td>
            </tr>
            <!-- End KPI Fields Placeholder -->

            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Current Timestamp', 'operations-organizer' ); ?></th>
                <td><span class="oo-readonly-field"><?php echo esc_html( $current_time_display ); ?></span> (<?php esc_html_e('Server time will be used on submit', 'operations-organizer'); ?>)</td>
            </tr>
        </table>
        <div class="form-buttons">
            <?php submit_button( __( 'Start Job', 'operations-organizer' ), 'primary', 'start_job_submit', false, array('id' => 'start-job-submit', 'disabled' => $form_disabled) ); ?>
        </div>
    </form>
</div>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Common function to display notices (if needed on this page specifically)
    if (typeof window.showNotice !== 'function') {
        window.showNotice = function(type, message) {
            $('.oo-notice').remove();
            var noticeHtml = '<div class="notice notice-' + type + ' is-dismissible oo-notice"><p>' + message + '</p>' +
                             '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
            $('div.wrap > h1').first().after(noticeHtml);
            setTimeout(function() {
                $('.oo-notice').fadeOut('slow', function() { $(this).remove(); });
            }, 5000);
            $('.oo-notice .notice-dismiss').on('click', function(event) {
                event.preventDefault();
                $(this).closest('.oo-notice').remove();
            });
        };
    }

    const startJobButton = $('#start-job-submit');
    const employeeNumberInput = $('#employee_number_start');
    const phpFormDisabled = <?php echo json_encode($form_disabled); ?>;

    function updateButtonState() {
        if (phpFormDisabled) {
            startJobButton.prop('disabled', true);
            // console.log('Start Job button disabled by PHP.');
        } else if (employeeNumberInput.val().trim() === '') {
            startJobButton.prop('disabled', true);
            // console.log('Start Job button disabled (no employee number entered).');
        } else {
            startJobButton.prop('disabled', false);
            // console.log('Start Job button enabled.');
        }
    }

    // Initial state check
    updateButtonState();

    // Update button state when employee number input changes
    employeeNumberInput.on('input', updateButtonState);

    // AJAX submission is in admin-scripts.js
});
</script> 
<?php
// Localize phase_id for admin-scripts.js
wp_localize_script('oo-admin-scripts', 'oo_form_data', array(
    'phase_id' => $phase_id_get,
    // Add any other data needed by the form's JS that comes from PHP
));
?> 