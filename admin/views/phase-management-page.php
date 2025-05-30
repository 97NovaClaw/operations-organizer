<?php
// /admin/views/phase-management-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure OO_DB class is available
if ( ! class_exists( 'OO_DB' ) ) {
    echo '<div class="error"><p>Operations Organizer: OO_DB class not found. Please ensure the plugin is installed correctly.</p></div>';
    return;
}

$action_message = '';

// Handle main KPI Measure delete action (This was a copy-paste error, should be Phase delete)
// Handle Phase delete action
if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_phase' && isset( $_GET['phase_id'] ) ) {
    $phase_id_to_delete = intval( $_GET['phase_id'] );
    if ( check_admin_referer( 'oo_delete_phase_nonce_' . $phase_id_to_delete ) ) {
        $job_logs_using_phase = OO_DB::get_job_logs_count(array('phase_id' => $phase_id_to_delete, 'number' => 1));

        if ($job_logs_using_phase > 0) {
            $delete_logs_and_phase_url = wp_nonce_url( admin_url( 'admin.php?page=oo_phases&action=delete_phase_and_logs&phase_id=' . $phase_id_to_delete ), 'oo_delete_phase_and_logs_nonce_' . $phase_id_to_delete );
            $action_message = '<div class="notice notice-error is-dismissible"><p>' . 
                sprintf(esc_html__('Cannot delete phase. It is currently associated with %d job log(s).', 'operations-organizer'), $job_logs_using_phase) . 
                ' ' . esc_html__('Please reassign or delete these logs first.', 'operations-organizer') . 
                '<br><a href="' . esc_url($delete_logs_and_phase_url) . '" class="button button-link-delete" style="margin-top:10px;" onclick="return confirm(\''.esc_attr__('WARNING: This will delete ALL %s job logs associated with this phase AND then delete the phase itself. This action cannot be undone. Are you absolutely sure?', 'operations-organizer').replace('%s', ''.$job_logs_using_phase.'') .'\');">' . 
                sprintf(esc_html__('Delete %d Job Logs & This Phase', 'operations-organizer'), $job_logs_using_phase) . 
                '</a></p></div>';
            $_GET['action'] = null; 
            unset($_GET['phase_id']);
        } else {
            OO_DB::delete_phase_kpi_links_for_phase($phase_id_to_delete); 
            $result = OO_DB::delete_phase( $phase_id_to_delete );
            $redirect_url = admin_url( 'admin.php?page=oo_phases' );
            if ( is_wp_error( $result ) ) {
                $redirect_url = add_query_arg( array('message' => 'phase_delete_error', 'error_code' => urlencode($result->get_error_message())), $redirect_url );
            } else {
                $redirect_url = add_query_arg( array('message' => 'phase_deleted'), $redirect_url );
            }
            wp_redirect($redirect_url);
            exit;
        }
    } else {
        $action_message = '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Could not delete phase.', 'operations-organizer' ) . '</p></div>';
    }
} elseif ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_phase_and_logs' && isset( $_GET['phase_id'] ) ) {
    $phase_id_to_delete_with_logs = intval( $_GET['phase_id'] );
    if ( check_admin_referer( 'oo_delete_phase_and_logs_nonce_' . $phase_id_to_delete_with_logs ) ) {
        $logs_deleted_result = OO_DB::delete_job_logs_for_phase($phase_id_to_delete_with_logs);
        
        if (is_wp_error($logs_deleted_result)) {
            $action_message = '<div class="notice notice-error is-dismissible"><p>' . sprintf(esc_html__('Error deleting job logs for phase: %s', 'operations-organizer'), $logs_deleted_result->get_error_message()) . '</p></div>';
            // Stay on the page to show the error, ensure $_GET params are set for list view
            $_GET['action'] = null;
            unset($_GET['phase_id']);
        } else {
            // Logs deleted successfully (or no logs to delete, $logs_deleted_result would be >= 0)
            oo_log('Successfully deleted ' . $logs_deleted_result . ' job logs for phase ID: ' . $phase_id_to_delete_with_logs, 'PhaseManagement');
            
            OO_DB::delete_phase_kpi_links_for_phase($phase_id_to_delete_with_logs);
            oo_log('Deleted KPI links for phase ID: ' . $phase_id_to_delete_with_logs, 'PhaseManagement');

            $phase_delete_result = OO_DB::delete_phase( $phase_id_to_delete_with_logs );
            oo_log('Phase deletion result for ID ' . $phase_id_to_delete_with_logs . ': ', $phase_delete_result);
            
            $redirect_url = admin_url( 'admin.php?page=oo_phases' );
            if ( is_wp_error( $phase_delete_result ) ) {
                $redirect_url = add_query_arg( array('message' => 'phase_delete_error', 'error_code' => urlencode($phase_delete_result->get_error_message())), $redirect_url );
            } else {
                $redirect_url = add_query_arg( array('message' => 'phase_and_logs_deleted', 'logs_deleted' => $logs_deleted_result), $redirect_url );
            }
            wp_redirect($redirect_url);
            exit;
        }
    } else {
        $action_message = '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Could not delete phase and logs.', 'operations-organizer' ) . '</p></div>';
    }
}

// Handle toggle status action for main Phase
if ( isset( $_GET['action'] ) && $_GET['action'] === 'toggle_phase_status' && isset( $_GET['phase_id'] ) && isset($_GET['_wpnonce']) ) {
    $phase_id_to_toggle = intval( $_GET['phase_id'] );
    if ( wp_verify_nonce( $_GET['_wpnonce'], 'oo_toggle_phase_status_nonce_' . $phase_id_to_toggle ) ) {
        $phase = OO_DB::get_phase( $phase_id_to_toggle );
        if ( $phase ) {
            $new_status = $phase->is_active ? 0 : 1;
            OO_DB::toggle_phase_status( $phase_id_to_toggle, $new_status );
            $redirect_url = admin_url( 'admin.php?page=oo_phases' );
            $redirect_url = add_query_arg( array('message' => 'phase_status_updated'), $redirect_url );
            wp_redirect($redirect_url);
            exit;
        }
    } else {
         $action_message = '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Could not toggle phase status.', 'operations-organizer' ) . '</p></div>';
    }
}

// Display messages based on GET parameters
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        // ... existing cases ...
        case 'phase_deleted':
            $action_message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Phase deleted successfully.', 'operations-organizer' ) . '</p></div>';
            break;
        case 'phase_delete_error':
            $error_message = isset($_GET['error_code']) ? esc_html(urldecode($_GET['error_code'])) : esc_html__( 'Unknown error.', 'operations-organizer' );
            $action_message = '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Error deleting phase:', 'operations-organizer' ) . ' ' . $error_message . '</p></div>';
            break;
        case 'phase_and_logs_deleted':
            $logs_count = isset($_GET['logs_deleted']) ? intval($_GET['logs_deleted']) : 0;
            $action_message = '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('Successfully deleted phase and %d associated job log(s).', 'operations-organizer'), $logs_count) . '</p></div>';
            break;
        case 'phase_status_updated':
            $action_message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Phase status updated successfully.', 'operations-organizer' ) . '</p></div>';
            break;
    }
}

global $phases, $total_phases, $current_page, $per_page, $search_term, $active_filter;
// Fetch data for the table view if not already populated by OO_Admin_Pages
// This ensures data is available if we land here after a redirect or error on this page itself.
if (empty($phases)) { 
    OO_Admin_Pages::prepare_phase_list_data();
}

?>
<div class="wrap oo-phase-management-page">
    <h1><?php esc_html_e( 'Job Phase Management', 'operations-organizer' ); ?></h1>
    
    <?php echo $action_message; // Display any action messages ?>

    <div class="oo-notice oo-info">
        <p><?php esc_html_e('Phases are specific to each stream type. Each phase belongs to one stream and will appear in that stream\'s tab on the dashboard.', 'operations-organizer'); ?></p>
        <p><?php esc_html_e('For the best organization, make sure to assign each phase to the appropriate stream type using the selector below.', 'operations-organizer'); ?></p>
    </div>

    <button id="openAddOOPhaseModalBtn" class="page-title-action oo-open-modal-button" data-modal-id="addOOPhaseModal"><?php esc_html_e( 'Add New Phase', 'operations-organizer' ); ?></button>

    <form method="get" class="oo-filters-form">
        <input type="hidden" name="page" value="oo_phases" />
        <div class="wp-filter">
            <div class="filter-items">
                <label for="stream_id_filter" class="stream-type-filter-label"><?php esc_html_e('Filter by Stream Type:', 'operations-organizer');?></label>
                <select name="stream_id_filter" id="stream_id_filter">
                    <option value=""><?php esc_html_e('All Stream Types', 'operations-organizer');?></option>
                    <?php foreach ($GLOBALS['streams'] as $st) : ?>
                        <option value="<?php echo esc_attr($st->stream_id); ?>" <?php selected($GLOBALS['selected_stream_id'], $st->stream_id); ?>><?php echo esc_html($st->stream_name); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="status_filter" class="screen-reader-text"><?php esc_html_e('Filter by status', 'operations-organizer');?></label>
                <select name="status_filter" id="status_filter">
                    <option value="all" <?php selected($active_filter, 'all'); ?>><?php esc_html_e('All Statuses', 'operations-organizer');?></option>
                    <option value="active" <?php selected($active_filter, 'active'); ?>><?php esc_html_e('Active', 'operations-organizer');?></option>
                    <option value="inactive" <?php selected($active_filter, 'inactive'); ?>><?php esc_html_e('Inactive', 'operations-organizer');?></option>
                </select>
                <input type="submit" name="filter_action" class="button" value="<?php esc_attr_e('Filter', 'operations-organizer');?>">
            </div>
            <p class="search-box">
                <label class="screen-reader-text" for="phase-search-input"><?php esc_html_e( 'Search Phases:', 'operations-organizer' ); ?></label>
                <input type="search" id="phase-search-input" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search by Name/Description', 'operations-organizer' ); ?>" />
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Phases', 'operations-organizer' ); ?>" />
            </p>
        </div>
    </form>
    <div class="clear"></div>

    <div id="oo-phase-list-table-wrapper">
        <table class="wp-list-table widefat fixed striped table-view-list phases">
            <thead>
                <tr>
                    <?php 
                    $columns = [
                        'phase_name' => __('Phase Name', 'operations-organizer'),
                        'phase_slug' => __('Slug', 'operations-organizer'),
                        'stream_type_name' => __('Stream Type', 'operations-organizer'),
                        'phase_description' => __('Description', 'operations-organizer'),
                        'sort_order' => __('Order', 'operations-organizer'),
                        'includes_kpi' => __('Includes KPIs', 'operations-organizer'),
                        'manage_kpis' => __('Manage KPIs', 'operations-organizer'),
                        'status' => __('Status', 'operations-organizer'),
                        'actions' => __('Actions', 'operations-organizer'),
                    ];
                    $current_orderby = isset($GLOBALS['orderby']) ? $GLOBALS['orderby'] : 'phase_name';
                    $current_order = isset($GLOBALS['order']) ? strtolower($GLOBALS['order']) : 'asc';

                    foreach($columns as $slug => $title) {
                        $class = "manage-column column-$slug";
                        $sort_link = '';
                        if ($slug === 'phase_name') { // Sortable column
                            $order = ($current_orderby == $slug && $current_order == 'asc') ? 'desc' : 'asc';
                            $class .= $current_orderby == $slug ? " sorted $current_order" : " sortable $order";
                            $sort_link_url = add_query_arg(['orderby' => $slug, 'order' => $order]);
                            $sort_link = "<a href=\"".esc_url($sort_link_url)."\"><span>$title</span><span class=\"sorting-indicator\"></span></a>";
                        } else {
                            $sort_link = $title;
                        }
                        echo "<th scope=\"col\" id=\"$slug\" class=\"$class\">$sort_link</th>";
                    }
                    ?>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php if ( ! empty( $phases ) ) : ?>
                    <?php foreach ( $phases as $phase ) : ?>
                        <tr id="phase-<?php echo $phase->phase_id; ?>" class="<?php echo $phase->is_active ? 'active' : 'inactive'; ?>">
                            <td class="phase_name column-phase_name" data-colname="<?php esc_attr_e('Phase Name', 'operations-organizer'); ?>">
                                <?php echo esc_html( $phase->phase_name ); ?>
                            </td>
                            <td class="phase_slug column-phase_slug" data-colname="<?php esc_attr_e('Slug', 'operations-organizer'); ?>">
                                <?php echo esc_html( $phase->phase_slug ); ?>
                            </td>
                            <td class="stream_type_name column-stream_type_name" data-colname="<?php esc_attr_e('Stream Type', 'operations-organizer'); ?>">
                                <?php echo isset($phase->stream_name) ? esc_html($phase->stream_name) : 'N/A'; ?>
                            </td>
                            <td class="phase_description column-phase_description" data-colname="<?php esc_attr_e('Description', 'operations-organizer'); ?>">
                                <?php echo esc_html( $phase->phase_description ); ?>
                            </td>
                            <td class="sort_order column-sort_order" data-colname="<?php esc_attr_e('Order', 'operations-organizer'); ?>">
                                <?php echo intval( $phase->sort_order ); ?>
                            </td>
                            <td class="includes_kpi column-includes_kpi" data-colname="<?php esc_attr_e('Includes KPIs', 'operations-organizer'); ?>">
                                <?php if ( $phase->includes_kpi ) : ?>
                                    <span style="color: green;"><?php esc_html_e( 'Yes', 'operations-organizer' ); ?></span>
                                <?php else : ?>
                                    <span style="color: red;"><?php esc_html_e( 'No', 'operations-organizer' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="manage_kpis column-manage_kpis" data-colname="<?php esc_attr_e('Manage KPIs', 'operations-organizer'); ?>">
                                <?php if ( $phase->includes_kpi ) : ?>
                                    <button class="button button-small oo-manage-phase-kpis-button" 
                                            data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>" 
                                            data-phase-name="<?php echo esc_attr( $phase->phase_name ); ?>">
                                        <?php esc_html_e('Manage KPIs', 'operations-organizer'); ?>
                                    </button>
                                <?php else : ?>
                                    <?php esc_html_e( 'N/A', 'operations-organizer' ); ?>
                                <?php endif; ?>
                            </td>
                            <td class="status column-status" data-colname="<?php esc_attr_e('Status', 'operations-organizer'); ?>">
                                <?php if ( $phase->is_active ) : ?>
                                    <span style="color: green;"><?php esc_html_e( 'Active', 'operations-organizer' ); ?></span>
                                <?php else : ?>
                                    <span style="color: red;"><?php esc_html_e( 'Inactive', 'operations-organizer' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="actions column-actions" data-colname="<?php esc_attr_e('Actions', 'operations-organizer'); ?>">
                                <button class="button-secondary oo-edit-phase-button" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>"><?php esc_html_e('Edit', 'operations-organizer'); ?></button>
                                <?php if ( $phase->is_active ) : ?>
                                    <button class="button-secondary oo-toggle-status-phase-button oo-deactivate" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>" data-new-status="0" data-nonce="<?php echo wp_create_nonce('oo_toggle_phase_status_nonce_' . $phase->phase_id); ?>"><?php esc_html_e('Deactivate', 'operations-organizer'); ?></button>
                                <?php else : ?>
                                    <button class="button-secondary oo-toggle-status-phase-button oo-activate" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>" data-new-status="1" data-nonce="<?php echo wp_create_nonce('oo_toggle_phase_status_nonce_' . $phase->phase_id); ?>"><?php esc_html_e('Activate', 'operations-organizer'); ?></button>
                                <?php endif; ?>
                                | <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=oo_phases&action=delete_phase&phase_id=' . $phase->phase_id ), 'oo_delete_phase_nonce_' . $phase->phase_id ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to permanently delete this phase? This action cannot be undone and may affect existing job logs if not handled carefully by the system.', 'operations-organizer' ); ?>');" style="color:#b32d2e;"><?php esc_html_e( 'Delete', 'operations-organizer' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="<?php echo count($columns); ?>"><?php esc_html_e( 'No phases found.', 'operations-organizer' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <?php 
                    foreach($columns as $slug => $title) {
                        echo "<th scope=\"col\" class=\"manage-column column-$slug\">$title</th>";
                    }
                    ?>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Pagination -->
    <?php
    if ( $total_phases > $per_page ) {
        $base_url = remove_query_arg(array('paged', 'filter_action'), wp_unslash($_SERVER['REQUEST_URI']));
        $page_links = paginate_links( array(
            'base' => $base_url . '%_%',
            'format' => '&paged=%#%', 
            'prev_text' => __( '&laquo; Previous' ),
            'next_text' => __( 'Next &raquo;' ),
            'total' => ceil( $total_phases / $per_page ),
            'current' => $current_page,
            'add_args' => array_map('urlencode', array_filter(compact('s', 'status_filter', 'orderby', 'order')))
        ) );

        if ( $page_links ) {
            echo "<div class=\"tablenav\"><div class=\"tablenav-pages\">$page_links</div></div>";
        }
    }
    ?>

    <!-- Add Phase Modal -->
    <div id="addOOPhaseModal" class="oo-modal" style="display:none;">
        <div class="oo-modal-content">
            <span class="oo-close-button">&times;</span>
            <h2><?php esc_html_e( 'Add New Phase', 'operations-organizer' ); ?></h2>
            <div class="oo-notice oo-info">
                <p><?php esc_html_e('Remember: Each phase must be assigned to a specific stream type.', 'operations-organizer'); ?></p>
                <p><?php esc_html_e('This phase will appear in the corresponding stream tab on the dashboard.', 'operations-organizer'); ?></p>
            </div>
            <form id="oo-add-phase-form">
                <?php wp_nonce_field( 'oo_add_phase_nonce', 'oo_add_phase_nonce' ); ?>
                <table class="form-table oo-form-table">
                    <tr valign="top">
                        <th scope="row"><label for="add_stream_type_id"><?php esc_html_e( 'Stream Type', 'operations-organizer' ); ?></label></th>
                        <td>
                            <select id="add_stream_type_id" name="stream_type_id" required>
                                <option value=""><?php esc_html_e('-- Select Stream Type --', 'operations-organizer'); ?></option>
                                <?php foreach ($GLOBALS['streams'] as $st) : ?>
                                    <option value="<?php echo esc_attr($st->stream_id); ?>"><?php echo esc_html($st->stream_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Choose the stream this phase belongs to.', 'operations-organizer'); ?></p>
                        </td>
                    </tr>
                    <input type="hidden" id="add_phase_slug" name="phase_slug" value="">
                    <tr valign="top">
                        <th scope="row"><label for="add_phase_name"><?php esc_html_e( 'Phase Name', 'operations-organizer' ); ?></label></th>
                        <td><input type="text" id="add_phase_name" name="phase_name" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="add_phase_description"><?php esc_html_e( 'Description', 'operations-organizer' ); ?></label></th>
                        <td><textarea id="add_phase_description" name="phase_description"></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="add_sort_order"><?php esc_html_e( 'Sort Order', 'operations-organizer' ); ?></label></th>
                        <td><input type="number" id="add_sort_order" name="sort_order" value="0" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Operations KPIs', 'operations-organizer' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="add_includes_kpi" name="includes_kpi" value="1" checked>
                                <?php esc_html_e( 'Includes operations KPIs', 'operations-organizer' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e('If checked, this phase will appear in the stream page and users can input tracking data.', 'operations-organizer'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Add Phase', 'operations-organizer' ), 'primary', 'submit_add_phase' ); ?>
            </form>
        </div>
    </div>

    <!-- Edit Phase Modal -->
    <div id="editOOPhaseModal" class="oo-modal" style="display:none;">
        <div class="oo-modal-content">
            <span class="oo-close-button">&times;</span>
            <h2><?php esc_html_e( 'Edit Phase', 'operations-organizer' ); ?></h2>
            <div class="oo-notice oo-info">
                <p><?php esc_html_e('Each phase belongs to a specific stream type. Changing the stream type will move this phase to a different stream tab.', 'operations-organizer'); ?></p>
            </div>
            <form id="oo-edit-phase-form">
                <?php wp_nonce_field( 'oo_edit_phase_nonce', 'oo_edit_phase_nonce' ); ?>
                <input type="hidden" id="edit_phase_id" name="edit_phase_id" value="" />
                <input type="hidden" id="edit_modal_phase_slug" name="edit_phase_slug" value="" />
                <table class="form-table oo-form-table">
                    <tr valign="top">
                        <th scope="row"><label for="edit_modal_stream_type_id"><?php esc_html_e( 'Stream Type', 'operations-organizer' ); ?></label></th>
                        <td>
                            <select id="edit_modal_stream_type_id" name="edit_stream_type_id" required>
                                <option value=""><?php esc_html_e('-- Select Stream Type --', 'operations-organizer'); ?></option>
                                <?php foreach ($GLOBALS['streams'] as $st) : ?>
                                    <option value="<?php echo esc_attr($st->stream_id); ?>"><?php echo esc_html($st->stream_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Choose the stream this phase belongs to.', 'operations-organizer'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="edit_modal_phase_name"><?php esc_html_e( 'Phase Name', 'operations-organizer' ); ?></label></th>
                        <td><input type="text" id="edit_modal_phase_name" name="edit_phase_name" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="edit_modal_phase_description"><?php esc_html_e( 'Description', 'operations-organizer' ); ?></label></th>
                        <td><textarea id="edit_modal_phase_description" name="edit_phase_description"></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="edit_modal_sort_order"><?php esc_html_e( 'Sort Order', 'operations-organizer' ); ?></label></th>
                        <td><input type="number" id="edit_modal_sort_order" name="edit_sort_order" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Operations KPIs', 'operations-organizer' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="edit_modal_includes_kpi" name="edit_includes_kpi" value="1">
                                <?php esc_html_e( 'Includes operations KPIs', 'operations-organizer' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'If checked, this phase will appear in the stream page and users can input tracking data.', 'operations-organizer' ); ?></p>
                        </td>
                    </tr>
                    
                    <!-- New Section for Managing Linked KPIs -->
                    <tr valign="top" class="oo-kpi-linking-section">
                        <th scope="row"><?php esc_html_e( 'Linked KPI Measures', 'operations-organizer' ); ?></th>
                        <td>
                            <div id="linked-kpi-measures-list">
                                <!-- Linked KPIs will be loaded here by JavaScript -->
                                <p><?php esc_html_e( 'Loading linked KPIs...', 'operations-organizer' ); ?></p>
                            </div>
                            <div style="margin-top: 15px;">
                                <label for="add_kpi_measure_to_phase"><?php esc_html_e( 'Add KPI Measure to this Phase:', 'operations-organizer' ); ?></label>
                                <select id="add_kpi_measure_to_phase" name="add_kpi_measure_to_phase" style="width: 70%;">
                                    <option value=""><?php esc_html_e( '-- Select KPI Measure --', 'operations-organizer' ); ?></option>
                                    <?php
                                    $all_kpi_measures = OO_DB::get_kpi_measures(array('is_active' => 1));
                                    if (!empty($all_kpi_measures)) {
                                        foreach ($all_kpi_measures as $measure) {
                                            echo '<option value="' . esc_attr($measure->kpi_measure_id) . '" data-measure-key="' . esc_attr($measure->measure_key) . '">' . esc_html($measure->measure_name) . ' (' . esc_html($measure->measure_key) . ')</option>';
                                        }
                                    }
                                    ?>
                                </select>
                                <button type="button" id="btn-add-kpi-to-phase" class="button"><?php esc_html_e( 'Add Selected KPI', 'operations-organizer' ); ?></button>
                            </div>
                            <p class="description"><?php esc_html_e( 'Select a predefined KPI measure and add it to this phase. You can then set if it\'s mandatory and its display order.', 'operations-organizer' ); ?></p>
                        </td>
                    </tr>
                    <!-- End New Section -->
                    
                </table>
                <?php submit_button( __( 'Save Changes', 'operations-organizer' ), 'primary', 'submit_edit_phase' ); ?>
                <button type="button" id="oo-ajax-delete-phase-from-modal-button" class="button button-link is-destructive" style="margin-left: 10px; vertical-align: middle; display:none;"><?php esc_html_e( 'Delete This Phase', 'operations-organizer' ); ?></button>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Modal open/close logic is in admin-scripts.js

    window.loadPhasesTable = function() {
        window.location.reload(); 
    };
    
    // Auto-generate slug from name
    $('#add_phase_name').on('input', function() {
        var slug = $(this).val().toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        $('#add_phase_slug').val(slug);
    });

    // Fix select dropdown display issues
    $('#add_stream_type_id, #edit_modal_stream_type_id').each(function() {
        $(this).find('option[value=""]').text('-- Select Stream Type --');
    });

    // Handle "Edit Phase" button click - using event delegation
    $(document).on('click', '.oo-edit-phase-button', function() {
        var phaseId = $(this).data('phase-id');
        // Store current phaseId for KPI linking
        $('#editOOPhaseModal').data('current-phase-id', phaseId); 

        $.post(oo_data.ajax_url, {
            action: 'oo_get_phase',
            phase_id: phaseId,
            _ajax_nonce_get_phase: oo_data.nonce_edit_phase
        }, function(response) {
            if (response.success) {
                $('#edit_phase_id').val(response.data.phase_id);
                $('#edit_modal_stream_type_id').val(response.data.stream_id);
                $('#edit_modal_phase_slug').val(response.data.phase_slug);
                $('#edit_modal_phase_name').val(response.data.phase_name);
                $('#edit_modal_phase_description').val(response.data.phase_description);
                $('#edit_modal_sort_order').val(response.data.order_in_stream);
                
                // Set the includes_kpi checkbox based on data
                if (parseInt(response.data.includes_kpi) === 1) {
                    $('#edit_modal_includes_kpi').prop('checked', true);
                } else {
                    $('#edit_modal_includes_kpi').prop('checked', false);
                }
                
                // Load and display linked KPI measures
                loadLinkedKpiMeasures(phaseId);
                
                // Show/Hide Delete button in modal
                var $deleteButton = $('#oo-ajax-delete-phase-from-modal-button');
                if (phaseId) {
                    $deleteButton.data('phase-id', phaseId).show();
                } else {
                    $deleteButton.hide();
                }

                $('#editOOPhaseModal').show();
            } else {
                 showNotice('error', response.data.message || 'Could not load phase data.');
            }
        }).fail(function() {
            showNotice('error', 'Request to load phase data failed.');
        });
    });

    // Function to load and display linked KPI measures for a phase
    function loadLinkedKpiMeasures(phaseId) {
        var container = $('#linked-kpi-measures-list');
        container.html('<p><?php echo esc_js( __( 'Loading linked KPIs...', 'operations-organizer' ) ); ?></p>');

        $.post(oo_data.ajax_url, {
            action: 'oo_get_phase_kpi_links',
            phase_id: phaseId,
            _ajax_nonce: oo_data.nonce_get_phase_kpi_links // Add a nonce for this action
        }, function(response) {
            if (response.success && response.data) {
                container.empty();
                if (response.data.length > 0) {
                    var table = $('<table class="wp-list-table widefat striped oo-linked-kpis-table"><thead><tr><th><?php echo esc_js( __('Measure Name', 'operations-organizer') ); ?></th><th><?php echo esc_js( __('Mandatory', 'operations-organizer') ); ?></th><th><?php echo esc_js( __('Order', 'operations-organizer') ); ?></th><th><?php echo esc_js( __('Actions', 'operations-organizer') ); ?></th></tr></thead><tbody></tbody></table>');
                    var tbody = table.find('tbody');
                    $.each(response.data, function(index, link) {
                        var row = $('<tr>');
                        row.append('<td>' + esc_html(link.measure_name) + ' (<code>' + esc_html(link.measure_key) + '</code>)</td>');
                        row.append('<td><input type="checkbox" class="is-mandatory-kpi" data-link-id="' + link.link_id + '" ' + (link.is_mandatory == 1 ? 'checked' : '') + '></td>');
                        row.append('<td><input type="number" class="display-order-kpi" data-link-id="' + link.link_id + '" value="' + link.display_order + '" style="width: 60px;"></td>');
                        row.append('<td><button type="button" class="button button-link-delete oo-remove-kpi-link" data-link-id="' + link.link_id + '"><?php echo esc_js( __('Remove', 'operations-organizer') ); ?></button></td>');
                        tbody.append(row);
                    });
                    container.append(table);
                } else {
                    container.html('<p><?php echo esc_js( __( 'No KPI measures are currently linked to this phase.', 'operations-organizer' ) ); ?></p>');
                }
            } else {
                container.html('<p><?php echo esc_js( __( 'Error loading linked KPIs.', 'operations-organizer' ) ); ?></p>');
                if(response.data && response.data.message) console.error("Error loading linked KPIs:", response.data.message);
            }
        }).fail(function() {
            container.html('<p><?php echo esc_js( __( 'Request to load linked KPIs failed.', 'operations-organizer' ) ); ?></p>');
        });
    }

    // Handle "Add Selected KPI" button click
    $('#btn-add-kpi-to-phase').on('click', function() {
        var phaseId = $('#editOOPhaseModal').data('current-phase-id');
        var kpiMeasureId = $('#add_kpi_measure_to_phase').val();
        var kpiMeasureName = $('#add_kpi_measure_to_phase option:selected').text();

        if (!phaseId || !kpiMeasureId) {
            alert('<?php echo esc_js( __( 'Please select a phase and a KPI measure.', 'operations-organizer' ) ); ?>');
            return;
        }

        $.post(oo_data.ajax_url, {
            action: 'oo_add_phase_kpi_link',
            phase_id: phaseId,
            kpi_measure_id: kpiMeasureId,
            is_mandatory: 0, // Default to not mandatory
            display_order: 0,  // Default order
            _ajax_nonce: oo_data.nonce_manage_phase_kpi_links // Add a nonce for this
        }, function(response) {
            if (response.success) {
                showNotice('success', '<?php echo esc_js( __( "KPI measure linked successfully.", "operations-organizer" ) ); ?> \'' + kpiMeasureName + '\'');
                loadLinkedKpiMeasures(phaseId);
                $('#add_kpi_measure_to_phase').val(''); // Reset dropdown
            } else {
                showNotice('error', response.data.message || '<?php echo esc_js( __( "Could not link KPI measure.", "operations-organizer" ) ); ?>');
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js( __( "Request to link KPI measure failed.", "operations-organizer" ) ); ?>');
        });
    });

    // Handle "Remove" KPI link button click (event delegation for dynamically added buttons)
    $('#linked-kpi-measures-list').on('click', '.oo-remove-kpi-link', function() {
        var linkId = $(this).data('link-id');
        var phaseId = $('#editOOPhaseModal').data('current-phase-id');
        if (!confirm('<?php echo esc_js( __( "Are you sure you want to remove this KPI measure from the phase?", "operations-organizer" ) ); ?>')) {
            return;
        }

        $.post(oo_data.ajax_url, {
            action: 'oo_delete_phase_kpi_link',
            link_id: linkId,
            _ajax_nonce: oo_data.nonce_manage_phase_kpi_links 
        }, function(response) {
            if (response.success) {
                showNotice('success', '<?php echo esc_js( __( "KPI measure unlinked successfully.", "operations-organizer" ) ); ?>');
                loadLinkedKpiMeasures(phaseId);
            } else {
                showNotice('error', response.data.message || '<?php echo esc_js( __( "Could not unlink KPI measure.", "operations-organizer" ) ); ?>');
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js( __( "Request to unlink KPI measure failed.", "operations-organizer" ) ); ?>');
        });
    });
    
    // Handle changes to "is_mandatory" and "display_order" for linked KPIs
    $('#linked-kpi-measures-list').on('change', '.is-mandatory-kpi, .display-order-kpi', function() {
        var linkId = $(this).data('link-id');
        var phaseId = $('#editOOPhaseModal').data('current-phase-id');
        var isMandatory = $(this).closest('tr').find('.is-mandatory-kpi').is(':checked') ? 1 : 0;
        var displayOrder = $(this).closest('tr').find('.display-order-kpi').val();

        $.post(oo_data.ajax_url, {
            action: 'oo_update_phase_kpi_link',
            link_id: linkId,
            is_mandatory: isMandatory,
            display_order: displayOrder,
            _ajax_nonce: oo_data.nonce_manage_phase_kpi_links
        }, function(response) {
            if (response.success) {
                showNotice('success', '<?php echo esc_js( __( "KPI link updated.", "operations-organizer" ) ); ?>');
                // No need to reload the whole list for this, but can if desired for consistency
                // loadLinkedKpiMeasures(phaseId); 
            } else {
                showNotice('error', response.data.message || '<?php echo esc_js( __( "Could not update KPI link.", "operations-organizer" ) ); ?>');
                // Optionally, revert the change in the UI if the update fails
                // For now, just show an error.
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js( __( "Request to update KPI link failed.", "operations-organizer" ) ); ?>');
        });
    });
    
    // Helper function for escaping HTML (since this is JS within PHP)
    function esc_html(str) {
        var p = document.createElement('p');
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    }

    // Handle "Toggle Status" button click - using event delegation
    $(document).on('click', '.oo-toggle-status-phase-button', function() {
        var phaseId = $(this).data('phase-id');
        var newStatus = $(this).data('new-status');
        var confirmMessage = newStatus == 1 ? 
            '<?php echo esc_js(__("Are you sure you want to activate this phase?", "operations-organizer")); ?>' : 
            '<?php echo esc_js(__("Are you sure you want to deactivate this phase?", "operations-organizer")); ?>';

        if (!confirm(confirmMessage)) {
            return;
        }
        
        $.post(oo_data.ajax_url, {
            action: 'oo_toggle_phase_status',
            phase_id: phaseId,
            is_active: newStatus,
            _ajax_nonce: oo_data.nonce_toggle_status
        }, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                loadPhasesTable(); 
            } else {
                showNotice('error', response.data.message || 'Could not change phase status.');
            }
        }).fail(function() {
            showNotice('error', 'Request to change phase status failed.');
        });
    });
    
    // AJAX Delete Phase from Modal
    $('#oo-ajax-delete-phase-from-modal-button').on('click', function() {
        var phaseId = $(this).data('phase-id');
        if (!phaseId) {
            alert('Error: Phase ID not found for deletion.');
            return;
        }

        if (!confirm('<?php echo esc_js( __("Are you sure you want to permanently delete this phase? This action cannot be undone and may affect existing job logs if not handled carefully by the system.", "operations-organizer") ); ?>')) {
            return;
        }

        $.post(oo_data.ajax_url, {
            action: 'oo_delete_phase_ajax',
            phase_id: phaseId,
            _ajax_nonce: oo_data.nonce_delete_phase_ajax 
        }, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                $('#editOOPhaseModal').hide();
                loadPhasesTable(); // Reload the list table
            } else {
                showNotice('error', response.data.message || 'Could not delete phase.');
            }
        }).fail(function() {
            showNotice('error', 'Request to delete phase failed.');
        });
    });

    // Common function to display notices
    if (typeof showNotice !== 'function') {
        window.showNotice = function(type, message) {
            $('.oo-notice').remove();
            var noticeHtml = '<div class="notice notice-' + type + ' is-dismissible oo-notice"><p>' + message + '</p>' +
                             '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
            $('.oo-phase-management-page h1').first().after(noticeHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $('.oo-notice').fadeOut('slow', function() { $(this).remove(); });
            }, 5000);
            
            // Allow manual dismiss
            $('.oo-notice .notice-dismiss').on('click', function(event) {
                event.preventDefault();
                $(this).closest('.oo-notice').remove();
            });
        };
    }

    // --- Manage Phase KPIs Modal Logic ---
    // Open Manage Phase KPIs Modal - already uses event delegation, which is good.
    $(document).on('click', '.oo-manage-phase-kpis-button', function() {
        var phaseId = $(this).data('phase-id');
        var phaseName = $(this).data('phase-name');

        $('#manageKPIsPhaseName').text(phaseName);
        $('#manageKPIsPhaseId').val(phaseId);
        $('#manageOOPhaseKPIsModal').show();
        loadAvailableAndLinkedKPIsForPhase(phaseId);
    });

    function loadAvailableAndLinkedKPIsForPhase(phaseId) {
        var container = $('#availableKPIsForPhaseList');
        container.html('<p><?php echo esc_js( __( 'Loading KPIs...', 'operations-organizer' ) ); ?></p>');

        $.when(
            // Get all active KPI measures
            $.post(oo_data.ajax_url, { 
                action: 'oo_get_kpi_measures', 
                _ajax_nonce: oo_data.nonce_dashboard, // Re-use a general nonce or create specific one
                is_active: 1,
                number: -1 // Get all
            }),
            // Get KPIs already linked to this phase
            $.post(oo_data.ajax_url, {
                action: 'oo_get_phase_kpi_links',
                phase_id: phaseId,
                _ajax_nonce: oo_data.nonce_get_phase_kpi_links 
            })
        ).done(function(allKpisResponse, linkedKpisResponse) {
            container.empty();
            var allKpis = (allKpisResponse[0] && allKpisResponse[0].success) ? allKpisResponse[0].data : [];
            var linkedKpis = (linkedKpisResponse[0] && linkedKpisResponse[0].success) ? linkedKpisResponse[0].data : [];

            if (allKpis.length === 0) {
                container.html('<p><?php echo esc_js( __( "No active KPI measures defined. Please define KPIs first.", "operations-organizer" ) ); ?></p>');
                return;
            }

            var list = $('<ul style="list-style-type: none; padding-left: 0;">');
            $.each(allKpis, function(index, kpi) {
                var currentLink = linkedKpis.find(function(link) { return link.kpi_measure_id == kpi.kpi_measure_id; });
                var isChecked = currentLink ? 'checked' : '';
                var isMandatory = (currentLink && currentLink.is_mandatory == 1) ? 'checked' : '';
                var displayOrder = (currentLink && typeof currentLink.display_order !== 'undefined') ? currentLink.display_order : 0;

                var listItem = $('<li style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #eee;">');
                listItem.append(
                    '<label style="display: block; margin-bottom: 5px;">' + 
                    '<input type="checkbox" name="phase_kpis[]" value="' + kpi.kpi_measure_id + '" ' + isChecked + ' style="margin-right: 5px;" />' + 
                    '<strong>' + esc_html(kpi.measure_name) + '</strong> (<code>' + esc_html(kpi.measure_key) + '</code>)' + 
                    '</label>');
                
                var optionsDiv = $('<div style="margin-left: 25px;" class="kpi-link-options"></div>').toggle(isChecked !== '');
                optionsDiv.append(
                    '<label style="margin-right: 15px;">' + 
                    '<input type="checkbox" name="kpi_mandatory[' + kpi.kpi_measure_id + ']" ' + isMandatory + ' style="margin-right: 3px;"/><?php echo esc_js( __("Mandatory?", "operations-organizer") ); ?>' + 
                    '</label>');
                optionsDiv.append(
                    '<label><?php echo esc_js( __("Display Order:", "operations-organizer") ); ?> ' + 
                    '<input type="number" name="kpi_display_order[' + kpi.kpi_measure_id + ']" value="' + displayOrder + '" style="width: 60px;" min="0"/>' + 
                    '</label>');
                listItem.append(optionsDiv);
                list.append(listItem);
            });
            container.append(list);

            // Toggle options visibility when a KPI is checked/unchecked
            container.find('input[name="phase_kpis[]"]').on('change', function() {
                $(this).closest('li').find('.kpi-link-options').toggle($(this).is(':checked'));
            });

        }).fail(function(jqXHR, textStatus, errorThrown) {
            container.html('<p><?php echo esc_js( __( "Error loading KPI data.", "operations-organizer" ) ); ?></p>');
            console.error("Error loading KPIs for phase: ", textStatus, errorThrown, jqXHR.responseText);
        });
    }

    $('#savePhaseKPILinksBtn').on('click', function() {
        var phaseId = $('#manageKPIsPhaseId').val();
        var links = [];
        $('#availableKPIsForPhaseList input[name="phase_kpis[]"]:checked').each(function() {
            var kpiId = $(this).val();
            var isMandatory = $('input[name="kpi_mandatory[' + kpiId + ']"]').is(':checked') ? 1 : 0;
            var displayOrder = $('input[name="kpi_display_order[' + kpiId + ']"]').val();
            links.push({
                kpi_measure_id: kpiId,
                is_mandatory: isMandatory,
                display_order: displayOrder || 0
            });
        });

        $.post(oo_data.ajax_url, {
            action: 'oo_save_phase_kpi_links',
            phase_id: phaseId,
            links: links,
            _ajax_nonce: oo_data.nonce_manage_phase_kpi_links // Ensure this nonce is defined in oo_data
        }, function(response) {
            if (response.success) {
                showNotice('success', response.data.message || '<?php echo esc_js( __("KPI links saved successfully.", "operations-organizer") ); ?>');
                $('#manageOOPhaseKPIsModal').hide();
            } else {
                showNotice('error', response.data.message || '<?php echo esc_js( __("Could not save KPI links.", "operations-organizer") ); ?>');
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js( __( "Request to save KPI links failed.", "operations-organizer") ); ?>');
        });
    });
    // --- End Manage Phase KPIs Modal Logic ---

});
</script> 

<style>
.oo-phase-management-page .stream-type-filter-label {
    font-weight: bold;
    margin-right: 5px;
}
.oo-notice.oo-info {
    border-left: 4px solid #72aee6;
    background: #f0f6fc;
    padding: 10px 15px;
    margin-bottom: 20px;
}
.oo-info p {
    margin: 0.5em 0;
}

/* Fix for stream type selectors */
#addOOPhaseModal select, 
#editOOPhaseModal select {
    width: 100%;
    max-width: 25em;
    padding: 6px 12px;
    height: auto;
}
#addOOPhaseModal option, 
#editOOPhaseModal option {
    padding: 4px 8px;
}
select#add_stream_type_id option[value=""],
select#edit_modal_stream_type_id option[value=""] {
    color: #757575;
    font-style: italic;
}
</style> 

<!-- Manage Phase KPIs Modal -->
<div id="manageOOPhaseKPIsModal" class="oo-modal" style="display:none;">
    <div class="oo-modal-content" style="width: 700px; max-width: 90%;">
        <span class="oo-close-button">&times;</span>
        <h2><?php esc_html_e( 'Manage KPIs for Phase:', 'operations-organizer' ); ?> <span id="manageKPIsPhaseName"></span></h2>
        <input type="hidden" id="manageKPIsPhaseId" value="" />
        
        <div id="availableKPIsForPhaseList" style="margin-bottom: 20px; max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
            <!-- AJAX content will load here -->
            <p><?php esc_html_e( 'Loading available KPIs...', 'operations-organizer' ); ?></p>
        </div>

        <p class="submit">
            <button type="button" id="savePhaseKPILinksBtn" class="button button-primary"><?php esc_html_e( 'Save KPI Links', 'operations-organizer' ); ?></button>
            <button type="button" class="button oo-modal-close oo-close-button"><?php esc_html_e( 'Close', 'operations-organizer' ); ?></button>
        </p>
    </div>
</div> 