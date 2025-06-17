jQuery(document).ready(function($) {

    // Check if we are on the stream dashboard page and have the necessary data
    if (typeof oo_data === 'undefined' || !oo_data.current_stream_id) {
        console.log('[EXTREME_DEBUG] oo_data is undefined or missing current_stream_id. Exiting.');
        return; // Exit if data is missing.
    }
    
    console.log('[EXTREME_DEBUG] ========== STREAM DASHBOARD JS LOADED ==========');
    console.log('[EXTREME_DEBUG] oo_data object:', oo_data);
    console.log('[EXTREME_DEBUG] Available nonces:', {
        nonce_add_phase: oo_data.nonce_add_phase,
        nonce_edit_phase: oo_data.nonce_edit_phase,
        nonce_delete_phase_ajax: oo_data.nonce_delete_phase_ajax,
        nonce_update_phase_order: oo_data.nonce_update_phase_order
    });

    var streamId = oo_data.current_stream_id;
    var streamSlug = oo_data.current_stream_tab_slug;

    // ========================================================================
    // Phase Management Logic (Corrected)
    // ========================================================================

    // 1. Setup jQuery UI Sortable for Phase Reordering
    var $phasesTableBody = $('#phase-kpi-settings-content table.phases tbody');
    if ($phasesTableBody.length) {
        $phasesTableBody.sortable({
            handle: '.oo-phase-drag-handle',
            placeholder: 'oo-phase-sortable-placeholder',
            helper: function(e, ui) {
                ui.children().each(function() { $(this).width($(this).width()); });
                return ui;
            },
            update: function(event, ui) {
                var phaseOrder = $(this).sortable('toArray', { attribute: 'data-phase-id' });
                var $spinner = $('<span class="spinner is-active" style="float:left;"></span>');
                ui.item.closest('table').parent().find('.page-title-action').first().after($spinner);

                $.ajax({
                    url: oo_data.ajax_url,
                    type: 'POST',
                                    data: {
                    action: 'oo_update_phase_order_from_stream',
                    _ajax_nonce: oo_data.nonce_update_phase_order,
                    stream_id: streamId,
                    order: phaseOrder
                },
                    success: function(response) {
                        if (!response.success) {
                            alert('Error saving phase order: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An unexpected error occurred while saving the phase order.');
                    },
                    complete: function() {
                        $spinner.remove();
                    }
                });
            }
        });
    }

    // 2. Add New Phase
    $(document).on('click', '#openAddOOPhaseModalBtn-stream-' + streamSlug, function() {
        $('#addOOPhaseModal').show();
    });

    $('#add-phase-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        $form.find('.spinner').addClass('is-active');

        var formData = {
            action: 'oo_add_phase_from_stream',
            _ajax_nonce: oo_data.nonce_add_phase,
            stream_type_id: streamId,
            phase_name: $('#add_phase_name').val(),
            phase_description: $('#add_phase_description').val(),
            includes_kpi: $('#add_includes_kpi').is(':checked') ? 1 : 0,
        };

        console.log('[EXTREME_DEBUG] ========== SENDING ADD PHASE AJAX ==========');
        console.log('[EXTREME_DEBUG] AJAX URL:', oo_data.ajax_url);
        console.log('[EXTREME_DEBUG] Form data being sent:', formData);
        console.log('[EXTREME_DEBUG] Nonce being sent:', formData._ajax_nonce);
        console.log('[EXTREME_DEBUG] Expected action:', formData.action);
        
        $.post(oo_data.ajax_url, formData, function(response) {
            console.log('[EXTREME_DEBUG] AJAX Response received:', response);
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.data.message || 'Could not add phase.'));
            }
        }).fail(function(xhr, status, error) {
            console.log('[EXTREME_DEBUG] ADD PHASE AJAX FAILED!');
            console.log('[EXTREME_DEBUG] XHR object:', xhr);
            console.log('[EXTREME_DEBUG] Status:', status);
            console.log('[EXTREME_DEBUG] Error:', error);
            console.log('[EXTREME_DEBUG] Response text:', xhr.responseText);
            alert('An unknown error occurred. Check console for details.');
        }).always(function(){
            $form.find('.spinner').removeClass('is-active');
        });
    });

    // 3. Edit Phase
    $(document).on('click', '.oo-edit-phase-button-stream', function() {
        var phaseId = $(this).data('phase-id');
        var $spinner = $('<span class="spinner is-active" style="margin-left: 5px;"></span>');
        $(this).after($spinner);

        var ajaxData = {
            action: 'oo_get_phase',
            _ajax_nonce: oo_data.nonce_edit_phase,
            phase_id: phaseId
        };

        console.log('[DEBUG] Sending "Get Phase" AJAX with data:', ajaxData);
        $.post(oo_data.ajax_url, ajaxData, function(response) {
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

    $('#edit-phase-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        $form.find('.spinner').addClass('is-active');

        var formData = {
            action: 'oo_update_phase_from_stream',
            _ajax_nonce: oo_data.nonce_edit_phase,
            edit_phase_id: $('#edit_phase_id').val(),
            edit_stream_type_id: streamId,
            edit_phase_name: $('#edit_phase_name').val(),
            edit_phase_description: $('#edit_phase_description').val(),
            edit_includes_kpi: $('#edit_includes_kpi').is(':checked') ? 1 : 0,
        };

        console.log('[DEBUG] Sending "Update Phase" AJAX with data:', formData);
        $.post(oo_data.ajax_url, formData, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.data.message || 'Could not update phase.'));
            }
        }).fail(function() {
            alert('An unknown error occurred.');
        }).always(function(){
            $form.find('.spinner').removeClass('is-active');
        });
    });

    // 4. Delete Phase
    $(document).on('click', '.oo-delete-phase-button-stream', function(e) {
        e.preventDefault();
        var confirmMessage = oo_data.i18n.confirmDeletePhase || 'Are you sure you want to delete this phase?';
        if (!confirm(confirmMessage)) return;

            var phaseId = $(this).data('phase-id');
        var $spinner = $('<span class="spinner is-active"></span>');
        $(this).parent().append($spinner);
            
        function performDelete(force) {
                    $.post(oo_data.ajax_url, {
            action: 'oo_delete_phase_from_stream',
            _ajax_nonce: oo_data.nonce_delete_phase_ajax,
            phase_id: phaseId,
            force_delete_logs: force
        }, function(response) {
                if (response.success) {
                    if (response.data.confirmation_needed) {
                        if (confirm(response.data.message)) {
                            performDelete(true);
                        } else {
                             $spinner.remove();
                        }
                    } else {
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
        }
        performDelete(false);
    });

    // 5. Toggle Phase Status
    $(document).on('click', '.oo-toggle-status-phase-button-stream', function() {
        var $button = $(this);
        var phaseId = $button.data('phase-id');
        var newStatus = $button.data('new-status');
        var nonce = $button.data('nonce');
        $button.prop('disabled', true);

        $.post(oo_data.ajax_url, {
            action: 'oo_toggle_phase_status_from_stream',
            _ajax_nonce: nonce,
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

    // ========================================================================
    // KPI Measure Management Logic (Fixed with correct nonces)
    // ========================================================================

    // Open "Add KPI" modal
    $(document).on('click', '#openAddKpiMeasureModalBtn-stream-' + streamSlug, function() {
        var $modal = $('#addKpiMeasureModal-stream-' + streamSlug);
        var $form = $modal.find('form');
        $form[0].reset(); // Reset all form fields
        // Clear the auto-generated measure_key field specifically
        $('#add_kpi_measure_key-stream-' + streamSlug).val('');
        // Uncheck all phase checkboxes
        $modal.find('input[name="link_to_phases[]"]').prop('checked', false);
        $modal.show();
    });

    // Auto-generate measure_key from measure_name in Add KPI modal
    $(document).on('input', '#add_kpi_measure_name-stream-' + streamSlug, function() {
        var measureName = $(this).val();
        var measureKey = measureName.toLowerCase()
            .replace(/[^a-z0-9\s]/g, '') // Remove special characters
            .replace(/\s+/g, '_') // Replace spaces with underscores
            .replace(/_+/g, '_') // Replace multiple underscores with single
            .replace(/^_|_$/g, ''); // Remove leading/trailing underscores
        $('#add_kpi_measure_key-stream-' + streamSlug).val(measureKey);
    });

    // Handle "Add KPI" form submission
    $(document).on('submit', '#oo-add-kpi-measure-form-stream-' + streamSlug, function(e) {
        e.preventDefault();
        var $form = $(this);
        var $modal = $('#addKpiMeasureModal-stream-' + streamSlug);
        var formData = $form.serializeArray();
        formData.push({name: 'action', value: 'oo_add_kpi_measure'});
        formData.push({name: '_ajax_nonce', value: oo_data.nonce_add_kpi_measure});
        
        $.post(oo_data.ajax_url, $.param(formData), function(response) {
            if (response.success) {
                alert('KPI measure added successfully!');
                $form[0].reset(); // Reset the form
                $modal.hide(); // Close the modal
                location.reload(); // Refresh to show new KPI
            } else {
                alert('Error: ' + (response.data.message || 'Could not add KPI Measure.'));
            }
        }).fail(function() {
            alert('Error: Failed to add KPI measure');
        });
    });

    // Open "Edit KPI" modal and populate it
    $(document).on('click', '.oo-edit-kpi-measure-stream', function() {
        var kpiMeasureId = $(this).data('kpi-measure-id');
        var $modal = $('#editKpiMeasureModal-stream-' + streamSlug);

        $.post(oo_data.ajax_url, {
            action: 'oo_get_kpi_measure_details',
            _ajax_nonce: oo_data.nonce_get_kpi_measure_details,
            kpi_measure_id: kpiMeasureId
        }, function(response) {
            if (response.success) {
                var kpi = response.data.kpi_measure;
                $modal.find('[name="kpi_measure_id"]').val(kpi.kpi_measure_id);
                $modal.find('#editKpiMeasureNameDisplay-' + streamSlug).text(kpi.measure_name);
                $modal.find('[name="measure_name"]').val(kpi.measure_name);
                $modal.find('[name="measure_key"]').val(kpi.measure_key);
                $modal.find('[name="unit_type"]').val(kpi.unit_type);
                $modal.find('[name="is_active"]').prop('checked', parseInt(kpi.is_active) === 1);
                
                // Load phase links for this KPI
                loadPhaseLinksForKpi(kpiMeasureId, streamSlug);
                
                $modal.show();
            } else {
                alert('Error fetching KPI details: ' + response.data.message);
            }
        });
    });

    // Function to load phase links for KPI in edit modal
    function loadPhaseLinksForKpi(kpiMeasureId, streamSlug) {
        var $phaseList = $('#edit-kpi-link-to-phases-list-' + streamSlug);
        $phaseList.html('<p>Loading phases...</p>');
        
        $.post(oo_data.ajax_url, {
            action: 'oo_get_phase_links_for_kpi_in_stream',
            _ajax_nonce: oo_data.nonce_get_phase_kpi_links,
            kpi_measure_id: kpiMeasureId,
            stream_id: oo_data.current_stream_id
        }, function(response) {
            if (response.success) {
                var html = '';
                var linkedPhaseIds = response.data.linked_phase_ids || [];
                
                if (response.data.phases && response.data.phases.length > 0) {
                    response.data.phases.forEach(function(phase) {
                        var isChecked = linkedPhaseIds.indexOf(parseInt(phase.phase_id)) !== -1 ? 'checked' : '';
                        html += '<label style="display: block;"><input type="checkbox" name="link_to_phases[]" value="' + phase.phase_id + '" ' + isChecked + '> ' + phase.phase_name + '</label>';
                    });
                } else {
                    html = '<p>No active phases found in this stream.</p>';
                }
                
                $phaseList.html(html);
            } else {
                $phaseList.html('<p>Error loading phases: ' + (response.data.message || 'Unknown error') + '</p>');
            }
        }).fail(function() {
            $phaseList.html('<p>Failed to load phases.</p>');
        });
    }

    // Handle "Edit KPI" form submission
    $(document).on('submit', '#oo-edit-kpi-measure-form-stream-' + streamSlug, function(e) {
        e.preventDefault();
        var formData = $(this).serializeArray();
        formData.push({name: 'action', value: 'oo_edit_kpi_measure'});
        formData.push({name: '_ajax_nonce', value: oo_data.nonce_edit_kpi_measure});
        
        $.post(oo_data.ajax_url, $.param(formData), function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error updating KPI Measure: ' + (response.data.message || 'Unknown error'));
            }
        });
    });

    // Handle "Delete KPI" button clicks
    $(document).on('click', '.oo-delete-kpi-measure-stream', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this KPI Measure?')) return;

        var kpiMeasureId = $(this).data('kpi-measure-id');
        var $spinner = $('<span class="spinner is-active"></span>');
        $(this).parent().append($spinner);

        $.post(oo_data.ajax_url, {
            action: 'oo_delete_kpi_measure',
            _ajax_nonce: oo_data.nonce_delete_kpi_measure,
            kpi_measure_id: kpiMeasureId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.data.message || 'Could not delete KPI Measure.'));
                $spinner.remove();
            }
        }).fail(function() {
            alert('An unknown error occurred during deletion.');
            $spinner.remove();
        });
    });

    // Handle "Toggle KPI Status" button clicks
    $(document).on('click', '.oo-toggle-kpi-measure-status-stream', function() {
        var $button = $(this);
        var kpiMeasureId = $button.data('kpi-measure-id');
        var newStatus = $button.data('new-status');
        $button.prop('disabled', true);

        $.post(oo_data.ajax_url, {
            action: 'oo_toggle_kpi_measure_status',
            _ajax_nonce: oo_data.nonce_toggle_kpi_status,
            kpi_measure_id: kpiMeasureId,
            is_active: newStatus
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.data.message || 'Could not update KPI status.'));
                $button.prop('disabled', false);
            }
        }).fail(function() {
            alert('An unknown error occurred.');
            $button.prop('disabled', false);
        });
    });

    // ========================================================================
    // Derived KPI Management Logic (Fixed with correct nonces)
    // ========================================================================

    // Open "Add Derived KPI" modal
    $(document).on('click', '#openAddDerivedKpiModalBtn-stream-' + streamSlug, function() {
        var $modal = $('#addDerivedKpiModal-stream-' + streamSlug);
        $modal.find('form')[0].reset();
        $modal.show();
    });

    // Handle "Add Derived KPI" form submission
    $(document).on('submit', '#oo-add-derived-kpi-form-stream-' + streamSlug, function(e) {
        e.preventDefault();
        var formData = $(this).serialize(); // Nonce is already in the form via wp_nonce_field
        
        $.post(oo_data.ajax_url, formData, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.data.message || 'Could not add Derived KPI.'));
            }
        });
    });

    // Open "Edit Derived KPI" modal and populate it
    $(document).on('click', '.oo-edit-derived-kpi-stream', function() {
        var derivedKpiId = $(this).data('derived-kpi-id');
        var $modal = $('#editDerivedKpiModal-stream-' + streamSlug);
            
            $.post(oo_data.ajax_url, {
            action: 'oo_get_derived_kpi_definition_details',
            _ajax_nonce: oo_data.nonce_get_derived_kpi_details,
            derived_definition_id: derivedKpiId
            }, function(response) {
            if (response.success) {
                var dkpi = response.data.definition;
                $modal.find('[name="derived_definition_id"]').val(dkpi.derived_definition_id);
                $modal.find('#editDerivedKpiNameDisplay-' + streamSlug).text(dkpi.definition_name);
                $modal.find('[name="derived_definition_name"]').val(dkpi.definition_name);
                $modal.find('input[name="primary_kpi_measure_id"]').val(dkpi.primary_kpi_measure_id);
                $modal.find('[name="derived_calculation_type"]').val(dkpi.calculation_type);
                $modal.find('[name="derived_secondary_kpi_measure_id"]').val(dkpi.secondary_kpi_measure_id);
                $modal.find('[name="derived_time_unit_for_rate"]').val(dkpi.time_unit_for_rate);
                $modal.find('[name="derived_output_description"]').val(dkpi.output_description);
                $modal.find('[name="derived_is_active"]').prop('checked', parseInt(dkpi.is_active) === 1);
                $modal.show();
            } else {
                alert('Error fetching Derived KPI details: ' + response.data.message);
            }
        });
    });

    // Handle "Edit Derived KPI" form submission
    $(document).on('submit', '#oo-edit-derived-kpi-form-stream-' + streamSlug, function(e) {
        e.preventDefault();
        var formData = $(this).serialize(); // Nonce is already in the form
        
        $.post(oo_data.ajax_url, formData, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error updating Derived KPI: ' + (response.data.message || 'Unknown error'));
            }
        });
    });

    // Handle "Delete Derived KPI" button clicks
    $(document).on('click', '.oo-delete-derived-kpi-stream', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this Derived KPI?')) return;

        var derivedKpiId = $(this).data('derived-kpi-id');
        var $spinner = $('<span class="spinner is-active"></span>');
        $(this).parent().append($spinner);

        $.post(oo_data.ajax_url, {
            action: 'oo_delete_derived_kpi',
            _ajax_nonce: oo_data.nonce_delete_derived_kpi,
            derived_definition_id: derivedKpiId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.data.message || 'Could not delete Derived KPI.'));
                $spinner.remove();
            }
        }).fail(function() {
            alert('An unknown error occurred during deletion.');
            $spinner.remove();
        });
    });

    // Handle "Toggle Derived KPI Status" button clicks
    $(document).on('click', '.oo-toggle-derived-kpi-status-stream', function() {
            var $button = $(this);
        var derivedKpiId = $button.data('derived-kpi-id');
        var newStatus = $button.data('new-status');
        $button.prop('disabled', true);

        $.post(oo_data.ajax_url, {
            action: 'oo_toggle_derived_kpi_status',
            _ajax_nonce: oo_data.nonce_toggle_derived_kpi_status,
            derived_definition_id: derivedKpiId,
            is_active: newStatus
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.data.message || 'Could not update Derived KPI status.'));
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

}); 