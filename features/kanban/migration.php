<?php
/**
 * Kanban Database Migration Handler
 * 
 * Handles creating new tables and modifying existing ones for Kanban functionality
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Kanban_Migration {
    
    /**
     * Check if migration is needed and run if necessary
     */
    public static function check_and_run_migration() {
        // Check if we've already run this migration
        $migration_version = get_option('oo_kanban_migration_version', '0');
        $current_version = '1.0.0'; // Increment this when schema changes
        
        if (version_compare($migration_version, $current_version, '<')) {
            self::run_migration();
            update_option('oo_kanban_migration_version', $current_version);
        }
    }
    
    /**
     * Run the migration
     */
    public static function run_migration() {
        oo_log('[KANBAN_MIGRATION] Starting Kanban feature migration', __METHOD__);
        
        global $wpdb;
        
        // Step 1: Create the activity log table
        if (!OO_Kanban_Schema::activity_log_table_exists()) {
            oo_log('[KANBAN_MIGRATION] Creating stream activity log table', __METHOD__);
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $sql = OO_Kanban_Schema::get_activity_log_table_sql();
            dbDelta($sql);
            
            // Verify table was created
            if (OO_Kanban_Schema::activity_log_table_exists()) {
                oo_log('[KANBAN_MIGRATION] Activity log table created successfully', __METHOD__);
            } else {
                oo_log('[KANBAN_MIGRATION] ERROR: Failed to create activity log table', __METHOD__);
                return false;
            }
        } else {
            oo_log('[KANBAN_MIGRATION] Activity log table already exists', __METHOD__);
        }
        
        // Step 2: Add current_phase_id column to job streams table
        if (!OO_Kanban_Schema::current_phase_column_exists()) {
            oo_log('[KANBAN_MIGRATION] Adding current_phase_id column to job streams table', __METHOD__);
            
            $modifications = OO_Kanban_Schema::get_job_streams_modification_sql();
            foreach ($modifications as $sql) {
                $result = $wpdb->query($sql);
                if ($result === false) {
                    oo_log('[KANBAN_MIGRATION] ERROR: Failed to execute SQL: ' . $sql . ' Error: ' . $wpdb->last_error, __METHOD__);
                    return false;
                }
            }
            
            // Verify column was added
            if (OO_Kanban_Schema::current_phase_column_exists()) {
                oo_log('[KANBAN_MIGRATION] current_phase_id column added successfully', __METHOD__);
            } else {
                oo_log('[KANBAN_MIGRATION] ERROR: Failed to add current_phase_id column', __METHOD__);
                return false;
            }
        } else {
            oo_log('[KANBAN_MIGRATION] current_phase_id column already exists', __METHOD__);
        }
        
        // Step 3: Backfill initial phase data for existing job streams
        self::backfill_initial_phases();
        
        oo_log('[KANBAN_MIGRATION] Migration completed successfully', __METHOD__);
        return true;
    }
    
    /**
     * Backfill initial phase data for existing job streams
     * 
     * For any job_stream records where current_phase_id is NULL,
     * set it to the first phase of that stream
     */
    private static function backfill_initial_phases() {
        global $wpdb;
        
        oo_log('[KANBAN_MIGRATION] Starting backfill of initial phases', __METHOD__);
        
        $job_streams_table = $wpdb->prefix . 'oo_job_streams_link';
        $phases_table = $wpdb->prefix . 'oo_phases';
        $activity_log_table = $wpdb->prefix . 'oo_stream_activity_log';
        
        // Get all job streams without a current phase
        $job_streams_without_phase = $wpdb->get_results("
            SELECT job_stream_id, job_id, stream_id 
            FROM {$job_streams_table} 
            WHERE current_phase_id IS NULL
        ");
        
        if (empty($job_streams_without_phase)) {
            oo_log('[KANBAN_MIGRATION] No job streams need phase backfill', __METHOD__);
            return;
        }
        
        oo_log('[KANBAN_MIGRATION] Found ' . count($job_streams_without_phase) . ' job streams needing phase assignment', __METHOD__);
        
        $activity_types = OO_Kanban_Schema::get_activity_types();
        $system_user_id = 0; // System user for automated actions
        
        foreach ($job_streams_without_phase as $job_stream) {
            // Get the first phase for this stream
            $first_phase = $wpdb->get_row($wpdb->prepare("
                SELECT phase_id, phase_name 
                FROM {$phases_table} 
                WHERE stream_id = %d 
                AND is_active = 1
                ORDER BY order_in_stream ASC 
                LIMIT 1
            ", $job_stream->stream_id));
            
            if (!$first_phase) {
                oo_log('[KANBAN_MIGRATION] WARNING: No active phases found for stream_id: ' . $job_stream->stream_id, __METHOD__);
                continue;
            }
            
            // Start transaction for data integrity
            $wpdb->query('START TRANSACTION');
            
            try {
                // Update the current phase
                $update_result = $wpdb->update(
                    $job_streams_table,
                    array('current_phase_id' => $first_phase->phase_id),
                    array('job_stream_id' => $job_stream->job_stream_id),
                    array('%d'),
                    array('%d')
                );
                
                if ($update_result === false) {
                    throw new Exception('Failed to update current_phase_id');
                }
                
                // Create an initial activity log entry
                $log_result = $wpdb->insert(
                    $activity_log_table,
                    array(
                        'job_stream_id' => $job_stream->job_stream_id,
                        'user_id' => $system_user_id,
                        'activity_type' => $activity_types['JOB_CREATED'],
                        'from_phase_id' => null,
                        'to_phase_id' => $first_phase->phase_id,
                        'notes' => 'Initial phase assignment during migration',
                        'metadata' => json_encode(array(
                            'migration' => true,
                            'phase_name' => $first_phase->phase_name
                        ))
                    ),
                    array('%d', '%d', '%s', '%d', '%d', '%s', '%s')
                );
                
                if ($log_result === false) {
                    throw new Exception('Failed to create activity log entry');
                }
                
                $wpdb->query('COMMIT');
                oo_log('[KANBAN_MIGRATION] Successfully set initial phase for job_stream_id: ' . $job_stream->job_stream_id, __METHOD__);
                
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                oo_log('[KANBAN_MIGRATION] ERROR: Failed to set initial phase for job_stream_id: ' . $job_stream->job_stream_id . ' - ' . $e->getMessage(), __METHOD__);
            }
        }
        
        oo_log('[KANBAN_MIGRATION] Backfill completed', __METHOD__);
    }
} 