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
            this.bindDragEvents();
            this.bindModalEvents();
            this.bindFormEvents();
        },
        
        /**
         * Bind drag and drop events
         */
        bindDragEvents: function() {
            const self = this;
            
            // Make tasks draggable
            $('.kanban-task').each(function() {
                this.addEventListener('dragstart', self.handleDragStart.bind(self));
                this.addEventListener('dragend', self.handleDragEnd.bind(self));
            });
            
            // Make columns droppable
            $('.kanban-block').each(function() {
                this.addEventListener('dragover', self.handleDragOver.bind(self));
                this.addEventListener('drop', self.handleDrop.bind(self));
                this.addEventListener('dragenter', self.handleDragEnter.bind(self));
                this.addEventListener('dragleave', self.handleDragLeave.bind(self));
            });
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
            
            $.ajax({
                url: oo_kanban_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'oo_kanban_phase_change',
                    nonce: oo_kanban_data.nonce,
                    job_stream_id: jobStreamId,
                    from_phase_id: fromPhaseId,
                    to_phase_id: toPhaseId,
                    notes: note
                },
                success: function(response) {
                    if (!response.success) {
                        // Revert the visual change
                        self.revertPhaseChange(jobStreamId, fromPhaseId, toPhaseId);
                        alert(response.data.message || oo_kanban_data.strings.error_updating);
                    } else {
                        // Show success feedback
                        self.showSuccessNotification(response.data.message);
                    }
                },
                error: function() {
                    // Revert the visual change
                    self.revertPhaseChange(jobStreamId, fromPhaseId, toPhaseId);
                    alert(oo_kanban_data.strings.server_error);
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
            $('.oo-close-modal, .cancel-note').on('click', function() {
                $(this).closest('.oo-modal').hide();
                // Clear pending phase change if closing phase change modal
                if ($(this).closest('#kanban-phase-change-modal').length) {
                    self.pendingPhaseChange = null;
                }
            });
            
            // Close modal on outside click
            $('.oo-modal').on('click', function(e) {
                if ($(e.target).hasClass('oo-modal')) {
                    $(this).hide();
                }
            });
        },
        
        /**
         * Show phase change modal
         */
        showPhaseChangeModal: function() {
            const $modal = $('#kanban-phase-change-modal');
            const data = this.pendingPhaseChange;
            
            // Set the target phase name
            $('#phase-change-target').text(data.toPhaseName);
            
            // Clear the note field
            $('#phase-change-note').val('').focus();
            
            // Show the modal
            $modal.fadeIn();
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
            $('#kanban-phase-change-form').on('submit', function(e) {
                e.preventDefault();
                
                const note = $('#phase-change-note').val().trim();
                if (!note) {
                    alert('Please enter a note for this phase change.');
                    return;
                }
                
                const data = self.pendingPhaseChange;
                
                // Hide modal
                $('#kanban-phase-change-modal').hide();
                
                // Move the card visually (optimistic update)
                data.$dropZone.find('.kanban-block-body').append(data.$task);
                
                // Update phase counts
                self.updatePhaseCounts(data.fromPhaseId, data.toPhaseId);
                
                // Send AJAX request
                self.sendPhaseChangeRequest(data.jobStreamId, data.fromPhaseId, data.toPhaseId, note);
            });
            
            // Cancel phase change
            $('.cancel-phase-change').on('click', function() {
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