<?php
// /features/feature-sets-management/views/management-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get all feature sets for display (including inactive ones for management)
$feature_sets = OO_DB::get_feature_sets(array('is_active' => null)); // Get all feature sets regardless of status

// Check if migration is needed
$migration_needed = OO_Feature_Sets_Migration::is_migration_needed();
$migration_status = OO_Feature_Sets_Migration::get_migration_status();
?>

<div class="wrap oo-feature-sets-management-page">
    <h1><?php esc_html_e('Feature Sets Management', 'operations-organizer'); ?></h1>
    
    <div class="oo-feature-sets-management-container">
        
        <!-- Feature Sets Migration Section -->
        <?php if ($migration_needed): ?>
            <div class="oo-migration-section">
                <h2><?php esc_html_e('Feature Sets Migration Required', 'operations-organizer'); ?></h2>
                <div class="notice notice-warning">
                    <p><?php esc_html_e('Feature sets migration is required to set up the default operational tools feature set and assign it to existing streams.', 'operations-organizer'); ?></p>
                </div>
                
                <div class="oo-migration-status">
                    <h3><?php esc_html_e('Migration Status', 'operations-organizer'); ?></h3>
                    <ul>
                        <li><strong><?php esc_html_e('Operational Tools Feature Set:', 'operations-organizer'); ?></strong> 
                            <?php echo $migration_status['operational_tools_exists'] ? 
                                '<span style="color: green;">✓ ' . esc_html__('Exists', 'operations-organizer') . '</span>' : 
                                '<span style="color: red;">✗ ' . esc_html__('Missing', 'operations-organizer') . '</span>'; ?>
                        </li>
                        <li><strong><?php esc_html_e('Total Active Streams:', 'operations-organizer'); ?></strong> <?php echo intval($migration_status['total_streams']); ?></li>
                        <li><strong><?php esc_html_e('Streams with Operational Tools:', 'operations-organizer'); ?></strong> <?php echo intval($migration_status['streams_with_operational_tools']); ?></li>
                        <?php if (!empty($migration_status['streams_without_operational_tools'])): ?>
                            <li><strong><?php esc_html_e('Streams needing assignment:', 'operations-organizer'); ?></strong> <?php echo esc_html(implode(', ', $migration_status['streams_without_operational_tools'])); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <button type="button" id="oo-run-feature-sets-migration" class="button button-primary button-large">
                    <?php esc_html_e('Run Feature Sets Migration', 'operations-organizer'); ?>
                </button>
                
                <div id="oo-migration-progress" style="display: none;">
                    <p><?php esc_html_e('Running migration...', 'operations-organizer'); ?></p>
                    <div class="oo-progress-bar">
                        <div class="oo-progress-fill"></div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="oo-migration-section">
                <div class="notice notice-success">
                    <p><?php esc_html_e('Feature sets are properly configured. No migration needed.', 'operations-organizer'); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Add New Feature Set Form -->
        <div class="oo-add-feature-set-section">
            <h2><?php esc_html_e('Add New Feature Set', 'operations-organizer'); ?></h2>
            <form id="oo-add-feature-set-form">
                <?php wp_nonce_field('oo_add_feature_set_nonce', 'oo_add_feature_set_nonce_field'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="feature_set_name"><?php esc_html_e('Feature Set Name', 'operations-organizer'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="feature_set_name" name="name" class="regular-text" required />
                            <p class="description"><?php esc_html_e('Enter a unique name for the feature set (e.g., "Operational Tools", "Inventory Management").', 'operations-organizer'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="feature_set_description"><?php esc_html_e('Description', 'operations-organizer'); ?></label>
                        </th>
                        <td>
                            <textarea id="feature_set_description" name="description" rows="3" class="large-text"></textarea>
                            <p class="description"><?php esc_html_e('Optional description for the feature set.', 'operations-organizer'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="feature_set_sort_order"><?php esc_html_e('Sort Order', 'operations-organizer'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="feature_set_sort_order" name="sort_order" value="0" min="0" class="small-text" />
                            <p class="description"><?php esc_html_e('Lower numbers appear first in sub-tabs.', 'operations-organizer'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Add Feature Set', 'operations-organizer'), 'primary', 'submit_add_feature_set'); ?>
            </form>
        </div>
        
        <!-- Existing Feature Sets Table -->
        <div class="oo-feature-sets-list-section">
            <h2><?php esc_html_e('Existing Feature Sets', 'operations-organizer'); ?></h2>
            
            <?php if (!empty($feature_sets)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-name column-primary"><?php esc_html_e('Feature Set Name', 'operations-organizer'); ?></th>
                            <th scope="col" class="manage-column column-description"><?php esc_html_e('Description', 'operations-organizer'); ?></th>
                            <th scope="col" class="manage-column column-sort-order"><?php esc_html_e('Sort Order', 'operations-organizer'); ?></th>
                            <th scope="col" class="manage-column column-status"><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                            <th scope="col" class="manage-column column-created"><?php esc_html_e('Created', 'operations-organizer'); ?></th>
                            <th scope="col" class="manage-column column-actions"><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feature_sets as $feature_set): ?>
                            <tr data-feature-set-id="<?php echo esc_attr($feature_set->feature_set_id); ?>">
                                <td class="column-name column-primary">
                                    <strong><?php echo esc_html($feature_set->name); ?></strong>
                                    <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e('Show more details', 'operations-organizer'); ?></span></button>
                                </td>
                                <td class="column-description" data-colname="<?php esc_attr_e('Description', 'operations-organizer'); ?>">
                                    <?php echo esc_html($feature_set->description ?: __('No description', 'operations-organizer')); ?>
                                </td>
                                <td class="column-sort-order" data-colname="<?php esc_attr_e('Sort Order', 'operations-organizer'); ?>">
                                    <?php echo esc_html($feature_set->sort_order); ?>
                                </td>
                                <td class="column-status" data-colname="<?php esc_attr_e('Status', 'operations-organizer'); ?>">
                                    <span class="status-badge status-<?php echo $feature_set->is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $feature_set->is_active ? esc_html__('Active', 'operations-organizer') : esc_html__('Inactive', 'operations-organizer'); ?>
                                    </span>
                                </td>
                                <td class="column-created" data-colname="<?php esc_attr_e('Created', 'operations-organizer'); ?>">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($feature_set->created_at))); ?>
                                </td>
                                <td class="column-actions" data-colname="<?php esc_attr_e('Actions', 'operations-organizer'); ?>">
                                    <button type="button" class="button button-small oo-edit-feature-set" data-feature-set-id="<?php echo esc_attr($feature_set->feature_set_id); ?>">
                                        <?php esc_html_e('Edit', 'operations-organizer'); ?>
                                    </button>
                                    <?php
                                    $toggle_action_text = $feature_set->is_active ? __('Deactivate', 'operations-organizer') : __('Activate', 'operations-organizer');
                                    $new_status_val = $feature_set->is_active ? 0 : 1;
                                    ?>
                                    <button type="button" 
                                            class="button button-small oo-toggle-feature-set-status" 
                                            data-feature-set-id="<?php echo esc_attr($feature_set->feature_set_id); ?>"
                                            data-new-status="<?php echo esc_attr($new_status_val); ?>">
                                        <?php echo esc_html($toggle_action_text); ?>
                                    </button>
                                    | <a href="#" 
                                       class="oo-delete-feature-set" 
                                       data-feature-set-id="<?php echo esc_attr($feature_set->feature_set_id); ?>" 
                                       style="color:#b32d2e; text-decoration: none;">
                                        <?php esc_html_e('Delete', 'operations-organizer'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('No feature sets found. Add your first feature set using the form above.', 'operations-organizer'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Feature Set Modal -->
<div id="oo-edit-feature-set-modal" class="oo-modal" style="display:none;">
    <div class="oo-modal-content">
        <span class="oo-close-button">&times;</span>
        <h2><?php esc_html_e('Edit Feature Set', 'operations-organizer'); ?></h2>
        <form id="oo-edit-feature-set-form">
            <?php wp_nonce_field('oo_update_feature_set_nonce', 'oo_update_feature_set_nonce_field'); ?>
            <input type="hidden" id="edit_feature_set_id" name="feature_set_id" value="" />
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="edit_feature_set_name"><?php esc_html_e('Feature Set Name', 'operations-organizer'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="edit_feature_set_name" name="name" class="regular-text" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="edit_feature_set_description"><?php esc_html_e('Description', 'operations-organizer'); ?></label>
                    </th>
                    <td>
                        <textarea id="edit_feature_set_description" name="description" rows="3" class="large-text"></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="edit_feature_set_sort_order"><?php esc_html_e('Sort Order', 'operations-organizer'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="edit_feature_set_sort_order" name="sort_order" value="0" min="0" class="small-text" />
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Update Feature Set', 'operations-organizer'), 'primary', 'submit_edit_feature_set'); ?>
        </form>
    </div>
</div>

<style>
.oo-feature-sets-management-container {
    max-width: 1200px;
}

.oo-migration-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.oo-migration-status ul {
    list-style: none;
    padding: 0;
}

.oo-migration-status li {
    margin: 10px 0;
    padding: 8px;
    background: #f9f9f9;
    border-left: 4px solid #0073aa;
}

.oo-progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 10px;
}

.oo-progress-fill {
    height: 100%;
    background: #0073aa;
    width: 0%;
    transition: width 0.3s ease;
    animation: progress-pulse 2s infinite;
}

@keyframes progress-pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.oo-add-feature-set-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
}

.oo-feature-sets-list-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.required {
    color: #d63384;
}

.oo-modal {
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.oo-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    position: relative;
}

.oo-close-button {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    position: absolute;
    right: 15px;
    top: 10px;
}

.oo-close-button:hover,
.oo-close-button:focus {
    color: black;
}
</style>

<script>
jQuery(document).ready(function($) {
    console.log('Feature Sets Management page loaded');
    
    // Add Feature Set Form Handler
    $('#oo-add-feature-set-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'oo_add_feature_set',
            nonce: $('#oo_add_feature_set_nonce_field').val(),
            name: $('#feature_set_name').val(),
            description: $('#feature_set_description').val(),
            sort_order: $('#feature_set_sort_order').val()
        };
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload(); // Refresh to show new feature set
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
    
    // Edit Feature Set Button Handler
    $('.oo-edit-feature-set').on('click', function() {
        var featureSetId = $(this).data('feature-set-id');
        
        // Get feature set data
        $.post(ajaxurl, {
            action: 'oo_get_feature_set',
            nonce: '<?php echo wp_create_nonce('oo_get_feature_set_nonce'); ?>',
            feature_set_id: featureSetId
        }, function(response) {
            if (response.success) {
                var featureSet = response.data.feature_set;
                $('#edit_feature_set_id').val(featureSet.feature_set_id);
                $('#edit_feature_set_name').val(featureSet.name);
                $('#edit_feature_set_description').val(featureSet.description);
                $('#edit_feature_set_sort_order').val(featureSet.sort_order);
                $('#oo-edit-feature-set-modal').show();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
    
    // Edit Feature Set Form Handler
    $('#oo-edit-feature-set-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'oo_update_feature_set',
            nonce: $('#oo_update_feature_set_nonce_field').val(),
            feature_set_id: $('#edit_feature_set_id').val(),
            name: $('#edit_feature_set_name').val(),
            description: $('#edit_feature_set_description').val(),
            sort_order: $('#edit_feature_set_sort_order').val()
        };
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
    
    // Toggle Feature Set Status Handler
    $('.oo-toggle-feature-set-status').on('click', function() {
        var $button = $(this);
        var featureSetId = $button.data('feature-set-id');
        var newStatus = $button.data('new-status');
        
        $button.prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'oo_toggle_feature_set_status',
            nonce: '<?php echo wp_create_nonce('oo_toggle_feature_set_status_nonce'); ?>',
            feature_set_id: featureSetId,
            is_active: newStatus
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
                $button.prop('disabled', false);
            }
        }).fail(function() {
            alert('An unknown error occurred.');
            $button.prop('disabled', false);
        });
    });
    
    // Delete Feature Set Handler
    $('.oo-delete-feature-set').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this feature set? This action cannot be undone.')) {
            return;
        }
        
        var featureSetId = $(this).data('feature-set-id');
        var $spinner = $('<span class="spinner is-active"></span>');
        $(this).parent().append($spinner);
        
        $.post(ajaxurl, {
            action: 'oo_delete_feature_set',
            nonce: '<?php echo wp_create_nonce('oo_delete_feature_set_nonce'); ?>',
            feature_set_id: featureSetId
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
                $spinner.remove();
            }
        }).fail(function() {
            alert('An unknown error occurred during deletion.');
            $spinner.remove();
        });
    });
    
    // Modal Close Handler
    $('.oo-close-button').on('click', function() {
        $('#oo-edit-feature-set-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if (e.target.id === 'oo-edit-feature-set-modal') {
            $('#oo-edit-feature-set-modal').hide();
        }
    });
    
    // Migration Button Handler
    $('#oo-run-feature-sets-migration').on('click', function() {
        var $button = $(this);
        var $progress = $('#oo-migration-progress');
        var $progressFill = $('.oo-progress-fill');
        var originalText = $button.text();
        
        // Confirm before running migration
        if (!confirm('This will set up the default operational tools feature set and assign it to existing streams. Continue?')) {
            return;
        }
        
        // Show progress and disable button
        $button.prop('disabled', true).text('Running Migration...');
        $progress.show();
        $progressFill.css('width', '30%');
        
        $.post(ajaxurl, {
            action: 'oo_run_feature_sets_migration',
            nonce: '<?php echo wp_create_nonce('oo_run_feature_sets_migration_nonce'); ?>'
        }, function(response) {
            $progressFill.css('width', '100%');
            
            setTimeout(function() {
                if (response.success) {
                    alert('Migration completed successfully: ' + response.data.message);
                    location.reload(); // Refresh to show updated status
                } else {
                    alert('Migration failed: ' + response.data.message);
                    $button.prop('disabled', false).text(originalText);
                    $progress.hide();
                    $progressFill.css('width', '0%');
                }
            }, 500);
        }).fail(function() {
            alert('Migration request failed. Please check your connection and try again.');
            $button.prop('disabled', false).text(originalText);
            $progress.hide();
            $progressFill.css('width', '0%');
        });
    });
});
</script> 