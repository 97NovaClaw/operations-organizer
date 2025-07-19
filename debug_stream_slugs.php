<?php
/**
 * Debug script to check stream slugs in database
 */

// Load WordPress
require_once('wp-load.php');

// Check if user has admin privileges
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

echo "<h1>Stream Slug Debug</h1>";
echo "<pre>";

// Get all streams from database
global $wpdb;
$streams_table = $wpdb->prefix . 'oo_streams';
$streams = $wpdb->get_results("SELECT * FROM {$streams_table} ORDER BY stream_id");

echo "=== DATABASE STREAMS ===\n";
foreach ($streams as $stream) {
    echo "ID: {$stream->stream_id}\n";
    echo "Name: {$stream->stream_name}\n";
    echo "Slug (in DB): {$stream->stream_slug}\n";
    echo "Expected tab slug: " . sanitize_key($stream->stream_name) . "\n";
    echo "Match: " . (($stream->stream_slug === sanitize_key($stream->stream_name)) ? "YES ✓" : "NO ✗") . "\n";
    echo "---\n";
}

// Check what oo_get_stream_by_slug returns
echo "\n=== TESTING oo_get_stream_by_slug ===\n";
$test_slugs = ['art', 'content', 'electronics', 'soft_content', 'storage'];
foreach ($test_slugs as $slug) {
    $result = oo_get_stream_by_slug($slug);
    if ($result) {
        echo "Slug '{$slug}': FOUND (ID: {$result->stream_id}, Name: {$result->stream_name})\n";
    } else {
        echo "Slug '{$slug}': NOT FOUND\n";
    }
}

echo "</pre>"; 