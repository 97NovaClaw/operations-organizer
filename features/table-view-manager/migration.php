<?php
/**
 * Table View Manager - Database Migration
 * 
 * Creates the database table for storing user table preferences
 * 
 * @package OperationsOrganizer
 * @subpackage Features/TableViewManager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class OO_Table_View_Manager_Migration {
    
    /**
     * Run the migration to create the preferences table
     * 
     * @return void
     */
    public static function run() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'oo_user_table_preferences';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            preference_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            table_id VARCHAR(255) NOT NULL,
            preferences JSON NOT NULL,
            last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (preference_id),
            UNIQUE KEY unique_user_table (user_id, table_id),
            KEY idx_user_id (user_id),
            KEY idx_table_id (table_id)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Log the migration
        error_log('[Table View Manager] Database table created/verified: ' . $table_name);
    }
    
    /**
     * Check if the migration has been run
     * 
     * @return bool
     */
    public static function is_migrated() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'oo_user_table_preferences';
        return $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    }
    
    /**
     * Drop the table (for cleanup/uninstall)
     * 
     * @return void
     */
    public static function rollback() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'oo_user_table_preferences';
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        
        error_log('[Table View Manager] Database table dropped: ' . $table_name);
    }
} 