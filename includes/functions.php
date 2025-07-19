<?php
// /includes/functions.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Helper functions for the plugin can go here.

/**
 * Get a list of active employees for use in dropdowns.
 *
 * @return array Array of employee data (id, name).
 */
function oo_get_active_employees_for_select() {
    $employees = OO_DB::get_employees( array( 'is_active' => 1, 'orderby' => 'last_name', 'order' => 'ASC', 'number' => -1 ) );
    $options = array();
    if ( $employees ) {
        foreach ( $employees as $employee ) {
            $options[] = array(
                'id' => $employee->employee_id,
                'name' => esc_html( $employee->first_name . ' ' . $employee->last_name . ' (' . $employee->employee_number . ')' )
            );
        }
    }
    return $options;
}

/**
 * Get a list of active phases for a specific stream type (or all active phases if stream_type_id is null).
 *
 * @param int|null $stream_type_id Optional. The ID of the stream type.
 * @return array Array of phase data (id, name).
 */
function oo_get_active_phases_for_select($stream_type_id = null) {
    $args = array( 'is_active' => 1, 'orderby' => 'sort_order', 'order' => 'ASC', 'number' => -1 );
    if ( !is_null($stream_type_id) && $stream_type_id > 0 ) {
        $args['stream_type_id'] = intval($stream_type_id);
    }
    $phases = OO_DB::get_phases( $args );
    $options = array();
    if ( $phases ) {
        foreach ( $phases as $phase ) {
            $options[] = array(
                'id' => $phase->phase_id,
                'name' => esc_html( $phase->phase_name )
            );
        }
    }
    return $options;
}

/**
 * Get all active stream types for select dropdown.
 *
 * @return array Array of stream type data (id, name, slug).
 */
function oo_get_active_stream_types_for_select() {
    $stream_types = OO_DB::get_stream_types(array('is_active' => 1, 'orderby' => 'stream_type_name'));
    $options = array();
    if ($stream_types) {
        foreach ($stream_types as $st) {
            $options[] = array(
                'id' => $st->stream_type_id,
                'name' => esc_html($st->stream_type_name),
                'slug' => esc_html($st->stream_type_slug)
            );
        }
    }
    return $options;
}

/**
 * Get current timestamp for display, respecting WordPress timezone settings.
 * @return string Formatted date and time.
 */
function oo_get_current_timestamp_display() {
    return wp_date(get_option('date_format') . ' ' . get_option('time_format'), current_time('timestamp'), wp_timezone());
}

/**
 * Get the capability required to manage plugin settings and view full dashboards.
 * Filters `oo_manage_capability` can be used to change this.
 * @return string The capability string.
 */
function oo_get_capability() {
    return apply_filters('oo_manage_capability', 'manage_options');
}

/**
 * Get the capability required to access the start/stop job forms (e.g., via QR code).
 * Filters `oo_form_access_capability` can be used to change this.
 * @return string The capability string.
 */
function oo_get_form_access_capability() {
    // 'read' means any logged-in user. 
    // Consider creating a custom role/capability for more fine-grained control.
    return apply_filters('oo_form_access_capability', 'read'); 
}

/**
 * Helper function for logging plugin debug messages.
 * Only logs if WP_DEBUG is true.
 *
 * @param mixed  $message The message or data to log.
 * @param string $context Optional context for the log entry (e.g., function name).
 */
function oo_log($message, $context = '') {
    $wp_debug_enabled = (defined('WP_DEBUG') && WP_DEBUG === true);
    $option_enabled = (get_option('oo_enable_debugging') === 'yes');

    if ( ! $wp_debug_enabled && ! $option_enabled ) {
        return;
    }

    $timestamp = wp_date('Y-m-d H:i:s e');
    $log_entry_prefix = '[' . $timestamp . '] [OO_DEBUG';

    if (!empty($context)) {
        $log_entry_prefix .= ' - ' . (is_scalar($context) ? $context : print_r($context, true));
    }
    $log_entry_prefix .= ']: ';

    $message_str = '';
    if (is_wp_error($message)) {
        $message_str = 'WP_Error: ' . $message->get_error_code() . ' - ' . $message->get_error_message();
        $error_data = $message->get_error_data();
        if (!empty($error_data)) {
            $message_str .= "\nError Data: " . print_r($error_data, true);
        }
    } elseif (is_array($message) || is_object($message)) {
        // Attempt to JSON encode, fallback to print_r for complex objects/recursion
        $encoded = json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false && json_last_error() !== JSON_ERROR_NONE) {
            $message_str = print_r($message, true); // Fallback if json_encode fails
        } else {
            $message_str = $encoded;
        }
    } elseif (is_resource($message)) {
        $message_str = '[RESOURCE of type: ' . get_resource_type($message) . ']';
    } else {
        $message_str = (string) $message;
    }

    $log_entry = $log_entry_prefix . $message_str . "\n";

    $log_dir = OO_PLUGIN_DIR . 'debug';
    $log_file = $log_dir . '/debug.log';

    if (!file_exists($log_dir)) {
        @mkdir($log_dir, 0755);
        if (file_exists($log_dir)) {
            $htaccess_content = "# Apache deny access to this directory\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>";
            @file_put_contents($log_dir . '/.htaccess', $htaccess_content);
            @file_put_contents($log_dir . '/.gitkeep', '');
        }
    }

    if (is_dir($log_dir) && is_writable($log_dir)) {
        if (@file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX) !== false) {
            return;
        }
    }
    // Fallback if custom log fails
    error_log(trim($log_entry_prefix . $message_str)); 
}

if ( ! function_exists( 'oo_sanitize_date' ) ) {
    /**
     * Sanitize a date string and format it to YYYY-MM-DD.
     *
     * @param string|null $date_string The date string to sanitize.
     * @return string|null The sanitized date in YYYY-MM-DD format, or null if input is empty or invalid.
     */
    function oo_sanitize_date( $date_string ) {
        if ( empty( $date_string ) ) {
            return null;
}
        try {
            $date = new DateTime( $date_string );
            return $date->format( 'Y-m-d' );
        } catch ( Exception $e ) {
            // Log invalid date format if needed
            oo_log( "Invalid date format provided: " . $date_string . " - Error: " . $e->getMessage(), __FUNCTION__ );
            return null; // Or handle error as appropriate
        }
    }
}

/**
 * Get streams from database (replaces hardcoded streams)
 * 
 * @param array $args Optional arguments for filtering streams
 * @return array Array of stream objects from database
 */
function oo_get_streams($args = array()) {
    // Default arguments
    $defaults = array(
        'is_active' => 1, // Only active streams by default
        'orderby' => 'stream_name',
        'order' => 'ASC',
        'number' => -1 // Get all streams
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Get streams from database
    $streams = OO_DB::get_streams($args);
    
    return $streams ? $streams : array();
}

/**
 * Get hardcoded streams used in the application (DEPRECATED)
 * 
 * @deprecated Use oo_get_streams() instead
 * @return array Array of stream objects with id, name, and description properties
 */
function oo_get_hardcoded_streams() {
    // For backward compatibility during transition, return database streams
    // but maintain the same format as the old hardcoded version
    return oo_get_streams();
}

/**
 * Get stream-specific data for a job based on its stream type
 * 
 * @param int $job_id The job ID
 * @param int $stream_id The stream ID
 * @return object|null The stream-specific data or null if not found
 */
function oo_get_stream_data_for_job($job_id, $stream_id) {
    if (empty($job_id) || empty($stream_id)) {
        return null;
    }
    
    // Use dynamic table name lookup
    $table_name = oo_get_stream_table_name($stream_id);
    
    if (empty($table_name)) {
            return null;
    }
    
    // Use generic database method for any stream
    return OO_DB::get_stream_data_by_job($job_id, $stream_id, $table_name);
}

/**
 * Create stream-specific data for a job based on its stream type
 * 
 * @param int $job_id The job ID
 * @param int $stream_id The stream ID
 * @param array $data The data to create
 * @return int|WP_Error The inserted data ID or a WP_Error object
 */
function oo_create_stream_data_for_job($job_id, $stream_id, $data = array()) {
    if (empty($job_id) || empty($stream_id)) {
        return new WP_Error('missing_fields', 'Job ID and Stream ID are required.');
    }
    
    // Use dynamic table name lookup
    $table_name = oo_get_stream_table_name($stream_id);
    
    if (empty($table_name)) {
        return new WP_Error('invalid_stream', 'Invalid stream ID.');
    }
    
    $args = array_merge(array('job_id' => $job_id), $data);
    
    // Use generic database method for any stream
    return OO_DB::add_stream_data($args, $stream_id, $table_name);
}

/**
 * Update stream-specific data for a job based on its stream type
 * 
 * @param int $data_id The data ID
 * @param int $stream_id The stream ID
 * @param array $data The data to update
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function oo_update_stream_data($data_id, $stream_id, $data) {
    if (empty($data_id) || empty($stream_id) || empty($data)) {
        return new WP_Error('missing_fields', 'Data ID, Stream ID, and data are required.');
    }
    
    // Use dynamic table name lookup
    $table_name = oo_get_stream_table_name($stream_id);
    
    if (empty($table_name)) {
            return new WP_Error('invalid_stream', 'Invalid stream ID.');
    }
    
    // Use generic database method for any stream
    return OO_DB::update_stream_data($data_id, $data, $stream_id, $table_name);
}

/**
 * Get the name of the stream from its ID
 * 
 * @param int $stream_id The stream ID
 * @return string The stream name
 */
function oo_get_stream_name($stream_id) {
    if (empty($stream_id)) {
        return 'Unknown Stream';
    }
    
    // Get stream directly from database
    $stream = OO_DB::get_stream($stream_id);
    
    if ($stream && !empty($stream->stream_name)) {
            return $stream->stream_name;
    }
    
    return 'Unknown Stream';
}

/**
 * Get the database table name for a specific stream (dynamic version)
 * 
 * @param int $stream_id The stream ID
 * @return string The table name or empty string if invalid
 */
function oo_get_stream_table_name($stream_id) {
    global $wpdb;
    
    if (empty($stream_id)) {
        return '';
    }
    
    // Get stream from database to find its slug
    $stream = OO_DB::get_stream($stream_id);
    
    if (!$stream || empty($stream->stream_slug)) {
            return '';
    }
    
    return $wpdb->prefix . 'oo_stream_data_' . $stream->stream_slug;
}

/**
 * Get stream by slug
 */
function oo_get_stream_by_slug($slug) {
    return OO_DB::get_stream_by_slug($slug);
}

// --- Feature Sets Functions ---

/**
 * Get all feature sets
 */
function oo_get_feature_sets($args = array()) {
    return OO_DB::get_feature_sets($args);
}

/**
 * Get feature set by ID
 */
function oo_get_feature_set($feature_set_id) {
    return OO_DB::get_feature_set($feature_set_id);
}

/**
 * Get feature set by slug
 */
function oo_get_feature_set_by_slug($slug) {
    return OO_DB::get_feature_set_by_slug($slug);
}

/**
 * Get feature sets for a stream
 */
function oo_get_feature_sets_for_stream($stream_id, $is_active = 1) {
    oo_log('[FEATURE_SET_DEBUG] oo_get_feature_sets_for_stream called with stream_id: ' . $stream_id . ', is_active: ' . ($is_active === null ? 'NULL' : $is_active));
    
    $result = OO_DB::get_feature_sets_for_stream($stream_id, $is_active);
    
    oo_log('[FEATURE_SET_DEBUG] oo_get_feature_sets_for_stream: Database returned ' . count($result) . ' feature sets');
    
    return $result;
}

/**
 * Render feature set content for a stream
 * This function provides a hook-based system for feature sets to render their content
 */
function oo_render_feature_set_content($stream_slug, $feature_set_slug) {
    // Get the stream and feature set
    $stream = oo_get_stream_by_slug($stream_slug);
    $feature_set = oo_get_feature_set_by_slug($feature_set_slug);
    
    if (!$stream || !$feature_set) {
        return '<div class="notice notice-error"><p>' . __('Stream or feature set not found.', 'operations-organizer') . '</p></div>';
    }
    
    // Check if the feature set is assigned to this stream
    $assigned_feature_sets = oo_get_feature_sets_for_stream($stream->stream_id, 1);
    $is_assigned = false;
    foreach ($assigned_feature_sets as $assigned_fs) {
        if ($assigned_fs->feature_set_id == $feature_set->feature_set_id) {
            $is_assigned = true;
            break;
        }
    }
    
    if (!$is_assigned) {
        return '<div class="notice notice-warning"><p>' . __('This feature set is not assigned to this stream.', 'operations-organizer') . '</p></div>';
    }
    
    // Start output buffering
    ob_start();
    
    // Fire the hook for this specific feature set
    do_action('oo_render_feature_set_' . $feature_set_slug, $stream, $feature_set);
    
    // If no specific hook handler, fire the generic hook
    if (!has_action('oo_render_feature_set_' . $feature_set_slug)) {
        do_action('oo_render_feature_set_generic', $stream, $feature_set);
    }
    
    // Get the output
    $content = ob_get_clean();
    
    // If still no content, show a default message
    if (empty(trim($content))) {
        $content = '<div class="notice notice-info"><p>' . 
                   sprintf(__('Feature set "%s" is assigned but no content renderer is available.', 'operations-organizer'), 
                          esc_html($feature_set->name)) . 
                   '</p></div>';
    }
    
    return $content;
}

/**
 * Get feature set sub-tabs for a stream
 * This generates the sub-tab structure for the dashboard
 */
function oo_get_feature_set_sub_tabs($stream_slug) {
    oo_log('[FEATURE_SET_DEBUG] oo_get_feature_set_sub_tabs called with stream_slug: ' . $stream_slug);
    
    // Add dedicated debug logging
    if (function_exists('oo_stream_debug_log')) {
        oo_stream_debug_log('oo_get_feature_set_sub_tabs called', array(
            'stream_slug' => $stream_slug,
            'function' => 'oo_get_feature_set_sub_tabs'
        ));
    }
    
    $stream = oo_get_stream_by_slug($stream_slug);
    if (!$stream) {
        oo_log('[FEATURE_SET_DEBUG] oo_get_feature_set_sub_tabs: Stream not found for slug: ' . $stream_slug);
        
        // Log the failure
        if (function_exists('oo_stream_debug_log')) {
            oo_stream_debug_log('STREAM NOT FOUND BY SLUG', array(
                'requested_slug' => $stream_slug,
                'error' => 'No stream found with this slug'
            ), 'ERROR');
        }
        
        return array();
    }
    
    oo_log('[FEATURE_SET_DEBUG] oo_get_feature_set_sub_tabs: Stream found - ID: ' . $stream->stream_id . ', Name: ' . $stream->stream_name);
    
    $feature_sets = oo_get_feature_sets_for_stream($stream->stream_id, 1);
    oo_log('[FEATURE_SET_DEBUG] oo_get_feature_set_sub_tabs: Feature sets from database: ', $feature_sets);
    
    $sub_tabs = array();
    
    foreach ($feature_sets as $feature_set) {
        oo_log('[FEATURE_SET_DEBUG] oo_get_feature_set_sub_tabs: Processing feature set: ' . $feature_set->name . ' (slug: ' . $feature_set->slug . ')');
        $sub_tabs[] = array(
            'id' => $feature_set->slug,
            'name' => $feature_set->name,
            'slug' => $feature_set->slug,
            'description' => $feature_set->description,
            'sort_order' => $feature_set->sort_order
        );
    }
    
    // Sort by sort_order
    usort($sub_tabs, function($a, $b) {
        return $a['sort_order'] - $b['sort_order'];
    });
    
    oo_log('[FEATURE_SET_DEBUG] oo_get_feature_set_sub_tabs: Final sub_tabs array: ', $sub_tabs);
    
    return $sub_tabs;
}

/**
 * Register a feature set content renderer
 * This is a helper function for feature set modules to register their content renderers
 */
function oo_register_feature_set_renderer($feature_set_slug, $callback) {
    add_action('oo_render_feature_set_' . $feature_set_slug, $callback, 10, 2);
} 