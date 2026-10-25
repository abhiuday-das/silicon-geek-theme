<?php
// Enqueue cards.js
add_action( 'init', function() {
    wp_register_script(
        'understrap-cards-block',
        get_template_directory_uri() . '/assets/js/card/cards.js',
        [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ],
        filemtime( get_template_directory() . '/assets/js/card/cards.js' )
    );

    register_block_type( 'understrap/card', array(
        'editor_script' => 'understrap-cards-block',
    ) );

    register_block_type( 'understrap/cards', array(
        'editor_script' => 'understrap-cards-block',
    ) );
});
