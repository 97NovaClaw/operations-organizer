<?php
/**
 * Recreate Job Logs Table
 * 
 * This script completely drops and recreates the job_logs table with the correct schema.
 * WARNING: This will delete all job logs data!
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

// Add confirmation step
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo '<h1>⚠️ WARNING: This script will DELETE ALL JOB LOG DATA ⚠️</h1>';
    echo '<p>This script will completely drop and recreate the job_logs table with the correct schema.</p>';
    echo '<p>ALL EXISTING JOB LOG DATA WILL BE LOST!</p>';
    echo '<p>Are you sure you want to continue?</p>';
    echo '<p><a href="?confirm=yes" style="background: red; color: white; padding: 10px; text-decoration: none; display: inline-block; margin-right: 10px;">Yes, I understand - DELETE ALL JOB LOGS</a>';
    echo '<a href="' . admin_url('admin.php?page=oo_dashboard') . '" style="background: #444; color: white; padding: 10px; text-decoration: none; display: inline-block;">No, take me back to the Dashboard</a></p>';
    exit;
}

global $wpdb;
$job_logs_table = $wpdb->prefix . 'oo_job_logs';
$charset_collate = $wpdb->get_charset_collate();

echo '<h1>Recreating Job Logs Table</h1>';

// Check if the table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$job_logs_table'") === $job_logs_table;

if ($table_exists) {
    echo '<h2>Dropping existing table</h2>';
    $drop_result = $wpdb->query("DROP TABLE IF EXISTS $job_logs_table");
    
    if ($drop_result === false) {
        die('<p style="color: red;">Error dropping the table: ' . $wpdb->last_error . '</p>');
    }
    
    echo '<p style="color: green;">Successfully dropped the job_logs table.</p>';
}

echo '<h2>Creating new table with correct schema</h2>';

// Define the correct SQL schema for job_logs
$sql_job_logs = "CREATE TABLE $job_logs_table (
    log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id BIGINT UNSIGNED NOT NULL,
    stream_id INT UNSIGNED NOT NULL,
    phase_id INT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    start_time DATETIME NOT NULL,
    stop_time DATETIME NULL,
    duration_minutes INT UNSIGNED NULL,
    boxes_completed INT UNSIGNED NULL,
    items_completed INT UNSIGNED NULL,
    kpi_data JSON NULL,
    notes TEXT NULL,
    log_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'started',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (log_id),
    INDEX idx_job_id (job_id),
    INDEX idx_stream_id (stream_id),
    INDEX idx_phase_id (phase_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_start_time (start_time),
    INDEX idx_stop_time (stop_time),
    INDEX idx_log_date (log_date),
    INDEX idx_status (status)
) $charset_collate;";

// Try to load includes/upgrade.php file from WordPress
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// Use dbDelta which is more reliable for table creation
$result = dbDelta($sql_job_logs);

// Check if table was created
$table_created = $wpdb->get_var("SHOW TABLES LIKE '$job_logs_table'") === $job_logs_table;

if ($table_created) {
    echo '<p style="color: green;">Successfully created new job_logs table with the correct schema.</p>';
    
    // Get the current columns in the table
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $job_logs_table");
    $column_names = array_map(function($col) { return $col->Field; }, $columns);
    
    echo '<h2>New Table Columns</h2>';
    echo '<pre>' . print_r($column_names, true) . '</pre>';
    
    echo '<h2>Success!</h2>';
    echo '<p>The job_logs table has been completely recreated with the correct schema.</p>';
    echo '<p><strong>Note:</strong> All previous job log data has been deleted.</p>';
} else {
    echo '<p style="color: red;">Error: Failed to create the new table.</p>';
}

echo '<p><a href="' . admin_url('admin.php?page=oo_dashboard') . '" style="background: #0073aa; color: white; padding: 10px; text-decoration: none; display: inline-block;">Return to Dashboard</a></p>'; 