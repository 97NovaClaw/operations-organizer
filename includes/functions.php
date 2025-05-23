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
    if (!(defined('WP_DEBUG') && WP_DEBUG === true)) {
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
 * Get hardcoded streams used in the application
 * 
 * @return array Array of stream objects with id, name, and description properties
 */
function oo_get_hardcoded_streams() {
    return array(
        (object) array(
            'stream_id' => 1,
            'stream_name' => 'Soft Content',
            'stream_description' => 'Soft Content stream for textiles and similar items',
            'is_active' => 1
        ),
        (object) array(
            'stream_id' => 2,
            'stream_name' => 'Electronics',
            'stream_description' => 'Electronics stream for electronic devices and components',
            'is_active' => 1
        ),
        (object) array(
            'stream_id' => 3,
            'stream_name' => 'Art',
            'stream_description' => 'Art stream for artwork and creative items',
            'is_active' => 1
        ),
        (object) array(
            'stream_id' => 4,
            'stream_name' => 'Content',
            'stream_description' => 'Content stream for general content items',
            'is_active' => 1
        )
    );
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
    
    switch (intval($stream_id)) {
        case 1: // Soft Content
            return OO_DB::get_stream_data_soft_content_by_job($job_id);
        case 2: // Electronics
            return OO_DB::get_stream_data_electronics_by_job($job_id);
        case 3: // Art
            return OO_DB::get_stream_data_art_by_job($job_id);
        case 4: // Content
            return OO_DB::get_stream_data_content_by_job($job_id);
        default:
            return null;
    }
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
    
    $args = array_merge(array('job_id' => $job_id), $data);
    
    switch (intval($stream_id)) {
        case 1: // Soft Content
            return OO_DB::add_stream_data_soft_content($args);
        case 2: // Electronics
            return OO_DB::add_stream_data_electronics($args);
        case 3: // Art
            return OO_DB::add_stream_data_art($args);
        case 4: // Content
            return OO_DB::add_stream_data_content($args);
        default:
            return new WP_Error('invalid_stream', 'Invalid stream ID.');
    }
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
    
    switch (intval($stream_id)) {
        case 1: // Soft Content
            return OO_DB::update_stream_data_soft_content($data_id, $data);
        case 2: // Electronics
            return OO_DB::update_stream_data_electronics($data_id, $data);
        case 3: // Art
            return OO_DB::update_stream_data_art($data_id, $data);
        case 4: // Content
            return OO_DB::update_stream_data_content($data_id, $data);
        default:
            return new WP_Error('invalid_stream', 'Invalid stream ID.');
    }
}

/**
 * Get the name of the stream from its ID
 * 
 * @param int $stream_id The stream ID
 * @return string The stream name
 */
function oo_get_stream_name($stream_id) {
    $streams = oo_get_hardcoded_streams();
    foreach ($streams as $stream) {
        if ($stream->stream_id == $stream_id) {
            return $stream->stream_name;
        }
    }
    return 'Unknown Stream';
}

/**
 * Get the database table name for a specific stream
 * 
 * @param int $stream_id The stream ID
 * @return string The table name or empty string if invalid
 */
function oo_get_stream_table_name($stream_id) {
    global $wpdb;
    switch (intval($stream_id)) {
        case 1: // Soft Content
            return $wpdb->prefix . 'oo_stream_data_soft_content';
        case 2: // Electronics
            return $wpdb->prefix . 'oo_stream_data_electronics';
        case 3: // Art
            return $wpdb->prefix . 'oo_stream_data_art';
        case 4: // Content
            return $wpdb->prefix . 'oo_stream_data_content';
        default:
            return '';
    }
} 