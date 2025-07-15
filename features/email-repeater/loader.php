<?php
/**
 * Loader for the Email Repeater Feature
 *
 * @package Operations_Organizer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue email repeater assets (CSS and JS)
 * 
 * This function ensures the email repeater CSS and JS files are loaded.
 * It should be called whenever the email repeater component is used on a page.
 */
function oo_enqueue_email_repeater_assets() {
    // Only enqueue if not already enqueued
    if ( wp_script_is( 'oo-email-repeater', 'enqueued' ) ) {
        return;
    }

    // Enqueue CSS
    wp_enqueue_style(
        'oo-email-repeater',
        OO_PLUGIN_URL . 'features/email-repeater/css/email-repeater.css',
        array(),
        OO_PLUGIN_VERSION
    );

    // Enqueue JavaScript (depends on jQuery)
    wp_enqueue_script(
        'oo-email-repeater',
        OO_PLUGIN_URL . 'features/email-repeater/js/email-repeater.js',
        array( 'jquery' ),
        OO_PLUGIN_VERSION,
        true
    );
}

/**
 * Generate email repeater HTML and configuration
 * 
 * This is the main helper function for using the email repeater component.
 * It returns the necessary HTML and enqueues the required assets.
 * 
 * @param array $args Configuration arguments
 * @return string HTML output
 */
function oo_get_email_repeater_html( $args = array() ) {
    // Enqueue the necessary assets
    oo_enqueue_email_repeater_assets();

    // Default configuration
    $defaults = array(
        'container_id'   => 'oo_email_container',
        'field_name'     => 'email_addresses',
        'add_button_text' => 'Add Email',
        'max_emails'     => 10,
        'initial_data'   => array(),
        'container_class' => '',
        'show_labels'    => true,
    );

    $config = wp_parse_args( $args, $defaults );

    // Generate unique container ID if needed
    if ( $config['container_id'] === $defaults['container_id'] ) {
        $config['container_id'] = 'oo_email_container_' . uniqid();
    }

    // Start output buffering
    ob_start();
    ?>
    <div id="<?php echo esc_attr( $config['container_id'] ); ?>" class="oo-email-repeater-wrapper <?php echo esc_attr( $config['container_class'] ); ?>">
        <div class="oo-email-repeater-container">
            <div class="oo-email-list"></div>
            <button type="button" class="oo-email-add-btn button button-secondary">
                <span class="dashicons dashicons-plus"></span>
                <?php echo esc_html( $config['add_button_text'] ); ?>
            </button>
        </div>
    </div>

    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof OOEmailRepeater !== 'undefined') {
                new OOEmailRepeater(<?php echo wp_json_encode( $config ); ?>);
            }
        });
    </script>
    <?php

    return ob_get_clean();
}

/**
 * Process email repeater form submission
 * 
 * Handles form data submitted from the email repeater widget
 * 
 * @param array $form_data Form data from $_POST
 * @param string $field_name Field name used in the repeater
 * @return array Processed email data
 */
function oo_process_email_repeater_submission( $form_data, $field_name = 'email_addresses' ) {
    $email_data = array();

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
        foreach ( $form_data[ $field_name ] as $index => $email_fields ) {
            if ( ! empty( $email_fields['email'] ) ) {
                $email_data[] = array(
                    'email' => sanitize_email( $email_fields['email'] ),
                    'note'  => ! empty( $email_fields['note'] ) ? sanitize_textarea_field( $email_fields['note'] ) : '',
                );
            }
        }
    }

    return $email_data;
}

/**
 * Process email repeater data from form submission
 * 
 * This function takes the raw POST data from the email repeater
 * and returns a clean array of email data.
 * 
 * @param array $raw_data Raw POST data from the form
 * @return array Processed email data
 */
function oo_process_email_repeater_data( $raw_data ) {
    if ( empty( $raw_data ) || ! is_array( $raw_data ) ) {
        return array();
    }

    $processed_emails = array();

    foreach ( $raw_data as $email_data ) {
        // Validate required fields
        if ( empty( $email_data['email'] ) ) {
            continue;
        }

        // Sanitize email
        $email = sanitize_email( $email_data['email'] );
        if ( ! is_email( $email ) ) {
            continue;
        }

        // Sanitize note
        $note = isset( $email_data['note'] ) ? sanitize_textarea_field( $email_data['note'] ) : '';

        $processed_emails[] = array(
            'email' => $email,
            'note'  => $note,
        );
    }

    return $processed_emails;
}

/**
 * Format email data for display
 * 
 * This function takes processed email data and formats it for display.
 * 
 * @param array $emails Array of email data
 * @return string Formatted email display
 */
function oo_format_email_display( $emails ) {
    if ( empty( $emails ) || ! is_array( $emails ) ) {
        return '';
    }

    $formatted_emails = array();

    foreach ( $emails as $email_data ) {
        $email = $email_data['email'];
        $note = ! empty( $email_data['note'] ) ? ' (' . $email_data['note'] . ')' : '';
        $formatted_emails[] = $email . $note;
    }

    return implode( ', ', $formatted_emails );
}

/**
 * Get email data from JSON string
 * 
 * This function safely decodes JSON email data from the database.
 * 
 * @param string $json_data JSON string from database
 * @return array Array of email data
 */
function oo_get_email_data_from_json( $json_data ) {
    if ( empty( $json_data ) ) {
        return array();
    }

    $decoded = json_decode( $json_data, true );
    
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return array();
    }

    return is_array( $decoded ) ? $decoded : array();
}

/**
 * Convert email data to JSON string
 * 
 * This function safely encodes email data to JSON for database storage.
 * 
 * @param array $email_data Array of email data
 * @return string JSON string
 */
function oo_convert_email_data_to_json( $email_data ) {
    if ( empty( $email_data ) || ! is_array( $email_data ) ) {
        return '';
    }

    return wp_json_encode( $email_data );
}

/**
 * Validate email data
 * 
 * @param array $email_data Email data to validate
 * @return array|WP_Error Validated email data or error
 */
function oo_validate_email_data( $email_data ) {
    if ( ! is_array( $email_data ) ) {
        return new WP_Error( 'invalid_email_data', 'Email data must be an array.' );
    }

    $validated_data = array();

    foreach ( $email_data as $index => $email ) {
        if ( ! is_array( $email ) ) {
            continue;
        }

        // Validate required email field
        if ( empty( $email['email'] ) ) {
            continue; // Skip empty emails
        }

        $sanitized_email = sanitize_email( $email['email'] );
        if ( ! is_email( $sanitized_email ) ) {
            continue; // Skip invalid emails
        }

        $validated_email = array(
            'email' => $sanitized_email,
            'note'  => ! empty( $email['note'] ) ? sanitize_textarea_field( $email['note'] ) : '',
        );

        $validated_data[] = $validated_email;
    }

    return $validated_data;
}

/**
 * Get formatted email display string
 * 
 * Formats email data for display purposes
 * 
 * @param array $email_data Structured email data
 * @param string $format Display format ('list', 'inline', 'first_only')
 * @return string Formatted email string
 */
function oo_format_email_display_advanced( $email_data, $format = 'list' ) {
    if ( empty( $email_data ) || ! is_array( $email_data ) ) {
        return '';
    }

    switch ( $format ) {
        case 'first_only':
            // Return only the first email
            if ( ! empty( $email_data[0]['email'] ) ) {
                return $email_data[0]['email'];
            }
            return '';

        case 'inline':
            // Return all emails as comma-separated inline string
            $email_strings = array();
            foreach ( $email_data as $email ) {
                if ( ! empty( $email['email'] ) ) {
                    $display = $email['email'];
                    if ( ! empty( $email['note'] ) ) {
                        $display .= ' (' . $email['note'] . ')';
                    }
                    $email_strings[] = $display;
                }
            }
            return implode( ', ', $email_strings );

        case 'list':
        default:
            // Return as HTML list
            $html = '<ul class="oo-email-list-display">';
            foreach ( $email_data as $email ) {
                if ( ! empty( $email['email'] ) ) {
                    $html .= '<li>';
                    $html .= '<a href="mailto:' . esc_attr( $email['email'] ) . '">' . esc_html( $email['email'] ) . '</a>';
                    if ( ! empty( $email['note'] ) ) {
                        $html .= ' <span class="oo-email-note">(' . esc_html( $email['note'] ) . ')</span>';
                    }
                    $html .= '</li>';
                }
            }
            $html .= '</ul>';
            return $html;
    }
} 