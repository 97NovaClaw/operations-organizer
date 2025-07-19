<?php
/**
 * Kanban Database Schema Definitions
 * 
 * Defines the structure for stream activity logging and phase tracking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Kanban_Schema {
    
    /**
     * Get the SQL for creating the stream activity log table
     * 
     * This table records all phase changes and other stream-related activities
     * providing a complete audit trail for each job within a stream
     * 
     * @return string SQL CREATE TABLE statement
     */
    public static function get_activity_log_table_sql() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'oo_stream_activity_log';
        
        return "CREATE TABLE {$table_name} (
            activity_log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_stream_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            activity_type VARCHAR(50) NOT NULL,
            from_phase_id BIGINT UNSIGNED NULL,
            to_phase_id BIGINT UNSIGNED NULL,
            notes TEXT NULL,
            metadata LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (activity_log_id),
            INDEX idx_job_stream_id (job_stream_id),
            INDEX idx_user_id (user_id),
            INDEX idx_activity_type (activity_type),
            INDEX idx_created_at (created_at)
        ) {$charset_collate};";
    }
    
    /**
     * Get the SQL for modifying the job streams link table
     * 
     * Adds current_phase_id column to track the current phase of a job in a stream
     * This serves as a cached value for quick Kanban board rendering
     * 
     * @return array Array of SQL statements to execute
     */
    public static function get_job_streams_modification_sql() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'oo_job_streams_link';
        
        return array(
            "ALTER TABLE {$table_name} 
             ADD COLUMN current_phase_id BIGINT UNSIGNED NULL AFTER stream_id",
            
            "ALTER TABLE {$table_name} 
             ADD INDEX idx_current_phase_id (current_phase_id)"
        );
    }
    
    /**
     * Get activity types as constants for consistency
     * 
     * @return array Activity type constants
     */
    public static function get_activity_types() {
        return array(
            'PHASE_CHANGE' => 'PHASE_CHANGE',
            'JOB_CREATED' => 'JOB_CREATED',
            'NOTE_ADDED' => 'NOTE_ADDED',
            'STATUS_CHANGE' => 'STATUS_CHANGE',
            'ASSIGNMENT_CHANGE' => 'ASSIGNMENT_CHANGE',
            'DATE_CHANGE' => 'DATE_CHANGE',
            'CUSTOM' => 'CUSTOM'
        );
    }
    
    /**
     * Check if the activity log table exists
     * 
     * @return bool
     */
    public static function activity_log_table_exists() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'oo_stream_activity_log';
        return $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    }
    
    /**
     * Check if current_phase_id column exists in job streams table
     * 
     * @return bool
     */
    public static function current_phase_column_exists() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'oo_job_streams_link';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'current_phase_id'");
        return !empty($column_exists);
    }
} 