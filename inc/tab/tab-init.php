<?php

// Enqueue tab-blocks.js
add_action( 'init', function() {
    wp_register_script(
        'understrap-tabs-block',
        get_template_directory_uri() . '/assets/js/tab/tab-blocks.js',
        [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ],
        filemtime( get_template_directory() . '/assets/js/tab/tab-blocks.js' )
    );

    register_block_type( 'understrap/tabs', array(
        'editor_script' => 'understrap-tabs-block',
    ) );

    register_block_type( 'understrap/tab-item', array(
        'editor_script' => 'understrap-tabs-block',
    ) );
});

// Enqueue frontend tab functionality
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_script(
        'understrap-tabs-frontend',
        get_template_directory_uri() . '/assets/js/tab/tab-frontend.js',
        [],
        filemtime( get_template_directory() . '/assets/js/tab/tab-frontend.js' ),
        true
    );
});