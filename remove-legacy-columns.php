<?php
/**
 * Remove Legacy KPI Columns
 * 
 * This script removes the legacy KPI columns from the Operations Organizer database tables:
 * - boxes_completed and items_completed from oo_job_logs table
 * - phase_type and default_kpi_units from oo_phases table
 * 
 * Run this after verifying that the new JSON-based KPI system is working correctly.
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
$phases_table = $wpdb->prefix . 'oo_phases';

echo '<h1>Remove Legacy KPI Columns</h1>';
echo '<div style="max-width: 800px; margin: 20px 0;">';

// Verify tables exist
$job_logs_exists = ($wpdb->get_var("SHOW TABLES LIKE '$job_logs_table'") === $job_logs_table);
$phases_exists = ($wpdb->get_var("SHOW TABLES LIKE '$phases_table'") === $phases_table);

if (!$job_logs_exists) {
    echo '<p style="color: red;">Error: Job logs table does not exist: ' . $job_logs_table . '</p>';
    echo '</div>';
    return;
}

if (!$phases_exists) {
    echo '<p style="color: red;">Error: Phases table does not exist: ' . $phases_table . '</p>';
    echo '</div>';
    return;
}

$changes_made = false;

// Check for and remove legacy columns from job_logs table
echo '<h2>Job Logs Table (' . $job_logs_table . ')</h2>';

$job_logs_columns = $wpdb->get_results("SHOW COLUMNS FROM $job_logs_table");
$job_logs_column_names = array_map(function($col) { return $col->Field; }, $job_logs_columns);

echo '<p><strong>Current columns:</strong> ' . implode(', ', $job_logs_column_names) . '</p>';

// Remove boxes_completed column
if (in_array('boxes_completed', $job_logs_column_names)) {
    echo '<h3>Removing boxes_completed column</h3>';
    
    $result = $wpdb->query("ALTER TABLE $job_logs_table DROP COLUMN `boxes_completed`");
    
    if ($result !== false) {
        echo '<p style="color: green;">✅ Successfully removed boxes_completed column.</p>';
        $changes_made = true;
    } else {
        echo '<p style="color: red;">❌ Error removing boxes_completed column: ' . $wpdb->last_error . '</p>';
    }
} else {
    echo '<p style="color: blue;">ℹ️ boxes_completed column not found (already removed or never existed).</p>';
}

// Remove items_completed column
if (in_array('items_completed', $job_logs_column_names)) {
    echo '<h3>Removing items_completed column</h3>';
    
    $result = $wpdb->query("ALTER TABLE $job_logs_table DROP COLUMN `items_completed`");
    
    if ($result !== false) {
        echo '<p style="color: green;">✅ Successfully removed items_completed column.</p>';
        $changes_made = true;
    } else {
        echo '<p style="color: red;">❌ Error removing items_completed column: ' . $wpdb->last_error . '</p>';
    }
} else {
    echo '<p style="color: blue;">ℹ️ items_completed column not found (already removed or never existed).</p>';
}

// Check for and remove legacy columns from phases table
echo '<h2>Phases Table (' . $phases_table . ')</h2>';

$phases_columns = $wpdb->get_results("SHOW COLUMNS FROM $phases_table");
$phases_column_names = array_map(function($col) { return $col->Field; }, $phases_columns);

echo '<p><strong>Current columns:</strong> ' . implode(', ', $phases_column_names) . '</p>';

// Remove phase_type column
if (in_array('phase_type', $phases_column_names)) {
    echo '<h3>Removing phase_type column</h3>';
    
    $result = $wpdb->query("ALTER TABLE $phases_table DROP COLUMN `phase_type`");
    
    if ($result !== false) {
        echo '<p style="color: green;">✅ Successfully removed phase_type column.</p>';
        $changes_made = true;
    } else {
        echo '<p style="color: red;">❌ Error removing phase_type column: ' . $wpdb->last_error . '</p>';
    }
} else {
    echo '<p style="color: blue;">ℹ️ phase_type column not found (already removed or never existed).</p>';
}

// Remove default_kpi_units column
if (in_array('default_kpi_units', $phases_column_names)) {
    echo '<h3>Removing default_kpi_units column</h3>';
    
    $result = $wpdb->query("ALTER TABLE $phases_table DROP COLUMN `default_kpi_units`");
    
    if ($result !== false) {
        echo '<p style="color: green;">✅ Successfully removed default_kpi_units column.</p>';
        $changes_made = true;
    } else {
        echo '<p style="color: red;">❌ Error removing default_kpi_units column: ' . $wpdb->last_error . '</p>';
    }
} else {
    echo '<p style="color: blue;">ℹ️ default_kpi_units column not found (already removed or never existed).</p>';
}

// Summary
echo '<h2>Summary</h2>';

if ($changes_made) {
    echo '<div style="background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px;">';
    echo '<h3 style="margin-top: 0;">✅ Legacy Column Removal Complete!</h3>';
    echo '<p>The legacy KPI columns have been successfully removed from your database. Your Operations Organizer plugin is now running with the new JSON-based KPI system only.</p>';
    echo '<p><strong>What was removed:</strong></p>';
    echo '<ul>';
    echo '<li>Legacy <code>boxes_completed</code> and <code>items_completed</code> columns from job logs table</li>';
    echo '<li>Legacy <code>phase_type</code> and <code>default_kpi_units</code> columns from phases table</li>';
    echo '</ul>';
    echo '<p><strong>Next steps:</strong></p>';
    echo '<ol>';
    echo '<li>Test your dashboard and KPI functionality to ensure everything works correctly</li>';
    echo '<li>Delete the legacy migration scripts: <code>force-fix.php</code>, <code>fix-database.php</code>, <code>direct-fix.php</code></li>';
    echo '<li>Delete this script: <code>remove-legacy-columns.php</code></li>';
    echo '</ol>';
    echo '</div>';
} else {
    echo '<div style="background: #cce5ff; color: #004085; padding: 15px; border: 1px solid #99ccff; border-radius: 4px;">';
    echo '<h3 style="margin-top: 0;">ℹ️ No Changes Needed</h3>';
    echo '<p>No legacy columns were found in your database. Your Operations Organizer plugin is already clean!</p>';
    echo '</div>';
}

// Show updated table structures
echo '<h2>Updated Table Structures</h2>';

$updated_job_logs_columns = $wpdb->get_results("SHOW COLUMNS FROM $job_logs_table");
$updated_job_logs_column_names = array_map(function($col) { return $col->Field; }, $updated_job_logs_columns);

$updated_phases_columns = $wpdb->get_results("SHOW COLUMNS FROM $phases_table");
$updated_phases_column_names = array_map(function($col) { return $col->Field; }, $updated_phases_columns);

echo '<h3>Job Logs Table Columns</h3>';
echo '<pre>' . implode("\n", $updated_job_logs_column_names) . '</pre>';

echo '<h3>Phases Table Columns</h3>';
echo '<pre>' . implode("\n", $updated_phases_column_names) . '</pre>';

echo '<p style="margin-top: 30px;"><a href="' . admin_url('admin.php?page=oo_dashboard') . '" class="button button-primary">Return to Dashboard</a></p>';
echo '</div>';
?> 