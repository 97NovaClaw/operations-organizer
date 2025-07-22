<?php
/**
 * Master Log Migration
 * 
 * Handles database creation and migration of existing activity logs
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Master_Log_Migration {
    
    /**
     * Run the migration
     * 
     * @return bool Success status
     */
    public static function run() {
        oo_log('[MASTER_LOG_MIGRATION] Starting migration', __METHOD__);
        
        // Step 1: Create the master log table
        if (!self::create_table()) {
            return false;
        }
        
        // Step 2: Migrate existing stream activity logs
        if (!self::migrate_stream_activity_logs()) {
            return false;
        }
        
        // Step 3: Migrate any other historical data
        if (!self::migrate_historical_data()) {
            return false;
        }
        
        oo_log('[MASTER_LOG_MIGRATION] Migration completed successfully', __METHOD__);
        return true;
    }
    
    /**
     * Create the master log table
     */
    private static function create_table() {
        oo_log('[MASTER_LOG_MIGRATION] Creating master log table', __METHOD__);
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $sql = OO_Master_Log_Database::get_table_schema();
        dbDelta($sql);
        
        // Verify table was created
        if (!OO_Master_Log_Database::table_exists()) {
            oo_log('[MASTER_LOG_MIGRATION] ERROR: Failed to create master log table', __METHOD__);
            return false;
        }
        
        oo_log('[MASTER_LOG_MIGRATION] Master log table created successfully', __METHOD__);
        return true;
    }
    
    /**
     * Migrate existing stream activity logs
     */
    private static function migrate_stream_activity_logs() {
        global $wpdb;
        
        oo_log('[MASTER_LOG_MIGRATION] Starting stream activity log migration', __METHOD__);
        
        $stream_log_table = $wpdb->prefix . 'oo_stream_activity_log';
        
        // Check if stream activity log table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$stream_log_table}'") !== $stream_log_table) {
            oo_log('[MASTER_LOG_MIGRATION] Stream activity log table does not exist, skipping migration', __METHOD__);
            return true;
        }
        
        // Get all stream activity logs
        $stream_logs = $wpdb->get_results("
            SELECT 
                sal.*,
                jsl.job_id,
                jsl.stream_id
            FROM {$stream_log_table} sal
            LEFT JOIN {$wpdb->prefix}oo_job_streams_link jsl 
                ON sal.job_stream_id = jsl.job_stream_id
            WHERE jsl.job_id IS NOT NULL
            ORDER BY sal.created_at ASC
        ");
        
        if (empty($stream_logs)) {
            oo_log('[MASTER_LOG_MIGRATION] No stream activity logs to migrate', __METHOD__);
            return true;
        }
        
        oo_log('[MASTER_LOG_MIGRATION] Found ' . count($stream_logs) . ' stream activity logs to migrate', __METHOD__);
        
        $migrated = 0;
        $failed = 0;
        
        foreach ($stream_logs as $log) {
            // Prepare data for master log
            $master_log_data = array(
                'job_id' => $log->job_id,
                'stream_id' => $log->stream_id,
                'job_stream_id' => $log->job_stream_id,
                'activity_type' => $log->activity_type,
                'activity_level' => 'stream',
                'user_id' => $log->user_id,
                'created_at' => $log->created_at,
                'user_notes' => $log->notes,
                'metadata' => array()
            );
            
            // Handle phase change specific data
            if ($log->activity_type === 'PHASE_CHANGE') {
                $master_log_data['activity_type'] = 'PHASE_CHANGED';
                $master_log_data['related_id'] = $log->to_phase_id;
                $master_log_data['related_type'] = 'phase';
                
                // Get phase names
                $from_phase = $log->from_phase_id ? OO_DB::get_phase($log->from_phase_id) : null;
                $to_phase = OO_DB::get_phase($log->to_phase_id);
                
                $master_log_data['metadata'] = array(
                    'from_phase_id' => $log->from_phase_id,
                    'to_phase_id' => $log->to_phase_id,
                    'from_phase_name' => $from_phase ? $from_phase->phase_name : null,
                    'to_phase_name' => $to_phase ? $to_phase->phase_name : null,
                    'migrated_from' => 'stream_activity_log'
                );
            }
            
            // Parse existing metadata if any
            if (!empty($log->metadata)) {
                $existing_metadata = json_decode($log->metadata, true);
                if (is_array($existing_metadata)) {
                    $master_log_data['metadata'] = array_merge($master_log_data['metadata'], $existing_metadata);
                }
            }
            
            // Add migration timestamp
            $master_log_data['metadata']['migrated_at'] = current_time('mysql');
            $master_log_data['metadata']['original_log_id'] = $log->activity_log_id;
            
            // Insert into master log
            $result = $wpdb->insert(
                OO_Master_Log_Database::get_table_name(),
                array(
                    'job_id' => $master_log_data['job_id'],
                    'stream_id' => $master_log_data['stream_id'],
                    'job_stream_id' => $master_log_data['job_stream_id'],
                    'activity_type' => $master_log_data['activity_type'],
                    'activity_level' => $master_log_data['activity_level'],
                    'user_id' => $master_log_data['user_id'],
                    'created_at' => $master_log_data['created_at'],
                    'related_id' => isset($master_log_data['related_id']) ? $master_log_data['related_id'] : null,
                    'related_type' => isset($master_log_data['related_type']) ? $master_log_data['related_type'] : null,
                    'user_notes' => $master_log_data['user_notes'],
                    'metadata' => json_encode($master_log_data['metadata'])
                )
            );
            
            if ($result !== false) {
                $migrated++;
            } else {
                $failed++;
                oo_log('[MASTER_LOG_MIGRATION] Failed to migrate log ID: ' . $log->activity_log_id . ' Error: ' . $wpdb->last_error, __METHOD__);
            }
        }
        
        oo_log("[MASTER_LOG_MIGRATION] Migration complete. Migrated: {$migrated}, Failed: {$failed}", __METHOD__);
        
        return $failed === 0;
    }
    
    /**
     * Migrate other historical data
     */
    private static function migrate_historical_data() {
        oo_log('[MASTER_LOG_MIGRATION] Checking for other historical data to migrate', __METHOD__);
        
        // This is a placeholder for future migrations
        // For example, we could scan job logs, phase logs, etc.
        // and create retroactive entries in the master log
        
        // For now, we'll just return true
        return true;
    }
    
    /**
     * Rollback migration (for testing/development)
     */
    public static function rollback() {
        global $wpdb;
        
        oo_log('[MASTER_LOG_MIGRATION] Rolling back migration', __METHOD__);
        
        $table_name = OO_Master_Log_Database::get_table_name();
        
        // Drop the table
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        
        // Remove version option
        delete_option('oo_master_log_version');
        
        oo_log('[MASTER_LOG_MIGRATION] Rollback completed', __METHOD__);
        
        return true;
    }
} 