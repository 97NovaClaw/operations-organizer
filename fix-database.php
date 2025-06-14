<?php
/**
 * Fix Database Schema
 * 
 * This script updates the database structure to rename the column from stream_type_id to stream_id.
 * Place this file in the plugin root directory and run it once by visiting:
 * http://your-site.com/wp-admin/admin.php?page=oo_fix_database
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Add admin menu page to run the database fix
add_action('admin_menu', 'oo_add_database_fix_page');
function oo_add_database_fix_page() {
    // Add as a top-level menu page to ensure it's accessible
    add_menu_page(
        'Fix Database Schema',
        'DB Fix',
        'activate_plugins', // Requires ability to activate plugins (administrators)
        'oo_fix_database',
        'oo_run_database_fix',
        'dashicons-database-view',
        99 // Position at the bottom of menu
    );
    
    // Add submenu for dropping all tables
    add_submenu_page(
        'oo_fix_database',
        'Drop All Tables',
        'Drop All Tables',
        'activate_plugins',
        'oo_drop_all_tables',
        'oo_drop_all_tables_page'
    );
}

// Add an admin notice if schema update is needed
add_action('admin_notices', 'oo_schema_update_admin_notice');
function oo_schema_update_admin_notice() {
    // Only show to admins
    if (!current_user_can('activate_plugins')) {
        return;
    }
    
    // Check if we need to show the notice
    if (get_transient('oo_needs_schema_update')) {
        ?>
        <div class="notice notice-error" style="padding: 10px; border-left-width: 4px;">
            <h3 style="margin-top: 0;"><?php _e('Operations Organizer: Database Update Required', 'operations-organizer'); ?></h3>
            <p><strong><?php _e('Your database needs to be updated to work with the latest version of Operations Organizer.', 'operations-organizer'); ?></strong></p>
            <p><?php _e('Please click the button below to run the database update tool:', 'operations-organizer'); ?></p>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=oo_fix_database')); ?>" class="button button-primary">
                    <?php _e('Fix Database Schema', 'operations-organizer'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}

// Hook into OO creation to check schema
add_action('oo_after_db_create', 'oo_check_database_schema_after_create');
function oo_check_database_schema_after_create() {
    oo_log('Running schema check after database creation', __FUNCTION__);
    oo_check_database_schema();
}

// Check database schema on admin init, but only once per session
add_action('admin_init', 'oo_check_database_schema_init');
function oo_check_database_schema_init() {
    // Only check once per session - this avoids repeatedly checking on every page load
    if (!get_transient('oo_schema_check_completed')) {
        oo_check_database_schema();
        // Set a transient that expires after 1 hour
        set_transient('oo_schema_check_completed', true, HOUR_IN_SECONDS);
    }
}

// Main function to check database schema
function oo_check_database_schema() {
    global $wpdb;
    oo_log('Checking database schema for issues...', __FUNCTION__);
    
    $schema_needs_update = false;
    $phases_table = $wpdb->prefix . 'oo_phases';
    $employees_table = $wpdb->prefix . 'oo_employees';
    $job_logs_table = $wpdb->prefix . 'oo_job_logs';
    
    // Check phases table for stream_type_id vs stream_id
    if ($wpdb->get_var("SHOW TABLES LIKE '{$phases_table}'") === $phases_table) {
        $phases_columns = $wpdb->get_results("SHOW COLUMNS FROM {$phases_table}");
        $phases_column_names = array_map(function($col) { return $col->Field; }, $phases_columns);
        
        if (in_array('stream_type_id', $phases_column_names) && !in_array('stream_id', $phases_column_names)) {
            $schema_needs_update = true;
        }
        
        // Check for missing phase-related columns
        if (!in_array('phase_type', $phases_column_names) || 
            !in_array('default_kpi_units', $phases_column_names) || 
            !in_array('includes_kpi', $phases_column_names)) {
            $schema_needs_update = true;
        }
    }
    
    // Check employees table for employee_pin
    if ($wpdb->get_var("SHOW TABLES LIKE '{$employees_table}'") === $employees_table) {
        $employees_columns = $wpdb->get_results("SHOW COLUMNS FROM {$employees_table}");
        $employees_column_names = array_map(function($col) { return $col->Field; }, $employees_columns);
        
        if (!in_array('employee_pin', $employees_column_names)) {
            $schema_needs_update = true;
        }
    }

    // Check job_logs table for job_id vs job_number
    if ($wpdb->get_var("SHOW TABLES LIKE '{$job_logs_table}'") === $job_logs_table) {
        $job_logs_columns = $wpdb->get_results("SHOW COLUMNS FROM {$job_logs_table}");
        $job_logs_column_names = array_map(function($col) { return $col->Field; }, $job_logs_columns);
        
        // Check if job_logs has job_number instead of job_id
        if (in_array('job_number', $job_logs_column_names) && !in_array('job_id', $job_logs_column_names)) {
            $schema_needs_update = true;
        }
        
        // Check if job_logs has stream_type_id instead of stream_id
        if (in_array('stream_type_id', $job_logs_column_names) && !in_array('stream_id', $job_logs_column_names)) {
            $schema_needs_update = true;
        }
        
        // Check if job_logs is missing the status column
        if (!in_array('status', $job_logs_column_names)) {
            $schema_needs_update = true;
        }
        
        // Check if job_logs is missing boxes_completed or items_completed columns
        if (!in_array('boxes_completed', $job_logs_column_names) || !in_array('items_completed', $job_logs_column_names)) {
            $schema_needs_update = true;
        }
        
        // Check for stop_time and end_time columns
        if (!in_array('stop_time', $job_logs_column_names) || !in_array('end_time', $job_logs_column_names)) {
            $schema_needs_update = true;
        }
    }
    
    if ($schema_needs_update) {
        oo_log('Schema update needed! Setting transient to trigger notice.', __FUNCTION__);
        set_transient('oo_needs_schema_update', true, WEEK_IN_SECONDS); // Long expiry since it's important
    } else {
        oo_log('Schema check passed, no update needed.', __FUNCTION__);
        delete_transient('oo_needs_schema_update');
    }
    
    return $schema_needs_update;
}

function oo_run_database_fix() {
    global $wpdb;

    // Handle debug toggle form submission
    if (isset($_POST['oo_debug_action']) && check_admin_referer('oo_toggle_debug_log')) {
        if ($_POST['oo_debug_action'] === 'enable') {
            update_option('oo_enable_debugging', 'yes');
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Debug logging has been enabled.', 'operations-organizer') . '</p></div>';
        } elseif ($_POST['oo_debug_action'] === 'disable') {
            update_option('oo_enable_debugging', 'no');
             echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Debug logging has been disabled.', 'operations-organizer') . '</p></div>';
        }
    }

    $phases_table = $wpdb->prefix . 'oo_phases';
    $employees_table = $wpdb->prefix . 'oo_employees';
    $job_logs_table = $wpdb->prefix . 'oo_job_logs';
    
    echo '<div class="wrap">';
    echo '<h1>Operations Organizer - Fix Database Schema</h1>';
    
    // Ensure user has necessary permissions
    if (!current_user_can('activate_plugins')) {
        echo '<div class="notice notice-error"><p>You do not have sufficient permissions to perform database updates. Please contact your site administrator.</p></div>';
        echo '</div>';
        return;
    }
    
    // Check if the phases table exists
    $phases_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$phases_table}'") === $phases_table;
    $employees_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$employees_table}'") === $employees_table;
    $job_logs_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$job_logs_table}'") === $job_logs_table;
    
    if (!$phases_table_exists && !$employees_table_exists && !$job_logs_table_exists) {
        echo '<div class="notice notice-warning"><p>The database tables do not exist. This may indicate that the plugin activation did not complete properly. Try deactivating and reactivating the plugin.</p></div>';
        echo '</div>';
        return;
    }
    
    $updates_performed = false;
    
    // Fix phases table - rename stream_type_id to stream_id if needed
    if ($phases_table_exists) {
        $phases_columns = $wpdb->get_results("SHOW COLUMNS FROM {$phases_table}");
        $phases_column_names = array_map(function($col) { return $col->Field; }, $phases_columns);
        
        if (in_array('stream_type_id', $phases_column_names) && !in_array('stream_id', $phases_column_names)) {
            echo '<h3>Fixing Phases Table</h3>';
            echo '<p>Renaming <code>stream_type_id</code> column to <code>stream_id</code>...</p>';
            
            $rename_result = $wpdb->query("ALTER TABLE {$phases_table} CHANGE COLUMN `stream_type_id` `stream_id` INT UNSIGNED NOT NULL");
            
            if ($rename_result !== false) {
                echo '<p class="notice notice-success" style="padding: 10px;">✅ Successfully renamed column.</p>';
                $updates_performed = true;
            } else {
                echo '<p class="notice notice-error" style="padding: 10px;">❌ Error renaming column: ' . $wpdb->last_error . '</p>';
            }
        } elseif (in_array('stream_id', $phases_column_names)) {
            echo '<p>✅ The phases table already has the correct <code>stream_id</code> column.</p>';
        }

        // Check for missing columns in phases table
        if (!in_array('phase_type', $phases_column_names)) {
            echo '<p>Adding missing <code>phase_type</code> column to phases table...</p>';
            $result = $wpdb->query("ALTER TABLE {$phases_table} ADD COLUMN `phase_type` VARCHAR(50) NULL AFTER `order_in_stream`");
            if ($result !== false) {
                echo '<p class="notice notice-success" style="padding: 10px;">✅ Successfully added phase_type column.</p>';
                $updates_performed = true;
            } else {
                echo '<p class="notice notice-error" style="padding: 10px;">❌ Error adding phase_type column: ' . $wpdb->last_error . '</p>';
            }
        }
        
        if (!in_array('default_kpi_units', $phases_column_names)) {
            echo '<p>Adding missing <code>default_kpi_units</code> column to phases table...</p>';
            $result = $wpdb->query("ALTER TABLE {$phases_table} ADD COLUMN `default_kpi_units` VARCHAR(50) NULL AFTER `phase_type`");
            if ($result !== false) {
                echo '<p class="notice notice-success" style="padding: 10px;">✅ Successfully added default_kpi_units column.</p>';
                $updates_performed = true;
            } else {
                echo '<p class="notice notice-error" style="padding: 10px;">❌ Error adding default_kpi_units column: ' . $wpdb->last_error . '</p>';
            }
        }
        
        if (!in_array('includes_kpi', $phases_column_names)) {
            echo '<p>Adding missing <code>includes_kpi</code> column to phases table...</p>';
            $result = $wpdb->query("ALTER TABLE {$phases_table} ADD COLUMN `includes_kpi` BOOLEAN NOT NULL DEFAULT 1 AFTER `default_kpi_units`");
            if ($result !== false) {
                echo '<p class="notice notice-success" style="padding: 10px;">✅ Successfully added includes_kpi column.</p>';
                $updates_performed = true;
            } else {
                echo '<p class="notice notice-error" style="padding: 10px;">❌ Error adding includes_kpi column: ' . $wpdb->last_error . '</p>';
            }
        }
    }
    
    // Fix employees table - add missing employee_pin column if needed
    if ($employees_table_exists) {
        $employees_columns = $wpdb->get_results("SHOW COLUMNS FROM {$employees_table}");
        $employees_column_names = array_map(function($col) { return $col->Field; }, $employees_columns);
        
        if (!in_array('employee_pin', $employees_column_names)) {
            echo '<h3>Fixing Employees Table</h3>';
            echo '<p>Adding missing <code>employee_pin</code> column...</p>';
            
            $add_column_result = $wpdb->query("ALTER TABLE {$employees_table} ADD COLUMN `employee_pin` VARCHAR(255) NULL AFTER `employee_number`");
            
            if ($add_column_result !== false) {
                echo '<p class="notice notice-success" style="padding: 10px;">✅ Successfully added employee_pin column.</p>';
                $updates_performed = true;
            } else {
                echo '<p class="notice notice-error" style="padding: 10px;">❌ Error adding employee_pin column: ' . $wpdb->last_error . '</p>';
            }
        } else {
            echo '<p>✅ The employees table already has the <code>employee_pin</code> column.</p>';
        }
    }

    // Fix job_logs table - multiple issues to address
    if ($job_logs_table_exists) {
        echo '<h3>Fixing Job Logs Table</h3>';
        $job_logs_columns = $wpdb->get_results("SHOW COLUMNS FROM {$job_logs_table}");
        $job_logs_column_names = array_map(function($col) { return $col->Field; }, $job_logs_columns);
        
        // 1. Fix job_number to job_id if needed
        if (in_array('job_number', $job_logs_column_names) && !in_array('job_id', $job_logs_column_names)) {
            echo '<p>Renaming <code>job_number</code> column to <code>job_id</code>...</p>';
            
            $rename_result = $wpdb->query("ALTER TABLE {$job_logs_table} CHANGE COLUMN `job_number` `job_id` BIGINT UNSIGNED NOT NULL");
            
            if ($rename_result !== false) {
                echo '<p class="notice notice-success" style="padding: 10px;">✅ Successfully renamed job_number to job_id.</p>';
                $updates_performed = true;
            } else {
                echo '<p class="notice notice-error" style="padding: 10px;">❌ Error renaming job_number column: ' . $wpdb->last_error . '</p>';
            }
        } elseif (in_array('job_id', $job_logs_column_names)) {
            echo '<p>✅ The job_logs table already has the correct <code>job_id</code> column.</p>';
        }
        
        // 2. Fix stream_type_id to stream_id if needed
        if (in_array('stream_type_id', $job_logs_column_names) && !in_array('stream_id', $job_logs_column_names)) {
            echo '<p>Renaming <code>stream_type_id</code> column to <code>stream_id</code> in job_logs table...</p>';
            
            $rename_stream_result = $wpdb->query("ALTER TABLE {$job_logs_table} CHANGE COLUMN `stream_type_id` `stream_id` INT UNSIGNED NOT NULL");
            
            if ($rename_stream_result !== false) {
                echo '<p class="notice notice-success" style="padding: 10px;">✅ Successfully renamed stream_type_id to stream_id in job_logs table.</p>';
                $updates_performed = true;
            } else {
                echo '<p class="notice notice-error" style="padding: 10px;">❌ Error renaming column: ' . $wpdb->last_error . '</p>';
            }
        } elseif (in_array('stream_id', $job_logs_column_names)) {
            echo '<p>✅ The job_logs table already has the correct <code>stream_id</code> column.</p>';
        }
        
        // 3. Add missing status column if needed
        if (!in_array('status', $job_logs_column_names)) {
            echo '<p>Adding missing <code>status</code> column to job_logs table...</p>';
            
            $add_status_result = $wpdb->query("ALTER TABLE {$job_logs_table} ADD COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'started' AFTER `log_date`");
            
            if ($add_status_result !== false) {
                echo '<p class="notice notice-success" style="padding: 10px;">✅ Successfully added status column to job_logs table.</p>';
                $updates_performed = true;
            } else {
                echo '<p class="notice notice-error" style="padding: 10px;">❌ Error adding status column: ' . $wpdb->last_error . '</p>';
            }
        } else {
            echo '<p>✅ The job_logs table already has the <code>status</code> column.</p>';
        }
        
        // 4. Add boxes_completed and items_completed columns if they don't exist
        if (!in_array('boxes_completed', $job_logs_column_names)) {
            echo '<p>Adding missing <code>boxes_completed</code> column to job_logs table...</p>';
            
            $add_boxes_result = $wpdb->query("ALTER TABLE {$job_logs_table} ADD COLUMN `boxes_completed` INT UNSIGNED NULL AFTER `duration_minutes`");
            
            if ($add_boxes_result !== false) {
                echo '<p class="notice notice-success" style="padding: 10px;">✅ Successfully added boxes_completed column to job_logs table.</p>';
                $updates_performed = true;
            } else {
                echo '<p class="notice notice-error" style="padding: 10px;">❌ Error adding boxes_completed column: ' . $wpdb->last_error . '</p>';
            }
        }
        
        if (!in_array('items_completed', $job_logs_column_names)) {
            echo '<p>Adding missing <code>items_completed</code> column to job_logs table...</p>';
            
            $add_items_result = $wpdb->query("ALTER TABLE {$job_logs_table} ADD COLUMN `items_completed` INT UNSIGNED NULL AFTER `boxes_completed`");
            
            if ($add_items_result !== false) {
                echo '<p class="notice notice-success" style="padding: 10px;">✅ Successfully added items_completed column to job_logs table.</p>';
                $updates_performed = true;
            } else {
                echo '<p class="notice notice-error" style="padding: 10px;">❌ Error adding items_completed column: ' . $wpdb->last_error . '</p>';
            }
        }
        
        // 5. Fix stop_time and end_time issues
        // The schema defines stop_time but code may be using end_time
        if (!in_array('stop_time', $job_logs_column_names)) {
            echo '<p>Adding missing <code>stop_time</code> column to job_logs table...</p>';
            
            $add_stop_time_result = $wpdb->query("ALTER TABLE {$job_logs_table} ADD COLUMN `stop_time` DATETIME NULL AFTER `start_time`");
            
            if ($add_stop_time_result !== false) {
                echo '<p class="notice notice-success" style="padding: 10px;">✅ Successfully added stop_time column to job_logs table.</p>';
                $updates_performed = true;
            } else {
                echo '<p class="notice notice-error" style="padding: 10px;">❌ Error adding stop_time column: ' . $wpdb->last_error . '</p>';
            }
        }
        
        if (!in_array('end_time', $job_logs_column_names)) {
            echo '<p>Adding missing <code>end_time</code> column to job_logs table (alias for stop_time)...</p>';
            
            // Use the stop_time column as a base for the end_time column (they are the same data)
            $add_end_time_result = $wpdb->query("ALTER TABLE {$job_logs_table} ADD COLUMN `end_time` DATETIME NULL AFTER `stop_time`");
            
            if ($add_end_time_result !== false) {
                echo '<p class="notice notice-success" style="padding: 10px;">✅ Successfully added end_time column to job_logs table.</p>';
                
                // Copy existing stop_time values to end_time if stop_time exists
                if (in_array('stop_time', $job_logs_column_names)) {
                    $sync_time_result = $wpdb->query("UPDATE {$job_logs_table} SET end_time = stop_time WHERE stop_time IS NOT NULL");
                    if ($sync_time_result !== false) {
                        echo '<p class="notice notice-success" style="padding: 10px;">✅ Successfully synced stop_time values to end_time column.</p>';
                    } else {
                        echo '<p class="notice notice-error" style="padding: 10px;">❌ Error syncing stop_time to end_time: ' . $wpdb->last_error . '</p>';
                    }
                }
                
                $updates_performed = true;
            } else {
                echo '<p class="notice notice-error" style="padding: 10px;">❌ Error adding end_time column: ' . $wpdb->last_error . '</p>';
            }
        }
    }
    
    if ($updates_performed) {
        echo '<div class="notice notice-success" style="padding: 10px; margin-top: 15px;"><p><strong>Database schema has been successfully updated!</strong></p></div>';
        delete_transient('oo_needs_schema_update');
    } else {
        echo '<div class="notice notice-info" style="padding: 10px; margin-top: 15px;"><p><strong>No database changes were needed. Your schema is up to date.</strong></p></div>';
    }
    
    echo '<p><a href="' . admin_url('admin.php?page=oo_dashboard') . '" class="button button-primary">Return to Dashboard</a></p>';

    ?>
    <hr>
    <div style="margin-top: 30px; padding: 20px; border: 1px solid #ddd; background: #f9f9f9;">
        <h2><?php esc_html_e('Debug Logging', 'operations-organizer'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('oo_toggle_debug_log'); ?>
            <?php $is_debug_enabled = get_option('oo_enable_debugging') === 'yes'; ?>
            <p><?php printf(esc_html__('Debug logging is currently %s.', 'operations-organizer'), '<strong>' . ($is_debug_enabled ? esc_html__('ENABLED', 'operations-organizer') : esc_html__('DISABLED', 'operations-organizer')) . '</strong>'); ?></p>
            <p class="description"><?php echo $is_debug_enabled ? esc_html__('This will write detailed information to the debug.log file in the plugin directory. Use this only when troubleshooting issues.', 'operations-organizer') : esc_html__('Enable debugging to start logging detailed information.', 'operations-organizer'); ?></p>
            
            <?php if ($is_debug_enabled): ?>
                <button type="submit" name="oo_debug_action" value="disable" class="button button-secondary"><?php esc_html_e('Disable Debugging', 'operations-organizer'); ?></button>
            <?php else: ?>
                <button type="submit" name="oo_debug_action" value="enable" class="button button-primary"><?php esc_html_e('Enable Debugging', 'operations-organizer'); ?></button>
            <?php endif; ?>
        </form>
    </div>
    <?php

    echo '</div>';
}

// Hook into plugin activation to run schema check
register_activation_hook(OO_PLUGIN_FILE, 'oo_activation_schema_check');
function oo_activation_schema_check() {
    oo_check_database_schema();
}

// Function to handle dropping all tables
function oo_drop_all_tables_page() {
    global $wpdb;
    
    echo '<div class="wrap">';
    echo '<h1>Operations Organizer - Drop All Tables</h1>';
    
    // Ensure user has necessary permissions
    if (!current_user_can('activate_plugins')) {
        echo '<div class="notice notice-error"><p>You do not have sufficient permissions to perform database operations. Please contact your site administrator.</p></div>';
        echo '</div>';
        return;
    }
    
    // Check if the user has confirmed the operation
    if (isset($_POST['confirm_drop']) && $_POST['confirm_drop'] === 'yes' && isset($_POST['oo_drop_tables_nonce']) && wp_verify_nonce($_POST['oo_drop_tables_nonce'], 'oo_drop_tables_action')) {
        
        // List of all plugin tables
        $tables = array(
            $wpdb->prefix . 'oo_buildings',
            $wpdb->prefix . 'oo_employees',
            $wpdb->prefix . 'oo_expenses',
            $wpdb->prefix . 'oo_expense_types',
            $wpdb->prefix . 'oo_jobs',
            $wpdb->prefix . 'oo_job_logs',
            $wpdb->prefix . 'oo_job_streams',
            $wpdb->prefix . 'oo_job_streams_link',
            $wpdb->prefix . 'oo_phases',
            $wpdb->prefix . 'oo_streams',
            $wpdb->prefix . 'oo_stream_data_art',
            $wpdb->prefix . 'oo_stream_data_content',
            $wpdb->prefix . 'oo_stream_data_electronics',
            $wpdb->prefix . 'oo_stream_data_soft_content',
            $wpdb->prefix . 'oo_stream_types'
        );
        
        $success = true;
        $tables_dropped = 0;
        $errors = array();
        
        echo '<h2>Dropping Tables...</h2>';
        
        foreach ($tables as $table) {
            // Check if the table exists first
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
                $result = $wpdb->query("DROP TABLE $table");
                
                if ($result === false) {
                    $success = false;
                    $errors[] = "Failed to drop $table: " . $wpdb->last_error;
                    echo '<p class="notice notice-error" style="padding: 10px;">❌ Error dropping table ' . esc_html($table) . ': ' . esc_html($wpdb->last_error) . '</p>';
                } else {
                    $tables_dropped++;
                    echo '<p class="notice notice-success" style="padding: 10px;">✅ Successfully dropped table ' . esc_html($table) . '</p>';
                }
            } else {
                echo '<p class="notice notice-info" style="padding: 10px;">ℹ️ Table ' . esc_html($table) . ' does not exist.</p>';
            }
        }
        
        if ($success) {
            echo '<div class="notice notice-success" style="padding: 15px; margin-top: 20px;">';
            echo '<h3 style="margin-top: 0;">Operation Complete</h3>';
            echo '<p>Successfully dropped ' . $tables_dropped . ' tables. You can now deactivate and reactivate the plugin to recreate the tables with a fresh schema.</p>';
            echo '</div>';
        } else {
            echo '<div class="notice notice-error" style="padding: 15px; margin-top: 20px;">';
            echo '<h3 style="margin-top: 0;">Operation Completed with Errors</h3>';
            echo '<p>Encountered errors while dropping some tables:</p>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        echo '<p><a href="' . admin_url('plugins.php') . '" class="button button-primary">Go to Plugins Page</a></p>';
        
    } else {
        // Display confirmation form
        ?>
        <div class="notice notice-warning" style="padding: 15px; margin-top: 20px;">
            <h3 style="margin-top: 0; color: #d63638;">⚠️ WARNING: This operation cannot be undone! ⚠️</h3>
            <p><strong>This will permanently delete ALL data in the Operations Organizer plugin tables:</strong></p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li>All jobs, phases, and streams</li>
                <li>All employee records</li>
                <li>All job logs and time tracking data</li>
                <li>All expenses and related data</li>
                <li>All buildings and location data</li>
            </ul>
            <p><strong>You should only use this when you want to completely start over or when uninstalling the plugin.</strong></p>
        </div>
        
        <form method="post" style="max-width: 800px; margin-top: 20px; background: #f8f8f8; padding: 20px; border: 1px solid #ddd;">
            <?php wp_nonce_field('oo_drop_tables_action', 'oo_drop_tables_nonce'); ?>
            <h3>Confirm Table Deletion</h3>
            <p>
                <label>
                    <input type="checkbox" name="confirm_drop" value="yes" required style="margin-right: 10px;">
                    I understand that this will permanently delete all Operations Organizer data and cannot be undone.
                </label>
            </p>
            <p>
                <input type="submit" class="button button-primary" style="background: #d63638; border-color: #d63638;" value="Drop All Plugin Tables">
                <a href="<?php echo admin_url('admin.php?page=oo_fix_database'); ?>" class="button" style="margin-left: 10px;">Cancel</a>
            </p>
        </form>
        <?php
    }
    
    echo '</div>';
} 