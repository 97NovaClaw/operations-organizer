<?php
/*
Feature Tree: Stream Management - Slug Regeneration

stream-management/
├── index.php              # Entry point
├── ajax.php               # AJAX endpoints
├── form-handler.php       # Form submission handling
├── slug-regeneration.php  # Slug regeneration utilities
└── views/
    └── management-page.php # UI templates
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Stream Slug Regeneration Utilities
 * 
 * Handles slug generation and regeneration for streams
 * ensuring consistency across create and update operations
 */
class OO_Stream_Slug_Regeneration {
    
    /**
     * Regenerate slug for a stream
     * 
     * @param int $stream_id The stream ID
     * @param string $stream_name The stream name to generate slug from
     * @return string|WP_Error The generated slug or error
     */
    public static function regenerate_slug_for_stream($stream_id, $stream_name) {
        oo_log('[SLUG_REGEN] Starting slug regeneration for stream ID: ' . $stream_id . ' with name: ' . $stream_name, __METHOD__);
        
        global $wpdb;
        $streams_table = $wpdb->prefix . 'oo_streams';
        
        // Generate base slug using sanitize_key (consistent with form handler)
        $base_slug = sanitize_key($stream_name);
        
        if (empty($base_slug)) {
            oo_log('[SLUG_REGEN] ERROR: Cannot generate valid slug from stream name: ' . $stream_name, __METHOD__);
            return new WP_Error('invalid_slug', __('Cannot generate valid slug from stream name.', 'operations-organizer'));
        }
        
        // Check if slug needs to be unique
        $slug = $base_slug;
        $counter = 1;
        
        while (true) {
            // Check if this slug exists on a different stream
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT stream_id FROM {$streams_table} WHERE stream_slug = %s AND stream_id != %d",
                $slug,
                $stream_id
            ));
            
            if (!$existing) {
                // Slug is unique
                break;
            }
            
            // Try next variation
            $slug = $base_slug . '_' . $counter;
            $counter++;
            
            // Prevent infinite loop
            if ($counter > 100) {
                oo_log('[SLUG_REGEN] ERROR: Could not find unique slug after 100 attempts', __METHOD__);
                return new WP_Error('slug_generation_failed', __('Could not generate unique slug.', 'operations-organizer'));
            }
        }
        
        oo_log('[SLUG_REGEN] Generated unique slug: ' . $slug, __METHOD__);
        return $slug;
    }
    
    /**
     * Fix all streams with missing or empty slugs
     * 
     * @return array Results of the fix operation
     */
    public static function fix_missing_slugs() {
        oo_log('[SLUG_REGEN] Starting fix for missing stream slugs', __METHOD__);
        
        global $wpdb;
        $streams_table = $wpdb->prefix . 'oo_streams';
        
        // Find all streams with empty or null slugs
        $streams_needing_slugs = $wpdb->get_results(
            "SELECT stream_id, stream_name FROM {$streams_table} 
             WHERE stream_slug IS NULL OR stream_slug = ''"
        );
        
        $fixed_count = 0;
        $errors = array();
        
        foreach ($streams_needing_slugs as $stream) {
            oo_log('[SLUG_REGEN] Processing stream ID: ' . $stream->stream_id . ' (' . $stream->stream_name . ')', __METHOD__);
            
            $new_slug = self::regenerate_slug_for_stream($stream->stream_id, $stream->stream_name);
            
            if (is_wp_error($new_slug)) {
                $error_msg = 'Failed to generate slug for stream "' . $stream->stream_name . '": ' . $new_slug->get_error_message();
                oo_log('[SLUG_REGEN] ERROR: ' . $error_msg, __METHOD__);
                $errors[] = $error_msg;
                continue;
            }
            
            // Update the stream with the new slug
            $result = $wpdb->update(
                $streams_table,
                array('stream_slug' => $new_slug),
                array('stream_id' => $stream->stream_id),
                array('%s'),
                array('%d')
            );
            
            if ($result === false) {
                $error_msg = 'Failed to update slug for stream "' . $stream->stream_name . '": ' . $wpdb->last_error;
                oo_log('[SLUG_REGEN] ERROR: ' . $error_msg, __METHOD__);
                $errors[] = $error_msg;
            } else {
                oo_log('[SLUG_REGEN] Successfully updated stream ID ' . $stream->stream_id . ' with slug: ' . $new_slug, __METHOD__);
                $fixed_count++;
            }
        }
        
        $result = array(
            'total_found' => count($streams_needing_slugs),
            'fixed' => $fixed_count,
            'errors' => $errors
        );
        
        oo_log('[SLUG_REGEN] Fix complete. Results: ' . json_encode($result), __METHOD__);
        
        return $result;
    }
    
    /**
     * Check if a stream needs slug regeneration
     * 
     * @param int $stream_id The stream ID
     * @return bool True if slug needs regeneration
     */
    public static function needs_slug_regeneration($stream_id) {
        global $wpdb;
        $streams_table = $wpdb->prefix . 'oo_streams';
        
        $stream = $wpdb->get_row($wpdb->prepare(
            "SELECT stream_name, stream_slug FROM {$streams_table} WHERE stream_id = %d",
            $stream_id
        ));
        
        if (!$stream) {
            return false;
        }
        
        // Check if slug is empty
        if (empty($stream->stream_slug)) {
            return true;
        }
        
        // Check if slug matches what would be generated from current name
        $expected_slug = sanitize_key($stream->stream_name);
        
        // If the base doesn't match, it needs regeneration
        // (Allow for numbered variations like slug_1, slug_2 etc)
        if (strpos($stream->stream_slug, $expected_slug) !== 0) {
            return true;
        }
        
        return false;
    }
} 