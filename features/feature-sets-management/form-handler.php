<?php
// /features/feature-sets-management/form-handler.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Feature_Sets_Management_Form_Handler {
    
    public static function init() {
        // Most processing is done via AJAX
        // This file is prepared for future form processing needs
    }
    
    /**
     * Validate feature set data
     */
    public static function validate_feature_set_data($data) {
        $errors = array();
        
        if (empty($data['name'])) {
            $errors[] = __('Feature set name is required.', 'operations-organizer');
        }
        
        if (strlen($data['name']) > 100) {
            $errors[] = __('Feature set name must be 100 characters or less.', 'operations-organizer');
        }
        
        if (!empty($data['description']) && strlen($data['description']) > 1000) {
            $errors[] = __('Feature set description must be 1000 characters or less.', 'operations-organizer');
        }
        
        return $errors;
    }
}

// Initialize the form handler
OO_Feature_Sets_Management_Form_Handler::init(); 