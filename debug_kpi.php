<?php
// Debug script to check KPI data
define('WP_USE_THEMES', false);
require_once('../../../../../../wp-config.php');

global $wpdb;

echo "=== KPI DEBUG SCRIPT ===\n\n";

// Check if KPI measures table exists and has data
$kpi_measures = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}oo_kpi_measures LIMIT 10");
echo "KPI Measures in database: " . count($kpi_measures) . "\n";
if ($kpi_measures) {
    foreach ($kpi_measures as $measure) {
        echo "- ID: {$measure->kpi_measure_id}, Name: {$measure->measure_name}, Key: {$measure->measure_key}, Active: {$measure->is_active}\n";
    }
} else {
    echo "No KPI measures found!\n";
}

echo "\n";

// Check if phase KPI links table exists and has data  
$phase_links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}oo_phase_kpi_measures_link LIMIT 10");
echo "Phase KPI Links in database: " . count($phase_links) . "\n";
if ($phase_links) {
    foreach ($phase_links as $link) {
        echo "- Link ID: {$link->link_id}, Phase ID: {$link->phase_id}, KPI Measure ID: {$link->kpi_measure_id}, Mandatory: {$link->is_mandatory}\n";
    }
} else {
    echo "No phase KPI links found!\n";
}

echo "\n";

// Check specifically for phase ID 1 links with JOIN
$phase_1_links = $wpdb->get_results("
    SELECT pkm.*, km.measure_name, km.measure_key, km.unit_type 
    FROM {$wpdb->prefix}oo_phase_kpi_measures_link pkm 
    LEFT JOIN {$wpdb->prefix}oo_kpi_measures km ON pkm.kpi_measure_id = km.kpi_measure_id 
    WHERE pkm.phase_id = 1 
    ORDER BY pkm.display_order ASC
");
echo "Phase 1 KPI Links (with measure details): " . count($phase_1_links) . "\n";
if ($phase_1_links) {
    foreach ($phase_1_links as $link) {
        echo "- Phase 1 Link: {$link->link_id}, KPI ID: {$link->kpi_measure_id}, Name: {$link->measure_name}, Key: {$link->measure_key}, Mandatory: {$link->is_mandatory}\n";
    }
} else {
    echo "No phase 1 KPI links found!\n";
}

echo "\n";

// Check what tables exist
$tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}oo_%'");
echo "Operations Organizer tables:\n";
foreach ($tables as $table) {
    $table_name = current((array)$table);
    echo "- {$table_name}\n";
}
?> 