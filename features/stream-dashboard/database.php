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

	public static function update_phase_order( $stream_id, $phase_order ) {
		self::init();
		global $wpdb;

		if ( empty($phase_order) ) {
			return new WP_Error('empty_order', 'No phase order was provided.');
		}

		$wpdb->query('START TRANSACTION');

		foreach ( $phase_order as $index => $phase_id ) {
			$order = $index + 1;
			$result = $wpdb->update(
				self::$phases_table,
				array( 'order_in_stream' => $order ),
				array( 'phase_id' => $phase_id, 'stream_id' => $stream_id ),
				array( '%d' ),
				array( '%d', '%d' )
			);

			if ( false === $result ) {
				$wpdb->query('ROLLBACK');
				return new WP_Error('db_update_error', 'Could not update the order for phase_id ' . $phase_id);
			}
		}

		$wpdb->query('COMMIT');
		return true;
	}

	// --- KPI Methods ---
	public static function get_kpi_measures_for_stream($stream_id, $args = array()) {
		self::init();
		global $wpdb;
		$defaults = array('is_active' => 1);
		$args = wp_parse_args($args, $defaults);
		$sql = "SELECT DISTINCT km.* FROM " . self::$kpi_measures_table . " km
				JOIN " . self::$phase_kpi_measures_link_table . " pkl ON km.kpi_measure_id = pkl.kpi_measure_id
				JOIN " . self::$phases_table . " p ON pkl.phase_id = p.phase_id
				WHERE p.stream_id = %d";
		$params = array($stream_id);
		if ( !is_null($args['is_active']) ) {
			$sql .= " AND km.is_active = %d";
			$params[] = $args['is_active'];
		}
		$sql .= " ORDER BY km.measure_name ASC";
		return $wpdb->get_results($wpdb->prepare($sql, $params));
	}

	public static function get_phase_names_for_kpi_in_stream($kpi_measure_id, $stream_id) {
		self::init();
		global $wpdb;
		$sql = "SELECT p.phase_name FROM " . self::$phases_table . " p
				JOIN " . self::$phase_kpi_measures_link_table . " pkl ON p.phase_id = pkl.phase_id
				WHERE p.stream_id = %d AND pkl.kpi_measure_id = %d AND p.is_active = 1";
		return $wpdb->get_col($wpdb->prepare($sql, $stream_id, $kpi_measure_id));
	}

	public static function get_derived_kpi_definitions( $params = array() ) {
		self::init();
		global $wpdb;
		$defaults = array(
			'is_active' => null,
			'number' => 20,
			'offset' => 0,
			'orderby' => 'definition_name',
			'order' => 'ASC',
			'primary_kpi_measure_id' => null
		);
		$params = wp_parse_args($params, $defaults);
		$sql_base = "SELECT * FROM " . self::$derived_kpi_definitions_table;
		$where_clauses = array();
		$query_params = array();
		if ( !is_null($params['is_active']) ) {
			$where_clauses[] = "is_active = %d";
			$query_params[] = $params['is_active'];
		}
		if ( !is_null($params['primary_kpi_measure_id']) ) {
			$where_clauses[] = "primary_kpi_measure_id = %d";
			$query_params[] = intval($params['primary_kpi_measure_id']);
		}
		$sql_where = "";
		if ( !empty($where_clauses) ) {
			$sql_where = " WHERE " . implode(" AND ", $where_clauses);
		}
		$sql = $sql_base . $sql_where;
		if (!empty($query_params)){
			$sql = $wpdb->prepare($sql, $query_params);
		}
		$sql .= " ORDER BY " . sanitize_sql_orderby($params['orderby']) . " " . (strtoupper($params['order']) === 'DESC' ? 'DESC' : 'ASC');
		if ($params['number'] > 0) {
			$sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $params['number'], $params['offset']);
		}
		return $wpdb->get_results($sql);
	}

	public static function get_kpi_measure($kpi_measure_id) {
		self::init();
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::$kpi_measures_table . " WHERE kpi_measure_id = %d", $kpi_measure_id));
	}
} 