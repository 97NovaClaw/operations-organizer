<?php
/**
 * View Stream Feature Debug Log
 * 
 * Provides a web interface to view the stream feature debug log
 */

// Check if we're being included from WordPress admin
$is_admin_include = defined('ABSPATH') && is_admin();

if (!$is_admin_include) {
    // Find and load WordPress
    $wp_load_path = null;
    $dir = dirname(__FILE__);
    for ($i = 0; $i < 10; $i++) {
        if (file_exists($dir . '/wp-load.php')) {
            $wp_load_path = $dir . '/wp-load.php';
            break;
        }
        $dir = dirname($dir);
    }

    if (!$wp_load_path) {
        die('Error: Could not find wp-load.php. Please ensure this file is in your WordPress plugin directory.');
    }

    require_once($wp_load_path);

    // Check if user has admin privileges
    if (!current_user_can('manage_options')) {
        wp_die('Access denied');
    }
}

// Include the debug logger
require_once dirname(__FILE__) . '/stream-feature-debug.php';

// Handle clear log request
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    oo_stream_debug_clear_log();
    $redirect_url = $is_admin_include 
        ? admin_url('admin.php?page=oo_stream_debug_log')
        : remove_query_arg('clear');
    wp_redirect($redirect_url);
    exit;
}

// Get number of lines to display
$lines = isset($_GET['lines']) ? intval($_GET['lines']) : 100;

// If in admin, use WordPress admin styles
if ($is_admin_include): ?>
<div class="wrap">
    <h1><?php _e('Stream Feature Debug Log', 'operations-organizer'); ?></h1>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <a href="?page=oo_stream_debug_log&lines=50" class="button">Last 50</a>
            <a href="?page=oo_stream_debug_log&lines=100" class="button">Last 100</a>
            <a href="?page=oo_stream_debug_log&lines=500" class="button">Last 500</a>
            <a href="?page=oo_stream_debug_log&lines=0" class="button">All</a>
            <a href="?page=oo_stream_debug_log&clear=1" class="button button-secondary" onclick="return confirm('Clear the log file?');">Clear Log</a>
        </div>
        <div class="alignright">
            <label>
                <input type="checkbox" id="autoRefresh" onchange="toggleAutoRefresh(this)">
                <?php _e('Auto-refresh (2s)', 'operations-organizer'); ?>
            </label>
        </div>
    </div>
    
    <div style="background: #f8f8f8; border: 1px solid #ddd; padding: 15px; margin-top: 20px; font-family: monospace; white-space: pre-wrap; overflow-x: auto;">
<?php else: ?>
<!DOCTYPE html>
<html>
<head>
    <title>Stream Feature Debug Log</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            margin: 0;
        }
        .controls {
            background: #2d2d30;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .controls a, .controls button {
            background: #007cba;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            margin-right: 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .controls a:hover, .controls button:hover {
            background: #005a87;
        }
        .log-content {
            background: #1e1e1e;
            border: 1px solid #3e3e42;
            padding: 20px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            border-radius: 5px;
        }
        .log-entry {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #3e3e42;
        }
        .timestamp {
            color: #608b4e;
        }
        .level-ERROR {
            color: #f44747;
            font-weight: bold;
        }
        .level-WARNING {
            color: #ff8800;
        }
        .level-INFO {
            color: #4ec9b0;
        }
        .level-DEBUG {
            color: #d4d4d4;
        }
        .auto-refresh {
            display: inline-block;
            margin-left: 20px;
        }
        .auto-refresh input {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="controls">
        <h1 style="margin: 0 0 15px 0; color: white;">Stream Feature Debug Log</h1>
        <a href="?lines=50">Last 50</a>
        <a href="?lines=100">Last 100</a>
        <a href="?lines=500">Last 500</a>
        <a href="?lines=0">All</a>
        <a href="?clear=1" onclick="return confirm('Clear the log file?');" style="background: #dc3545;">Clear Log</a>
        
        <div class="auto-refresh">
            <label style="color: white;">
                <input type="checkbox" id="autoRefresh" onchange="toggleAutoRefresh(this)">
                Auto-refresh (2s)
            </label>
        </div>
    </div>
    
    <div class="log-content">
<?php endif; ?>
        <?php
        $log_content = oo_stream_debug_get_log($lines);
        
        if (empty($log_content)) {
            echo '<div style="color: #808080;">Log is empty</div>';
        } else {
            // Parse and format log entries
            $entries = explode("\n[", $log_content);
            
            foreach ($entries as $i => $entry) {
                if (empty(trim($entry))) continue;
                
                // Add back the [ for entries after the first
                if ($i > 0) $entry = '[' . $entry;
                
                // Extract timestamp and level
                if (preg_match('/\[(.*?)\]\s*\[(.*?)\]\s*(.*)$/s', $entry, $matches)) {
                    $timestamp = $matches[1];
                    $level = $matches[2];
                    $message = $matches[3];
                    
                    echo '<div class="log-entry">';
                    echo '<span class="timestamp">[' . htmlspecialchars($timestamp) . ']</span> ';
                    echo '<span class="level-' . htmlspecialchars($level) . '">[' . htmlspecialchars($level) . ']</span> ';
                    echo '<span>' . htmlspecialchars($message) . '</span>';
                    echo '</div>';
                } else {
                    echo '<div class="log-entry">' . htmlspecialchars($entry) . '</div>';
                }
            }
        }
        ?>
<?php if ($is_admin_include): ?>
    </div>
    
    <p class="description">
        <?php _e('Log file location:', 'operations-organizer'); ?> <?php echo plugin_dir_path(dirname(__FILE__)) . 'stream-feature-debug.log'; ?><br>
        <?php _e('Showing:', 'operations-organizer'); ?> <?php echo $lines === 0 ? __('All entries', 'operations-organizer') : sprintf(__('Last %d lines', 'operations-organizer'), $lines); ?>
    </p>
</div>

<script>
    let refreshInterval;
    
    function toggleAutoRefresh(checkbox) {
        if (checkbox.checked) {
            refreshInterval = setInterval(() => {
                location.reload();
            }, 2000);
        } else {
            clearInterval(refreshInterval);
        }
    }
</script>
<?php else: ?>
    </div>
    
    <div style="margin-top: 20px; color: #808080;">
        <p>Log file location: <?php echo plugin_dir_path(dirname(__FILE__)) . 'stream-feature-debug.log'; ?></p>
        <p>Showing: <?php echo $lines === 0 ? 'All entries' : 'Last ' . $lines . ' lines'; ?></p>
    </div>
    
    <script>
        let refreshInterval;
        
        function toggleAutoRefresh(checkbox) {
            if (checkbox.checked) {
                refreshInterval = setInterval(() => {
                    location.reload();
                }, 2000);
            } else {
                clearInterval(refreshInterval);
            }
        }
        
        // Scroll to bottom on load if auto-refresh is enabled
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('auto') === '1') {
                document.getElementById('autoRefresh').checked = true;
                toggleAutoRefresh(document.getElementById('autoRefresh'));
                window.scrollTo(0, document.body.scrollHeight);
            }
        }
    </script>
</body>
</html>
<?php endif; ?> 