<?php
/**
 * Fix script to update stream slugs to match expected format
 */

// Load WordPress
require_once('wp-load.php');

// Check if user has admin privileges
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

echo "<h1>Fix Stream Slugs</h1>";
echo "<pre>";

global $wpdb;
$streams_table = $wpdb->prefix . 'oo_streams';

// Get all streams
$streams = $wpdb->get_results("SELECT * FROM {$streams_table} ORDER BY stream_id");

echo "=== CURRENT STREAM SLUGS IN DATABASE ===\n\n";
foreach ($streams as $stream) {
    echo "ID: {$stream->stream_id} | Name: {$stream->stream_name} | Current Slug: {$stream->stream_slug}\n";
}

echo "\n=== FIXING STREAM SLUGS ===\n\n";

$fixed_count = 0;
foreach ($streams as $stream) {
    // Generate the correct slug using sanitize_key directly
    // This matches what the system expects
    $correct_slug = sanitize_key($stream->stream_name);
    
    echo "Stream: {$stream->stream_name}\n";
    echo "Current slug: {$stream->stream_slug}\n";
    echo "Expected slug: {$correct_slug}\n";
    
    if ($stream->stream_slug !== $correct_slug) {
        // Check if the correct slug already exists on another stream
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT stream_id FROM {$streams_table} WHERE stream_slug = %s AND stream_id != %d",
            $correct_slug,
            $stream->stream_id
        ));
        
        if ($existing) {
            echo "ERROR: Slug '{$correct_slug}' already exists on stream ID {$existing}\n";
            echo "Skipping...\n";
        } else {
            // Update the slug
            $result = $wpdb->update(
                $streams_table,
                array('stream_slug' => $correct_slug),
                array('stream_id' => $stream->stream_id),
                array('%s'),
                array('%d')
            );
            
            if ($result !== false) {
                echo "✓ FIXED!\n";
                $fixed_count++;
            } else {
                echo "✗ ERROR: " . $wpdb->last_error . "\n";
            }
        }
    } else {
        echo "✓ Already correct\n";
    }
    echo "---\n";
}

echo "\n=== SUMMARY ===\n";
echo "Fixed {$fixed_count} stream slugs\n";

// Verify all slugs are now correct
echo "\n=== VERIFICATION ===\n";
$streams = $wpdb->get_results("SELECT * FROM {$streams_table} ORDER BY stream_id");
$all_correct = true;
foreach ($streams as $stream) {
    $expected = sanitize_key($stream->stream_name);
    if ($stream->stream_slug !== $expected) {
        echo "✗ Stream '{$stream->stream_name}' still has incorrect slug: {$stream->stream_slug} (expected: {$expected})\n";
        $all_correct = false;
    }
}

if ($all_correct) {
    echo "✓ All stream slugs are now correct!\n";
}

echo "</pre>"; 