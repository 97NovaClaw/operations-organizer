<?php
// /admin/views/employee-management-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Data for this page is prepared in OO_Employee::display_employee_management_page()
// and passed via global variables or directly included before this view.
// Expected variables: $employees, $total_employees, $current_page, $per_page, $search_term, $active_filter
// $GLOBALS['orderby'], $GLOBALS['order'] are also expected to be set.

global $employees, $total_employees, $current_page, $per_page, $search_term, $active_filter;

?>
<div class="wrap oo-employee-management-page">
    <h1><?php esc_html_e( 'Employee Management', 'operations-organizer' ); ?></h1>

    <button id="openAddOOEmployeeModalBtn" class="page-title-action oo-open-modal-button" data-modal-id="addOOEmployeeModal"><?php esc_html_e( 'Add New Employee', 'operations-organizer' ); ?></button>

    <!-- Search and Filter Form -->
    <form method="get" class="oo-filters-form">
        <input type="hidden" name="page" value="oo_employees" />
        <div class="wp-filter">
            <div class="filter-items">
                <label for="status_filter" class="screen-reader-text"><?php esc_html_e('Filter by status', 'operations-organizer');?></label>
                <select name="status_filter" id="status_filter">
                    <option value="all" <?php selected($active_filter, 'all'); ?>><?php esc_html_e('All Statuses', 'operations-organizer');?></option>
                    <option value="active" <?php selected($active_filter, 'active'); ?>><?php esc_html_e('Active', 'operations-organizer');?></option>
                    <option value="inactive" <?php selected($active_filter, 'inactive'); ?>><?php esc_html_e('Inactive', 'operations-organizer');?></option>
                </select>
                <input type="submit" name="filter_action" class="button" value="<?php esc_attr_e('Filter', 'operations-organizer');?>">
            </div>
            <p class="search-box">
                <label class="screen-reader-text" for="employee-search-input"><?php esc_html_e( 'Search Employees:', 'operations-organizer' ); ?></label>
                <input type="search" id="employee-search-input" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search by Name/Number', 'operations-organizer' ); ?>"/>
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Employees', 'operations-organizer' ); ?>" />
            </p>
        </div>
    </form>
    <div class="clear"></div>

    <div id="oo-employee-list-table-wrapper">
        <table class="wp-list-table widefat fixed striped table-view-list employees">
            <thead>
                <tr>
                    <?php 
                    $columns = [
                        'employee_number' => __('Emp. Number', 'operations-organizer'),
                        'first_name' => __('First Name', 'operations-organizer'),
                        'last_name' => __('Last Name', 'operations-organizer'),
                        'status' => __('Status', 'operations-organizer'),
                        'actions' => __('Actions', 'operations-organizer'),
                    ];
                    $current_orderby = isset($GLOBALS['orderby']) ? $GLOBALS['orderby'] : 'last_name';
                    $current_order = isset($GLOBALS['order']) ? strtolower($GLOBALS['order']) : 'asc';

                    foreach($columns as $slug => $title) {
                        $class = "manage-column column-$slug";
                        $sort_link = '';
                        if (in_array($slug, ['employee_number', 'first_name', 'last_name'])) { // Sortable columns
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
                <?php 
                if ( ! empty( $employees ) ) : ?>
                    <?php foreach ( $employees as $employee ) : ?>
                        <tr id="employee-<?php echo $employee->employee_id; ?>" class="<?php echo $employee->is_active ? 'active' : 'inactive'; ?>">
                            <td class="employee_number column-employee_number" data-colname="<?php esc_attr_e('Emp. Number', 'operations-organizer'); ?>">
                                <?php echo esc_html( $employee->employee_number ); ?>
                            </td>
                            <td class="first_name column-first_name" data-colname="<?php esc_attr_e('First Name', 'operations-organizer'); ?>">
                                <?php echo esc_html( $employee->first_name ); ?>
                            </td>
                            <td class="last_name column-last_name" data-colname="<?php esc_attr_e('Last Name', 'operations-organizer'); ?>">
                                <?php echo esc_html( $employee->last_name ); ?>
                            </td>
                            <td class="status column-status" data-colname="<?php esc_attr_e('Status', 'operations-organizer'); ?>">
                                <?php if ( $employee->is_active ) : ?>
                                    <span style="color: green;"><?php esc_html_e( 'Active', 'operations-organizer' ); ?></span>
                                <?php else : ?>
                                    <span style="color: red;"><?php esc_html_e( 'Inactive', 'operations-organizer' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="actions column-actions" data-colname="<?php esc_attr_e('Actions', 'operations-organizer'); ?>">
                                <button class="button-secondary oo-edit-employee-button" data-employee-id="<?php echo esc_attr( $employee->employee_id ); ?>"><?php esc_html_e('Edit', 'operations-organizer'); ?></button>
                                <?php if ( $employee->is_active ) : ?>
                                    <button class="button-secondary oo-toggle-status-employee-button oo-deactivate" data-employee-id="<?php echo esc_attr( $employee->employee_id ); ?>" data-new-status="0"><?php esc_html_e('Deactivate', 'operations-organizer'); ?></button>
                                <?php else : ?>
                                    <button class="button-secondary oo-toggle-status-employee-button oo-activate" data-employee-id="<?php echo esc_attr( $employee->employee_id ); ?>" data-new-status="1"><?php esc_html_e('Activate', 'operations-organizer'); ?></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="<?php echo count($columns); ?>"><?php esc_html_e( 'No employees found.', 'operations-organizer' ); ?></td>
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
    if ( $total_employees > $per_page ) {
        $base_url = remove_query_arg(array('paged', 'filter_action'), wp_unslash($_SERVER['REQUEST_URI']));
        $page_links = paginate_links( array(
            'base' => $base_url . '%_%',
            'format' => '&paged=%#%',
            'prev_text' => __( '&laquo; Previous' ),
            'next_text' => __( 'Next &raquo;' ),
            'total' => ceil( $total_employees / $per_page ),
            'current' => $current_page,
            'add_args' => array_map('urlencode', array_filter(compact('s', 'status_filter', 'orderby', 'order')))
        ) );

        if ( $page_links ) {
            echo "<div class=\"tablenav\"><div class=\"tablenav-pages\">$page_links</div></div>";
        }
    }
    ?>

    <!-- Add Employee Modal -->
    <div id="addOOEmployeeModal" class="oo-modal" style="display:none;">
        <div class="oo-modal-content">
            <span class="oo-close-button">&times;</span>
            <h2><?php esc_html_e( 'Add New Employee', 'operations-organizer' ); ?></h2>
            <form id="oo-add-employee-form">
                <?php wp_nonce_field( 'oo_add_employee_nonce', 'oo_add_employee_nonce' ); ?>
                <table class="form-table oo-form-table">
                    <tr valign="top">
                        <th scope="row"><label for="employee_number_add"><?php esc_html_e( 'Employee Number', 'operations-organizer' ); ?></label></th>
                        <td><input type="text" id="employee_number_add" name="employee_number" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="first_name_add"><?php esc_html_e( 'First Name', 'operations-organizer' ); ?></label></th>
                        <td><input type="text" id="first_name_add" name="first_name" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="last_name_add"><?php esc_html_e( 'Last Name', 'operations-organizer' ); ?></label></th>
                        <td><input type="text" id="last_name_add" name="last_name" required /></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Add Employee', 'operations-organizer' ), 'primary', 'submit_add_employee' ); ?>
            </form>
        </div>
    </div>

    <!-- Edit Employee Modal -->
    <div id="editEmployeeModal" class="oo-modal" style="display:none;">
        <div class="oo-modal-content">
            <span class="oo-close-button">&times;</span>
            <h2><?php esc_html_e( 'Edit Employee', 'operations-organizer' ); ?></h2>
            <form id="oo-edit-employee-form">
                <?php wp_nonce_field( 'oo_edit_employee_nonce', 'oo_edit_employee_nonce' ); ?>
                <input type="hidden" id="edit_employee_id" name="edit_employee_id" value="" />
                <table class="form-table oo-form-table">
                    <tr valign="top">
                        <th scope="row"><label for="edit_employee_number"><?php esc_html_e( 'Employee Number', 'operations-organizer' ); ?></label></th>
                        <td><input type="text" id="edit_employee_number" name="edit_employee_number" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="edit_first_name"><?php esc_html_e( 'First Name', 'operations-organizer' ); ?></label></th>
                        <td><input type="text" id="edit_first_name" name="edit_first_name" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="edit_last_name"><?php esc_html_e( 'Last Name', 'operations-organizer' ); ?></label></th>
                        <td><input type="text" id="edit_last_name" name="edit_last_name" required /></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save Changes', 'operations-organizer' ), 'primary', 'submit_edit_employee' ); ?>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Modal open/close logic is in admin-scripts.js

    // Function to reload table (can be a full page reload or AJAX based)
    // This is a simple page reload version.
    window.loadEmployeesTable = function() {
        // Keep current filters in URL
        var currentUrl = window.location.href;
        // Basic reload, or more complex if trying to maintain exact state without full reload
        window.location.href = currentUrl;
    };

    // Handle "Edit Employee" button click to populate modal
    $('.oo-edit-employee-button').on('click', function() {
        var employeeId = $(this).data('employee-id');
        
        $.post(oo_data.ajax_url, {
            action: 'oo_get_employee',
            employee_id: employeeId,
            _ajax_nonce_get_employee: oo_data.nonce_edit_employee
        }, function(response) {
            if (response.success) {
                $('#edit_employee_id').val(response.data.employee_id);
                $('#edit_employee_number').val(response.data.employee_number);
                $('#edit_first_name').val(response.data.first_name);
                $('#edit_last_name').val(response.data.last_name);
                $('#editEmployeeModal').show();
            } else {
                showNotice('error', response.data.message || 'Could not load employee data.');
            }
        }).fail(function() {
            showNotice('error', 'Request to load employee data failed.');
        });
    });

    // Handle "Toggle Status" button click
    $('.oo-toggle-status-employee-button').on('click', function() {
        var employeeId = $(this).data('employee-id');
        var newStatus = $(this).data('new-status');
        var confirmMessage = newStatus == 1 ? 
            '<?php echo esc_js(__('Are you sure you want to activate this employee?', 'operations-organizer')); ?>' : 
            '<?php echo esc_js(__('Are you sure you want to deactivate this employee?', 'operations-organizer')); ?>';

        if (!confirm(confirmMessage)) {
            return;
        }
        
        $.post(oo_data.ajax_url, {
            action: 'oo_toggle_employee_status',
            employee_id: employeeId,
            is_active: newStatus,
            _ajax_nonce: oo_data.nonce_toggle_status
        }, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                loadEmployeesTable(); 
            } else {
                showNotice('error', response.data.message || 'Could not change employee status.');
            }
        }).fail(function() {
            showNotice('error', 'Request to change employee status failed.');
        });
    });
});
</script> 