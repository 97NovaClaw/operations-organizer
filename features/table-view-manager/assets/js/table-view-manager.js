/**
 * Table View Manager JavaScript
 */
(function($) {
    'use strict';

    // Main object for Table View Manager
    window.OOTableViewManager = {
        // Current table configuration
        currentTable: null,
        currentColumns: [],
        currentPreferences: null,
        
        // Initialize the feature
        init: function() {
            this.bindEvents();
            this.initializeDataTables();
        },
        
        // Bind UI events
        bindEvents: function() {
            var self = this;
            
            // Tab switching
            $(document).on('click', '.oo-tvm-tab-button', function() {
                var tab = $(this).data('tab');
                self.switchTab(tab);
            });
            
            // Column checkbox changes
            $(document).on('change', '.oo-tvm-column-checkbox', function() {
                self.handleColumnToggle($(this));
            });
            
            // Group checkbox changes
            $(document).on('change', '.oo-tvm-group-checkbox', function() {
                self.handleGroupToggle($(this));
            });
            
            // Group expand/collapse
            $(document).on('click', '.oo-tvm-group-header', function(e) {
                if (!$(e.target).is('input')) {
                    $(this).closest('.oo-tvm-column-group').toggleClass('collapsed');
                }
            });
            
            // Modal close
            $(document).on('click', '.oo-table-view-modal .oo-close-modal, .oo-tvm-cancel-btn', function() {
                self.closeModal();
            });
            
            // Apply changes
            $(document).on('click', '.oo-tvm-apply-btn', function() {
                self.applyChanges();
            });
            
            // Reset to default
            $(document).on('click', '.oo-tvm-reset-btn', function() {
                self.resetToDefault();
            });
            
            // Configure view button click
            $(document).on('click', '.oo-configure-view-btn', function() {
                var tableId = $(this).data('table-id');
                var $table = $('table[data-table-id="' + tableId + '"]');
                self.openModal(tableId, $table);
            });
        },
        
        // Initialize DataTables integration
        initializeDataTables: function() {
            var self = this;
            
            console.log('[TVM] Initializing DataTables integration');
            
            // Hook into DataTables initialization
            $(document).on('init.dt', function(e, settings) {
                console.log('[TVM] DataTable init event fired');
                var api = new $.fn.dataTable.Api(settings);
                var $table = $(api.table().node());
                var tableId = $table.data('table-id');
                
                console.log('[TVM] Table found:', $table.attr('id'), 'with table-id:', tableId);
                
                if (tableId) {
                    // Add Configure View button
                    var $container = $table.closest('.dataTables_wrapper');
                    console.log('[TVM] DataTables wrapper found:', $container.length > 0);
                    
                    if ($container.length && !$container.find('.oo-configure-view-btn').length) {
                        var btnHtml = '<button class="button oo-configure-view-btn" data-table-id="' + tableId + '">' +
                                     ooTableViewManager.strings.configure_view + '</button>';
                        $container.find('.dataTables_length').after(btnHtml);
                        console.log('[TVM] Configure View button added for table:', tableId);
                    }
                    // Special case for job activity log table
                    else if (tableId === 'job_details_activity_log') {
                        var $header = $table.closest('.oo-job-activity-column').find('.oo-activity-log-header');
                        if ($header.length && !$header.find('.oo-configure-view-btn').length) {
                            var btnHtml = '<button class="button oo-configure-view-btn" data-table-id="' + tableId + '">' +
                                         ooTableViewManager.strings.configure_view + '</button>';
                            // Add before the collapse button
                            $header.find('#oo-toggle-activity-log').before(btnHtml + ' ');
                            console.log('[TVM] Configure View button added to activity log header');
                        }
                    }
                    
                    // Apply saved preferences if available
                    if (ooTableViewManager.user_preferences && ooTableViewManager.user_preferences[tableId]) {
                        self.applyPreferencesToTable(api, ooTableViewManager.user_preferences[tableId]);
                    } else {
                        // Apply default column limit
                        self.applyDefaultColumnLimit(api);
                    }
                }
            });
        },
        
        // Open the configuration modal
        openModal: function(tableId, $table) {
            var self = this;
            
            this.currentTable = tableId;
            this.currentPreferences = null;
            
            // Show modal
            $('#oo-table-view-modal').show();
            
            // Load available columns
            this.loadAvailableColumns(tableId, function(columns) {
                self.currentColumns = columns;
                self.renderVisibilityTab();
                self.renderOrderTab();
            });
        },
        
        // Close the modal
        closeModal: function() {
            $('#oo-table-view-modal').hide();
            this.currentTable = null;
            this.currentColumns = [];
            this.currentPreferences = null;
        },
        
        // Switch between tabs
        switchTab: function(tab) {
            $('.oo-tvm-tab-button').removeClass('active');
            $('.oo-tvm-tab-button[data-tab="' + tab + '"]').addClass('active');
            
            $('.oo-tvm-tab-panel').removeClass('active');
            $('#oo-tvm-' + tab + '-tab').addClass('active');
            
            // Refresh order tab when switching to it
            if (tab === 'order') {
                this.renderOrderTab();
            }
        },
        
        // Load available columns via AJAX
        loadAvailableColumns: function(tableId, callback) {
            $.ajax({
                url: ooTableViewManager.ajax_url,
                type: 'POST',
                data: {
                    action: 'oo_get_table_available_columns',
                    table_id: tableId,
                    nonce: ooTableViewManager.nonce
                },
                success: function(response) {
                    if (response.success && response.data.columns) {
                        callback(response.data.columns);
                    } else {
                        console.error('Failed to load columns');
                        callback([]);
                    }
                },
                error: function() {
                    console.error('AJAX error loading columns');
                    callback([]);
                }
            });
        },
        
        // Render the visibility tab
        renderVisibilityTab: function() {
            var self = this;
            var html = '';
            var savedPrefs = this.getSavedPreferences();
            
            // Build column HTML
            this.currentColumns.forEach(function(column, index) {
                if (column.is_group) {
                    html += self.renderGroupCheckbox(column, savedPrefs, index);
                } else {
                    var checked = self.isColumnVisible(column.id, savedPrefs, index);
                    html += self.renderColumnCheckbox(column, checked);
                }
            });
            
            $('#oo-tvm-visibility-list').html(html);
            
            // Update group checkbox states
            this.updateGroupCheckboxStates();
        },
        
        // Render a single column checkbox
        renderColumnCheckbox: function(column, checked) {
            var template = $('#oo-tvm-column-checkbox-template').html();
            return template
                .replace(/{{id}}/g, column.id)
                .replace(/{{title}}/g, column.title)
                .replace(/{{checked}}/g, checked ? 'checked' : '');
        },
        
        // Render a group checkbox with children
        renderGroupCheckbox: function(group, savedPrefs, groupIndex) {
            var self = this;
            var childrenHtml = '';
            
            // Render children
            if (group.children) {
                group.children.forEach(function(child, childIndex) {
                    var checked = self.isColumnVisible(child.id, savedPrefs, groupIndex);
                    childrenHtml += self.renderColumnCheckbox(child, checked);
                });
            }
            
            var template = $('#oo-tvm-group-checkbox-template').html();
            return template
                .replace(/{{id}}/g, group.id)
                .replace(/{{title}}/g, group.title)
                .replace(/{{checked}}/g, '')  // Will be set by updateGroupCheckboxStates
                .replace(/{{children}}/g, childrenHtml);
        },
        
        // Check if a column should be visible
        isColumnVisible: function(columnId, savedPrefs, defaultIndex) {
            if (savedPrefs && savedPrefs.columns) {
                // Find column in saved preferences
                var columnIndex = this.findColumnIndex(columnId);
                if (columnIndex !== -1 && savedPrefs.columns[columnIndex]) {
                    return savedPrefs.columns[columnIndex].visible;
                }
            }
            
            // Default: show first N columns
            return defaultIndex < ooTableViewManager.default_column_limit;
        },
        
        // Find column index by ID
        findColumnIndex: function(columnId) {
            for (var i = 0; i < this.currentColumns.length; i++) {
                if (this.currentColumns[i].id === columnId) {
                    return i;
                }
                // Check in group children
                if (this.currentColumns[i].children) {
                    for (var j = 0; j < this.currentColumns[i].children.length; j++) {
                        if (this.currentColumns[i].children[j].id === columnId) {
                            return i; // Return group index for now
                        }
                    }
                }
            }
            return -1;
        },
        
        // Handle individual column toggle
        handleColumnToggle: function($checkbox) {
            // Update order tab when visibility changes
            this.updateGroupCheckboxStates();
        },
        
        // Handle group checkbox toggle
        handleGroupToggle: function($groupCheckbox) {
            var $group = $groupCheckbox.closest('.oo-tvm-column-group');
            var isChecked = $groupCheckbox.prop('checked');
            
            // Toggle all children
            $group.find('.oo-tvm-group-children .oo-tvm-column-checkbox').prop('checked', isChecked);
        },
        
        // Update group checkbox states based on children
        updateGroupCheckboxStates: function() {
            $('.oo-tvm-column-group').each(function() {
                var $group = $(this);
                var $groupCheckbox = $group.find('.oo-tvm-group-checkbox');
                var $children = $group.find('.oo-tvm-group-children .oo-tvm-column-checkbox');
                
                var checkedCount = $children.filter(':checked').length;
                var totalCount = $children.length;
                
                if (checkedCount === 0) {
                    $groupCheckbox.prop('checked', false).prop('indeterminate', false);
                } else if (checkedCount === totalCount) {
                    $groupCheckbox.prop('checked', true).prop('indeterminate', false);
                } else {
                    $groupCheckbox.prop('checked', false).prop('indeterminate', true);
                }
            });
        },
        
        // Render the order tab
        renderOrderTab: function() {
            var self = this;
            var html = '';
            var visibleColumns = this.getVisibleColumns();
            var savedOrder = this.getSavedOrder();
            
            // Sort by saved order if available
            if (savedOrder && savedOrder.length > 0) {
                visibleColumns.sort(function(a, b) {
                    var aIndex = savedOrder.indexOf(self.getColumnDataIndex(a.id));
                    var bIndex = savedOrder.indexOf(self.getColumnDataIndex(b.id));
                    
                    if (aIndex === -1) aIndex = 999;
                    if (bIndex === -1) bIndex = 999;
                    
                    return aIndex - bIndex;
                });
            }
            
            // Build sortable items
            visibleColumns.forEach(function(column) {
                html += self.renderSortableItem(column);
            });
            
            $('#oo-tvm-order-list').html(html);
            
            // Initialize sortable
            $('#oo-tvm-order-list').sortable({
                handle: '.oo-tvm-drag-handle',
                placeholder: 'oo-tvm-sortable-placeholder',
                forcePlaceholderSize: true
            });
        },
        
        // Get currently visible columns
        getVisibleColumns: function() {
            var visibleColumns = [];
            
            $('.oo-tvm-column-checkbox:checked').each(function() {
                var columnId = $(this).val();
                var column = this.findColumnById(columnId);
                if (column && !column.is_group) {
                    visibleColumns.push(column);
                }
            }.bind(this));
            
            return visibleColumns;
        },
        
        // Find column by ID
        findColumnById: function(columnId) {
            for (var i = 0; i < this.currentColumns.length; i++) {
                if (this.currentColumns[i].id === columnId) {
                    return this.currentColumns[i];
                }
                // Check in group children
                if (this.currentColumns[i].children) {
                    for (var j = 0; j < this.currentColumns[i].children.length; j++) {
                        if (this.currentColumns[i].children[j].id === columnId) {
                            return this.currentColumns[i].children[j];
                        }
                    }
                }
            }
            return null;
        },
        
        // Render a sortable item
        renderSortableItem: function(column) {
            var template = $('#oo-tvm-sortable-item-template').html();
            return template
                .replace(/{{id}}/g, column.id)
                .replace(/{{title}}/g, column.title);
        },
        
        // Apply changes
        applyChanges: function() {
            var self = this;
            
            // Validate at least one column selected
            if ($('.oo-tvm-column-checkbox:checked').length === 0) {
                alert(ooTableViewManager.strings.no_columns_selected);
                return;
            }
            
            // Build preferences object
            var preferences = this.buildPreferences();
            
            // Save via AJAX
            this.savePreferences(preferences, function() {
                // Apply to current table
                self.applyToCurrentTable(preferences);
                
                // Close modal
                self.closeModal();
            });
        },
        
        // Build preferences object from current state
        buildPreferences: function() {
            var preferences = {
                columns: [],
                order: []
            };
            
            // Build column visibility array
            this.currentColumns.forEach(function(column, index) {
                if (!column.is_group) {
                    var $checkbox = $('.oo-tvm-column-checkbox[value="' + column.id + '"]');
                    preferences.columns.push({
                        visible: $checkbox.prop('checked'),
                        search: ""
                    });
                }
            });
            
            // Build order array from sortable
            $('#oo-tvm-order-list .oo-tvm-sortable-item').each(function() {
                var columnId = $(this).data('column-id');
                var dataIndex = this.getColumnDataIndex(columnId);
                if (dataIndex !== -1) {
                    preferences.order.push(dataIndex);
                }
            }.bind(this));
            
            return preferences;
        },
        
        // Get DataTable column index for a column ID
        getColumnDataIndex: function(columnId) {
            var index = 0;
            for (var i = 0; i < this.currentColumns.length; i++) {
                if (!this.currentColumns[i].is_group) {
                    if (this.currentColumns[i].id === columnId) {
                        return index;
                    }
                    index++;
                }
            }
            return -1;
        },
        
        // Save preferences via AJAX
        savePreferences: function(preferences, callback) {
            var self = this;
            
            // Show loading state
            $('.oo-tvm-apply-btn').prop('disabled', true).text(ooTableViewManager.strings.saving);
            
            $.ajax({
                url: ooTableViewManager.ajax_url,
                type: 'POST',
                data: {
                    action: 'oo_save_table_view_preference',
                    table_id: this.currentTable,
                    preferences: JSON.stringify(preferences),
                    nonce: ooTableViewManager.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update local cache
                        if (!ooTableViewManager.user_preferences) {
                            ooTableViewManager.user_preferences = {};
                        }
                        ooTableViewManager.user_preferences[self.currentTable] = preferences;
                        
                        callback();
                    } else {
                        alert(response.data || ooTableViewManager.strings.error);
                    }
                },
                error: function() {
                    alert(ooTableViewManager.strings.error);
                },
                complete: function() {
                    $('.oo-tvm-apply-btn').prop('disabled', false).text(ooTableViewManager.strings.apply);
                }
            });
        },
        
        // Apply preferences to current DataTable
        applyToCurrentTable: function(preferences) {
            var $table = $('table[data-table-id="' + this.currentTable + '"]');
            if ($table.length && $.fn.DataTable.isDataTable($table)) {
                var api = $table.DataTable();
                this.applyPreferencesToTable(api, preferences);
            }
        },
        
        // Apply preferences to a DataTable instance
        applyPreferencesToTable: function(api, preferences) {
            // Apply column visibility
            if (preferences.columns) {
                preferences.columns.forEach(function(col, index) {
                    api.column(index).visible(col.visible);
                });
            }
            
            // Apply column order
            if (preferences.order && preferences.order.length > 0 && api.colReorder) {
                api.colReorder.order(preferences.order);
            }
        },
        
        // Apply default column limit
        applyDefaultColumnLimit: function(api) {
            var totalColumns = api.columns().count();
            var limit = ooTableViewManager.default_column_limit;
            
            // Hide columns beyond the limit
            for (var i = limit; i < totalColumns; i++) {
                api.column(i).visible(false);
            }
        },
        
        // Reset to default
        resetToDefault: function() {
            var self = this;
            
            // Delete saved preferences
            $.ajax({
                url: ooTableViewManager.ajax_url,
                type: 'POST',
                data: {
                    action: 'oo_delete_table_view_preference',
                    table_id: this.currentTable,
                    nonce: ooTableViewManager.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove from local cache
                        if (ooTableViewManager.user_preferences) {
                            delete ooTableViewManager.user_preferences[self.currentTable];
                        }
                        
                        // Re-render tabs with defaults
                        self.renderVisibilityTab();
                        self.renderOrderTab();
                    }
                }
            });
        },
        
        // Get saved preferences for current table
        getSavedPreferences: function() {
            if (ooTableViewManager.user_preferences && ooTableViewManager.user_preferences[this.currentTable]) {
                return ooTableViewManager.user_preferences[this.currentTable];
            }
            return null;
        },
        
        // Get saved column order
        getSavedOrder: function() {
            var prefs = this.getSavedPreferences();
            return prefs ? prefs.order : null;
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        OOTableViewManager.init();
        
        // Also check for already initialized DataTables
        setTimeout(function() {
            console.log('[TVM] Checking for already initialized DataTables');
            $('table[data-table-id]').each(function() {
                var $table = $(this);
                var tableId = $table.data('table-id');
                
                if ($.fn.DataTable.isDataTable($table)) {
                    console.log('[TVM] Found initialized DataTable:', tableId);
                    var $container = $table.closest('.dataTables_wrapper');
                    
                    // Check for standard DataTables wrapper
                    if ($container.length && !$container.find('.oo-configure-view-btn').length) {
                        var btnHtml = '<button class="button oo-configure-view-btn" data-table-id="' + tableId + '">' +
                                     ooTableViewManager.strings.configure_view + '</button>';
                        $container.find('.dataTables_length').after(btnHtml);
                        console.log('[TVM] Configure View button added to DataTables wrapper for table:', tableId);
                    }
                    // Special case for job activity log table
                    else if (tableId === 'job_details_activity_log') {
                        var $header = $table.closest('.oo-job-activity-column').find('.oo-activity-log-header');
                        if ($header.length && !$header.find('.oo-configure-view-btn').length) {
                            var btnHtml = '<button class="button oo-configure-view-btn" data-table-id="' + tableId + '">' +
                                         ooTableViewManager.strings.configure_view + '</button>';
                            // Add before the collapse button
                            $header.find('#oo-toggle-activity-log').before(btnHtml + ' ');
                            console.log('[TVM] Configure View button added to activity log header');
                        }
                    }
                    
                    // Apply saved preferences if available
                    var api = $table.DataTable();
                    if (ooTableViewManager.user_preferences && ooTableViewManager.user_preferences[tableId]) {
                        OOTableViewManager.applyPreferencesToTable(api, ooTableViewManager.user_preferences[tableId]);
                    } else {
                        OOTableViewManager.applyDefaultColumnLimit(api);
                    }
                }
            });
        }, 1000); // Wait a bit for DataTables to initialize
    });
    
})(jQuery); 