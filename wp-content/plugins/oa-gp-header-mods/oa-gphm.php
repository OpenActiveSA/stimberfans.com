<?php
/**
 * Plugin Name: Open Agency: GeneratePress Header Mods
 * Description: Add selectable header layouts to GeneratePress via the Customizer.
 * Version: 1.3.1
 * Author: Open Agency
 * Text Domain: oa-gphm
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register Customizer setting
function oa_gphm_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'oa-gphm-layout-section', [
        'title'    => __( 'Header Layout Mods', 'oa-gphm' ),
        'priority' => 30,
    ] );

    $wp_customize->add_setting( 'oa_gphm_layout_choice', [
        'default'           => 'oa-gphm-layout-1',
        'sanitize_callback' => 'sanitize_text_field',
    ] );

    $wp_customize->add_control( 'oa_gphm_layout_choice', [
        'label'   => __( 'Select Header Layout', 'oa-gphm' ),
        'section' => 'oa-gphm-layout-section',
        'type'    => 'select',
        'choices' => [
            'oa-gphm-layout-1' => 'Layout 1',
            // 'oa-gphm-layout-2' => 'Layout 2',
            // 'oa-gphm-layout-3' => 'Layout 3',
            // 'oa-gphm-layout-4' => 'Layout 4',
            // 'oa-gphm-layout-5' => 'Layout 5',
        ],
    ] );
}
add_action( 'customize_register', 'oa_gphm_customize_register' );

// Enqueue selected layout stylesheet
function oa_gphm_enqueue_layout_stylesheet() {
    $layout = get_theme_mod( 'oa_gphm_layout_choice', 'oa-gphm-layout-1' );
    $handle = 'oa-gphm-' . esc_attr( $layout );

    // All layout files must use the oa-gphm-layout-x.css naming convention
    $file_path = plugin_dir_path( __FILE__ ) . 'css/' . $layout . '.css';
    $file_url  = plugin_dir_url( __FILE__ ) . 'css/' . $layout . '.css';

    if ( file_exists( $file_path ) ) {
        wp_enqueue_style( $handle, $file_url, [], filemtime( $file_path ) );
    }
}
add_action( 'wp_enqueue_scripts', 'oa_gphm_enqueue_layout_stylesheet' );