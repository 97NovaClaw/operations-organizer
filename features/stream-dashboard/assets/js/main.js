jQuery(document).ready(function($) {
    // Helper function for escaping HTML in JS (moved to top of ready block)
    function esc_html(str) {
        if (str === null || typeof str === 'undefined') return '';
        var p = document.createElement("p");
        p.appendChild(document.createTextNode(String(str)));
        return p.innerHTML;
    }

    // JS for Quick Phase Actions in this Stream tab
    $('.oo-stream-page .oo-start-link-btn, .oo-stream-page .oo-stop-link-btn').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $row = $button.closest('.oo-phase-action-row');
        var jobNumber = $row.find('.oo-job-number-input').val();
        var phaseId = $button.data('phase-id');
        var isAdminUrl = oo_data.admin_url;
        var returnTabSlug = oo_data.current_stream_tab_slug; 

        if (!jobNumber) {
            alert(oo_data.text_enter_job_number || "Please enter a Job Number first.");
            return;
        }

        var actionPage = $button.hasClass('oo-start-link-btn') ? 'oo_start_job' : 'oo_stop_job';
        var url = isAdminUrl + 'admin.php?page=' + actionPage + '&job_number=' + encodeURIComponent(jobNumber) + '&phase_id=' + encodeURIComponent(phaseId) + '&return_tab=' + returnTabSlug;
        
        window.location.href = url;
    });
	
	// ... all the other JavaScript logic from the old template file ...

    // Handle Add Phase Form Submission for Stream Page
    $('#oo-add-phase-form-stream-' + streamSlug).on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('#submit_add_phase-stream-' + streamSlug);
        $submitButton.prop('disabled', true).val('<?php echo esc_js(__("Adding...", "operations-organizer")); ?>');
        var formData = $form.serialize() + '&action=oo_add_phase_from_stream&return_to_stream=' + streamSlug + '&return_sub_tab=phase_kpi_settings'; 

        $.post(oo_data.ajax_url, formData, function(response) {
            // ... existing code ...
        });
    });

    // Handle Edit Phase Button Click on Stream Page table
    $('#phase-kpi-settings-content').on('click', '.oo-edit-phase-button-stream', function() { 
        var phaseId = $(this).data('phase-id');
        var phaseName = $(this).closest('tr').find('td:first-child .oo-edit-phase-button-stream').text().trim() || $(this).data('phase-name');
        
        editPhaseModal_Stream.data('current-phase-id', phaseId); 
        editPhaseModal_Stream.find('#editModalPhaseNameDisplay-' + streamSlug).text(phaseName);

        $.post(oo_data.ajax_url, {
            action: 'oo_get_phase_for_stream_modal',
            phase_id: phaseId,
            _ajax_nonce_get_phase: oo_data.nonce_edit_phase 
        }, function(response) {
            // ... existing code ...
        });
    });

    // Handle Edit Phase Form Submission for Stream Page
    $('#oo-edit-phase-form-stream-' + streamSlug).on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('#submit_edit_phase-stream-' + streamSlug);
        $submitButton.prop('disabled', true).val('<?php echo esc_js(__("Saving...", "operations-organizer")); ?>');
        var formData = $form.serialize() + '&action=oo_update_phase_from_stream'; 
         formData += '&return_to_stream=' + streamSlug; 
         formData += '&return_sub_tab=phase_kpi_settings';

        $.post(oo_data.ajax_url, formData, function(response) {
            // ... existing code ...
        });
    });

    // AJAX Delete Phase from table on Stream Page
    $('#phase-kpi-settings-content').on('click', '.oo-delete-phase-button-stream', function() { 
        // ... (confirmation logic) ...
        var ajaxData = {
            action: 'oo_delete_phase_from_stream',
            phase_id: phaseId,
            _ajax_nonce: oo_data.nonce_delete_phase_ajax,
            // ... existing code ...
        };
    });

    // Handle Toggle Status Button Click on Stream Page table (AJAX)
    $('#phase-kpi-settings-content').on('click', '.oo-toggle-status-phase-button-stream', function() { 
        // ... (confirmation logic) ...
        $.post(oo_data.ajax_url, {
            action: 'oo_toggle_phase_status_from_stream', 
            phase_id: phaseId,
            is_active: newStatus,
            // ... existing code ...
        });
    });

    // Function to load and display linked KPI measures and populate Add KPI dropdown (Stream Page Edit Modal)
    function loadAndDisplayPhaseKpis_StreamPage(phaseId, streamSlugContext) {
        // ...
        $.when(
            $.post(oo_data.ajax_url, { 
                action: 'oo_get_all_kpis_for_stream_linking', 
                _ajax_nonce: oo_data.nonce_get_kpi_measures,
                is_active: 1, 
                number: -1 
            }),
            $.post(oo_data.ajax_url, { 
                action: 'oo_get_phase_kpi_links_for_stream', 
                phase_id: phaseId, 
                _ajax_nonce: oo_data.nonce_get_phase_kpi_links 
            })
        ).done(function(allKpisResponse, linkedKpisResponse) {
            if (allKpisResponse[0].success && linkedKpisResponse[0].success) {
                var kpi = allKpisResponse[0].data.kpi_measure;
                var phasesInStream = linkedKpisResponse[0].data.phases;

                // FIX: Defensively check if linked_phase_ids is an array before using .map()
                var linkedIdsData = linkedKpisResponse[0].data.linked_phase_ids;
                var existingPhaseIdsLinked = (Array.isArray(linkedIdsData)) ? linkedIdsData.map(String) : [];

                editKpiMeasureModal_Stream.find('#edit_kpi_measure_id-stream-' + streamSlug).val(kpi.kpi_measure_id);
                editKpiMeasureModal_Stream.find('#editKpiMeasureNameDisplay-' + streamSlug).text(esc_html(kpi.measure_name));
                // ... (rest of the modal population logic) ...
                
                // Populate phase checklist
                $phaseChecklistContainer.empty();
                if (phasesInStream && phasesInStream.length > 0) {
                    $.each(phasesInStream, function(index, phase) {
                        var isChecked = existingPhaseIdsLinked.indexOf(phase.phase_id.toString()) !== -1;
                        var checkbox = '<label style="display: block;"><input type="checkbox" name="link_to_phases[]" value="' + phase.phase_id + '" ' + (isChecked ? 'checked' : '') + '> ' + esc_html(phase.phase_name) + '</label>';
                        $phaseChecklistContainer.append(checkbox);
                    });
                } else {
                    $phaseChecklistContainer.html('<p><?php echo esc_js(__("No active phases found in this stream to link to.", "operations-organizer")); ?></p>');
                }
                editKpiMeasureModal_Stream.show();
            } else {
                // ... existing code ...
            }
        });
    }

    // Add Selected KPI to Phase (Stream Page)
    $('#btn-add-kpi-to-phase-stream-' + streamSlug).on('click', function() {
        // ...
        $.post(oo_data.ajax_url, {
            action: 'oo_add_phase_kpi_link_from_stream',
            phase_id: phaseId,
            kpi_measure_id: kpiMeasureId,
            // ... existing code ...
        });
    });

    // Remove KPI Link from Phase (Stream Page - Event Delegation)
    editPhaseModal_Stream.on('click', '.oo-remove-kpi-link-stream', function() {
        // ...
        $.post(oo_data.ajax_url, {
            action: 'oo_delete_phase_kpi_link_from_stream',
            link_id: linkId,
            _ajax_nonce: oo_data.nonce_manage_phase_kpi_links 
        }, function(response) {
            // ... existing code ...
        });
    });

    // Update KPI Link (Mandatory/Order) on change (Stream Page - Event Delegation)
    editPhaseModal_Stream.on('change', '.is-mandatory-kpi-stream, .display-order-kpi-stream', function() {
        // ...
        $.post(oo_data.ajax_url, {
            action: 'oo_update_phase_kpi_link_from_stream',
            link_id: linkId,
            is_mandatory: isMandatory,
            // ... existing code ...
        });
    });

    // --- Start of Job Logs Table JS ---
    if ($('#stream-job-logs-section').length > 0) {
        
        var contentDashboardTable = $('#content-dashboard-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: oo_data.ajax_url,
                type: 'POST',
                data: function(d) {
                    d.action = 'oo_get_stream_job_logs'; // Use the new scoped action
                    d.nonce = oo_data.nonce_dashboard; // Can reuse the dashboard nonce
                    d.filter_stream_id = oo_data.current_stream_id; // Pass the stream ID from localized data
                    
                    // Add other filters from the UI
                    d.filter_employee_id = $('#content_filter_employee_id').val();
                    d.filter_job_number = $('#content_filter_job_number').val();
                    d.filter_phase_id = $('#content_filter_phase_id').val();
                    d.filter_date_from = $('#content_filter_date_from').val();
                    d.filter_date_to = $('#content_filter_date_to').val();
                    d.filter_status = $('#content_filter_status').val();
                    d.selected_columns_config = window.contentSelectedKpiObjects;
                }
            },
            // ... existing code ...
        });
    }

    // --- Phase Management for this Stream Page (NEW/MODIFIED) ---
    var streamSlug = oo_data.current_stream_tab_slug;
    var addPhaseModal_Stream = $('#addOOPhaseModal-stream-' + streamSlug);
    var editPhaseModal_Stream = $('#editOOPhaseModal-stream-' + streamSlug);

    // Open Add Phase Modal for Stream
    // ... existing code ...
}); 