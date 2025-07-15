/**
 * Reusable Email Repeater Widget for Operations Organizer
 * 
 * This widget provides email repeater functionality that can be attached to any container.
 * It follows the architectural patterns established for the autocomplete and phone repeater features.
 */

(function($) {
    'use strict';

    // Global namespace for email repeater instances
    window.OO_EmailRepeater = window.OO_EmailRepeater || {};

    /**
     * Email Repeater Widget Constructor
     * 
     * @param {Object} config Configuration object
     * @param {string} config.container_id - ID of the container element
     * @param {string} config.field_name - Base name for the form fields (e.g., 'email_addresses')
     * @param {Array} config.initial_data - Initial email data array
     * @param {string} config.add_button_text - Text for "Add Email" button (optional)
     * @param {boolean} config.show_labels - Whether to show field labels (default: true)
     * @param {number} config.max_emails - Maximum number of emails allowed (default: 10)
     * @param {string} config.container_class - Additional CSS class for the container
     */
    function EmailRepeaterWidget(config) {
        this.config = $.extend({
            add_button_text: 'Add Email',
            show_labels: true,
            max_emails: 10,
            initial_data: [],
            container_class: ''
        }, config);

        this.$container = $('#' + this.config.container_id);
        this.$emailList = null;
        this.$addButton = null;
        this.emailCount = 0;
        this.isInitialized = false;

        // Validate required config
        if (!this.config.container_id || !this.config.field_name) {
            console.error('OO Email Repeater: Missing required configuration (container_id, field_name)');
            return;
        }

        if (!this.$container.length) {
            console.error('OO Email Repeater: Container element not found:', this.config.container_id);
            return;
        }

        console.log('OO Email Repeater: Full config received:', this.config);

        this.init();
    }

    /**
     * Initialize the email repeater widget
     */
    EmailRepeaterWidget.prototype.init = function() {
        if (this.isInitialized) {
            return;
        }

        this.setupDOM();
        this.bindEvents();
        this.loadInitialData();
        this.isInitialized = true;

        console.log('OO Email Repeater: Initialized for #' + this.config.container_id);
    };

    /**
     * Set up the DOM structure
     */
    EmailRepeaterWidget.prototype.setupDOM = function() {
        var $wrapper = this.$container.find('.oo-email-repeater-container');
        
        // Add container class if specified
        if (this.config.container_class) {
            $wrapper.addClass(this.config.container_class);
            console.log('OO Email Repeater: Added container class:', this.config.container_class);
            console.log('OO Email Repeater: Container classes now:', $wrapper.attr('class'));
        }

        this.$emailList = $wrapper.find('.oo-email-list');
        this.$addButton = $wrapper.find('.oo-email-add-btn');

        // Update button text
        this.$addButton.find('span:not(.dashicons)').text(this.config.add_button_text);
    };

    /**
     * Bind event handlers
     */
    EmailRepeaterWidget.prototype.bindEvents = function() {
        var self = this;

        // Add email button click
        this.$addButton.on('click', function(e) {
            e.preventDefault();
            self.addEmail();
        });

        // Handle remove button clicks (delegated)
        this.$emailList.on('click', '.oo-email-remove-btn', function(e) {
            e.preventDefault();
            self.removeEmail($(this).closest('.oo-email-row'));
        });

        // Handle email input changes for validation
        this.$emailList.on('blur', '.oo-email-input', function() {
            self.validateEmail($(this));
        });
    };

    /**
     * Load initial data
     */
    EmailRepeaterWidget.prototype.loadInitialData = function() {
        if (this.config.initial_data && this.config.initial_data.length > 0) {
            for (var i = 0; i < this.config.initial_data.length; i++) {
                this.addEmail(this.config.initial_data[i]);
            }
        } else {
            this.showEmptyState();
        }
    };

    /**
     * Add a new email row
     * @param {Object} emailData - Optional email data to populate
     */
    EmailRepeaterWidget.prototype.addEmail = function(emailData) {
        if (this.emailCount >= this.config.max_emails) {
            alert('Maximum of ' + this.config.max_emails + ' emails allowed.');
            return;
        }

        emailData = emailData || {};
        var emailIndex = this.emailCount++;

        var $emailRow = this.createEmailRow(emailIndex, emailData);
        this.$emailList.append($emailRow);

        this.hideEmptyState();
        this.updateAddButtonState();
    };

    /**
     * Create a new email row element
     * @param {number} index - Email index
     * @param {Object} emailData - Email data
     * @return {jQuery} Email row element
     */
    EmailRepeaterWidget.prototype.createEmailRow = function(index, emailData) {
        var fieldName = this.config.field_name;
        var showLabels = this.config.show_labels;
        
        var $row = $('<div class="oo-email-row">');
        
        var $grid = $('<div class="oo-email-row-grid">');
        var $fieldsContainer = $('<div class="oo-email-fields-container">');
        
        // Email field
        var $emailGroup = $('<div class="oo-email-field-group">');
        if (showLabels) {
            $emailGroup.append('<label>Email</label>');
        }
        $emailGroup.append(
            '<input type="email" class="oo-email-input" name="' + fieldName + '[' + index + '][email]" value="' + 
            (emailData.email || '') + '" placeholder="email@example.com" required>'
        );
        $fieldsContainer.append($emailGroup);
        
        // Note field
        var $noteGroup = $('<div class="oo-email-field-group">');
        if (showLabels) {
            $noteGroup.append('<label>Note</label>');
        }
        $noteGroup.append(
            '<textarea class="oo-email-note" name="' + fieldName + '[' + index + '][note]" placeholder="Optional note" rows="2">' + 
            (emailData.note || '') + '</textarea>'
        );
        $fieldsContainer.append($noteGroup);
        
        $grid.append($fieldsContainer);
        
        // Remove button
        var $removeBtn = $('<button type="button" class="oo-email-remove-btn button-link-delete" title="Remove Email">×</button>');
        $grid.append($removeBtn);
        
        $row.append($grid);
        
        return $row;
    };

    /**
     * Remove an email row
     * @param {jQuery} $row - Email row to remove
     */
    EmailRepeaterWidget.prototype.removeEmail = function($row) {
        $row.remove();
        this.emailCount--;
        
        if (this.emailCount === 0) {
            this.showEmptyState();
        }
        
        this.updateAddButtonState();
    };

    /**
     * Validate email field
     * @param {jQuery} $input - Email input element
     */
    EmailRepeaterWidget.prototype.validateEmail = function($input) {
        var email = $input.val().trim();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            $input.addClass('oo-email-invalid');
            $input.attr('title', 'Please enter a valid email address');
        } else {
            $input.removeClass('oo-email-invalid');
            $input.removeAttr('title');
        }
    };

    /**
     * Show empty state message
     */
    EmailRepeaterWidget.prototype.showEmptyState = function() {
        if (this.$emailList.find('.oo-email-empty-state').length === 0) {
            this.$emailList.append('<div class="oo-email-empty-state">No emails added yet.</div>');
        }
    };

    /**
     * Hide empty state message
     */
    EmailRepeaterWidget.prototype.hideEmptyState = function() {
        this.$emailList.find('.oo-email-empty-state').remove();
    };

    /**
     * Update add button state based on email count
     */
    EmailRepeaterWidget.prototype.updateAddButtonState = function() {
        if (this.emailCount >= this.config.max_emails) {
            this.$addButton.prop('disabled', true);
            this.$addButton.find('span:not(.dashicons)').text('Maximum emails reached');
        } else {
            this.$addButton.prop('disabled', false);
            this.$addButton.find('span:not(.dashicons)').text(this.config.add_button_text);
        }
    };

    /**
     * Get current email data
     * @return {Array} Array of email objects
     */
    EmailRepeaterWidget.prototype.getEmailData = function() {
        var emails = [];
        
        this.$emailList.find('.oo-email-row').each(function() {
            var $row = $(this);
            var email = $row.find('.oo-email-input').val().trim();
            var note = $row.find('.oo-email-note').val().trim();
            
            if (email) {
                emails.push({
                    email: email,
                    note: note
                });
            }
        });
        
        return emails;
    };

    /**
     * Clear all emails
     */
    EmailRepeaterWidget.prototype.clearEmails = function() {
        this.$emailList.empty();
        this.emailCount = 0;
        this.showEmptyState();
        this.updateAddButtonState();
    };

    /**
     * Set email data
     * @param {Array} emails - Array of email objects
     */
    EmailRepeaterWidget.prototype.setEmailData = function(emails) {
        this.clearEmails();
        
        if (emails && emails.length > 0) {
            for (var i = 0; i < emails.length; i++) {
                this.addEmail(emails[i]);
            }
        }
    };

    // Export to global namespace
    window.OOEmailRepeater = EmailRepeaterWidget;
    window.OO_EmailRepeater.instances = window.OO_EmailRepeater.instances || {};

})(jQuery); 