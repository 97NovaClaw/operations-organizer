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
    public static function add_employee( $employee_number, $first_name, $last_name ) {
        oo_log('Attempting to add employee.', __METHOD__);
        oo_log(array('employee_number' => $employee_number, 'first_name' => $first_name, 'last_name' => $last_name), __METHOD__);
        self::init(); // Ensures self::$employees_table is set with new prefix
        global $wpdb;
        if (empty($employee_number) || empty($first_name) || empty($last_name)) {
            $error = new WP_Error('missing_fields', 'All fields (Employee Number, First Name, Last Name) are required.');
            oo_log('Error adding employee: Missing fields.', $error);
            return $error;
        }
        $exists = $wpdb->get_var( $wpdb->prepare("SELECT employee_id FROM " . self::$employees_table . " WHERE employee_number = %s", $employee_number) );
        if ($exists) {
            $error = new WP_Error('employee_exists', 'Employee number already exists.');
            oo_log('Error adding employee: Employee number exists.', $error);
            return $error;
        }
        $result = $wpdb->insert(self::$employees_table, array('employee_number' => sanitize_text_field($employee_number), 'first_name' => sanitize_text_field($first_name), 'last_name' => sanitize_text_field($last_name), 'is_active' => 1, 'created_at' => current_time('mysql', 1)), array('%s', '%s', '%s', '%d', '%s'));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not add employee. Error: ' . $wpdb->last_error);
            oo_log('Error adding employee: DB insert failed.', $error);
            oo_log('WPDB Last Error: ' . $wpdb->last_error, __METHOD__);
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

    public static function update_employee( $employee_id, $employee_number, $first_name, $last_name, $is_active = null ) {
        oo_log('Attempting to update employee ID: ' . $employee_id, __METHOD__);
        oo_log(compact('employee_id', 'employee_number', 'first_name', 'last_name', 'is_active'), __METHOD__);
        self::init();
        global $wpdb;
        if (empty($employee_number) || empty($first_name) || empty($last_name)) {
            $error = new WP_Error('missing_fields', 'All fields (Employee Number, First Name, Last Name) are required.');
            oo_log('Error updating employee: Missing fields.', $error);
            return $error;
        }
        $existing_employee = $wpdb->get_row( $wpdb->prepare("SELECT employee_id FROM " . self::$employees_table . " WHERE employee_number = %s AND employee_id != %d", $employee_number, $employee_id) );
        if ($existing_employee) {
            $error = new WP_Error('employee_number_exists', 'This employee number is already assigned to another employee.');
            oo_log('Error updating employee: Employee number exists for another ID.', $error);
            return $error;
        }
        $data = array('employee_number' => sanitize_text_field($employee_number), 'first_name' => sanitize_text_field($first_name), 'last_name' => sanitize_text_field($last_name));
        $formats = array('%s', '%s', '%s');
        if ( !is_null($is_active) ) { $data['is_active'] = intval($is_active); $formats[] = '%d'; }
        $result = $wpdb->update(self::$employees_table, $data, array( 'employee_id' => $employee_id ), $formats, array( '%d' ));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update employee. Error: ' . $wpdb->last_error);
            oo_log('Error updating employee: DB update failed. ' . $wpdb->last_error, $error);
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
        return $wpdb->get_var( $sql );
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
}

// Initialize table names on load with new class name
add_action('plugins_loaded', array('OO_DB', 'init'), 5); 