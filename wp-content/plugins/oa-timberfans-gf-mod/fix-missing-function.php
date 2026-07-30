<?php
/**
 * Plugin Name: Fix Missing Function
 * Description: Temporary fix for missing oa_debug_class_loading function
 * Version: 1.0.0
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Add the missing function
 */
function oa_debug_class_loading() {
    // Empty function to prevent fatal error
    return;
}

// Hook it to init if it's being called
add_action( 'init', 'oa_debug_class_loading' ); 