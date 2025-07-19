<?php
/**
 * View Stream Feature Debug Log
 * 
 * Provides a web interface to view the stream feature debug log
 */

// Load WordPress
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php');

// Check if user has admin privileges
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

// Include the debug logger
require_once dirname(__FILE__) . '/stream-feature-debug.php';

// Handle clear log request
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    oo_stream_debug_clear_log();
    wp_redirect(remove_query_arg('clear'));
    exit;
}

// Get number of lines to display
$lines = isset($_GET['lines']) ? intval($_GET['lines']) : 100;

?>
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
    </div>
    
    <div style="margin-top: 20px; color: #808080;">
        <p>Log file location: <?php echo plugin_dir_path(dirname(__FILE__)) . 'stream-feature-debug.log'; ?></p>
        <p>Showing: <?php echo $lines === 0 ? 'All entries' : 'Last ' . $lines . ' lines'; ?></p>
    </div>
</body>
</html> 