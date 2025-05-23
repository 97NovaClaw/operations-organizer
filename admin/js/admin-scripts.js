jQuery(document).ready(function($) {
    // Debounce function to limit how often a function can run.
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    // Utility function for job log DataTables
    window.formatJobLogActions = function(row) {
        if (!row || typeof row !== 'object') {
            console.error('Invalid row data passed to formatJobLogActions:', row);
            return '<div class="row-actions">Error: Invalid data</div>';
        }
        
        // Make sure we have admin_url available
        var adminUrl = (typeof oo_data !== 'undefined' && oo_data.admin_url) ? 
                      oo_data.admin_url : 
                      '/wp-admin/';
        
        var actionButtons = '<div class="row-actions">';
        
        // Show Stop button only for running jobs
        if (row.status && row.status.includes && row.status.includes('Running')) {
            actionButtons += '<span class="edit"><a href="' + adminUrl + 'admin.php?page=oo_stop_job&log_id=' + 
                            (row.log_id || '') + '" class="oo-stop-job-action">Stop</a> | </span>';
        }
        
        actionButtons += '<span class="edit"><a href="#" class="oo-edit-log-action" data-log-id="' + 
                        (row.log_id || '') + '">Edit</a> | </span>';
        actionButtons += '<span class="trash"><a href="#" class="oo-delete-log-action" data-log-id="' + 
                        (row.log_id || '') + '">Delete</a></span>';
        actionButtons += '</div>';
        
        return actionButtons;
    };

    // Common function to display notices
    // Ensures only one notice is visible at a time and fades out.
    window.showNotice = debounce(function(type, message) {
        $('.oo-notice').remove(); // Remove any existing notices
        var noticeHtml = '<div class="notice notice-' + type + ' is-dismissible oo-notice"><p>' + message + '</p>' +
                         '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
        
        // Try to place notice after h1, or at the top of .wrap if h1 not specific enough
        if ($('.wrap h1').length) {
            $('.wrap h1').first().after(noticeHtml);
        } else {
            $('.wrap').prepend(noticeHtml);
        }

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.oo-notice').fadeOut('slow', function() { $(this).remove(); });
        }, 5000);

        // Allow manual dismiss
        $('.oo-notice .notice-dismiss').on('click', function(event) {
            event.preventDefault();
            $(this).closest('.oo-notice').remove();
        });
    }, 250); // Debounce for 250ms to prevent multiple rapid notices

    // AJAX form submission for Add Employee
    $('body').on('submit', '#oo-add-employee-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        $submitButton.prop('disabled', true);

        var formData = $form.serialize();
        $.post(oo_data.ajax_url, formData + '&action=oo_add_employee', function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                $form[0].reset();
                if (typeof window.loadEmployeesTable === 'function') {
                    window.loadEmployeesTable();
                }
                $('#addEmployeeModal').hide();
            } else {
                showNotice('error', response.data.message || 'An unknown error occurred.');
            }
        }).fail(function() {
            showNotice('error', 'Request failed. Please try again.');
        }).always(function() {
            $submitButton.prop('disabled', false);
        });
    });

    // AJAX form submission for Edit Employee
    $('body').on('submit', '#oo-edit-employee-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        $submitButton.prop('disabled', true);
        
        var formData = $form.serialize();
        $.post(oo_data.ajax_url, formData + '&action=oo_update_employee', function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                if (typeof window.loadEmployeesTable === 'function') {
                    window.loadEmployeesTable();
                }
                $('#editEmployeeModal').hide();
            } else {
                showNotice('error', response.data.message || 'An unknown error occurred.');
            }
        }).fail(function() {
            showNotice('error', 'Request failed. Please try again.');
        }).always(function() {
            $submitButton.prop('disabled', false);
        });
    });

    // AJAX form submission for Add Phase
    $('body').on('submit', '#oo-add-phase-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        $submitButton.prop('disabled', true);

        var formData = $form.serialize();
        $.post(oo_data.ajax_url, formData + '&action=oo_add_phase', function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                $form[0].reset();
                if (typeof window.loadPhasesTable === 'function') {
                    window.loadPhasesTable();
                }
                $('#addPhaseModal').hide();
            } else {
                showNotice('error', response.data.message || 'An unknown error occurred.');
            }
        }).fail(function() {
            showNotice('error', 'Request failed. Please try again.');
        }).always(function() {
            $submitButton.prop('disabled', false);
        });
    });

    // AJAX form submission for Edit Phase
    $('body').on('submit', '#oo-edit-phase-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        $submitButton.prop('disabled', true);
        
        // Explicitly check if the checkbox is checked and console.log for debugging
        var includesKPI = $('#edit_modal_includes_kpi').is(':checked');
        console.log('Checkbox #edit_modal_includes_kpi is checked:', includesKPI);
        
        // Get form data and log it
        var formData = $form.serialize();
        console.log('Form data:', formData);
        
        // Add debug info to the form data
        formData += '&debug_includes_kpi=' + (includesKPI ? '1' : '0');
        
        $.post(oo_data.ajax_url, formData + '&action=oo_update_phase', function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                if (typeof window.loadPhasesTable === 'function') {
                    window.loadPhasesTable(); 
                }
                $('#editOOPhaseModal').hide();
            } else {
                showNotice('error', response.data.message || 'An unknown error occurred.');
            }
        }).fail(function() {
            showNotice('error', 'Request failed. Please try again.');
        }).always(function() {
            $submitButton.prop('disabled', false);
        });
    });

    // Modal Open/Close (ensure these are bound even if modals are added dynamically, though not the case here)
    $('body').on('click', '.oo-open-modal-button', function() {
        var targetModal = $(this).data('modal-id');
        $('#' + targetModal).show();
    });

    $('body').on('click', '.oo-close-button', function() {
        $(this).closest('.oo-modal').hide();
    });

    // Close modal if clicked outside of content
    $(window).on('click', function(event) {
        if ($(event.target).is('.oo-modal')) {
            $(event.target).hide();
        }
    });
    
    // Start Job Form AJAX Submission
    $('body').on('submit', '#oo-start-job-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('#start-job-submit');
        $submitButton.prop('disabled', true).text('Starting...');
        
        var formData = $form.serialize();
        formData += '&action=oo_start_job_action';

        $.post(oo_data.ajax_url, formData)
            .done(function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    window.location.href = oo_data.dashboard_url;
                } else {
                    showNotice('error', response.data.message || 'An unknown error occurred.');
                    $submitButton.prop('disabled', false).text('Start Job'); // Re-enable only on error
                }
            })
            .fail(function() {
                showNotice('error', 'Request failed. Please try again.');
                $submitButton.prop('disabled', false).text('Start Job');
            });
    });

    // Stop Job Form AJAX Submission
    $('body').on('submit', '#oo-stop-job-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('#stop-job-submit');
        $submitButton.prop('disabled', true).text('Stopping...');
        
        var formData = $form.serialize();
        formData += '&action=oo_stop_job_action';

        $.post(oo_data.ajax_url, formData)
            .done(function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    window.location.href = oo_data.dashboard_url;
                } else {
                    showNotice('error', response.data.message || 'An unknown error occurred.');
                    $submitButton.prop('disabled', false).text('Stop Job & Save'); // Re-enable only on error
                }
            })
            .fail(function() {
                showNotice('error', 'Request failed. Please try again.');
                $submitButton.prop('disabled', false).text('Stop Job & Save');
            });
    });

    // Initialize Datepicker for any elements with class .oo-datepicker
    // This might be on dashboard page or other admin pages if added later
    if (typeof $.fn.datepicker === 'function') {
        $('.oo-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true
        });
    }

    // --- Logic for dynamically loading KPI fields in Start/Stop Job Forms ---
    function loadPhaseKpiFieldsForForm(phaseId, containerSelector, formType /* 'start' or 'stop' */) {
        if (!phaseId || phaseId <= 0) {
            $(containerSelector).empty().hide();
            $(containerSelector).closest('.kpi-fields-row').hide();
            return;
        }

        var $container = $(containerSelector);
        var $kpiRow = $container.closest('.kpi-fields-row');
        $container.html('<p>Loading KPI fields...</p>');
        $kpiRow.show();

        $.post(oo_data.ajax_url, {
            action: 'oo_get_kpis_for_phase_form',
            phase_id: phaseId,
            _ajax_nonce: oo_data.nonce_dashboard // Using dashboard nonce for now, consider specific if needed
        }, function(response) {
            $container.empty();
            if (response.success && response.data && response.data.length > 0) {
                $.each(response.data, function(index, kpi) {
                    if (!kpi.measure_key || !kpi.measure_name) return true; // continue to next KPI

                    let inputFieldHtml = '';
                    const inputId = 'kpi_' + formType + '_' + kpi.measure_key;
                    const inputName = 'kpi_values[' + kpi.measure_key + ']';
                    const labelText = kpi.measure_name + (kpi.is_mandatory == 1 ? ' <span class="required" style="color:red;">*</span>' : '');
                    let requiredAttr = kpi.is_mandatory == 1 ? 'required' : '';

                    var fieldWrapper = $('<div class="oo-kpi-field-wrapper" style="margin-bottom: 10px;"></div>');
                    var label = $('<label for="' + inputId + '" style="display:block; font-weight:bold; margin-bottom:3px;">' + labelText + '</label>');
                    var inputElement;

                    switch (kpi.unit_type) {
                        case 'integer':
                            inputElement = $('<input type="number" id="' + inputId + '" name="' + inputName + '" ' + requiredAttr + ' step="1" class="regular-text" />');
                            break;
                        case 'decimal':
                            inputElement = $('<input type="number" id="' + inputId + '" name="' + inputName + '" ' + requiredAttr + ' step="0.01" class="regular-text" />');
                            break;
                        case 'text':
                            inputElement = $('<textarea id="' + inputId + '" name="' + inputName + '" ' + requiredAttr + ' rows="2" class="widefat"></textarea>');
                            break;
                        case 'boolean':
                            // For boolean, a value of "1" is sent if checked. If not checked, it won't be in POST data unless a hidden field is used.
                            // We can make it simpler: a checked checkbox sends its value.
                            inputElement = $('<input type="checkbox" id="' + inputId + '" name="' + inputName + '" value="1" style="margin-top: 5px;"/>');
                            // No 'required' for checkbox in the same way, but label indicates if mandatory.
                            break;
                        case 'date':
                             inputElement = $('<input type="date" id="' + inputId + '" name="' + inputName + '" ' + requiredAttr + ' class="regular-text" />');
                             if (typeof $.fn.datepicker === 'function') { // Initialize if datepicker is available
                                // Datepicker usually auto-initializes based on type=date or a class.
                                // If manual initialization needed: inputElement.datepicker({ dateFormat: 'yy-mm-dd' });
                             }
                            break;
                        default:
                            inputElement = $('<input type="text" id="' + inputId + '" name="' + inputName + '" ' + requiredAttr + ' class="regular-text" />');
                    }
                    fieldWrapper.append(label).append(inputElement);
                    $container.append(fieldWrapper);
                });
                $kpiRow.show();
            } else {
                $container.html('<p>No specific KPIs configured for this phase.</p>');
                // If response.data is empty but success is true, it means phase has no KPIs
                if(response.success && response.data && response.data.length === 0){
                    $kpiRow.hide(); // Hide the whole KPI row if no KPIs
                } else if(!response.success){
                     $container.append('<p style=\"color:red;\">Error: ' + (response.data.message || 'Could not load KPIs.') + '</p>');
                     $kpiRow.show(); // Show row with error
                }
            }
        }).fail(function(xhr, status, error) {
            $container.html('<p style="color:red;">Failed to load KPI fields. Error: ' + status + '</p>');
            console.error("AJAX request for KPI fields failed:", status, error, xhr.responseText);
            $kpiRow.show(); // Show row with error
        });
    }

    // Usage for Start Job Form (assuming phase_id is available as a JS variable `oo_form_data.phase_id`)
    if ($('#phase-kpi-fields-container-start').length && typeof oo_form_data !== 'undefined' && oo_form_data.phase_id) {
        loadPhaseKpiFieldsForForm(oo_form_data.phase_id, '#phase-kpi-fields-container-start', 'start');
    }

    // Usage for Stop Job Form (assuming phase_id is available as a JS variable `oo_form_data.phase_id`)
    if ($('#phase-kpi-fields-container-stop').length && typeof oo_form_data !== 'undefined' && oo_form_data.phase_id) {
        loadPhaseKpiFieldsForForm(oo_form_data.phase_id, '#phase-kpi-fields-container-stop', 'stop');
    }
    // --- End KPI Fields Logic ---

}); 