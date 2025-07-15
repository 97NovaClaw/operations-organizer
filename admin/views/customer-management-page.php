<?php
// /admin/views/customer-management-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

global $customers, $total_customers, $current_page, $per_page, $search_term, $active_filter;

?>
<div class="wrap oo-customer-management-page">
    <h1><?php esc_html_e( 'Customer Management', 'operations-organizer' ); ?></h1>

    <?php if ( isset( $GLOBALS['oo_customer_error'] ) ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $GLOBALS['oo_customer_error'] ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( isset( $GLOBALS['oo_customer_success'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html( $GLOBALS['oo_customer_success'] ); ?></p>
        </div>
    <?php endif; ?>

    <button id="openAddCustomerModalBtn" class="page-title-action oo-open-modal-button" data-modal-id="addCustomerModal"><?php esc_html_e( 'Add New Customer', 'operations-organizer' ); ?></button>

    <form method="get" class="oo-filters-form">
        <input type="hidden" name="page" value="oo_customers" />
        <div class="wp-filter">
            <div class="filter-items">
                <label for="company_filter" class="screen-reader-text"><?php esc_html_e('Filter by company', 'operations-organizer');?></label>
                <select name="company_filter" id="company_filter">
                    <option value=""><?php esc_html_e('All Companies', 'operations-organizer');?></option>
                    <?php
                    $companies = OO_DB::get_companies(array('orderby' => 'name', 'order' => 'ASC'));
                    $selected_company = isset($_GET['company_filter']) ? intval($_GET['company_filter']) : '';
                    foreach ($companies as $company) : ?>
                        <option value="<?php echo esc_attr($company->company_id); ?>" <?php selected($selected_company, $company->company_id); ?>><?php echo esc_html($company->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" name="filter_action" class="button" value="<?php esc_attr_e('Filter', 'operations-organizer');?>">
            </div>
            <p class="search-box">
                <label class="screen-reader-text" for="customer-search-input"><?php esc_html_e( 'Search Customers:', 'operations-organizer' ); ?></label>
                <input type="search" id="customer-search-input" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search by Name, Email, or Company', 'operations-organizer' ); ?>" />
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Customers', 'operations-organizer' ); ?>" />
            </p>
        </div>
    </form>
    <div class="clear"></div>

    <div id="oo-customer-list-table-wrapper">
        <table class="wp-list-table widefat fixed striped table-view-list customers">
            <thead>
                <tr>
                    <?php 
                    $columns = [
                        'name' => __('Name', 'operations-organizer'),
                        'email' => __('Email', 'operations-organizer'),
                        'phone' => __('Phone', 'operations-organizer'),
                        'company' => __('Company', 'operations-organizer'),
                        'created_at' => __('Created', 'operations-organizer'),
                        'actions' => __('Actions', 'operations-organizer'),
                    ];
                    $current_orderby = isset($GLOBALS['orderby']) ? $GLOBALS['orderby'] : 'name';
                    $current_order = isset($GLOBALS['order']) ? strtolower($GLOBALS['order']) : 'asc';

                    foreach($columns as $slug => $title) {
                        $class = "manage-column column-$slug";
                        $sort_link = '';
                        if (in_array($slug, ['name', 'email', 'created_at'])) { // Sortable columns
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
                <?php if ( ! empty( $customers ) ) : ?>
                    <?php foreach ( $customers as $customer ) : ?>
                        <tr id="customer-<?php echo $customer->customer_id; ?>">
                            <td class="name column-name" data-colname="<?php esc_attr_e('Name', 'operations-organizer'); ?>">
                                <strong><?php echo esc_html( $customer->name ); ?></strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="#" class="oo-edit-customer-button" data-customer-id="<?php echo esc_attr( $customer->customer_id ); ?>"><?php esc_html_e('Edit', 'operations-organizer'); ?></a>
                                    </span>
                                </div>
                            </td>
                            <td class="email column-email" data-colname="<?php esc_attr_e('Email', 'operations-organizer'); ?>">
                                <?php 
                                if ( !empty($customer->email_addresses) ) {
                                    // Check if it's JSON data
                                    $email_data = json_decode( $customer->email_addresses, true );
                                    if ( json_last_error() === JSON_ERROR_NONE && is_array( $email_data ) ) {
                                        echo oo_format_email_display_advanced( $email_data, 'first_only' );
                                    } else {
                                        echo esc_html( $customer->email_addresses );
                                    }
                                } elseif ( $customer->email ) {
                                    echo esc_html( $customer->email );
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="phone column-phone" data-colname="<?php esc_attr_e('Phone', 'operations-organizer'); ?>">
                                <?php 
                                if ( !empty($customer->phone_numbers) ) {
                                    // Check if it's JSON data
                                    $phone_data = json_decode( $customer->phone_numbers, true );
                                    if ( json_last_error() === JSON_ERROR_NONE && is_array( $phone_data ) ) {
                                        echo oo_format_phone_display( $phone_data, 'primary_only' );
                                    } else {
                                        echo esc_html( $customer->phone_numbers );
                                    }
                                } elseif ( $customer->phone ) {
                                    // Check if it's JSON data
                                    $phone_data = json_decode( $customer->phone, true );
                                    if ( json_last_error() === JSON_ERROR_NONE && is_array( $phone_data ) ) {
                                        echo oo_format_phone_display( $phone_data, 'primary_only' );
                                    } else {
                                        echo esc_html( $customer->phone );
                                    }
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="company column-company" data-colname="<?php esc_attr_e('Company', 'operations-organizer'); ?>">
                                <?php echo !empty($customer->company_name) ? esc_html( $customer->company_name ) : '—'; ?>
                            </td>
                            <td class="created_at column-created_at" data-colname="<?php esc_attr_e('Created', 'operations-organizer'); ?>">
                                <?php echo $customer->created_at ? esc_html( date_i18n( get_option('date_format'), strtotime( $customer->created_at ) ) ) : '—'; ?>
                            </td>
                            <td class="actions column-actions" data-colname="<?php esc_attr_e('Actions', 'operations-organizer'); ?>">
                                <button class="button-secondary oo-edit-customer-button" data-customer-id="<?php echo esc_attr( $customer->customer_id ); ?>"><?php esc_html_e('Edit', 'operations-organizer'); ?></button>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=oo_jobs&customer_filter=' . $customer->customer_id ) ); ?>" class="button-secondary"><?php esc_html_e('View Jobs', 'operations-organizer'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6"><?php esc_html_e( 'No customers found.', 'operations-organizer' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    // Pagination
    if ( $total_customers > $per_page ) {
        $base_url = remove_query_arg( array( 'paged', 'filter_action' ), wp_unslash( $_SERVER['REQUEST_URI'] ) );
        $page_links = paginate_links( array(
            'base' => $base_url . '%_%',
            'format' => '&paged=%#%',
            'prev_text' => __( '&laquo; Previous' ),
            'next_text' => __( 'Next &raquo;' ),
            'total' => ceil( $total_customers / $per_page ),
            'current' => $current_page,
            'add_args' => array_map( 'urlencode', array_filter( compact( 's', 'company_filter' ) ) )
        ) );
        if ( $page_links ) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
        }
    }
    ?>
</div>

<!-- Add Customer Modal -->
<div id="addCustomerModal" class="oo-modal" style="display: none;">
    <div class="oo-modal-content">
        <div class="oo-modal-header">
            <h2><?php esc_html_e( 'Add New Customer', 'operations-organizer' ); ?></h2>
            <span class="oo-modal-close">&times;</span>
        </div>
        <form id="addCustomerForm" method="post">
            <?php wp_nonce_field( 'oo_add_customer_nonce', 'oo_add_customer_nonce' ); ?>
            <input type="hidden" name="oo_action" value="add_customer" />
            
            <div class="oo-form-field">
                <label for="modal_customer_name"><?php esc_html_e( 'Customer Name', 'operations-organizer' ); ?> <span class="required">*</span></label>
                <input type="text" id="modal_customer_name" name="name" class="regular-text" required />
            </div>
            
            <div class="oo-form-field">
                <label for="modal_customer_email"><?php esc_html_e( 'Email', 'operations-organizer' ); ?></label>
                <input type="email" id="modal_customer_email" name="email" class="regular-text" />
            </div>
            
            <div class="oo-form-field">
                <label for="modal_customer_email_container"><?php esc_html_e( 'Email Addresses', 'operations-organizer' ); ?></label>
                <?php
                echo oo_get_email_repeater_html(array(
                    'container_id'   => 'modal_customer_email_container',
                    'field_name'     => 'email_addresses',
                    'add_button_text' => 'Add Email',
                    'max_emails'     => 5
                ));
                ?>
            </div>
            
            <div class="oo-form-field">
                <label for="modal_customer_phone_container"><?php esc_html_e( 'Phone Numbers', 'operations-organizer' ); ?></label>
                <?php
                echo oo_get_phone_repeater_html(array(
                    'container_id'   => 'modal_customer_phone_container',
                    'field_name'     => 'phone_numbers',
                    'add_button_text' => 'Add Phone Number',
                    'max_phones'     => 5
                ));
                ?>
            </div>
            
            <div class="oo-form-field">
                <label for="modal_customer_company"><?php esc_html_e( 'Company', 'operations-organizer' ); ?></label>
                <?php
                echo oo_get_autocomplete_html(array(
                    'input_id'              => 'modal_customer_company',
                    'input_name'            => 'company_search',
                    'placeholder'           => 'Search for a company...',
                    'ajax_action'           => 'oo_search_companies',
                    'render_item_callback'  => 'renderCompanyItem',
                    'on_select_callback'    => 'onCompanySelect',
                    'on_add_new_callback'   => 'onAddNewCompany',
                    'nonce'                 => wp_create_nonce('oo_search_companies_nonce'),
                    'add_new_text'          => 'Create New Company',
                    'hidden_field_id'       => 'selected_company_id',
                    'hidden_field_name'     => 'company_id'
                ));
                ?>
            </div>
            
            <div class="oo-form-field">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Add Customer', 'operations-organizer' ); ?></button>
                <button type="button" class="button oo-modal-cancel"><?php esc_html_e( 'Cancel', 'operations-organizer' ); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="editCustomerModal" class="oo-modal" style="display: none;">
    <div class="oo-modal-content">
        <div class="oo-modal-header">
            <h2><?php esc_html_e( 'Edit Customer', 'operations-organizer' ); ?></h2>
            <span class="oo-modal-close">&times;</span>
        </div>
        <form id="editCustomerForm" method="post">
            <?php wp_nonce_field( 'oo_edit_customer_nonce', 'oo_edit_customer_nonce' ); ?>
            <input type="hidden" name="oo_action" value="edit_customer" />
            <input type="hidden" id="edit_customer_id" name="customer_id" />
            
            <div class="oo-form-field">
                <label for="edit_customer_name"><?php esc_html_e( 'Customer Name', 'operations-organizer' ); ?> <span class="required">*</span></label>
                <input type="text" id="edit_customer_name" name="name" class="regular-text" required />
            </div>
            
            <div class="oo-form-field">
                <label for="edit_customer_email"><?php esc_html_e( 'Email', 'operations-organizer' ); ?></label>
                <input type="email" id="edit_customer_email" name="email" class="regular-text" />
            </div>
            
            <div class="oo-form-field">
                <label for="edit_customer_email_container"><?php esc_html_e( 'Email Addresses', 'operations-organizer' ); ?></label>
                <?php
                echo oo_get_email_repeater_html(array(
                    'container_id'   => 'edit_customer_email_container',
                    'field_name'     => 'email_addresses',
                    'add_button_text' => 'Add Email',
                    'max_emails'     => 5
                ));
                ?>
            </div>
            
            <div class="oo-form-field">
                <label for="edit_customer_phone_container"><?php esc_html_e( 'Phone Numbers', 'operations-organizer' ); ?></label>
                <?php
                echo oo_get_phone_repeater_html(array(
                    'container_id'   => 'edit_customer_phone_container',
                    'field_name'     => 'phone_numbers',
                    'add_button_text' => 'Add Phone Number',
                    'max_phones'     => 5
                ));
                ?>
            </div>
            
            <div class="oo-form-field">
                <label for="edit_customer_company"><?php esc_html_e( 'Company', 'operations-organizer' ); ?></label>
                <?php
                echo oo_get_autocomplete_html(array(
                    'input_id'              => 'edit_customer_company',
                    'input_name'            => 'company_search',
                    'placeholder'           => 'Search for a company...',
                    'ajax_action'           => 'oo_search_companies',
                    'render_item_callback'  => 'renderCompanyItem',
                    'on_select_callback'    => 'onEditCompanySelect',
                    'on_add_new_callback'   => 'onAddNewCompany',
                    'nonce'                 => wp_create_nonce('oo_search_companies_nonce'),
                    'add_new_text'          => 'Create New Company',
                    'hidden_field_id'       => 'edit_selected_company_id',
                    'hidden_field_name'     => 'company_id'
                ));
                ?>
            </div>
            
            <div class="oo-form-field">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Update Customer', 'operations-organizer' ); ?></button>
                <button type="button" class="button oo-modal-cancel"><?php esc_html_e( 'Cancel', 'operations-organizer' ); ?></button>
            </div>
        </form>
    </div>
</div>

<?php
// Register autocomplete callbacks
oo_register_default_autocomplete_callbacks();
?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Company autocomplete callback functions
    window.OO_Autocomplete_Callbacks = window.OO_Autocomplete_Callbacks || {};
    
    /**
     * Handle company selection in the add modal
     */
    window.OO_Autocomplete_Callbacks.onCompanySelect = function(company, $input) {
        $('#selected_company_id').val(company.id);
        $input.val(company.name);
        console.log('Company selected:', company);
    };
    
    /**
     * Handle company selection in the edit modal
     */
    window.OO_Autocomplete_Callbacks.onEditCompanySelect = function(company, $input) {
        $('#edit_selected_company_id').val(company.id);
        $input.val(company.name);
        console.log('Company selected for edit:', company);
    };
    
    /**
     * Handle "Add New Company" action
     */
    window.OO_Autocomplete_Callbacks.onAddNewCompany = function(searchTerm, $input) {
        alert('Add New Company functionality will be implemented. Search term: ' + searchTerm);
    };
    
    // Modal handling
    $('#openAddCustomerModalBtn').on('click', function() {
        $('#addCustomerModal').show();
        $('#modal_customer_name').focus();
    });
    
    $('.oo-edit-customer-button').on('click', function() {
        var customerId = $(this).data('customer-id');
        loadCustomerForEdit(customerId);
    });
    
    $('.oo-modal-close, .oo-modal-cancel').on('click', function() {
        $(this).closest('.oo-modal').hide();
        // Reset forms
        $('#addCustomerForm')[0].reset();
        $('#editCustomerForm')[0].reset();
    });
    
    // Close modal when clicking outside
    $('.oo-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Form submissions
    $('#addCustomerForm').on('submit', function(e) {
        e.preventDefault();
        submitCustomerForm(this, 'add');
    });
    
    $('#editCustomerForm').on('submit', function(e) {
        e.preventDefault();
        submitCustomerForm(this, 'edit');
    });
    
    function loadCustomerForEdit(customerId) {
        $.ajax({
            url: oo_data.ajax_url,
            type: 'POST',
            data: {
                action: 'oo_get_customer_details',
                customer_id: customerId,
                nonce: oo_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    var customer = response.data;
                    $('#edit_customer_id').val(customer.customer_id);
                    $('#edit_customer_name').val(customer.name);
                    $('#edit_customer_email').val(customer.email);
                    $('#edit_customer_company').val(customer.company_name || '');
                    $('#edit_selected_company_id').val(customer.company_id || '');
                    
                    // Load phone data into phone repeater
                    var phoneData = [];
                    if (customer.phone) {
                        try {
                            phoneData = JSON.parse(customer.phone);
                        } catch (e) {
                            // Legacy format - convert to array
                            phoneData = [{
                                type: 'Phone',
                                number: customer.phone,
                                extension: '',
                                is_primary: true
                            }];
                        }
                    }
                    
                    // Set phone repeater data (assuming the widget has a setPhoneData method)
                    if (window.OO_PhoneRepeater && window.OO_PhoneRepeater.instances) {
                        // Find the edit phone repeater instance and set data
                        // This would need to be implemented in the phone repeater widget
                    }
                    
                    $('#editCustomerModal').show();
                    $('#edit_customer_name').focus();
                } else {
                    alert('Error loading customer details: ' + response.data.message);
                }
            },
            error: function() {
                alert('Error loading customer details. Please try again.');
            }
        });
    }
    
    function submitCustomerForm(form, action) {
        var $form = $(form);
        var formData = new FormData(form);
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Reload the page to show updated data
                window.location.reload();
            },
            error: function() {
                alert('Error ' + (action === 'add' ? 'adding' : 'updating') + ' customer. Please try again.');
            }
        });
    }
});
</script> 