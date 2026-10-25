<?php
add_action('init', function () {
    // Button variants
    foreach (['primary','secondary'] as $v) {
        register_block_style('core/button', ['name' => $v, 'label' => ucfirst($v)]);
    }

    // Button sizes + shape
    register_block_style('core/button', ['name' => 'sm',   'label' => 'Small']);
    register_block_style('core/button', ['name' => 'lg',   'label' => 'Large']);
    register_block_style('core/button', ['name' => 'pill', 'label' => 'Pill']);
    
});
// Enqueue existing editor scripts
add_action('enqueue_block_editor_assets', function () {
    $js_file = get_template_directory() . '/assets/js/override-button/button-icon.js';
    if (file_exists($js_file)) {
        wp_enqueue_script(
            'understrap-parent-button-icon',
            get_template_directory_uri() . '/assets/js/override-button/button-icon.js',
            [ 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-components', 'wp-element', 'wp-compose', 'wp-block-editor', 'wp-hooks' ],
            filemtime($js_file),
            true
        );
    }
});