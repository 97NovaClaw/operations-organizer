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
} 