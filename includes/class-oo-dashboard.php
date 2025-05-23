<?php
// /includes/class-oo-dashboard.php // Renamed file

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Dashboard { // Renamed class

    public static function display_dashboard_page() {
        if ( ! current_user_can( oo_get_capability() ) ) { 
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'operations-organizer' ) );
        }
        
        $GLOBALS['employees'] = OO_DB::get_employees(array('is_active' => 1, 'orderby' => 'last_name', 'order' => 'ASC', 'number' => -1));
        // For the Quick Actions, we might want all stream types, or let user select a stream type first, then its phases.
        // For now, let's get all phases and group them by stream type in the view, or filter later.
        $GLOBALS['phases'] = OO_DB::get_phases(array('is_active' => 1, 'orderby' => 'stream_id, order_in_stream'));
        
        // Use hardcoded streams as requested by the client
        $GLOBALS['streams'] = oo_get_hardcoded_streams();
        // For backward compatibility with dashboard-page.php that still uses stream_types
        $GLOBALS['stream_types'] = $GLOBALS['streams'];

        include_once OO_PLUGIN_DIR . 'admin/views/dashboard-page.php';
    }

    private static function calculate_duration($start_time_str, $end_time_str, $format = 'string') {
        // ... (this method remains the same, no OO_ specific changes needed internally)
        if (empty($end_time_str) || empty($start_time_str)) {
            if ($format === 'seconds') return 0;
            if ($format === 'hours') return 0;
            return 'N/A';
        }
        try {
            $start_time = new DateTime($start_time_str, new DateTimeZone(wp_timezone_string()));
            $end_time = new DateTime($end_time_str, new DateTimeZone(wp_timezone_string()));
            
            // Make sure timestamps are normalized to UTC for accurate comparison
            $start_ts = $start_time->getTimestamp();
            $end_ts = $end_time->getTimestamp();
            
            // Log the timestamps for debugging
            oo_log("Duration calculation: Start time: {$start_time_str} ({$start_ts}), End time: {$end_time_str} ({$end_ts})", __METHOD__);
            
            // Check if end time is before start time (which indicates a time zone or data issue)
            if ($end_ts < $start_ts) {
                oo_log("Warning: End time {$end_time_str} is before start time {$start_time_str}. Check time zone settings.", __METHOD__);
                // Try to handle this case by assuming the dates might be incorrect but times are correct
                // For example, work might have started at night and ended in the morning
                if ($format === 'seconds') return 0;
                if ($format === 'hours') return 0;
                return '0s';
            }
            
            $timestamp_diff = $end_ts - $start_ts;
            
            if ($format === 'seconds') return $timestamp_diff;
            if ($format === 'hours') return $timestamp_diff > 0 ? $timestamp_diff / 3600 : 0;
            
            $hours = floor($timestamp_diff / 3600);
            $mins = floor(($timestamp_diff % 3600) / 60);
            $secs = $timestamp_diff % 60;
            
            $duration_str = '';
            if ($hours > 0) $duration_str .= $hours . 'h ';
            if ($mins > 0) $duration_str .= $mins . 'm ';
            $duration_str .= $secs . 's';
            
            return trim($duration_str) ?: '0s';
        } catch (Exception $e) {
            oo_log("Error calculating duration: " . $e->getMessage(), __METHOD__); // Use oo_log
            if ($format === 'seconds') return 0;
            if ($format === 'hours') return 0;
            return 'Error';
        }
    }

    public static function ajax_get_dashboard_data() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_dashboard_nonce', 'nonce');

        if (!current_user_can(oo_get_capability())) {
            oo_log('AJAX Error: Permission denied for dashboard data.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        // ... (data parsing from $_POST remains similar for now) ...
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 25;
        if ($length === -1) { $length = 999999; }
        $search_value = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';
        $filter_employee_id = isset($_POST['filter_employee_id']) && !empty($_POST['filter_employee_id']) ? intval($_POST['filter_employee_id']) : null;
        $filter_job_number = isset($_POST['filter_job_number']) && !empty($_POST['filter_job_number']) ? sanitize_text_field($_POST['filter_job_number']) : null;
        $filter_phase_id = isset($_POST['filter_phase_id']) && !empty($_POST['filter_phase_id']) ? intval($_POST['filter_phase_id']) : null;
        $filter_stream_id = isset($_POST['filter_stream_id']) && !empty($_POST['filter_stream_id']) ? intval($_POST['filter_stream_id']) : null;
        $filter_date_from = isset($_POST['filter_date_from']) && !empty($_POST['filter_date_from']) ? sanitize_text_field($_POST['filter_date_from']) : null;
        $filter_date_to = isset($_POST['filter_date_to']) && !empty($_POST['filter_date_to']) ? sanitize_text_field($_POST['filter_date_to']) : null;
        $filter_status = isset($_POST['filter_status']) && !empty($_POST['filter_status']) ? sanitize_text_field($_POST['filter_status']) : null;
        $selected_kpi_keys = isset($_POST['selected_kpi_keys']) && is_array($_POST['selected_kpi_keys']) ? array_map('sanitize_key', $_POST['selected_kpi_keys']) : array();

        // Legacy KPI keys are no longer forced into selection.
        // If 'boxes_completed' or 'items_completed' are needed, they should be selected as dynamic KPIs.
        oo_log('Selected KPI keys for processing: ', $selected_kpi_keys);
        
        // Check if we have explicit order parameter, useful when tabs enforce specific ordering
        $order_column_index = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 4; // Default to start_time column
        $order_dir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'desc';
        
        // Content stream tab specific ordering - force order by start_time when filtering by stream_id=4
        if ($filter_stream_id === 4) {
            $orderby_db_col = 'jl.start_time';
            $order_dir = 'desc';
        } else {
            $columns = [
                'employee_name' => 'e.last_name',
                'job_number' => 'jl.job_number',
                'stream_name' => 'st.stream_name',
                'phase_name' => 'p.phase_name',
                'start_time' => 'jl.start_time',
                'end_time' => 'jl.end_time',
                'duration' => null,
                // KPI columns might become dynamic based on stream_id via kpi_fields_config
                'kpi_data' => 'jl.kpi_data', // New general KPI data
                'status' => 'jl.status',
                'notes' => 'jl.notes'
            ];
            $column_keys = array_keys($columns);
            $orderby_db_col = isset($column_keys[$order_column_index]) && !is_null($columns[$column_keys[$order_column_index]]) 
                                ? $columns[$column_keys[$order_column_index]] 
                                : 'jl.start_time'; // Default if column not directly sortable or mapping missing
            
            if ($column_keys[$order_column_index] == 'employee_name') {
                 $orderby_db_col = 'e.last_name ' . $order_dir . ', e.first_name'; 
            } 
        }

        $args = array(
            'number'         => $length,
            'offset'         => $start,
            'orderby'        => $orderby_db_col,
            'order'          => $order_dir,
            'search_general' => $search_value, 
            'employee_id'    => $filter_employee_id,
            'job_number'     => $filter_job_number,
            'phase_id'       => $filter_phase_id,
            'stream_id'      => $filter_stream_id,
            'date_from'      => $filter_date_from,
            'date_to'        => $filter_date_to,
            'status'         => $filter_status,
        );
        oo_log($args, 'Dashboard data query args: ');

        $logs = OO_DB::get_job_logs($args);
        $count_args = $args; unset($count_args['number']); unset($count_args['offset']); unset($count_args['orderby']); unset($count_args['order']);
        $total_filtered_records = OO_DB::get_job_logs_count($count_args);
        $total_records = OO_DB::get_job_logs_count(array('job_number' => null)); // Simpler count for all

        oo_log('Dashboard data counts: Total=' . $total_records . ', Filtered=' . $total_filtered_records . ', Fetched for page=' . count($logs), 'Results for ' . __METHOD__);

        $data = array();
        foreach ($logs as $log) {
            $duration_seconds = self::calculate_duration($log->start_time, $log->end_time, 'seconds');
            $duration_hours = self::calculate_duration($log->start_time, $log->end_time, 'hours');
            $duration_display = self::calculate_duration($log->start_time, $log->end_time, 'string');

            // Handle existing and new kpi_data
            $kpis_from_json = !empty($log->kpi_data) ? json_decode($log->kpi_data, true) : array();
            if (!is_array($kpis_from_json)) $kpis_from_json = array();

            // Initialize with default values from direct columns (for backward compatibility during transition)
            // $current_kpi_values = [
            //     'boxes_completed' => !is_null($log->boxes_completed) ? intval($log->boxes_completed) : (isset($kpis_from_json['boxes_completed']) ? intval($kpis_from_json['boxes_completed']) : 0),
            //     'items_completed' => !is_null($log->items_completed) ? intval($log->items_completed) : (isset($kpis_from_json['items_completed']) ? intval($kpis_from_json['items_completed']) : 0)
            // ];
            // Merge JSON data over these defaults (JSON is source of truth for custom KPIs)
            // $current_kpi_values = array_merge($current_kpi_values, $kpis_from_json);

            // All KPIs, including potential 'boxes_completed' and 'items_completed', should come from kpi_data (JSON)
            $current_kpi_values = $kpis_from_json;

            $boxes_completed = isset($current_kpi_values['boxes_completed']) ? intval($current_kpi_values['boxes_completed']) : 0;
            $items_completed = isset($current_kpi_values['items_completed']) ? intval($current_kpi_values['items_completed']) : 0;

            // Generic KPI display (example - could be more complex)
            $kpi_display_array = array();
            // if ($boxes_completed > 0) $kpi_display_array[] = "Boxes: $boxes_completed"; // Removed legacy
            // if ($items_completed > 0) $kpi_display_array[] = "Items: $items_completed"; // Removed legacy
            // Loop through other items in $kpis if defined by stream_type kpi_config
            // This part will need significant work once kpi_fields_config is used

            // $time_per_box_seconds = ($boxes_completed > 0 && $duration_seconds > 0) ? round($duration_seconds / $boxes_completed) : 0; 
            // $time_per_item_seconds = ($items_completed > 0 && $duration_seconds > 0) ? round($duration_seconds / $items_completed) : 0; 
            // $boxes_per_hour = ($duration_hours > 0 && $boxes_completed > 0) ? round($boxes_completed / $duration_hours, 2) : 0;
            // $items_per_hour = ($duration_hours > 0 && $items_completed > 0) ? round($items_completed / $duration_hours, 2) : 0;

            $employee_name = esc_html($log->first_name . ' ' . $log->last_name);
            $status_badge = ''; // (status badge logic remains same)
            if ($log->status === 'started') {
                $status_badge = '<span class="dashicons dashicons-clock" style="color:orange; font-size:1.2em; vertical-align:middle;" title="Started"></span> <span style="vertical-align:middle;">' . __('Running', 'operations-organizer') . '</span>';
            } elseif ($log->status === 'completed') {
                $status_badge = '<span class="dashicons dashicons-yes-alt" style="color:green; font-size:1.2em; vertical-align:middle;" title="Completed"></span> <span style="vertical-align:middle;">' . __('Completed', 'operations-organizer') . '</span>';
            } else {
                $status_badge = esc_html(ucfirst($log->status));
            }
            
            // Handle job number display - if job_number is empty but job_id exists, get job number from job record
            $job_number = '';
            
            if (!empty($log->job_number)) {
                $job_number = $log->job_number;
            } elseif (!empty($log->job_id)) {
                // Get job record to retrieve the job number
                oo_log('Job number missing for log ID ' . $log->log_id . '. Fetching from job ID ' . $log->job_id, __METHOD__);
                $job = OO_DB::get_job($log->job_id);
                
                if ($job && !empty($job->job_number)) {
                    $job_number = $job->job_number;
                    oo_log('Found job number ' . $job_number . ' for job ID ' . $log->job_id, __METHOD__);
                    
                    // Update the job_number in the job_logs table for future queries
                    $update_result = OO_DB::update_job_log($log->log_id, array('job_number' => $job_number));
                    
                    if (is_wp_error($update_result)) {
                        oo_log('Error updating job_number for log ID ' . $log->log_id . ': ' . $update_result->get_error_message(), __METHOD__);
                    } else {
                        oo_log('Successfully updated job_number for log ID ' . $log->log_id, __METHOD__);
                    }
                } else {
                    oo_log('Could not find job number for job ID ' . $log->job_id, __METHOD__);
                }
            }
            
            // If we started a job with a job_number directly (not job_id)
            if (empty($job_number) && !empty($log->job_number)) {
                $job_number = $log->job_number;
            }

            $row_data = array(
                'log_id'           => $log->log_id,
                'employee_name'    => $employee_name,
                'job_number'       => esc_html($job_number),
                'stream_name'      => isset($log->stream_name) ? esc_html($log->stream_name) : __('N/A', 'operations-organizer'),
                'phase_name'       => esc_html($log->phase_name),
                'start_time'       => esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->start_time))),
                'end_time'         => $log->end_time ? esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->end_time))) : 'N/A',
                'duration'         => $duration_display,
                // 'kpi_data'         => !empty($kpi_display_array) ? implode(', ', $kpi_display_array) : ( !empty($log->kpi_data) && $log->kpi_data !=='null' && $log->kpi_data !=='[]' ? esc_html($log->kpi_data) : 'N/A'), // Simple display of kpi_data for now - now handled by dynamic columns or direct kpi_data column
                'kpi_data'         => ( !empty($log->kpi_data) && $log->kpi_data !=='null' && $log->kpi_data !=='[]' ? esc_html($log->kpi_data) : 'N/A'),
                // 'time_per_box'     => $time_per_box_seconds > 0 ? sprintf('%02dh %02dm %02ds', floor($time_per_box_seconds/3600), floor(($time_per_box_seconds%3600)/60), ($time_per_box_seconds%60)) : 'N/A',
                // 'time_per_item'    => $time_per_item_seconds > 0 ? sprintf('%02dh %02dm %02ds', floor($time_per_item_seconds/3600), floor(($time_per_item_seconds%3600)/60), ($time_per_item_seconds%60)) : 'N/A',
                // 'boxes_per_hour'   => $boxes_per_hour,
                // 'items_per_hour'   => $items_per_hour,
                'status'           => $status_badge,
                'notes'            => nl2br(esc_html($log->notes)),
            );

            // Add selected dynamic KPI data to the row
            // The keys in $row_data should match the `data` property in DataTable column definitions (e.g., 'kpi_measurekey')
            foreach ($selected_kpi_keys as $kpi_key) {
                $row_data['kpi_' . $kpi_key] = isset($current_kpi_values[$kpi_key]) ? esc_html($current_kpi_values[$kpi_key]) : 'N/A';
            }
            // Ensure legacy 'boxes_completed' and 'items_completed' are available if not in selected_kpi_keys but needed by other parts of JS
            // (e.g. if hardcoded columns for these are still present, or for calculations)
            // This ensures they are in the data object sent to datatables, even if not explicitly selected as a kpi_ column.
            // if (!isset($row_data['kpi_boxes_completed']) && !in_array('boxes_completed', $selected_kpi_keys)) {
            //      $row_data['boxes_completed'] = $boxes_completed; 
            // }
            // if (!isset($row_data['kpi_items_completed']) && !in_array('items_completed', $selected_kpi_keys)) {
            //      $row_data['items_completed'] = $items_completed; 
            // }

            $data[] = $row_data;
        }

        oo_log('Sending dashboard data to DataTables. Draw: ' . $draw . ', Records: ' . count($data), 'Response for ' . __METHOD__);
        
        // Use wp_send_json_success to ensure proper JSON formatting
        wp_send_json_success(array(
            'draw'            => $draw,
            'recordsTotal'    => $total_records,
            'recordsFiltered' => $total_filtered_records,
            'data'            => $data,
        ));
    }

    public static function ajax_get_job_log_details() {
        check_ajax_referer('oo_edit_log_nonce', 'nonce');
        oo_log('AJAX: Get job log details', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(['message' => 'Permission denied.'], 403); return;
        }
        $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
        if ($log_id <= 0) {
            wp_send_json_error(['message' => 'Invalid Log ID.']); return;
        }
        $log_details = OO_DB::get_job_log($log_id);
        if ($log_details) {
            // Make sure job_number is available
            if (empty($log_details->job_number) && !empty($log_details->job_id)) {
                oo_log('Job number missing in log details, fetching from job ID: ' . $log_details->job_id, __METHOD__);
                $job = OO_DB::get_job($log_details->job_id);
                if ($job && !empty($job->job_number)) {
                    $log_details->job_number = $job->job_number;
                    oo_log('Found job number: ' . $log_details->job_number, __METHOD__);
                    
                    // Optionally update the record for future queries
                    OO_DB::update_job_log($log_id, ['job_number' => $log_details->job_number]);
                }
            }
            
            // Format start_time and end_time for the datetime-local input
            if (!empty($log_details->start_time)) {
                try {
                    $start_dt = new DateTime($log_details->start_time, new DateTimeZone(wp_timezone_string()));
                    $log_details->start_time = $start_dt->format('Y-m-d\TH:i');
                    oo_log('Formatted start_time: ' . $log_details->start_time, __METHOD__);
                } catch (Exception $e) {
                    oo_log('Error formatting start_time: ' . $e->getMessage(), __METHOD__);
                }
            }
            
            if (!empty($log_details->end_time)) {
                try {
                    $end_dt = new DateTime($log_details->end_time, new DateTimeZone(wp_timezone_string()));
                    $log_details->end_time = $end_dt->format('Y-m-d\TH:i');
                    oo_log('Formatted end_time: ' . $log_details->end_time, __METHOD__);
                } catch (Exception $e) {
                    oo_log('Error formatting end_time: ' . $e->getMessage(), __METHOD__);
                }
            }
            
            // Prepare kpi_data for form
            if (!empty($log_details->kpi_data)) {
                $kpis = json_decode($log_details->kpi_data, true);
                if (is_array($kpis)) {
                    // If we had specific form fields for kpi_data, we'd populate them here.
                    // For now, we might just pass the raw kpi_data string or decoded array.
                    // The edit form would need to dynamically generate fields based on stream_type kpi_config
                }
            }
            wp_send_json_success($log_details);
        } else {
            wp_send_json_error(['message' => 'Job log not found.']);
        }
    }

    public static function ajax_update_job_log() {
        check_ajax_referer('oo_edit_log_nonce', 'oo_edit_log_nonce_field');
        oo_log('AJAX: Update job log received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        if (!current_user_can(oo_get_capability())) {
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied.'], 403); return;
        }
        $log_id = isset($_POST['edit_log_id']) ? intval($_POST['edit_log_id']) : 0;
        if ($log_id <= 0) {
            oo_log('AJAX Error: Invalid Log ID for update: ' . $log_id, __METHOD__);
            wp_send_json_error(['message' => 'Invalid Log ID for update.']); return;
        }
        $data_to_update = array();
        if (isset($_POST['edit_log_employee_id'])) $data_to_update['employee_id'] = intval($_POST['edit_log_employee_id']);
        
        // Handle job_number validation and job_id update
        $job_id_updated = false;
        if (isset($_POST['edit_log_job_number']) && !empty($_POST['edit_log_job_number'])) {
            $job_number = sanitize_text_field($_POST['edit_log_job_number']);
            
            // Check if job exists with this number
            $job = OO_Job::get_by_job_number($job_number);
            
            if ($job) {
                // Job exists - update both job_number and job_id
                $data_to_update['job_number'] = $job_number;
                $data_to_update['job_id'] = $job->get_id();
                $job_id_updated = true;
                oo_log('Job number validated and job_id updated: ' . $job_number . ' -> ' . $job->get_id(), __METHOD__);
            } else {
                // Job doesn't exist - notify the user
                oo_log('AJAX Error: Job number not found: ' . $job_number, __METHOD__);
                wp_send_json_error(['message' => 'Invalid job number. This job number does not exist in the system.']); 
                return;
            }
        } else if (isset($_POST['edit_log_job_id']) && !$job_id_updated) {
            $data_to_update['job_id'] = intval($_POST['edit_log_job_id']);
        }
        
        if (isset($_POST['edit_log_phase_id'])) $data_to_update['phase_id'] = intval($_POST['edit_log_phase_id']);
        if (isset($_POST['edit_log_stream_id'])) $data_to_update['stream_id'] = intval($_POST['edit_log_stream_id']);
        
        // Handle start_time with timezone
        if (isset($_POST['edit_log_start_time']) && !empty($_POST['edit_log_start_time'])) {
            try { 
                $start_time_dt = new DateTime($_POST['edit_log_start_time'], new DateTimeZone(wp_timezone_string())); 
                $data_to_update['start_time'] = $start_time_dt->format('Y-m-d H:i:s');
                oo_log('Parsed start_time: ' . $_POST['edit_log_start_time'] . ' -> ' . $data_to_update['start_time'], __METHOD__);
            }
            catch (Exception $e) { 
                oo_log('AJAX Error: Invalid start_time format: ' . $_POST['edit_log_start_time'] . ' - ' . $e->getMessage(), __METHOD__); 
                wp_send_json_error(['message' => 'Invalid Start Time format.']); 
                return; 
            }
        } 
        
        // Handle end_time with timezone
        if (isset($_POST['edit_log_end_time'])) {
            if (!empty($_POST['edit_log_end_time'])) {
                try { 
                    $end_time_dt = new DateTime($_POST['edit_log_end_time'], new DateTimeZone(wp_timezone_string())); 
                    $data_to_update['end_time'] = $end_time_dt->format('Y-m-d H:i:s');
                    oo_log('Parsed end_time: ' . $_POST['edit_log_end_time'] . ' -> ' . $data_to_update['end_time'], __METHOD__);
                    
                    // If we're setting end_time, make sure status is completed
                    if (empty($data_to_update['status'])) {
                        $data_to_update['status'] = 'completed';
                    }
                    
                    // Get the start time for verification
                    $current_log = isset($current_log) ? $current_log : OO_DB::get_job_log($log_id);
                    $start_time = isset($data_to_update['start_time']) ? $data_to_update['start_time'] : $current_log->start_time;
                    
                    // Verify end time is not before start time
                    $start_dt = new DateTime($start_time, new DateTimeZone(wp_timezone_string()));
                    if ($end_time_dt < $start_dt) {
                        oo_log('Warning: End time is before start time. Adjusting to ensure valid duration.', __METHOD__);
                        // Make end time at least equal to start time
                        $data_to_update['end_time'] = $start_time;
                    }
                }
                catch (Exception $e) { 
                    oo_log('AJAX Error: Invalid end_time format: ' . $_POST['edit_log_end_time'] . ' - ' . $e->getMessage(), __METHOD__); 
                    wp_send_json_error(['message' => 'Invalid End Time format.']); 
                    return; 
                }
            } else { 
                $data_to_update['end_time'] = null; 
                
                // If we're clearing end_time, make sure status is started
                if (empty($data_to_update['status'])) {
                    $data_to_update['status'] = 'started';
                }
            }
        }
        
        // Get current log for KPI data updates
        $current_log = isset($current_log) ? $current_log : OO_DB::get_job_log($log_id);
        
        // Handle boxes_completed and items_completed updates
        $kpi_data = !empty($current_log->kpi_data) ? json_decode($current_log->kpi_data, true) : array();
        if (!is_array($kpi_data)) $kpi_data = array();
        
        // Handle boxes_completed
        if (isset($_POST['edit_log_boxes_completed'])) {
            $boxes_completed_value = !empty($_POST['edit_log_boxes_completed']) || $_POST['edit_log_boxes_completed'] === '0' ? 
                intval($_POST['edit_log_boxes_completed']) : null;
            
            // $data_to_update['boxes_completed'] = $boxes_completed_value; // Do not update dedicated column
            if (!is_null($boxes_completed_value)) { // Only add to kpi_data if a value is provided
                $kpi_data['boxes_completed'] = $boxes_completed_value;
            } else {
                unset($kpi_data['boxes_completed']); // Remove if cleared
            }
        }
        
        // Handle items_completed
        if (isset($_POST['edit_log_items_completed'])) {
            $items_completed_value = !empty($_POST['edit_log_items_completed']) || $_POST['edit_log_items_completed'] === '0' ? 
                intval($_POST['edit_log_items_completed']) : null;
            
            // $data_to_update['items_completed'] = $items_completed_value; // Do not update dedicated column
            if (!is_null($items_completed_value)) { // Only add to kpi_data if a value is provided
                $kpi_data['items_completed'] = $items_completed_value;
            } else {
                unset($kpi_data['items_completed']); // Remove if cleared
            }
        }
        
        // Update KPI data if we have changes
        if (!empty($kpi_data)) {
            $data_to_update['kpi_data'] = json_encode($kpi_data);
        }

        if (isset($_POST['edit_log_status'])) $data_to_update['status'] = sanitize_text_field($_POST['edit_log_status']);
        if (isset($_POST['edit_log_notes'])) $data_to_update['notes'] = sanitize_textarea_field($_POST['edit_log_notes']);
        
        if (empty($data_to_update['employee_id']) || empty($data_to_update['job_id']) || empty($data_to_update['phase_id']) || empty($data_to_update['start_time']) || empty($data_to_update['status'])) {
            oo_log('AJAX Error: Missing required fields for log update.', $data_to_update);
            wp_send_json_error(['message' => 'Missing required fields for log update (Employee, Job, Phase, Start Time, Status).']); return;
        }
        
        oo_log('Data prepared for DB update_job_log: ', $data_to_update);
        $result = OO_DB::update_job_log($log_id, $data_to_update);
        if (is_wp_error($result)) {
            oo_log('AJAX Error from DB update_job_log: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(['message' => 'Error updating job log: ' . $result->get_error_message()]);
        } else {
            oo_log('AJAX Success: Job log updated. Log ID: ' . $log_id, __METHOD__);
            wp_send_json_success(['message' => 'Job log updated successfully.']);
        }
    }

    public static function ajax_delete_job_log() {
        check_ajax_referer('oo_delete_log_nonce', 'nonce');
        oo_log('AJAX: Delete job log request received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        if (!current_user_can(oo_get_capability())) { 
            oo_log('AJAX Error: Permission denied for deleting job log.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied.'], 403); return;
        }
        $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
        if ($log_id <= 0) {
            oo_log('AJAX Error: Invalid Log ID for deletion: ' . $log_id, __METHOD__);
            wp_send_json_error(['message' => 'Invalid Log ID for deletion.']); return;
        }
        $result = OO_DB::delete_job_log($log_id);
        if (is_wp_error($result)) {
            oo_log('AJAX Error from DB delete_job_log: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(['message' => 'Error deleting job log: ' . $result->get_error_message()]);
        } else {
            oo_log('AJAX Success: Job log deleted. Log ID: ' . $log_id, __METHOD__);
            wp_send_json_success(['message' => 'Job log deleted successfully.']);
        }
    }
    
    /**
     * Display debug information about the job_logs table structure
     */
    public static function display_table_debug_info() {
        // Only run in admin area for administrators
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'oo_job_logs';
        
        // Get all columns in the table
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table}");
        
        if (empty($columns)) {
            $message = 'Error: Unable to get job_logs table columns. Table may not exist.';
        } else {
            $column_names = array_map(function($col) { return $col->Field; }, $columns);
            
            $message = '<strong>Job Logs Table Structure Debug</strong><br>';
            $message .= 'Table: ' . $table . '<br>';
            $message .= 'Columns: ' . implode(', ', $column_names) . '<br>';
            
            // Check for presence of important columns
            $has_end_time = in_array('end_time', $column_names) ? 'Yes' : 'No';
            $has_stop_time = in_array('stop_time', $column_names) ? 'Yes' : 'No';
            $has_job_number = in_array('job_number', $column_names) ? 'Yes' : 'No';
            
            $message .= 'Has end_time column: ' . $has_end_time . '<br>';
            $message .= 'Has stop_time column: ' . $has_stop_time . '<br>';
            $message .= 'Has job_number column: ' . $has_job_number . '<br>';
            
            // If needed, add a fix button
            if ($has_stop_time === 'Yes' || $has_end_time === 'No' || $has_job_number === 'No') {
                $message .= '<p><a href="' . admin_url('admin.php?page=oo_dashboard&tab=content&action=fix_columns') . '" class="button button-primary">Fix Table Structure</a></p>';
            }
        }
        
        echo '<div class="notice notice-info is-dismissible"><p>' . $message . '</p></div>';
    }
    
    /**
     * Initialize our debug features
     */
    public static function init_debug() {
        // Add debug notice if needed
        add_action('admin_notices', [self::class, 'display_table_debug_info']);
        
        // Handle the fix_columns action
        if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'oo_dashboard' && 
            isset($_GET['action']) && $_GET['action'] === 'fix_columns') {
            
            // Run our column fixes
            OO_DB::check_job_number_column(); // This will also run check_time_columns()
            
            // Redirect to remove the action from URL
            wp_redirect(admin_url('admin.php?page=oo_dashboard&tab=content&fixed=1'));
            exit;
        }
    }
} 

// Initialize debug features 
add_action('admin_init', ['OO_Dashboard', 'init_debug']); 