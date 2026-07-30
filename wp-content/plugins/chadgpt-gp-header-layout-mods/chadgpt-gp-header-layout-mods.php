<?php
/**
 * Plugin Name: ChadGPT - GeneratePress Header Layout Mods
 * Description: Add selectable header layouts to GeneratePress via the Customizer and expose dynamic logo width as a CSS variable.
 * Version: 1.3
 * Author: Open Agency
 */

// Register Customizer setting
function gphlm_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'gphlm_layout_section', [
        'title'    => __( 'Header Layout Mods', 'gphlm' ),
        'priority' => 30,
    ] );

    $wp_customize->add_setting( 'gphlm_layout_choice', [
        'default'           => 'layout-1',
        'sanitize_callback' => 'sanitize_text_field',
    ] );

    $wp_customize->add_control( 'gphlm_layout_choice', [
        'label'   => __( 'Select Header Layout', 'gphlm' ),
        'section' => 'gphlm_layout_section',
        'type'    => 'select',
        'choices' => [
            'layout-1' => 'Layout 1',
            'layout-2' => 'Layout 2',
            'layout-3' => 'Layout 3',
            'layout-4' => 'Layout 4',
            'layout-5' => 'Layout 5',
        ],
    ] );
}
add_action( 'customize_register', 'gphlm_customize_register' );

// Enqueue selected layout stylesheet
function gphlm_enqueue_layout_stylesheet() {
    $layout = get_theme_mod( 'gphlm_layout_choice', 'layout-1' );
    $handle = 'gphlm-' . esc_attr( $layout );

    $file_path = plugin_dir_path( __FILE__ ) . 'css/' . $layout . '.css';
    $file_url  = plugin_dir_url( __FILE__ ) . 'css/' . $layout . '.css';

    if ( file_exists( $file_path ) ) {
        wp_enqueue_style( $handle, $file_url, [], filemtime( $file_path ) );
    }
}
add_action( 'wp_enqueue_scripts', 'gphlm_enqueue_layout_stylesheet', 20 );