<?php
/**
 * Loader for the Phone Repeater Feature
 *
 * @package Operations_Organizer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue phone repeater assets (CSS and JS)
 * 
 * This function ensures the phone repeater CSS and JS files are loaded.
 * It should be called whenever the phone repeater component is used on a page.
 */
function oo_enqueue_phone_repeater_assets() {
    // Only enqueue if not already enqueued
    if ( wp_script_is( 'oo-phone-repeater', 'enqueued' ) ) {
        return;
    }

    // Enqueue CSS
    wp_enqueue_style(
        'oo-phone-repeater',
        OO_PLUGIN_URL . 'features/phone-repeater/css/phone-repeater.css',
        array(),
        OO_PLUGIN_VERSION
    );

    // Enqueue JavaScript (depends on jQuery)
    wp_enqueue_script(
        'oo-phone-repeater',
        OO_PLUGIN_URL . 'features/phone-repeater/js/phone-repeater.js',
        array( 'jquery' ),
        OO_PLUGIN_VERSION,
        true
    );
}

/**
 * Generate phone repeater HTML and configuration
 * 
 * This is the main helper function for using the phone repeater component.
 * It returns the necessary HTML and enqueues the required assets.
 * 
 * @param array $args Configuration arguments
 * @return string HTML output
 */
function oo_get_phone_repeater_html( $args = array() ) {
    // Default configuration
    $defaults = array(
        'container_id'      => '',          // Required: ID for the container element
        'field_name'        => 'phone_numbers', // Base name for form fields
        'initial_data'      => array(),     // Initial phone data array
        'add_button_text'   => 'Add Phone Number', // Text for add button
        'allow_primary'     => true,        // Whether to allow primary phone selection
        'show_labels'       => true,        // Whether to show field labels
        'max_phones'        => 10,          // Maximum number of phones allowed
        'container_class'   => '',          // Additional CSS class for container
        'existing_data'     => '',          // Existing phone data (JSON string or array)
    );

    $config = wp_parse_args( $args, $defaults );

    // Validate required arguments
    if ( empty( $config['container_id'] ) ) {
        return '<p style="color: red;">Error: container_id is required for phone repeater</p>';
    }

    // Enqueue the phone repeater assets
    oo_enqueue_phone_repeater_assets();

    // Process existing data
    $initial_data = array();
    if ( ! empty( $config['existing_data'] ) ) {
        if ( is_string( $config['existing_data'] ) ) {
            // Try to decode JSON string
            $decoded = json_decode( $config['existing_data'], true );
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
                $initial_data = $decoded;
            } else {
                // Legacy string format - try to parse
                $initial_data = oo_parse_legacy_phone_string( $config['existing_data'] );
            }
        } elseif ( is_array( $config['existing_data'] ) ) {
            $initial_data = $config['existing_data'];
        }
    }

    // Merge with any provided initial_data
    if ( ! empty( $config['initial_data'] ) && is_array( $config['initial_data'] ) ) {
        $initial_data = array_merge( $initial_data, $config['initial_data'] );
    }

    // Generate unique ID for this instance
    $instance_id = 'oo_phone_repeater_' . uniqid();
    $container_id = sanitize_key( $config['container_id'] );

    // Prepare JavaScript configuration
    $js_config = array(
        'container_selector' => '#' . $container_id,
        'field_name'         => $config['field_name'],
        'initial_data'       => $initial_data,
        'add_button_text'    => $config['add_button_text'],
        'allow_primary'      => (bool) $config['allow_primary'],
        'show_labels'        => (bool) $config['show_labels'],
        'max_phones'         => intval( $config['max_phones'] ),
        'container_class'    => $config['container_class'],
    );

    // Generate container HTML
    $container_class = 'oo-phone-repeater-wrapper';
    if ( ! empty( $config['container_class'] ) ) {
        $container_class .= ' ' . esc_attr( $config['container_class'] );
    }

    $html = '<div id="' . esc_attr( $container_id ) . '" class="' . $container_class . '"></div>';

    // Add JavaScript to initialize the widget
    $html .= '<script type="text/javascript">
    jQuery(document).ready(function($) {
        if (typeof OO_PhoneRepeater !== "undefined") {
            var config_' . $instance_id . ' = ' . wp_json_encode( $js_config ) . ';
            var widget_' . $instance_id . ' = OO_PhoneRepeater.create(config_' . $instance_id . ');
        } else {
            console.error("OO_PhoneRepeater not found. Make sure phone-repeater.js is loaded.");
        }
    });
    </script>';

    return $html;
}

/**
 * Parse legacy phone string format into structured data
 * 
 * Converts strings like "555-1234, 555-5678" into structured phone data
 * 
 * @param string $phone_string Legacy phone string
 * @return array Structured phone data
 */
function oo_parse_legacy_phone_string( $phone_string ) {
    if ( empty( $phone_string ) ) {
        return array();
    }

    $phones = array();
    $phone_parts = explode( ',', $phone_string );
    
    foreach ( $phone_parts as $index => $phone ) {
        $phone = trim( $phone );
        if ( ! empty( $phone ) ) {
            $phones[] = array(
                'type'       => $index === 0 ? 'Office' : 'Phone',
                'number'     => $phone,
                'extension'  => '',
                'is_primary' => $index === 0, // First phone is primary
            );
        }
    }

    return $phones;
}

/**
 * Convert structured phone data to JSON string
 * 
 * @param array $phone_data Structured phone data
 * @return string JSON string
 */
function oo_phone_data_to_json( $phone_data ) {
    if ( empty( $phone_data ) || ! is_array( $phone_data ) ) {
        return '';
    }

    return wp_json_encode( $phone_data );
}

/**
 * Convert structured phone data to legacy string format
 * 
 * For backward compatibility with existing string-based storage
 * 
 * @param array $phone_data Structured phone data
 * @return string Legacy phone string
 */
function oo_phone_data_to_legacy_string( $phone_data ) {
    if ( empty( $phone_data ) || ! is_array( $phone_data ) ) {
        return '';
    }

    $phone_strings = array();
    foreach ( $phone_data as $phone ) {
        if ( ! empty( $phone['number'] ) ) {
            $phone_string = $phone['number'];
            if ( ! empty( $phone['extension'] ) ) {
                $phone_string .= ' ext. ' . $phone['extension'];
            }
            $phone_strings[] = $phone_string;
        }
    }

    return implode( ', ', $phone_strings );
}

/**
 * Get formatted phone display string
 * 
 * Formats phone data for display purposes
 * 
 * @param array $phone_data Structured phone data
 * @param string $format Display format ('list', 'inline', 'primary_only')
 * @return string Formatted phone string
 */
function oo_format_phone_display( $phone_data, $format = 'list' ) {
    if ( empty( $phone_data ) || ! is_array( $phone_data ) ) {
        return '';
    }

    switch ( $format ) {
        case 'primary_only':
            // Return only the primary phone number
            foreach ( $phone_data as $phone ) {
                if ( ! empty( $phone['is_primary'] ) && ! empty( $phone['number'] ) ) {
                    $display = $phone['number'];
                    if ( ! empty( $phone['extension'] ) ) {
                        $display .= ' ext. ' . $phone['extension'];
                    }
                    return $display;
                }
            }
            // If no primary found, return the first phone
            if ( ! empty( $phone_data[0]['number'] ) ) {
                $display = $phone_data[0]['number'];
                if ( ! empty( $phone_data[0]['extension'] ) ) {
                    $display .= ' ext. ' . $phone_data[0]['extension'];
                }
                return $display;
            }
            return '';

        case 'inline':
            // Return all phones as comma-separated inline string
            $phone_strings = array();
            foreach ( $phone_data as $phone ) {
                if ( ! empty( $phone['number'] ) ) {
                    $display = $phone['number'];
                    if ( ! empty( $phone['extension'] ) ) {
                        $display .= ' ext. ' . $phone['extension'];
                    }
                    if ( ! empty( $phone['type'] ) ) {
                        $display .= ' (' . $phone['type'] . ')';
                    }
                    $phone_strings[] = $display;
                }
            }
            return implode( ', ', $phone_strings );

        case 'list':
        default:
            // Return as HTML list
            $html = '<ul class="oo-phone-list-display">';
            foreach ( $phone_data as $phone ) {
                if ( ! empty( $phone['number'] ) ) {
                    $html .= '<li>';
                    if ( ! empty( $phone['type'] ) ) {
                        $html .= '<strong>' . esc_html( $phone['type'] ) . ':</strong> ';
                    }
                    $html .= esc_html( $phone['number'] );
                    if ( ! empty( $phone['extension'] ) ) {
                        $html .= ' ext. ' . esc_html( $phone['extension'] );
                    }
                    if ( ! empty( $phone['is_primary'] ) ) {
                        $html .= ' <span class="oo-primary-badge">(Primary)</span>';
                    }
                    $html .= '</li>';
                }
            }
            $html .= '</ul>';
            return $html;
    }
}

/**
 * Process phone repeater form submission
 * 
 * Handles form data submitted from the phone repeater widget
 * 
 * @param array $form_data Form data from $_POST
 * @param string $field_name Field name used in the repeater
 * @return array Processed phone data
 */
function oo_process_phone_repeater_submission( $form_data, $field_name = 'phone_numbers' ) {
    $phone_data = array();

    // Check for JSON data first (preferred method)
    $json_field_name = $field_name . '_json';
    if ( ! empty( $form_data[ $json_field_name ] ) ) {
        $decoded = json_decode( $form_data[ $json_field_name ], true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
            return $decoded;
        }
    }

    // Fall back to individual field processing
    if ( ! empty( $form_data[ $field_name ] ) && is_array( $form_data[ $field_name ] ) ) {
        foreach ( $form_data[ $field_name ] as $index => $phone_fields ) {
            if ( ! empty( $phone_fields['number'] ) ) {
                $phone_data[] = array(
                    'type'       => ! empty( $phone_fields['type'] ) ? sanitize_text_field( $phone_fields['type'] ) : '',
                    'number'     => sanitize_text_field( $phone_fields['number'] ),
                    'extension'  => ! empty( $phone_fields['extension'] ) ? sanitize_text_field( $phone_fields['extension'] ) : '',
                    'is_primary' => ! empty( $phone_fields['is_primary'] ),
                );
            }
        }
    }

    return $phone_data;
}

/**
 * Validate phone data
 * 
 * @param array $phone_data Phone data to validate
 * @return array|WP_Error Validated phone data or error
 */
function oo_validate_phone_data( $phone_data ) {
    if ( ! is_array( $phone_data ) ) {
        return new WP_Error( 'invalid_phone_data', 'Phone data must be an array.' );
    }

    $validated_data = array();
    $has_primary = false;

    foreach ( $phone_data as $index => $phone ) {
        if ( ! is_array( $phone ) ) {
            continue;
        }

        // Validate required number field
        if ( empty( $phone['number'] ) ) {
            continue; // Skip empty phone numbers
        }

        $validated_phone = array(
            'type'       => ! empty( $phone['type'] ) ? sanitize_text_field( $phone['type'] ) : '',
            'number'     => sanitize_text_field( $phone['number'] ),
            'extension'  => ! empty( $phone['extension'] ) ? sanitize_text_field( $phone['extension'] ) : '',
            'is_primary' => ! empty( $phone['is_primary'] ),
        );

        // Ensure only one primary phone
        if ( $validated_phone['is_primary'] ) {
            if ( $has_primary ) {
                $validated_phone['is_primary'] = false; // Remove primary from subsequent phones
            } else {
                $has_primary = true;
            }
        }

        $validated_data[] = $validated_phone;
    }

    // If no primary phone was set, make the first one primary
    if ( ! $has_primary && ! empty( $validated_data ) ) {
        $validated_data[0]['is_primary'] = true;
    }

    return $validated_data;
}

