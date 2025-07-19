<?php
/**
 * Debug Stream Slugs
 * 
 * This page shows current vs expected stream slugs
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
$streams = $wpdb->get_results("SELECT * FROM {$streams_table} ORDER BY stream_id");
?>

<div class="wrap">
    <h1><?php _e('Debug Stream Slugs', 'operations-organizer'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('This tool shows the difference between database slugs and what the system expects.', 'operations-organizer'); ?></p>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'operations-organizer'); ?></th>
                <th><?php _e('Stream Name', 'operations-organizer'); ?></th>
                <th><?php _e('Database Slug', 'operations-organizer'); ?></th>
                <th><?php _e('Expected Slug', 'operations-organizer'); ?></th>
                <th><?php _e('Status', 'operations-organizer'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $has_mismatches = false;
            foreach ($streams as $stream) {
                $expected_slug = sanitize_key($stream->stream_name);
                $match = ($stream->stream_slug === $expected_slug);
                if (!$match) {
                    $has_mismatches = true;
                }
                ?>
                <tr>
                    <td><?php echo esc_html($stream->stream_id); ?></td>
                    <td><?php echo esc_html($stream->stream_name); ?></td>
                    <td><code><?php echo esc_html($stream->stream_slug); ?></code></td>
                    <td><code><?php echo esc_html($expected_slug); ?></code></td>
                    <td>
                        <?php if ($match): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php _e('Match', 'operations-organizer'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-warning" style="color: red;"></span> <?php _e('Mismatch', 'operations-organizer'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    
    <?php if ($has_mismatches): ?>
        <div class="notice notice-warning" style="margin-top: 20px;">
            <p><?php _e('Slug mismatches detected! This is why some streams are not showing their feature sets.', 'operations-organizer'); ?></p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=oo_fix_stream_slugs'); ?>" class="button button-primary">
                    <?php _e('Fix Stream Slugs', 'operations-organizer'); ?>
                </a>
            </p>
        </div>
    <?php else: ?>
        <div class="notice notice-success" style="margin-top: 20px;">
            <p><?php _e('All stream slugs are correct!', 'operations-organizer'); ?></p>
        </div>
    <?php endif; ?>
</div> 