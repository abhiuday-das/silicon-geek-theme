<?php
// Enqueue accordion-blocks.js
add_action( 'init', function() {
    wp_register_script(
        'understrap-accordion-block',
        get_template_directory_uri() . '/assets/js/accordion/accordion-blocks.js',
        [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ],
        filemtime( get_template_directory() . '/assets/js/accordion/accordion-blocks.js' )
    );

    register_block_type( 'understrap/accordion', array(
        'editor_script' => 'understrap-accordion-block',
    ) );

    register_block_type( 'understrap/accordion-item', array(
        'editor_script' => 'understrap-accordion-block',
    ) );
});

// Enqueue frontend accordion functionality
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_script(
        'understrap-accordion-frontend',
        get_template_directory_uri() . '/assets/js/accordion/accordion-frontend.js',
        [],
        filemtime( get_template_directory() . '/assets/js/accordion/accordion-frontend.js' ),
        true
    );
});