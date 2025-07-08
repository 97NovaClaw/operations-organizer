/**
 * Reusable Autocomplete Widget for Operations Organizer
 * 
 * This widget provides autocomplete functionality that can be attached to any input field.
 * It follows the architectural patterns established in lessons-and-issues.txt.
 */

(function($) {
    'use strict';

    // Global namespace for autocomplete instances
    window.OO_Autocomplete = window.OO_Autocomplete || {};
    
    // Global registry for callback functions
    window.OO_Autocomplete_Callbacks = window.OO_Autocomplete_Callbacks || {};

    /**
     * Autocomplete Widget Constructor
     * 
     * @param {Object} config Configuration object
     * @param {string} config.input_selector - jQuery selector for the input field
     * @param {string} config.ajax_action - WordPress AJAX action to call
     * @param {string} config.render_item_callback - Name of function to render each item
     * @param {string} config.on_select_callback - Name of function to call when item selected
     * @param {string} config.on_add_new_callback - Name of function to call when "Add New" clicked
     * @param {string} config.nonce - Security nonce for AJAX requests
     * @param {string} config.add_new_text - Text for "Add New" button (optional)
     * @param {string} config.placeholder - Placeholder text for input (optional)
     * @param {number} config.min_chars - Minimum characters before search (default: 2)
     * @param {number} config.delay - Delay in ms before search (default: 300)
     */
    function AutocompleteWidget(config) {
        this.config = $.extend({
            min_chars: 2,
            delay: 300,
            add_new_text: 'Add New Item',
            placeholder: 'Search...'
        }, config);

        this.$input = $(this.config.input_selector);
        this.$container = null;
        this.$suggestions = null;
        this.searchTimeout = null;
        this.isInitialized = false;

        // Validate required config
        if (!this.config.input_selector || !this.config.ajax_action) {
            console.error('OO Autocomplete: Missing required configuration (input_selector, ajax_action)');
            return;
        }

        if (!this.$input.length) {
            console.error('OO Autocomplete: Input element not found:', this.config.input_selector);
            return;
        }

        this.init();
    }

    /**
     * Initialize the autocomplete widget
     */
    AutocompleteWidget.prototype.init = function() {
        if (this.isInitialized) {
            console.warn('OO Autocomplete: Widget already initialized for', this.config.input_selector);
            return;
        }

        this.setupDOM();
        this.bindEvents();
        this.isInitialized = true;

        console.log('OO Autocomplete: Initialized for', this.config.input_selector);
    };

    /**
     * Set up the DOM structure
     */
    AutocompleteWidget.prototype.setupDOM = function() {
        // Wrap input in container if not already wrapped
        if (!this.$input.parent().hasClass('oo-autocomplete-container')) {
            this.$input.wrap('<div class="oo-autocomplete-container"></div>');
        }
        
        this.$container = this.$input.parent('.oo-autocomplete-container');
        
        // Create suggestions container
        this.$suggestions = $('<div class="oo-autocomplete-suggestions" style="display: none;"></div>');
        this.$container.append(this.$suggestions);

        // Set placeholder if provided
        if (this.config.placeholder) {
            this.$input.attr('placeholder', this.config.placeholder);
        }
    };

    /**
     * Bind event handlers
     */
    AutocompleteWidget.prototype.bindEvents = function() {
        var self = this;

        // Input events
        this.$input.on('input.oo-autocomplete', function() {
            self.handleInput();
        });

        this.$input.on('focus.oo-autocomplete', function() {
            self.handleFocus();
        });

        this.$input.on('blur.oo-autocomplete', function(e) {
            // Delay hiding to allow for clicks on suggestions
            setTimeout(function() {
                self.hideSuggestions();
            }, 200);
        });

        // Keyboard navigation
        this.$input.on('keydown.oo-autocomplete', function(e) {
            self.handleKeydown(e);
        });

        // Click outside to close
        $(document).on('click.oo-autocomplete-' + this.config.input_selector.replace(/[^a-zA-Z0-9]/g, ''), function(e) {
            if (!self.$container.is(e.target) && self.$container.has(e.target).length === 0) {
                self.hideSuggestions();
            }
        });
    };

    /**
     * Handle input changes
     */
    AutocompleteWidget.prototype.handleInput = function() {
        var query = this.$input.val().trim();
        
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        if (query.length < this.config.min_chars) {
            this.hideSuggestions();
            return;
        }

        // Debounce the search
        var self = this;
        this.searchTimeout = setTimeout(function() {
            self.performSearch(query);
        }, this.config.delay);
    };

    /**
     * Handle input focus
     */
    AutocompleteWidget.prototype.handleFocus = function() {
        var query = this.$input.val().trim();
        if (query.length >= this.config.min_chars) {
            this.performSearch(query);
        }
    };

    /**
     * Handle keyboard navigation
     */
    AutocompleteWidget.prototype.handleKeydown = function(e) {
        if (!this.$suggestions.is(':visible')) {
            return;
        }

        var $items = this.$suggestions.find('.oo-autocomplete-suggestion');
        var $selected = $items.filter('.selected');

        switch (e.keyCode) {
            case 38: // Up arrow
                e.preventDefault();
                if ($selected.length === 0) {
                    $items.last().addClass('selected');
                } else {
                    $selected.removeClass('selected').prev().addClass('selected');
                }
                break;

            case 40: // Down arrow
                e.preventDefault();
                if ($selected.length === 0) {
                    $items.first().addClass('selected');
                } else {
                    $selected.removeClass('selected').next().addClass('selected');
                }
                break;

            case 13: // Enter
                e.preventDefault();
                if ($selected.length > 0) {
                    $selected.click();
                }
                break;

            case 27: // Escape
                e.preventDefault();
                this.hideSuggestions();
                break;
        }
    };

    /**
     * Perform AJAX search
     */
    AutocompleteWidget.prototype.performSearch = function(query) {
        var self = this;

        // Show loading state
        this.showLoading();

        // Prepare AJAX data
        var ajaxData = {
            action: this.config.ajax_action,
            query: query,
            nonce: this.config.nonce
        };

        // Add any additional context if provided
        if (this.config.context) {
            $.extend(ajaxData, this.config.context);
        }

        $.ajax({
            url: oo_data.ajax_url,
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            success: function(response) {
                self.handleSearchResponse(response, query);
            },
            error: function(xhr, status, error) {
                console.error('OO Autocomplete: AJAX error', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                self.showError('Search request failed. Please try again.');
            }
        });
    };

    /**
     * Handle search response
     */
    AutocompleteWidget.prototype.handleSearchResponse = function(response, query) {
        if (response.success && response.data) {
            this.displaySuggestions(response.data, query);
        } else {
            console.error('OO Autocomplete: Search failed', response);
            this.showError(response.data ? response.data.message : 'Search failed');
        }
    };

    /**
     * Display suggestions
     */
    AutocompleteWidget.prototype.displaySuggestions = function(items, query) {
        this.$suggestions.empty().show();

        if (!items || items.length === 0) {
            this.showNoResults();
            this.addNewOption(query);
            return;
        }

        var self = this;

        // Render each item using the callback
        $.each(items, function(index, item) {
            var $suggestion = self.renderItem(item);
            if ($suggestion) {
                self.$suggestions.append($suggestion);
            }
        });

        // Always add "Add New" option
        this.addNewOption(query);
    };

    /**
     * Render a single item using the configured callback
     */
    AutocompleteWidget.prototype.renderItem = function(item) {
        var callbackName = this.config.render_item_callback;
        
        if (!callbackName || typeof window.OO_Autocomplete_Callbacks[callbackName] !== 'function') {
            console.error('OO Autocomplete: Render callback not found:', callbackName);
            return this.defaultRenderItem(item);
        }

        try {
            var html = window.OO_Autocomplete_Callbacks[callbackName](item);
            var $suggestion = $(html).addClass('oo-autocomplete-suggestion');
            
            // Bind click event
            var self = this;
            $suggestion.on('click', function(e) {
                e.preventDefault();
                self.selectItem(item);
            });

            return $suggestion;
        } catch (error) {
            console.error('OO Autocomplete: Error in render callback:', error);
            return this.defaultRenderItem(item);
        }
    };

    /**
     * Default item renderer (fallback)
     */
    AutocompleteWidget.prototype.defaultRenderItem = function(item) {
        var displayText = item.name || item.title || item.text || 'Item';
        var html = '<div class="oo-autocomplete-suggestion">' + 
                   '<div class="item-details-container">' +
                   '<div class="item-primary-text">' + this.escapeHtml(displayText) + '</div>' +
                   '</div></div>';
        
        var $suggestion = $(html);
        var self = this;
        
        $suggestion.on('click', function(e) {
            e.preventDefault();
            self.selectItem(item);
        });

        return $suggestion;
    };

    /**
     * Add "Add New" option
     */
    AutocompleteWidget.prototype.addNewOption = function(query) {
        if (!this.config.on_add_new_callback) {
            return; // No callback provided, don't show add new option
        }

        var html = '<div class="oo-autocomplete-suggestion add-new">' +
                   '<div class="item-icon">+</div>' +
                   '<div class="item-details-container">' +
                   '<div class="item-primary-text">' + this.escapeHtml(this.config.add_new_text) + '</div>' +
                   '</div></div>';

        var $addNew = $(html);
        var self = this;

        $addNew.on('click', function(e) {
            e.preventDefault();
            self.triggerAddNew(query);
        });

        this.$suggestions.append($addNew);
    };

    /**
     * Select an item
     */
    AutocompleteWidget.prototype.selectItem = function(item) {
        var callbackName = this.config.on_select_callback;
        
        if (callbackName && typeof window.OO_Autocomplete_Callbacks[callbackName] === 'function') {
            try {
                window.OO_Autocomplete_Callbacks[callbackName](item, this.$input);
            } catch (error) {
                console.error('OO Autocomplete: Error in select callback:', error);
            }
        } else {
            console.warn('OO Autocomplete: Select callback not found:', callbackName);
            // Default behavior: set input value
            this.$input.val(item.name || item.title || item.text || '');
        }

        this.hideSuggestions();
    };

    /**
     * Trigger "Add New" action
     */
    AutocompleteWidget.prototype.triggerAddNew = function(query) {
        var callbackName = this.config.on_add_new_callback;
        
        if (callbackName && typeof window.OO_Autocomplete_Callbacks[callbackName] === 'function') {
            try {
                window.OO_Autocomplete_Callbacks[callbackName](query, this.$input);
            } catch (error) {
                console.error('OO Autocomplete: Error in add new callback:', error);
            }
        } else {
            console.warn('OO Autocomplete: Add new callback not found:', callbackName);
        }

        this.hideSuggestions();
    };

    /**
     * Show loading state
     */
    AutocompleteWidget.prototype.showLoading = function() {
        this.$suggestions.html('<div class="oo-autocomplete-suggestion loading">Searching...</div>').show();
    };

    /**
     * Show no results message
     */
    AutocompleteWidget.prototype.showNoResults = function() {
        this.$suggestions.append('<div class="oo-autocomplete-suggestion no-results">No results found</div>');
    };

    /**
     * Show error message
     */
    AutocompleteWidget.prototype.showError = function(message) {
        this.$suggestions.html('<div class="oo-autocomplete-suggestion no-results">Error: ' + this.escapeHtml(message) + '</div>').show();
    };

    /**
     * Hide suggestions
     */
    AutocompleteWidget.prototype.hideSuggestions = function() {
        this.$suggestions.hide().empty();
        this.$suggestions.find('.selected').removeClass('selected');
    };

    /**
     * Destroy the widget
     */
    AutocompleteWidget.prototype.destroy = function() {
        if (!this.isInitialized) {
            return;
        }

        // Remove event handlers
        this.$input.off('.oo-autocomplete');
        $(document).off('.oo-autocomplete-' + this.config.input_selector.replace(/[^a-zA-Z0-9]/g, ''));

        // Remove DOM elements
        if (this.$suggestions) {
            this.$suggestions.remove();
        }

        // Clear timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        this.isInitialized = false;
        console.log('OO Autocomplete: Destroyed for', this.config.input_selector);
    };

    /**
     * Utility function to escape HTML
     */
    AutocompleteWidget.prototype.escapeHtml = function(text) {
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
    window.OO_Autocomplete.Widget = AutocompleteWidget;

    // Helper function to create and initialize a widget
    window.OO_Autocomplete.create = function(config) {
        return new AutocompleteWidget(config);
    };

})(jQuery); 