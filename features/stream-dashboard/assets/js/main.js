jQuery(document).ready(function($) {
    console.log('[Stream Dashboard] Main JS file loaded and document is ready.');

    var streamSlug = oo_data.current_stream_tab_slug;
    if (!streamSlug) {
        console.error('[Stream Dashboard] CRITICAL: streamSlug is not available in oo_data. All event handlers will fail.');
        return;
    }
    console.log('[Stream Dashboard] Initializing for stream slug:', streamSlug);

    // Helper function for escaping HTML in JS (moved to top of ready block)
    function esc_html(str) {
        if (str === null || typeof str === 'undefined') return '';
        var p = document.createElement("p");
        p.appendChild(document.createTextNode(String(str)));
        return p.innerHTML;
    }

    // --- QUICK PHASE ACTIONS TAB ---
    if ($('#phase-log-actions-content').length) {
        console.log('[Stream Dashboard] Attaching handlers for Phase Log Actions tab.');
        $('.oo-stream-page .oo-start-link-btn, .oo-stream-page .oo-stop-link-btn').on('click', function(e) {
            console.log('[Stream Dashboard] Quick Start/Stop button clicked.');
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest('.oo-phase-action-row');
            var jobNumber = $row.find('.oo-job-number-input').val();
            var phaseId = $button.data('phase-id');
            var isAdminUrl = oo_data.admin_url;
            var returnTabSlug = oo_data.current_stream_tab_slug; 

            if (!jobNumber) {
                alert('Please enter a Job Number first.');
                return;
            }
            var actionPage = $button.hasClass('oo-start-link-btn') ? 'oo_start_job' : 'oo_stop_job';
            var url = isAdminUrl + 'admin.php?page=' + actionPage + '&job_number=' + encodeURIComponent(jobNumber) + '&phase_id=' + encodeURIComponent(phaseId) + '&return_tab=' + returnTabSlug;
            window.location.href = url;
        });
    }

    // --- PHASE DASHBOARD TAB ---
    if ($('#phase-dashboard-content').length) {
        console.log('[Stream Dashboard] Initializing logic for Phase Dashboard tab.');
        // All logic for the data tables and modals on this tab goes here
    }

    // --- PHASE & KPI SETTINGS TAB ---
    if ($('#phase-kpi-settings-content').length) {
        console.log('[Stream Dashboard] Attaching handlers for Phase & KPI Settings tab.');

        var addPhaseModal_Stream = $('#addOOPhaseModal-stream-' + streamSlug);
        var editPhaseModal_Stream = $('#editOOPhaseModal-stream-' + streamSlug);
        var kpiMeasuresListContainer_Stream = $('#kpi-measures-list-stream-' + streamSlug);
        
        // Open "Add New Phase" modal
        $('#openAddOOPhaseModalBtn-stream-' + streamSlug).on('click', function() {
            console.log('[Stream Dashboard] "Add New Phase" button clicked.');
            addPhaseModal_Stream.show();
        });

        // Open "Edit Phase" modal
        $('#phase-kpi-settings-content').on('click', '.oo-edit-phase-button-stream', function() { 
            console.log('[Stream Dashboard] "Edit Phase" button clicked.');
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
        if ($('#oo-edit-phase-form-stream-' + streamSlug).length) {
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
        }

        // AJAX Delete Phase from table on Stream Page
        if ($('#phase-kpi-settings-content').length) {
            $('#phase-kpi-settings-content').on('click', '.oo-delete-phase-button-stream', function() { 
                // ... (confirmation logic) ...
                var ajaxData = {
                    action: 'oo_delete_phase_from_stream',
                    phase_id: phaseId,
                    _ajax_nonce: oo_data.nonce_delete_phase_ajax,
                    // ... existing code ...
                };
            });
        }

        // Handle Toggle Status Button Click on Stream Page table (AJAX)
        if ($('#phase-kpi-settings-content').length) {
            $('#phase-kpi-settings-content').on('click', '.oo-toggle-status-phase-button-stream', function() { 
                // ... (confirmation logic) ...
                $.post(oo_data.ajax_url, {
                    action: 'oo_toggle_phase_status_from_stream', 
                    phase_id: phaseId,
                    is_active: newStatus,
                    // ... existing code ...
                });
            });
        }

        // Function to load and display linked KPI measures and populate Add KPI dropdown (Stream Page Edit Modal)
        if (loadAndDisplayPhaseKpis_StreamPage.length) {
            loadAndDisplayPhaseKpis_StreamPage(phaseId, streamSlugContext);
        }

        // Add Selected KPI to Phase (Stream Page)
        if ($('#btn-add-kpi-to-phase-stream-' + streamSlug).length) {
            $('#btn-add-kpi-to-phase-stream-' + streamSlug).on('click', function() {
                // ...
                $.post(oo_data.ajax_url, {
                    action: 'oo_add_phase_kpi_link_from_stream',
                    phase_id: phaseId,
                    kpi_measure_id: kpiMeasureId,
                    // ... existing code ...
                });
            });
        }

        // Remove KPI Link from Phase (Stream Page - Event Delegation)
        if (editPhaseModal_Stream.length) {
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
        }

        // Update KPI Link (Mandatory/Order) on change (Stream Page - Event Delegation)
        if (editPhaseModal_Stream.length) {
            editPhaseModal_Stream.on('change', '.is-mandatory-kpi-stream, .display-order-kpi-stream', function() {
                // ...
                $.post(oo_data.ajax_url, {
                    action: 'oo_update_phase_kpi_link_from_stream',
                    link_id: linkId,
                    is_mandatory: isMandatory,
                    // ... existing code ...
                });
            });
        }

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

        // Open Add KPI Measure Modal
        if ($('#openAddKpiMeasureModalBtn-stream-' + streamSlug).length) {
            $(document).on('click', '#openAddKpiMeasureModalBtn-stream-' + streamSlug, function() {
                console.log('[Stream Dashboard] Add New KPI Measure button clicked.');
                // ... (rest of the function)
            });
        }

        // Handle Edit KPI Measure Button Click
        $(document).on('click', '#kpi-measures-list-stream-' + streamSlug + ' .oo-edit-kpi-measure-stream', function() {
            console.log('[Stream Dashboard] Edit KPI Measure button clicked.');
            var kpiMeasureId = $(this).data('kpi-measure-id');
            var editKpiMeasureModal_Stream = $('#editKpiMeasureModal-stream-' + streamSlug);
            var $phaseChecklistContainer = editKpiMeasureModal_Stream.find('#edit-kpi-link-to-phases-list-' + streamSlug);
            
            editKpiMeasureModal_Stream.find('#editKpiMeasureNameDisplay-' + streamSlug).text('Loading...');
            $phaseChecklistContainer.html('<p>Loading phases...</p>');

            $.when(
                $.post(oo_data.ajax_url, {
                    action: 'oo_get_kpi_measure_details',
                    kpi_measure_id: kpiMeasureId,
                    _ajax_nonce: oo_data.nonce_get_kpi_measure_details
                }),
                $.post(oo_data.ajax_url, {
                    action: 'oo_get_phases_for_stream',
                    stream_id: oo_data.current_stream_id,
                    _ajax_nonce: oo_data.nonce_get_phases
                }),
                $.post(oo_data.ajax_url, {
                    action: 'oo_get_phase_links_for_kpi_in_stream',
                    kpi_measure_id: kpiMeasureId,
                    stream_id: oo_data.current_stream_id,
                    _ajax_nonce: oo_data.nonce_get_phase_kpi_links
                })
            ).done(function(kpiDetailsResponse, phasesResponse, existingLinksResponse) {
                console.log('[EDIT KPI MODAL] AJAX Success:', { kpiDetails: kpiDetailsResponse[0], phases: phasesResponse[0], links: existingLinksResponse[0] });

                if (kpiDetailsResponse[0].success && phasesResponse[0].success && existingLinksResponse[0].success) {
                    var kpi = kpiDetailsResponse[0].data.kpi_measure;
                    var phasesInStream = phasesResponse[0].data.phases;
                    var linkedIdsData = existingLinksResponse[0].data.linked_phase_ids;
                    var existingPhaseIdsLinked = (Array.isArray(linkedIdsData)) ? linkedIdsData.map(String) : [];
                    
                    console.log('[EDIT KPI MODAL] All Phases in Stream:', phasesInStream);
                    console.log('[EDIT KPI MODAL] Already Linked Phase IDs:', existingPhaseIdsLinked);

                    editKpiMeasureModal_Stream.find('#edit_kpi_measure_id-stream-' + streamSlug).val(kpi.kpi_measure_id);
                    editKpiMeasureModal_Stream.find('#editKpiMeasureNameDisplay-' + streamSlug).text(esc_html(kpi.measure_name));
                    editKpiMeasureModal_Stream.find('#edit_kpi_measure_name-stream-' + streamSlug).val(kpi.measure_name);
                    editKpiMeasureModal_Stream.find('#edit_kpi_measure_key-stream-' + streamSlug).val(kpi.measure_key);
                    editKpiMeasureModal_Stream.find('#edit_kpi_unit_type-stream-' + streamSlug).val(kpi.unit_type);
                    editKpiMeasureModal_Stream.find('#edit_kpi_is_active-stream-' + streamSlug).prop('checked', parseInt(kpi.is_active) === 1);

                    $phaseChecklistContainer.empty();
                    if (phasesInStream && phasesInStream.length > 0) {
                        $.each(phasesInStream, function(index, phase) {
                            var isChecked = existingPhaseIdsLinked.indexOf(phase.phase_id.toString()) !== -1;
                            console.log('[EDIT KPI MODAL] Checking phase "' + phase.phase_name + '" (ID: ' + phase.phase_id + '). Is linked? ' + isChecked);
                            var checkbox = '<label style="display: block;"><input type="checkbox" name="link_to_phases[]" value="' + phase.phase_id + '" ' + (isChecked ? 'checked' : '') + '> ' + esc_html(phase.phase_name) + '</label>';
                            $phaseChecklistContainer.append(checkbox);
                        });
                    } else {
                        $phaseChecklistContainer.html('<p>No active phases found in this stream to link to.</p>');
                    }
                    editKpiMeasureModal_Stream.show();
                } else {
                    var errorMsg = 'Could not load KPI Measure data or phase list.';
                    if (kpiDetailsResponse[0] && !kpiDetailsResponse[0].success) errorMsg = kpiDetailsResponse[0].data.message;
                    else if (phasesResponse[0] && !phasesResponse[0].success) errorMsg = phasesResponse[0].data.message;
                    else if (existingLinksResponse[0] && !existingLinksResponse[0].success) errorMsg = existingLinksResponse[0].data.message;
                    showNotice('error', errorMsg);
                }
            }).fail(function() {
                showNotice('error', 'Request to load KPI Measure data or phase list failed.');
                $phaseChecklistContainer.html('<p>Error loading phases.</p>');
            });
        });

        // ... and so on for ALL other button/form handlers in this file ...
    }
}); 