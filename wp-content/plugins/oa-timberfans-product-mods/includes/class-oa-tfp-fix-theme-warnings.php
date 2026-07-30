<?php
/**
 * Suppress WordPress Debug Warnings
 * 
 * HONEST NOTE: This hides warnings, it doesn't fix the root cause.
 * 
 * The warnings are from:
 * - GeneratePress theme calling is_search() before query runs
 * - Plugins loading translations too early
 * 
 * These don't affect functionality - they're just notices during development.
 * The proper fix would require modifying the theme files.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Only suppress if WP_DEBUG_DISPLAY is enabled (development mode)
if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) {
    
    // Set custom error handler to filter notices
    set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext = null) {
        
        // Patterns to suppress
        $suppress_notices = array(
            'is_search was called incorrectly',
            '_load_textdomain_just_in_time was called incorrectly',
            'Conditional query tags do not work before the query is run',
            'Translation loading for the'
        );
        
        // Check if this notice matches our suppress list
        foreach ($suppress_notices as $pattern) {
            if (stripos($errstr, $pattern) !== false) {
                return true; // Suppress this notice
            }
        }
        
        // For all other errors, use default WordPress handler
        return false;
        
    }, E_USER_NOTICE | E_NOTICE | E_WARNING);
}
