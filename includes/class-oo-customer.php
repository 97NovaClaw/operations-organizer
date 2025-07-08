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
} 