<?php
// /features/feature-sets-management/migration.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Feature_Sets_Migration {
    
    public static function run_initial_migration() {
        oo_log('Starting feature sets initial migration', __METHOD__);
        
        try {
            // Create default operational tools feature set
            $operational_tools_id = self::create_operational_tools_feature_set();
            
            if (is_wp_error($operational_tools_id)) {
                oo_log('Failed to create operational tools feature set: ' . $operational_tools_id->get_error_message(), __METHOD__);
                return $operational_tools_id;
            }
            
            // Assign operational tools to all existing active streams
            $assigned_count = self::assign_operational_tools_to_streams($operational_tools_id);
            
            if (is_wp_error($assigned_count)) {
                oo_log('Failed to assign operational tools to streams: ' . $assigned_count->get_error_message(), __METHOD__);
                return $assigned_count;
            }
            
            oo_log('Feature sets migration completed successfully. Assigned operational tools to ' . $assigned_count . ' streams.', __METHOD__);
            
            return array(
                'success' => true,
                'message' => sprintf(
                    __('Feature sets migration completed successfully. Created operational tools feature set and assigned it to %d streams.', 'operations-organizer'),
                    $assigned_count
                )
            );
            
        } catch (Exception $e) {
            oo_log('Feature sets migration failed with exception: ' . $e->getMessage(), __METHOD__);
            return new WP_Error('migration_failed', 'Migration failed: ' . $e->getMessage());
        }
    }
    
    private static function create_operational_tools_feature_set() {
        // Check if operational tools feature set already exists
        $existing = OO_DB::get_feature_set_by_slug('operational_tools');
        
        if ($existing) {
            oo_log('Operational tools feature set already exists with ID: ' . $existing->feature_set_id, __METHOD__);
            return $existing->feature_set_id;
        }
        
        // Create the operational tools feature set
        $result = OO_DB::add_feature_set(
            'Operational Tools',
            'operational_tools',
            'Core operational functionality including Phase Log Actions, Phase Dashboard, Phase & KPI Settings, inventory management, job tracking, quality control, and basic reporting.',
            1, // Active
            0  // Sort order (first)
        );
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        oo_log('Created operational tools feature set with ID: ' . $result, __METHOD__);
        return $result;
    }
    
    private static function assign_operational_tools_to_streams($feature_set_id) {
        oo_log('MIGRATION_DEBUG: Starting assign_operational_tools_to_streams with feature_set_id: ' . $feature_set_id, __METHOD__);
        
        // Get all active streams
        $streams = oo_get_streams(array('is_active' => 1));
        oo_log('MIGRATION_DEBUG: Found ' . count($streams) . ' active streams', __METHOD__);
        
        if (empty($streams)) {
            oo_log('MIGRATION_DEBUG: No active streams found to assign operational tools to', __METHOD__);
            return 0;
        }
        
        $assigned_count = 0;
        $errors = array();
        
        foreach ($streams as $stream) {
            oo_log('MIGRATION_DEBUG: Processing stream: ' . $stream->stream_name . ' (ID: ' . $stream->stream_id . ')', __METHOD__);
            
            // Check if already assigned
            $existing_assignment = OO_DB::get_feature_sets_for_stream($stream->stream_id, 1);
            $already_assigned = false;
            
            oo_log('MIGRATION_DEBUG: Found ' . count($existing_assignment) . ' existing assignments for stream ' . $stream->stream_id, __METHOD__);
            
            foreach ($existing_assignment as $assigned_fs) {
                oo_log('MIGRATION_DEBUG: Existing assignment - Feature Set ID: ' . $assigned_fs->feature_set_id . ', Name: ' . $assigned_fs->name, __METHOD__);
                if ($assigned_fs->feature_set_id == $feature_set_id) {
                    $already_assigned = true;
                    oo_log('MIGRATION_DEBUG: Operational tools already assigned to stream: ' . $stream->stream_name, __METHOD__);
                    break;
                }
            }
            
            if ($already_assigned) {
                $assigned_count++;
                continue;
            }
            
            // Assign operational tools to this stream
            oo_log('MIGRATION_DEBUG: Assigning operational tools to stream: ' . $stream->stream_name, __METHOD__);
            $result = OO_DB::assign_feature_set_to_stream($stream->stream_id, $feature_set_id);
            
            if (is_wp_error($result)) {
                $error_msg = sprintf(
                    'Failed to assign operational tools to stream "%s": %s',
                    $stream->stream_name,
                    $result->get_error_message()
                );
                oo_log('MIGRATION_DEBUG: ERROR - ' . $error_msg, __METHOD__);
                $errors[] = $error_msg;
            } else {
                oo_log('MIGRATION_DEBUG: SUCCESS - Assigned operational tools to stream: ' . $stream->stream_name . ', result: ' . $result, __METHOD__);
                $assigned_count++;
            }
        }
        
        oo_log('MIGRATION_DEBUG: Migration completed - assigned to ' . $assigned_count . ' streams, ' . count($errors) . ' errors', __METHOD__);
        
        if (!empty($errors)) {
            oo_log('MIGRATION_DEBUG: Migration errors: ' . json_encode($errors), __METHOD__);
            return new WP_Error('assignment_errors', 'Some assignments failed: ' . implode('; ', $errors));
        }
        
        return $assigned_count;
    }
    
    /**
     * Check if feature sets migration is needed
     */
    public static function is_migration_needed() {
        // Check if operational tools feature set exists
        $operational_tools = OO_DB::get_feature_set_by_slug('operational_tools');
        
        if (!$operational_tools) {
            return true;
        }
        
        // Check if any streams are missing the operational tools assignment
        $streams = oo_get_streams(array('is_active' => 1));
        
        if (empty($streams)) {
            return false;
        }
        
        foreach ($streams as $stream) {
            $assigned_feature_sets = OO_DB::get_feature_sets_for_stream($stream->stream_id, 1);
            $has_operational_tools = false;
            
            foreach ($assigned_feature_sets as $assigned_fs) {
                if ($assigned_fs->feature_set_id == $operational_tools->feature_set_id) {
                    $has_operational_tools = true;
                    break;
                }
            }
            
            if (!$has_operational_tools) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get migration status information
     */
    public static function get_migration_status() {
        $operational_tools = OO_DB::get_feature_set_by_slug('operational_tools');
        $streams = oo_get_streams(array('is_active' => 1));
        
        $status = array(
            'operational_tools_exists' => !empty($operational_tools),
            'operational_tools_id' => $operational_tools ? $operational_tools->feature_set_id : null,
            'total_streams' => count($streams),
            'streams_with_operational_tools' => 0,
            'streams_without_operational_tools' => array()
        );
        
        if ($operational_tools && !empty($streams)) {
            foreach ($streams as $stream) {
                $assigned_feature_sets = OO_DB::get_feature_sets_for_stream($stream->stream_id, 1);
                $has_operational_tools = false;
                
                foreach ($assigned_feature_sets as $assigned_fs) {
                    if ($assigned_fs->feature_set_id == $operational_tools->feature_set_id) {
                        $has_operational_tools = true;
                        break;
                    }
                }
                
                if ($has_operational_tools) {
                    $status['streams_with_operational_tools']++;
                } else {
                    $status['streams_without_operational_tools'][] = $stream->stream_name;
                }
            }
        }
        
        return $status;
    }
} 