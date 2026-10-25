<?php
// Post content first image show in blog as featured image fallback
add_filter(
    'render_block_core/post-featured-image',
    'block_theme_dynamic_fallback_image',
    10,
    2
);

function block_theme_dynamic_fallback_image( $block_content, $block ) {

    global $post;

    if ( ! $post ) {
        return $block_content;
    }

    // If featured image exists, keep default output
    if ( has_post_thumbnail( $post->ID ) ) {
        return $block_content;
    }

    // Prevent infinite recursion
    remove_filter( 'render_block_core/post-featured-image', 'block_theme_dynamic_fallback_image', 10 );

    // Render post content blocks
    $blocks = parse_blocks( $post->post_content );
    $rendered_content = '';

    foreach ( $blocks as $block_item ) {
        $rendered_content .= render_block( $block_item );
    }

    // Re-add filter
    add_filter( 'render_block_core/post-featured-image', 'block_theme_dynamic_fallback_image', 10, 2 );

    // Find first image
    preg_match(
        '/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i',
        $rendered_content,
        $image
    );

    $permalink = get_permalink( $post->ID );
    $title     = get_the_title( $post->ID );

    // If image found in content
    if ( ! empty( $image['src'] ) ) {
        return sprintf(
            '<figure class="wp-block-post-featured-image">
                <a href="%s">
                    <img src="%s" alt="%s" />
                </a>
            </figure>',
            esc_url( $permalink ),
            esc_url( $image['src'] ),
            esc_attr( $title )
        );
    }

    // Default fallback image
    $default_blog_logo = get_template_directory_uri() . '/assets/images/blog_logo.jpg';

    return sprintf(
        '<figure class="wp-block-post-featured-image default-blog-logo">
            <a href="%s">
                <img src="%s" alt="Default Blog Image" />
            </a>
        </figure>',
        esc_url( $permalink ),
        esc_url( $default_blog_logo )
    );
}
