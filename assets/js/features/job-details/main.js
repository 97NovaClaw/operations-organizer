/**
 * Job Details JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('[JOB_DETAILS_DEBUG] Job details script loaded');
    console.log('[JOB_DETAILS_DEBUG] Available global objects:', {
        oo_job_details_data: typeof oo_job_details_data,
        DataTable: typeof $.fn.DataTable
    });
    
    // Initialize job activity log table
    initializeJobActivityLogTable();
    
    // Initialize tabs
    $('.oo-tab-button').on('click', function() {
        var tabId = $(this).data('tab');
        
        console.log('[JOB_DETAILS_DEBUG] Tab clicked:', tabId);
        
        // Update active states
        $('.oo-tab-button').removeClass('active');
        $(this).addClass('active');
        
        $('.oo-tab-panel').removeClass('active');
        $('#' + tabId).addClass('active');
        
        // Load logs for the newly active tab if not already loaded
        var $panel = $('#' + tabId);
        console.log('[JOB_DETAILS_DEBUG] Panel found:', $panel.length > 0);
        console.log('[JOB_DETAILS_DEBUG] Logs already loaded:', $panel.data('logs-loaded'));
        
        if (!$panel.data('logs-loaded')) {
            loadLogsForTab($panel);
        }
    });
    
    // Load logs for the initially active tab
    var $activePanel = $('.oo-tab-panel.active');
    console.log('[JOB_DETAILS_DEBUG] Found active panel on load:', $activePanel.length > 0);
    if ($activePanel.length) {
        console.log('[JOB_DETAILS_DEBUG] Loading logs for initially active panel');
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
        
        console.log('[JOB_DETAILS_DEBUG] Starting loadLogsForTab for stream:', streamId);
        console.log('[JOB_DETAILS_DEBUG] Table found:', $table.length > 0);
        console.log('[JOB_DETAILS_DEBUG] Table ID will be:', tableId);
        console.log('[JOB_DETAILS_DEBUG] jQuery DataTables available:', typeof $.fn.DataTable !== 'undefined');
        console.log('[JOB_DETAILS_DEBUG] oo_job_details_data:', oo_job_details_data);
        
        // Set unique ID for this table
        $table.attr('id', tableId);
        
        // Destroy existing DataTable if it exists
        if ($.fn.DataTable.isDataTable('#' + tableId)) {
            console.log('[JOB_DETAILS_DEBUG] Destroying existing DataTable');
            $('#' + tableId).DataTable().destroy();
        }
        
        // Initialize DataTable with the same system as stream dashboard
        console.log('[JOB_DETAILS_DEBUG] About to initialize DataTable');
        
        try {
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
                        
                        console.log('[JOB_DETAILS_DEBUG] Sending job details logs request data:', d);
                        return d;
                    },
                    dataSrc: function(json) {
                        console.log('[JOB_DETAILS_DEBUG] Received job details logs response:', json);
                        if (json && json.success === true && json.data && json.data.data) {
                            console.log('[JOB_DETAILS_DEBUG] Extracted data array:', json.data.data);
                            return json.data.data;
                        }
                        console.log('[JOB_DETAILS_DEBUG] No data found or wrong format');
                        return json.data || [];
                    },
                    error: function(xhr, error, thrown) {
                        console.error('[JOB_DETAILS_DEBUG] DataTables AJAX error:', error, thrown);
                        console.error('[JOB_DETAILS_DEBUG] Response text:', xhr.responseText);
                        console.error('[JOB_DETAILS_DEBUG] Response status:', xhr.status);
                        alert('Error loading job log data: ' + error + '\nCheck console for details.');
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
        
        console.log('[JOB_DETAILS_DEBUG] DataTable initialized successfully');
        
        // Store table reference and mark as loaded
        $panel.data('logs-table', logsTable);
        $panel.data('logs-loaded', true);
        
        // Apply filters functionality
        $panel.find('.oo-apply-log-filters').off('click').on('click', function() {
            console.log('[JOB_DETAILS_DEBUG] Apply filters clicked');
            logsTable.ajax.reload();
        });
        
        // Reset filters functionality
        $panel.find('.oo-reset-log-filters').off('click').on('click', function() {
            console.log('[JOB_DETAILS_DEBUG] Reset filters clicked');
            $panel.find('.oo-log-filter').val('');
            logsTable.ajax.reload();
        });
        
        } catch (error) {
            console.error('[JOB_DETAILS_DEBUG] Error initializing DataTable:', error);
            alert('Failed to initialize job logs table: ' + error.message);
        }
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
     * Initialize the job activity log DataTable
     */
    function initializeJobActivityLogTable() {
        console.log('[JOB_DETAILS_DEBUG] Initializing job activity log table');
        
        if (!oo_job_details_data.job_id) {
            console.log('[JOB_DETAILS_DEBUG] No job ID available, skipping activity log table');
            return;
        }
        
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('[JOB_DETAILS_DEBUG] DataTables not loaded');
            return;
        }
        
        var $table = $('#oo-job-activity-log-table');
        if ($table.length === 0) {
            console.log('[JOB_DETAILS_DEBUG] Activity log table not found in DOM');
            return;
        }
        
        console.log('[JOB_DETAILS_DEBUG] Initializing DataTable for job activity log with job_id:', oo_job_details_data.job_id);
        
        try {
            var activityLogTable = $table.DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: oo_job_details_data.ajax_url,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'oo_job_details_get_master_activity_log';
                        d.nonce = oo_job_details_data.master_log_nonce;
                        d.job_id = oo_job_details_data.job_id; // Filter by current job
                        
                        console.log('[JOB_DETAILS_DEBUG] Sending activity log request:', d);
                        return d;
                    },
                    dataSrc: function(json) {
                        console.log('[JOB_DETAILS_DEBUG] Received activity log response:', json);
                        if (json && json.data) {
                            return json.data;
                        }
                        return [];
                    },
                    error: function(xhr, error, thrown) {
                        console.error('[JOB_DETAILS_DEBUG] Activity log AJAX error:', error, thrown);
                        console.error('[JOB_DETAILS_DEBUG] Response text:', xhr.responseText);
                    }
                },
                columns: [
                    { 
                        data: 'created_at', 
                        title: 'Date/Time',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                var date = new Date(data);
                                return date.toLocaleString();
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'activity_type', 
                        title: 'Activity Type',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                return data.replace(/_/g, ' ');
                            }
                            return data;
                        }
                    },
                    { data: 'activity_level', title: 'Level' },
                    { data: 'user_display_name', title: 'User', defaultContent: 'Unknown' },
                    { data: 'stream_name', title: 'Stream', defaultContent: '-' },
                    { data: 'field_name', title: 'Field', defaultContent: '-' },
                    { data: 'old_value', title: 'Old Value', defaultContent: '-' },
                    { data: 'new_value', title: 'New Value', defaultContent: '-' },
                    { data: 'user_notes', title: 'Notes', defaultContent: '-' },
                    { data: 'activity_category', title: 'Category', defaultContent: '-' }
                ],
                order: [[0, 'desc']], // Order by date descending (newest first)
                pageLength: 25,
                responsive: true,
                language: {
                    emptyTable: "No activity logs found for this job",
                    zeroRecords: "No activity logs match the current filters",
                    processing: "Loading activity logs..."
                }
            });
            
            console.log('[JOB_DETAILS_DEBUG] Job activity log DataTable initialized successfully');
            
        } catch (error) {
            console.error('[JOB_DETAILS_DEBUG] Error initializing activity log DataTable:', error);
        }
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