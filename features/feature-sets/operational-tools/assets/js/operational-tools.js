/**
 * Operational Tools Feature Set JavaScript
 * 
 * This file contains the JavaScript functionality for the operational tools feature set.
 * It handles modal interactions, AJAX requests, and UI updates.
 */

jQuery(document).ready(function($) {
    console.log('Operational Tools feature set loaded');
    
    // Check if we have the required data object
    if (typeof oo_operational_tools_data === 'undefined') {
        console.warn('oo_operational_tools_data is not defined');
        return;
    }
    
    var streamId = oo_operational_tools_data.stream_id;
    var ajaxUrl = oo_operational_tools_data.ajax_url;
    var nonce = oo_operational_tools_data.nonce;
    var strings = oo_operational_tools_data.strings;
    
    // Modal management
    var $modalContainer = $('#oo-operational-modals-container');
    
    /**
     * Create a generic modal
     */
    function createModal(id, title, content) {
        var modalHtml = '<div id="' + id + '" class="oo-operational-modal" style="display:none;">' +
            '<div class="oo-modal-content">' +
            '<span class="oo-modal-close">&times;</span>' +
            '<h2>' + title + '</h2>' +
            '<div class="oo-modal-body">' + content + '</div>' +
            '</div>' +
            '</div>';
        
        $modalContainer.append(modalHtml);
        
        // Add close handler
        $('#' + id + ' .oo-modal-close').on('click', function() {
            $('#' + id).hide();
        });
        
        // Close on outside click
        $('#' + id).on('click', function(e) {
            if (e.target.id === id) {
                $('#' + id).hide();
            }
        });
        
        return $('#' + id);
    }
    
    /**
     * Show loading state
     */
    function showLoading(element) {
        $(element).addClass('oo-loading');
    }
    
    /**
     * Hide loading state
     */
    function hideLoading(element) {
        $(element).removeClass('oo-loading');
    }
    
    /**
     * Make AJAX request with error handling
     */
    function makeRequest(action, data, callback) {
        var requestData = $.extend({
            action: 'oo_operational_tools_' + action,
            nonce: nonce,
            stream_id: streamId
        }, data);
        
        $.post(ajaxUrl, requestData)
            .done(function(response) {
                if (response.success) {
                    callback(null, response.data);
                } else {
                    callback(response.data.message || strings.error);
                }
            })
            .fail(function() {
                callback(strings.error);
            });
    }
    
    // Modal implementations would go here
    // For now, these are placeholder implementations
    
    console.log('Operational Tools JavaScript initialized for stream:', streamId);
});

// Global functions that can be called from the PHP template
function openInventoryModal() {
    jQuery('#oo-inventory-modal').show();
}

function openAlertsModal() {
    jQuery('#oo-alerts-modal').show();
}

function openJobsModal() {
    jQuery('#oo-jobs-modal').show();
}

function openHistoryModal() {
    jQuery('#oo-history-modal').show();
}

function openQualityModal() {
    jQuery('#oo-quality-modal').show();
}

function openReportsModal() {
    jQuery('#oo-reports-modal').show();
}

function startNewJob() {
    alert('Start new job functionality will be implemented here.');
}

function addInventoryItem() {
    alert('Add inventory item functionality will be implemented here.');
}

function generateReport() {
    alert('Generate report functionality will be implemented here.');
}

function viewAnalytics() {
    alert('View analytics functionality will be implemented here.');
} 