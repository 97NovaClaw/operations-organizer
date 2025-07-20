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
        
        // Confirm the change
        var confirmMessage = oo_job_details_data.strings.confirm_phase_change.replace('{phase}', phaseName);
        if (!confirm(confirmMessage)) {
            // Reset to previous value
            $this.val($this.data('previous-value') || '');
            return;
        }
        
        // Store current value
        $this.data('previous-value', newPhaseId);
        
        // Disable selector during update
        $this.prop('disabled', true);
        
        // Debugging
        console.log('[JOB_DETAILS_DEBUG] Phase change AJAX data:', {
            action: 'oo_job_details_change_phase',
            nonce: oo_job_details_data.nonce,
            job_stream_id: jobStreamId,
            new_phase_id: newPhaseId,
            ajax_url: oo_job_details_data.ajax_url
        });
        
        // Make AJAX request
        $.ajax({
            url: oo_job_details_data.ajax_url,
            type: 'POST',
            data: {
                action: 'oo_job_details_change_phase',
                nonce: oo_job_details_data.nonce,
                job_stream_id: jobStreamId,
                new_phase_id: newPhaseId
            },
            success: function(response) {
                console.log('[JOB_DETAILS_DEBUG] AJAX Success response:', response);
                if (response.success) {
                    showNotification(response.data.message, 'success');
                } else {
                    console.log('[JOB_DETAILS_DEBUG] AJAX Success but response.success = false');
                    showNotification(response.data || oo_job_details_data.strings.error_updating, 'error');
                    // Reset to previous value on error
                    $this.val($this.data('previous-value') || '');
                }
            },
            error: function(xhr, status, error) {
                console.log('[JOB_DETAILS_DEBUG] AJAX Error:', {
                    xhr: xhr,
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                showNotification(oo_job_details_data.strings.error_updating, 'error');
                // Reset to previous value on error
                $this.val($this.data('previous-value') || '');
            },
            complete: function() {
                $this.prop('disabled', false);
            }
        });
    });
    
    // Store initial phase values
    $('.oo-phase-selector').each(function() {
        $(this).data('previous-value', $(this).val());
    });
    
    // Apply log filters
    $('.oo-apply-log-filters').on('click', function() {
        var $panel = $(this).closest('.oo-tab-panel');
        loadLogsForTab($panel);
    });
    
    // Reset log filters
    $('.oo-reset-log-filters').on('click', function() {
        var $panel = $(this).closest('.oo-tab-panel');
        $panel.find('.oo-log-filter').val('');
        loadLogsForTab($panel);
    });
    
    // View activity log
    $('.oo-view-activity-log').on('click', function() {
        var jobStreamId = $(this).data('job-stream-id');
        showActivityLog(jobStreamId);
    });
    
    // Close modal
    $('.oo-close-modal').on('click', function() {
        $(this).closest('.oo-modal').fadeOut();
    });
    
    // Close modal on outside click
    $('.oo-modal').on('click', function(e) {
        if ($(e.target).hasClass('oo-modal')) {
            $(this).fadeOut();
        }
    });
    
    /**
     * Load logs for a specific tab
     */
    function loadLogsForTab($panel) {
        var streamId = $panel.data('stream-id');
        var $tbody = $panel.find('.oo-logs-table tbody');
        
        // Show loading
        $tbody.html('<tr><td colspan="6" class="oo-loading-message">' + 
                   'Loading logs...' + '</td></tr>');
        
        // Collect filter values
        var filters = {
            action: 'oo_job_details_get_logs',
            nonce: oo_job_details_data.nonce,
            job_id: oo_job_details_data.job_id,
            stream_id: streamId
        };
        
        $panel.find('.oo-log-filter').each(function() {
            var filterType = $(this).data('filter');
            var value = $(this).val();
            if (value) {
                if (filterType === 'employee' || filterType === 'phase') {
                    filters[filterType + '_id'] = value;
                } else {
                    filters[filterType] = value;
                }
            }
        });
        
        // Make AJAX request
        $.ajax({
            url: oo_job_details_data.ajax_url,
            type: 'POST',
            data: filters,
            success: function(response) {
                if (response.success && response.data.logs) {
                    renderLogs($tbody, response.data.logs);
                    $panel.data('logs-loaded', true);
                } else {
                    $tbody.html('<tr><td colspan="6" class="oo-no-logs">' + 
                               'No logs found.' + '</td></tr>');
                }
            },
            error: function() {
                $tbody.html('<tr><td colspan="6" class="oo-error-message">' + 
                           'Error loading logs.' + '</td></tr>');
            }
        });
    }
    
    /**
     * Render logs in the table
     */
    function renderLogs($tbody, logs) {
        if (logs.length === 0) {
            $tbody.html('<tr><td colspan="6" class="oo-no-logs">' + 
                       'No logs found.' + '</td></tr>');
            return;
        }
        
        var html = '';
        $.each(logs, function(index, log) {
            html += '<tr>';
            html += '<td>' + escapeHtml(log.employee_name) + '</td>';
            html += '<td>' + escapeHtml(log.phase_name) + '</td>';
            html += '<td>' + formatDateTime(log.start_time) + '</td>';
            html += '<td>' + (log.end_time ? formatDateTime(log.end_time) : '-') + '</td>';
            html += '<td><span class="oo-status-' + log.status + '">' + 
                    escapeHtml(log.status) + '</span></td>';
            html += '<td>' + (log.notes ? escapeHtml(log.notes) : '-') + '</td>';
            html += '</tr>';
        });
        
        $tbody.html(html);
    }
    
    /**
     * Show activity log modal
     */
    function showActivityLog(jobStreamId) {
        var $modal = $('#job-details-activity-log-modal');
        var $loading = $modal.find('.activity-log-loading');
        var $content = $modal.find('.activity-log-content');
        
        // Show modal with loading state
        $modal.fadeIn();
        $loading.show();
        $content.hide();
        
        // Load activity log
        $.ajax({
            url: oo_job_details_data.ajax_url,
            type: 'POST',
            data: {
                action: 'oo_job_details_get_activity_log',
                nonce: oo_job_details_data.nonce,
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