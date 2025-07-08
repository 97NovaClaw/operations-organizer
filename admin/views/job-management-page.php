<?php
// /admin/views/job-management-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

global $jobs, $total_jobs, $current_page, $per_page, $search_term;

?>
<div class="wrap oo-job-management-page">
    <h1><?php esc_html_e( 'Job Management', 'operations-organizer' ); ?></h1>

    <?php if ( isset( $GLOBALS['oo_job_error'] ) ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $GLOBALS['oo_job_error'] ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( isset( $GLOBALS['oo_job_success'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html( $GLOBALS['oo_job_success'] ); ?></p>
        </div>
    <?php endif; ?>

    <div class="oo-add-job-form-container">
        <div class="oo-dashboard-section">
            <h2><?php esc_html_e( 'Add New Job', 'operations-organizer' ); ?></h2>
            <form method="post" class="oo-add-job-form">
                <?php wp_nonce_field( 'oo_add_job_nonce', 'oo_add_job_nonce' ); ?>
                
                <!-- Job Streams Section -->
                <div class="oo-form-section">
                    <h3 class="oo-section-title"><?php esc_html_e( 'Job Streams', 'operations-organizer' ); ?></h3>
                    <p class="description" style="margin-bottom: 15px; color: #646970;"><?php esc_html_e( 'Select which types of work this job will include.', 'operations-organizer' ); ?></p>
                    <div class="oo-form-field">
                        <fieldset class="oo-checkbox-group">
                            <legend class="screen-reader-text"><?php esc_html_e( 'Select streams for this job', 'operations-organizer' ); ?></legend>
                            <div class="oo-checkbox-grid">
                                <?php foreach ($GLOBALS['streams'] as $stream): ?>
                                <label class="oo-checkbox-item">
                                    <input type="checkbox" id="stream_<?php echo esc_attr($stream->stream_id); ?>" name="stream_<?php echo esc_attr($stream->stream_id); ?>" value="1" />
                                    <span class="oo-checkbox-label"><?php echo esc_html($stream->stream_name); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                    </div>
                </div>
                
                <!-- Job Details Section -->
                <div class="oo-form-section">
                    <h3 class="oo-section-title"><?php esc_html_e( 'Job Details', 'operations-organizer' ); ?></h3>
                    <div class="oo-form-grid">
                        <div class="oo-form-field">
                            <label for="job_number"><?php esc_html_e( 'Job Number', 'operations-organizer' ); ?> <span class="required">*</span></label>
                            <input type="text" id="job_number" name="job_number" required class="regular-text" />
                        </div>
                        <div class="oo-form-field">
                            <label for="claim_number"><?php esc_html_e( 'Claim Number', 'operations-organizer' ); ?></label>
                            <input type="text" id="claim_number" name="claim_number" class="regular-text" />
                        </div>
                        <div class="oo-form-field">
                            <label for="start_date"><?php esc_html_e( 'Start Date', 'operations-organizer' ); ?></label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr(current_time('Y-m-d')); ?>" class="regular-text" />
                        </div>
                    </div>
                </div>

                <!-- Job Address Section -->
                <div class="oo-form-section">
                    <h3 class="oo-section-title"><?php esc_html_e( 'Job Address', 'operations-organizer' ); ?></h3>
                    <div class="oo-form-grid">
                        <div class="oo-form-field oo-form-field-full">
                            <label for="address"><?php esc_html_e( 'Address', 'operations-organizer' ); ?></label>
                            <input type="text" id="address" name="address" class="regular-text" />
                        </div>
                        <div class="oo-form-field">
                            <label for="city"><?php esc_html_e( 'City', 'operations-organizer' ); ?></label>
                            <input type="text" id="city" name="city" class="regular-text" />
                        </div>
                        <div class="oo-form-field">
                            <label for="province"><?php esc_html_e( 'Province', 'operations-organizer' ); ?></label>
                            <input type="text" id="province" name="province" class="regular-text" />
                        </div>
                        <div class="oo-form-field">
                            <label for="postal_code"><?php esc_html_e( 'Postal Code', 'operations-organizer' ); ?></label>
                            <input type="text" id="postal_code" name="postal_code" class="regular-text" />
                        </div>
                    </div>
                </div>

                <!-- Client Information Section -->
                <div class="oo-form-section">
                    <h3 class="oo-section-title"><?php esc_html_e( 'Client Information', 'operations-organizer' ); ?></h3>
                    <p class="description" style="margin-bottom: 15px; color: #646970;"><?php esc_html_e( 'The contractor, adjuster, or company assigning this work to you.', 'operations-organizer' ); ?></p>
                    <div class="oo-form-grid">
                        <div class="oo-form-field">
                            <label for="client_name"><?php esc_html_e( 'Client Name', 'operations-organizer' ); ?></label>
                            <input type="text" id="client_name" name="client_name" class="regular-text" />
                        </div>
                        <div class="oo-form-field">
                            <label for="client_phone"><?php esc_html_e( 'Client Phone Number(s)', 'operations-organizer' ); ?></label>
                            <input type="text" id="client_phone" name="client_phone" class="regular-text" />
                        </div>
                        <div class="oo-form-field">
                            <label for="client_email"><?php esc_html_e( 'Client Email(s)', 'operations-organizer' ); ?></label>
                            <input type="email" id="client_email" name="client_email" class="regular-text" />
                        </div>
                        <div class="oo-form-field oo-form-field-full">
                            <label for="client_contact"><?php esc_html_e( 'Additional Client Contact Info', 'operations-organizer' ); ?></label>
                            <textarea id="client_contact" name="client_contact" rows="2" placeholder="<?php esc_attr_e( 'Any additional contact information or notes about the client', 'operations-organizer' ); ?>" class="regular-text"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Customer Information Section -->
                <div class="oo-form-section">
                    <h3 class="oo-section-title"><?php esc_html_e( 'Customer Information', 'operations-organizer' ); ?></h3>
                    <p class="description" style="margin-bottom: 15px; color: #646970;"><?php esc_html_e( 'The homeowner or policy holder whose belongings need to be cleaned.', 'operations-organizer' ); ?></p>
                    <div class="oo-form-grid">
                        <div class="oo-form-field">
                            <label for="customer_name"><?php esc_html_e( 'Customer Name', 'operations-organizer' ); ?></label>
                            <?php
                            // Use our new autocomplete component for customer selection
                            echo oo_get_autocomplete_html(array(
                                'input_id'              => 'customer_name',
                                'input_name'            => 'customer_name',
                                'placeholder'           => 'Type customer name...',
                                'ajax_action'           => 'oo_search_customers',
                                'render_item_callback'  => 'renderCustomerItem',
                                'on_select_callback'    => 'onCustomerSelect',
                                'on_add_new_callback'   => 'onAddNewCustomer',
                                'nonce'                 => wp_create_nonce('oo_search_customers_nonce'),
                                'add_new_text'          => 'Add New Customer',
                                'hidden_field_id'       => 'customer_id',
                                'hidden_field_name'     => 'customer_id'
                            ));
                            ?>
                            <p class="description"><?php esc_html_e( 'Start typing to search existing customers or select "Add New Customer" from the dropdown.', 'operations-organizer' ); ?></p>
                        </div>
                    </div>
                </div>

                <div class="oo-form-actions">
                    <input type="submit" name="submit_add_job" id="submit_add_job" class="button button-primary button-large" value="<?php esc_attr_e( 'Add Job', 'operations-organizer' ); ?>" />
                </div>
            </form>
        </div>
    </div>

    <h2><?php esc_html_e( 'Jobs List', 'operations-organizer' ); ?></h2>

    <form method="get" class="oo-filters-form">
        <input type="hidden" name="page" value="oo_jobs" />
        <div class="wp-filter">
            <p class="search-box">
                <label class="screen-reader-text" for="job-search-input"><?php esc_html_e( 'Search Jobs:', 'operations-organizer' ); ?></label>
                <input type="search" id="job-search-input" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search jobs...', 'operations-organizer' ); ?>" />
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Jobs', 'operations-organizer' ); ?>" />
            </p>
        </div>
    </form>
    <div class="clear"></div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e( 'Job Number', 'operations-organizer' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Client', 'operations-organizer' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Start Date', 'operations-organizer' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Streams', 'operations-organizer' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Actions', 'operations-organizer' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $jobs ) ) : ?>
                <?php foreach ( $jobs as $job ) : 
                    // Get associated streams for this job
                    $job_stream_links = OO_DB::get_job_streams_for_job($job->job_id);
                    $stream_names = array();
                    
                    foreach ($job_stream_links as $job_stream) {
                        // Use the helper function to get stream name
                        $stream_name = oo_get_stream_name($job_stream->stream_id);
                        if ($stream_name) {
                            $stream_names[] = esc_html($stream_name);
                        }
                    }
                ?>
                    <tr>
                        <td><?php echo esc_html( $job->job_number ); ?></td>
                        <td><?php echo esc_html( $job->client_name ); ?></td>
                        <td><?php echo $job->start_date ? esc_html( $job->start_date ) : '—'; ?></td>
                        <td><?php echo !empty($stream_names) ? implode(', ', $stream_names) : '—'; ?></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=oo_dashboard&filter_job_number=' . urlencode( $job->job_number ) ) ); ?>" class="button button-secondary"><?php esc_html_e( 'View Logs', 'operations-organizer' ); ?></a>
                            <button class="button button-secondary oo-edit-job-button" data-job-id="<?php echo esc_attr( $job->job_id ); ?>"><?php esc_html_e( 'Edit', 'operations-organizer' ); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5"><?php esc_html_e( 'No jobs found.', 'operations-organizer' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    // Pagination
    if ( $total_jobs > $per_page ) {
        $base_url = remove_query_arg( array( 'paged', 'filter_action' ), wp_unslash( $_SERVER['REQUEST_URI'] ) );
        $page_links = paginate_links( array(
            'base' => $base_url . '%_%',
            'format' => '&paged=%#%',
            'prev_text' => __( '&laquo; Previous' ),
            'next_text' => __( 'Next &raquo;' ),
            'total' => ceil( $total_jobs / $per_page ),
            'current' => $current_page,
            'add_args' => array_map( 'urlencode', array_filter( compact( 's', 'status_filter' ) ) )
        ) );

        if ( $page_links ) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
        }
    }
    ?>
</div>

<!-- Customer Modal -->
<div id="customerModal" class="oo-modal">
    <div class="oo-modal-content">
        <span class="oo-modal-close">&times;</span>
        <h2><?php esc_html_e( 'Add New Customer', 'operations-organizer' ); ?></h2>
        <form id="addCustomerForm">
            <div class="form-field">
                <label for="modal_customer_name"><?php esc_html_e( 'Customer Name', 'operations-organizer' ); ?> <span class="required">*</span></label>
                <input type="text" id="modal_customer_name" name="name" required class="regular-text" />
            </div>
            <div class="form-field">
                <label for="modal_customer_email"><?php esc_html_e( 'Email', 'operations-organizer' ); ?></label>
                <input type="email" id="modal_customer_email" name="email" class="regular-text" />
            </div>
            <div class="form-field">
                <label for="modal_customer_phone"><?php esc_html_e( 'Phone', 'operations-organizer' ); ?></label>
                <input type="text" id="modal_customer_phone" name="phone" class="regular-text" />
            </div>
            <div class="form-field">
                <label for="modal_customer_company"><?php esc_html_e( 'Company', 'operations-organizer' ); ?></label>
                <?php
                // Use our new autocomplete component for company selection
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
            <div class="form-field">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Add Customer', 'operations-organizer' ); ?></button>
                <button type="button" class="button oo-modal-cancel"><?php esc_html_e( 'Cancel', 'operations-organizer' ); ?></button>
            </div>
        </form>
    </div>
</div>

<?php
// Register default autocomplete callbacks
oo_register_default_autocomplete_callbacks();
?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Company autocomplete callback functions
    window.OO_Autocomplete_Callbacks = window.OO_Autocomplete_Callbacks || {};
    
    /**
     * Handle company selection in the modal
     */
    window.OO_Autocomplete_Callbacks.onCompanySelect = function(company, $input) {
        // Set the hidden field value
        $('#selected_company_id').val(company.id);
        // Set the display name in the input
        $input.val(company.name);
        console.log('Company selected:', company);
    };
    
    /**
     * Handle "Add New Company" action
     */
    window.OO_Autocomplete_Callbacks.onAddNewCompany = function(searchTerm, $input) {
        // For now, just alert - this would open a company creation modal
        alert('Add New Company functionality will be implemented next. Search term: ' + searchTerm);
    };
    
    /**
     * Handle customer selection in the main form
     */
    window.OO_Autocomplete_Callbacks.onCustomerSelect = function(customer, $input) {
        // Set the hidden field value
        $('#customer_id').val(customer.id);
        // Set the display name in the input
        $input.val(customer.name);
        console.log('Customer selected:', customer);
    };
    
    /**
     * Handle "Add New Customer" action
     */
    window.OO_Autocomplete_Callbacks.onAddNewCustomer = function(searchTerm, $input) {
        // Open the customer modal with the search term pre-filled
        $('#modal_customer_name').val(searchTerm);
        $('#customerModal').show();
        $('#modal_customer_name').focus();
    };
    // Edit job button functionality
    $('.oo-edit-job-button').on('click', function() {
        var jobId = $(this).data('job-id');
        alert('Job editing will open a detailed form with stream-specific fields for Soft Content, Electronics, Art, and Content data. This functionality will be implemented in the next update. Job ID: ' + jobId);
    });

    // Success notification function
    function showSuccessNotification(message) {
        // Remove any existing notifications
        $('.oo-success-notification').remove();
        
        // Create notification element
        const $notification = $('<div class="oo-success-notification">' +
            '<div class="oo-notification-content">' +
                '<span class="oo-notification-icon">✓</span>' +
                '<span class="oo-notification-message">' + message + '</span>' +
                '<span class="oo-notification-close">&times;</span>' +
            '</div>' +
        '</div>');
        
        // Add to page
        $('body').append($notification);
        
        // Show with animation
        setTimeout(function() {
            $notification.addClass('show');
        }, 100);
        
        // Auto-hide after 4 seconds
        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 4000);
        
        // Manual close
        $notification.find('.oo-notification-close').on('click', function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        });
    }

    // Modal functionality
    
    $('.oo-modal-close, .oo-modal-cancel').on('click', function() {
        $('#customerModal').hide();
        $('#addCustomerForm')[0].reset();
        // Clear company autocomplete fields
        $('#modal_customer_company').val('');
        $('#selected_company_id').val('');
    });
    
    // Close modal when clicking outside
    $('#customerModal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
            $('#addCustomerForm')[0].reset();
            // Clear company autocomplete fields
            $('#modal_customer_company').val('');
            $('#selected_company_id').val('');
        }
    });
    
    // Add customer form submission
    $('#addCustomerForm').on('submit', function(e) {
        e.preventDefault();
        
        // Check if oo_data is available
        if (typeof oo_data === 'undefined') {
            alert('Configuration error. Please refresh the page and try again.');
            return;
        }
        
        const formData = {
            action: 'oo_add_customer',
            nonce: oo_data.nonce_add_customer,
            name: $('#modal_customer_name').val(),
            email: $('#modal_customer_email').val(),
            phone: $('#modal_customer_phone').val(),
            company_id: $('#selected_company_id').val()
        };
        
        $.post(oo_data.ajax_url, formData)
        .done(function(response) {
            if (response.success) {
                // Select the newly created customer using our callback
                window.OO_Autocomplete_Callbacks.onCustomerSelect(response.data.customer, $('#customer_name'));
                $('#customerModal').hide();
                $('#addCustomerForm')[0].reset();
                // Also clear the company autocomplete fields
                $('#modal_customer_company').val('');
                $('#selected_company_id').val('');
                
                // Show success notification with better styling
                showSuccessNotification(response.data.message);
            } else {
                alert('Error: ' + response.data.message);
            }
        })
        .fail(function() {
            alert('Error adding customer. Please try again.');
        });
    });
});
</script> 