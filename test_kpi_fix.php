<?php
/**
 * Test script to verify KPI filtering fix
 * This script tests the get_phase_kpi_links_for_phase method
 */

// Include WordPress functionality
require_once('../../../wp-config.php'); // Adjust path as needed
require_once('operations-organizer.php');

function test_kpi_filtering() {
    echo "Testing KPI filtering fix...\n\n";
    
    $phase_id = 1; // Test with phase ID 1 (Packout)
    
    echo "=== Test 1: Get all KPI links (including inactive) ===\n";
    $all_links = OO_DB::get_phase_kpi_links_for_phase($phase_id, array('active_only' => false));
    echo "Found " . count($all_links) . " total KPI links for phase {$phase_id}\n";
    foreach ($all_links as $link) {
        $active_status = isset($link->is_active) ? ($link->is_active ? 'Active' : 'Inactive') : 'Unknown';
        echo "- KPI: {$link->measure_name} (Key: {$link->measure_key}) - Status: {$active_status}\n";
    }
    
    echo "\n=== Test 2: Get only active KPI links (new filtering) ===\n";
    $active_links = OO_DB::get_phase_kpi_links_for_phase($phase_id, array('active_only' => true));
    echo "Found " . count($active_links) . " active KPI links for phase {$phase_id}\n";
    foreach ($active_links as $link) {
        $active_status = isset($link->is_active) ? ($link->is_active ? 'Active' : 'Inactive') : 'Unknown';
        echo "- KPI: {$link->measure_name} (Key: {$link->measure_key}) - Status: {$active_status}\n";
    }
    
    echo "\n=== Test 3: Legacy boolean parameter (true for active only) ===\n";
    $legacy_active_links = OO_DB::get_phase_kpi_links_for_phase($phase_id, true);
    echo "Found " . count($legacy_active_links) . " active KPI links for phase {$phase_id} (legacy call)\n";
    foreach ($legacy_active_links as $link) {
        $active_status = isset($link->is_active) ? ($link->is_active ? 'Active' : 'Inactive') : 'Unknown';
        echo "- KPI: {$link->measure_name} (Key: {$link->measure_key}) - Status: {$active_status}\n";
    }
    
    echo "\n=== Test 4: Check if 'Boxtest' KPI exists and its status ===\n";
    $boxtest_kpi = OO_DB::get_kpi_measure_by_key('boxtest1');
    if ($boxtest_kpi) {
        $status = $boxtest_kpi->is_active ? 'Active' : 'Inactive';
        echo "Boxtest KPI found: ID {$boxtest_kpi->kpi_measure_id}, Status: {$status}\n";
    } else {
        echo "Boxtest KPI not found!\n";
    }
    
    echo "\n=== Summary ===\n";
    echo "Total links: " . count($all_links) . "\n";
    echo "Active links: " . count($active_links) . "\n";
    echo "Legacy active links: " . count($legacy_active_links) . "\n";
    
    if (count($active_links) === count($legacy_active_links)) {
        echo "✓ Legacy parameter compatibility working correctly\n";
    } else {
        echo "✗ Legacy parameter compatibility issue\n";
    }
}

// Run the test
test_kpi_filtering();
?> 