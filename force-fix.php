<?php
/**
 * Force Fix for Job Logs Table
 * 
 * This simple script directly renames the stream_type_id column to stream_id
 * and adds the status column if it's missing.
 */

// First, ensure this is run in WordPress context
if (!defined('ABSPATH')) {
    // If not, let's try to find WordPress and load it
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
        die("Couldn't find WordPress installation. Run this script from your WordPress directory.");
    }
}

// Ensure only admins can run this
if (!current_user_can('manage_options')) {
    die("You don't have permission to run this script.");
}

global $wpdb;
$job_logs_table = $wpdb->prefix . 'oo_job_logs';

echo '<h1>Force Fix for Job Logs Table</h1>';

// Check if the table exists
if ($wpdb->get_var("SHOW TABLES LIKE '$job_logs_table'") !== $job_logs_table) {
    die("The job logs table ($job_logs_table) doesn't exist!");
}

// Get the current columns in the table
$columns = $wpdb->get_results("SHOW COLUMNS FROM $job_logs_table");
$column_names = array_map(function($col) { return $col->Field; }, $columns);

echo '<h2>Current Columns</h2>';
echo '<pre>' . print_r($column_names, true) . '</pre>';

$did_something = false;

// Check for and fix stream_type_id to stream_id
if (in_array('stream_type_id', $column_names) && !in_array('stream_id', $column_names)) {
    echo '<h2>Renaming stream_type_id to stream_id</h2>';
    
    $result = $wpdb->query("ALTER TABLE $job_logs_table CHANGE COLUMN `stream_type_id` `stream_id` INT UNSIGNED NOT NULL");
    
    if ($result !== false) {
        echo '<p style="color: green;">Success! Column renamed.</p>';
        $did_something = true;
    } else {
        echo '<p style="color: red;">Error: ' . $wpdb->last_error . '</p>';
    }
}

// Check for and add status column if missing
if (!in_array('status', $column_names)) {
    echo '<h2>Adding status column</h2>';
    
    $result = $wpdb->query("ALTER TABLE $job_logs_table ADD COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'started' AFTER `log_date`");
    
    if ($result !== false) {
        echo '<p style="color: green;">Success! Status column added.</p>';
        $did_something = true;
    } else {
        echo '<p style="color: red;">Error: ' . $wpdb->last_error . '</p>';
    }
}

// Add boxes_completed and items_completed columns if they don't exist
if (!in_array('boxes_completed', $column_names)) {
    echo '<h2>Adding boxes_completed column</h2>';
    
    $result = $wpdb->query("ALTER TABLE $job_logs_table ADD COLUMN `boxes_completed` INT UNSIGNED NULL AFTER `duration_minutes`");
    
    if ($result !== false) {
        echo '<p style="color: green;">Success! boxes_completed column added.</p>';
        $did_something = true;
    } else {
        echo '<p style="color: red;">Error: ' . $wpdb->last_error . '</p>';
    }
}

if (!in_array('items_completed', $column_names)) {
    echo '<h2>Adding items_completed column</h2>';
    
    $result = $wpdb->query("ALTER TABLE $job_logs_table ADD COLUMN `items_completed` INT UNSIGNED NULL AFTER `boxes_completed`");
    
    if ($result !== false) {
        echo '<p style="color: green;">Success! items_completed column added.</p>';
        $did_something = true;
    } else {
        echo '<p style="color: red;">Error: ' . $wpdb->last_error . '</p>';
    }
}

if (!$did_something) {
    echo '<h2>No changes needed!</h2>';
    echo '<p>All columns appear to be correct already.</p>';
}

// Get the updated columns
$updated_columns = $wpdb->get_results("SHOW COLUMNS FROM $job_logs_table");
$updated_column_names = array_map(function($col) { return $col->Field; }, $updated_columns);

echo '<h2>Updated Columns</h2>';
echo '<pre>' . print_r($updated_column_names, true) . '</pre>';

echo '<p><a href="' . admin_url('admin.php?page=oo_dashboard') . '">Return to Dashboard</a></p>'; 