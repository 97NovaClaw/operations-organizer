<?php
/**
 * Direct Database Fix for Missing Columns
 * 
 * This script directly fixes the missing end_time column in the job_logs table.
 * Run this script from the WordPress root directory.
 */

// First, ensure this is run in WordPress context
if (!defined('ABSPATH')) {
    // If not, try to find WordPress and load it
    $base_dir = __DIR__;
    $depth = 0;
    $max_depth = 5; // Maximum directory levels to search up
    
    while ($depth < $max_depth) {
        if (file_exists($base_dir . '/wp-load.php')) {
            require_once($base_dir . '/wp-load.php');
            break;
        }
        $base_dir = dirname($base_dir);
        $depth++;
    }
    
    // If we couldn't find WordPress, exit
    if (!defined('ABSPATH')) {
        die("Couldn't find WordPress installation. Run this script from the WordPress root directory.");
    }
}

// Basic security check - only admin users can run this
if (!current_user_can('manage_options')) {
    die("You don't have sufficient permissions to run this script.");
}

// Add direct fix output header
echo '<html><head><title>Operations Organizer - Direct Database Fix</title>';
echo '<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
    h1 { color: #2271b1; }
    .success { background-color: #d4edda; color: #155724; padding: 10px; margin: 10px 0; }
    .error { background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; }
    .warning { background-color: #fff3cd; color: #856404; padding: 10px; margin: 10px 0; }
    .info { background-color: #d1ecf1; color: #0c5460; padding: 10px; margin: 10px 0; }
    code { background: #f5f5f5; padding: 2px 4px; border-radius: 3px; }
</style>';
echo '</head><body>';
echo '<h1>Operations Organizer - Direct Database Fix</h1>';

global $wpdb;
$job_logs_table = $wpdb->prefix . 'oo_job_logs';

// Check if the job_logs table exists
if ($wpdb->get_var("SHOW TABLES LIKE '{$job_logs_table}'") !== $job_logs_table) {
    echo '<div class="error">Error: The job_logs table does not exist. Please activate the plugin properly first.</div>';
    exit;
}

// Get the current columns
$job_logs_columns = $wpdb->get_results("SHOW COLUMNS FROM {$job_logs_table}");
$job_logs_column_names = array_map(function($col) { return $col->Field; }, $job_logs_columns);

// Check for and add missing columns
$updates_performed = false;

// Fix stop_time if missing
if (!in_array('stop_time', $job_logs_column_names)) {
    echo '<div class="info">Adding missing <code>stop_time</code> column to job_logs table...</div>';
    
    $add_stop_time_result = $wpdb->query("ALTER TABLE {$job_logs_table} ADD COLUMN `stop_time` DATETIME NULL AFTER `start_time`");
    
    if ($add_stop_time_result !== false) {
        echo '<div class="success">Successfully added stop_time column to job_logs table.</div>';
        $updates_performed = true;
    } else {
        echo '<div class="error">Error adding stop_time column: ' . $wpdb->last_error . '</div>';
    }
} else {
    echo '<div class="info">The <code>stop_time</code> column already exists in the job_logs table.</div>';
}

// Fix end_time if missing
if (!in_array('end_time', $job_logs_column_names)) {
    echo '<div class="info">Adding missing <code>end_time</code> column to job_logs table (alias for stop_time)...</div>';
    
    $add_end_time_result = $wpdb->query("ALTER TABLE {$job_logs_table} ADD COLUMN `end_time` DATETIME NULL AFTER `stop_time`");
    
    if ($add_end_time_result !== false) {
        echo '<div class="success">Successfully added end_time column to job_logs table.</div>';
        
        // Copy existing stop_time values to end_time if stop_time exists
        if (in_array('stop_time', $job_logs_column_names)) {
            $sync_time_result = $wpdb->query("UPDATE {$job_logs_table} SET end_time = stop_time WHERE stop_time IS NOT NULL");
            if ($sync_time_result !== false) {
                echo '<div class="success">Successfully synced stop_time values to end_time column.</div>';
            } else {
                echo '<div class="error">Error syncing stop_time to end_time: ' . $wpdb->last_error . '</div>';
            }
        }
        
        $updates_performed = true;
    } else {
        echo '<div class="error">Error adding end_time column: ' . $wpdb->last_error . '</div>';
    }
} else {
    echo '<div class="info">The <code>end_time</code> column already exists in the job_logs table.</div>';
}

// Fix status column if missing
if (!in_array('status', $job_logs_column_names)) {
    echo '<div class="info">Adding missing <code>status</code> column to job_logs table...</div>';
    
    $add_status_result = $wpdb->query("ALTER TABLE {$job_logs_table} ADD COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'started' AFTER `log_date`");
    
    if ($add_status_result !== false) {
        echo '<div class="success">Successfully added status column to job_logs table.</div>';
        $updates_performed = true;
    } else {
        echo '<div class="error">Error adding status column: ' . $wpdb->last_error . '</div>';
    }
} else {
    echo '<div class="info">The <code>status</code> column already exists in the job_logs table.</div>';
}

// Fix boxes_completed column if missing
if (!in_array('boxes_completed', $job_logs_column_names)) {
    echo '<div class="info">Adding missing <code>boxes_completed</code> column to job_logs table...</div>';
    
    $add_boxes_result = $wpdb->query("ALTER TABLE {$job_logs_table} ADD COLUMN `boxes_completed` INT UNSIGNED NULL AFTER `duration_minutes`");
    
    if ($add_boxes_result !== false) {
        echo '<div class="success">Successfully added boxes_completed column to job_logs table.</div>';
        $updates_performed = true;
    } else {
        echo '<div class="error">Error adding boxes_completed column: ' . $wpdb->last_error . '</div>';
    }
} else {
    echo '<div class="info">The <code>boxes_completed</code> column already exists in the job_logs table.</div>';
}

// Fix items_completed column if missing
if (!in_array('items_completed', $job_logs_column_names)) {
    echo '<div class="info">Adding missing <code>items_completed</code> column to job_logs table...</div>';
    
    $add_items_result = $wpdb->query("ALTER TABLE {$job_logs_table} ADD COLUMN `items_completed` INT UNSIGNED NULL AFTER `boxes_completed`");
    
    if ($add_items_result !== false) {
        echo '<div class="success">Successfully added items_completed column to job_logs table.</div>';
        $updates_performed = true;
    } else {
        echo '<div class="error">Error adding items_completed column: ' . $wpdb->last_error . '</div>';
    }
} else {
    echo '<div class="info">The <code>items_completed</code> column already exists in the job_logs table.</div>';
}

// Fix stream_type_id to stream_id if needed
if (in_array('stream_type_id', $job_logs_column_names) && !in_array('stream_id', $job_logs_column_names)) {
    echo '<div class="info">Renaming <code>stream_type_id</code> column to <code>stream_id</code> in job_logs table...</div>';
    
    $rename_stream_result = $wpdb->query("ALTER TABLE {$job_logs_table} CHANGE COLUMN `stream_type_id` `stream_id` INT UNSIGNED NOT NULL");
    
    if ($rename_stream_result !== false) {
        echo '<div class="success">Successfully renamed stream_type_id to stream_id in job_logs table.</div>';
        $updates_performed = true;
    } else {
        echo '<div class="error">Error renaming column: ' . $wpdb->last_error . '</div>';
    }
} elseif (in_array('stream_id', $job_logs_column_names)) {
    echo '<div class="info">The job_logs table already has the correct <code>stream_id</code> column.</div>';
}

// Final summary
if ($updates_performed) {
    echo '<div class="success" style="margin-top: 20px; font-weight: bold;">Database schema has been successfully updated!</div>';
} else {
    echo '<div class="info" style="margin-top: 20px; font-weight: bold;">No database changes were needed. Your schema is up to date.</div>';
}

echo '<p><a href="' . admin_url('admin.php?page=oo_dashboard') . '" style="display: inline-block; padding: 10px 15px; background-color: #2271b1; color: white; text-decoration: none; border-radius: 3px;">Return to Dashboard</a></p>';

echo '</body></html>'; 