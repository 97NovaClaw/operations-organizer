<?php
// /includes/class-oo-company.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Company {

    public $company_id;
    public $name;
    public $address;
    public $city;
    public $province;
    public $postal_code;
    public $phone_numbers;
    public $created_at;
    public $updated_at;

    public function __construct( $company = null ) {
        if ( $company ) {
            $this->load( $company );
        }
    }

    public function load( $company ) {
        if ( is_object( $company ) ) {
            $this->company_id    = isset( $company->company_id ) ? intval( $company->company_id ) : null;
            $this->name          = isset( $company->name ) ? $company->name : null;
            $this->address       = isset( $company->address ) ? $company->address : null;
            $this->city          = isset( $company->city ) ? $company->city : null;
            $this->province      = isset( $company->province ) ? $company->province : null;
            $this->postal_code   = isset( $company->postal_code ) ? $company->postal_code : null;
            $this->phone_numbers = isset( $company->phone_numbers ) ? $company->phone_numbers : null;
            $this->created_at    = isset( $company->created_at ) ? $company->created_at : null;
            $this->updated_at    = isset( $company->updated_at ) ? $company->updated_at : null;
        } elseif ( is_numeric( $company ) ) {
            $data = OO_DB::get_company( intval( $company ) );
            if ( $data ) {
                $this->load( $data );
            }
        }
    }

    /**
     * AJAX handler for company search
     * 
     * Searches companies by name and returns results for autocomplete
     */
    public static function ajax_search_companies() {
        // Verify nonce
        if ( ! check_ajax_referer( 'oo_search_companies_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
            return;
        }

        // Check user permissions
        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
            return;
        }

        // Get search query
        $query = isset( $_POST['query'] ) ? sanitize_text_field( $_POST['query'] ) : '';
        
        if ( empty( $query ) || strlen( $query ) < 2 ) {
            wp_send_json_error( array( 'message' => 'Query too short.' ) );
            return;
        }

        // Search companies using the database method
        $companies = OO_DB::get_companies( array( 'search' => $query, 'number' => 10 ) );
        
        // Format results for autocomplete
        $formatted_companies = array();
        
        if ( ! empty( $companies ) ) {
            foreach ( $companies as $company ) {
                $formatted_companies[] = array(
                    'id'       => intval( $company->company_id ),
                    'name'     => $company->name,
                    'address'  => $company->address,
                    'city'     => $company->city,
                    'province' => $company->province,
                    'phone'    => $company->phone_numbers,
                );
            }
        }

        wp_send_json_success( $formatted_companies );
    }

    /**
     * Display company management page
     */
    public static function display_company_management_page() {
        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'operations-organizer' ) );
        }

        // Handle form submissions
        if ( isset( $_POST['oo_action'] ) ) {
            self::handle_company_form_submission();
        }

        // Prepare data for the page
        self::prepare_company_list_data();

        // Include the view
        include_once OO_PLUGIN_DIR . 'admin/views/company-management-page.php';
    }

    /**
     * Handle company form submissions
     */
    private static function handle_company_form_submission() {
        $action = sanitize_text_field( $_POST['oo_action'] );
        
        switch ( $action ) {
            case 'add_company':
                if ( check_admin_referer( 'oo_add_company_nonce', 'oo_add_company_nonce' ) ) {
                    self::handle_add_company();
                }
                break;
            
            case 'edit_company':
                if ( check_admin_referer( 'oo_edit_company_nonce', 'oo_edit_company_nonce' ) ) {
                    self::handle_edit_company();
                }
                break;
        }
    }

    /**
     * Handle add company form submission
     */
    private static function handle_add_company() {
        $name = sanitize_text_field( $_POST['name'] );
        $address = sanitize_text_field( $_POST['address'] );
        $city = sanitize_text_field( $_POST['city'] );
        $province = sanitize_text_field( $_POST['province'] );
        $postal_code = sanitize_text_field( $_POST['postal_code'] );
        
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

        if ( empty( $name ) ) {
            $GLOBALS['oo_company_error'] = __( 'Company name is required.', 'operations-organizer' );
            return;
        }

        $company_data = array(
            'name' => $name,
            'address' => $address,
            'city' => $city,
            'province' => $province,
            'postal_code' => $postal_code,
            'phone_numbers' => $phone_json,
        );

        $result = OO_DB::add_company( $company_data );
        
        if ( is_wp_error( $result ) ) {
            $GLOBALS['oo_company_error'] = $result->get_error_message();
        } else {
            $GLOBALS['oo_company_success'] = __( 'Company added successfully.', 'operations-organizer' );
        }
    }

    /**
     * Handle edit company form submission
     */
    private static function handle_edit_company() {
        $company_id = intval( $_POST['company_id'] );
        $name = sanitize_text_field( $_POST['name'] );
        $address = sanitize_text_field( $_POST['address'] );
        $city = sanitize_text_field( $_POST['city'] );
        $province = sanitize_text_field( $_POST['province'] );
        $postal_code = sanitize_text_field( $_POST['postal_code'] );
        
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

        if ( empty( $name ) ) {
            $GLOBALS['oo_company_error'] = __( 'Company name is required.', 'operations-organizer' );
            return;
        }

        $company_data = array(
            'name' => $name,
            'address' => $address,
            'city' => $city,
            'province' => $province,
            'postal_code' => $postal_code,
            'phone_numbers' => $phone_json,
        );

        $result = OO_DB::update_company( $company_id, $company_data );
        
        if ( is_wp_error( $result ) ) {
            $GLOBALS['oo_company_error'] = $result->get_error_message();
        } else {
            $GLOBALS['oo_company_success'] = __( 'Company updated successfully.', 'operations-organizer' );
        }
    }

    /**
     * Prepare company list data for display
     */
    private static function prepare_company_list_data() {
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $search_term = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'name';
        $order = isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'ASC';

        $args = array(
            'number' => $per_page,
            'offset' => ( $current_page - 1 ) * $per_page,
            'search' => $search_term,
            'orderby' => $orderby,
            'order' => $order,
        );

        $companies = OO_DB::get_companies( $args );
        
        // Add customer count for each company
        foreach ( $companies as $company ) {
            $customer_count = OO_DB::get_customers_count( array( 'company_id' => $company->company_id ) );
            $company->customer_count = $customer_count;
        }
        
        $total_companies = OO_DB::get_companies_count( $args );

        // Set global variables for the view
        $GLOBALS['companies'] = $companies;
        $GLOBALS['total_companies'] = $total_companies;
        $GLOBALS['current_page'] = $current_page;
        $GLOBALS['per_page'] = $per_page;
        $GLOBALS['search_term'] = $search_term;
        $GLOBALS['orderby'] = $orderby;
        $GLOBALS['order'] = $order;
    }

    /**
     * AJAX handler for getting company details
     */
    public static function ajax_get_company_details() {
        check_ajax_referer( 'oo_get_company_details_nonce', 'nonce' );
        
        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
            return;
        }

        $company_id = isset( $_POST['company_id'] ) ? intval( $_POST['company_id'] ) : 0;
        
        if ( $company_id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid company ID.' ) );
            return;
        }

        $company = OO_DB::get_company( $company_id );
        
        if ( ! $company ) {
            wp_send_json_error( array( 'message' => 'Company not found.' ) );
            return;
        }
        
        wp_send_json_success( array(
            'company_id' => $company->company_id,
            'name' => $company->name,
            'address' => $company->address,
            'city' => $company->city,
            'province' => $company->province,
            'postal_code' => $company->postal_code,
            'phone_numbers' => $company->phone_numbers,
        ) );
    }
} 