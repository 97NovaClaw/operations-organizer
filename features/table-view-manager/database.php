<?php
/**
 * Table View Manager - Database Operations
 * 
 * Handles all database interactions for user table preferences
 * 
 * @package OperationsOrganizer
 * @subpackage Features/TableViewManager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class OO_Table_View_Manager_Database {
    
    /**
     * Get the preferences table name
     * 
     * @return string
     */
    private static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'oo_user_table_preferences';
    }
    
    /**
     * Get user preferences for specified tables
     * 
     * @param int $user_id User ID
     * @param array $table_ids Array of table IDs to fetch
     * @return array Associative array of table_id => preferences
     */
    public static function get_preferences($user_id, $table_ids = array()) {
        global $wpdb;
        
        if (!$user_id || !is_numeric($user_id)) {
            return array();
        }
        
        $table_name = self::get_table_name();
        $preferences = array();
        
        // Build query
        if (empty($table_ids)) {
            // Get all preferences for user
            $query = $wpdb->prepare(
                "SELECT table_id, preferences 
                FROM {$table_name} 
                WHERE user_id = %d",
                $user_id
            );
        } else {
            // Get specific table preferences
            $placeholders = array_fill(0, count($table_ids), '%s');
            $placeholders_string = implode(',', $placeholders);
            
            $query = $wpdb->prepare(
                "SELECT table_id, preferences 
                FROM {$table_name} 
                WHERE user_id = %d 
                AND table_id IN ({$placeholders_string})",
                array_merge(array($user_id), $table_ids)
            );
        }
        
        $results = $wpdb->get_results($query);
        
        // Format results
        foreach ($results as $row) {
            $preferences[$row->table_id] = json_decode($row->preferences, true);
        }
        
        return $preferences;
    }
    
    /**
     * Save user preference for a specific table
     * 
     * @param int $user_id User ID
     * @param string $table_id Table identifier
     * @param array $preferences Preferences array to save
     * @return bool Success status
     */
    public static function save_preference($user_id, $table_id, $preferences) {
        global $wpdb;
        
        if (!$user_id || !is_numeric($user_id) || empty($table_id)) {
            return false;
        }
        
        $table_name = self::get_table_name();
        
        // Sanitize table_id
        $table_id = sanitize_key($table_id);
        
        // Encode preferences to JSON
        $preferences_json = wp_json_encode($preferences);
        if ($preferences_json === false) {
            error_log('[Table View Manager] Failed to encode preferences to JSON');
            return false;
        }
        
        // Use INSERT ... ON DUPLICATE KEY UPDATE
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table_name} (user_id, table_id, preferences, last_updated) 
            VALUES (%d, %s, %s, NOW()) 
            ON DUPLICATE KEY UPDATE 
            preferences = VALUES(preferences), 
            last_updated = NOW()",
            $user_id,
            $table_id,
            $preferences_json
        ));
        
        if ($result === false) {
            error_log('[Table View Manager] Database error: ' . $wpdb->last_error);
            return false;
        }
        
        return true;
    }
    
    /**
     * Delete user preference for a specific table
     * 
     * @param int $user_id User ID
     * @param string $table_id Table identifier
     * @return bool Success status
     */
    public static function delete_preference($user_id, $table_id) {
        global $wpdb;
        
        if (!$user_id || !is_numeric($user_id) || empty($table_id)) {
            return false;
        }
        
        $table_name = self::get_table_name();
        
        $result = $wpdb->delete(
            $table_name,
            array(
                'user_id' => $user_id,
                'table_id' => sanitize_key($table_id)
            ),
            array('%d', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete all preferences for a user
     * 
     * @param int $user_id User ID
     * @return bool Success status
     */
    public static function delete_all_user_preferences($user_id) {
        global $wpdb;
        
        if (!$user_id || !is_numeric($user_id)) {
            return false;
        }
        
        $table_name = self::get_table_name();
        
        $result = $wpdb->delete(
            $table_name,
            array('user_id' => $user_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get preference statistics
     * 
     * @return array Statistics about stored preferences
     */
    public static function get_statistics() {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        $stats = array(
            'total_preferences' => 0,
            'unique_users' => 0,
            'unique_tables' => 0,
            'most_customized_tables' => array()
        );
        
        // Total preferences
        $stats['total_preferences'] = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        
        // Unique users
        $stats['unique_users'] = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$table_name}");
        
        // Unique tables
        $stats['unique_tables'] = $wpdb->get_var("SELECT COUNT(DISTINCT table_id) FROM {$table_name}");
        
        // Most customized tables
        $most_customized = $wpdb->get_results(
            "SELECT table_id, COUNT(*) as user_count 
            FROM {$table_name} 
            GROUP BY table_id 
            ORDER BY user_count DESC 
            LIMIT 5"
        );
        
        foreach ($most_customized as $table) {
            $stats['most_customized_tables'][$table->table_id] = $table->user_count;
        }
        
        return $stats;
    }
} 