<?php
// /admin/views/company-management-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

global $companies, $total_companies, $current_page, $per_page, $search_term;

?>
<div class="wrap oo-company-management-page">
    <h1><?php esc_html_e( 'Company Management', 'operations-organizer' ); ?></h1>

    <?php if ( isset( $GLOBALS['oo_company_error'] ) ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $GLOBALS['oo_company_error'] ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( isset( $GLOBALS['oo_company_success'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html( $GLOBALS['oo_company_success'] ); ?></p>
        </div>
    <?php endif; ?>

    <button id="openAddCompanyModalBtn" class="page-title-action oo-open-modal-button" data-modal-id="addCompanyModal"><?php esc_html_e( 'Add New Company', 'operations-organizer' ); ?></button>

    <form method="get" class="oo-filters-form">
        <input type="hidden" name="page" value="oo_companies" />
        <div class="wp-filter">
            <p class="search-box">
                <label class="screen-reader-text" for="company-search-input"><?php esc_html_e( 'Search Companies:', 'operations-organizer' ); ?></label>
                <input type="search" id="company-search-input" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search by Name or Address', 'operations-organizer' ); ?>" />
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Companies', 'operations-organizer' ); ?>" />
            </p>
        </div>
    </form>
    <div class="clear"></div>

    <div id="oo-company-list-table-wrapper">
        <table class="wp-list-table widefat fixed striped table-view-list companies">
            <thead>
                <tr>
                    <?php 
                    $columns = [
                        'name' => __('Company Name', 'operations-organizer'),
                        'address' => __('Address', 'operations-organizer'),
                        'phone_numbers' => __('Phone Numbers', 'operations-organizer'),
                        'email_addresses' => __('Email Addresses', 'operations-organizer'),
                        'customer_count' => __('Customers', 'operations-organizer'),
                        'created_at' => __('Created', 'operations-organizer'),
                        'actions' => __('Actions', 'operations-organizer'),
                    ];
                    $current_orderby = isset($GLOBALS['orderby']) ? $GLOBALS['orderby'] : 'name';
                    $current_order = isset($GLOBALS['order']) ? strtolower($GLOBALS['order']) : 'asc';

                    foreach($columns as $slug => $title) {
                        $class = "manage-column column-$slug";
                        $sort_link = '';
                        if (in_array($slug, ['name', 'created_at'])) { // Sortable columns
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
                <?php if ( ! empty( $companies ) ) : ?>
                    <?php foreach ( $companies as $company ) : ?>
                        <tr id="company-<?php echo $company->company_id; ?>">
                            <td class="name column-name" data-colname="<?php esc_attr_e('Company Name', 'operations-organizer'); ?>">
                                <strong><?php echo esc_html( $company->name ); ?></strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="#" class="oo-edit-company-button" data-company-id="<?php echo esc_attr( $company->company_id ); ?>"><?php esc_html_e('Edit', 'operations-organizer'); ?></a>
                                    </span>
                                </div>
                            </td>
                            <td class="address column-address" data-colname="<?php esc_attr_e('Address', 'operations-organizer'); ?>">
                                <?php 
                                $address_parts = array_filter(array(
                                    $company->address,
                                    $company->city,
                                    $company->province,
                                    $company->postal_code
                                ));
                                echo !empty($address_parts) ? esc_html( implode(', ', $address_parts) ) : '—';
                                ?>
                            </td>
                            <td class="phone_numbers column-phone_numbers" data-colname="<?php esc_attr_e('Phone Numbers', 'operations-organizer'); ?>">
                                <?php 
                                if ( $company->phone_numbers ) {
                                    // Check if it's JSON data
                                    $phone_data = json_decode( $company->phone_numbers, true );
                                    if ( json_last_error() === JSON_ERROR_NONE && is_array( $phone_data ) ) {
                                        echo oo_format_phone_display( $phone_data, 'primary_only' );
                                    } else {
                                        echo esc_html( $company->phone_numbers );
                                    }
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="email_addresses column-email_addresses" data-colname="<?php esc_attr_e('Email Addresses', 'operations-organizer'); ?>">
                                <?php 
                                if ( !empty($company->email_addresses) ) {
                                    // Check if it's JSON data
                                    $email_data = json_decode( $company->email_addresses, true );
                                    if ( json_last_error() === JSON_ERROR_NONE && is_array( $email_data ) ) {
                                        echo oo_format_email_display_advanced( $email_data, 'first_only' );
                                    } else {
                                        echo esc_html( $company->email_addresses );
                                    }
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="customer_count column-customer_count" data-colname="<?php esc_attr_e('Customers', 'operations-organizer'); ?>">
                                <?php 
                                $customer_count = isset($company->customer_count) ? intval($company->customer_count) : 0;
                                if ($customer_count > 0) {
                                    echo '<a href="' . esc_url( admin_url( 'admin.php?page=oo_customers&company_filter=' . $company->company_id ) ) . '">' . $customer_count . '</a>';
                                } else {
                                    echo '0';
                                }
                                ?>
                            </td>
                            <td class="created_at column-created_at" data-colname="<?php esc_attr_e('Created', 'operations-organizer'); ?>">
                                <?php echo $company->created_at ? esc_html( date_i18n( get_option('date_format'), strtotime( $company->created_at ) ) ) : '—'; ?>
                            </td>
                            <td class="actions column-actions" data-colname="<?php esc_attr_e('Actions', 'operations-organizer'); ?>">
                                <button class="button-secondary oo-edit-company-button" data-company-id="<?php echo esc_attr( $company->company_id ); ?>"><?php esc_html_e('Edit', 'operations-organizer'); ?></button>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=oo_customers&company_filter=' . $company->company_id ) ); ?>" class="button-secondary"><?php esc_html_e('View Customers', 'operations-organizer'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6"><?php esc_html_e( 'No companies found.', 'operations-organizer' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    // Pagination
    if ( $total_companies > $per_page ) {
        $base_url = remove_query_arg( array( 'paged', 'filter_action' ), wp_unslash( $_SERVER['REQUEST_URI'] ) );
        $page_links = paginate_links( array(
            'base' => $base_url . '%_%',
            'format' => '&paged=%#%',
            'prev_text' => __( '&laquo; Previous' ),
            'next_text' => __( 'Next &raquo;' ),
            'total' => ceil( $total_companies / $per_page ),
            'current' => $current_page,
            'add_args' => array_map( 'urlencode', array_filter( compact( 's' ) ) )
        ) );
        if ( $page_links ) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
        }
    }
    ?>
</div>

<!-- Add Company Modal -->
<div id="addCompanyModal" class="oo-modal" style="display: none;">
    <div class="oo-modal-content">
        <div class="oo-modal-header">
            <h2><?php esc_html_e( 'Add New Company', 'operations-organizer' ); ?></h2>
            <span class="oo-modal-close">&times;</span>
        </div>
        <form id="addCompanyForm" method="post">
            <?php wp_nonce_field( 'oo_add_company_nonce', 'oo_add_company_nonce' ); ?>
            <input type="hidden" name="oo_action" value="add_company" />
            
            <div class="oo-form-field">
                <label for="modal_company_name"><?php esc_html_e( 'Company Name', 'operations-organizer' ); ?> <span class="required">*</span></label>
                <input type="text" id="modal_company_name" name="name" class="regular-text" required />
            </div>
            
            <div class="oo-form-field">
                <label for="modal_company_address"><?php esc_html_e( 'Address', 'operations-organizer' ); ?></label>
                <input type="text" id="modal_company_address" name="address" class="regular-text" />
            </div>
            
            <div class="oo-form-grid">
                <div class="oo-form-field">
                    <label for="modal_company_city"><?php esc_html_e( 'City', 'operations-organizer' ); ?></label>
                    <input type="text" id="modal_company_city" name="city" class="regular-text" />
                </div>
                <div class="oo-form-field">
                    <label for="modal_company_province"><?php esc_html_e( 'Province', 'operations-organizer' ); ?></label>
                    <input type="text" id="modal_company_province" name="province" class="regular-text" />
                </div>
                <div class="oo-form-field">
                    <label for="modal_company_postal_code"><?php esc_html_e( 'Postal Code', 'operations-organizer' ); ?></label>
                    <input type="text" id="modal_company_postal_code" name="postal_code" class="regular-text" />
                </div>
            </div>
            
            <div class="oo-form-field">
                <label for="modal_company_phone_container"><?php esc_html_e( 'Phone Numbers', 'operations-organizer' ); ?></label>
                <?php
                echo oo_get_phone_repeater_html(array(
                    'container_id'   => 'modal_company_phone_container',
                    'field_name'     => 'phone_numbers',
                    'add_button_text' => 'Add Phone Number',
                    'max_phones'     => 10
                ));
                ?>
            </div>
            
            <div class="oo-form-field">
                <label for="modal_company_email_container"><?php esc_html_e( 'Email Addresses', 'operations-organizer' ); ?></label>
                <?php
                echo oo_get_email_repeater_html(array(
                    'container_id'   => 'modal_company_email_container',
                    'field_name'     => 'email_addresses',
                    'add_button_text' => 'Add Email',
                    'max_emails'     => 10
                ));
                ?>
            </div>
            
            <div class="oo-form-field">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Add Company', 'operations-organizer' ); ?></button>
                <button type="button" class="button oo-modal-cancel"><?php esc_html_e( 'Cancel', 'operations-organizer' ); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Company Modal -->
<div id="editCompanyModal" class="oo-modal" style="display: none;">
    <div class="oo-modal-content">
        <div class="oo-modal-header">
            <h2><?php esc_html_e( 'Edit Company', 'operations-organizer' ); ?></h2>
            <span class="oo-modal-close">&times;</span>
        </div>
        <form id="editCompanyForm" method="post">
            <?php wp_nonce_field( 'oo_edit_company_nonce', 'oo_edit_company_nonce' ); ?>
            <input type="hidden" name="oo_action" value="edit_company" />
            <input type="hidden" id="edit_company_id" name="company_id" />
            
            <div class="oo-form-field">
                <label for="edit_company_name"><?php esc_html_e( 'Company Name', 'operations-organizer' ); ?> <span class="required">*</span></label>
                <input type="text" id="edit_company_name" name="name" class="regular-text" required />
            </div>
            
            <div class="oo-form-field">
                <label for="edit_company_address"><?php esc_html_e( 'Address', 'operations-organizer' ); ?></label>
                <input type="text" id="edit_company_address" name="address" class="regular-text" />
            </div>
            
            <div class="oo-form-grid">
                <div class="oo-form-field">
                    <label for="edit_company_city"><?php esc_html_e( 'City', 'operations-organizer' ); ?></label>
                    <input type="text" id="edit_company_city" name="city" class="regular-text" />
                </div>
                <div class="oo-form-field">
                    <label for="edit_company_province"><?php esc_html_e( 'Province', 'operations-organizer' ); ?></label>
                    <input type="text" id="edit_company_province" name="province" class="regular-text" />
                </div>
                <div class="oo-form-field">
                    <label for="edit_company_postal_code"><?php esc_html_e( 'Postal Code', 'operations-organizer' ); ?></label>
                    <input type="text" id="edit_company_postal_code" name="postal_code" class="regular-text" />
                </div>
            </div>
            
            <div class="oo-form-field">
                <label for="edit_company_phone_container"><?php esc_html_e( 'Phone Numbers', 'operations-organizer' ); ?></label>
                <?php
                echo oo_get_phone_repeater_html(array(
                    'container_id'   => 'edit_company_phone_container',
                    'field_name'     => 'phone_numbers',
                    'add_button_text' => 'Add Phone Number',
                    'max_phones'     => 10
                ));
                ?>
            </div>
            
            <div class="oo-form-field">
                <label for="edit_company_email_container"><?php esc_html_e( 'Email Addresses', 'operations-organizer' ); ?></label>
                <?php
                echo oo_get_email_repeater_html(array(
                    'container_id'   => 'edit_company_email_container',
                    'field_name'     => 'email_addresses',
                    'add_button_text' => 'Add Email',
                    'max_emails'     => 10
                ));
                ?>
            </div>
            
            <div class="oo-form-field">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Update Company', 'operations-organizer' ); ?></button>
                <button type="button" class="button oo-modal-cancel"><?php esc_html_e( 'Cancel', 'operations-organizer' ); ?></button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Modal handling
    $('#openAddCompanyModalBtn').on('click', function() {
        $('#addCompanyModal').show();
        $('#modal_company_name').focus();
    });
    
    $('.oo-edit-company-button').on('click', function() {
        var companyId = $(this).data('company-id');
        loadCompanyForEdit(companyId);
    });
    
    $('.oo-modal-close, .oo-modal-cancel').on('click', function() {
        $(this).closest('.oo-modal').hide();
        // Reset forms
        $('#addCompanyForm')[0].reset();
        $('#editCompanyForm')[0].reset();
    });
    
    // Close modal when clicking outside
    $('.oo-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Form submissions
    $('#addCompanyForm').on('submit', function(e) {
        e.preventDefault();
        submitCompanyForm(this, 'add');
    });
    
    $('#editCompanyForm').on('submit', function(e) {
        e.preventDefault();
        submitCompanyForm(this, 'edit');
    });
    
    function loadCompanyForEdit(companyId) {
        $.ajax({
            url: oo_data.ajax_url,
            type: 'POST',
            data: {
                action: 'oo_get_company_details',
                company_id: companyId,
                nonce: oo_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    var company = response.data;
                    $('#edit_company_id').val(company.company_id);
                    $('#edit_company_name').val(company.name);
                    $('#edit_company_address').val(company.address || '');
                    $('#edit_company_city').val(company.city || '');
                    $('#edit_company_province').val(company.province || '');
                    $('#edit_company_postal_code').val(company.postal_code || '');
                    
                    // Load phone data into phone repeater
                    var phoneData = [];
                    if (company.phone_numbers) {
                        try {
                            phoneData = JSON.parse(company.phone_numbers);
                        } catch (e) {
                            // Legacy format - convert to array
                            phoneData = [{
                                type: 'Office',
                                number: company.phone_numbers,
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
                    
                    $('#editCompanyModal').show();
                    $('#edit_company_name').focus();
                } else {
                    alert('Error loading company details: ' + response.data.message);
                }
            },
            error: function() {
                alert('Error loading company details. Please try again.');
            }
        });
    }
    
    function submitCompanyForm(form, action) {
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
                alert('Error ' + (action === 'add' ? 'adding' : 'updating') + ' company. Please try again.');
            }
        });
    }
});
</script> 