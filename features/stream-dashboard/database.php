<?php
/**
 * Database handlers for the Stream Dashboard feature.
 *
 * @package Operations_Organizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class OO_Stream_Dashboard_DB
 *
 * This class contains all the database methods previously in OO_DB
 * that are required for the Stream Dashboard feature to function.
 */
class OO_Stream_Dashboard_DB {

	private static $jobs_table;
	private static $streams_table;
	private static $job_streams_link_table;
	private static $phases_table;
	private static $job_logs_table;
	private static $employees_table;
	private static $buildings_table;
	private static $expenses_table;
	private static $expense_types_table;
	private static $kpi_measures_table;
	private static $phase_kpi_measures_link_table;
	private static $derived_kpi_definitions_table;
	private static $job_log_derived_values_table;
	private static $stream_data_soft_content_table;
	private static $stream_data_electronics_table;
	private static $stream_data_art_table;
	private static $stream_data_content_table;

	public static function init() {
		oo_log('OO_Stream_Dashboard_DB::init() called.', 'StreamDashboardDB');
		global $wpdb;
		self::$jobs_table = $wpdb->prefix . 'oo_jobs';
		self::$streams_table = $wpdb->prefix . 'oo_streams';
		self::$job_streams_link_table = $wpdb->prefix . 'oo_job_streams_link';
		self::$phases_table = $wpdb->prefix . 'oo_phases';
		self::$job_logs_table = $wpdb->prefix . 'oo_job_logs';
		self::$employees_table = $wpdb->prefix . 'oo_employees';
		self::$buildings_table = $wpdb->prefix . 'oo_buildings';
		self::$expenses_table = $wpdb->prefix . 'oo_expenses';
		self::$expense_types_table = $wpdb->prefix . 'oo_expense_types';
		self::$kpi_measures_table = $wpdb->prefix . 'oo_kpi_measures';
		self::$phase_kpi_measures_link_table = $wpdb->prefix . 'oo_phase_kpi_measures_link';
		self::$derived_kpi_definitions_table = $wpdb->prefix . 'oo_derived_kpi_definitions';
		self::$job_log_derived_values_table = $wpdb->prefix . 'oo_job_log_derived_values';
		self::$stream_data_soft_content_table = $wpdb->prefix . 'oo_stream_data_soft_content';
		self::$stream_data_electronics_table = $wpdb->prefix . 'oo_stream_data_electronics';
		self::$stream_data_art_table = $wpdb->prefix . 'oo_stream_data_art';
		self::$stream_data_content_table = $wpdb->prefix . 'oo_stream_data_content';
		oo_log('OO_Stream_Dashboard_DB::init() finished. phases_table set to: ' . self::$phases_table, 'StreamDashboardDB');
	}

	// --- Employee Methods ---
	public static function get_employees( $args = array() ) {
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
		return $wpdb->get_results( $sql );
	}

	// --- Phase Methods ---
	public static function get_phases( $args = array() ) { 
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
		return $wpdb->get_results( $sql );
	}
	
	// ... (All other necessary DB methods will be added here with their full implementation) ...

	public static function get_kpi_measures_for_stream($stream_id, $args = array()) {
		oo_log('OO_Stream_Dashboard_DB::get_kpi_measures_for_stream() called with stream_id: ' . $stream_id, 'StreamDashboardDB');
		oo_log($args, 'StreamDashboardDB get_kpi_measures_for_stream args');
		self::init();
		global $wpdb;
		// ... existing code ...
	}
} 