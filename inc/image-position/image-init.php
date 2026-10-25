<?php
// Enqueue image-position.js
add_action( 'init', function() {
    wp_register_script(
        'understrap-image-position-block',
        get_template_directory_uri() . '/assets/js/image-position/image-position.js',
        [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ],
        filemtime( get_template_directory() . '/assets/js/image-position/image-position.js' )
    );

    register_block_type( 'understrap/image-position', array(
        'editor_script' => 'understrap-image-position-block',
    ) );


});