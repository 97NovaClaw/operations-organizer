<?php
// /includes/class-oo-customer.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Customer {

    public $customer_id;
    public $name;
    public $email;
    public $phone;
    public $company_id;
    public $created_at;
    public $updated_at;

    public function __construct( $customer = null ) {
        if ( $customer ) {
            $this->load( $customer );
        }
    }

    public function load( $customer ) {
        if ( is_object( $customer ) ) {
            $this->customer_id = isset( $customer->customer_id ) ? intval( $customer->customer_id ) : null;
            $this->name        = isset( $customer->name ) ? $customer->name : null;
            $this->email       = isset( $customer->email ) ? $customer->email : null;
            $this->phone       = isset( $customer->phone ) ? $customer->phone : null;
            $this->company_id  = isset( $customer->company_id ) ? intval( $customer->company_id ) : null;
            $this->created_at  = isset( $customer->created_at ) ? $customer->created_at : null;
            $this->updated_at  = isset( $customer->updated_at ) ? $customer->updated_at : null;
        } elseif ( is_numeric( $customer ) ) {
            $data = OO_DB::get_customer( intval( $customer ) );
            if ( $data ) {
                $this->load( $data );
            }
        }
    }

    /**
     * Save the customer data to the database.
     * Creates a new customer or updates an existing one.
     *
     * @return int|WP_Error Customer ID on success, WP_Error on failure.
     */
    public function save() {
        $data = array(
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company_id' => $this->company_id,
        );

        if ( $this->customer_id ) {
            // Update existing customer
            $result = OO_DB::update_customer( $this->customer_id, $data );
            if ( is_wp_error( $result ) ) {
                oo_log('Error updating customer (ID: ' . $this->customer_id . '): ' . $result->get_error_message(), __METHOD__);
                return $result;
            }
            oo_log('Customer updated successfully (ID: ' . $this->customer_id . ')', __METHOD__);
            return $this->customer_id;
        } else {
            // Create new customer
            if ( empty( $this->name ) ) {
                return new WP_Error('missing_customer_name', 'Customer name is required to create a new customer.');
            }
            $new_customer_id = OO_DB::add_customer( $data );
            if ( is_wp_error( $new_customer_id ) ) {
                oo_log('Error adding new customer: ' . $new_customer_id->get_error_message(), __METHOD__);
                return $new_customer_id;
            }
            $this->customer_id = $new_customer_id;
            oo_log('New customer added successfully (ID: ' . $this->customer_id . ')', __METHOD__);
            return $this->customer_id;
        }
    }

    /**
     * Find or create a customer by name.
     * First searches for existing customers with the exact name.
     * If not found, creates a new customer with just the name.
     *
     * @param string $name Customer name to search for or create.
     * @return OO_Customer|WP_Error Customer object on success, WP_Error on failure.
     */
    public static function find_or_create_by_name( $name ) {
        if ( empty( $name ) ) {
            return new WP_Error( 'empty_customer_name', 'Customer name cannot be empty.' );
        }

        $name = sanitize_text_field( $name );

        // First, try to find existing customer with exact name match
        $existing_customers = OO_DB::get_customers( array(
            'search' => $name,
            'number' => 5,
            'orderby' => 'name',
            'order' => 'ASC'
        ) );

        // Check for exact name match
        foreach ( $existing_customers as $customer ) {
            if ( strcasecmp( $customer->name, $name ) === 0 ) {
                oo_log('Found existing customer by name: ' . $name . ' (ID: ' . $customer->customer_id . ')', __METHOD__);
                return new self( $customer );
            }
        }

        // No exact match found, create new customer
        $new_customer = new self();
        $new_customer->name = $name;
        
        $result = $new_customer->save();
        if ( is_wp_error( $result ) ) {
            oo_log('Error creating customer with name: ' . $name . ' - ' . $result->get_error_message(), __METHOD__);
            return $result;
        }

        oo_log('Created new customer with name: ' . $name . ' (ID: ' . $result . ')', __METHOD__);
        return $new_customer;
    }

    /**
     * Get customer by ID.
     *
     * @param int $customer_id Customer ID.
     * @return OO_Customer|null Customer object if found, null otherwise.
     */
    public static function get_by_id( $customer_id ) {
        $customer = new self( $customer_id );
        return $customer->customer_id ? $customer : null;
    }

    /**
     * Display customer management page
     */
    public static function display_customer_management_page() {
        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'operations-organizer' ) );
        }

        // Handle form submissions
        if ( isset( $_POST['oo_action'] ) ) {
            self::handle_customer_form_submission();
        }

        // Prepare data for the page
        self::prepare_customer_list_data();

        // Include the view
        include_once OO_PLUGIN_DIR . 'admin/views/customer-management-page.php';
    }

    /**
     * Handle customer form submissions
     */
    private static function handle_customer_form_submission() {
        $action = sanitize_text_field( $_POST['oo_action'] );
        
        switch ( $action ) {
            case 'add_customer':
                if ( check_admin_referer( 'oo_add_customer_nonce', 'oo_add_customer_nonce' ) ) {
                    self::handle_add_customer();
                }
                break;
            
            case 'edit_customer':
                if ( check_admin_referer( 'oo_edit_customer_nonce', 'oo_edit_customer_nonce' ) ) {
                    self::handle_edit_customer();
                }
                break;
        }
    }

    /**
     * Handle add customer form submission
     */
    private static function handle_add_customer() {
        $name = sanitize_text_field( $_POST['name'] );
        $company_id = ! empty( $_POST['company_id'] ) ? intval( $_POST['company_id'] ) : null;
        
        // Process phone numbers
        $phone_json = '';
        if ( ! empty( $_POST['phone_numbers_json'] ) ) {
            $phone_data = json_decode( stripslashes( $_POST['phone_numbers_json'] ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $validated_phone_data = oo_validate_phone_data( $phone_data );
                if ( ! is_wp_error( $validated_phone_data ) ) {
                    $phone_json = wp_json_encode( $validated_phone_data );
                }
            }
        }

        // Process email addresses
        $email_json = '';
        if ( ! empty( $_POST['email_addresses_json'] ) ) {
            $email_data = json_decode( stripslashes( $_POST['email_addresses_json'] ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $validated_email_data = oo_validate_email_data( $email_data );
                if ( ! is_wp_error( $validated_email_data ) ) {
                    $email_json = wp_json_encode( $validated_email_data );
                }
            }
        }

        if ( empty( $name ) ) {
            $GLOBALS['oo_customer_error'] = __( 'Customer name is required.', 'operations-organizer' );
            return;
        }

        $customer_data = array(
            'name' => $name,
            'phone_numbers' => $phone_json,
            'email_addresses' => $email_json,
            'company_id' => $company_id,
        );

        $result = OO_DB::add_customer( $customer_data );
        
        if ( is_wp_error( $result ) ) {
            $GLOBALS['oo_customer_error'] = $result->get_error_message();
        } else {
            $GLOBALS['oo_customer_success'] = __( 'Customer added successfully.', 'operations-organizer' );
        }
    }

    /**
     * Handle edit customer form submission
     */
    private static function handle_edit_customer() {
        $customer_id = intval( $_POST['customer_id'] );
        $name = sanitize_text_field( $_POST['name'] );
        $company_id = ! empty( $_POST['company_id'] ) ? intval( $_POST['company_id'] ) : null;
        
        // Process phone numbers
        $phone_json = '';
        if ( ! empty( $_POST['phone_numbers_json'] ) ) {
            $phone_data = json_decode( stripslashes( $_POST['phone_numbers_json'] ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $validated_phone_data = oo_validate_phone_data( $phone_data );
                if ( ! is_wp_error( $validated_phone_data ) ) {
                    $phone_json = wp_json_encode( $validated_phone_data );
                }
            }
        }

        // Process email addresses
        $email_json = '';
        if ( ! empty( $_POST['email_addresses_json'] ) ) {
            $email_data = json_decode( stripslashes( $_POST['email_addresses_json'] ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $validated_email_data = oo_validate_email_data( $email_data );
                if ( ! is_wp_error( $validated_email_data ) ) {
                    $email_json = wp_json_encode( $validated_email_data );
                }
            }
        }

        if ( empty( $name ) ) {
            $GLOBALS['oo_customer_error'] = __( 'Customer name is required.', 'operations-organizer' );
            return;
        }

        $customer_data = array(
            'name' => $name,
            'phone_numbers' => $phone_json,
            'email_addresses' => $email_json,
            'company_id' => $company_id,
        );

        $result = OO_DB::update_customer( $customer_id, $customer_data );
        
        if ( is_wp_error( $result ) ) {
            $GLOBALS['oo_customer_error'] = $result->get_error_message();
        } else {
            $GLOBALS['oo_customer_success'] = __( 'Customer updated successfully.', 'operations-organizer' );
        }
    }

    /**
     * Prepare customer list data for display
     */
    private static function prepare_customer_list_data() {
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $search_term = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $company_filter = isset( $_GET['company_filter'] ) ? intval( $_GET['company_filter'] ) : '';
        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'name';
        $order = isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'ASC';

        $args = array(
            'number' => $per_page,
            'offset' => ( $current_page - 1 ) * $per_page,
            'search' => $search_term,
            'orderby' => $orderby,
            'order' => $order,
        );

        // Add company filter if specified
        if ( $company_filter ) {
            $args['company_id'] = $company_filter;
        }

        $customers = OO_DB::get_customers( $args );
        $total_customers = OO_DB::get_customers_count( $args );

        // Set global variables for the view
        $GLOBALS['customers'] = $customers;
        $GLOBALS['total_customers'] = $total_customers;
        $GLOBALS['current_page'] = $current_page;
        $GLOBALS['per_page'] = $per_page;
        $GLOBALS['search_term'] = $search_term;
        $GLOBALS['orderby'] = $orderby;
        $GLOBALS['order'] = $order;
    }

    /**
     * AJAX handler for getting customer details
     */
    public static function ajax_get_customer_details() {
        check_ajax_referer( 'oo_get_customer_details_nonce', 'nonce' );
        
        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
            return;
        }

        $customer_id = isset( $_POST['customer_id'] ) ? intval( $_POST['customer_id'] ) : 0;
        
        if ( $customer_id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid customer ID.' ) );
            return;
        }

        // Get customer with company information
        $customers = OO_DB::get_customers( array( 'customer_id' => $customer_id, 'number' => 1 ) );
        
        if ( empty( $customers ) ) {
            wp_send_json_error( array( 'message' => 'Customer not found.' ) );
            return;
        }

        $customer = $customers[0];
        
        wp_send_json_success( array(
            'customer_id' => $customer->customer_id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'company_id' => $customer->company_id,
            'company_name' => $customer->company_name,
        ) );
    }
} 