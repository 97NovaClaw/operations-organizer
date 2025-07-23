<?php
/**
 * Kanban Database Operations
 * 
 * Handles all database interactions for the Kanban feature
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Kanban_Database {
    
    /**
     * Record a stream phase change with full transaction support
     * 
     * This is the primary method for moving a job between phases
     * It updates both the current state and creates an audit log entry
     * 
     * @param int $job_stream_id The job-stream link ID
     * @param int $from_phase_id The phase moving from (can be null for initial)
     * @param int $to_phase_id The phase moving to
     * @param int $user_id The user making the change
     * @param string $notes Optional notes about the change
     * @param array $metadata Optional additional data to store
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function record_phase_change($job_stream_id, $from_phase_id, $to_phase_id, $user_id, $notes = '', $metadata = array()) {
        global $wpdb;
        
        oo_log('[KANBAN_DB] Recording phase change for job_stream_id: ' . $job_stream_id, __METHOD__);
        oo_log('[KANBAN_DB] From phase: ' . ($from_phase_id ?: 'NULL') . ', To phase: ' . $to_phase_id, __METHOD__);
        
        $job_streams_table = $wpdb->prefix . 'oo_job_streams_link';
        $activity_log_table = $wpdb->prefix . 'oo_stream_activity_log';
        $activity_types = OO_Kanban_Schema::get_activity_types();
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Step 1: Insert activity log record
            $log_data = array(
                'job_stream_id' => intval($job_stream_id),
                'user_id' => intval($user_id),
                'activity_type' => $activity_types['PHASE_CHANGE'],
                'from_phase_id' => $from_phase_id ? intval($from_phase_id) : null,
                'to_phase_id' => intval($to_phase_id),
                'notes' => sanitize_textarea_field($notes),
                'metadata' => !empty($metadata) ? json_encode($metadata) : null,
                'created_at' => current_time('mysql', 1)
            );
            
            $log_formats = array('%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s');
            
            $log_result = $wpdb->insert($activity_log_table, $log_data, $log_formats);
            
            if ($log_result === false) {
                throw new Exception('Failed to insert activity log: ' . $wpdb->last_error);
            }
            
            oo_log('[KANBAN_DB] Activity log entry created with ID: ' . $wpdb->insert_id, __METHOD__);
            
            // Step 2: Get the phase name for status_in_stream
            $phases_table = $wpdb->prefix . 'oo_phases';
            $to_phase = $wpdb->get_row($wpdb->prepare(
                "SELECT phase_name FROM {$phases_table} WHERE phase_id = %d",
                $to_phase_id
            ));
            
            if (!$to_phase) {
                throw new Exception('Failed to get phase name for phase_id: ' . $to_phase_id);
            }
            
            // Step 3: Update current phase in job streams table (both fields for compatibility)
            $update_result = $wpdb->update(
                $job_streams_table,
                array(
                    'current_phase_id' => intval($to_phase_id),
                    'status_in_stream' => $to_phase->phase_name,
                    'updated_at' => current_time('mysql', 1)
                ),
                array('job_stream_id' => intval($job_stream_id)),
                array('%d', '%s', '%s'),
                array('%d')
            );
            
            if ($update_result === false) {
                throw new Exception('Failed to update current phase: ' . $wpdb->last_error);
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            oo_log('[KANBAN_DB] Phase change recorded successfully', __METHOD__);
            
            // Fire action hook for other features to respond to phase changes
            do_action('oo_kanban_phase_changed', $job_stream_id, $from_phase_id, $to_phase_id, $user_id, $notes);
            
            return true;
            
        } catch (Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            
            oo_log('[KANBAN_DB] ERROR: ' . $e->getMessage(), __METHOD__);
            return new WP_Error('phase_change_failed', $e->getMessage());
        }
    }
    
    /**
     * Get the activity log for a job stream
     * 
     * @param int $job_stream_id The job-stream link ID
     * @param array $args Optional arguments for filtering/pagination
     * @return array Array of activity log entries
     */
    public static function get_activity_log($job_stream_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'activity_type' => null,
            'user_id' => null,
            'date_from' => null,
            'date_to' => null,
            'limit' => 50,
            'offset' => 0,
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $activity_log_table = $wpdb->prefix . 'oo_stream_activity_log';
        $users_table = $wpdb->users;
        $phases_table = $wpdb->prefix . 'oo_phases';
        
        // Build query
        $sql = "SELECT 
                    al.*,
                    u.display_name as user_name,
                    fp.phase_name as from_phase_name,
                    tp.phase_name as to_phase_name
                FROM {$activity_log_table} al
                LEFT JOIN {$users_table} u ON al.user_id = u.ID
                LEFT JOIN {$phases_table} fp ON al.from_phase_id = fp.phase_id
                LEFT JOIN {$phases_table} tp ON al.to_phase_id = tp.phase_id
                WHERE al.job_stream_id = %d";
        
        $query_params = array($job_stream_id);
        
        // Add filters
        if (!empty($args['activity_type'])) {
            $sql .= " AND al.activity_type = %s";
            $query_params[] = $args['activity_type'];
        }
        
        if (!empty($args['user_id'])) {
            $sql .= " AND al.user_id = %d";
            $query_params[] = intval($args['user_id']);
        }
        
        if (!empty($args['date_from'])) {
            $sql .= " AND al.created_at >= %s";
            $query_params[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $sql .= " AND al.created_at <= %s";
            $query_params[] = $args['date_to'];
        }
        
        // Add ordering
        $order = in_array(strtoupper($args['order']), array('ASC', 'DESC')) ? $args['order'] : 'DESC';
        $sql .= " ORDER BY al.created_at {$order}";
        
        // Add pagination
        if ($args['limit'] > 0) {
            $sql .= " LIMIT %d OFFSET %d";
            $query_params[] = intval($args['limit']);
            $query_params[] = intval($args['offset']);
        }
        
        $prepared_sql = $wpdb->prepare($sql, $query_params);
        $results = $wpdb->get_results($prepared_sql);
        
        // Decode metadata for each result
        foreach ($results as &$result) {
            if (!empty($result->metadata)) {
                $result->metadata = json_decode($result->metadata, true);
            }
        }
        
        return $results;
    }
    
    /**
     * Add a note to the activity log
     * 
     * @param int $job_stream_id The job-stream link ID
     * @param int $user_id The user adding the note
     * @param string $note The note content
     * @param array $metadata Optional additional data
     * @return int|WP_Error The log entry ID on success, WP_Error on failure
     */
    public static function add_stream_note($job_stream_id, $user_id, $note, $metadata = array()) {
        global $wpdb;
        
        $activity_log_table = $wpdb->prefix . 'oo_stream_activity_log';
        $activity_types = OO_Kanban_Schema::get_activity_types();
        
        $result = $wpdb->insert(
            $activity_log_table,
            array(
                'job_stream_id' => intval($job_stream_id),
                'user_id' => intval($user_id),
                'activity_type' => $activity_types['NOTE_ADDED'],
                'notes' => sanitize_textarea_field($note),
                'metadata' => !empty($metadata) ? json_encode($metadata) : null,
                'created_at' => current_time('mysql', 1)
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('note_add_failed', 'Failed to add note: ' . $wpdb->last_error);
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get current phase information for a job stream
     * 
     * @param int $job_stream_id The job-stream link ID
     * @return object|null Phase object or null if not found
     */
    public static function get_current_phase($job_stream_id) {
        global $wpdb;
        
        $job_streams_table = $wpdb->prefix . 'oo_job_streams_link';
        $phases_table = $wpdb->prefix . 'oo_phases';
        
        $sql = "SELECT p.* 
                FROM {$phases_table} p
                INNER JOIN {$job_streams_table} js ON p.phase_id = js.current_phase_id
                WHERE js.job_stream_id = %d";
        
        return $wpdb->get_row($wpdb->prepare($sql, $job_stream_id));
    }
    
    /**
     * Get phase statistics for a stream
     * 
     * @param int $stream_id The stream ID
     * @return array Array of phase statistics
     */
    public static function get_phase_statistics($stream_id) {
        global $wpdb;
        
        $job_streams_table = $wpdb->prefix . 'oo_job_streams_link';
        $phases_table = $wpdb->prefix . 'oo_phases';
        
        $sql = "SELECT 
                    p.phase_id,
                    p.phase_name,
                    p.order_in_stream,
                    COUNT(js.job_stream_id) as job_count
                FROM {$phases_table} p
                LEFT JOIN {$job_streams_table} js ON p.phase_id = js.current_phase_id
                WHERE p.stream_id = %d
                AND p.is_active = 1
                GROUP BY p.phase_id
                ORDER BY p.order_in_stream ASC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $stream_id));
    }
} 