<?php
/**
 * Stream Feature Set Debug Logger
 * 
 * Provides dedicated logging for stream feature set issues
 * Logs are stored in debug/stream-feature-debug.log
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Log a stream feature debug message
 * 
 * @param string $message The message to log
 * @param mixed $data Optional data to log (will be printed with print_r)
 * @param string $level Log level (INFO, WARNING, ERROR, DEBUG)
 */
function oo_stream_debug_log($message, $data = null, $level = 'DEBUG') {
    // Get the debug directory path
    $debug_dir = plugin_dir_path(dirname(__FILE__)) . 'debug/';
    $log_file = $debug_dir . 'stream-feature-debug.log';
    
    // Create timestamp
    $timestamp = current_time('Y-m-d H:i:s T');
    
    // Format the log entry
    $log_entry = "[{$timestamp}] [{$level}] {$message}";
    
    // Add data if provided
    if ($data !== null) {
        ob_start();
        print_r($data, true);
        $data_output = ob_get_clean();
        $log_entry .= "\n" . $data_output;
    }
    
    $log_entry .= "\n";
    
    // Write to log file
    error_log($log_entry, 3, $log_file);
}

/**
 * Clear the stream feature debug log
 */
function oo_stream_debug_clear_log() {
    $debug_dir = plugin_dir_path(dirname(__FILE__)) . 'debug/';
    $log_file = $debug_dir . 'stream-feature-debug.log';
    
    if (file_exists($log_file)) {
        file_put_contents($log_file, '');
    }
}

/**
 * Get the contents of the stream feature debug log
 * 
 * @param int $lines Number of lines to retrieve (0 = all)
 * @return string Log contents
 */
function oo_stream_debug_get_log($lines = 0) {
    $debug_dir = plugin_dir_path(dirname(__FILE__)) . 'debug/';
    $log_file = $debug_dir . 'stream-feature-debug.log';
    
    if (!file_exists($log_file)) {
        return '';
    }
    
    if ($lines === 0) {
        return file_get_contents($log_file);
    }
    
    // Get last N lines
    $file = new SplFileObject($log_file, 'r');
    $file->seek(PHP_INT_MAX);
    $total_lines = $file->key() + 1;
    
    $start_line = max(0, $total_lines - $lines);
    $output = [];
    
    for ($i = $start_line; $i < $total_lines; $i++) {
        $file->seek($i);
        $output[] = $file->current();
    }
    
    return implode('', $output);
} 