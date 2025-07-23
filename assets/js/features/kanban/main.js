/**
 * Kanban Board JavaScript
 * 
 * Handles drag-and-drop functionality and AJAX interactions
 * for the Operations Organizer Kanban board feature
 */

(function($) {
    'use strict';
    
    // Only initialize if we're on a Kanban tab
    if (typeof oo_kanban_data === 'undefined') {
        return;
    }
    
    /**
     * Kanban Board Manager
     */
    const KanbanBoard = {
        
        // Properties
        draggedTask: null,
        fromPhaseId: null,
        
        /**
         * Initialize the Kanban board
         */
        init: function() {
            console.log('[KANBAN_DEBUG] Initializing Kanban board');
            
            // Prevent multiple initializations
            if (this.initialized) {
                console.log('[KANBAN_DEBUG] Already initialized, skipping');
                return;
            }
            
            this.bindDragEvents();
            this.bindModalEvents();
            this.bindFormEvents();
            
            this.initialized = true;
            console.log('[KANBAN_DEBUG] Kanban board initialized');
        },
        
        /**
         * Bind drag and drop events
         */
        bindDragEvents: function() {
            const self = this;
            
            console.log('[KANBAN_DEBUG] Binding drag events');
            
            // Make tasks draggable
            $('.kanban-task').each(function() {
                console.log('[KANBAN_DEBUG] Making task draggable:', $(this).data('job-stream-id'));
                this.addEventListener('dragstart', self.handleDragStart.bind(self));
                this.addEventListener('dragend', self.handleDragEnd.bind(self));
            });
            
            // Make columns droppable
            $('.kanban-block').each(function() {
                console.log('[KANBAN_DEBUG] Making block droppable:', $(this).data('phase-id'));
                this.addEventListener('dragover', self.handleDragOver.bind(self));
                this.addEventListener('drop', self.handleDrop.bind(self));
                this.addEventListener('dragenter', self.handleDragEnter.bind(self));
                this.addEventListener('dragleave', self.handleDragLeave.bind(self));
            });
            
            console.log('[KANBAN_DEBUG] Drag events bound to', $('.kanban-task').length, 'tasks and', $('.kanban-block').length, 'blocks');
        },
        
        /**
         * Handle drag start
         */
        handleDragStart: function(e) {
            this.draggedTask = e.target;
            this.fromPhaseId = $(e.target).closest('.kanban-block').data('phase-id');
            
            // Add dragging class for visual feedback
            $(e.target).addClass('dragging');
            
            // Store data for Firefox
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', e.target.innerHTML);
        },
        
        /**
         * Handle drag end
         */
        handleDragEnd: function(e) {
            // Remove dragging class
            $(e.target).removeClass('dragging');
            
            // Remove all drag-over classes
            $('.kanban-block').removeClass('drag-over');
            
            this.draggedTask = null;
            this.fromPhaseId = null;
        },
        
        /**
         * Handle drag over
         */
        handleDragOver: function(e) {
            if (e.preventDefault) {
                e.preventDefault(); // Allows us to drop
            }
            
            e.dataTransfer.dropEffect = 'move';
            return false;
        },
        
        /**
         * Handle drag enter
         */
        handleDragEnter: function(e) {
            $(e.currentTarget).addClass('drag-over');
        },
        
        /**
         * Handle drag leave
         */
        handleDragLeave: function(e) {
            // Only remove class if we're actually leaving the column
            if (e.target === e.currentTarget) {
                $(e.currentTarget).removeClass('drag-over');
            }
        },
        
        /**
         * Handle drop
         */
        handleDrop: function(e) {
            if (e.stopPropagation) {
                e.stopPropagation(); // Stops some browsers from redirecting
            }
            
            const $dropZone = $(e.currentTarget);
            const toPhaseId = $dropZone.data('phase-id');
            const toPhaseName = $dropZone.data('phase-name');
            
            // Don't do anything if dropped in the same column
            if (this.fromPhaseId == toPhaseId) {
                return false;
            }
            
            // Get task data
            const $task = $(this.draggedTask);
            const jobStreamId = $task.data('job-stream-id');
            
            console.log('[KANBAN_DEBUG] Preparing to show modal for phase change');
            console.log('[KANBAN_DEBUG] From phase:', this.fromPhaseId, 'To phase:', toPhaseId);
            console.log('[KANBAN_DEBUG] Phase name:', toPhaseName);
            
            // Store data for the modal
            this.pendingPhaseChange = {
                jobStreamId: jobStreamId,
                fromPhaseId: this.fromPhaseId,
                toPhaseId: toPhaseId,
                toPhaseName: toPhaseName,
                $task: $(this.draggedTask),
                $dropZone: $dropZone
            };
            
            // Show the modal
            this.showPhaseChangeModal();
            
            return false;
        },
        
        /**
         * Send phase change AJAX request
         */
        sendPhaseChangeRequest: function(jobStreamId, fromPhaseId, toPhaseId, note) {
            const self = this;
            
            console.log('[KANBAN_DEBUG] Sending phase change request');
            console.log('[KANBAN_DEBUG] Job Stream ID:', jobStreamId);
            console.log('[KANBAN_DEBUG] From Phase ID:', fromPhaseId);
            console.log('[KANBAN_DEBUG] To Phase ID:', toPhaseId);
            console.log('[KANBAN_DEBUG] Notes:', note);
            console.log('[KANBAN_DEBUG] Notes length:', note ? note.length : 0);
            
            const ajaxData = {
                action: 'oo_kanban_phase_change',
                nonce: oo_kanban_data.nonce,
                job_stream_id: jobStreamId,
                from_phase_id: fromPhaseId,
                to_phase_id: toPhaseId,
                notes: note
            };
            
            console.log('[KANBAN_DEBUG] Full AJAX data:', ajaxData);
            
            $.ajax({
                url: oo_kanban_data.ajax_url,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    console.log('[KANBAN_DEBUG] AJAX Success Response:', response);
                    
                    if (!response.success) {
                        console.log('[KANBAN_DEBUG] Response indicates failure:', response.data);
                        // Revert the visual change
                        self.revertPhaseChange(jobStreamId, fromPhaseId, toPhaseId);
                        alert(response.data.message || oo_kanban_data.strings.error_updating);
                    } else {
                        console.log('[KANBAN_DEBUG] Phase change successful');
                        // Show success feedback
                        self.showSuccessNotification(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('[KANBAN_DEBUG] AJAX Error:', {
                        xhr: xhr,
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    
                    // Revert the visual change
                    self.revertPhaseChange(jobStreamId, fromPhaseId, toPhaseId);
                    alert(oo_kanban_data.strings.server_error + ' Details: ' + error);
                }
            });
        },
        
        /**
         * Revert phase change on error
         */
        revertPhaseChange: function(jobStreamId, fromPhaseId, toPhaseId) {
            const $task = $('[data-job-stream-id="' + jobStreamId + '"]');
            const $originalColumn = $('[data-phase-id="' + fromPhaseId + '"]');
            
            $originalColumn.find('.kanban-block-body').append($task);
            
            // Update counts back
            this.updatePhaseCounts(toPhaseId, fromPhaseId);
        },
        
        /**
         * Update phase counts
         */
        updatePhaseCounts: function(fromPhaseId, toPhaseId) {
            // Decrease from count
            const $fromCount = $('[data-phase-id="' + fromPhaseId + '"] .phase-count');
            const fromCount = parseInt($fromCount.text()) || 0;
            $fromCount.text(Math.max(0, fromCount - 1));
            
            // Increase to count
            const $toCount = $('[data-phase-id="' + toPhaseId + '"] .phase-count');
            const toCount = parseInt($toCount.text()) || 0;
            $toCount.text(toCount + 1);
        },
        
        /**
         * Bind modal events
         */
        bindModalEvents: function() {
            const self = this;
            
            // Activity log button
            $(document).on('click', '.view-activity-log', function(e) {
                e.preventDefault();
                const jobStreamId = $(this).data('job-stream-id');
                self.showActivityLog(jobStreamId);
            });
            
            // Add note button
            $(document).on('click', '.add-note', function(e) {
                e.preventDefault();
                const jobStreamId = $(this).data('job-stream-id');
                self.showAddNoteModal(jobStreamId);
            });
            
            // Close modal buttons
            $(document).on('click', '.oo-close-modal, .cancel-note, .cancel-phase-change', function() {
                const $modal = $(this).closest('.oo-modal');
                $modal.hide();
                
                // Clear pending phase change if closing phase change modal
                if ($modal.attr('id') === 'kanban-phase-change-modal') {
                    console.log('[KANBAN_DEBUG] Clearing pending phase change from close button');
                    self.pendingPhaseChange = null;
                }
            });
            
            // Close modal on outside click
            $('.oo-modal').on('click', function(e) {
                if ($(e.target).hasClass('oo-modal')) {
                    $(this).hide();
                    // Clear pending phase change if closing phase change modal
                    if ($(this).attr('id') === 'kanban-phase-change-modal') {
                        self.pendingPhaseChange = null;
                    }
                }
            });
        },
        
        /**
         * Show phase change modal
         */
        showPhaseChangeModal: function() {
            console.log('[KANBAN_DEBUG] showPhaseChangeModal called');
            const $modal = $('#kanban-phase-change-modal');
            const data = this.pendingPhaseChange;
            
            console.log('[KANBAN_DEBUG] Modal data:', data);
            console.log('[KANBAN_DEBUG] Modal element found:', $modal.length);
            
            if ($modal.length === 0) {
                console.log('[KANBAN_DEBUG] ERROR: Modal element not found in DOM!');
                alert('Error: Modal not found. Please refresh the page.');
                return;
            }
            
            console.log('[KANBAN_DEBUG] Modal current display:', $modal.css('display'));
            console.log('[KANBAN_DEBUG] Modal parent elements:', $modal.parents().map(function() { return this.tagName; }).get());
            
            // Ensure modal is hidden first (reset state)
            $modal.hide();
            
            // Set the target phase name
            $('#kanban-phase-change-target').text(data.toPhaseName);
            
            // Clear the note field and remove any error states
            $('#kanban-phase-change-note').val('').removeClass('error').focus();
            
            // Show the modal with a slight delay to ensure it renders
            setTimeout(function() {
                $modal.fadeIn();
                $('#kanban-phase-change-note').focus();
                console.log('[KANBAN_DEBUG] Modal should be visible now');
            }, 50);
        },
        
        /**
         * Show activity log modal
         */
        showActivityLog: function(jobStreamId) {
            const $modal = $('#kanban-activity-log-modal');
            const $content = $modal.find('.activity-log-content');
            const $loading = $modal.find('.activity-log-loading');
            
            // Show modal and loading
            $modal.show();
            $loading.show();
            $content.hide();
            
            // Fetch activity log
            $.ajax({
                url: oo_kanban_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'oo_kanban_get_activity_log',
                    nonce: oo_kanban_data.nonce,
                    job_stream_id: jobStreamId
                },
                success: function(response) {
                    if (response.success) {
                        self.renderActivityLog(response.data.activity_log);
                        $loading.hide();
                        $content.show();
                    } else {
                        alert(response.data.message);
                        $modal.hide();
                    }
                },
                error: function() {
                    alert(oo_kanban_data.strings.server_error);
                    $modal.hide();
                }
            });
        },
        
        /**
         * Render activity log entries
         */
        renderActivityLog: function(entries) {
            const $content = $('.activity-log-content');
            
            if (entries.length === 0) {
                $content.html('<p class="no-activity">No activity recorded yet.</p>');
                return;
            }
            
            let html = '<div class="activity-log-entries">';
            
            entries.forEach(function(entry) {
                html += '<div class="activity-entry activity-type-' + entry.activity_type.toLowerCase() + '">';
                html += '<div class="activity-header">';
                html += '<span class="activity-user">' + (entry.user_name || 'System') + '</span>';
                html += '<span class="activity-date">' + entry.created_at_formatted + '</span>';
                html += '</div>';
                
                if (entry.activity_type === 'PHASE_CHANGE') {
                    html += '<div class="activity-content">';
                    html += 'Moved from <strong>' + (entry.from_phase_name || 'Start') + '</strong>';
                    html += ' to <strong>' + entry.to_phase_name + '</strong>';
                    html += '</div>';
                } else if (entry.activity_type === 'NOTE_ADDED') {
                    html += '<div class="activity-content">';
                    html += '<div class="activity-note">' + entry.notes + '</div>';
                    html += '</div>';
                } else {
                    html += '<div class="activity-content">' + entry.activity_type + '</div>';
                }
                
                if (entry.notes && entry.activity_type !== 'NOTE_ADDED') {
                    html += '<div class="activity-notes">' + entry.notes + '</div>';
                }
                
                html += '</div>';
            });
            
            html += '</div>';
            $content.html(html);
        },
        
        /**
         * Show add note modal
         */
        showAddNoteModal: function(jobStreamId) {
            const $modal = $('#kanban-add-note-modal');
            
            // Reset form
            $('#kanban-add-note-form')[0].reset();
            $('#note-job-stream-id').val(jobStreamId);
            
            // Show modal
            $modal.show();
            
            // Focus on textarea
            $('#note-content').focus();
        },
        
        /**
         * Bind form events
         */
        bindFormEvents: function() {
            const self = this;
            
            // Phase change form submission
            $(document).on('submit', '#kanban-phase-change-form', function(e) {
                e.preventDefault();
                console.log('[KANBAN_DEBUG] Form submitted');
                
                const note = $('#kanban-phase-change-note').val().trim();
                console.log('[KANBAN_DEBUG] Note from form:', note);
                console.log('[KANBAN_DEBUG] Note length:', note ? note.length : 0);
                console.log('[KANBAN_DEBUG] Raw form value:', $('#kanban-phase-change-note').val());
                
                if (!note) {
                    console.log('[KANBAN_DEBUG] Note validation failed - empty note');
                    alert('Please enter a note for this phase change.');
                    $('#kanban-phase-change-note').addClass('error').focus();
                    return;
                }
                
                const data = self.pendingPhaseChange;
                console.log('[KANBAN_DEBUG] Form data:', data);
                
                if (!data) {
                    console.log('[KANBAN_DEBUG] ERROR: No pending phase change data');
                    alert('Error: Phase change data lost. Please try again.');
                    $('#kanban-phase-change-modal').hide();
                    return;
                }
                
                console.log('[KANBAN_DEBUG] About to hide modal and send request');
                
                // Hide modal
                $('#kanban-phase-change-modal').hide();
                
                // Move the card visually (optimistic update)
                data.$dropZone.find('.kanban-block-body').append(data.$task);
                
                // Update phase counts
                self.updatePhaseCounts(data.fromPhaseId, data.toPhaseId);
                
                // Send AJAX request
                console.log('[KANBAN_DEBUG] Calling sendPhaseChangeRequest with note:', note);
                self.sendPhaseChangeRequest(data.jobStreamId, data.fromPhaseId, data.toPhaseId, note);
                
                // Clear the pending change data
                self.pendingPhaseChange = null;
            });
            
            // Cancel phase change
            $(document).on('click', '.cancel-phase-change', function() {
                console.log('[KANBAN_DEBUG] Phase change cancelled');
                $('#kanban-phase-change-modal').hide();
                self.pendingPhaseChange = null;
            });
            
            // Add note form
            $('#kanban-add-note-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $submitBtn = $form.find('button[type="submit"]');
                
                // Disable submit button
                $submitBtn.prop('disabled', true);
                
                // Get form data
                const jobStreamId = $('#note-job-stream-id').val();
                const noteContent = $('#note-content').val();
                const noteType = $('#note-type').val();
                
                // Send AJAX request
                $.ajax({
                    url: oo_kanban_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'oo_kanban_add_note',
                        nonce: oo_kanban_data.nonce,
                        job_stream_id: jobStreamId,
                        note: noteContent,
                        note_type: noteType
                    },
                    success: function(response) {
                        if (response.success) {
                            self.showSuccessNotification(response.data.message);
                            $('#kanban-add-note-modal').hide();
                            $form[0].reset();
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert(oo_kanban_data.strings.server_error);
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false);
                    }
                });
            });
        },
        
        /**
         * Show success notification
         */
        showSuccessNotification: function(message) {
            // Create notification element
            const $notification = $('<div class="oo-kanban-notification success">' + message + '</div>');
            
            // Add to body
            $('body').append($notification);
            
            // Show with animation
            setTimeout(function() {
                $notification.addClass('show');
            }, 10);
            
            // Remove after 3 seconds
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        KanbanBoard.init();
    });
    
})(jQuery); 