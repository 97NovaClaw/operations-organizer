<?php
/**
 * Configure View Modal Template
 * 
 * Reusable modal for configuring table column visibility and order
 * 
 * @package OperationsOrganizer
 * @subpackage Features/TableViewManager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<!-- Table View Manager Modal -->
<div id="oo-table-view-modal" class="oo-modal oo-table-view-modal" style="display:none;">
    <div class="oo-modal-content oo-modal-large">
        <div class="oo-modal-header">
            <h2><?php esc_html_e('Configure Table View', 'operations-organizer'); ?></h2>
            <span class="oo-close-modal">&times;</span>
        </div>
        
        <div class="oo-modal-body">
            <!-- Tab Navigation -->
            <div class="oo-tvm-tabs">
                <button class="oo-tvm-tab-button active" data-tab="visibility">
                    <?php esc_html_e('Visibility', 'operations-organizer'); ?>
                </button>
                <button class="oo-tvm-tab-button" data-tab="order">
                    <?php esc_html_e('Order', 'operations-organizer'); ?>
                </button>
            </div>
            
            <!-- Tab Content -->
            <div class="oo-tvm-tab-content">
                <!-- Visibility Tab -->
                <div id="oo-tvm-visibility-tab" class="oo-tvm-tab-panel active">
                    <div class="oo-tvm-instructions">
                        <p><?php esc_html_e('Select which columns to display in the table. Groups can be expanded/collapsed.', 'operations-organizer'); ?></p>
                    </div>
                    
                    <div class="oo-tvm-column-list" id="oo-tvm-visibility-list">
                        <!-- Column checkboxes will be dynamically inserted here -->
                        <div class="oo-tvm-loading">
                            <span class="spinner is-active"></span>
                            <p><?php esc_html_e('Loading columns...', 'operations-organizer'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Order Tab -->
                <div id="oo-tvm-order-tab" class="oo-tvm-tab-panel">
                    <div class="oo-tvm-instructions">
                        <p><?php esc_html_e('Drag columns to reorder them. Only visible columns are shown here.', 'operations-organizer'); ?></p>
                    </div>
                    
                    <div class="oo-tvm-sortable-list" id="oo-tvm-order-list">
                        <!-- Sortable column items will be dynamically inserted here -->
                        <div class="oo-tvm-loading">
                            <span class="spinner is-active"></span>
                            <p><?php esc_html_e('Loading column order...', 'operations-organizer'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="oo-modal-footer">
            <div class="oo-tvm-footer-left">
                <button type="button" class="button oo-tvm-reset-btn">
                    <?php esc_html_e('Reset to Default', 'operations-organizer'); ?>
                </button>
            </div>
            <div class="oo-tvm-footer-right">
                <button type="button" class="button oo-tvm-cancel-btn">
                    <?php esc_html_e('Cancel', 'operations-organizer'); ?>
                </button>
                <button type="button" class="button button-primary oo-tvm-apply-btn">
                    <?php esc_html_e('Apply', 'operations-organizer'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template for column checkbox item (used by JavaScript) -->
<script type="text/template" id="oo-tvm-column-checkbox-template">
    <div class="oo-tvm-column-item" data-column-id="{{id}}">
        <label>
            <input type="checkbox" class="oo-tvm-column-checkbox" value="{{id}}" {{checked}}>
            <span class="oo-tvm-column-title">{{title}}</span>
        </label>
    </div>
</script>

<!-- Template for group checkbox item (used by JavaScript) -->
<script type="text/template" id="oo-tvm-group-checkbox-template">
    <div class="oo-tvm-column-group" data-group-id="{{id}}">
        <div class="oo-tvm-group-header">
            <span class="oo-tvm-group-toggle">
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </span>
            <label>
                <input type="checkbox" class="oo-tvm-group-checkbox" value="{{id}}" {{checked}}>
                <span class="oo-tvm-column-title">{{title}}</span>
            </label>
        </div>
        <div class="oo-tvm-group-children">
            {{children}}
        </div>
    </div>
</script>

<!-- Template for sortable column item (used by JavaScript) -->
<script type="text/template" id="oo-tvm-sortable-item-template">
    <div class="oo-tvm-sortable-item" data-column-id="{{id}}">
        <span class="oo-tvm-drag-handle">
            <span class="dashicons dashicons-menu"></span>
        </span>
        <span class="oo-tvm-column-title">{{title}}</span>
    </div>
</script> 