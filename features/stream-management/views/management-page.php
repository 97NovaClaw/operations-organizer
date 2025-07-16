<?php
// /features/stream-management/views/management-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get all streams for display (including inactive ones for management)
$streams = oo_get_streams(array('is_active' => null)); // Get all streams regardless of status
?>

<div class="wrap oo-stream-management-page">
    <h1><?php esc_html_e('Stream Management', 'operations-organizer'); ?></h1>
    
    <div class="oo-stream-management-container">
        
        <!-- Add New Stream Form -->
        <div class="oo-add-stream-section">
            <h2><?php esc_html_e('Add New Stream', 'operations-organizer'); ?></h2>
            <form id="oo-add-stream-form">
                <?php wp_nonce_field('oo_add_stream_nonce', 'oo_add_stream_nonce_field'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="stream_name"><?php esc_html_e('Stream Name', 'operations-organizer'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="stream_name" name="stream_name" class="regular-text" required />
                            <p class="description"><?php esc_html_e('Enter a unique name for the stream.', 'operations-organizer'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="stream_description"><?php esc_html_e('Description', 'operations-organizer'); ?></label>
                        </th>
                        <td>
                            <textarea id="stream_description" name="stream_description" rows="3" class="large-text"></textarea>
                            <p class="description"><?php esc_html_e('Optional description for the stream.', 'operations-organizer'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Add Stream', 'operations-organizer'), 'primary', 'submit_add_stream'); ?>
            </form>
        </div>
        
        <!-- Existing Streams Table -->
        <div class="oo-streams-list-section">
            <h2><?php esc_html_e('Existing Streams', 'operations-organizer'); ?></h2>
            
            <?php if (!empty($streams)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-name column-primary"><?php esc_html_e('Stream Name', 'operations-organizer'); ?></th>
                            <th scope="col" class="manage-column column-description"><?php esc_html_e('Description', 'operations-organizer'); ?></th>
                            <th scope="col" class="manage-column column-status"><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                            <th scope="col" class="manage-column column-created"><?php esc_html_e('Created', 'operations-organizer'); ?></th>
                            <th scope="col" class="manage-column column-actions"><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($streams as $stream): ?>
                            <tr data-stream-id="<?php echo esc_attr($stream->stream_id); ?>">
                                <td class="column-name column-primary">
                                    <strong><?php echo esc_html($stream->stream_name); ?></strong>
                                    <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e('Show more details', 'operations-organizer'); ?></span></button>
                                </td>
                                <td class="column-description" data-colname="<?php esc_attr_e('Description', 'operations-organizer'); ?>">
                                    <?php echo esc_html($stream->stream_description ?: __('No description', 'operations-organizer')); ?>
                                </td>
                                <td class="column-status" data-colname="<?php esc_attr_e('Status', 'operations-organizer'); ?>">
                                    <span class="status-badge status-<?php echo $stream->is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $stream->is_active ? esc_html__('Active', 'operations-organizer') : esc_html__('Inactive', 'operations-organizer'); ?>
                                    </span>
                                </td>
                                <td class="column-created" data-colname="<?php esc_attr_e('Created', 'operations-organizer'); ?>">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($stream->created_at))); ?>
                                </td>
                                <td class="column-actions" data-colname="<?php esc_attr_e('Actions', 'operations-organizer'); ?>">
                                    <button type="button" class="button button-small oo-edit-stream" data-stream-id="<?php echo esc_attr($stream->stream_id); ?>">
                                        <?php esc_html_e('Edit', 'operations-organizer'); ?>
                                    </button>
                                    <button type="button" class="button button-small oo-toggle-stream-status" data-stream-id="<?php echo esc_attr($stream->stream_id); ?>">
                                        <?php echo $stream->is_active ? esc_html__('Deactivate', 'operations-organizer') : esc_html__('Activate', 'operations-organizer'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('No streams found. Add your first stream using the form above.', 'operations-organizer'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Database Migration Section -->
        <div class="oo-migration-section">
            <button type="button" id="oo-migrate-stream-slugs" class="button button-secondary">
                <?php esc_html_e('Migrate Stream Database', 'operations-organizer'); ?>
            </button>
            <span class="migration-description"><?php esc_html_e('Run this if you have issues creating new streams', 'operations-organizer'); ?></span>
        </div>
    </div>
</div>

<!-- Edit Stream Modal -->
<div id="oo-edit-stream-modal" class="oo-modal" style="display:none;">
    <div class="oo-modal-content">
        <span class="oo-close-button">&times;</span>
        <h2><?php esc_html_e('Edit Stream', 'operations-organizer'); ?></h2>
        <form id="oo-edit-stream-form">
            <?php wp_nonce_field('oo_edit_stream_nonce', 'oo_edit_stream_nonce_field'); ?>
            <input type="hidden" id="edit_stream_id" name="edit_stream_id" value="" />
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="edit_stream_name"><?php esc_html_e('Stream Name', 'operations-organizer'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="edit_stream_name" name="edit_stream_name" class="regular-text" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="edit_stream_description"><?php esc_html_e('Description', 'operations-organizer'); ?></label>
                    </th>
                    <td>
                        <textarea id="edit_stream_description" name="edit_stream_description" rows="3" class="large-text"></textarea>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Update Stream', 'operations-organizer'), 'primary', 'submit_edit_stream'); ?>
        </form>
    </div>
</div>

<style>
.oo-stream-management-container {
    max-width: 1200px;
}

.oo-add-stream-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
}

.oo-streams-list-section {
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

.oo-migration-section {
    text-align: right;
    margin-top: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 3px;
}

.migration-description {
    margin-left: 10px;
    font-style: italic;
    color: #666;
    font-size: 13px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Add Stream Form Handler
    $('#oo-add-stream-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'oo_add_stream',
            nonce: $('#oo_add_stream_nonce_field').val(),
            stream_name: $('#stream_name').val(),
            stream_description: $('#stream_description').val()
        };
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload(); // Refresh to show new stream
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
    
    // Edit Stream Button Handler
    $('.oo-edit-stream').on('click', function() {
        var streamId = $(this).data('stream-id');
        
        // Get stream data
        $.post(ajaxurl, {
            action: 'oo_get_stream',
            nonce: '<?php echo wp_create_nonce('oo_get_stream_nonce'); ?>',
            stream_id: streamId
        }, function(response) {
            if (response.success) {
                var stream = response.data.stream;
                $('#edit_stream_id').val(stream.stream_id);
                $('#edit_stream_name').val(stream.stream_name);
                $('#edit_stream_description').val(stream.stream_description);
                $('#oo-edit-stream-modal').show();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
    
    // Edit Stream Form Handler
    $('#oo-edit-stream-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'oo_update_stream',
            nonce: $('#oo_edit_stream_nonce_field').val(),
            stream_id: $('#edit_stream_id').val(),
            stream_name: $('#edit_stream_name').val(),
            stream_description: $('#edit_stream_description').val()
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
    
    // Toggle Stream Status Handler
    $('.oo-toggle-stream-status').on('click', function() {
        var streamId = $(this).data('stream-id');
        
        $.post(ajaxurl, {
            action: 'oo_toggle_stream_status',
            nonce: '<?php echo wp_create_nonce('oo_toggle_stream_status_nonce'); ?>',
            stream_id: streamId
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
    
    // Modal Close Handler
    $('.oo-close-button').on('click', function() {
        $('#oo-edit-stream-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if (e.target.id === 'oo-edit-stream-modal') {
            $('#oo-edit-stream-modal').hide();
        }
    });
    
    // Migration Button Handler
    $('#oo-migrate-stream-slugs').on('click', function() {
        var $button = $(this);
        var originalText = $button.text();
        
        // Confirm before running migration
        if (!confirm('This will update the database to support dynamic streams. Continue?')) {
            return;
        }
        
        // Disable button and show loading state
        $button.prop('disabled', true).text('Running Migration...');
        
        $.post(ajaxurl, {
            action: 'oo_migrate_stream_slugs',
            nonce: '<?php echo wp_create_nonce('oo_migrate_stream_slugs_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                alert('Migration completed successfully! ' + response.data.message);
                // Optionally reload to refresh any data
                location.reload();
            } else {
                alert('Migration failed: ' + response.data.message);
            }
        }).fail(function() {
            alert('Migration request failed. Please check your connection and try again.');
        }).always(function() {
            // Re-enable button
            $button.prop('disabled', false).text(originalText);
        });
    });
});
</script> 