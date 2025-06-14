// This is the full, corrected, and heavily-debugged main.js file.
// It is structured to prevent race conditions and scope errors.

jQuery(document).ready(function($) {
    console.log('[Stream Dashboard] Main JS file loaded and document is ready.');
    var oo_data = window.oo_data || {};
    var currentStreamSlug = oo_data.current_stream_tab_slug || '';
    var currentStreamId = oo_data.current_stream_id || 0;

    if (!currentStreamSlug) {
        console.error('[Stream Dashboard] CRITICAL: streamSlug is not available. Aborting script.');
        return;
    }
    console.log('[DEBUG] Initializing for stream slug:', currentStreamSlug, 'and stream ID:', currentStreamId);

    // Helper function for escaping HTML in JS (moved to top of ready block)
    function esc_html(str) {
        if (str === null || typeof str === 'undefined') return '';
        var p = document.createElement("p");
        p.appendChild(document.createTextNode(String(str)));
        return p.innerHTML;
    }

    // JS for Quick Phase Actions in this Stream tab
    $('.oo-stream-page .oo-start-link-btn, .oo-stream-page .oo-stop-link-btn').on('click', function(e) {
        console.log('[DEBUG] Quick Action button clicked.');
        e.preventDefault();
        var $button = $(this);
        var $row = $button.closest('.oo-phase-action-row');
        var jobNumber = $row.find('.oo-job-number-input').val();
        var phaseId = $button.data('phase-id');
        var isAdminUrl = oo_data.admin_url;
        var returnTabSlug = currentStreamSlug;

        if (!jobNumber) {
            alert("Please enter a Job Number first.");
            return;
        }

        var actionPage = $button.hasClass('oo-start-link-btn') ? 'oo_start_job' : 'oo_stop_job';
        var url = isAdminUrl + 'admin.php?page=' + actionPage + '&job_number=' + encodeURIComponent(jobNumber) + '&phase_id=' + encodeURIComponent(phaseId) + '&return_tab=' + returnTabSlug;
        
        window.location.href = url;
    });

    // Initialize Jobs Table for this Stream
    var streamJobsTable = $('#stream-jobs-table-' + currentStreamSlug).DataTable({
        ajax: {
            data: function(d) {
                d.stream_id = currentStreamId;
            },
        },
    });

    // ========================================================================
    // Phase Management Logic
    // ========================================================================

    // 1. Setup jQuery UI Sortable for Phase Reordering
    var $phasesTable = $('#phase-kpi-settings-content table.phases tbody');
    if ($phasesTable.length) {
        $phasesTable.sortable({
            handle: '.oo-phase-drag-handle',
            placeholder: 'oo-phase-sortable-placeholder',
            helper: function(e, ui) {
                ui.children().each(function() {
                    $(this).width($(this).width());
                });
                return ui;
            },
            update: function(event, ui) {
                var phaseOrder = $(this).sortable('toArray', { attribute: 'data-phase-id' });
                
                // Show a saving indicator
                var $spinner = $('<span class="spinner is-active" style="float:left;"></span>');
                $('#openAddOOPhaseModalBtn-stream-' + oo_data.current_stream_tab_slug).after($spinner);

                $.ajax({
                    url: oo_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'oo_update_phase_order_from_stream',
                        _ajax_nonce: oo_data.nonces.update_phase_order,
                        stream_id: currentStreamId,
                        order: phaseOrder
                    },
                    success: function(response) {
                        if (response.success) {
                            // Optionally, give user feedback
                            console.log('Phase order saved successfully.');
                        } else {
                            alert('Error saving phase order: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An unexpected error occurred while saving the phase order.');
                    },
                    complete: function() {
                        // Remove saving indicator
                        $spinner.remove();
                    }
                });
            }
        });
    }


    // 2. Add New Phase
    // Show modal
    $(document).on('click', '[id^="openAddOOPhaseModalBtn-stream-"]', function() {
        $('#addOOPhaseModal').show();
    });

    // Handle form submission
    $('#add-phase-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        $form.find('.spinner').addClass('is-active');

        var formData = {
            action: 'oo_add_phase_from_stream',
            oo_add_phase_nonce: oo_data.nonces.add_phase,
            stream_type_id: currentStreamId,
            phase_name: $('#add_phase_name').val(),
            phase_description: $('#add_phase_description').val(),
            includes_kpi: $('#add_includes_kpi').is(':checked') ? 1 : 0,
        };

        $.post(oo_data.ajax_url, formData, function(response) {
            if (response.success) {
                alert('Phase added successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.data.message || 'Could not add phase.'));
                $form.find('.spinner').removeClass('is-active');
            }
        }).fail(function() {
            alert('An unknown error occurred.');
            $form.find('.spinner').removeClass('is-active');
        });
    });

    // 3. Edit Phase
    // Show modal and populate with data
    $(document).on('click', '.oo-edit-phase-button-stream', function() {
        var phaseId = $(this).data('phase-id');
        
        // Show spinner
        var $spinner = $('<span class="spinner is-active" style="margin-left: 5px;"></span>');
        $(this).after($spinner);

        $.post(oo_data.ajax_url, {
            action: 'oo_get_phase',
            _ajax_nonce_get_phase: oo_data.nonces.get_phase,
            phase_id: phaseId
        }, function(response) {
            if (response.success) {
                $('#edit_phase_id').val(response.data.phase_id);
                $('#edit_phase_name').val(response.data.phase_name);
                $('#edit_phase_description').val(response.data.phase_description);
                $('#edit_includes_kpi').prop('checked', parseInt(response.data.includes_kpi) === 1);
                $('#editOOPhaseModal').show();
            } else {
                alert('Error: ' + (response.data.message || 'Could not fetch phase details.'));
            }
        }).fail(function() {
            alert('An unknown error occurred while fetching phase details.');
        }).always(function() {
            $spinner.remove();
        });
    });

    // Handle edit form submission
    $('#edit-phase-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        $form.find('.spinner').addClass('is-active');

        var formData = {
            action: 'oo_update_phase_from_stream',
            oo_edit_phase_nonce: oo_data.nonces.update_phase,
            edit_phase_id: $('#edit_phase_id').val(),
            edit_stream_type_id: currentStreamId,
            edit_phase_name: $('#edit_phase_name').val(),
            edit_phase_description: $('#edit_phase_description').val(),
            edit_includes_kpi: $('#edit_includes_kpi').is(':checked') ? 1 : 0,
        };

        $.post(oo_data.ajax_url, formData, function(response) {
            if (response.success) {
                alert('Phase updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.data.message || 'Could not update phase.'));
                $form.find('.spinner').removeClass('is-active');
            }
        }).fail(function() {
            alert('An unknown error occurred.');
            $form.find('.spinner').removeClass('is-active');
        });
    });


    // 4. Delete Phase
    $(document).on('click', '.oo-delete-phase-button-stream', function(e) {
        e.preventDefault();
        
        if (!confirm(oo_data.i18n.confirmDeletePhase)) {
            return;
        }

        var phaseId = $(this).data('phase-id');
        var $row = $(this).closest('tr');
        var $spinner = $('<span class="spinner is-active"></span>');
        $row.find('td.actions').append($spinner);

        $.post(oo_data.ajax_url, {
            action: 'oo_delete_phase_from_stream',
            _ajax_nonce: oo_data.nonces.delete_phase,
            phase_id: phaseId
        }, function(response) {
            if (response.success) {
                if(response.data.confirmation_needed) {
                    if(confirm(response.data.message)) {
                        // User confirmed, send delete request again with force flag
                        $.post(oo_data.ajax_url, {
                            action: 'oo_delete_phase_from_stream',
                            _ajax_nonce: oo_data.nonces.delete_phase,
                            phase_id: phaseId,
                            force_delete_logs: true
                        }, function(force_response){
                            if(force_response.success) {
                                alert('Phase and associated logs deleted successfully.');
                                location.reload();
                            } else {
                                alert('Error: ' + (force_response.data.message || 'Could not delete phase.'));
                            }
                        }).fail(function(){
                             alert('An unknown error occurred during forced deletion.');
                        }).always(function(){
                            $spinner.remove();
                        });
                    } else {
                       $spinner.remove(); 
                    }
                } else {
                    alert('Phase deleted successfully.');
                    location.reload();
                }
            } else {
                alert('Error: ' + (response.data.message || 'Could not delete phase.'));
                $spinner.remove();
            }
        }).fail(function() {
            alert('An unknown error occurred during deletion.');
            $spinner.remove();
        });
    });

    // 5. Toggle Phase Status
    $(document).on('click', '.oo-toggle-status-phase-button-stream', function() {
        var $button = $(this);
        var phaseId = $button.data('phase-id');
        var newStatus = $button.data('new-status');
        var nonce = $button.data('nonce'); // Nonce is on the button for this action

        $button.prop('disabled', true);

        $.post(oo_data.ajax_url, {
            action: 'oo_toggle_phase_status_from_stream',
            _ajax_nonce: nonce, // Use the dynamic nonce from the button
            phase_id: phaseId,
            is_active: newStatus
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.data.message || 'Could not update status.'));
                $button.prop('disabled', false);
            }
        }).fail(function() {
            alert('An unknown error occurred.');
            $button.prop('disabled', false);
        });
    });


    // Generic Modal Close Logic
    $('.oo-modal .oo-modal-close, .oo-modal .oo-modal-cancel').on('click', function() {
        $(this).closest('.oo-modal').hide();
    });

    console.log('[Stream Dashboard] All event handlers attached.');
}); 