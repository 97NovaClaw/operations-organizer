<?php
// /admin/views/stop-job-form-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get pre-filled data from URL parameters
$log_id = isset( $_GET['log_id'] ) ? intval( $_GET['log_id'] ) : 0;
$job_number_get = isset( $_GET['job_number'] ) ? sanitize_text_field( $_GET['job_number'] ) : '';
$phase_id_get = isset( $_GET['phase_id'] ) ? intval( $_GET['phase_id'] ) : 0;
$return_tab_get = isset( $_GET['return_tab'] ) ? sanitize_key( $_GET['return_tab'] ) : '';

$phase_name_display = 'N/A';
$phase_valid = false;
if ( $phase_id_get > 0 ) {
    $phase = OO_DB::get_phase( $phase_id_get );
    if ( $phase ) { // Phase exists, active or not
        $phase_name_display = esc_html( $phase->phase_name );
        if (!$phase->is_active) {
             $phase_name_display .= ' (' . __('Inactive', 'operations-organizer') . ')';
        }
        $phase_valid = true; // We allow stopping jobs for phases that might have become inactive
    } else {
        $phase_name_display = '<span style="color:red;">' . __('Error: Invalid Phase ID', 'operations-organizer') . '</span>';
    }
}

$active_employees = oo_get_active_employees_for_select();
$current_time_display = oo_get_current_timestamp_display();
$form_disabled = empty( $job_number_get ) || !$phase_valid;

?>
<div class="wrap oo-stop-job-form-page">
    <h1><?php esc_html_e( 'Stop Job Phase & Record KPIs', 'operations-organizer' ); ?></h1>

    <?php if ( empty( $job_number_get ) || empty( $phase_id_get ) ): ?>
        <div class="notice notice-error oo-notice"><p>
            <?php esc_html_e( 'Error: Job Number and Phase ID must be provided in the URL and the Phase ID must be valid.', 'operations-organizer' ); ?>
            <br>
            <?php esc_html_e( 'Example QR Code URL:', 'operations-organizer' ); ?>
            <code><?php echo esc_url( admin_url('admin.php?page=oo_stop_job&job_number=YOUR_JOB_ID&phase_id=YOUR_PHASE_ID') ); ?></code>
        </p></div>
        <?php 
        if (empty( $job_number_get ) || empty( $phase_id_get )) return;
        ?>
    <?php elseif (!$phase_valid && $phase_id_get > 0): ?>
         <div class="notice notice-error oo-notice"><p>
            <?php esc_html_e( 'Error: The specified Phase ID is invalid or the phase could not be found.', 'operations-organizer' ); ?>
        </p></div>
    <?php endif; ?>

    <form id="oo-stop-job-form" method="post">
        <?php wp_nonce_field( 'oo_stop_job_nonce', 'oo_stop_job_nonce' ); ?>
        <input type="hidden" name="log_id" value="<?php echo esc_attr( $log_id ); ?>" />
        <input type="hidden" name="job_number" value="<?php echo esc_attr( $job_number_get ); ?>" />
        <input type="hidden" name="phase_id" value="<?php echo esc_attr( $phase_id_get ); ?>" />
        <?php if ( !empty($return_tab_get) ) : ?>
            <input type="hidden" name="return_tab" value="<?php echo esc_attr( $return_tab_get ); ?>" />
        <?php endif; ?>

        <table class="form-table oo-form-table">
            <tr valign="top">
                <th scope="row"><label for="employee_number_stop"><?php esc_html_e( 'Employee Number', 'operations-organizer' ); ?></label></th>
                <td><input type="text" id="employee_number_stop" name="employee_number" required <?php disabled($form_disabled); ?> placeholder="<?php esc_attr_e('Enter your Employee No.', 'operations-organizer'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Job Number', 'operations-organizer' ); ?></th>
                <td><span class="oo-readonly-field"><?php echo esc_html( $job_number_get ); ?></span></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Phase', 'operations-organizer' ); ?></th>
                <td><span class="oo-readonly-field"><?php echo $phase_name_display; ?></span></td>
            </tr>
            
            <!-- KPI Fields Placeholder -->
            <tr valign="top" class="kpi-fields-row">
                <th scope="row"><?php esc_html_e( 'KPIs for this Phase', 'operations-organizer' ); ?></th>
                <td>
                    <div id="phase-kpi-fields-container-stop">
                        <!-- Dynamic KPI fields will be injected here -->
                        <p><?php esc_html_e( 'Loading KPI fields...', 'operations-organizer' ); ?></p>
                    </div>
                </td>
            </tr>
            <!-- End KPI Fields Placeholder -->
            
             <tr valign="top">
                <th scope="row"><label for="notes_stop"><?php esc_html_e( 'Notes (Optional)', 'operations-organizer' ); ?></label></th>
                <td><textarea id="notes_stop" name="notes" rows="3" class="widefat" <?php disabled($form_disabled); ?>></textarea></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Current Timestamp', 'operations-organizer' ); ?></th>
                <td><span class="oo-readonly-field"><?php echo esc_html( $current_time_display ); ?></span> (<?php esc_html_e('Server time will be used on submit', 'operations-organizer'); ?>)</td>
            </tr>
        </table>
        <div class="form-buttons">
            <?php submit_button( __( 'Stop Job & Save', 'operations-organizer' ), 'primary', 'stop_job_submit', false, array('id'=>'stop-job-submit', 'disabled' => $form_disabled) ); ?>
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

    const stopJobButton = $('#stop-job-submit');
    const employeeNumberInputStop = $('#employee_number_stop');
    const phpFormDisabledStop = <?php echo json_encode($form_disabled); ?>;
    const phaseIdGet = <?php echo json_encode($phase_id_get); ?>;
    const kpiFieldsContainer = $('#phase-kpi-fields-container-stop');

    function updateStopButtonState() {
        if (phpFormDisabledStop) {
            stopJobButton.prop('disabled', true);
            // console.log('Stop Job button disabled by PHP.');
        } else if (employeeNumberInputStop.val().trim() === '') {
            stopJobButton.prop('disabled', true);
            // console.log('Stop Job button disabled (no employee number entered).');
        } else {
            stopJobButton.prop('disabled', false);
            // console.log('Stop Job button enabled.');
        }
    }

    // Initial state check
    updateStopButtonState();

    // Update button state when employee number input changes
    employeeNumberInputStop.on('input', updateStopButtonState);

    // Function to load and display KPI fields for the current phase
    function loadPhaseKpiFields() {
        console.log('=== KPI FIELD LOADING DEBUG ===');
        console.log('phpFormDisabledStop:', phpFormDisabledStop);
        console.log('phaseIdGet:', phaseIdGet);
        
        if (phpFormDisabledStop || !phaseIdGet || phaseIdGet <= 0) {
            console.log('KPI loading stopped - form disabled or invalid phase ID');
            kpiFieldsContainer.html('<p><?php echo esc_js(__('KPI fields cannot be loaded. Ensure Phase ID is valid.', 'operations-organizer')); ?></p>');
            return;
        }

        console.log('Starting KPI field loading for phase:', phaseIdGet);
        kpiFieldsContainer.html('<p><?php echo esc_js(__('Loading KPI fields...', 'operations-organizer')); ?></p>');

        $.post(oo_data.ajax_url, {
            action: 'oo_get_phase_kpi_links',
            phase_id: phaseIdGet,
            _ajax_nonce: oo_data.nonce_get_phase_kpi_links 
        }, function(response) {
            console.log('=== AJAX RESPONSE ===');
            console.log('Full response:', response);
            console.log('Success:', response.success);
            console.log('Data:', response.data);
            console.log('Data length:', response.data ? response.data.length : 'no data');
            
            kpiFieldsContainer.empty();
            if (response.success && response.data && response.data.length > 0) {
                console.log('Processing', response.data.length, 'KPI fields');
                
                // Create a container for the KPI fields
                var kpiFieldsHtml = '<div class="oo-kpi-fields-wrapper">';
                
                $.each(response.data, function(index, kpi) {
                    console.log('Processing KPI:', kpi);
                    
                    if (!kpi.measure_key || !kpi.measure_name) {
                        console.log('Skipping KPI - missing key or name:', kpi);
                        return;
                    }

                    var inputFieldHtml = '';
                    var inputId = 'kpi_stop_' + kpi.measure_key;
                    var inputName = 'kpi_values[' + kpi.measure_key + ']';
                    var label = esc_html(kpi.measure_name) + (kpi.is_mandatory == 1 ? ' <span class="required" style="color: red;">*</span>' : '');

                    console.log('Creating field - ID:', inputId, 'Name:', inputName, 'Type:', kpi.unit_type);

                    switch (kpi.unit_type) {
                        case 'integer':
                        case 'decimal':
                            inputFieldHtml = '<input type="number" id="' + inputId + '" name="' + inputName + '" ' + 
                                           (kpi.unit_type === 'decimal' ? 'step="0.01"' : 'step="1"') + ' min="0" ' + 
                                           (kpi.is_mandatory == 1 ? 'required' : '') + ' class="regular-text" />';
                            break;
                        case 'text':
                            inputFieldHtml = '<textarea id="' + inputId + '" name="' + inputName + '" rows="2" class="widefat" ' + 
                                           (kpi.is_mandatory == 1 ? 'required' : '') + '></textarea>';
                            break;
                        case 'boolean':
                            inputFieldHtml = '<input type="checkbox" id="' + inputId + '" name="' + inputName + '" value="1" /> ' + 
                                           '<label for="' + inputId + '"><?php echo esc_js(__("Yes", "operations-organizer")); ?></label>';
                            break;
                        default:
                            inputFieldHtml = '<input type="text" id="' + inputId + '" name="' + inputName + '" ' + 
                                           (kpi.is_mandatory == 1 ? 'required' : '') + ' class="regular-text" />';
                    }

                    kpiFieldsHtml += '<div class="oo-kpi-field-row" style="margin-bottom: 15px;" data-kpi-key="' + kpi.measure_key + '">' +
                                    '<label for="' + inputId + '" style="display: block; font-weight: bold; margin-bottom: 5px;">' + label + '</label>' +
                                    inputFieldHtml +
                                    '</div>';
                });
                
                kpiFieldsHtml += '</div>';
                console.log('Generated KPI HTML:', kpiFieldsHtml);
                kpiFieldsContainer.html(kpiFieldsHtml);
                console.log('KPI fields inserted into container');
            } else {
                console.log('No KPI fields to display or error occurred');
                if (response.data && response.data.length === 0) {
                    console.log('Zero KPI links found for this phase');
                    kpiFieldsContainer.html('<p style="color: orange; font-weight: bold;"><?php echo esc_js(__('No specific KPIs are configured for this phase.', 'operations-organizer')); ?></p>');
                } else {
                    console.log('Error loading KPI fields:', response);
                    kpiFieldsContainer.html('<p style="color: red; font-weight: bold;"><?php echo esc_js(__('Could not load KPI fields for this phase.', 'operations-organizer')); ?></p>');
                    if(response.data && response.data.message) console.error("Error loading KPI fields:", response.data.message);
                }
            }
        }).fail(function(xhr, status, error) {
            console.error('=== AJAX FAILED ===');
            console.error('Status:', status);
            console.error('Error:', error);
            console.error('Response:', xhr.responseText);
            kpiFieldsContainer.html('<p style="color: red; font-weight: bold;"><?php echo esc_js(__('Failed to load KPI fields.', 'operations-organizer')); ?> Error: ' + esc_html(status) + '</p>');
        });
    }
    
    // Helper function for escaping HTML in JS
    function esc_html(str) {
        var p = document.createElement("p");
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    }

    // Load KPI fields on page load if phase ID is present
    if (phaseIdGet > 0 && !phpFormDisabledStop) {
        loadPhaseKpiFields();
    } else {
        kpiFieldsContainer.html('<p><?php echo esc_js(__('No phase ID provided or form is disabled.', 'operations-organizer')); ?></p>');
    }

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