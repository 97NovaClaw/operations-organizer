<?php
/**
 * Fix Stream Slugs
 * 
 * This page fixes stream slug mismatches
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'operations-organizer'));
}

global $wpdb;
$streams_table = $wpdb->prefix . 'oo_streams';

// Handle form submission
if (isset($_POST['fix_slugs']) && check_admin_referer('fix_stream_slugs_nonce')) {
    $streams = $wpdb->get_results("SELECT * FROM {$streams_table} ORDER BY stream_id");
    $fixed_count = 0;
    $errors = array();
    
    foreach ($streams as $stream) {
        $correct_slug = sanitize_key($stream->stream_name);
        
        if ($stream->stream_slug !== $correct_slug) {
            // Check if the correct slug already exists on another stream
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT stream_id FROM {$streams_table} WHERE stream_slug = %s AND stream_id != %d",
                $correct_slug,
                $stream->stream_id
            ));
            
            if ($existing) {
                $errors[] = sprintf(
                    __('Cannot update stream "%s": slug "%s" already exists on stream ID %d', 'operations-organizer'),
                    $stream->stream_name,
                    $correct_slug,
                    $existing
                );
            } else {
                // Update the slug
                $result = $wpdb->update(
                    $streams_table,
                    array('stream_slug' => $correct_slug),
                    array('stream_id' => $stream->stream_id),
                    array('%s'),
                    array('%d')
                );
                
                if ($result !== false) {
                    $fixed_count++;
                } else {
                    $errors[] = sprintf(
                        __('Failed to update stream "%s": %s', 'operations-organizer'),
                        $stream->stream_name,
                        $wpdb->last_error
                    );
                }
            }
        }
    }
    
    if ($fixed_count > 0) {
        echo '<div class="notice notice-success"><p>' . sprintf(__('Successfully fixed %d stream slug(s).', 'operations-organizer'), $fixed_count) . '</p></div>';
    }
    
    if (!empty($errors)) {
        echo '<div class="notice notice-error"><p>' . implode('<br>', $errors) . '</p></div>';
    }
}

// Get current state
$streams = $wpdb->get_results("SELECT * FROM {$streams_table} ORDER BY stream_id");
?>

<div class="wrap">
    <h1><?php _e('Fix Stream Slugs', 'operations-organizer'); ?></h1>
    
    <div class="notice notice-warning">
        <p><strong><?php _e('Warning:', 'operations-organizer'); ?></strong> <?php _e('This tool will update stream slugs to match what the system expects. Make sure to backup your database before proceeding.', 'operations-organizer'); ?></p>
    </div>
    
    <h2><?php _e('Current State', 'operations-organizer'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Stream Name', 'operations-organizer'); ?></th>
                <th><?php _e('Current Slug', 'operations-organizer'); ?></th>
                <th><?php _e('Will Change To', 'operations-organizer'); ?></th>
                <th><?php _e('Action', 'operations-organizer'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $needs_fixing = false;
            foreach ($streams as $stream) {
                $expected_slug = sanitize_key($stream->stream_name);
                $needs_update = ($stream->stream_slug !== $expected_slug);
                if ($needs_update) {
                    $needs_fixing = true;
                }
                ?>
                <tr>
                    <td><?php echo esc_html($stream->stream_name); ?></td>
                    <td><code><?php echo esc_html($stream->stream_slug); ?></code></td>
                    <td>
                        <?php if ($needs_update): ?>
                            <code style="color: red;"><?php echo esc_html($expected_slug); ?></code>
                        <?php else: ?>
                            <span style="color: green;"><?php _e('No change needed', 'operations-organizer'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($needs_update): ?>
                            <span class="dashicons dashicons-warning" style="color: orange;"></span> <?php _e('Will be updated', 'operations-organizer'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php _e('Already correct', 'operations-organizer'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    
    <?php if ($needs_fixing): ?>
        <form method="post" style="margin-top: 20px;">
            <?php wp_nonce_field('fix_stream_slugs_nonce'); ?>
            <p>
                <input type="submit" name="fix_slugs" class="button button-primary button-large" value="<?php esc_attr_e('Fix All Stream Slugs', 'operations-organizer'); ?>" 
                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to fix all stream slugs? This action cannot be undone.', 'operations-organizer'); ?>');">
            </p>
        </form>
    <?php else: ?>
        <div class="notice notice-success" style="margin-top: 20px;">
            <p><?php _e('All stream slugs are already correct! No action needed.', 'operations-organizer'); ?></p>
        </div>
    <?php endif; ?>
    
    <p style="margin-top: 20px;">
        <a href="<?php echo admin_url('admin.php?page=oo_debug_stream_slugs'); ?>" class="button">
            <?php _e('Back to Debug View', 'operations-organizer'); ?>
        </a>
    </p>
</div> 