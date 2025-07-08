<?php
/**
 * Standalone script to fix missing columns in the oo_job_streams_link table
 * 
 * Run this script once to add the missing columns that were causing fatal errors.
 */

// WordPress installation check
if (!defined('ABSPATH')) {
    // Try to find WordPress config
    $wp_config_paths = [
        __DIR__ . '/wp-config.php',
        dirname(__DIR__) . '/wp-config.php',
        dirname(dirname(__DIR__)) . '/wp-config.php'
    ];
    
    $wp_config_found = false;
    foreach ($wp_config_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_config_found = true;
            break;
        }
    }
    
    if (!$wp_config_found) {
        die('WordPress configuration not found. Please run this script from within your WordPress installation.');
    }
}

// Force WordPress to load
if (!function_exists('wp_get_current_user')) {
    require_once ABSPATH . 'wp-includes/wp-db.php';
    require_once ABSPATH . 'wp-includes/functions.php';
    require_once ABSPATH . 'wp-includes/option.php';
}

echo "🔧 Operations Organizer - Missing Columns Fix Script\n";
echo "==================================================\n\n";

global $wpdb;

$table_name = $wpdb->prefix . 'oo_job_streams_link';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

if (!$table_exists) {
    echo "❌ Error: Table {$table_name} does not exist.\n";
    echo "Please activate the Operations Organizer plugin first.\n";
    exit(1);
}

echo "✅ Found table: {$table_name}\n";

// Get current columns
$current_columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
$column_names = array_map(function($col) { return $col->Field; }, $current_columns);

echo "📋 Current columns: " . implode(', ', $column_names) . "\n\n";

// Define missing columns that need to be added
$missing_columns = [
    'status_in_stream' => "VARCHAR(50) NOT NULL DEFAULT 'Not Started'",
    'assigned_manager_id' => "BIGINT UNSIGNED NULL",
    'start_date_stream' => "DATE NULL",
    'due_date_stream' => "DATE NULL", 
    'building_id' => "BIGINT UNSIGNED NULL",
    'notes' => "TEXT NULL"
];

$columns_added = 0;
$errors = [];

echo "🔍 Checking for missing columns...\n";

foreach ($missing_columns as $column_name => $column_definition) {
    if (!in_array($column_name, $column_names)) {
        echo "➕ Adding missing column: {$column_name}...";
        
        $sql = "ALTER TABLE {$table_name} ADD COLUMN `{$column_name}` {$column_definition}";
        $result = $wpdb->query($sql);
        
        if ($result !== false) {
            echo " ✅ Success\n";
            $columns_added++;
        } else {
            echo " ❌ Failed\n";
            $errors[] = "Failed to add {$column_name}: " . $wpdb->last_error;
        }
    } else {
        echo "✅ Column {$column_name} already exists\n";
    }
}

echo "\n🏁 Summary:\n";
echo "===========\n";
echo "Columns added: {$columns_added}\n";

if (!empty($errors)) {
    echo "❌ Errors encountered:\n";
    foreach ($errors as $error) {
        echo "   - {$error}\n";
    }
    exit(1);
} else {
    echo "✅ All missing columns have been successfully added!\n";
    echo "\n💡 Your Operations Organizer plugin should now work without fatal errors.\n";
    echo "You can safely delete this fix-missing-columns.php file after running it.\n";
    exit(0);
} 