/**
 * Reusable Phone Repeater Widget for Operations Organizer
 * 
 * This widget provides phone number repeater functionality that can be attached to any container.
 * It follows the architectural patterns established for the autocomplete feature.
 */

(function($) {
    'use strict';

    // Global namespace for phone repeater instances
    window.OO_PhoneRepeater = window.OO_PhoneRepeater || {};

    /**
     * Phone Repeater Widget Constructor
     * 
     * @param {Object} config Configuration object
     * @param {string} config.container_selector - jQuery selector for the container element
     * @param {string} config.field_name - Base name for the form fields (e.g., 'phone_numbers')
     * @param {Array} config.initial_data - Initial phone data array
     * @param {string} config.add_button_text - Text for "Add Phone" button (optional)
     * @param {boolean} config.allow_primary - Whether to allow primary phone selection (default: true)
     * @param {boolean} config.show_labels - Whether to show field labels (default: true)
     * @param {number} config.max_phones - Maximum number of phones allowed (default: 10)
     */
    function PhoneRepeaterWidget(config) {
        this.config = $.extend({
            add_button_text: 'Add Phone Number',
            allow_primary: true,
            show_labels: true,
            max_phones: 10,
            initial_data: []
        }, config);

        this.$container = $(this.config.container_selector);
        this.$phoneList = null;
        this.$addButton = null;
        this.phoneCount = 0;
        this.isInitialized = false;

        // Validate required config
        if (!this.config.container_selector || !this.config.field_name) {
            console.error('OO Phone Repeater: Missing required configuration (container_selector, field_name)');
            return;
        }

        if (!this.$container.length) {
            console.error('OO Phone Repeater: Container element not found:', this.config.container_selector);
            return;
        }

        this.init();
    }

    /**
     * Initialize the phone repeater widget
     */
    PhoneRepeaterWidget.prototype.init = function() {
        if (this.isInitialized) {
            console.warn('OO Phone Repeater: Widget already initialized for', this.config.container_selector);
            return;
        }

        this.setupDOM();
        this.bindEvents();
        this.loadInitialData();
        this.isInitialized = true;

        console.log('OO Phone Repeater: Initialized for', this.config.container_selector);
    };

    /**
     * Set up the DOM structure
     */
    PhoneRepeaterWidget.prototype.setupDOM = function() {
        console.log('OO Phone Repeater: Full config received:', this.config);
        
        // Add CSS class to container
        this.$container.addClass('oo-phone-repeater-container');
        
        // Add any additional container classes
        if (this.config.container_class) {
            this.$container.addClass(this.config.container_class);
            console.log('OO Phone Repeater: Added container class:', this.config.container_class);
            console.log('OO Phone Repeater: Container classes now:', this.$container.attr('class'));
        } else {
            console.log('OO Phone Repeater: No container_class found in config');
        }

        // Create phone list container
        this.$phoneList = $('<div class="oo-phone-list"></div>');
        this.$container.append(this.$phoneList);

        // Create add button
        this.$addButton = $('<button type="button" class="oo-phone-add-btn">' + 
                           this.escapeHtml(this.config.add_button_text) + '</button>');
        this.$container.append(this.$addButton);
    };

    /**
     * Bind event handlers
     */
    PhoneRepeaterWidget.prototype.bindEvents = function() {
        var self = this;

        // Add phone button click
        this.$addButton.on('click', function(e) {
            e.preventDefault();
            self.addPhoneRow();
        });

        // Delegate events for dynamic elements
        this.$container.on('click', '.oo-phone-remove-btn', function(e) {
            e.preventDefault();
            self.removePhoneRow($(this).closest('.oo-phone-row'));
        });

        // Handle primary phone selection
        if (this.config.allow_primary) {
            this.$container.on('change', '.oo-phone-primary-checkbox', function() {
                self.handlePrimarySelection($(this));
            });
        }

        // Update JSON on any field change
        this.$container.on('input change', '.oo-phone-field-group input', function() {
            self.updateJSON();
        });
    };

    /**
     * Load initial data
     */
    PhoneRepeaterWidget.prototype.loadInitialData = function() {
        if (this.config.initial_data && this.config.initial_data.length > 0) {
            var self = this;
            $.each(this.config.initial_data, function(index, phoneData) {
                self.addPhoneRow(phoneData);
            });
        } else {
            // Add one empty row by default
            this.addPhoneRow();
        }
    };

    /**
     * Add a phone row
     */
    PhoneRepeaterWidget.prototype.addPhoneRow = function(phoneData) {
        if (this.phoneCount >= this.config.max_phones) {
            alert('Maximum number of phone numbers reached (' + this.config.max_phones + ')');
            return;
        }

        phoneData = phoneData || {};
        var rowId = 'phone_row_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        var fieldBaseName = this.config.field_name + '[' + this.phoneCount + ']';

        var html = '<div class="oo-phone-row" data-row-id="' + rowId + '">';
        html += '<div class="oo-phone-row-grid">';
        
        // Main fields container
        html += '<div class="oo-phone-fields-container">';
        
        // Phone Number field
        html += '<div class="oo-phone-field-group">';
        if (this.config.show_labels) {
            html += '<label>Number</label>';
        }
        html += '<input type="tel" name="' + fieldBaseName + '[number]" ' +
                'value="' + this.escapeHtml(phoneData.number || '') + '" ' +
                'placeholder="Phone number" required />';
        html += '</div>';

        // Extension field
        html += '<div class="oo-phone-field-group">';
        if (this.config.show_labels) {
            html += '<label>Extension</label>';
        }
        html += '<input type="text" name="' + fieldBaseName + '[extension]" ' +
                'value="' + this.escapeHtml(phoneData.extension || '') + '" ' +
                'placeholder="Ext." />';
        html += '</div>';

        // Phone Type field
        html += '<div class="oo-phone-field-group">';
        if (this.config.show_labels) {
            html += '<label>Type</label>';
        }
        html += '<input type="text" name="' + fieldBaseName + '[type]" ' +
                'value="' + this.escapeHtml(phoneData.type || '') + '" ' +
                'placeholder="e.g., Office, Mobile" />';
        html += '</div>';
        
        html += '</div>'; // Close oo-phone-fields-container

        // Actions column with remove button and primary checkbox
        html += '<div class="oo-phone-actions">';
        html += '<button type="button" class="oo-phone-remove-btn" title="Remove this phone number">×</button>';
        
        // Primary checkbox under the remove button
        if (this.config.allow_primary) {
            html += '<div class="oo-phone-primary-field">';
            html += '<input type="checkbox" class="oo-phone-primary-checkbox" ' +
                    'name="' + fieldBaseName + '[is_primary]" ' +
                    'value="1" ' + (phoneData.is_primary ? 'checked' : '') + ' />';
            html += '<span>Primary</span>';
            html += '</div>';
        }
        html += '</div>';
        
        html += '</div>'; // Close oo-phone-row-grid

        html += '</div>'; // Close oo-phone-row

        var $row = $(html);
        this.$phoneList.append($row);
        this.phoneCount++;

        // If this is the first row and primary is enabled, make it primary
        if (this.config.allow_primary && this.phoneCount === 1) {
            $row.find('.oo-phone-primary-checkbox').prop('checked', true);
        }

        this.updateAddButtonState();
        this.updateJSON();
    };

    /**
     * Remove a phone row
     */
    PhoneRepeaterWidget.prototype.removePhoneRow = function($row) {
        var wasPrimary = $row.find('.oo-phone-primary-checkbox').is(':checked');
        
        $row.remove();
        this.phoneCount--;

        // If we removed the primary phone, make the first remaining phone primary
        if (wasPrimary && this.config.allow_primary) {
            this.$phoneList.find('.oo-phone-primary-checkbox').first().prop('checked', true);
        }

        // Ensure we always have at least one row
        if (this.phoneCount === 0) {
            this.addPhoneRow();
        }

        this.updateAddButtonState();
        this.updateJSON();
    };

    /**
     * Handle primary phone selection
     */
    PhoneRepeaterWidget.prototype.handlePrimarySelection = function($checkbox) {
        if ($checkbox.is(':checked')) {
            // Uncheck all other primary checkboxes
            this.$phoneList.find('.oo-phone-primary-checkbox').not($checkbox).prop('checked', false);
        }
        this.updateJSON();
    };

    /**
     * Update add button state based on max phones
     */
    PhoneRepeaterWidget.prototype.updateAddButtonState = function() {
        if (this.phoneCount >= this.config.max_phones) {
            this.$addButton.prop('disabled', true).addClass('disabled');
        } else {
            this.$addButton.prop('disabled', false).removeClass('disabled');
        }
    };

    /**
     * Update JSON representation
     */
    PhoneRepeaterWidget.prototype.updateJSON = function() {
        var phoneData = this.getPhoneData();
        var jsonString = JSON.stringify(phoneData);

        // Update or create hidden field with JSON data
        var $hiddenField = this.$container.find('input[name="' + this.config.field_name + '_json"]');
        if ($hiddenField.length === 0) {
            $hiddenField = $('<input type="hidden" name="' + this.config.field_name + '_json" />');
            this.$container.append($hiddenField);
        }
        $hiddenField.val(jsonString);

        // Trigger custom event for external listeners
        this.$container.trigger('oo-phone-repeater-updated', [phoneData]);
    };

    /**
     * Get current phone data as array
     */
    PhoneRepeaterWidget.prototype.getPhoneData = function() {
        var phoneData = [];
        var self = this;

        this.$phoneList.find('.oo-phone-row').each(function() {
            var $row = $(this);
            var phone = {
                type: $row.find('input[name*="[type]"]').val().trim(),
                number: $row.find('input[name*="[number]"]').val().trim(),
                extension: $row.find('input[name*="[extension]"]').val().trim(),
                is_primary: self.config.allow_primary ? $row.find('.oo-phone-primary-checkbox').is(':checked') : false
            };

            // Only include if number is not empty
            if (phone.number) {
                phoneData.push(phone);
            }
        });

        return phoneData;
    };

    /**
     * Set phone data programmatically
     */
    PhoneRepeaterWidget.prototype.setPhoneData = function(phoneData) {
        // Clear existing rows
        this.$phoneList.empty();
        this.phoneCount = 0;

        // Add new rows
        if (phoneData && phoneData.length > 0) {
            var self = this;
            $.each(phoneData, function(index, phone) {
                self.addPhoneRow(phone);
            });
        } else {
            this.addPhoneRow();
        }
    };

    /**
     * Destroy the widget
     */
    PhoneRepeaterWidget.prototype.destroy = function() {
        if (!this.isInitialized) {
            return;
        }

        // Remove event handlers
        this.$addButton.off('click');
        this.$container.off('click change input');

        // Remove DOM elements
        this.$phoneList.remove();
        this.$addButton.remove();
        this.$container.find('input[name="' + this.config.field_name + '_json"]').remove();

        // Remove CSS class
        this.$container.removeClass('oo-phone-repeater-container');

        this.isInitialized = false;
        console.log('OO Phone Repeater: Destroyed for', this.config.container_selector);
    };

    /**
     * Utility function to escape HTML
     */
    PhoneRepeaterWidget.prototype.escapeHtml = function(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    };

    // Expose the constructor globally
    window.OO_PhoneRepeater.Widget = PhoneRepeaterWidget;

    // Helper function to create and initialize a widget
    window.OO_PhoneRepeater.create = function(config) {
        return new PhoneRepeaterWidget(config);
    };

})(jQuery); 