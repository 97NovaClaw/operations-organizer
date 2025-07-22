<?php
/**
 * Master Log Core Class
 * 
 * Main business logic for activity logging
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Master_Log {
    
    /**
     * Main logging method - called via do_action('oo_log_activity', $args)
     * 
     * @param array $args {
     *     Required and optional arguments for logging
     *     
     *     @type string $activity_type    Required. Type of activity (e.g., 'PHASE_CHANGED')
     *     @type string $activity_level   Required. 'job' or 'stream'
     *     @type int    $job_id          Required. The job ID
     *     @type int    $stream_id       Optional. The stream ID (for stream-level events)
     *     @type int    $job_stream_id   Optional. The job-stream link ID
     *     @type string $activity_category Optional. Category for grouping
     *     @type int    $user_id         Optional. Defaults to current user
     *     @type int    $related_id      Optional. ID of related entity
     *     @type string $related_type    Optional. Type of related entity
     *     @type string $field_name      Optional. For field updates
     *     @type mixed  $old_value       Optional. Previous value
     *     @type mixed  $new_value       Optional. New value
     *     @type string $user_notes      Optional. User-provided notes
     *     @type array  $metadata        Optional. Additional structured data
     *     @type string $capability_required Optional. Required capability
     * }
     * @return int|false Log ID on success, false on failure
     */
    public static function log_activity($args) {
        // Validate required fields
        if (empty($args['activity_type']) || empty($args['job_id'])) {
            oo_log('[MASTER_LOG] Missing required fields for logging', __METHOD__);
            return false;
        }
        
        // Set defaults
        $defaults = array(
            'activity_level' => 'stream',
            'user_id' => get_current_user_id(),
            'capability_required' => 'oo_manage_operations'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Validate user
        if (empty($args['user_id'])) {
            oo_log('[MASTER_LOG] No user ID available for logging', __METHOD__);
            return false;
        }
        
        // Add context metadata
        if (!isset($args['metadata'])) {
            $args['metadata'] = array();
        }
        
        // Add request context
        $args['metadata']['request_context'] = array(
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ajax' => wp_doing_ajax(),
            'cron' => wp_doing_cron()
        );
        
        // Get user info
        $user = get_user_by('id', $args['user_id']);
        if ($user) {
            $args['metadata']['user_info'] = array(
                'login' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name
            );
        }
        
        // Get job info if not in metadata
        if (empty($args['metadata']['job_number'])) {
            $job = OO_DB::get_job($args['job_id']);
            if ($job) {
                $args['metadata']['job_number'] = $job->job_number;
                $args['metadata']['client_name'] = $job->client_name;
            }
        }
        
        // Get stream info if stream_id provided
        if (!empty($args['stream_id']) && empty($args['metadata']['stream_name'])) {
            $stream = OO_DB::get_stream($args['stream_id']);
            if ($stream) {
                $args['metadata']['stream_name'] = $stream->stream_name;
            }
        }
        
        // Log the activity
        oo_log('[MASTER_LOG] Logging activity: ' . $args['activity_type'] . ' for job ' . $args['job_id'], __METHOD__);
        
        $log_id = OO_Master_Log_Database::insert_log($args);
        
        if ($log_id) {
            oo_log('[MASTER_LOG] Activity logged successfully with ID: ' . $log_id, __METHOD__);
            
            // Trigger action for other features to hook into
            do_action('oo_activity_logged', $log_id, $args);
        }
        
        return $log_id;
    }
    
    /**
     * Get formatted activity description
     * 
     * @param object $log_entry Log entry from database
     * @return string Formatted description
     */
    public static function get_activity_description($log_entry) {
        return OO_Master_Log_Formatters::format_activity($log_entry);
    }
    
    /**
     * Get activity icon/emoji
     * 
     * @param string $activity_type
     * @return string Icon or emoji
     */
    public static function get_activity_icon($activity_type) {
        $icons = array(
            'PHASE_CHANGED' => '🔄',
            'JOB_CREATED' => '✨',
            'JOB_UPDATED' => '✏️',
            'JOB_DELETED' => '🗑️',
            'STREAM_ASSIGNED_TO_JOB' => '🔗',
            'STREAM_REMOVED_FROM_JOB' => '🔓',
            'NOTE_ADDED' => '📝',
            'PHASE_CREATED' => '➕',
            'PHASE_UPDATED' => '🔧',
            'PHASE_DELETED' => '❌',
            'STATUS_CHANGED' => '🚦',
            'DATE_CHANGED' => '📅',
            'ASSIGNMENT_CHANGED' => '👤'
        );
        
        return isset($icons[$activity_type]) ? $icons[$activity_type] : '📌';
    }
    
    /**
     * Get activity color class
     * 
     * @param string $activity_type
     * @return string CSS class
     */
    public static function get_activity_color_class($activity_type) {
        $colors = array(
            'PHASE_CHANGED' => 'activity-blue',
            'JOB_CREATED' => 'activity-green',
            'JOB_UPDATED' => 'activity-yellow',
            'JOB_DELETED' => 'activity-red',
            'STREAM_ASSIGNED_TO_JOB' => 'activity-purple',
            'STREAM_REMOVED_FROM_JOB' => 'activity-orange',
            'NOTE_ADDED' => 'activity-teal',
            'PHASE_CREATED' => 'activity-green',
            'PHASE_UPDATED' => 'activity-yellow',
            'PHASE_DELETED' => 'activity-red'
        );
        
        return isset($colors[$activity_type]) ? $colors[$activity_type] : 'activity-gray';
    }
    
    /**
     * Check if user can view log
     * 
     * @param object $log_entry
     * @param int $user_id Optional. Defaults to current user
     * @return bool
     */
    public static function can_user_view_log($log_entry, $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // For now, check basic capability
        // This can be expanded based on your role system
        $capability = $log_entry->capability_required ?? 'oo_manage_operations';
        
        return user_can($user_id, $capability);
    }
    
    /**
     * Get recent activities for dashboard widget
     * 
     * @param int $limit Number of activities to return
     * @return array
     */
    public static function get_recent_activities($limit = 10) {
        global $wpdb;
        $table_name = OO_Master_Log_Database::get_table_name();
        
        $query = $wpdb->prepare(
            "SELECT l.*, j.job_number, j.client_name, s.stream_name
             FROM {$table_name} l
             LEFT JOIN {$wpdb->prefix}oo_jobs j ON l.job_id = j.job_id
             LEFT JOIN {$wpdb->prefix}oo_streams s ON l.stream_id = s.stream_id
             ORDER BY l.created_at DESC
             LIMIT %d",
            $limit
        );
        
        $results = $wpdb->get_results($query);
        
        // Decode metadata
        foreach ($results as &$log) {
            if ($log->metadata) {
                $log->metadata = json_decode($log->metadata, true);
            }
        }
        
        return $results;
    }
    
    /**
     * Get activity summary for a job
     * 
     * @param int $job_id
     * @return array Summary statistics
     */
    public static function get_job_activity_summary($job_id) {
        global $wpdb;
        $table_name = OO_Master_Log_Database::get_table_name();
        
        // Get counts by activity type
        $type_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT activity_type, COUNT(*) as count
             FROM {$table_name}
             WHERE job_id = %d
             GROUP BY activity_type",
            $job_id
        ), OBJECT_K);
        
        // Get recent phase changes
        $recent_phases = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name}
             WHERE job_id = %d AND activity_type = 'PHASE_CHANGED'
             ORDER BY created_at DESC
             LIMIT 5",
            $job_id
        ));
        
        // Get active users
        $active_users = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id
             FROM {$table_name}
             WHERE job_id = %d
             AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $job_id
        ));
        
        return array(
            'total_activities' => array_sum(wp_list_pluck($type_counts, 'count')),
            'type_counts' => $type_counts,
            'recent_phases' => $recent_phases,
            'active_users' => count($active_users),
            'last_activity' => $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(created_at) FROM {$table_name} WHERE job_id = %d",
                $job_id
            ))
        );
    }
} 