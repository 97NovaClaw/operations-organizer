<?php
// /features/feature-sets/operational-tools/views/main-view.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// The $stream and $feature_set variables are passed from the renderer
?>

<div class="oo-operational-tools-container">
    <div class="oo-feature-set-header">
        <h3><?php echo esc_html($feature_set->name); ?></h3>
        <?php if (!empty($feature_set->description)): ?>
            <p class="description"><?php echo esc_html($feature_set->description); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="oo-operational-tools-content">
        
        <!-- Inventory Management Section -->
        <div class="oo-tool-section" id="inventory-section">
            <h4><?php esc_html_e('Inventory Management', 'operations-organizer'); ?></h4>
            <div class="oo-tool-grid">
                <div class="oo-tool-card">
                    <h5><?php esc_html_e('Current Inventory', 'operations-organizer'); ?></h5>
                    <p><?php esc_html_e('View and manage current inventory levels for this stream.', 'operations-organizer'); ?></p>
                    <button type="button" class="button button-primary" onclick="openInventoryModal()">
                        <?php esc_html_e('Manage Inventory', 'operations-organizer'); ?>
                    </button>
                </div>
                <div class="oo-tool-card">
                    <h5><?php esc_html_e('Inventory Alerts', 'operations-organizer'); ?></h5>
                    <p><?php esc_html_e('Set up alerts for low inventory levels.', 'operations-organizer'); ?></p>
                    <button type="button" class="button button-secondary" onclick="openAlertsModal()">
                        <?php esc_html_e('Configure Alerts', 'operations-organizer'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Job Tracking Section -->
        <div class="oo-tool-section" id="tracking-section">
            <h4><?php esc_html_e('Job Tracking', 'operations-organizer'); ?></h4>
            <div class="oo-tool-grid">
                <div class="oo-tool-card">
                    <h5><?php esc_html_e('Active Jobs', 'operations-organizer'); ?></h5>
                    <p><?php esc_html_e('Monitor jobs currently in progress for this stream.', 'operations-organizer'); ?></p>
                    <button type="button" class="button button-primary" onclick="openJobsModal()">
                        <?php esc_html_e('View Active Jobs', 'operations-organizer'); ?>
                    </button>
                </div>
                <div class="oo-tool-card">
                    <h5><?php esc_html_e('Job History', 'operations-organizer'); ?></h5>
                    <p><?php esc_html_e('Review completed jobs and their performance metrics.', 'operations-organizer'); ?></p>
                    <button type="button" class="button button-secondary" onclick="openHistoryModal()">
                        <?php esc_html_e('View History', 'operations-organizer'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Quality Control Section -->
        <div class="oo-tool-section" id="quality-section">
            <h4><?php esc_html_e('Quality Control', 'operations-organizer'); ?></h4>
            <div class="oo-tool-grid">
                <div class="oo-tool-card">
                    <h5><?php esc_html_e('Quality Metrics', 'operations-organizer'); ?></h5>
                    <p><?php esc_html_e('Monitor quality metrics and performance indicators.', 'operations-organizer'); ?></p>
                    <button type="button" class="button button-primary" onclick="openQualityModal()">
                        <?php esc_html_e('View Metrics', 'operations-organizer'); ?>
                    </button>
                </div>
                <div class="oo-tool-card">
                    <h5><?php esc_html_e('Quality Reports', 'operations-organizer'); ?></h5>
                    <p><?php esc_html_e('Generate quality control reports for this stream.', 'operations-organizer'); ?></p>
                    <button type="button" class="button button-secondary" onclick="openReportsModal()">
                        <?php esc_html_e('Generate Reports', 'operations-organizer'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Section -->
        <div class="oo-tool-section" id="quick-actions-section">
            <h4><?php esc_html_e('Quick Actions', 'operations-organizer'); ?></h4>
            <div class="oo-quick-actions">
                <button type="button" class="button button-primary" onclick="startNewJob()">
                    <?php esc_html_e('Start New Job', 'operations-organizer'); ?>
                </button>
                <button type="button" class="button button-secondary" onclick="addInventoryItem()">
                    <?php esc_html_e('Add Inventory Item', 'operations-organizer'); ?>
                </button>
                <button type="button" class="button button-secondary" onclick="generateReport()">
                    <?php esc_html_e('Generate Report', 'operations-organizer'); ?>
                </button>
                <button type="button" class="button button-secondary" onclick="viewAnalytics()">
                    <?php esc_html_e('View Analytics', 'operations-organizer'); ?>
                </button>
            </div>
        </div>
        
        <!-- Stream-Specific Information -->
        <div class="oo-tool-section" id="stream-info-section">
            <h4><?php esc_html_e('Stream Information', 'operations-organizer'); ?></h4>
            <div class="oo-info-grid">
                <div class="oo-info-item">
                    <strong><?php esc_html_e('Stream Name:', 'operations-organizer'); ?></strong>
                    <span><?php echo esc_html($stream->stream_name); ?></span>
                </div>
                <div class="oo-info-item">
                    <strong><?php esc_html_e('Stream ID:', 'operations-organizer'); ?></strong>
                    <span><?php echo esc_html($stream->stream_id); ?></span>
                </div>
                <div class="oo-info-item">
                    <strong><?php esc_html_e('Status:', 'operations-organizer'); ?></strong>
                    <span class="status-badge status-<?php echo $stream->is_active ? 'active' : 'inactive'; ?>">
                        <?php echo $stream->is_active ? esc_html__('Active', 'operations-organizer') : esc_html__('Inactive', 'operations-organizer'); ?>
                    </span>
                </div>
                <?php if (!empty($stream->stream_description)): ?>
                <div class="oo-info-item">
                    <strong><?php esc_html_e('Description:', 'operations-organizer'); ?></strong>
                    <span><?php echo esc_html($stream->stream_description); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<!-- Placeholder Modals (will be implemented by JavaScript) -->
<div id="oo-operational-modals-container">
    <!-- Modals will be dynamically created here -->
</div>

<script>
// Placeholder functions for the operational tools
// These would be implemented in the main JavaScript file

function openInventoryModal() {
    alert('Inventory management modal will be implemented here.');
}

function openAlertsModal() {
    alert('Inventory alerts configuration modal will be implemented here.');
}

function openJobsModal() {
    alert('Active jobs modal will be implemented here.');
}

function openHistoryModal() {
    alert('Job history modal will be implemented here.');
}

function openQualityModal() {
    alert('Quality metrics modal will be implemented here.');
}

function openReportsModal() {
    alert('Quality reports modal will be implemented here.');
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
</script> 