<?php
/**
 * Debug Log Toggle
 * 
 * This page provides a simple toggle for enabling/disabling debug logging.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Add admin menu page for debug toggle only
add_action('admin_menu', 'oo_add_debug_toggle_page');
function oo_add_debug_toggle_page() {
    add_menu_page(
        'Debug Toggle',
        'Debug Toggle',
        'activate_plugins',
        'oo_fix_database',
        'oo_debug_toggle_page',
        'dashicons-admin-tools',
        99
    );
}

function oo_debug_toggle_page() {
    // Handle debug toggle form submission
    if (isset($_POST['oo_debug_action']) && check_admin_referer('oo_toggle_debug_log')) {
        if ($_POST['oo_debug_action'] === 'enable') {
            update_option('oo_enable_debugging', 'yes');
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Debug logging has been enabled.', 'operations-organizer') . '</p></div>';
        } elseif ($_POST['oo_debug_action'] === 'disable') {
            update_option('oo_enable_debugging', 'no');
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Debug logging has been disabled.', 'operations-organizer') . '</p></div>';
        }
    }
    
    echo '<div class="wrap">';
    echo '<h1>Operations Organizer - Debug Toggle</h1>';
    
    // Ensure user has necessary permissions
    if (!current_user_can('activate_plugins')) {
        echo '<div class="notice notice-error"><p>You do not have sufficient permissions to manage debug settings. Please contact your site administrator.</p></div>';
        echo '</div>';
        return;
    }
    
    // Get current debug status
    $debug_enabled = get_option('oo_enable_debugging', 'no') === 'yes';
    
    echo '<div style="max-width: 600px;">';
    echo '<p>Use this toggle to enable or disable debug logging for the Operations Organizer plugin.</p>';
    
    // Debug toggle form
    echo '<form method="post" style="background: #f8f8f8; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin: 20px 0;">';
    wp_nonce_field('oo_toggle_debug_log');
    echo '<h3>Debug Logging</h3>';
    echo '<p><strong>Current Status:</strong> ' . ($debug_enabled ? '<span style="color: green;">Enabled</span>' : '<span style="color: red;">Disabled</span>') . '</p>';
    
    if ($debug_enabled) {
        echo '<p>Debug logging is currently <strong>enabled</strong>. Debug information will be written to the WordPress debug log.</p>';
        echo '<input type="hidden" name="oo_debug_action" value="disable">';
        echo '<input type="submit" class="button button-primary" value="Disable Debug Logging">';
    } else {
        echo '<p>Debug logging is currently <strong>disabled</strong>. No debug information will be written to logs.</p>';
        echo '<input type="hidden" name="oo_debug_action" value="enable">';
        echo '<input type="submit" class="button button-primary" value="Enable Debug Logging">';
    }
    
    echo '</form>';
    echo '</div>';
    
    echo '<p><a href="' . admin_url('admin.php?page=oo_dashboard') . '" class="button">Return to Dashboard</a></p>';
    echo '</div>';
} 