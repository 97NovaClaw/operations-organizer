<?php
/**
 * Master Log Feature
 * 
 * Centralized activity logging system for all job-related events
 * 
 * @package Operations_Organizer
 */

/*
Feature Tree: Master Log

master-log/
├── index.php          # Entry point & hook registration
├── database.php       # Database operations & table management
├── logger.php         # Core logging class & business logic
├── formatters.php     # Activity description generators
├── migration.php      # Database migration & data import
├── ajax.php          # AJAX handlers for admin UI
└── views/
    └── admin-page.php # Simple admin view for logs
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Include dependencies
require_once dirname(__FILE__) . '/database.php';
require_once dirname(__FILE__) . '/logger.php';
require_once dirname(__FILE__) . '/formatters.php';
require_once dirname(__FILE__) . '/migration.php';

class OO_Master_Log_Feature {
    
    /**
     * Initialize the feature
     */
    public static function init() {
        oo_log('[MASTER_LOG] Initializing Master Log feature', __METHOD__);
        error_log('[MASTER_LOG_DEBUG] Master Log init() called');
        
        // Run migration on activation or when needed
        add_action('admin_init', array(__CLASS__, 'maybe_run_migration'));
        
        // Register the primary logging action
        add_action('oo_log_activity', array('OO_Master_Log', 'log_activity'), 10, 1);
        
        // Hook into existing events
        self::register_event_hooks();
        
        // Register admin page
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'), 20);
        
        // Initialize AJAX handlers if in admin
        if (is_admin()) {
            require_once dirname(__FILE__) . '/ajax.php';
            OO_Master_Log_AJAX::init();
        }
    }
    
    /**
     * Register hooks for existing events
     */
    private static function register_event_hooks() {
        // ===== Phase Change Events =====
        add_action('oo_kanban_phase_changed', array(__CLASS__, 'handle_phase_change'), 10, 4);
        add_action('oo_job_details_phase_changed', array(__CLASS__, 'handle_phase_change'), 10, 4);
        
        // ===== Job Management Events =====
        add_action('oo_job_created', array(__CLASS__, 'handle_job_created'), 10, 2);
        add_action('oo_job_updated', array(__CLASS__, 'handle_job_updated'), 10, 3);
        add_action('oo_job_deleted', array(__CLASS__, 'handle_job_deleted'), 10, 2);
        
        // ===== Job-Stream Assignment Events =====
        add_action('oo_job_stream_assigned', array(__CLASS__, 'handle_stream_assigned'), 10, 3);
        add_action('oo_job_stream_removed', array(__CLASS__, 'handle_stream_removed'), 10, 3);
        add_action('oo_job_stream_updated', array(__CLASS__, 'handle_job_stream_updated'), 10, 4);
        
        // ===== Note Events =====
        add_action('oo_note_added', array(__CLASS__, 'handle_note_added'), 10, 4);
        
        // ===== Phase Definition Events =====
        add_action('oo_phase_created', array(__CLASS__, 'handle_phase_created'), 10, 2);
        add_action('oo_phase_updated', array(__CLASS__, 'handle_phase_updated'), 10, 4);
        add_action('oo_phase_deleted', array(__CLASS__, 'handle_phase_deleted'), 10, 2);
    }
    
    /**
     * Check and run migration if needed
     */
    public static function maybe_run_migration() {
        $current_version = get_option('oo_master_log_version', '0');
        $target_version = '1.0.0';
        
        if (version_compare($current_version, $target_version, '<')) {
            oo_log('[MASTER_LOG] Running migration from version ' . $current_version . ' to ' . $target_version, __METHOD__);
            
            if (OO_Master_Log_Migration::run()) {
                update_option('oo_master_log_version', $target_version);
                oo_log('[MASTER_LOG] Migration completed successfully', __METHOD__);
            } else {
                oo_log('[MASTER_LOG] Migration failed', __METHOD__);
            }
        }
    }
    
    /**
     * Add admin menu item
     */
    public static function add_admin_menu() {
        error_log('[MASTER_LOG_DEBUG] add_admin_menu() called');
        add_menu_page(
            __('Activity Log', 'operations-organizer'),
            __('Activity Log', 'operations-organizer'),
            oo_get_capability(),
            'oo_activity_log',
            array(__CLASS__, 'render_admin_page'),
            'dashicons-list-view',
            30
        );
    }
    
    /**
     * Render admin page
     */
    public static function render_admin_page() {
        include dirname(__FILE__) . '/views/admin-page.php';
    }
    
    // ===== Event Handlers =====
    
    /**
     * Handle phase change from Kanban or Job Details
     */
    public static function handle_phase_change($job_stream_id, $from_phase_id, $to_phase_id, $user_id) {
        // Get job stream data for context
        $job_stream = OO_DB::get_job_stream($job_stream_id);
        if (!$job_stream) {
            return;
        }
        
        // Get phase names
        $from_phase = $from_phase_id ? OO_DB::get_phase($from_phase_id) : null;
        $to_phase = OO_DB::get_phase($to_phase_id);
        
        $args = array(
            'activity_type' => 'PHASE_CHANGED',
            'activity_level' => 'stream',
            'job_id' => $job_stream->job_id,
            'stream_id' => $job_stream->stream_id,
            'job_stream_id' => $job_stream_id,
            'related_id' => $to_phase_id,
            'related_type' => 'phase',
            'metadata' => array(
                'from_phase_id' => $from_phase_id,
                'to_phase_id' => $to_phase_id,
                'from_phase_name' => $from_phase ? $from_phase->phase_name : null,
                'to_phase_name' => $to_phase->phase_name
            )
        );
        
        do_action('oo_log_activity', $args);
    }
    
    /**
     * Handle job created event
     */
    public static function handle_job_created($job_id, $job_data) {
        $args = array(
            'activity_type' => 'JOB_CREATED',
            'activity_level' => 'job',
            'job_id' => $job_id,
            'metadata' => array(
                'job_number' => $job_data['job_number'],
                'client_name' => $job_data['client_name'],
                'start_date' => $job_data['start_date'],
                'due_date' => $job_data['due_date']
            )
        );
        
        do_action('oo_log_activity', $args);
    }
    
    /**
     * Handle job updated event
     */
    public static function handle_job_updated($job_id, $field_name, $old_value, $new_value) {
        $job = OO_DB::get_job($job_id);
        
        $args = array(
            'activity_type' => 'JOB_UPDATED',
            'activity_level' => 'job',
            'job_id' => $job_id,
            'field_name' => $field_name,
            'old_value' => $old_value,
            'new_value' => $new_value,
            'metadata' => array(
                'job_number' => $job->job_number
            )
        );
        
        do_action('oo_log_activity', $args);
    }
    
    /**
     * Handle stream assigned to job
     */
    public static function handle_stream_assigned($job_id, $stream_id, $job_stream_id) {
        $stream = OO_DB::get_stream($stream_id);
        
        $args = array(
            'activity_type' => 'STREAM_ASSIGNED_TO_JOB',
            'activity_level' => 'job',
            'job_id' => $job_id,
            'stream_id' => $stream_id,
            'job_stream_id' => $job_stream_id,
            'related_id' => $stream_id,
            'related_type' => 'stream',
            'metadata' => array(
                'stream_name' => $stream->stream_name
            )
        );
        
        do_action('oo_log_activity', $args);
    }
    
    /**
     * Handle note added
     */
    public static function handle_note_added($job_id, $job_stream_id, $note_content, $note_type) {
        $args = array(
            'activity_type' => 'NOTE_ADDED',
            'activity_level' => $job_stream_id ? 'stream' : 'job',
            'job_id' => $job_id,
            'job_stream_id' => $job_stream_id,
            'user_notes' => $note_content,
            'metadata' => array(
                'note_type' => $note_type
            )
        );
        
        do_action('oo_log_activity', $args);
    }
}

// Feature is initialized directly from operations-organizer.php to ensure proper menu registration 