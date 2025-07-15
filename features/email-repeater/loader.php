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