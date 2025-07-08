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
                            <div class="oo-customer-autocomplete-container">
                                <input type="text" id="customer_name" name="customer_name" placeholder="<?php esc_attr_e( 'Type customer name...', 'operations-organizer' ); ?>" autocomplete="off" class="regular-text" />
                                <input type="hidden" id="customer_id" name="customer_id" value="" />
                                <div id="customer_suggestions" class="oo-autocomplete-suggestions" style="display: none;"></div>
                                <button type="button" id="add_new_customer_btn" class="button button-secondary" style="margin-top: 5px;"><?php esc_html_e( 'Add New Customer', 'operations-organizer' ); ?></button>
                            </div>
                            <p class="description"><?php esc_html_e( 'Start typing to search existing customers or click "Add New Customer" to create one.', 'operations-organizer' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Job Streams Section -->
                <div class="oo-form-section">
                    <h3 class="oo-section-title"><?php esc_html_e( 'Job Streams', 'operations-organizer' ); ?></h3>
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
                <select id="modal_customer_company" name="company_id" class="regular-text">
                    <option value=""><?php esc_html_e( 'Select Company', 'operations-organizer' ); ?></option>
                    <?php
                    $companies = OO_DB::get_companies(array('orderby' => 'name', 'order' => 'ASC'));
                    foreach ($companies as $company) {
                        echo '<option value="' . esc_attr($company->company_id) . '">' . esc_html($company->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-field">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Add Customer', 'operations-organizer' ); ?></button>
                <button type="button" class="button oo-modal-cancel"><?php esc_html_e( 'Cancel', 'operations-organizer' ); ?></button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
console.log('🚀 [CUSTOMER AUTOCOMPLETE DEBUG] Script starting to load...');
console.log('🚀 [CUSTOMER AUTOCOMPLETE DEBUG] Current URL:', window.location.href);

jQuery(document).ready(function($) {
    console.log('🎯 [CUSTOMER AUTOCOMPLETE DEBUG] jQuery ready fired!');
    
    // === AGGRESSIVE BOTTOM-UP DEBUGGING ===
    
    // 1. Check basic jQuery and DOM readiness
    console.log('✅ [DEBUG] jQuery version:', $.fn.jquery);
    console.log('✅ [DEBUG] Document ready state:', document.readyState);
    
    // 2. Check if customer input field exists
    const $customerInput = $('#customer_name');
    console.log('🔍 [DEBUG] Customer input field found:', $customerInput.length > 0);
    console.log('🔍 [DEBUG] Customer input element:', $customerInput[0]);
    
    // 3. Check if suggestions container exists
    const $suggestionsContainer = $('#customer_suggestions');
    console.log('🔍 [DEBUG] Suggestions container found:', $suggestionsContainer.length > 0);
    console.log('🔍 [DEBUG] Suggestions element:', $suggestionsContainer[0]);
    
    // 4. Check oo_data object availability
    console.log('🔍 [DEBUG] oo_data available:', typeof oo_data !== 'undefined');
    if (typeof oo_data !== 'undefined') {
        console.log('✅ [DEBUG] oo_data.ajax_url:', oo_data.ajax_url);
        console.log('✅ [DEBUG] oo_data.nonce_search_customers:', oo_data.nonce_search_customers);
        console.log('✅ [DEBUG] oo_data.nonce_add_customer:', oo_data.nonce_add_customer);
    } else {
        console.error('❌ [DEBUG] oo_data object is NOT AVAILABLE!');
    }
    
    // 5. Test basic input event binding
    console.log('🔧 [DEBUG] Attempting to bind input event to customer field...');
    
    $customerInput.on('focus', function() {
        console.log('🎯 [DEBUG] Customer input FOCUSED!');
    });
    
    $customerInput.on('blur', function() {
        console.log('🎯 [DEBUG] Customer input BLURRED!');
    });
    
    $customerInput.on('keyup', function() {
        console.log('🎯 [DEBUG] Customer input KEYUP event fired! Value:', $(this).val());
    });
    
    $customerInput.on('input', function() {
        console.log('🎯 [DEBUG] Customer input INPUT event fired! Value:', $(this).val());
        const searchTerm = $(this).val();
        
        if (searchTerm.length < 2) {
            console.log('🔍 [DEBUG] Search term too short, clearing suggestions');
            return;
        }
        
        console.log('🔍 [DEBUG] Search term valid, proceeding with search...');
        
        // Test if we can show the suggestions container
        $suggestionsContainer.html('<div style="padding: 10px; background: yellow; color: black;">🧪 TEST: This proves the suggestions container works!</div>').show();
        console.log('🧪 [DEBUG] Test message displayed in suggestions container');
    });
    
    console.log('✅ [DEBUG] Event binding completed!');
    
    // 6. Add a visual test button to verify everything works
    $customerInput.after('<button type="button" id="test-customer-input" style="margin-left: 10px; background: red; color: white; padding: 5px;">🧪 TEST INPUT</button>');
    
    $('#test-customer-input').on('click', function() {
        console.log('🧪 [DEBUG] Test button clicked!');
        $customerInput.val('test customer').trigger('input');
        console.log('🧪 [DEBUG] Triggered input event with test value');
    });
    
    console.log('🧪 [DEBUG] Test button added next to customer input');

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

    // Customer autocomplete functionality
    let searchTimeout;
    let selectedCustomerIndex = -1;
    
    $('#customer_name').on('input', function() {
        const searchTerm = $(this).val();
        const $suggestions = $('#customer_suggestions');
        
        if (searchTerm.length < 2) {
            $suggestions.hide().empty();
            $('#customer_id').val('');
            return;
        }
        
        clearTimeout(searchTimeout);
        
        // Show loading state
        const $suggestions = $('#customer_suggestions');
        $suggestions.empty()
            .addClass('loading')
            .html('<div class="oo-autocomplete-suggestion" style="text-align: center; padding: 20px;">🔍 Searching customers...</div>')
            .show();
        
        searchTimeout = setTimeout(function() {
            // Check if oo_data is available
            if (typeof oo_data === 'undefined') {
                console.error('oo_data not available for customer search');
                $suggestions.removeClass('loading')
                    .empty()
                    .html('<div class="oo-autocomplete-suggestion no-results">Configuration error. Please refresh the page.</div>')
                    .show();
                return;
            }

            $.post(oo_data.ajax_url, {
                action: 'oo_search_customers',
                nonce: oo_data.nonce_search_customers,
                search: searchTerm
            })
            .done(function(response) {
                $suggestions.removeClass('loading');
                if (response.success) {
                    displayCustomerSuggestions(response.data.customers);
                } else {
                    $suggestions.empty()
                        .html('<div class="oo-autocomplete-suggestion no-results">Search failed. Please try again.</div>')
                        .show();
                }
            })
            .fail(function() {
                $suggestions.removeClass('loading')
                    .empty()
                    .html('<div class="oo-autocomplete-suggestion no-results">Search failed. Please try again.</div>')
                    .show();
            });
        }, 300);
    });

    function displayCustomerSuggestions(customers) {
        const $suggestions = $('#customer_suggestions');
        $suggestions.empty();
        selectedCustomerIndex = -1;
        
        if (customers.length === 0) {
            // Show no results message
            const $noResults = $('<div>')
                .addClass('oo-autocomplete-suggestion no-results')
                .html('No customers found. Click "Add New Customer" to create one.');
            $suggestions.append($noResults).show();
            return;
        }
        
        customers.forEach(function(customer, index) {
            // Generate customer initials for icon
            const initials = customer.name.split(' ')
                .map(word => word.charAt(0).toUpperCase())
                .slice(0, 2)
                .join('');
            
            // Build customer info string
            let customerInfo = [];
            if (customer.email) customerInfo.push(customer.email);
            if (customer.phone) customerInfo.push(customer.phone);
            if (customer.company_name) customerInfo.push(customer.company_name);
            
            const $suggestion = $('<div>')
                .addClass('oo-autocomplete-suggestion')
                .attr('data-index', index)
                .attr('data-customer-id', customer.id)
                .html(
                    '<div class="customer-icon">' + initials + '</div>' +
                    '<div class="customer-details">' +
                        '<div class="customer-name">' + customer.name + '</div>' +
                        (customerInfo.length > 0 ? '<div class="oo-customer-info">' + customerInfo.join(' • ') + '</div>' : '') +
                    '</div>'
                );
            
            $suggestion.on('click', function() {
                selectCustomer(customer);
            });
            
            $suggestions.append($suggestion);
        });
        
        // Add "Add New Customer" option at the bottom
        const currentSearch = $('#customer_name').val();
        if (currentSearch.length >= 2) {
            const $addNew = $('<div>')
                .addClass('oo-autocomplete-suggestion add-new-customer')
                .html(
                    '<div class="customer-icon">+</div>' +
                    '<div class="customer-details">' +
                        '<div class="customer-name">Add New Customer</div>' +
                        '<div class="oo-customer-info">Create "' + currentSearch + '" as a new customer</div>' +
                    '</div>'
                );
            
            $addNew.on('click', function() {
                $('#customer_suggestions').hide();
                $('#modal_customer_name').val(currentSearch);
                $('#customerModal').show();
                $('#modal_customer_name').focus();
            });
            
            $suggestions.append($addNew);
        }
        
        $suggestions.show();
    }
    
    function selectCustomer(customer) {
        $('#customer_name').val(customer.name);
        $('#customer_id').val(customer.id);
        $('#customer_suggestions').hide();
        selectedCustomerIndex = -1;
    }
    
    // Keyboard navigation for suggestions
    $('#customer_name').on('keydown', function(e) {
        const $suggestions = $('#customer_suggestions .oo-autocomplete-suggestion');
        
        if ($suggestions.length === 0) return;
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedCustomerIndex = Math.min(selectedCustomerIndex + 1, $suggestions.length - 1);
            updateSelectedSuggestion();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedCustomerIndex = Math.max(selectedCustomerIndex - 1, -1);
            updateSelectedSuggestion();
        } else if (e.key === 'Enter' && selectedCustomerIndex >= 0) {
            e.preventDefault();
            const customerId = $suggestions.eq(selectedCustomerIndex).attr('data-customer-id');
            const customerName = $suggestions.eq(selectedCustomerIndex).find('strong').text();
            selectCustomer({id: customerId, name: customerName});
        } else if (e.key === 'Escape') {
            $('#customer_suggestions').hide();
            selectedCustomerIndex = -1;
        }
    });
    
    function updateSelectedSuggestion() {
        const $suggestions = $('#customer_suggestions .oo-autocomplete-suggestion');
        $suggestions.removeClass('selected');
        if (selectedCustomerIndex >= 0) {
            $suggestions.eq(selectedCustomerIndex).addClass('selected');
        }
    }
    
    // Hide suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.oo-customer-autocomplete-container').length) {
            $('#customer_suggestions').hide();
        }
    });
    
    // Add new customer modal functionality
    $('#add_new_customer_btn').on('click', function() {
        $('#customerModal').show();
        $('#modal_customer_name').focus();
    });
    
    $('.oo-modal-close, .oo-modal-cancel').on('click', function() {
        $('#customerModal').hide();
        $('#addCustomerForm')[0].reset();
    });
    
    // Close modal when clicking outside
    $('#customerModal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
            $('#addCustomerForm')[0].reset();
        }
    });
    
    // Add customer form submission
    $('#addCustomerForm').on('submit', function(e) {
        e.preventDefault();
        
        // Check if oo_data is available
        if (typeof oo_data === 'undefined') {
            console.error('oo_data not available for add customer');
            alert('Configuration error. Please refresh the page and try again.');
            return;
        }
        
        const formData = {
            action: 'oo_add_customer',
            nonce: oo_data.nonce_add_customer,
            name: $('#modal_customer_name').val(),
            email: $('#modal_customer_email').val(),
            phone: $('#modal_customer_phone').val(),
            company_id: $('#modal_customer_company').val()
        };
        
        $.post(oo_data.ajax_url, formData)
        .done(function(response) {
            if (response.success) {
                // Select the newly created customer
                selectCustomer(response.data.customer);
                $('#customerModal').hide();
                $('#addCustomerForm')[0].reset();
                
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