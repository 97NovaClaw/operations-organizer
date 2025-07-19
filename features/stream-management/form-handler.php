<?php
// /features/stream-management/form-handler.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Stream_Management_Form_Handler {
    
    /**
     * Create a new stream and its corresponding data table
     * 
     * @param string $stream_name The name of the stream
     * @param string $stream_description The description of the stream
     * @return int|WP_Error The stream ID on success, WP_Error on failure
     */
    public static function create_new_stream($stream_name, $stream_description = '') {
        global $wpdb;
        
        // Validate input
        if (empty($stream_name)) {
            return new WP_Error('missing_name', __('Stream name is required.', 'operations-organizer'));
        }
        
        // Generate a unique slug for the stream
        $stream_slug = self::generate_stream_slug($stream_name);
        
        if (is_wp_error($stream_slug)) {
            return $stream_slug;
        }
        
        // Check if stream name already exists
        $existing_stream = OO_DB::get_stream_by_name($stream_name);
        if ($existing_stream) {
            return new WP_Error('duplicate_name', __('A stream with this name already exists.', 'operations-organizer'));
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Create the stream record
            $stream_id = OO_DB::add_stream($stream_name, $stream_slug, $stream_description);
            
            if (is_wp_error($stream_id)) {
                throw new Exception($stream_id->get_error_message());
            }
            
            // Create the corresponding data table
            $table_created = self::create_stream_data_table($stream_slug);
            
            if (is_wp_error($table_created)) {
                throw new Exception($table_created->get_error_message());
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            oo_log('Successfully created stream: ' . $stream_name . ' (ID: ' . $stream_id . ') with table: ' . $wpdb->prefix . 'oo_stream_data_' . $stream_slug, __METHOD__);
            
            return $stream_id;
            
        } catch (Exception $e) {
            // Rollback transaction
            $wpdb->query('ROLLBACK');
            oo_log('Failed to create stream: ' . $e->getMessage(), __METHOD__);
            return new WP_Error('creation_failed', $e->getMessage());
        }
    }
    
    /**
     * Generate a unique slug for a stream
     * 
     * @param string $stream_name The stream name
     * @return string|WP_Error The slug on success, WP_Error on failure
     */
    private static function generate_stream_slug($stream_name) {
        global $wpdb;
        
        // Create base slug using sanitize_key directly (converts spaces to dashes)
        $base_slug = sanitize_key($stream_name);
        
        if (empty($base_slug)) {
            return new WP_Error('invalid_slug', __('Cannot generate valid slug from stream name.', 'operations-organizer'));
        }
        
        // Check if slug already exists
        $slug = $base_slug;
        $counter = 1;
        
        while (self::slug_exists($slug)) {
            $slug = $base_slug . '_' . $counter;
            $counter++;
            
            // Prevent infinite loop
            if ($counter > 100) {
                return new WP_Error('slug_generation_failed', __('Could not generate unique slug.', 'operations-organizer'));
            }
        }
        
        return $slug;
    }
    
    /**
     * Check if a slug already exists in the streams table
     * 
     * @param string $slug The slug to check
     * @return bool True if exists, false otherwise
     */
    private static function slug_exists($slug) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'oo_streams';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE stream_slug = %s",
            $slug
        ));
        
        return $count > 0;
    }
    
    /**
     * Create a data table for a new stream
     * 
     * @param string $stream_slug The stream slug
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private static function create_stream_data_table($stream_slug) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'oo_stream_data_' . $stream_slug;
        
        // Define the table schema based on existing stream data tables
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            stream_data_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Common fields that all streams might have
            notes text,
            status varchar(50) DEFAULT 'active',
            
            -- Flexible JSON field for stream-specific data
            stream_specific_data longtext,
            
            PRIMARY KEY (stream_data_id),
            KEY job_id (job_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $result = dbDelta($sql);
        
        // Check if table was created successfully
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            return new WP_Error('table_creation_failed', __('Failed to create stream data table.', 'operations-organizer'));
        }
        
        oo_log('Successfully created table: ' . $table_name, __METHOD__);
        
        return true;
    }
    
    /**
     * Get the template schema for stream data tables
     * This can be customized based on your needs
     * 
     * @return array Array of field definitions
     */
    public static function get_stream_data_table_schema() {
        return array(
            'stream_data_id' => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT',
            'job_id' => 'bigint(20) unsigned NOT NULL',
            'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'notes' => 'text',
            'status' => "varchar(50) DEFAULT 'active'",
            'stream_specific_data' => 'longtext' // JSON field for flexibility
        );
    }
} 