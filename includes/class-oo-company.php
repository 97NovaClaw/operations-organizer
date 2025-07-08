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
} 