<?php
// Enqueue cards.js
add_action( 'init', function() {
    wp_register_script(
        'understrap-swiper-cards-block',
        get_template_directory_uri() . '/assets/js/swiper-card/swiper-cards.js',
        [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ],
        filemtime( get_template_directory() . '/assets/js/swiper-card/swiper-cards.js' )
    );

    register_block_type( 'understrap/swiper-card', array(
        'editor_script' => 'understrap-swiper-cards-block',
    ) );

    register_block_type( 'understrap/swiper-cards', array(
        'editor_script' => 'understrap-swiper-cards-block',
    ) );
});
