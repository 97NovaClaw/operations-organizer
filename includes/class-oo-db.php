<?php
// /includes/class-oo-db.php // Renamed file

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_DB { // Renamed class

    private static $jobs_table;
    private static $streams_table; // Was stream_types_table, renamed for clarity to match entity
    private static $job_streams_table;
    private static $phases_table;
    private static $job_logs_table;
    private static $employees_table;
    private static $buildings_table;
    private static $expenses_table;
    private static $expense_types_table;

    public static function init() {
        global $wpdb;
        // Define table names with new prefix
        self::$jobs_table = $wpdb->prefix . 'oo_jobs';
        self::$streams_table = $wpdb->prefix . 'oo_streams'; // Renamed from oo_stream_types
        self::$job_streams_table = $wpdb->prefix . 'oo_job_streams';
        self::$phases_table = $wpdb->prefix . 'oo_phases';
        self::$job_logs_table = $wpdb->prefix . 'oo_job_logs';
        self::$employees_table = $wpdb->prefix . 'oo_employees';
        self::$buildings_table = $wpdb->prefix . 'oo_buildings';
        self::$expenses_table = $wpdb->prefix . 'oo_expenses';
        self::$expense_types_table = $wpdb->prefix . 'oo_expense_types';
    }

    /**
     * Create/update custom database tables.
     */
    public static function create_tables() {
        oo_log('Attempting to create/update database tables...', __METHOD__);
        self::init();
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // SQL for oo_employees table
        $sql_employees = "CREATE TABLE " . self::$employees_table . " (
            employee_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wp_user_id BIGINT UNSIGNED NULL,
            employee_number VARCHAR(50) NULL, #Can be null if wp_user_id is set
            employee_pin VARCHAR(255) NULL, #Hashed PIN for QR login
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            job_title VARCHAR(100) NULL,
            is_active BOOLEAN NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (employee_id),
            UNIQUE KEY uq_employee_number (employee_number),
            KEY idx_wp_user_id (wp_user_id),
            INDEX idx_is_active (is_active)
        ) $charset_collate;";

        // SQL for oo_streams table (formerly oo_stream_types)
        $sql_streams = "CREATE TABLE " . self::$streams_table . " (
            stream_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            stream_name VARCHAR(100) NOT NULL,
            stream_description TEXT NULL,
            is_active BOOLEAN NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (stream_id),
            UNIQUE KEY uq_stream_name (stream_name)
        ) $charset_collate;";
        
        // SQL for oo_jobs table
        $sql_jobs = "CREATE TABLE " . self::$jobs_table . " (
            job_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_number VARCHAR(50) NOT NULL,
            client_name VARCHAR(255) NULL,
            client_contact TEXT NULL,
            start_date DATE NULL,
            due_date DATE NULL,
            overall_status VARCHAR(50) NOT NULL DEFAULT 'Pending', # e.g., 'Pending', 'In Progress', 'On Hold', 'Completed', 'Cancelled'
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (job_id),
            UNIQUE KEY uq_job_number (job_number),
            INDEX idx_overall_status (overall_status),
            INDEX idx_due_date (due_date)
        ) $charset_collate;";

        // SQL for oo_job_streams table (linking jobs to streams)
        $sql_job_streams = "CREATE TABLE " . self::$job_streams_table . " (
            job_stream_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_id BIGINT UNSIGNED NOT NULL,
            stream_id INT UNSIGNED NOT NULL,
            status_in_stream VARCHAR(50) NOT NULL DEFAULT 'Not Started', # e.g., 'Not Started', 'Active', 'Paused', 'Completed'
            assigned_manager_id BIGINT UNSIGNED NULL, # FK to wp_users or oo_employees
            start_date_stream DATE NULL,
            due_date_stream DATE NULL,
            building_id BIGINT UNSIGNED NULL, # FK to oo_buildings
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (job_stream_id),
            UNIQUE KEY uq_job_stream (job_id, stream_id), # A job can only have a specific stream type once
            FOREIGN KEY (job_id) REFERENCES " . self::$jobs_table . "(job_id) ON DELETE CASCADE,
            FOREIGN KEY (stream_id) REFERENCES " . self::$streams_table . "(stream_id) ON DELETE RESTRICT,
            FOREIGN KEY (building_id) REFERENCES " . self::$buildings_table . "(building_id) ON DELETE SET NULL,
            INDEX idx_status_in_stream (status_in_stream),
            INDEX idx_assigned_manager_id (assigned_manager_id)
        ) $charset_collate;";

        // SQL for oo_phases table
        $sql_phases = "CREATE TABLE " . self::$phases_table . " (
            phase_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            stream_id INT UNSIGNED NOT NULL, # FK to oo_streams
            phase_name VARCHAR(100) NOT NULL,
            phase_description TEXT NULL,
            order_in_stream INT NOT NULL DEFAULT 0,
            phase_type VARCHAR(50) NULL, # e.g., 'KPI Tracking', 'Inventory', 'Cleaning'
            default_kpi_units VARCHAR(50) NULL, # e.g. 'boxes', 'items', 'hours'
            is_active BOOLEAN NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (phase_id),
            FOREIGN KEY (stream_id) REFERENCES " . self::$streams_table . "(stream_id) ON DELETE CASCADE,
            UNIQUE KEY uq_stream_phase_name (stream_id, phase_name),
            INDEX idx_is_active (is_active),
            INDEX idx_order_in_stream (order_in_stream)
        ) $charset_collate;";

        // SQL for oo_job_logs table
        $sql_job_logs = "CREATE TABLE " . self::$job_logs_table . " (
            log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_id BIGINT UNSIGNED NOT NULL,      # FK to oo_jobs
            stream_id INT UNSIGNED NOT NULL,    # FK to oo_streams (denormalized for easier querying, or could join through job_streams)
            phase_id INT UNSIGNED NOT NULL,     # FK to oo_phases
            employee_id BIGINT UNSIGNED NOT NULL, # FK to oo_employees
            start_time DATETIME NOT NULL,
            stop_time DATETIME NULL,
            duration_minutes INT UNSIGNED NULL, # Calculated on stop
            kpi_data JSON NULL, # Flexible key-value pairs
            notes TEXT NULL,
            log_date DATE NOT NULL, # Date of the work
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (log_id),
            FOREIGN KEY (job_id) REFERENCES " . self::$jobs_table . "(job_id) ON DELETE CASCADE,
            FOREIGN KEY (stream_id) REFERENCES " . self::$streams_table . "(stream_id) ON DELETE RESTRICT,
            FOREIGN KEY (phase_id) REFERENCES " . self::$phases_table . "(phase_id) ON DELETE RESTRICT,
            FOREIGN KEY (employee_id) REFERENCES " . self::$employees_table . "(employee_id) ON DELETE RESTRICT,
            INDEX idx_start_time (start_time),
            INDEX idx_stop_time (stop_time),
            INDEX idx_log_date (log_date)
        ) $charset_collate;";

        // SQL for oo_buildings table
        $sql_buildings = "CREATE TABLE " . self::$buildings_table . " (
            building_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            building_name VARCHAR(150) NOT NULL,
            address TEXT NULL,
            storage_capacity_notes TEXT NULL,
            primary_contact_id BIGINT UNSIGNED NULL, # FK to oo_employees or wp_users
            is_active BOOLEAN NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (building_id),
            UNIQUE KEY uq_building_name (building_name)
        ) $charset_collate;";
        
        // SQL for oo_expense_types table
        $sql_expense_types = "CREATE TABLE " . self::$expense_types_table . " (
            expense_type_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            type_name VARCHAR(100) NOT NULL,
            default_unit VARCHAR(50) NULL, # e.g., 'hour', 'item', 'km', 'job'
            is_active BOOLEAN NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (expense_type_id),
            UNIQUE KEY uq_type_name (type_name)
        ) $charset_collate;";

        // SQL for oo_expenses table
        $sql_expenses = "CREATE TABLE " . self::$expenses_table . " (
            expense_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_id BIGINT UNSIGNED NOT NULL,
            stream_id INT UNSIGNED NULL, # Optional, if expense is for specific stream
            expense_type_id INT UNSIGNED NOT NULL,
            employee_id BIGINT UNSIGNED NULL, # Optional, if labor cost
            amount DECIMAL(10,2) NOT NULL,
            expense_date DATE NOT NULL,
            description TEXT NULL,
            receipt_image_url VARCHAR(255) NULL,
            related_log_id BIGINT UNSIGNED NULL, # Optional, FK to oo_job_logs
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (expense_id),
            FOREIGN KEY (job_id) REFERENCES " . self::$jobs_table . "(job_id) ON DELETE CASCADE,
            FOREIGN KEY (stream_id) REFERENCES " . self::$streams_table . "(stream_id) ON DELETE SET NULL,
            FOREIGN KEY (expense_type_id) REFERENCES " . self::$expense_types_table . "(expense_type_id) ON DELETE RESTRICT,
            FOREIGN KEY (employee_id) REFERENCES " . self::$employees_table . "(employee_id) ON DELETE SET NULL,
            FOREIGN KEY (related_log_id) REFERENCES " . self::$job_logs_table . "(log_id) ON DELETE SET NULL,
            INDEX idx_expense_date (expense_date)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Order of execution matters due to foreign keys.
        // Create tables without FKs first, or ensure referenced tables exist.
        
        oo_log('Running dbDelta for employees table (' . self::$employees_table . ').', __METHOD__);
        dbDelta( $sql_employees );
        
        oo_log('Running dbDelta for streams table (' . self::$streams_table . ').', __METHOD__);
        dbDelta( $sql_streams );

        oo_log('Running dbDelta for jobs table (' . self::$jobs_table . ').', __METHOD__);
        dbDelta( $sql_jobs );
        
        oo_log('Running dbDelta for buildings table (' . self::$buildings_table . ').', __METHOD__);
        dbDelta( $sql_buildings );

        oo_log('Running dbDelta for expense_types table (' . self::$expense_types_table . ').', __METHOD__);
        dbDelta( $sql_expense_types );
        
        // Tables with Foreign Keys (ensure referenced tables are created above)
        oo_log('Running dbDelta for phases table (' . self::$phases_table . ').', __METHOD__);
        dbDelta( $sql_phases );
        
        oo_log('Running dbDelta for job_logs table (' . self::$job_logs_table . ').', __METHOD__);
        dbDelta( $sql_job_logs );

        // Job Streams references Jobs, Streams, Buildings. Buildings should be created before this.
        // Temporarily remove FK to buildings if it causes issues with dbDelta order, then add it back.
        // For now, we assume dbDelta handles it or we create buildings table before this.
        // Re-checked: oo_buildings is defined and created before oo_job_streams references it.
        oo_log('Running dbDelta for job_streams table (' . self::$job_streams_table . ').', __METHOD__);
        dbDelta( $sql_job_streams );
        
        oo_log('Running dbDelta for expenses table (' . self::$expenses_table . ').', __METHOD__);
        dbDelta( $sql_expenses );
        
        oo_log('Finished dbDelta calls. Check logs for specific table creation/update details.', __METHOD__);
    }

    // For example, add_employee, get_employees will use self::$employees_table (which is now oo_employees)
    // Methods for phases will need to accept/filter by stream_type_id
    // Methods for job_logs will need to handle new kpi_data field

    // --- Employee CRUD Methods ---
    public static function add_employee( $args ) { // MODIFIED to accept array
        oo_log('Attempting to add employee with args:', __METHOD__);
        oo_log($args, __METHOD__);
        self::init(); 
        global $wpdb;

        if ( empty( $args['first_name'] ) || empty( $args['last_name'] ) ) {
            return new WP_Error('missing_name', 'First Name and Last Name are required.');
        }
        // Additional validation: e.g. either employee_number or wp_user_id should exist
        if ( empty( $args['employee_number'] ) && empty( $args['wp_user_id'] ) ) {
             // Depending on strictness, this could be an error or a warning.
             // For now, allow it, but it's good to note.
        }
        if ( !empty($args['employee_number']) ){
            $exists = $wpdb->get_var( $wpdb->prepare("SELECT employee_id FROM " . self::$employees_table . " WHERE employee_number = %s", $args['employee_number']) );
            if ($exists) {
                return new WP_Error('employee_exists', 'Employee number already exists.');
            }
        }
        if ( !empty($args['wp_user_id']) ){
            $exists_wp = $wpdb->get_var( $wpdb->prepare("SELECT employee_id FROM " . self::$employees_table . " WHERE wp_user_id = %d", $args['wp_user_id']) );
            if ($exists_wp) {
                return new WP_Error('wp_user_id_exists', 'This WordPress User ID is already linked to an employee.');
            }
        }

        $data = array(
            'first_name' => sanitize_text_field( $args['first_name'] ),
            'last_name' => sanitize_text_field( $args['last_name'] ),
            'is_active' => isset($args['is_active']) ? intval($args['is_active']) : 1,
            'created_at' => current_time('mysql', 1),
            'updated_at' => current_time('mysql', 1) // Also set updated_at on creation
        );
        $formats = array('%s', '%s', '%d', '%s', '%s');

        if ( !empty($args['wp_user_id']) ) { $data['wp_user_id'] = intval($args['wp_user_id']); $formats[] = '%d'; }
        else { $data['wp_user_id'] = null; $formats[] = null; } // Explicitly set null if not provided
        
        if ( !empty($args['employee_number']) ) { $data['employee_number'] = sanitize_text_field($args['employee_number']); $formats[] = '%s'; }
        else { $data['employee_number'] = null; $formats[] = null; }

        if ( !empty($args['employee_pin']) ) { 
            // Assuming PIN is already hashed when passed in $args
            $data['employee_pin'] = $args['employee_pin']; 
            $formats[] = '%s'; 
        } else { $data['employee_pin'] = null; $formats[] = null; }

        if ( !empty($args['job_title']) ) { $data['job_title'] = sanitize_text_field($args['job_title']); $formats[] = '%s'; }
        else { $data['job_title'] = null; $formats[] = null; }
        
        // Clean up null formats as $wpdb->insert doesn't like null in format array
        $final_data = array();
        $final_formats = array();
        $format_idx = 0;
        foreach($data as $key => $value) {
            if ($formats[$format_idx] !== null) {
                $final_data[$key] = $value;
                $final_formats[] = $formats[$format_idx];
            } else if ($value !== null) { // If format was null, but value is not, this is an issue.
                 // This case should ideally not happen if logic is correct for setting null formats
            } else {
                 // If format is null and value is null, we can just pass the key with null value to insert
                 $final_data[$key] = null; 
                 // $wpdb->insert will handle this if the column is nullable.
            }
            $format_idx++;
        }

        $result = $wpdb->insert(self::$employees_table, $final_data, $final_formats);

        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not add employee. Error: ' . $wpdb->last_error);
            oo_log('Error adding employee: DB insert failed.', array('error' => $error, 'data' => $final_data, 'formats' => $final_formats) );
            return $error;
        }
        oo_log('Employee added successfully. ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    public static function get_employee( $employee_id ) {
        oo_log('Attempting to get employee by ID: ' . $employee_id, __METHOD__);
        self::init();
        global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$employees_table . " WHERE employee_id = %d", $employee_id ) );
        oo_log('Result for get_employee: ', $result);
        return $result;
    }
    
    public static function get_employee_by_number( $employee_number ) {
        oo_log('Attempting to get employee by number: ' . $employee_number, __METHOD__);
        self::init();
        global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$employees_table . " WHERE employee_number = %s", $employee_number ) );
        oo_log('Result for get_employee_by_number: ', $result);
        return $result;
    }

    public static function get_employee_by_wp_user_id( $wp_user_id ) {
        oo_log('Attempting to get employee by WP User ID: ' . $wp_user_id, __METHOD__);
        self::init();
        global $wpdb;
        if (empty($wp_user_id) || !is_numeric($wp_user_id)) {
            return null;
        }
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$employees_table . " WHERE wp_user_id = %d", intval($wp_user_id) ) );
        oo_log('Result for get_employee_by_wp_user_id: ', $result);
        return $result;
    }

    public static function update_employee( $employee_id, $args ) { // MODIFIED to accept array
        oo_log('Attempting to update employee ID: ' . $employee_id, __METHOD__);
        oo_log($args, __METHOD__);
        self::init();
        global $wpdb;
        $employee_id = intval($employee_id);

        if ( empty($employee_id) ){
            return new WP_Error('invalid_id', 'Invalid Employee ID for update.');
        }
        if ( empty( $args['first_name'] ) || empty( $args['last_name'] ) ) {
             return new WP_Error('missing_name', 'First Name and Last Name are required for update.');
        }
        
        if ( !empty($args['employee_number']) ){
            $existing_employee = $wpdb->get_row( $wpdb->prepare("SELECT employee_id FROM " . self::$employees_table . " WHERE employee_number = %s AND employee_id != %d", $args['employee_number'], $employee_id) );
            if ($existing_employee) {
                return new WP_Error('employee_number_exists', 'This employee number is already assigned to another employee.');
            }
        }
        if ( !empty($args['wp_user_id']) ){
            $existing_wp_link = $wpdb->get_row( $wpdb->prepare("SELECT employee_id FROM " . self::$employees_table . " WHERE wp_user_id = %d AND employee_id != %d", $args['wp_user_id'], $employee_id) );
            if ($existing_wp_link) {
                return new WP_Error('wp_user_id_exists', 'This WordPress User ID is already assigned to another employee.');
            }
        }

        $data = array();
        $formats = array();

        if (isset($args['first_name'])) { $data['first_name'] = sanitize_text_field($args['first_name']); $formats[] = '%s'; }
        if (isset($args['last_name'])) { $data['last_name'] = sanitize_text_field($args['last_name']); $formats[] = '%s'; }
        
        if (array_key_exists('employee_number', $args)) { $data['employee_number'] = $args['employee_number'] ? sanitize_text_field($args['employee_number']) : null; $formats[] = '%s'; }
        if (array_key_exists('wp_user_id', $args)) { $data['wp_user_id'] = $args['wp_user_id'] ? intval($args['wp_user_id']) : null; $formats[] = '%d'; }
        if (array_key_exists('employee_pin', $args)) { 
            // Assuming PIN is already hashed or null to clear
            $data['employee_pin'] = $args['employee_pin']; 
            $formats[] = '%s'; 
        }
        if (array_key_exists('job_title', $args)) { $data['job_title'] = $args['job_title'] ? sanitize_text_field($args['job_title']) : null; $formats[] = '%s'; }
        if (isset($args['is_active'])) { $data['is_active'] = intval($args['is_active']); $formats[] = '%d'; }

        if (empty($data)) {
            return new WP_Error('no_data', 'No data provided for update.');
        }

        $data['updated_at'] = current_time('mysql', 1);
        $formats[] = '%s';

        $result = $wpdb->update(self::$employees_table, $data, array( 'employee_id' => $employee_id ), $formats, array( '%d' ));
        
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update employee. Error: ' . $wpdb->last_error);
            oo_log('Error updating employee: DB update failed. ' . $wpdb->last_error, array('error' => $error, 'data' => $data, 'id' => $employee_id));
            return $error;
        }
        oo_log('Employee updated successfully. ID: ' . $employee_id, __METHOD__);
        return true;
    }

    public static function toggle_employee_status( $employee_id, $is_active ) {
        oo_log('Toggling employee status for ID: ' . $employee_id . ' to ' . $is_active, __METHOD__);
        self::init();
        global $wpdb;
        $result = $wpdb->update(self::$employees_table, array( 'is_active' => intval($is_active) ), array( 'employee_id' => $employee_id ), array( '%d' ), array( '%d' ));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update employee status. Error: ' . $wpdb->last_error);
            oo_log('Error toggling employee status: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Employee status toggled successfully for ID: ' . $employee_id, __METHOD__);
        return true;
    }

    public static function get_employees( $args = array() ) {
        oo_log('Attempting to get employees with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('is_active' => null, 'orderby' => 'last_name', 'order' => 'ASC', 'search' => '', 'number' => -1, 'offset' => 0);
        $args = wp_parse_args($args, $defaults);
        $sql_base = "SELECT * FROM " . self::$employees_table;
        $where_clauses = array(); $query_params = array();
        if ( !is_null($args['is_active']) ) { $where_clauses[] = "is_active = %d"; $query_params[] = $args['is_active']; }
        if ( !empty($args['search']) ) { $search_term = '%' . $wpdb->esc_like($args['search']) . '%'; $where_clauses[] = "(employee_number LIKE %s OR first_name LIKE %s OR last_name LIKE %s)"; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;}
        $sql_where = ""; if ( !empty($where_clauses) ) { $sql_where = " WHERE " . implode(" AND ", $where_clauses);}
        $sql = $sql_base . $sql_where;
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $orderby_clause = ""; if (!empty($args['orderby'])) { $orderby_val = sanitize_sql_orderby($args['orderby']); if ($orderby_val) { $order_val = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC'; $orderby_clause = " ORDER BY $orderby_val $order_val"; }}
        $sql .= $orderby_clause;
        $limit_clause = ""; if ( isset($args['number']) && $args['number'] > 0 ) { $limit_clause = sprintf(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));}
        $sql .= $limit_clause;
        $results = $wpdb->get_results( $sql );
        oo_log('Get employees query executed. SQL: ' . $sql . ' Number of results: ' . count($results), __METHOD__);
        return $results;
    }
    
    public static function get_employees_count( $args = array() ) {
        oo_log('Attempting to get employees count with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('is_active' => null, 'search' => ''); $args = wp_parse_args($args, $defaults);
        $sql = "SELECT COUNT(*) FROM " . self::$employees_table;
        $where_clauses = array(); $query_params = array();
        if ( !is_null($args['is_active']) ) { $where_clauses[] = "is_active = %d"; $query_params[] = $args['is_active']; }
        if ( !empty($args['search']) ) { $search_term = '%' . $wpdb->esc_like($args['search']) . '%'; $where_clauses[] = "(employee_number LIKE %s OR first_name LIKE %s OR last_name LIKE %s)"; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;}
        if ( !empty($where_clauses) ) { $sql .= " WHERE " . implode(" AND ", $where_clauses);}
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $count = $wpdb->get_var( $sql );
        oo_log('Employees count result: ' . $count . ' SQL: ' . $sql, __METHOD__);
        return $count;
    }

    /**
     * Delete an employee.
     * @param int $employee_id
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function delete_employee( $employee_id ) {
        self::init(); global $wpdb;
        $employee_id = intval($employee_id);
        if ( $employee_id <= 0 ) {
            return new WP_Error('invalid_employee_id', 'Invalid Employee ID for deletion.');
        }

        // Consider implications if employee_id is used in other tables (job_logs, expenses)
        // The current schema uses ON DELETE RESTRICT or SET NULL for these relationships.
        // Application logic in OO_Employee::delete() should check for active logs/assignments if strict deletion prevention is needed.

        oo_log('Attempting to delete employee ID: ' . $employee_id, __METHOD__);
        $result = $wpdb->delete( self::$employees_table, array( 'employee_id' => $employee_id ), array('%d') );

        if ( $result === false ) {
            oo_log('Error deleting employee ID ' . $employee_id . ': ' . $wpdb->last_error, __METHOD__);
            return new WP_Error('db_delete_error', 'Could not delete employee: ' . $wpdb->last_error);
        }
        if ( $result === 0 ) {
            // Not necessarily an error, could mean already deleted.
            oo_log('Employee not found for deletion or no rows affected. ID: ' . $employee_id, __METHOD__);
            return true; 
        }
        oo_log('Employee deleted successfully. ID: ' . $employee_id . ' Rows affected: ' . $result, __METHOD__);
        return true;
    }

    // ... (Placeholders for Stream Type, Phase, and Job Log methods to be refactored/added next)
    // ... existing code ...

    // --- Stream CRUD Methods (Was Stream Type) ---
    public static function add_stream( $stream_name, $stream_description = '', $is_active = 1 ) {
        // formerly add_stream_type
        oo_log('Attempting to add stream.', __METHOD__);
        oo_log(compact('stream_name', 'stream_description', 'is_active'), __METHOD__);
        self::init(); 
        global $wpdb;
        if (empty($stream_name)) {
            return new WP_Error('missing_field', 'Stream Name is required.');
        }
        $exists = $wpdb->get_var( $wpdb->prepare("SELECT stream_id FROM " . self::$streams_table . " WHERE stream_name = %s", $stream_name) );
        if ($exists) {
            return new WP_Error('stream_exists', 'Stream name already exists.');
        }
        $result = $wpdb->insert(
            self::$streams_table, 
            array(
                'stream_name' => sanitize_text_field($stream_name), 
                'stream_description' => sanitize_textarea_field($stream_description), 
                'is_active' => intval($is_active),
                'created_at' => current_time('mysql', 1),
                'updated_at' => current_time('mysql', 1)
            ), 
            array('%s', '%s', '%d', '%s', '%s')
        );
        if ($result === false) {
            return new WP_Error('db_error', 'Could not add stream. Error: ' . $wpdb->last_error);
        }
        return $wpdb->insert_id;
    }

    public static function get_stream($stream_id) {
        // formerly get_stream_type
        oo_log('Attempting to get stream by ID: ' . $stream_id, __METHOD__);
        self::init();
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$streams_table . " WHERE stream_id = %d", $stream_id ) );
    }
    
    public static function get_stream_by_name($stream_name) {
        // New method
        oo_log('Attempting to get stream by name: ' . $stream_name, __METHOD__);
        self::init();
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$streams_table . " WHERE stream_name = %s", $stream_name ) );
    }

    public static function update_stream($stream_id, $stream_name, $stream_description = null, $is_active = null) {
        // formerly update_stream_type
        oo_log('Attempting to update stream ID: ' . $stream_id, __METHOD__);
        oo_log(compact('stream_id', 'stream_name', 'stream_description', 'is_active'), __METHOD__);
        self::init();
        global $wpdb;
        if (empty($stream_name)) {
            return new WP_Error('missing_field', 'Stream Name is required.');
        }
        $existing_stream = $wpdb->get_row( $wpdb->prepare("SELECT stream_id FROM " . self::$streams_table . " WHERE stream_name = %s AND stream_id != %d", $stream_name, $stream_id) );
        if ($existing_stream) {
            return new WP_Error('stream_name_exists', 'This stream name is already in use.');
        }
        $data = array('stream_name' => sanitize_text_field($stream_name), 'updated_at' => current_time('mysql', 1));
        $formats = array('%s', '%s'); // name, updated_at
        if ( !is_null($stream_description) ) { $data['stream_description'] = sanitize_textarea_field($stream_description); $formats[] = '%s'; }
        if ( !is_null($is_active) ) { $data['is_active'] = intval($is_active); $formats[] = '%d'; }
        
        $result = $wpdb->update(self::$streams_table, $data, array( 'stream_id' => $stream_id ), $formats, array( '%d' ));
        if ($result === false) {
            return new WP_Error('db_error', 'Could not update stream. Error: ' . $wpdb->last_error);
        }
        return true;
    }

    public static function toggle_stream_status( $stream_id, $is_active ) {
        // formerly toggle_stream_type_status
        oo_log('Toggling stream status for ID: ' . $stream_id . ' to ' . $is_active, __METHOD__);
        self::init();
        global $wpdb;
        $result = $wpdb->update(
            self::$streams_table, 
            array( 'is_active' => intval($is_active), 'updated_at' => current_time('mysql', 1) ), 
            array( 'stream_id' => $stream_id ), 
            array( '%d', '%s' ), 
            array( '%d' )
        );
        if ($result === false) {
            return new WP_Error('db_error', 'Could not update stream status. Error: ' . $wpdb->last_error);
        }
        return true;
    }

    public static function get_streams( $args = array() ) {
        // formerly get_stream_types
        oo_log('Attempting to get streams with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('is_active' => null, 'orderby' => 'stream_name', 'order' => 'ASC', 'search' => '', 'number' => -1, 'offset' => 0);
        $args = wp_parse_args($args, $defaults);
        $sql_base = "SELECT * FROM " . self::$streams_table;
        $where_clauses = array(); $query_params = array();
        if ( !is_null($args['is_active']) ) { $where_clauses[] = "is_active = %d"; $query_params[] = $args['is_active']; }
        if ( !empty($args['search']) ) { $search_term = '%' . $wpdb->esc_like($args['search']) . '%'; $where_clauses[] = "(stream_name LIKE %s OR stream_description LIKE %s)"; $query_params[] = $search_term; $query_params[] = $search_term;}
        $sql_where = ""; if ( !empty($where_clauses) ) { $sql_where = " WHERE " . implode(" AND ", $where_clauses); }
        $sql = $sql_base . $sql_where;
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $orderby_clause = ""; if (!empty($args['orderby'])) { $orderby_val = sanitize_sql_orderby($args['orderby']); if ($orderby_val) { $order_val = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC'; $orderby_clause = " ORDER BY $orderby_val $order_val"; }}
        $sql .= $orderby_clause;
        $limit_clause = ""; if ( isset($args['number']) && $args['number'] > 0 ) { $limit_clause = sprintf(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));}
        $sql .= $limit_clause;
        return $wpdb->get_results( $sql );
    }
    
    public static function get_streams_count( $args = array() ) {
        // formerly get_stream_types_count
        oo_log('Attempting to get streams count with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('is_active' => null, 'search' => ''); $args = wp_parse_args($args, $defaults);
        $sql = "SELECT COUNT(*) FROM " . self::$streams_table;
        $where_clauses = array(); $query_params = array();
        if ( !is_null($args['is_active']) ) { $where_clauses[] = "is_active = %d"; $query_params[] = $args['is_active']; }
        if ( !empty($args['search']) ) { $search_term = '%' . $wpdb->esc_like($args['search']) . '%'; $where_clauses[] = "(stream_name LIKE %s OR stream_description LIKE %s)"; $query_params[] = $search_term; $query_params[] = $search_term;}
        if ( !empty($where_clauses) ) { $sql .= " WHERE " . implode(" AND ", $where_clauses); }
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $count = $wpdb->get_var( $sql );
        oo_log('Streams count result: ' . $count . ' SQL: ' . $sql, __METHOD__);
        return $count;
    }

    /**
     * Delete a stream type.
     * Note: This does not automatically handle related phases or job_streams.
     * Foreign key constraints (ON DELETE CASCADE for phases, ON DELETE RESTRICT for job_streams)
     * should be considered at the application level before calling this.
     *
     * @param int $stream_id The ID of the stream to delete.
     * @return bool|WP_Error True on success, false or WP_Error on failure.
     */
    public static function delete_stream( $stream_id ) {
        self::init(); global $wpdb;
        $stream_id = intval($stream_id);
        if ($stream_id <= 0) {
            return new WP_Error('invalid_stream_id', 'Invalid Stream ID for deletion.');
        }

        oo_log('Attempting to delete stream ID: ' . $stream_id, __METHOD__);

        // The OO_Stream class should perform checks (e.g., if stream is used in job_streams)
        // before calling this method due to ON DELETE RESTRICT on wp_oo_job_streams.
        // Phases linked to this stream will be deleted automatically due to ON DELETE CASCADE.

        $result = $wpdb->delete(self::$streams_table, array('stream_id' => $stream_id), array('%d'));

        if ($result === false) {
            oo_log('Error deleting stream ID ' . $stream_id . ': ' . $wpdb->last_error, __METHOD__);
            return new WP_Error('db_delete_error', 'Could not delete stream: ' . $wpdb->last_error);
        }
        if ($result === 0) {
            // This might not be an error; could mean the stream was already deleted.
            oo_log('Stream not found for deletion or no rows affected. ID: ' . $stream_id, __METHOD__);
            return true; 
        }
        oo_log('Stream deleted successfully. ID: ' . $stream_id, __METHOD__);
        return true;
    }

    // --- Phase CRUD Methods ---
    public static function add_phase( $stream_id, $phase_name, $phase_description = '', $order_in_stream = 0, $phase_type = null, $default_kpi_units = null, $is_active = 1 ) {
        oo_log('Attempting to add phase.', __METHOD__);
        oo_log(compact('stream_id', 'phase_name', 'phase_description', 'order_in_stream', 'phase_type', 'default_kpi_units', 'is_active'), __METHOD__);
        self::init(); global $wpdb;
        if (empty($stream_id) || empty($phase_name)) {
            $error = new WP_Error('missing_fields', 'Stream ID and Phase Name are required.');
            oo_log('Error adding phase: Missing fields.', $error);
            return $error;
        }
        $exists = $wpdb->get_var( $wpdb->prepare("SELECT phase_id FROM " . self::$phases_table . " WHERE stream_id = %d AND phase_name = %s", $stream_id, $phase_name) );
        if ($exists) {
            $error = new WP_Error('phase_exists', 'Phase name already exists for this stream.');
            oo_log('Error adding phase: Name exists for stream.', $error);
            return $error;
        }
        $result = $wpdb->insert(self::$phases_table, 
            array(
                'stream_id' => intval($stream_id),
                'phase_name' => sanitize_text_field($phase_name), 
                'phase_description' => sanitize_textarea_field($phase_description),
                'order_in_stream' => intval($order_in_stream),
                'phase_type' => $phase_type,
                'default_kpi_units' => $default_kpi_units,
                'is_active' => intval($is_active),
                'created_at' => current_time('mysql', 1)
            ), 
            array('%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s')
        );
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not add phase. Error: ' . $wpdb->last_error);
            oo_log('Error adding phase: DB insert failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Phase added successfully. ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    public static function get_phase( $phase_id ) {
        oo_log('Attempting to get phase by ID: ' . $phase_id, __METHOD__);
        self::init(); global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$phases_table . " WHERE phase_id = %d", $phase_id ) );
        oo_log('Result for get_phase: ', $result);
        return $result;
    }

    public static function update_phase( $phase_id, $stream_id, $phase_name, $phase_description = '', $order_in_stream = null, $phase_type = null, $default_kpi_units = null, $is_active = null ) {
        oo_log('Attempting to update phase ID: ' . $phase_id, __METHOD__);
        oo_log(compact('phase_id', 'stream_id', 'phase_name', 'phase_description', 'order_in_stream', 'phase_type', 'default_kpi_units', 'is_active'), __METHOD__);
        self::init(); global $wpdb;

        if (empty($stream_id) || empty($phase_name)) {
            $error = new WP_Error('missing_fields', 'Stream ID and Phase Name are required for update.');
            oo_log('Error updating phase: Missing fields.', $error);
            return $error;
        }

        $existing_phase = $wpdb->get_row( $wpdb->prepare("SELECT phase_id FROM " . self::$phases_table . " WHERE stream_id = %d AND phase_name = %s AND phase_id != %d", $stream_id, $phase_name, $phase_id) );
        if ($existing_phase) {
            $error = new WP_Error('phase_name_exists', 'This phase name is already in use for this stream on another phase.');
            oo_log('Error updating phase: Name exists for stream on another phase.', $error);
            return $error;
        }

        $data = array(
            'stream_id' => intval($stream_id),
            'phase_name' => sanitize_text_field($phase_name),
            'phase_description' => sanitize_textarea_field($phase_description),
        );
        $formats = array('%d', '%s', '%s');

        if (!is_null($order_in_stream)) { $data['order_in_stream'] = intval($order_in_stream); $formats[] = '%d'; }
        if (!is_null($phase_type)) { $data['phase_type'] = $phase_type; $formats[] = '%s'; }
        if (!is_null($default_kpi_units)) { $data['default_kpi_units'] = $default_kpi_units; $formats[] = '%s'; }
        if (!is_null($is_active)) { $data['is_active'] = intval($is_active); $formats[] = '%d'; }

        $result = $wpdb->update(self::$phases_table, $data, array( 'phase_id' => $phase_id ), $formats, array( '%d' ));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update phase. Error: ' . $wpdb->last_error);
            oo_log('Error updating phase: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Phase updated successfully. ID: ' . $phase_id, __METHOD__);
        return true;
    }

    public static function toggle_phase_status( $phase_id, $is_active ) {
        oo_log('Toggling phase status for ID: ' . $phase_id . ' to ' . $is_active, __METHOD__);
        self::init(); global $wpdb;
        $result = $wpdb->update(self::$phases_table, array( 'is_active' => intval($is_active) ), array( 'phase_id' => $phase_id ), array( '%d' ), array( '%d' ));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update phase status. Error: ' . $wpdb->last_error);
            oo_log('Error toggling phase status: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Phase status toggled successfully for ID: ' . $phase_id, __METHOD__);
        return true;
    }

    public static function get_phases( $args = array() ) { 
        oo_log('Attempting to get phases with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('stream_id' => null, 'is_active' => null, 'orderby' => 'order_in_stream', 'order' => 'ASC', 'search' => '', 'number' => -1, 'offset' => 0);
        $args = wp_parse_args($args, $defaults);
        $sql_base = "SELECT * FROM " . self::$phases_table;
        $where_clauses = array(); $query_params = array();
        if ( !empty($args['stream_id']) ) { $where_clauses[] = "stream_id = %d"; $query_params[] = intval($args['stream_id']);}
        if ( !is_null($args['is_active']) ) { $where_clauses[] = "is_active = %d"; $query_params[] = intval($args['is_active']); }
        if ( !empty($args['search']) ) { $search_term = '%' . $wpdb->esc_like($args['search']) . '%'; $where_clauses[] = "(phase_name LIKE %s OR phase_description LIKE %s OR phase_type LIKE %s)"; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;}
        $sql_where = ""; if ( !empty($where_clauses) ) { $sql_where = " WHERE " . implode(" AND ", $where_clauses);}
        $sql = $sql_base . $sql_where;
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $orderby_clause = ""; if (!empty($args['orderby'])) { $orderby_val = sanitize_sql_orderby($args['orderby']); if ($orderby_val) { $order_val = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC'; $orderby_clause = " ORDER BY $orderby_val $order_val"; }}
        $sql .= $orderby_clause;
        $limit_clause = ""; if ( isset($args['number']) && $args['number'] > 0 ) { $limit_clause = sprintf(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));}
        $sql .= $limit_clause;
        $results = $wpdb->get_results( $sql );
        oo_log('Get phases query executed. SQL: ' . $sql . ' Number of results: ' . count($results), __METHOD__);
        return $results;
    }
    
    public static function get_phases_count( $args = array() ) {
        oo_log('Attempting to get phases count with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('stream_id' => null, 'is_active' => null, 'search' => ''); $args = wp_parse_args($args, $defaults);
        $sql = "SELECT COUNT(*) FROM " . self::$phases_table;
        $where_clauses = array(); $query_params = array();
        if ( !empty($args['stream_id']) ) { $where_clauses[] = "stream_id = %d"; $query_params[] = intval($args['stream_id']);}
        if ( !is_null($args['is_active']) ) { $where_clauses[] = "is_active = %d"; $query_params[] = intval($args['is_active']); }
        if ( !empty($args['search']) ) { $search_term = '%' . $wpdb->esc_like($args['search']) . '%'; $where_clauses[] = "(phase_name LIKE %s OR phase_description LIKE %s OR phase_type LIKE %s)"; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;}
        if ( !empty($where_clauses) ) { $sql .= " WHERE " . implode(" AND ", $where_clauses);}
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $count = $wpdb->get_var( $sql );
        oo_log('Phases count result: ' . $count . ' SQL: ' . $sql, __METHOD__);
        return $count;
    }

    // --- Job Log CRUD Methods ---
    public static function start_job_phase( $employee_id, $job_id, $stream_id, $phase_id, $notes = '', $kpi_data = null ) {
        oo_log('Attempting to start job phase.', __METHOD__);
        oo_log(compact('employee_id', 'job_id', 'stream_id', 'phase_id', 'notes', 'kpi_data'), __METHOD__);
        self::init(); global $wpdb;
        if (empty($employee_id) || empty($job_id) || empty($stream_id) || empty($phase_id)) {
            $error = new WP_Error('missing_fields', 'Employee, Job, Stream, and Phase are required.');
            oo_log('Error starting job: Missing fields.', $error);
            return $error;
        }
        
        $log_to_check = self::get_open_job_log($employee_id, $job_id, $stream_id, $phase_id);
        if ($log_to_check) {
             $error = new WP_Error('already_started', 'This employee has already started this job phase and it has not been stopped yet. Log ID: ' . $log_to_check->log_id);
            oo_log('Error starting job: Already started.', $error);
            return $error;
        }

        $insert_data = array(
            'employee_id' => intval($employee_id),
            'job_id' => intval($job_id),
            'stream_id' => intval($stream_id),
            'phase_id' => intval($phase_id),
            'start_time' => current_time('mysql', 1),
            'status' => 'started',
            'notes' => sanitize_textarea_field($notes)
        );
        $formats = array('%d', '%d', '%d', '%d', '%s', '%s', '%s');
        
        if (!is_null($kpi_data) && is_array($kpi_data)) {
            if(isset($kpi_data['boxes_completed'])) {
                $insert_data['boxes_completed'] = intval($kpi_data['boxes_completed']);
                $formats[] = '%d'; 
            } else { $insert_data['boxes_completed'] = null; $formats[] = null; }
            if(isset($kpi_data['items_completed'])) {
                $insert_data['items_completed'] = intval($kpi_data['items_completed']);
                $formats[] = '%d';
            } else { $insert_data['items_completed'] = null; $formats[] = null; }
            $insert_data['kpi_data'] = wp_json_encode($kpi_data);
            $formats[] = '%s';
        } else {
            $insert_data['boxes_completed'] = null; $formats[] = null; 
            $insert_data['items_completed'] = null; $formats[] = null; 
            $insert_data['kpi_data'] = null; $formats[] = null; 
        }
         // Clean up null formats that were added if kpi_data was null or individual keys not set
        $formats = array_values(array_filter($formats, function($f) { return !is_null($f); }));

        $result = $wpdb->insert(self::$job_logs_table, $insert_data, $formats);
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not start job phase. Error: ' . $wpdb->last_error);
            oo_log('Error starting job phase: DB insert failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Job phase started successfully. Log ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    public static function stop_job_phase( $employee_id, $job_id, $stream_id, $phase_id, $kpi_data_updates = array(), $notes = '' ) {
        oo_log('Attempting to stop job phase.', __METHOD__);
        oo_log(compact('employee_id', 'job_id', 'stream_id', 'phase_id', 'kpi_data_updates', 'notes'), __METHOD__);
        self::init(); global $wpdb;
        if (empty($employee_id) || empty($job_id) || empty($stream_id) || empty($phase_id)) {
            $error = new WP_Error('missing_fields', 'Employee, Job, Stream, and Phase are required to stop a job.');
            oo_log('Error stopping job: Missing fields.', $error);
            return $error;
        }

        $log_to_update = self::get_open_job_log($employee_id, $job_id, $stream_id, $phase_id);
        if ( !$log_to_update ) {
            $error = new WP_Error('no_start_record', 'No matching \'started\' record found for this employee, job, and phase. Cannot stop.');
            oo_log('Error stopping job: No start record found.', $error);
            return $error;
        }

        $update_data = array(
            'end_time' => current_time('mysql', 1),
            'status'   => 'completed',
        );
        $formats = array('%s', '%s');
        
        $existing_kpi_data = !empty($log_to_update->kpi_data) ? json_decode($log_to_update->kpi_data, true) : array();
        if(!is_array($existing_kpi_data)) $existing_kpi_data = array();
        $final_kpi_data = array_merge($existing_kpi_data, $kpi_data_updates);

        $update_data['kpi_data'] = wp_json_encode($final_kpi_data);
        $formats[] = '%s';

        if (isset($kpi_data_updates['boxes_completed'])) {
            $update_data['boxes_completed'] = intval($kpi_data_updates['boxes_completed']); $formats[] = '%d';
        } elseif (array_key_exists('boxes_completed', $kpi_data_updates) && is_null($kpi_data_updates['boxes_completed'])) {
            $update_data['boxes_completed'] = null; $formats[] = '%s'; // format for NULL should be %s or handled by $wpdb
        } else if (!isset($final_kpi_data['boxes_completed'])) { // If not in new data and not in old, ensure it can be nulled if column allows
             $update_data['boxes_completed'] = null; $formats[] = '%s';
        }

        if (isset($kpi_data_updates['items_completed'])) {
            $update_data['items_completed'] = intval($kpi_data_updates['items_completed']); $formats[] = '%d';
        } elseif (array_key_exists('items_completed', $kpi_data_updates) && is_null($kpi_data_updates['items_completed'])) {
            $update_data['items_completed'] = null; $formats[] = '%s';
        } else if (!isset($final_kpi_data['items_completed'])) {
             $update_data['items_completed'] = null; $formats[] = '%s';
        }

        if (isset($notes)) { // Allow clearing notes if an empty string is explicitly passed
            $update_data['notes'] = sanitize_textarea_field($notes);
            $formats[] = '%s';
        }

        $result = $wpdb->update(self::$job_logs_table, $update_data, array( 'log_id' => $log_to_update->log_id ), $formats, array('%d'));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not stop job phase. Error: ' . $wpdb->last_error);
            oo_log('Error stopping job phase: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Job phase stopped successfully. Log ID: ' . $log_to_update->log_id, __METHOD__);
        return true;
    }

    public static function get_job_log( $log_id ) {
        oo_log('Attempting to get job log by ID: ' . $log_id, __METHOD__);
        self::init(); global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$job_logs_table . " WHERE log_id = %d", $log_id ) );
        oo_log('Result for get_job_log: ', $result);
        return $result;
    }

    public static function get_job_logs( $args = array() ) {
        oo_log('Attempting to get job logs with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('employee_id' => null, 'job_id' => null, 'stream_id' => null, 'date_from' => null, 'date_to' => null, 'status' => null, 'orderby' => 'jl.start_time', 'order' => 'DESC', 'number' => 25, 'offset' => 0, 'search_general' => '');
        $args = wp_parse_args($args, $defaults);
        $sql_select = "SELECT jl.*, e.first_name, e.last_name, e.employee_number, p.phase_name, p.stream_id, st.stream_name, st.stream_description";
        $sql_from = " FROM " . self::$job_logs_table . " jl LEFT JOIN " . self::$employees_table . " e ON jl.employee_id = e.employee_id LEFT JOIN " . self::$phases_table . " p ON jl.phase_id = p.phase_id LEFT JOIN " . self::$streams_table . " st ON jl.stream_id = st.stream_id";
        $sql_base = $sql_select . $sql_from;
        $where_clauses = array(); $query_params = array();
        if ( !empty($args['employee_id']) ) { $where_clauses[] = "jl.employee_id = %d"; $query_params[] = $args['employee_id'];}
        if ( !empty($args['job_id']) ) { $where_clauses[] = "jl.job_id = %d"; $query_params[] = $args['job_id'];}
        if ( !empty($args['stream_id']) ) { $where_clauses[] = "jl.stream_id = %d"; $query_params[] = $args['stream_id'];}
        if ( !empty($args['date_from']) ) { $where_clauses[] = "jl.start_time >= %s"; $query_params[] = sanitize_text_field($args['date_from']) . ' 00:00:00';}
        if ( !empty($args['date_to']) ) { $date_to_end_of_day = sanitize_text_field($args['date_to']) . ' 23:59:59'; if (!empty($args['date_from'])) { $date_from_start_of_day = sanitize_text_field($args['date_from']) . ' 00:00:00'; $where_clauses[] = "( (jl.start_time <= %s AND (jl.end_time IS NULL OR jl.end_time >= %s)) OR (jl.start_time >= %s AND jl.start_time <= %s) )"; $query_params[] = $date_to_end_of_day; $query_params[] = $date_from_start_of_day; $query_params[] = $date_from_start_of_day; $query_params[] = $date_to_end_of_day; } else { $where_clauses[] = "jl.start_time <= %s"; $query_params[] = $date_to_end_of_day; }}
        if ( !empty($args['status']) ) { $where_clauses[] = "jl.status = %s"; $query_params[] = sanitize_text_field($args['status']);}
        if ( !empty($args['search_general']) ) { $search_term = '%' . $wpdb->esc_like(sanitize_text_field($args['search_general'])) . '%'; $where_clauses[] = "(jl.notes LIKE %s OR e.first_name LIKE %s OR e.last_name LIKE %s OR e.employee_number LIKE %s OR st.stream_name LIKE %s)"; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;}
        $sql_where = ""; if ( !empty($where_clauses) ) { $sql_where = " WHERE " . implode(" AND ", $where_clauses);}
        $sql = $sql_base . $sql_where;
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $orderby_clause = ""; if (!empty($args['orderby'])) { $allowed_orderby = array('jl.start_time', 'jl.end_time', 'e.last_name', 'e.first_name', 'e.employee_number', 'jl.job_id', 'p.phase_name', 'st.stream_name', 'jl.status', 'jl.boxes_completed', 'jl.items_completed'); $orderby_input = $args['orderby']; $order_val = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC'; if ($orderby_input === 'e.last_name' || $orderby_input === 'e.first_name') { $orderby_clause = " ORDER BY e.last_name $order_val, e.first_name $order_val"; } elseif (in_array($orderby_input, $allowed_orderby)) { $orderby_clause = " ORDER BY $orderby_input $order_val"; } else { $orderby_clause = " ORDER BY jl.start_time $order_val"; }}
        $sql .= $orderby_clause;
        $limit_clause = ""; if ( isset($args['number']) && $args['number'] > 0 ) { $limit_clause = sprintf(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));}
        $sql .= $limit_clause;
        $results = $wpdb->get_results( $sql );
        oo_log('Get job_logs query executed. SQL: ' . $sql . ' Number of results: ' . count($results), __METHOD__);
        return $results;
    }

    public static function get_job_logs_count( $args = array() ) {
        oo_log('Attempting to get job logs count with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('employee_id' => null, 'job_id' => null, 'stream_id' => null, 'date_from' => null, 'date_to' => null, 'status' => null, 'search_general' => '');
        $args = wp_parse_args($args, $defaults);
        $sql_select = "SELECT COUNT(jl.log_id)";
        $sql_from = " FROM " . self::$job_logs_table . " jl LEFT JOIN " . self::$employees_table . " e ON jl.employee_id = e.employee_id LEFT JOIN " . self::$phases_table . " p ON jl.phase_id = p.phase_id LEFT JOIN " . self::$streams_table . " st ON jl.stream_id = st.stream_id";
        $sql_base = $sql_select . $sql_from;
        $where_clauses = array(); $query_params = array();
        if ( !empty($args['employee_id']) ) { $where_clauses[] = "jl.employee_id = %d"; $query_params[] = $args['employee_id'];}
        if ( !empty($args['job_id']) ) { $where_clauses[] = "jl.job_id = %d"; $query_params[] = $args['job_id'];}
        if ( !empty($args['stream_id']) ) { $where_clauses[] = "jl.stream_id = %d"; $query_params[] = $args['stream_id'];}
        if ( !empty($args['date_from']) ) { $where_clauses[] = "jl.start_time >= %s"; $query_params[] = sanitize_text_field($args['date_from']) . ' 00:00:00';}
        if ( !empty($args['date_to']) ) { $date_to_end_of_day = sanitize_text_field($args['date_to']) . ' 23:59:59'; if (!empty($args['date_from'])) { $date_from_start_of_day = sanitize_text_field($args['date_from']) . ' 00:00:00'; $where_clauses[] = "( (jl.start_time <= %s AND (jl.end_time IS NULL OR jl.end_time >= %s)) OR (jl.start_time >= %s AND jl.start_time <= %s) )"; $query_params[] = $date_to_end_of_day; $query_params[] = $date_from_start_of_day; $query_params[] = $date_from_start_of_day; $query_params[] = $date_to_end_of_day; } else { $where_clauses[] = "jl.start_time <= %s"; $query_params[] = $date_to_end_of_day; }}
        if ( !empty($args['status']) ) { $where_clauses[] = "jl.status = %s"; $query_params[] = sanitize_text_field($args['status']);}
        if ( !empty($args['search_general']) ) { $search_term = '%' . $wpdb->esc_like(sanitize_text_field($args['search_general'])) . '%'; $where_clauses[] = "(jl.notes LIKE %s OR e.first_name LIKE %s OR e.last_name LIKE %s OR e.employee_number LIKE %s OR st.stream_name LIKE %s)"; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;}
        $sql_where = ""; if ( !empty($where_clauses) ) { $sql_where = " WHERE " . implode(" AND ", $where_clauses);}
        $sql = $sql_base . $sql_where;
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $count = $wpdb->get_var( $sql );
        oo_log('Job logs count result: ' . $count . ' SQL: ' . $sql, __METHOD__);
        return $count;
    }
    
    public static function get_open_job_log($employee_id, $job_id, $stream_id, $phase_id) { 
        oo_log('Attempting to get open job log.', __METHOD__);
        oo_log(compact('employee_id', 'job_id', 'stream_id', 'phase_id'), __METHOD__);
        self::init(); global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::$job_logs_table . " WHERE employee_id = %d AND job_id = %d AND stream_id = %d AND phase_id = %d AND status = 'started' ORDER BY start_time DESC LIMIT 1", $employee_id, $job_id, $stream_id, $phase_id));
        oo_log('Result for get_open_job_log: ', $result);
        return $result;
    }
    
    public static function update_job_log($log_id, $data) {
        oo_log('Attempting to update job log ID: ' . $log_id, __METHOD__);
        oo_log('Raw data received for update:', $data);
        self::init(); global $wpdb;
        $update_data = array(); $update_formats = array();
        if (isset($data['employee_id'])) { $update_data['employee_id'] = intval($data['employee_id']); $update_formats[] = '%d'; }
        if (isset($data['job_id'])) { $update_data['job_id'] = intval($data['job_id']); $update_formats[] = '%d'; }
        if (isset($data['stream_id'])) { $update_data['stream_id'] = intval($data['stream_id']); $update_formats[] = '%d'; }
        if (isset($data['phase_id'])) { $update_data['phase_id'] = intval($data['phase_id']); $update_formats[] = '%d'; }
        if (isset($data['start_time'])) { $update_data['start_time'] = sanitize_text_field($data['start_time']); $update_formats[] = '%s'; }
        if (array_key_exists('end_time', $data)) { $update_data['end_time'] = is_null($data['end_time']) ? null : sanitize_text_field($data['end_time']); $update_formats[] = '%s'; }
        
        if (isset($data['kpi_data'])) { 
            $update_data['kpi_data'] = is_string($data['kpi_data']) ? $data['kpi_data'] : wp_json_encode($data['kpi_data']); 
            $update_formats[] = '%s';
        }
        // Legacy/direct fields (can be phased out if kpi_data is fully adopted)
        if (array_key_exists('boxes_completed', $data)) { 
            $update_data['boxes_completed'] = is_null($data['boxes_completed']) ? null : intval($data['boxes_completed']); 
            $update_formats[] = '%d'; 
        }
        if (array_key_exists('items_completed', $data)) { 
            $update_data['items_completed'] = is_null($data['items_completed']) ? null : intval($data['items_completed']); 
            $update_formats[] = '%d'; 
        }

        if (isset($data['status'])) { $update_data['status'] = sanitize_text_field($data['status']); $update_formats[] = '%s'; }
        if (isset($data['notes'])) { $update_data['notes'] = sanitize_textarea_field($data['notes']); $update_formats[] = '%s'; }

        if (empty($update_data)) {
            $error = new WP_Error('no_data_to_update', 'No valid data fields provided for update.');
            oo_log('Error updating job log: No data fields to update after sanitization.', $error);
            return $error;
        }
        oo_log('Sanitized data for $wpdb->update:', $update_data);
        oo_log('Formats for $wpdb->update:', $update_formats);
        $result = $wpdb->update(self::$job_logs_table, $update_data, array('log_id' => $log_id), $update_formats, array('%d'));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update job log. WPDB Error: ' . $wpdb->last_error);
            oo_log('Error updating job log: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Job log update result (rows affected or 0 if existing data matched): ' . $result . '. For Log ID: ' . $log_id, __METHOD__);
        return true;
    }

    public static function delete_job_log( $log_id ) {
        self::init(); global $wpdb;
        oo_log('Attempting to delete job log ID: ' . $log_id, __METHOD__);
        if (empty($log_id) || !is_numeric($log_id) || intval($log_id) <= 0) {
            $error = new WP_Error('invalid_id', 'Invalid Log ID provided for deletion.');
            oo_log('Error deleting job log: ' . $error->get_error_message(), __METHOD__);
            return $error;
        }
        $result = $wpdb->delete(self::$job_logs_table, array( 'log_id' => intval($log_id) ), array( '%d' ));
        if ($result === false) {
            $error = new WP_Error('db_delete_error', 'Could not delete job log. WPDB Error: ' . $wpdb->last_error);
            oo_log('Error deleting job log: DB delete failed. ' . $wpdb->last_error, $error);
            return $error;
        } elseif ($result === 0) {
            oo_log('No job log found with ID: ' . $log_id . ' to delete. Or no rows affected.', __METHOD__);
            return true; 
        }
        oo_log('Job log deleted successfully. ID: ' . $log_id . ', Rows affected: ' . $result, __METHOD__);
        return true;
    }

    // --- Job CRUD Methods (NEW) ---
    /**
     * Add a new job.
     * @param array $args Associative array of job data (job_number, client_name, etc.)
     * @return int|WP_Error The new job_id on success, or WP_Error on failure.
     */
    public static function add_job( $args ) {
        self::init(); global $wpdb;
        oo_log('Attempting to add job with args:', $args);

        if ( empty( $args['job_number'] ) ) {
            return new WP_Error('missing_job_number', 'Job Number is required.');
        }

        // Check if job_number already exists
        $existing_job = $wpdb->get_var( $wpdb->prepare(
            "SELECT job_id FROM " . self::$jobs_table . " WHERE job_number = %s",
            sanitize_text_field( $args['job_number'] )
        ) );
        if ( $existing_job ) {
            return new WP_Error('job_number_exists', 'This Job Number already exists.');
        }

        $data = array(
            'job_number' => sanitize_text_field( $args['job_number'] ),
            'client_name' => isset($args['client_name']) ? sanitize_text_field( $args['client_name'] ) : null,
            'client_contact' => isset($args['client_contact']) ? sanitize_textarea_field( $args['client_contact'] ) : null,
            'start_date' => isset($args['start_date']) ? oo_sanitize_date( $args['start_date'] ) : null,
            'due_date' => isset($args['due_date']) ? oo_sanitize_date( $args['due_date'] ) : null,
            'overall_status' => isset($args['overall_status']) ? sanitize_text_field( $args['overall_status'] ) : 'Pending',
            'notes' => isset($args['notes']) ? sanitize_textarea_field( $args['notes'] ) : null,
            'created_at' => current_time('mysql', 1),
            'updated_at' => current_time('mysql', 1)
        );

        $formats = array(
            '%s', // job_number
            '%s', // client_name
            '%s', // client_contact
            '%s', // start_date
            '%s', // due_date
            '%s', // overall_status
            '%s', // notes
            '%s', // created_at
            '%s'  // updated_at
        );
        
        // Remove null values and their formats to allow DB defaults
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                unset($data[$key]);
                // Find the corresponding format and unset it. This is a bit tricky due to array re-indexing if not careful.
                // For simplicity, we will rely on $wpdb to handle nulls appropriately if the format is still %s.
                // Or, ensure formats array is built in parallel to non-null data keys.
            }
        }

        $result = $wpdb->insert( self::$jobs_table, $data, $formats );

        if ( $result === false ) {
            oo_log('Error adding job: ' . $wpdb->last_error, $data);
            return new WP_Error('db_insert_error', 'Could not add job: ' . $wpdb->last_error);
        }
        oo_log('Job added successfully. ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    /**
     * Get a specific job by its ID.
     * @param int $job_id
     * @return object|null Job object or null if not found.
     */
    public static function get_job( $job_id ) {
        self::init(); global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$jobs_table . " WHERE job_id = %d", $job_id ) );
    }

    /**
     * Get a specific job by its number.
     * @param string $job_number
     * @return object|null Job object or null if not found.
     */
    public static function get_job_by_number( $job_number ) {
        self::init(); global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$jobs_table . " WHERE job_number = %s", sanitize_text_field($job_number) ) );
    }

    /**
     * Update an existing job.
     * @param int $job_id
     * @param array $args Associative array of data to update.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function update_job( $job_id, $args ) {
        self::init(); global $wpdb;
        oo_log('Attempting to update job ID: ' . $job_id . ' with args:', $args);

        $job_id = intval($job_id);
        if ( $job_id <= 0 ) {
            return new WP_Error('invalid_job_id', 'Invalid Job ID provided for update.');
        }

        $data = array();
        $formats = array();

        if ( isset( $args['job_number'] ) ) {
            $new_job_number = sanitize_text_field($args['job_number']);
            // Check if new job_number conflicts with another existing job
            $existing_job = $wpdb->get_var( $wpdb->prepare(
                "SELECT job_id FROM " . self::$jobs_table . " WHERE job_number = %s AND job_id != %d",
                $new_job_number, $job_id
            ) );
            if ( $existing_job ) {
                return new WP_Error('job_number_exists', 'This Job Number is already assigned to another job.');
            }
            $data['job_number'] = $new_job_number;
            $formats[] = '%s';
        }
        if ( array_key_exists('client_name', $args) ) { $data['client_name'] = sanitize_text_field( $args['client_name'] ); $formats[] = '%s'; }
        if ( array_key_exists('client_contact', $args) ) { $data['client_contact'] = sanitize_textarea_field( $args['client_contact'] ); $formats[] = '%s'; }
        if ( array_key_exists('start_date', $args) ) { $data['start_date'] = oo_sanitize_date( $args['start_date'] ); $formats[] = '%s'; }
        if ( array_key_exists('due_date', $args) ) { $data['due_date'] = oo_sanitize_date( $args['due_date'] ); $formats[] = '%s'; }
        if ( isset( $args['overall_status'] ) ) { $data['overall_status'] = sanitize_text_field( $args['overall_status'] ); $formats[] = '%s'; }
        if ( array_key_exists('notes', $args) ) { $data['notes'] = sanitize_textarea_field( $args['notes'] ); $formats[] = '%s'; }

        if ( empty($data) ) {
            return new WP_Error('no_data_to_update', 'No data provided to update.');
        }

        $data['updated_at'] = current_time('mysql', 1);
        $formats[] = '%s';

        $result = $wpdb->update( self::$jobs_table, $data, array( 'job_id' => $job_id ), $formats, array('%d') );

        if ( $result === false ) {
            oo_log('Error updating job ID ' . $job_id . ': ' . $wpdb->last_error, $data);
            return new WP_Error('db_update_error', 'Could not update job: ' . $wpdb->last_error);
        }
        oo_log('Job updated successfully. ID: ' . $job_id, __METHOD__);
        return true;
    }

    /**
     * Delete a job.
     * @param int $job_id
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function delete_job( $job_id ) {
        self::init(); global $wpdb;
        $job_id = intval($job_id);
        if ( $job_id <= 0 ) {
            return new WP_Error('invalid_job_id', 'Invalid Job ID for deletion.');
        }

        // Optional: Check for related job_streams and handle them (e.g., prevent deletion or cascade)
        // For now, the DB schema uses ON DELETE CASCADE for job_streams related to jobs.
        // Expenses related to jobs also use ON DELETE CASCADE.
        // Job logs related to jobs also use ON DELETE CASCADE.

        $result = $wpdb->delete( self::$jobs_table, array( 'job_id' => $job_id ), array('%d') );

        if ( $result === false ) {
            oo_log('Error deleting job ID ' . $job_id . ': ' . $wpdb->last_error, __METHOD__);
            return new WP_Error('db_delete_error', 'Could not delete job: ' . $wpdb->last_error);
        }
        if ( $result === 0 ) {
            return new WP_Error('job_not_found', 'Job not found for deletion.');
        }
        oo_log('Job deleted successfully. ID: ' . $job_id, __METHOD__);
        return true;
    }

    /**
     * Get multiple jobs with filtering, sorting, and pagination.
     * @param array $params Parameters for filtering, orderby, order, number, offset, search.
     * @return array Array of job objects.
     */
    public static function get_jobs( $params = array() ) {
        self::init(); global $wpdb;

        $defaults = array(
            'job_number' => null,
            'client_name_like' => null,
            'overall_status' => null,
            'date_field' => null, // e.g., 'start_date', 'due_date', 'created_at'
            'date_from' => null,
            'date_to' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'number' => 20,
            'offset' => 0,
            'search_general' => null // General search across job_number, client_name, notes
        );
        $args = wp_parse_args( $params, $defaults );

        $sql = "SELECT * FROM " . self::$jobs_table;
        $where_clauses = array();
        $query_params = array();

        if ( !empty($args['job_number']) ) { $where_clauses[] = "job_number = %s"; $query_params[] = sanitize_text_field($args['job_number']); }
        if ( !empty($args['client_name_like']) ) { $where_clauses[] = "client_name LIKE %s"; $query_params[] = '%' . $wpdb->esc_like($args['client_name_like']) . '%'; }
        if ( !empty($args['overall_status']) ) { $where_clauses[] = "overall_status = %s"; $query_params[] = sanitize_text_field($args['overall_status']); }
        
        if ( !empty($args['date_field']) && in_array($args['date_field'], ['start_date', 'due_date', 'created_at', 'updated_at'])) {
            if ( !empty($args['date_from']) ) { $where_clauses[] = $args['date_field'] . " >= %s"; $query_params[] = oo_sanitize_date($args['date_from']); }
            if ( !empty($args['date_to']) ) { $where_clauses[] = $args['date_field'] . " <= %s"; $query_params[] = oo_sanitize_date($args['date_to']); }
        }

        if ( !empty($args['search_general']) ) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($args['search_general'])) . '%';
            $search_fields = array("job_number LIKE %s", "client_name LIKE %s", "notes LIKE %s");
            $where_clauses[] = "(" . implode(" OR ", $search_fields) . ")";
            $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;
        }

        if ( !empty($where_clauses) ) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        if ( !empty($query_params) ) {
            $sql = $wpdb->prepare($sql, $query_params);
        }

        $allowed_orderby = ['job_id', 'job_number', 'client_name', 'start_date', 'due_date', 'overall_status', 'created_at', 'updated_at'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY $orderby $order";

        if ( $args['number'] > 0 ) {
            $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['number'], $args['offset']);
        }

        return $wpdb->get_results( $sql );
    }

    /**
     * Get the count of jobs based on filters.
     * @param array $params Parameters for filtering (same as get_jobs, excluding pagination/order).
     * @return int Count of jobs.
     */
    public static function get_jobs_count( $params = array() ) {
        self::init(); global $wpdb;

        $defaults = array(
            'job_number' => null,
            'client_name_like' => null,
            'overall_status' => null,
            'date_field' => null,
            'date_from' => null,
            'date_to' => null,
            'search_general' => null
        );
        $args = wp_parse_args( $params, $defaults );

        $sql = "SELECT COUNT(*) FROM " . self::$jobs_table;
        $where_clauses = array();
        $query_params = array();

        // Build WHERE clauses and query_params identical to get_jobs()
        if ( !empty($args['job_number']) ) { $where_clauses[] = "job_number = %s"; $query_params[] = sanitize_text_field($args['job_number']); }
        if ( !empty($args['client_name_like']) ) { $where_clauses[] = "client_name LIKE %s"; $query_params[] = '%' . $wpdb->esc_like($args['client_name_like']) . '%'; }
        if ( !empty($args['overall_status']) ) { $where_clauses[] = "overall_status = %s"; $query_params[] = sanitize_text_field($args['overall_status']); }
        if ( !empty($args['date_field']) && in_array($args['date_field'], ['start_date', 'due_date', 'created_at', 'updated_at'])) {
            if ( !empty($args['date_from']) ) { $where_clauses[] = $args['date_field'] . " >= %s"; $query_params[] = oo_sanitize_date($args['date_from']); }
            if ( !empty($args['date_to']) ) { $where_clauses[] = $args['date_field'] . " <= %s"; $query_params[] = oo_sanitize_date($args['date_to']); }
        }
        if ( !empty($args['search_general']) ) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($args['search_general'])) . '%';
            $search_fields = array("job_number LIKE %s", "client_name LIKE %s", "notes LIKE %s");
            $where_clauses[] = "(" . implode(" OR ", $search_fields) . ")";
            $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;
        }

        if ( !empty($where_clauses) ) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        if ( !empty($query_params) ) {
            $sql = $wpdb->prepare($sql, $query_params);
        }

        return (int) $wpdb->get_var( $sql );
    }

    // --- JobStream CRUD Methods (NEW) ---
    // Placeholder for add_job_stream, get_job_stream, update_job_stream, get_job_streams_for_job etc.

    /**
     * Add a new job stream.
     * Links a job to a specific stream with additional details.
     *
     * @param array $args Associative array of job stream data. Required: job_id, stream_id.
     *                    Optional: status_in_stream, assigned_manager_id, start_date_stream,
     *                              due_date_stream, building_id, notes.
     * @return int|WP_Error The new job_stream_id on success, or WP_Error on failure.
     */
    public static function add_job_stream( $args ) {
        self::init(); global $wpdb;
        oo_log('Attempting to add job_stream with args:', $args);

        if ( empty( $args['job_id'] ) || empty( $args['stream_id'] ) ) {
            return new WP_Error('missing_required_fields', 'Job ID and Stream ID are required to add a job stream.');
        }

        // Check for uniqueness: A job can only have a specific stream type once.
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT job_stream_id FROM " . self::$job_streams_table . " WHERE job_id = %d AND stream_id = %d",
            intval( $args['job_id'] ),
            intval( $args['stream_id'] )
        ) );
        if ( $existing ) {
            return new WP_Error('job_stream_exists', 'This stream is already associated with this job.');
        }

        $data = array(
            'job_id' => intval( $args['job_id'] ),
            'stream_id' => intval( $args['stream_id'] ),
            'status_in_stream' => isset($args['status_in_stream']) ? sanitize_text_field( $args['status_in_stream'] ) : 'Not Started',
            'assigned_manager_id' => isset($args['assigned_manager_id']) ? intval( $args['assigned_manager_id'] ) : null,
            'start_date_stream' => isset($args['start_date_stream']) ? oo_sanitize_date( $args['start_date_stream'] ) : null,
            'due_date_stream' => isset($args['due_date_stream']) ? oo_sanitize_date( $args['due_date_stream'] ) : null,
            'building_id' => isset($args['building_id']) ? intval( $args['building_id'] ) : null,
            'notes' => isset($args['notes']) ? sanitize_textarea_field( $args['notes'] ) : null,
            'created_at' => current_time('mysql', 1),
            'updated_at' => current_time('mysql', 1)
        );

        $formats = array(
            '%d', // job_id
            '%d', // stream_id
            '%s', // status_in_stream
            '%d', // assigned_manager_id (use %d, will be NULL if value is null)
            '%s', // start_date_stream
            '%s', // due_date_stream
            '%d', // building_id (use %d, will be NULL if value is null)
            '%s', // notes
            '%s', // created_at
            '%s'  // updated_at
        );
        
        // Remove null values and their formats to allow DB defaults, except for explicit nulls.
        // $wpdb->insert handles null values correctly for columns that allow NULL.
        // No need to manually unset nulls here if formats match column types correctly.

        $result = $wpdb->insert( self::$job_streams_table, $data, $formats );

        if ( $result === false ) {
            oo_log('Error adding job_stream: ' . $wpdb->last_error, array('data' => $data, 'formats' => $formats));
            return new WP_Error('db_insert_error', 'Could not add job stream: ' . $wpdb->last_error);
        }
        oo_log('Job stream added successfully. ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    /**
     * Get a specific job stream by its ID.
     * @param int $job_stream_id
     * @return object|null Job stream object or null if not found.
     */
    public static function get_job_stream( $job_stream_id ) {
        self::init(); global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$job_streams_table . " WHERE job_stream_id = %d", intval($job_stream_id) ) );
    }

    /**
     * Get a specific job stream by job_id and stream_id.
     * @param int $job_id
     * @param int $stream_id
     * @return object|null Job stream object or null if not found.
     */
    public static function get_job_stream_by_job_and_stream( $job_id, $stream_id ) {
        self::init(); global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::$job_streams_table . " WHERE job_id = %d AND stream_id = %d",
            intval($job_id), intval($stream_id)
        ) );
    }

    /**
     * Update an existing job stream.
     * @param int $job_stream_id
     * @param array $args Associative array of data to update.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function update_job_stream( $job_stream_id, $args ) {
        self::init(); global $wpdb;
        oo_log('Attempting to update job_stream ID: ' . $job_stream_id . ' with args:', $args);

        $job_stream_id = intval($job_stream_id);
        if ( $job_stream_id <= 0 ) {
            return new WP_Error('invalid_job_stream_id', 'Invalid Job Stream ID provided for update.');
        }

        $data = array();
        $formats = array();

        // Note: job_id and stream_id typically shouldn't be updated as they define the unique link.
        // If they need to change, it might be conceptually a delete and re-add.
        // However, if an update is allowed for some reason, ensure uniqueness.
        if ( isset( $args['job_id'] ) || isset( $args['stream_id'] ) ) {
            $current_js = self::get_job_stream($job_stream_id);
            if (!$current_js) {
                return new WP_Error('job_stream_not_found', 'Job stream to update not found.');
            }
            $new_job_id = isset( $args['job_id'] ) ? intval($args['job_id']) : $current_js->job_id;
            $new_stream_id = isset( $args['stream_id'] ) ? intval($args['stream_id']) : $current_js->stream_id;

            if ($new_job_id !== $current_js->job_id || $new_stream_id !== $current_js->stream_id) {
                 $existing = $wpdb->get_var( $wpdb->prepare(
                    "SELECT job_stream_id FROM " . self::$job_streams_table . " WHERE job_id = %d AND stream_id = %d AND job_stream_id != %d",
                    $new_job_id, $new_stream_id, $job_stream_id
                ) );
                if ( $existing ) {
                    return new WP_Error('job_stream_conflict', 'Updating job_id or stream_id would cause a conflict with an existing job stream.');
                }
            }
            if (isset($args['job_id'])) { $data['job_id'] = $new_job_id; $formats[] = '%d';}
            if (isset($args['stream_id'])) { $data['stream_id'] = $new_stream_id; $formats[] = '%d';}
        }


        if ( array_key_exists('status_in_stream', $args) ) { $data['status_in_stream'] = sanitize_text_field( $args['status_in_stream'] ); $formats[] = '%s'; }
        if ( array_key_exists('assigned_manager_id', $args) ) { $data['assigned_manager_id'] = is_null($args['assigned_manager_id']) ? null : intval( $args['assigned_manager_id'] ); $formats[] = '%d'; }
        if ( array_key_exists('start_date_stream', $args) ) { $data['start_date_stream'] = oo_sanitize_date( $args['start_date_stream'] ); $formats[] = '%s'; }
        if ( array_key_exists('due_date_stream', $args) ) { $data['due_date_stream'] = oo_sanitize_date( $args['due_date_stream'] ); $formats[] = '%s'; }
        if ( array_key_exists('building_id', $args) ) { $data['building_id'] = is_null($args['building_id']) ? null : intval( $args['building_id'] ); $formats[] = '%d'; }
        if ( array_key_exists('notes', $args) ) { $data['notes'] = sanitize_textarea_field( $args['notes'] ); $formats[] = '%s'; }

        if ( empty($data) ) {
            oo_log('No data provided to update for job_stream ID: ' . $job_stream_id, __METHOD__);
            return new WP_Error('no_data_to_update', 'No data provided to update job stream.');
        }

        $data['updated_at'] = current_time('mysql', 1);
        $formats[] = '%s';

        $result = $wpdb->update( self::$job_streams_table, $data, array( 'job_stream_id' => $job_stream_id ), $formats, array('%d') );

        if ( $result === false ) {
            oo_log('Error updating job_stream ID ' . $job_stream_id . ': ' . $wpdb->last_error, array('data' => $data, 'formats' => $formats));
            return new WP_Error('db_update_error', 'Could not update job stream: ' . $wpdb->last_error);
        }
        oo_log('Job stream updated successfully. ID: ' . $job_stream_id, __METHOD__);
        return true;
    }

    /**
     * Delete a job stream.
     * @param int $job_stream_id
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function delete_job_stream( $job_stream_id ) {
        self::init(); global $wpdb;
        $job_stream_id = intval($job_stream_id);
        if ( $job_stream_id <= 0 ) {
            return new WP_Error('invalid_job_stream_id', 'Invalid Job Stream ID for deletion.');
        }

        // Note: Foreign key constraints (e.g., from oo_job_logs if it directly references job_stream_id) should be considered.
        // Currently, oo_job_logs references job_id and stream_id, so direct deletion of job_stream should be okay
        // as long as other tables don't directly FK to job_stream_id with RESTRICT.

        $result = $wpdb->delete( self::$job_streams_table, array( 'job_stream_id' => $job_stream_id ), array('%d') );

        if ( $result === false ) {
            oo_log('Error deleting job_stream ID ' . $job_stream_id . ': ' . $wpdb->last_error, __METHOD__);
            return new WP_Error('db_delete_error', 'Could not delete job stream: ' . $wpdb->last_error);
        }
        if ( $result === 0 ) {
            // This isn't necessarily an error, could mean the record was already deleted.
            oo_log('Job stream not found for deletion or no rows affected. ID: ' . $job_stream_id, __METHOD__);
            return true; // Or return a specific indicator like WP_Error('job_stream_not_found', 'Job stream not found for deletion.')
        }
        oo_log('Job stream deleted successfully. ID: ' . $job_stream_id, __METHOD__);
        return true;
    }

    /**
     * Get multiple job streams with filtering, sorting, and pagination.
     * @param array $params Parameters for filtering, orderby, order, number, offset.
     *                       Filters: job_id, stream_id, status_in_stream, assigned_manager_id, building_id
     * @return array Array of job stream objects.
     */
    public static function get_job_streams( $params = array() ) {
        self::init(); global $wpdb;

        $defaults = array(
            'job_id' => null,
            'stream_id' => null,
            'status_in_stream' => null,
            'assigned_manager_id' => null,
            'building_id' => null,
            'orderby' => 'js.job_stream_id', // Default order by job_stream_id
            'order' => 'ASC',
            'number' => 20,
            'offset' => 0,
            // Consider adding search capabilities if needed
        );
        $args = wp_parse_args( $params, $defaults );

        // Base query with joins for potential future use (e.g., pulling job_number or stream_name directly)
        $sql = "SELECT js.*, j.job_number, s.stream_name
                FROM " . self::$job_streams_table . " js
                LEFT JOIN " . self::$jobs_table . " j ON js.job_id = j.job_id
                LEFT JOIN " . self::$streams_table . " s ON js.stream_id = s.stream_id";

        $where_clauses = array();
        $query_params = array();

        if ( !empty($args['job_id']) ) { $where_clauses[] = "js.job_id = %d"; $query_params[] = intval($args['job_id']); }
        if ( !empty($args['stream_id']) ) { $where_clauses[] = "js.stream_id = %d"; $query_params[] = intval($args['stream_id']); }
        if ( !empty($args['status_in_stream']) ) { $where_clauses[] = "js.status_in_stream = %s"; $query_params[] = sanitize_text_field($args['status_in_stream']); }
        if ( !empty($args['assigned_manager_id']) ) { $where_clauses[] = "js.assigned_manager_id = %d"; $query_params[] = intval($args['assigned_manager_id']); }
        if ( !empty($args['building_id']) ) { $where_clauses[] = "js.building_id = %d"; $query_params[] = intval($args['building_id']); }

        if ( !empty($where_clauses) ) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        if ( !empty($query_params) ) {
            $sql = $wpdb->prepare($sql, $query_params);
        }

        $allowed_orderby = ['js.job_stream_id', 'js.job_id', 'js.stream_id', 'js.status_in_stream', 'js.start_date_stream', 'js.due_date_stream', 'j.job_number', 's.stream_name', 'js.created_at'];
        // Sanitize orderby to prevent SQL injection if it's dynamic. Here, we use a whitelist.
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'js.job_stream_id';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC'; // Sanitize order
        $sql .= " ORDER BY $orderby $order";

        if ( $args['number'] > 0 ) {
            $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));
        }
        
        oo_log('Executing get_job_streams query: ' . $sql, __METHOD__);
        return $wpdb->get_results( $sql );
    }

    /**
     * Get the count of job streams based on filters.
     * @param array $params Parameters for filtering (same as get_job_streams, excluding pagination/order).
     * @return int Count of job streams.
     */
    public static function get_job_streams_count( $params = array() ) {
        self::init(); global $wpdb;

        $defaults = array(
            'job_id' => null,
            'stream_id' => null,
            'status_in_stream' => null,
            'assigned_manager_id' => null,
            'building_id' => null,
        );
        $args = wp_parse_args( $params, $defaults );

        $sql = "SELECT COUNT(js.job_stream_id) 
                FROM " . self::$job_streams_table . " js
                LEFT JOIN " . self::$jobs_table . " j ON js.job_id = j.job_id
                LEFT JOIN " . self::$streams_table . " s ON js.stream_id = s.stream_id";
                
        $where_clauses = array();
        $query_params = array();

        if ( !empty($args['job_id']) ) { $where_clauses[] = "js.job_id = %d"; $query_params[] = intval($args['job_id']); }
        if ( !empty($args['stream_id']) ) { $where_clauses[] = "js.stream_id = %d"; $query_params[] = intval($args['stream_id']); }
        if ( !empty($args['status_in_stream']) ) { $where_clauses[] = "js.status_in_stream = %s"; $query_params[] = sanitize_text_field($args['status_in_stream']); }
        if ( !empty($args['assigned_manager_id']) ) { $where_clauses[] = "js.assigned_manager_id = %d"; $query_params[] = intval($args['assigned_manager_id']); }
        if ( !empty($args['building_id']) ) { $where_clauses[] = "js.building_id = %d"; $query_params[] = intval($args['building_id']); }


        if ( !empty($where_clauses) ) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        if ( !empty($query_params) ) {
            $sql = $wpdb->prepare($sql, $query_params);
        }
        oo_log('Executing get_job_streams_count query: ' . $sql, __METHOD__);
        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Get all job streams associated with a specific job.
     * Convenience wrapper for get_job_streams.
     *
     * @param int $job_id The ID of the job.
     * @param array $extra_args Additional arguments for get_job_streams (e.g., orderby, order).
     * @return array Array of job stream objects.
     */
    public static function get_job_streams_for_job( $job_id, $extra_args = array() ) {
        if ( empty($job_id) || intval($job_id) <= 0 ) {
            return array(); // Or WP_Error
        }
        $args = array_merge( $extra_args, array('job_id' => intval($job_id), 'number' => -1 ) ); // number -1 to get all
        return self::get_job_streams( $args );
    }

    /**
     * Get all job streams associated with a specific stream type.
     * Convenience wrapper for get_job_streams.
     *
     * @param int $stream_id The ID of the stream.
     * @param array $extra_args Additional arguments for get_job_streams.
     * @return array Array of job stream objects.
     */
    public static function get_job_streams_by_stream( $stream_id, $extra_args = array() ) {
        if ( empty($stream_id) || intval($stream_id) <= 0 ) {
            return array(); // Or WP_Error
        }
        $args = array_merge( $extra_args, array('stream_id' => intval($stream_id), 'number' => -1 ) );
        return self::get_job_streams( $args );
    }


    // --- Building CRUD Methods (NEW) ---
    // Placeholder for add_building, get_building, update_building, get_buildings etc.

    /**
     * Add a new building.
     *
     * @param array $args Associative array of building data.
     *                    Required: building_name.
     *                    Optional: address, storage_capacity_notes, primary_contact_id, is_active.
     * @return int|WP_Error The new building_id on success, or WP_Error on failure.
     */
    public static function add_building( $args ) {
        self::init(); global $wpdb;
        oo_log('Attempting to add building with args:', $args);

        if ( empty( $args['building_name'] ) ) {
            return new WP_Error('missing_building_name', 'Building Name is required.');
        }

        // Check if building_name already exists
        $existing_building = $wpdb->get_var( $wpdb->prepare(
            "SELECT building_id FROM " . self::$buildings_table . " WHERE building_name = %s",
            sanitize_text_field( $args['building_name'] )
        ) );
        if ( $existing_building ) {
            return new WP_Error('building_name_exists', 'This Building Name already exists.');
        }

        $data = array(
            'building_name' => sanitize_text_field( $args['building_name'] ),
            'address' => isset($args['address']) ? sanitize_textarea_field( $args['address'] ) : null,
            'storage_capacity_notes' => isset($args['storage_capacity_notes']) ? sanitize_textarea_field( $args['storage_capacity_notes'] ) : null,
            'primary_contact_id' => isset($args['primary_contact_id']) ? intval( $args['primary_contact_id'] ) : null,
            'is_active' => isset($args['is_active']) ? intval( $args['is_active'] ) : 1,
            'created_at' => current_time('mysql', 1),
            'updated_at' => current_time('mysql', 1)
        );

        $formats = array(
            '%s', // building_name
            '%s', // address
            '%s', // storage_capacity_notes
            '%d', // primary_contact_id
            '%d', // is_active
            '%s', // created_at
            '%s'  // updated_at
        );

        $result = $wpdb->insert( self::$buildings_table, $data, $formats );

        if ( $result === false ) {
            oo_log('Error adding building: ' . $wpdb->last_error, array('data' => $data));
            return new WP_Error('db_insert_error', 'Could not add building: ' . $wpdb->last_error);
        }
        oo_log('Building added successfully. ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    /**
     * Get a specific building by its ID.
     * @param int $building_id
     * @return object|null Building object or null if not found.
     */
    public static function get_building( $building_id ) {
        self::init(); global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$buildings_table . " WHERE building_id = %d", intval($building_id) ) );
    }

    /**
     * Get a specific building by its name.
     * @param string $building_name
     * @return object|null Building object or null if not found.
     */
    public static function get_building_by_name( $building_name ) {
        self::init(); global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$buildings_table . " WHERE building_name = %s", sanitize_text_field($building_name) ) );
    }

    /**
     * Update an existing building.
     * @param int $building_id
     * @param array $args Associative array of data to update.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function update_building( $building_id, $args ) {
        self::init(); global $wpdb;
        oo_log('Attempting to update building ID: ' . $building_id . ' with args:', $args);

        $building_id = intval($building_id);
        if ( $building_id <= 0 ) {
            return new WP_Error('invalid_building_id', 'Invalid Building ID provided for update.');
        }

        $data = array();
        $formats = array();

        if ( isset( $args['building_name'] ) ) {
            $new_building_name = sanitize_text_field($args['building_name']);
            $existing_building = $wpdb->get_var( $wpdb->prepare(
                "SELECT building_id FROM " . self::$buildings_table . " WHERE building_name = %s AND building_id != %d",
                $new_building_name, $building_id
            ) );
            if ( $existing_building ) {
                return new WP_Error('building_name_exists', 'This Building Name is already assigned to another building.');
            }
            $data['building_name'] = $new_building_name;
            $formats[] = '%s';
        }
        if ( array_key_exists('address', $args) ) { $data['address'] = sanitize_textarea_field( $args['address'] ); $formats[] = '%s'; }
        if ( array_key_exists('storage_capacity_notes', $args) ) { $data['storage_capacity_notes'] = sanitize_textarea_field( $args['storage_capacity_notes'] ); $formats[] = '%s'; }
        if ( array_key_exists('primary_contact_id', $args) ) { $data['primary_contact_id'] = is_null($args['primary_contact_id']) ? null : intval( $args['primary_contact_id'] ); $formats[] = '%d'; }
        if ( isset( $args['is_active'] ) ) { $data['is_active'] = intval( $args['is_active'] ); $formats[] = '%d'; }

        if ( empty($data) ) {
            oo_log('No data provided to update for building ID: ' . $building_id, __METHOD__);
            return new WP_Error('no_data_to_update', 'No data provided to update building.');
        }

        $data['updated_at'] = current_time('mysql', 1);
        $formats[] = '%s';

        $result = $wpdb->update( self::$buildings_table, $data, array( 'building_id' => $building_id ), $formats, array('%d') );

        if ( $result === false ) {
            oo_log('Error updating building ID ' . $building_id . ': ' . $wpdb->last_error, array('data' => $data));
            return new WP_Error('db_update_error', 'Could not update building: ' . $wpdb->last_error);
        }
        oo_log('Building updated successfully. ID: ' . $building_id, __METHOD__);
        return true;
    }

    /**
     * Toggle the active status of a building.
     * @param int $building_id
     * @param bool $is_active
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function toggle_building_status( $building_id, $is_active ) {
        oo_log('Toggling building status for ID: ' . $building_id . ' to ' . $is_active, __METHOD__);
        self::init(); global $wpdb;
        $result = $wpdb->update(
            self::$buildings_table, 
            array( 'is_active' => intval($is_active), 'updated_at' => current_time('mysql', 1) ), 
            array( 'building_id' => intval($building_id) ), 
            array( '%d', '%s' ), // formats for data
            array( '%d' )  // formats for where
        );
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update building status. Error: ' . $wpdb->last_error);
            oo_log('Error toggling building status: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Building status toggled successfully for ID: ' . $building_id, __METHOD__);
        return true;
    }


    /**
     * Delete a building.
     * Note: job_streams.building_id is ON DELETE SET NULL.
     * @param int $building_id
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function delete_building( $building_id ) {
        self::init(); global $wpdb;
        $building_id = intval($building_id);
        if ( $building_id <= 0 ) {
            return new WP_Error('invalid_building_id', 'Invalid Building ID for deletion.');
        }

        $result = $wpdb->delete( self::$buildings_table, array( 'building_id' => $building_id ), array('%d') );

        if ( $result === false ) {
            oo_log('Error deleting building ID ' . $building_id . ': ' . $wpdb->last_error, __METHOD__);
            return new WP_Error('db_delete_error', 'Could not delete building: ' . $wpdb->last_error);
        }
        if ( $result === 0 ) {
            oo_log('Building not found for deletion or no rows affected. ID: ' . $building_id, __METHOD__);
            return true; // Not necessarily an error
        }
        oo_log('Building deleted successfully. ID: ' . $building_id, __METHOD__);
        return true;
    }

    /**
     * Get multiple buildings with filtering, sorting, and pagination.
     * @param array $params Parameters: is_active, search (name, address), orderby, order, number, offset.
     * @return array Array of building objects.
     */
    public static function get_buildings( $params = array() ) {
        self::init(); global $wpdb;

        $defaults = array(
            'is_active' => null,
            'search' => null,
            'orderby' => 'building_name',
            'order' => 'ASC',
            'number' => 20,
            'offset' => 0,
        );
        $args = wp_parse_args( $params, $defaults );

        $sql = "SELECT * FROM " . self::$buildings_table;
        $where_clauses = array();
        $query_params = array();

        if ( !is_null($args['is_active']) ) { $where_clauses[] = "is_active = %d"; $query_params[] = intval($args['is_active']); }
        
        if ( !empty($args['search']) ) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $search_fields = array("building_name LIKE %s", "address LIKE %s", "storage_capacity_notes LIKE %s");
            $where_clauses[] = "(" . implode(" OR ", $search_fields) . ")";
            $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;
        }

        if ( !empty($where_clauses) ) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        if ( !empty($query_params) ) {
            $sql = $wpdb->prepare($sql, $query_params);
        }

        $allowed_orderby = ['building_id', 'building_name', 'is_active', 'created_at', 'updated_at'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'building_name';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY $orderby $order";

        if ( $args['number'] > 0 ) {
            $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));
        }
        
        oo_log('Executing get_buildings query: ' . $sql, __METHOD__);
        return $wpdb->get_results( $sql );
    }

    /**
     * Get the count of buildings based on filters.
     * @param array $params Parameters: is_active, search.
     * @return int Count of buildings.
     */
    public static function get_buildings_count( $params = array() ) {
        self::init(); global $wpdb;

        $defaults = array(
            'is_active' => null,
            'search' => null,
        );
        $args = wp_parse_args( $params, $defaults );

        $sql = "SELECT COUNT(*) FROM " . self::$buildings_table;
        $where_clauses = array();
        $query_params = array();

        if ( !is_null($args['is_active']) ) { $where_clauses[] = "is_active = %d"; $query_params[] = intval($args['is_active']); }

        if ( !empty($args['search']) ) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $search_fields = array("building_name LIKE %s", "address LIKE %s", "storage_capacity_notes LIKE %s");
            $where_clauses[] = "(" . implode(" OR ", $search_fields) . ")";
            $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;
        }

        if ( !empty($where_clauses) ) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        if ( !empty($query_params) ) {
            $sql = $wpdb->prepare($sql, $query_params);
        }
        oo_log('Executing get_buildings_count query: ' . $sql, __METHOD__);
        return (int) $wpdb->get_var( $sql );
    }

    // --- ExpenseType CRUD Methods (NEW) ---
    // Placeholder for add_expense_type, get_expense_type, update_expense_type, etc.

    /**
     * Add a new expense type.
     *
     * @param array $args Associative array of expense type data.
     *                    Required: type_name.
     *                    Optional: default_unit, is_active.
     * @return int|WP_Error The new expense_type_id on success, or WP_Error on failure.
     */
    public static function add_expense_type( $args ) {
        self::init(); global $wpdb;
        oo_log('Attempting to add expense type with args:', $args);

        if ( empty( $args['type_name'] ) ) {
            return new WP_Error('missing_type_name', 'Expense Type Name is required.');
        }

        $existing_type = $wpdb->get_var( $wpdb->prepare(
            "SELECT expense_type_id FROM " . self::$expense_types_table . " WHERE type_name = %s",
            sanitize_text_field( $args['type_name'] )
        ) );
        if ( $existing_type ) {
            return new WP_Error('expense_type_name_exists', 'This Expense Type Name already exists.');
        }

        $data = array(
            'type_name' => sanitize_text_field( $args['type_name'] ),
            'default_unit' => isset($args['default_unit']) ? sanitize_text_field( $args['default_unit'] ) : null,
            'is_active' => isset($args['is_active']) ? intval( $args['is_active'] ) : 1,
            'created_at' => current_time('mysql', 1),
            'updated_at' => current_time('mysql', 1)
        );

        $formats = array(
            '%s', // type_name
            '%s', // default_unit
            '%d', // is_active
            '%s', // created_at
            '%s'  // updated_at
        );

        $result = $wpdb->insert( self::$expense_types_table, $data, $formats );

        if ( $result === false ) {
            oo_log('Error adding expense type: ' . $wpdb->last_error, array('data' => $data));
            return new WP_Error('db_insert_error', 'Could not add expense type: ' . $wpdb->last_error);
        }
        oo_log('Expense type added successfully. ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    /**
     * Get a specific expense type by its ID.
     * @param int $expense_type_id
     * @return object|null Expense type object or null if not found.
     */
    public static function get_expense_type( $expense_type_id ) {
        self::init(); global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$expense_types_table . " WHERE expense_type_id = %d", intval($expense_type_id) ) );
    }

    /**
     * Get a specific expense type by its name.
     * @param string $type_name
     * @return object|null Expense type object or null if not found.
     */
    public static function get_expense_type_by_name( $type_name ) {
        self::init(); global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$expense_types_table . " WHERE type_name = %s", sanitize_text_field($type_name) ) );
    }

    /**
     * Update an existing expense type.
     * @param int $expense_type_id
     * @param array $args Associative array of data to update.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function update_expense_type( $expense_type_id, $args ) {
        self::init(); global $wpdb;
        oo_log('Attempting to update expense type ID: ' . $expense_type_id . ' with args:', $args);

        $expense_type_id = intval($expense_type_id);
        if ( $expense_type_id <= 0 ) {
            return new WP_Error('invalid_expense_type_id', 'Invalid Expense Type ID provided for update.');
        }

        $data = array();
        $formats = array();

        if ( isset( $args['type_name'] ) ) {
            $new_type_name = sanitize_text_field($args['type_name']);
            $existing_type = $wpdb->get_var( $wpdb->prepare(
                "SELECT expense_type_id FROM " . self::$expense_types_table . " WHERE type_name = %s AND expense_type_id != %d",
                $new_type_name, $expense_type_id
            ) );
            if ( $existing_type ) {
                return new WP_Error('expense_type_name_exists', 'This Expense Type Name is already assigned to another type.');
            }
            $data['type_name'] = $new_type_name;
            $formats[] = '%s';
        }
        if ( array_key_exists('default_unit', $args) ) { $data['default_unit'] = sanitize_text_field( $args['default_unit'] ); $formats[] = '%s'; }
        if ( isset( $args['is_active'] ) ) { $data['is_active'] = intval( $args['is_active'] ); $formats[] = '%d'; }

        if ( empty($data) ) {
            oo_log('No data provided to update for expense type ID: ' . $expense_type_id, __METHOD__);
            return new WP_Error('no_data_to_update', 'No data provided to update expense type.');
        }

        $data['updated_at'] = current_time('mysql', 1);
        $formats[] = '%s';

        $result = $wpdb->update( self::$expense_types_table, $data, array( 'expense_type_id' => $expense_type_id ), $formats, array('%d') );

        if ( $result === false ) {
            oo_log('Error updating expense type ID ' . $expense_type_id . ': ' . $wpdb->last_error, array('data' => $data));
            return new WP_Error('db_update_error', 'Could not update expense type: ' . $wpdb->last_error);
        }
        oo_log('Expense type updated successfully. ID: ' . $expense_type_id, __METHOD__);
        return true;
    }

    /**
     * Toggle the active status of an expense type.
     * @param int $expense_type_id
     * @param bool $is_active
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function toggle_expense_type_status( $expense_type_id, $is_active ) {
        oo_log('Toggling expense type status for ID: ' . $expense_type_id . ' to ' . $is_active, __METHOD__);
        self::init(); global $wpdb;
        $result = $wpdb->update(
            self::$expense_types_table, 
            array( 'is_active' => intval($is_active), 'updated_at' => current_time('mysql', 1) ), 
            array( 'expense_type_id' => intval($expense_type_id) ), 
            array( '%d', '%s' ), 
            array( '%d' )
        );
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update expense type status. Error: ' . $wpdb->last_error);
            oo_log('Error toggling expense type status: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Expense type status toggled successfully for ID: ' . $expense_type_id, __METHOD__);
        return true;
    }

    /**
     * Delete an expense type.
     * Note: Foreign key to oo_expenses is ON DELETE RESTRICT.
     * @param int $expense_type_id
     * @return bool|WP_Error True on success, WP_Error on failure (e.g., if type is in use).
     */
    public static function delete_expense_type( $expense_type_id ) {
        self::init(); global $wpdb;
        $expense_type_id = intval($expense_type_id);
        if ( $expense_type_id <= 0 ) {
            return new WP_Error('invalid_expense_type_id', 'Invalid Expense Type ID for deletion.');
        }

        // Check if this expense type is used in any expenses
        $usage_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::$expenses_table . " WHERE expense_type_id = %d",
            $expense_type_id
        ) );

        if ( $usage_count > 0 ) {
            oo_log('Attempt to delete expense type ID ' . $expense_type_id . ' failed because it is in use.', __METHOD__);
            return new WP_Error('expense_type_in_use', 'This expense type cannot be deleted because it is currently assigned to one or more expenses.');
        }

        $result = $wpdb->delete( self::$expense_types_table, array( 'expense_type_id' => $expense_type_id ), array('%d') );

        if ( $result === false ) {
            oo_log('Error deleting expense type ID ' . $expense_type_id . ': ' . $wpdb->last_error, __METHOD__);
            return new WP_Error('db_delete_error', 'Could not delete expense type: ' . $wpdb->last_error);
        }
        if ( $result === 0 ) {
            oo_log('Expense type not found for deletion or no rows affected. ID: ' . $expense_type_id, __METHOD__);
            return true; // Not necessarily an error
        }
        oo_log('Expense type deleted successfully. ID: ' . $expense_type_id, __METHOD__);
        return true;
    }

    /**
     * Get multiple expense types with filtering, sorting, and pagination.
     * @param array $params Parameters: is_active, search (type_name), orderby, order, number, offset.
     * @return array Array of expense type objects.
     */
    public static function get_expense_types( $params = array() ) {
        self::init(); global $wpdb;

        $defaults = array(
            'is_active' => null,
            'search' => null,
            'orderby' => 'type_name',
            'order' => 'ASC',
            'number' => 20,
            'offset' => 0,
        );
        $args = wp_parse_args( $params, $defaults );

        $sql = "SELECT * FROM " . self::$expense_types_table;
        $where_clauses = array();
        $query_params = array();

        if ( !is_null($args['is_active']) ) { $where_clauses[] = "is_active = %d"; $query_params[] = intval($args['is_active']); }
        
        if ( !empty($args['search']) ) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $where_clauses[] = "(type_name LIKE %s OR default_unit LIKE %s)";
            $query_params[] = $search_term; $query_params[] = $search_term;
        }

        if ( !empty($where_clauses) ) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        if ( !empty($query_params) ) {
            $sql = $wpdb->prepare($sql, $query_params);
        }

        $allowed_orderby = ['expense_type_id', 'type_name', 'default_unit', 'is_active', 'created_at'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'type_name';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY $orderby $order";

        if ( $args['number'] > 0 ) {
            $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));
        }
        
        oo_log('Executing get_expense_types query: ' . $sql, __METHOD__);
        return $wpdb->get_results( $sql );
    }

    /**
     * Get the count of expense types based on filters.
     * @param array $params Parameters: is_active, search.
     * @return int Count of expense types.
     */
    public static function get_expense_types_count( $params = array() ) {
        self::init(); global $wpdb;

        $defaults = array(
            'is_active' => null,
            'search' => null,
        );
        $args = wp_parse_args( $params, $defaults );

        $sql = "SELECT COUNT(*) FROM " . self::$expense_types_table;
        $where_clauses = array();
        $query_params = array();

        if ( !is_null($args['is_active']) ) { $where_clauses[] = "is_active = %d"; $query_params[] = intval($args['is_active']); }

        if ( !empty($args['search']) ) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $where_clauses[] = "(type_name LIKE %s OR default_unit LIKE %s)";
            $query_params[] = $search_term; $query_params[] = $search_term;
        }

        if ( !empty($where_clauses) ) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        if ( !empty($query_params) ) {
            $sql = $wpdb->prepare($sql, $query_params);
        }
        oo_log('Executing get_expense_types_count query: ' . $sql, __METHOD__);
        return (int) $wpdb->get_var( $sql );
    }

    // --- Expense CRUD Methods (NEW) ---
    // Placeholder for add_expense, get_expense, update_expense, etc.

    /**
     * Add a new expense record.
     *
     * @param array $args Associative array of expense data.
     *                    Required: job_id, expense_type_id, amount, expense_date.
     *                    Optional: stream_id, employee_id, description, receipt_image_url, related_log_id.
     * @return int|WP_Error The new expense_id on success, or WP_Error on failure.
     */
    public static function add_expense( $args ) {
        self::init(); global $wpdb;
        oo_log('Attempting to add expense with args:', $args);

        if ( empty( $args['job_id'] ) || empty( $args['expense_type_id'] ) || !isset( $args['amount'] ) || empty( $args['expense_date'] ) ) {
            return new WP_Error('missing_required_fields', 'Job ID, Expense Type ID, Amount, and Expense Date are required.');
        }

        $data = array(
            'job_id' => intval( $args['job_id'] ),
            'stream_id' => isset($args['stream_id']) ? intval( $args['stream_id'] ) : null,
            'expense_type_id' => intval( $args['expense_type_id'] ),
            'employee_id' => isset($args['employee_id']) ? intval( $args['employee_id'] ) : null,
            'amount' => wc_format_decimal( $args['amount'], 2 ), // Assuming WooCommerce for formatting, or use number_format.
            'expense_date' => oo_sanitize_date( $args['expense_date'] ),
            'description' => isset($args['description']) ? sanitize_textarea_field( $args['description'] ) : null,
            'receipt_image_url' => isset($args['receipt_image_url']) ? esc_url_raw( $args['receipt_image_url'] ) : null,
            'related_log_id' => isset($args['related_log_id']) ? intval( $args['related_log_id'] ) : null,
            'created_at' => current_time('mysql', 1),
            'updated_at' => current_time('mysql', 1)
        );

        $formats = array(
            '%d', // job_id
            '%d', // stream_id
            '%d', // expense_type_id
            '%d', // employee_id
            '%f', // amount (use %f for float/decimal)
            '%s', // expense_date
            '%s', // description
            '%s', // receipt_image_url
            '%d', // related_log_id
            '%s', // created_at
            '%s'  // updated_at
        );
        
        // Filter out nulls for $wpdb->insert if not explicitly wanting to insert NULL
        // For $wpdb->insert, if a key is present in $data but its corresponding format is not in $formats, it might cause issues.
        // It's safer to ensure $data and $formats align, or let $wpdb handle nulls if columns allow.
        // Example: if stream_id is null, it should be passed as null to $data, and its format should be %d or %s.

        $result = $wpdb->insert( self::$expenses_table, $data, $formats );

        if ( $result === false ) {
            oo_log('Error adding expense: ' . $wpdb->last_error, array('data' => $data, 'formats' => $formats));
            return new WP_Error('db_insert_error', 'Could not add expense: ' . $wpdb->last_error);
        }
        oo_log('Expense added successfully. ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    /**
     * Get a specific expense by its ID.
     * @param int $expense_id
     * @return object|null Expense object or null if not found.
     */
    public static function get_expense( $expense_id ) {
        self::init(); global $wpdb;
        // Consider JOINing with related tables here if always needed, or make it optional.
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$expenses_table . " WHERE expense_id = %d", intval($expense_id) ) );
    }

    /**
     * Update an existing expense.
     * @param int $expense_id
     * @param array $args Associative array of data to update.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function update_expense( $expense_id, $args ) {
        self::init(); global $wpdb;
        oo_log('Attempting to update expense ID: ' . $expense_id . ' with args:', $args);

        $expense_id = intval($expense_id);
        if ( $expense_id <= 0 ) {
            return new WP_Error('invalid_expense_id', 'Invalid Expense ID provided for update.');
        }

        $data = array();
        $formats = array();

        // Fields that can be updated
        if ( isset( $args['job_id'] ) ) { $data['job_id'] = intval( $args['job_id'] ); $formats[] = '%d'; }
        if ( array_key_exists('stream_id', $args) ) { $data['stream_id'] = is_null($args['stream_id']) ? null : intval( $args['stream_id'] ); $formats[] = '%d'; }
        if ( isset( $args['expense_type_id'] ) ) { $data['expense_type_id'] = intval( $args['expense_type_id'] ); $formats[] = '%d'; }
        if ( array_key_exists('employee_id', $args) ) { $data['employee_id'] = is_null($args['employee_id']) ? null : intval( $args['employee_id'] ); $formats[] = '%d'; }
        if ( isset( $args['amount'] ) ) { $data['amount'] = wc_format_decimal( $args['amount'], 2 ); $formats[] = '%f'; }
        if ( isset( $args['expense_date'] ) ) { $data['expense_date'] = oo_sanitize_date( $args['expense_date'] ); $formats[] = '%s'; }
        if ( array_key_exists('description', $args) ) { $data['description'] = sanitize_textarea_field( $args['description'] ); $formats[] = '%s'; }
        if ( array_key_exists('receipt_image_url', $args) ) { $data['receipt_image_url'] = is_null($args['receipt_image_url']) ? null : esc_url_raw( $args['receipt_image_url'] ); $formats[] = '%s'; }
        if ( array_key_exists('related_log_id', $args) ) { $data['related_log_id'] = is_null($args['related_log_id']) ? null : intval( $args['related_log_id'] ); $formats[] = '%d'; }

        if ( empty($data) ) {
            oo_log('No data provided to update for expense ID: ' . $expense_id, __METHOD__);
            return new WP_Error('no_data_to_update', 'No data provided to update expense.');
        }

        $data['updated_at'] = current_time('mysql', 1);
        $formats[] = '%s';

        $result = $wpdb->update( self::$expenses_table, $data, array( 'expense_id' => $expense_id ), $formats, array('%d') );

        if ( $result === false ) {
            oo_log('Error updating expense ID ' . $expense_id . ': ' . $wpdb->last_error, array('data' => $data));
            return new WP_Error('db_update_error', 'Could not update expense: ' . $wpdb->last_error);
        }
        oo_log('Expense updated successfully. ID: ' . $expense_id, __METHOD__);
        return true;
    }

    /**
     * Delete an expense.
     * @param int $expense_id
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function delete_expense( $expense_id ) {
        self::init(); global $wpdb;
        $expense_id = intval($expense_id);
        if ( $expense_id <= 0 ) {
            return new WP_Error('invalid_expense_id', 'Invalid Expense ID for deletion.');
        }

        $result = $wpdb->delete( self::$expenses_table, array( 'expense_id' => $expense_id ), array('%d') );

        if ( $result === false ) {
            oo_log('Error deleting expense ID ' . $expense_id . ': ' . $wpdb->last_error, __METHOD__);
            return new WP_Error('db_delete_error', 'Could not delete expense: ' . $wpdb->last_error);
        }
        if ( $result === 0 ) {
            oo_log('Expense not found for deletion or no rows affected. ID: ' . $expense_id, __METHOD__);
            return true; 
        }
        oo_log('Expense deleted successfully. ID: ' . $expense_id, __METHOD__);
        return true;
    }

    /**
     * Get multiple expenses with filtering, sorting, and pagination.
     * @param array $params Parameters: job_id, stream_id, expense_type_id, employee_id, 
     *                       date_from, date_to, search (description), orderby, order, number, offset.
     * @return array Array of expense objects (potentially joined with related data).
     */
    public static function get_expenses( $params = array() ) {
        self::init(); global $wpdb;

        $defaults = array(
            'job_id' => null,
            'stream_id' => null,
            'expense_type_id' => null,
            'employee_id' => null,
            'date_from' => null, // for expense_date
            'date_to' => null,   // for expense_date
            'search' => null,    // search description
            'orderby' => 'exp.expense_date',
            'order' => 'DESC',
            'number' => 20,
            'offset' => 0,
        );
        $args = wp_parse_args( $params, $defaults );

        $sql_select = "SELECT exp.*, j.job_number, s.stream_name, et.type_name AS expense_type_name, e.first_name AS emp_first_name, e.last_name AS emp_last_name";
        $sql_from = " FROM " . self::$expenses_table . " exp ";
        $sql_joins = "LEFT JOIN " . self::$jobs_table . " j ON exp.job_id = j.job_id " .
                     "LEFT JOIN " . self::$streams_table . " s ON exp.stream_id = s.stream_id " .
                     "LEFT JOIN " . self::$expense_types_table . " et ON exp.expense_type_id = et.expense_type_id " .
                     "LEFT JOIN " . self::$employees_table . " e ON exp.employee_id = e.employee_id ";
        
        $sql = $sql_select . $sql_from . $sql_joins;

        $where_clauses = array();
        $query_params = array();

        if ( !empty($args['job_id']) ) { $where_clauses[] = "exp.job_id = %d"; $query_params[] = intval($args['job_id']); }
        if ( !empty($args['stream_id']) ) { $where_clauses[] = "exp.stream_id = %d"; $query_params[] = intval($args['stream_id']); }
        if ( !empty($args['expense_type_id']) ) { $where_clauses[] = "exp.expense_type_id = %d"; $query_params[] = intval($args['expense_type_id']); }
        if ( !empty($args['employee_id']) ) { $where_clauses[] = "exp.employee_id = %d"; $query_params[] = intval($args['employee_id']); }
        
        if ( !empty($args['date_from']) ) { $where_clauses[] = "exp.expense_date >= %s"; $query_params[] = oo_sanitize_date($args['date_from']); }
        if ( !empty($args['date_to']) ) { $where_clauses[] = "exp.expense_date <= %s"; $query_params[] = oo_sanitize_date($args['date_to']); }

        if ( !empty($args['search']) ) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $where_clauses[] = "exp.description LIKE %s";
            $query_params[] = $search_term;
        }

        if ( !empty($where_clauses) ) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        if ( !empty($query_params) ) {
            $sql = $wpdb->prepare($sql, $query_params);
        }

        $allowed_orderby = ['exp.expense_id', 'exp.expense_date', 'exp.amount', 'j.job_number', 's.stream_name', 'et.type_name', 'exp.created_at'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'exp.expense_date';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY $orderby $order";

        if ( $args['number'] > 0 ) {
            $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));
        }
        
        oo_log('Executing get_expenses query: ' . $sql, __METHOD__);
        return $wpdb->get_results( $sql );
    }

    /**
     * Get the count of expenses based on filters.
     * @param array $params Parameters (same as get_expenses, excluding pagination/order).
     * @return int Count of expenses.
     */
    public static function get_expenses_count( $params = array() ) {
        self::init(); global $wpdb;

        $defaults = array(
            'job_id' => null,
            'stream_id' => null,
            'expense_type_id' => null,
            'employee_id' => null,
            'date_from' => null,
            'date_to' => null,
            'search' => null,
        );
        $args = wp_parse_args( $params, $defaults );

        $sql = "SELECT COUNT(exp.expense_id) FROM " . self::$expenses_table . " exp ";
        $sql_joins = "LEFT JOIN " . self::$jobs_table . " j ON exp.job_id = j.job_id " .
                     "LEFT JOIN " . self::$streams_table . " s ON exp.stream_id = s.stream_id " .
                     "LEFT JOIN " . self::$expense_types_table . " et ON exp.expense_type_id = et.expense_type_id " .
                     "LEFT JOIN " . self::$employees_table . " e ON exp.employee_id = e.employee_id ";
        $sql .= $sql_joins; 

        $where_clauses = array();
        $query_params = array();

        if ( !empty($args['job_id']) ) { $where_clauses[] = "exp.job_id = %d"; $query_params[] = intval($args['job_id']); }
        if ( !empty($args['stream_id']) ) { $where_clauses[] = "exp.stream_id = %d"; $query_params[] = intval($args['stream_id']); }
        if ( !empty($args['expense_type_id']) ) { $where_clauses[] = "exp.expense_type_id = %d"; $query_params[] = intval($args['expense_type_id']); }
        if ( !empty($args['employee_id']) ) { $where_clauses[] = "exp.employee_id = %d"; $query_params[] = intval($args['employee_id']); }
        if ( !empty($args['date_from']) ) { $where_clauses[] = "exp.expense_date >= %s"; $query_params[] = oo_sanitize_date($args['date_from']); }
        if ( !empty($args['date_to']) ) { $where_clauses[] = "exp.expense_date <= %s"; $query_params[] = oo_sanitize_date($args['date_to']); }
        if ( !empty($args['search']) ) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $where_clauses[] = "exp.description LIKE %s";
            $query_params[] = $search_term;
        }

        if ( !empty($where_clauses) ) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        if ( !empty($query_params) ) {
            $sql = $wpdb->prepare($sql, $query_params);
        }
        oo_log('Executing get_expenses_count query: ' . $sql, __METHOD__);
        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Get all expenses for a specific job.
     * @param int $job_id
     * @param array $extra_args Additional arguments for get_expenses.
     * @return array Array of expense objects.
     */
    public static function get_expenses_for_job( $job_id, $extra_args = array() ) {
        if ( empty($job_id) || intval($job_id) <= 0 ) {
            return array(); 
        }
        $args = array_merge( $extra_args, array('job_id' => intval($job_id), 'number' => -1 ) );
        return self::get_expenses( $args );
    }

    /**
     * Get all expenses for a specific job and stream.
     * @param int $job_id
     * @param int $stream_id
     * @param array $extra_args Additional arguments for get_expenses.
     * @return array Array of expense objects.
     */
    public static function get_expenses_for_job_stream( $job_id, $stream_id, $extra_args = array() ) {
        if ( empty($job_id) || intval($job_id) <= 0 || empty($stream_id) || intval($stream_id) <=0 ) {
            return array();
        }
        $args = array_merge( $extra_args, array(
            'job_id' => intval($job_id), 
            'stream_id' => intval($stream_id), 
            'number' => -1 
            ) 
        );
        return self::get_expenses( $args );
    }

}

// Initialize table names on load with new class name
add_action('plugins_loaded', array('OO_DB', 'init'), 5); 