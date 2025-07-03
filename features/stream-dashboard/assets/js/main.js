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
                
                // Debug logging
                console.log('[DEBUG] KPI ID:', kpiMeasureId);
                console.log('[DEBUG] Linked Phase IDs from server:', linkedPhaseIds);
                console.log('[DEBUG] Available phases:', response.data.phases);
                
                if (response.data.phases && response.data.phases.length > 0) {
                    response.data.phases.forEach(function(phase) {
                        // Convert both to integers for comparison
                        var phaseId = parseInt(phase.phase_id);
                        var isLinked = false;
                        
                        // Check if this phase ID is in the linked phases array
                        for (var i = 0; i < linkedPhaseIds.length; i++) {
                            if (parseInt(linkedPhaseIds[i]) === phaseId) {
                                isLinked = true;
                                break;
                            }
                        }
                        
                        var isChecked = isLinked ? 'checked' : '';
                        console.log('[DEBUG] Phase:', phase.phase_name, 'ID:', phaseId, 'Is Linked:', isLinked, 'Checked:', isChecked);
                        
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
        var $form = $(this);
        var formData = $form.serializeArray();
        formData.push({name: 'action', value: 'oo_edit_kpi_measure'});
        formData.push({name: '_ajax_nonce', value: oo_data.nonce_edit_kpi_measure});
        formData.push({name: 'stream_id_context', value: oo_data.current_stream_id}); // Add stream context for phase linking
        
        // Fix: Explicitly handle the is_active checkbox since serializeArray() doesn't include unchecked checkboxes
        var isActiveChecked = $form.find('[name="is_active"]').prop('checked');
        if (isActiveChecked) {
            formData.push({name: 'is_active', value: '1'});
        } else {
            formData.push({name: 'is_active', value: '0'});
        }
        
        console.log('[EXTREME_DEBUG] ========== EDIT KPI FORM SUBMISSION ==========');
        console.log('[EXTREME_DEBUG] Raw form data (serializeArray):', formData);
        console.log('[EXTREME_DEBUG] Form HTML:', $form[0].outerHTML);
        console.log('[EXTREME_DEBUG] Is Active checkbox value:', $form.find('[name="is_active"]').prop('checked'));
        console.log('[EXTREME_DEBUG] Checked phase checkboxes:', $form.find('[name="link_to_phases[]"]:checked').map(function() { return this.value; }).get());
        console.log('[EXTREME_DEBUG] All phase checkboxes:', $form.find('[name="link_to_phases[]"]').map(function() { return {value: this.value, checked: this.checked}; }).get());
        console.log('[EXTREME_DEBUG] URL Encoded form data:', $.param(formData));
        
        $.post(oo_data.ajax_url, $.param(formData), function(response) {
            console.log('[EXTREME_DEBUG] AJAX Response received:', response);
            if (response.success) {
                alert('KPI measure updated successfully!');
                location.reload();
            } else {
                alert('Error updating KPI Measure: ' + (response.data.message || 'Unknown error'));
            }
        }).fail(function(xhr, status, error) {
            console.log('[EXTREME_DEBUG] AJAX FAILED!');
            console.log('[EXTREME_DEBUG] XHR object:', xhr);
            console.log('[EXTREME_DEBUG] Status:', status);
            console.log('[EXTREME_DEBUG] Error:', error);
            console.log('[EXTREME_DEBUG] Response text:', xhr.responseText);
            alert('Error: Failed to update KPI measure');
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

    // Helper functions for Derived KPI modals
    function populateCalculationTypes(primaryKpiUnitType, $selectElement) {
        $selectElement.empty();
        $selectElement.append($('<option>', { value: '', text: '-- Select Calculation Type --' }));

        var calcTypes = {
            all: [
                { value: 'sum_value', text: 'Sum of Primary KPI Value' },
                { value: 'average_value', text: 'Average of Primary KPI Value' },
            ],
            numeric: [
                { value: 'rate_per_time', text: 'Rate: Primary KPI per Time Unit' },
                { value: 'ratio_to_kpi', text: 'Ratio: Primary KPI to Secondary KPI' },
            ],
            boolean: [
                { value: 'count_if_true', text: 'Count occurrences if Primary KPI is TRUE' },
                { value: 'count_if_false', text: 'Count occurrences if Primary KPI is FALSE' },
            ]
        };

        // Add common calculation types
        calcTypes.all.forEach(function(type) {
            $selectElement.append($('<option>', { value: type.value, text: type.text }));
        });

        // Add type-specific calculation types
        if (primaryKpiUnitType === 'integer' || primaryKpiUnitType === 'decimal') {
            calcTypes.numeric.forEach(function(type) {
                $selectElement.append($('<option>', { value: type.value, text: type.text }));
            });
        } else if (primaryKpiUnitType === 'boolean') {
            calcTypes.boolean.forEach(function(type) {
                $selectElement.append($('<option>', { value: type.value, text: type.text }));
            });
        }
    }

    function populateSecondaryKpis($selectElement, primaryKpiIdToExclude) {
        $selectElement.empty().append($('<option>', { value: '', text: '-- Select Secondary KPI --' }));
        if (oo_data.all_kpi_measures && oo_data.all_kpi_measures.length > 0) {
            oo_data.all_kpi_measures.forEach(function(kpi) {
                if (kpi.kpi_measure_id.toString() !== primaryKpiIdToExclude.toString()) {
                    $selectElement.append($('<option>', { 
                        value: kpi.kpi_measure_id, 
                        text: kpi.measure_name + ' (' + kpi.unit_type + ')' 
                    }));
                }
            });
        }
        if ($selectElement.children().length === 1) { // Only the default option
             $selectElement.append('<option value="" disabled>No other KPIs available</option>');
        }
    }

    function handleDerivedKpiCalcTypeChange($modal) {
        var selectedType = $modal.find('select[name="derived_calculation_type"]').val();
        var $secondaryKpiField = $modal.find('[id*="derived_secondary_kpi_field"]');
        var $timeUnitField = $modal.find('[id*="derived_time_unit_field"]');

        if (selectedType === 'ratio_to_kpi') {
            $secondaryKpiField.show();
            $timeUnitField.hide();
        } else if (selectedType === 'rate_per_time') {
            $secondaryKpiField.hide();
            $timeUnitField.show();
        } else {
            $secondaryKpiField.hide();
            $timeUnitField.hide();
        }
    }

    // Open "Add Derived KPI" modal
    $(document).on('click', '#openAddDerivedKpiModalBtn-stream-' + streamSlug, function() {
        var $modal = $('#addDerivedKpiModal-stream-' + streamSlug);
        var $form = $modal.find('form');
        var $primaryKpiSelect = $modal.find('#add_derived_primary_kpi_id-stream-' + streamSlug);
        var $calcTypeSelect = $modal.find('#add_derived_calculation_type-stream-' + streamSlug);

        // Reset the form first
        $form[0].reset();
        $primaryKpiSelect.empty().append('<option value="">Loading KPIs...</option>').prop('disabled', true);
        $calcTypeSelect.empty().append('<option value="">-- Select Calculation Type --</option>');
        handleDerivedKpiCalcTypeChange($modal); // Reset dependent fields

        // Load available KPI measures for this stream
        $.post(oo_data.ajax_url, {
            action: 'oo_get_json_kpi_measures_for_stream',
            stream_id: oo_data.current_stream_id,
            _ajax_nonce: oo_data.nonce_get_kpi_measures
        }, function(response) {
            if (response.success && response.data.kpis) {
                $primaryKpiSelect.empty().append('<option value="">-- Select Primary KPI --</option>').prop('disabled', false);
                response.data.kpis.forEach(function(kpi) {
                    $primaryKpiSelect.append($('<option>', {
                        value: kpi.kpi_measure_id,
                        text: kpi.measure_name + ' (' + kpi.unit_type + ')',
                        'data-unit-type': kpi.unit_type
                    }));
                });
            } else {
                $primaryKpiSelect.empty().append('<option value="">No KPIs available</option>');
            }
        }).fail(function() {
            $primaryKpiSelect.empty().append('<option value="">Failed to load KPIs</option>');
        }).always(function() {
            // After attempting to load, populate other fields based on current selection
            var initialPrimaryKpiUnit = $primaryKpiSelect.find('option:selected').data('unit-type') || '';
            populateCalculationTypes(initialPrimaryKpiUnit, $calcTypeSelect);
            populateSecondaryKpis($modal.find('#add_derived_secondary_kpi_id-stream-' + streamSlug), $primaryKpiSelect.val());
            $modal.show();
        });
    });

    // Handle Primary KPI change in Add Derived KPI Modal
    $(document).on('change', '#add_derived_primary_kpi_id-stream-' + streamSlug, function() {
        var $modal = $('#addDerivedKpiModal-stream-' + streamSlug);
        var unitType = $(this).find('option:selected').data('unit-type') || '';
        var primaryKpiId = $(this).val();
        populateCalculationTypes(unitType, $modal.find('#add_derived_calculation_type-stream-' + streamSlug));
        populateSecondaryKpis($modal.find('#add_derived_secondary_kpi_id-stream-' + streamSlug), primaryKpiId);
    });

    // Handle Calculation Type change in Add Derived KPI Modal
    $(document).on('change', '#add_derived_calculation_type-stream-' + streamSlug, function() {
        var $modal = $('#addDerivedKpiModal-stream-' + streamSlug);
        handleDerivedKpiCalcTypeChange($modal);
    });

    // Handle "Add Derived KPI" form submission
    $(document).on('submit', '#oo-add-derived-kpi-form-stream-' + streamSlug, function(e) {
        e.preventDefault();
        var $form = $(this);
        var formData = $form.serializeArray();
        
        // Fix: Explicitly handle the derived_is_active checkbox
        var isActiveChecked = $form.find('[name="derived_is_active"]').prop('checked');
        if (isActiveChecked) {
            formData.push({name: 'derived_is_active', value: '1'});
        } else {
            formData.push({name: 'derived_is_active', value: '0'});
        }
        
        $.post(oo_data.ajax_url, $.param(formData), function(response) {
            if (response.success) {
                alert('Derived KPI added successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.data.message || 'Could not add Derived KPI.'));
            }
        }).fail(function() {
            alert('Error: Failed to add Derived KPI');
        });
    });

    // Open "Edit Derived KPI" modal and populate it
    $(document).on('click', '.oo-edit-derived-kpi-stream', function() {
        var derivedKpiId = $(this).data('derived-kpi-id');
        var $modal = $('#editDerivedKpiModal-stream-' + streamSlug);
        $modal.find('#editDerivedKpiNameDisplay-' + streamSlug).text('Loading...');
            
            $.post(oo_data.ajax_url, {
            action: 'oo_get_derived_kpi_definition_details',
            _ajax_nonce: oo_data.nonce_get_derived_kpi_details,
            derived_definition_id: derivedKpiId
            }, function(response) {
            if (response.success) {
                var dkpi = response.data.definition;
                var primary_kpi = response.data.primary_kpi; // Expecting this from backend
                
                $modal.find('[name="derived_definition_id"]').val(dkpi.derived_definition_id);
                $modal.find('#editDerivedKpiNameDisplay-' + streamSlug).text(dkpi.definition_name);
                $modal.find('[name="derived_definition_name"]').val(dkpi.definition_name);
                
                // Primary KPI display (name and unit type)
                $modal.find('#edit_derived_primary_kpi_name_display-stream-' + streamSlug).text(primary_kpi ? primary_kpi.measure_name : 'Unknown KPI');
                $modal.find('#edit_derived_primary_kpi_unit_type-stream-' + streamSlug).val(primary_kpi ? primary_kpi.unit_type : '');
                $modal.find('[name="primary_kpi_measure_id"]').val(dkpi.primary_kpi_measure_id);
                
                // Populate dynamic dropdowns and handle conditional visibility FIRST
                populateCalculationTypes(primary_kpi ? primary_kpi.unit_type : '', $modal.find('[name="derived_calculation_type"]'));
                $modal.find('[name="derived_calculation_type"]').val(dkpi.calculation_type);
                $modal.find('[name="derived_calculation_type"]').trigger('change'); // This might re-render sections
                
                populateSecondaryKpis($modal.find('[name="derived_secondary_kpi_measure_id"]'), dkpi.primary_kpi_measure_id);
                if (dkpi.calculation_type === 'ratio_to_kpi' && dkpi.secondary_kpi_measure_id) {
                    $modal.find('[name="derived_secondary_kpi_measure_id"]').val(dkpi.secondary_kpi_measure_id);
                }
                if (dkpi.calculation_type === 'rate_per_time' && dkpi.time_unit_for_rate) {
                    $modal.find('[name="derived_time_unit_for_rate"]').val(dkpi.time_unit_for_rate);
                }
                
                $modal.find('[name="derived_output_description"]').val(dkpi.output_description);
                $modal.find('[name="derived_is_active"]').prop('checked', parseInt(dkpi.is_active) === 1);
                
                $modal.show();
            } else {
                alert('Error fetching Derived KPI details: ' + response.data.message);
            }
        }).fail(function() {
            alert('Error: Request to load Derived KPI data failed.');
        });
    });

    // Handle Calculation Type change in Edit Derived KPI Modal
    $(document).on('change', '#edit_derived_calculation_type-stream-' + streamSlug, function() {
        var $modal = $('#editDerivedKpiModal-stream-' + streamSlug);
        handleDerivedKpiCalcTypeChange($modal);
        // If changing to a type that doesn't need secondary KPI, clear its value
        if ($(this).val() !== 'ratio_to_kpi') {
            $modal.find('[name="derived_secondary_kpi_measure_id"]').val('');
        }
        // If changing to a type that doesn't need time unit, clear its value
        if ($(this).val() !== 'rate_per_time') {
            $modal.find('[name="derived_time_unit_for_rate"]').val('hour'); // Reset to default
        }
    });

    // Handle "Edit Derived KPI" form submission
    $(document).on('submit', '#oo-edit-derived-kpi-form-stream-' + streamSlug, function(e) {
        e.preventDefault();
        var $form = $(this);
        var formData = $form.serializeArray();
        
        // Fix: Explicitly handle the derived_is_active checkbox
        var isActiveChecked = $form.find('[name="derived_is_active"]').prop('checked');
        if (isActiveChecked) {
            formData.push({name: 'derived_is_active', value: '1'});
        } else {
            formData.push({name: 'derived_is_active', value: '0'});
        }
        
        $.post(oo_data.ajax_url, $.param(formData), function(response) {
            if (response.success) {
                alert('Derived KPI updated successfully!');
                location.reload();
            } else {
                alert('Error updating Derived KPI: ' + (response.data.message || 'Unknown error'));
            }
        }).fail(function() {
            alert('Error: Failed to update Derived KPI');
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
            action: 'oo_delete_derived_kpi_definition',
            _ajax_nonce: oo_data.nonce_delete_derived_kpi,
            derived_definition_id: derivedKpiId
        }, function(response) {
            if (response.success) {
                alert('Derived KPI deleted successfully!');
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
                alert('Derived KPI status updated successfully!');
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