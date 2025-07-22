<?php
/**
 * Master Log Formatters
 * 
 * Converts raw log data into human-readable descriptions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Master_Log_Formatters {
    
    /**
     * Format activity log entry into human-readable description
     * 
     * @param object $log_entry Log entry from database
     * @return string Formatted description
     */
    public static function format_activity($log_entry) {
        $method = 'format_' . strtolower($log_entry->activity_type);
        
        if (method_exists(__CLASS__, $method)) {
            return self::$method($log_entry);
        }
        
        // Default format
        return self::format_default($log_entry);
    }
    
    /**
     * Format phase change activity
     */
    private static function format_phase_changed($log) {
        $metadata = is_array($log->metadata) ? $log->metadata : json_decode($log->metadata, true);
        
        $from = !empty($metadata['from_phase_name']) ? $metadata['from_phase_name'] : 'Start';
        $to = $metadata['to_phase_name'] ?? 'Unknown';
        
        $description = sprintf(
            'moved from <strong>%s</strong> to <strong>%s</strong>',
            esc_html($from),
            esc_html($to)
        );
        
        if (!empty($log->user_notes)) {
            $description .= sprintf(' with note: "<em>%s</em>"', esc_html($log->user_notes));
        }
        
        return $description;
    }
    
    /**
     * Format job created activity
     */
    private static function format_job_created($log) {
        $metadata = is_array($log->metadata) ? $log->metadata : json_decode($log->metadata, true);
        
        return sprintf(
            'created job <strong>%s</strong> for <strong>%s</strong>',
            esc_html($metadata['job_number'] ?? 'Unknown'),
            esc_html($metadata['client_name'] ?? 'Unknown Client')
        );
    }
    
    /**
     * Format job updated activity
     */
    private static function format_job_updated($log) {
        $field_labels = array(
            'client_name' => 'client name',
            'start_date' => 'start date',
            'due_date' => 'due date',
            'overall_status' => 'overall status',
            'priority' => 'priority',
            'description' => 'description'
        );
        
        $field = $log->field_name;
        $field_label = isset($field_labels[$field]) ? $field_labels[$field] : $field;
        
        $description = sprintf(
            'updated %s',
            esc_html($field_label)
        );
        
        if (!empty($log->old_value) && !empty($log->new_value)) {
            $description .= sprintf(
                ' from <strong>%s</strong> to <strong>%s</strong>',
                esc_html($log->old_value),
                esc_html($log->new_value)
            );
        } elseif (!empty($log->new_value)) {
            $description .= sprintf(
                ' to <strong>%s</strong>',
                esc_html($log->new_value)
            );
        }
        
        return $description;
    }
    
    /**
     * Format stream assigned activity
     */
    private static function format_stream_assigned_to_job($log) {
        $metadata = is_array($log->metadata) ? $log->metadata : json_decode($log->metadata, true);
        
        return sprintf(
            'assigned <strong>%s</strong> stream to job',
            esc_html($metadata['stream_name'] ?? 'Unknown')
        );
    }
    
    /**
     * Format stream removed activity
     */
    private static function format_stream_removed_from_job($log) {
        $metadata = is_array($log->metadata) ? $log->metadata : json_decode($log->metadata, true);
        
        return sprintf(
            'removed <strong>%s</strong> stream from job',
            esc_html($metadata['stream_name'] ?? 'Unknown')
        );
    }
    
    /**
     * Format note added activity
     */
    private static function format_note_added($log) {
        $metadata = is_array($log->metadata) ? $log->metadata : json_decode($log->metadata, true);
        $note_type = $metadata['note_type'] ?? 'general';
        
        $description = sprintf(
            'added a %s note',
            esc_html($note_type)
        );
        
        if (!empty($log->user_notes)) {
            $truncated = wp_trim_words($log->user_notes, 20, '...');
            $description .= sprintf(': "<em>%s</em>"', esc_html($truncated));
        }
        
        return $description;
    }
    
    /**
     * Format phase created activity
     */
    private static function format_phase_created($log) {
        $metadata = is_array($log->metadata) ? $log->metadata : json_decode($log->metadata, true);
        
        return sprintf(
            'created phase <strong>%s</strong> in <strong>%s</strong> stream',
            esc_html($metadata['phase_name'] ?? 'Unknown'),
            esc_html($metadata['stream_name'] ?? 'Unknown')
        );
    }
    
    /**
     * Format phase updated activity
     */
    private static function format_phase_updated($log) {
        $metadata = is_array($log->metadata) ? $log->metadata : json_decode($log->metadata, true);
        
        $description = sprintf(
            'updated phase <strong>%s</strong>',
            esc_html($metadata['phase_name'] ?? 'Unknown')
        );
        
        if (!empty($log->field_name)) {
            $description .= sprintf(' (%s)', esc_html($log->field_name));
        }
        
        return $description;
    }
    
    /**
     * Format phase deleted activity
     */
    private static function format_phase_deleted($log) {
        $metadata = is_array($log->metadata) ? $log->metadata : json_decode($log->metadata, true);
        
        return sprintf(
            'deleted phase <strong>%s</strong> from <strong>%s</strong> stream',
            esc_html($metadata['phase_name'] ?? 'Unknown'),
            esc_html($metadata['stream_name'] ?? 'Unknown')
        );
    }
    
    /**
     * Default formatter for unknown activity types
     */
    private static function format_default($log) {
        $activity = str_replace('_', ' ', strtolower($log->activity_type));
        
        if (!empty($log->user_notes)) {
            return sprintf(
                '%s: "%s"',
                esc_html($activity),
                esc_html(wp_trim_words($log->user_notes, 20, '...'))
            );
        }
        
        return esc_html($activity);
    }
    
    /**
     * Format the user display for an activity
     * 
     * @param object $log_entry
     * @return string HTML formatted user display
     */
    public static function format_user_display($log_entry) {
        $metadata = is_array($log_entry->metadata) ? $log_entry->metadata : json_decode($log_entry->metadata, true);
        
        if (!empty($metadata['user_info']['display_name'])) {
            $display_name = $metadata['user_info']['display_name'];
        } else {
            $user = get_user_by('id', $log_entry->user_id);
            $display_name = $user ? $user->display_name : 'Unknown User';
        }
        
        return sprintf(
            '<strong>%s</strong>',
            esc_html($display_name)
        );
    }
    
    /**
     * Format the timestamp for display
     * 
     * @param string $timestamp
     * @return string Formatted timestamp
     */
    public static function format_timestamp($timestamp) {
        $time = strtotime($timestamp);
        $now = current_time('timestamp');
        $diff = $now - $time;
        
        // Less than 1 minute
        if ($diff < 60) {
            return 'just now';
        }
        
        // Less than 1 hour
        if ($diff < 3600) {
            $minutes = floor($diff / 60);
            return sprintf(
                '%d minute%s ago',
                $minutes,
                $minutes > 1 ? 's' : ''
            );
        }
        
        // Less than 24 hours
        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return sprintf(
                '%d hour%s ago',
                $hours,
                $hours > 1 ? 's' : ''
            );
        }
        
        // Less than 7 days
        if ($diff < 604800) {
            $days = floor($diff / 86400);
            return sprintf(
                '%d day%s ago',
                $days,
                $days > 1 ? 's' : ''
            );
        }
        
        // Default to date format
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $time);
    }
    
    /**
     * Format a complete activity line for display
     * 
     * @param object $log_entry
     * @return string HTML formatted activity line
     */
    public static function format_activity_line($log_entry) {
        $icon = OO_Master_Log::get_activity_icon($log_entry->activity_type);
        $user = self::format_user_display($log_entry);
        $activity = self::format_activity($log_entry);
        $time = self::format_timestamp($log_entry->created_at);
        
        return sprintf(
            '<div class="activity-line">
                <span class="activity-icon">%s</span>
                <span class="activity-content">%s %s</span>
                <span class="activity-time">%s</span>
            </div>',
            $icon,
            $user,
            $activity,
            $time
        );
    }
} 