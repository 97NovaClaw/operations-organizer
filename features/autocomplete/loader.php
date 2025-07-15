<?php
/**
 * Loader for the Autocomplete Feature
 *
 * @package Operations_Organizer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue autocomplete assets (CSS and JS)
 * 
 * This function ensures the autocomplete CSS and JS files are loaded.
 * It should be called whenever the autocomplete component is used on a page.
 */
function oo_enqueue_autocomplete_assets() {
    // Only enqueue if not already enqueued
    if ( wp_script_is( 'oo-autocomplete', 'enqueued' ) ) {
        return;
    }

    // Enqueue CSS
    wp_enqueue_style(
        'oo-autocomplete',
        OO_PLUGIN_URL . 'features/autocomplete/css/autocomplete.css',
        array(),
        OO_PLUGIN_VERSION
    );

    // Enqueue JavaScript (depends on jQuery, which should already be loaded)
    wp_enqueue_script(
        'oo-autocomplete',
        OO_PLUGIN_URL . 'features/autocomplete/js/autocomplete.js',
        array( 'jquery' ),
        OO_PLUGIN_VERSION,
        true
    );
}

/**
 * Generate autocomplete HTML and configuration
 * 
 * This is the main helper function for using the autocomplete component.
 * It returns the necessary HTML and enqueues the required assets.
 * 
 * @param array $args Configuration arguments
 * @return string HTML output
 */
function oo_get_autocomplete_html( $args = array() ) {
    // Default configuration
    $defaults = array(
        'input_selector'        => '',      // Required: jQuery selector for existing input, or ID for new input
        'input_id'              => '',      // Alternative to input_selector - creates new input with this ID
        'input_name'            => '',      // Name attribute for new input
        'input_value'           => '',      // Initial value for input
        'input_class'           => 'regular-text', // CSS class for input
        'ajax_action'           => '',      // Required: WordPress AJAX action name
        'render_item_callback'  => '',      // Required: JavaScript function name to render items
        'on_select_callback'    => '',      // Required: JavaScript function name for item selection
        'on_add_new_callback'   => '',      // Optional: JavaScript function name for "Add New" action
        'nonce'                 => '',      // Required: Security nonce
        'add_new_text'          => 'Add New Item', // Text for "Add New" button
        'placeholder'           => 'Search...', // Placeholder text
        'min_chars'             => 2,       // Minimum characters before search
        'delay'                 => 300,     // Delay in ms before search
        'context'               => array(), // Additional context data for AJAX
        'container_class'       => '',      // Additional CSS class for container
        'hidden_field_id'       => '',      // ID for hidden field to store selected value
        'hidden_field_name'     => '',      // Name for hidden field
        'query_param'           => 'query', // Parameter name for search query (query, search, term, etc.)
        'response_data_path'    => '',      // Path to data in response (empty for root, 'customers', 'results', etc.)
        'data_mapping'          => array(   // Map response fields to expected fields
            'id'    => 'id',
            'name'  => 'name',
            'title' => 'title',
            'text'  => 'text'
        ),
    );

    $config = wp_parse_args( $args, $defaults );

    // Validate required arguments
    if ( empty( $config['ajax_action'] ) ) {
        return '<p style="color: red;">Error: ajax_action is required for autocomplete</p>';
    }

    if ( empty( $config['input_selector'] ) && empty( $config['input_id'] ) ) {
        return '<p style="color: red;">Error: Either input_selector or input_id is required</p>';
    }

    // Enqueue the autocomplete assets
    oo_enqueue_autocomplete_assets();

    // Generate unique ID for this instance
    $instance_id = 'oo_autocomplete_' . uniqid();

    // Determine input selector
    $input_selector = '';
    $input_html = '';

    if ( ! empty( $config['input_selector'] ) ) {
        // Using existing input
        $input_selector = $config['input_selector'];
    } else {
        // Creating new input
        $input_id = sanitize_key( $config['input_id'] );
        $input_selector = '#' . $input_id;
        
        $input_attributes = array(
            'type'        => 'text',
            'id'          => $input_id,
            'name'        => ! empty( $config['input_name'] ) ? $config['input_name'] : $input_id,
            'value'       => esc_attr( $config['input_value'] ),
            'class'       => esc_attr( $config['input_class'] ),
            'placeholder' => esc_attr( $config['placeholder'] ),
        );

        $input_html = '<input ' . oo_build_html_attributes( $input_attributes ) . ' />';
    }

    // Prepare JavaScript configuration
    $js_config = array(
        'input_selector'        => $input_selector,
        'ajax_action'           => $config['ajax_action'],
        'render_item_callback'  => $config['render_item_callback'],
        'on_select_callback'    => $config['on_select_callback'],
        'on_add_new_callback'   => $config['on_add_new_callback'],
        'nonce'                 => $config['nonce'],
        'add_new_text'          => $config['add_new_text'],
        'placeholder'           => $config['placeholder'],
        'min_chars'             => intval( $config['min_chars'] ),
        'delay'                 => intval( $config['delay'] ),
        'context'               => $config['context'],
    );

    // Remove empty values to keep config clean
    $js_config = array_filter( $js_config, function( $value ) {
        return $value !== '' && $value !== array();
    });

    // Generate container HTML
    $container_class = 'oo-autocomplete-wrapper';
    if ( ! empty( $config['container_class'] ) ) {
        $container_class .= ' ' . esc_attr( $config['container_class'] );
    }

    $html = '<div class="' . $container_class . '">';
    
    // Add input if we're creating it
    if ( ! empty( $input_html ) ) {
        $html .= $input_html;
    }

    // Add hidden field if requested
    if ( ! empty( $config['hidden_field_id'] ) ) {
        $hidden_name = ! empty( $config['hidden_field_name'] ) ? $config['hidden_field_name'] : $config['hidden_field_id'];
        $html .= '<input type="hidden" id="' . esc_attr( $config['hidden_field_id'] ) . '" name="' . esc_attr( $hidden_name ) . '" />';
    }

    $html .= '</div>';

    // Add JavaScript to initialize the widget
    $html .= '<script type="text/javascript">
    jQuery(document).ready(function($) {
        if (typeof OO_Autocomplete !== "undefined") {
            var config_' . $instance_id . ' = ' . wp_json_encode( $js_config ) . ';
            var widget_' . $instance_id . ' = OO_Autocomplete.create(config_' . $instance_id . ');
        } else {
            console.error("OO_Autocomplete not found. Make sure autocomplete.js is loaded.");
        }
    });
    </script>';

    return $html;
}

/**
 * Helper function to build HTML attributes from an array
 * 
 * @param array $attributes Key-value pairs of attributes
 * @return string HTML attributes string
 */
function oo_build_html_attributes( $attributes ) {
    $html_attrs = array();
    
    foreach ( $attributes as $key => $value ) {
        if ( $value !== '' && $value !== null ) {
            $html_attrs[] = esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
        }
    }
    
    return implode( ' ', $html_attrs );
}

/**
 * Get preconfigured autocomplete HTML for customers
 * 
 * @param array $args Configuration arguments
 * @return string HTML output
 */
function oo_get_customer_autocomplete_html( $args = array() ) {
    $defaults = array(
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
        'hidden_field_name'     => 'customer_id',
        'query_param'           => 'query',
        'response_data_path'    => '',
        'data_mapping'          => array(
            'id'    => 'id',
            'name'  => 'name',
            'title' => 'display_name',
            'text'  => 'name'
        ),
    );
    
    $config = wp_parse_args( $args, $defaults );
    return oo_get_autocomplete_html( $config );
}

/**
 * Get preconfigured autocomplete HTML for companies
 * 
 * @param array $args Configuration arguments
 * @return string HTML output
 */
function oo_get_company_autocomplete_html( $args = array() ) {
    $defaults = array(
        'input_id'              => 'company_name',
        'input_name'            => 'company_name',
        'placeholder'           => 'Search for a company...',
        'ajax_action'           => 'oo_search_companies',
        'render_item_callback'  => 'renderCompanyItem',
        'on_select_callback'    => 'onCompanySelect',
        'on_add_new_callback'   => 'onAddNewCompany',
        'nonce'                 => wp_create_nonce('oo_search_companies_nonce'),
        'add_new_text'          => 'Create New Company',
        'hidden_field_id'       => 'company_id',
        'hidden_field_name'     => 'company_id',
        'query_param'           => 'query',
        'response_data_path'    => '',
        'data_mapping'          => array(
            'id'    => 'id',
            'name'  => 'name',
            'title' => 'name',
            'text'  => 'name'
        ),
    );
    
    $config = wp_parse_args( $args, $defaults );
    return oo_get_autocomplete_html( $config );
}

/**
 * Register default callback functions for common use cases
 * 
 * This function adds some standard JavaScript callback functions to the global registry.
 * It should be called on pages where the autocomplete is used.
 */
function oo_register_default_autocomplete_callbacks() {
    // Only add the script once per page
    static $callbacks_registered = false;
    if ( $callbacks_registered ) {
        return;
    }
    $callbacks_registered = true;

    ?>
    <script type="text/javascript">
    // Default callback functions for common autocomplete use cases
    window.OO_Autocomplete_Callbacks = window.OO_Autocomplete_Callbacks || {};

    /**
     * Default customer item renderer
     * Renders customer with icon, name, and address
     */
    window.OO_Autocomplete_Callbacks.renderCustomerItem = function(item) {
        var initials = '';
        if (item.first_name && item.last_name) {
            initials = item.first_name.charAt(0) + item.last_name.charAt(0);
        } else if (item.name) {
            var nameParts = item.name.split(' ');
            initials = nameParts[0].charAt(0) + (nameParts[1] ? nameParts[1].charAt(0) : '');
        }

        var address = '';
        if (item.address) {
            address = '<div class="item-secondary-text">' + item.address + '</div>';
        }

        return '<div class="item-icon">' + initials.toUpperCase() + '</div>' +
               '<div class="item-details-container">' +
               '<div class="item-primary-text">' + (item.name || (item.first_name + ' ' + item.last_name)) + '</div>' +
               address +
               '</div>';
    };

    /**
     * Default company item renderer
     * Renders company with building icon and name
     */
    window.OO_Autocomplete_Callbacks.renderCompanyItem = function(item) {
        var initials = item.name ? item.name.charAt(0).toUpperCase() : 'C';
        
        return '<div class="item-icon">' + initials + '</div>' +
               '<div class="item-details-container">' +
               '<div class="item-primary-text">' + (item.name || 'Company') + '</div>' +
               '</div>';
    };

    /**
     * Generic item renderer
     * Works with any item that has a 'name' property
     */
    window.OO_Autocomplete_Callbacks.renderGenericItem = function(item) {
        var displayName = item.name || item.title || item.text || 'Item';
        var initials = displayName.charAt(0).toUpperCase();
        
        return '<div class="item-icon">' + initials + '</div>' +
               '<div class="item-details-container">' +
               '<div class="item-primary-text">' + displayName + '</div>' +
               '</div>';
    };
    </script>
    <?php
} 