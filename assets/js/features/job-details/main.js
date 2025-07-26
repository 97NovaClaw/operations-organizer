/**
 * Job Details JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize tabs
    $('.oo-tab-button').on('click', function() {
        var tabId = $(this).data('tab');
        
        // Update active states
        $('.oo-tab-button').removeClass('active');
        $(this).addClass('active');
        
        $('.oo-tab-panel').removeClass('active');
        $('#' + tabId).addClass('active');
        
        // Load logs for the newly active tab if not already loaded
        var $panel = $('#' + tabId);
        if (!$panel.data('logs-loaded')) {
            loadLogsForTab($panel);
        }
    });
    
    // Load logs for the initially active tab
    var $activePanel = $('.oo-tab-panel.active');
    if ($activePanel.length) {
        loadLogsForTab($activePanel);
    }
    
    // Phase selector change
    $('.oo-phase-selector').on('change', function() {
        var $this = $(this);
        var jobStreamId = $this.data('job-stream-id');
        var newPhaseId = $this.val();
        var phaseName = $this.find('option:selected').text();
        
        if (!newPhaseId) {
            return;
        }
        
        // Store data for modal
        window.pendingPhaseChange = {
            $selector: $this,
            jobStreamId: jobStreamId,
            newPhaseId: newPhaseId,
            phaseName: phaseName
        };
        
        // Show modal
        showPhaseChangeModal();
    });
    
    // Store initial phase values
    $('.oo-phase-selector').each(function() {
        $(this).data('previous-value', $(this).val());
    });
    
    // Apply/Reset log filters are now handled inside loadLogsForTab function
    
    // View activity log
    $('.oo-view-activity-log').on('click', function() {
        var jobStreamId = $(this).data('job-stream-id');
        showActivityLog(jobStreamId);
    });
    
    // Close modal
    $('.oo-close-modal, .oo-modal-close').on('click', function() {
        $(this).closest('.oo-modal').fadeOut();
    });
    
    // Close modal on outside click
    $('.oo-modal').on('click', function(e) {
        if ($(e.target).hasClass('oo-modal')) {
            $(this).fadeOut();
        }
    });
    
    /**
     * Load logs for a specific tab using DataTables
     */
    function loadLogsForTab($panel) {
        var streamId = $panel.data('stream-id');
        var $table = $panel.find('.oo-logs-table');
        var tableId = 'job-details-logs-table-' + streamId;
        
        // Set unique ID for this table
        $table.attr('id', tableId);
        
        // Destroy existing DataTable if it exists
        if ($.fn.DataTable.isDataTable('#' + tableId)) {
            $('#' + tableId).DataTable().destroy();
        }
        
        // Initialize DataTable with the same system as stream dashboard
        var logsTable = $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: oo_job_details_data.ajax_url,
                type: 'POST',
                data: function(d) {
                    d.action = 'oo_get_dashboard_data';
                    d.nonce = oo_job_details_data.dashboard_nonce || oo_job_details_data.nonce;
                    d.filter_employee_id = $panel.find('[data-filter="employee"]').val();
                    d.filter_phase_id = $panel.find('[data-filter="phase"]').val();
                    d.filter_date_from = $panel.find('[data-filter="date_from"]').val();
                    d.filter_date_to = $panel.find('[data-filter="date_to"]').val();
                    d.filter_stream_id = streamId;
                    d.filter_job_id = oo_job_details_data.job_id; // Add job filter
                    d.selected_columns_config = [];
                    
                    console.log('Sending job details logs request data:', d);
                    return d;
                },
                dataSrc: function(json) {
                    console.log('Received job details logs response:', json);
                    if (json && json.success === true && json.data && json.data.data) {
                        return json.data.data;
                    }
                    return json.data || [];
                },
                error: function(xhr, error, thrown) {
                    console.error('Job Details DataTables AJAX error:', error, thrown, xhr.responseText);
                    alert('Error loading job log data: ' + error);
                }
            },
            columns: [
                { data: 'employee_name', title: 'Employee' },
                { data: 'phase_name', title: 'Phase' },
                { data: 'start_time', title: 'Start Time' },
                { data: 'end_time', title: 'End Time', defaultContent: '-' },
                { data: 'status', title: 'Status' },
                { data: 'notes', title: 'Notes', defaultContent: '-' }
            ],
            order: [[2, 'desc']], // Order by start_time descending
            pageLength: 25,
            responsive: true,
            language: {
                emptyTable: "No logs found for this job in this stream",
                zeroRecords: "No logs match the current filters"
            }
        });
        
        // Store table reference and mark as loaded
        $panel.data('logs-table', logsTable);
        $panel.data('logs-loaded', true);
        
        // Apply filters functionality
        $panel.find('.oo-apply-log-filters').off('click').on('click', function() {
            logsTable.ajax.reload();
        });
        
        // Reset filters functionality
        $panel.find('.oo-reset-log-filters').off('click').on('click', function() {
            $panel.find('.oo-log-filter').val('');
            logsTable.ajax.reload();
        });
    }
    

    
    /**
     * Show activity log modal
     */
    function showActivityLog(jobStreamId) {
        // Always use the job details activity log system for consistency
        var modalId = '#job-details-activity-log-modal';
        var action = 'oo_job_details_get_activity_log';
        var nonce = oo_job_details_data.nonce;
        
        console.log('[DEBUG] Activity log - Using Job Details system');
        console.log('[DEBUG] Modal ID:', modalId);
        console.log('[DEBUG] Action:', action);
        
        var $modal = $(modalId);
        var $loading = $modal.find('.activity-log-loading');
        var $content = $modal.find('.activity-log-content');
        
        console.log('[DEBUG] Modal element found:', $modal.length > 0);
        console.log('[DEBUG] Modal current display:', $modal.css('display'));
        console.log('[DEBUG] Modal visibility:', $modal.is(':visible'));
        console.log('[DEBUG] Modal element:', $modal[0]);
        
        // Show modal with loading state
        console.log('[DEBUG] About to show modal...');
        $modal.fadeIn();
        console.log('[DEBUG] Modal display after fadeIn:', $modal.css('display'));
        console.log('[DEBUG] Modal visibility after fadeIn:', $modal.is(':visible'));
        
        $loading.show();
        $content.hide();
        
        // Load activity log
        $.ajax({
            url: oo_job_details_data.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: nonce,
                job_stream_id: jobStreamId
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $content.html(response.data.html).show();
                    $loading.hide();
                } else {
                    $content.html('<p class="oo-error">Error loading activity log.</p>').show();
                    $loading.hide();
                }
            },
            error: function() {
                $content.html('<p class="oo-error">Error loading activity log.</p>').show();
                $loading.hide();
            }
        });
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        var $notification = $('<div class="oo-notification oo-notification-' + type + '">' + 
                            message + '</div>');
        
        $('body').append($notification);
        
        $notification.fadeIn(300).delay(3000).fadeOut(300, function() {
            $(this).remove();
        });
    }
    
    /**
     * Format date/time
     */
    function formatDateTime(dateString) {
        if (!dateString) return '-';
        
        var date = new Date(dateString);
        var options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        
        return date.toLocaleDateString(undefined, options);
    }
    
    /**
     * Show phase change modal
     */
    function showPhaseChangeModal() {
        var $modal = $('#job-details-phase-change-modal');
        var data = window.pendingPhaseChange;
        
        // Set the target phase name
        $('#job-details-phase-change-target').text(data.phaseName);
        
        // Clear the note field
        $('#job-details-phase-change-note').val('').focus();
        
        // Show the modal
        $modal.fadeIn();
    }
    
    // Phase change form submission
    $('#job-details-phase-change-form').on('submit', function(e) {
        e.preventDefault();
        
        var note = $('#job-details-phase-change-note').val().trim();
        if (!note) {
            alert('Please enter a note for this phase change.');
            return;
        }
        
        var data = window.pendingPhaseChange;
        
        // Hide modal
        $('#job-details-phase-change-modal').hide();
        
        // Re-enable selector
        data.$selector.prop('disabled', false);
        
        // Make AJAX request
        $.ajax({
            url: oo_job_details_data.ajax_url,
            type: 'POST',
            data: {
                action: 'oo_job_details_change_phase',
                nonce: oo_job_details_data.nonce,
                job_stream_id: data.jobStreamId,
                new_phase_id: data.newPhaseId,
                note: note
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    // Update the stored previous value
                    data.$selector.data('previous-value', data.newPhaseId);
                } else {
                    showNotification(response.data || oo_job_details_data.strings.error_updating, 'error');
                    // Reset to previous value on error
                    data.$selector.val(data.$selector.data('previous-value') || '');
                }
            },
            error: function(xhr, status, error) {
                showNotification(oo_job_details_data.strings.error_updating, 'error');
                // Reset to previous value on error
                data.$selector.val(data.$selector.data('previous-value') || '');
            }
        });
    });
    
    // Cancel phase change
    $('.cancel-phase-change').on('click', function() {
        $('#job-details-phase-change-modal').hide();
        
        if (window.pendingPhaseChange) {
            // Reset selector to previous value
            var data = window.pendingPhaseChange;
            data.$selector.val(data.$selector.data('previous-value') || '');
            data.$selector.prop('disabled', false);
            window.pendingPhaseChange = null;
        }
    });
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
}); 