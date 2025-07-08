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
} 