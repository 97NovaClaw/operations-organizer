<?php
/**
 * Master Log Database Operations
 * 
 * Handles all database interactions for the master activity log
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Master_Log_Database {
    
    /**
     * Get the master log table name
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'oo_master_activity_log';
    }
    
    /**
     * Get the table schema SQL
     */
    public static function get_table_schema() {
        global $wpdb;
        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();
        
        return "CREATE TABLE {$table_name} (
            log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            
            -- Core identifiers
            job_id BIGINT UNSIGNED NOT NULL,
            stream_id BIGINT UNSIGNED NULL, -- Primary stream (for backward compatibility)
            job_stream_id BIGINT UNSIGNED NULL,
            stream_ids JSON NULL, -- Multiple streams (array of stream IDs)
            
            -- Event details
            activity_type VARCHAR(50) NOT NULL,
            activity_level ENUM('job', 'stream') NOT NULL DEFAULT 'stream',
            activity_category VARCHAR(50) NULL,
            
            -- User and timestamp
            user_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            
            -- Related entity (flexible reference)
            related_id BIGINT UNSIGNED NULL,
            related_type VARCHAR(50) NULL,
            
            -- Change tracking
            field_name VARCHAR(100) NULL,
            old_value TEXT NULL,
            new_value TEXT NULL,
            
            -- Notes and metadata
            user_notes TEXT NULL,
            metadata LONGTEXT NULL,
            
            -- Security placeholder
            capability_required VARCHAR(50) NULL DEFAULT 'oo_manage_operations',
            
            PRIMARY KEY (log_id),
            INDEX idx_job_id (job_id),
            INDEX idx_stream_id (stream_id),
            INDEX idx_job_stream_id (job_stream_id),
            INDEX idx_user_id (user_id),
            INDEX idx_activity_type (activity_type),
            INDEX idx_created_at (created_at),
            INDEX idx_related (related_id, related_type),
            INDEX idx_job_created (job_id, created_at)
        ) {$charset_collate};";
    }
    
    /**
     * Check if the table exists
     */
    public static function table_exists() {
        global $wpdb;
        $table_name = self::get_table_name();
        return $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    }
    
    /**
     * Insert a log entry
     * 
     * @param array $data Log entry data
     * @return int|false The log ID on success, false on failure
     */
    public static function insert_log($data) {
        global $wpdb;
        
        // Sanitize and prepare data
        $insert_data = array(
            'job_id' => intval($data['job_id']),
            'stream_id' => isset($data['stream_id']) ? intval($data['stream_id']) : null,
            'job_stream_id' => isset($data['job_stream_id']) ? intval($data['job_stream_id']) : null,
            'stream_ids' => isset($data['stream_ids']) ? json_encode(array_map('intval', (array)$data['stream_ids'])) : null,
            'activity_type' => sanitize_text_field($data['activity_type']),
            'activity_level' => in_array($data['activity_level'], ['job', 'stream']) ? $data['activity_level'] : 'stream',
            'activity_category' => isset($data['activity_category']) ? sanitize_text_field($data['activity_category']) : null,
            'user_id' => intval($data['user_id']),
            'related_id' => isset($data['related_id']) ? intval($data['related_id']) : null,
            'related_type' => isset($data['related_type']) ? sanitize_text_field($data['related_type']) : null,
            'field_name' => isset($data['field_name']) ? sanitize_text_field($data['field_name']) : null,
            'old_value' => isset($data['old_value']) ? wp_kses_post($data['old_value']) : null,
            'new_value' => isset($data['new_value']) ? wp_kses_post($data['new_value']) : null,
            'user_notes' => isset($data['user_notes']) ? wp_kses_post($data['user_notes']) : null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'capability_required' => isset($data['capability_required']) ? sanitize_text_field($data['capability_required']) : 'oo_manage_operations'
        );
        
        // Remove null values for cleaner insert
        $insert_data = array_filter($insert_data, function($value) {
            return $value !== null;
        });
        
        $result = $wpdb->insert(
            self::get_table_name(),
            $insert_data
        );
        
        if ($result === false) {
            oo_log('[MASTER_LOG_DB] Failed to insert log: ' . $wpdb->last_error, __METHOD__);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get logs for a specific job
     * 
     * @param int $job_id The job ID
     * @param array $args Optional arguments for filtering
     * @return array Array of log entries
     */
    public static function get_logs_for_job($job_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'activity_type' => null,
            'stream_id' => null,
            'user_id' => null,
            'date_from' => null,
            'date_to' => null,
            'order_by' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table_name = self::get_table_name();
        $where_clauses = array("job_id = %d");
        $where_values = array($job_id);
        
        // Build WHERE clauses
        if ($args['activity_type']) {
            $where_clauses[] = "activity_type = %s";
            $where_values[] = $args['activity_type'];
        }
        
        if ($args['stream_id']) {
            $where_clauses[] = "stream_id = %d";
            $where_values[] = $args['stream_id'];
        }
        
        if ($args['user_id']) {
            $where_clauses[] = "user_id = %d";
            $where_values[] = $args['user_id'];
        }
        
        if ($args['date_from']) {
            $where_clauses[] = "created_at >= %s";
            $where_values[] = $args['date_from'];
        }
        
        if ($args['date_to']) {
            $where_clauses[] = "created_at <= %s";
            $where_values[] = $args['date_to'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Validate order_by
        $allowed_order_by = array('created_at', 'activity_type', 'user_id');
        $order_by = in_array($args['order_by'], $allowed_order_by) ? $args['order_by'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Build query
        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE {$where_sql}
             ORDER BY {$order_by} {$order}
             LIMIT %d OFFSET %d",
            array_merge($where_values, array($args['limit'], $args['offset']))
        );
        
        $results = $wpdb->get_results($query);
        
        // Decode metadata JSON
        foreach ($results as &$log) {
            if ($log->metadata) {
                $log->metadata = json_decode($log->metadata, true);
            }
        }
        
        return $results;
    }
    
    /**
     * Get logs for a specific stream
     */
    public static function get_logs_for_stream($stream_id, $args = array()) {
        $args['stream_id'] = $stream_id;
        return self::get_logs_for_job(0, $args); // Will be filtered by stream_id
    }
    
    /**
     * Get count of logs for pagination
     */
    public static function get_log_count($job_id = null, $stream_id = null, $filters = array()) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        $where_clauses = array();
        $where_values = array();
        
        if ($job_id) {
            $where_clauses[] = "job_id = %d";
            $where_values[] = $job_id;
        }
        
        if ($stream_id) {
            $where_clauses[] = "stream_id = %d";
            $where_values[] = $stream_id;
        }
        
        // Add additional filters
        if (!empty($filters['activity_type'])) {
            $where_clauses[] = "activity_type = %s";
            $where_values[] = $filters['activity_type'];
        }
        
        if (!empty($filters['user_id'])) {
            $where_clauses[] = "user_id = %d";
            $where_values[] = $filters['user_id'];
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} {$where_sql}",
                $where_values
            );
        } else {
            $query = "SELECT COUNT(*) FROM {$table_name}";
        }
        
        return intval($wpdb->get_var($query));
    }
    
    /**
     * Get all unique activity types for filtering
     */
    public static function get_activity_types() {
        global $wpdb;
        $table_name = self::get_table_name();
        
        return $wpdb->get_col(
            "SELECT DISTINCT activity_type 
             FROM {$table_name} 
             ORDER BY activity_type ASC"
        );
    }
    
    /**
     * Clean up old logs (optional maintenance)
     * 
     * @param int $days_to_keep Number of days to keep logs
     * @return int Number of deleted rows
     */
    public static function cleanup_old_logs($days_to_keep = 365) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE created_at < %s",
                $cutoff_date
            )
        );
        
        oo_log("[MASTER_LOG_DB] Cleaned up {$deleted} old log entries", __METHOD__);
        
        return $deleted;
    }
} 