<?php
/**
 * Search Filter Block
 * URL-based search filtering (no AJAX)
 */

add_action( 'init', function() {

    // Register editor script
    $editor_js_path = get_template_directory() . '/assets/js/search-filter/search-filter.js';
    $editor_js_url  = get_template_directory_uri() . '/assets/js/search-filter/search-filter.js';

    if ( file_exists( $editor_js_path ) ) {
        wp_register_script(
            'ts-search-filter-editor-js',
            $editor_js_url,
            [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ],
            filemtime( $editor_js_path )
        );
    }

    // Register block type
    register_block_type( 'ts-theme/search-filter', [
        'editor_script'   => 'ts-search-filter-editor-js',
        'render_callback' => 'ts_render_search_filter_block',
        'attributes'      => [
            'placeholder'  => [ 'type' => 'string', 'default' => 'Search...' ],
            'buttonText'   => [ 'type' => 'string', 'default' => 'Search' ],
            'alignment'    => [ 'type' => 'string', 'default' => 'left' ],
            'postType'     => [ 'type' => 'string', 'default' => 'post' ],
        ],
    ] );
} );

/**
 * Server render callback - outputs Bootstrap-styled search form
 */
function ts_render_search_filter_block( $attributes ) {
    $placeholder = isset( $attributes['placeholder'] ) ? sanitize_text_field( $attributes['placeholder'] ) : 'Search...';
    $button_text = isset( $attributes['buttonText'] ) ? sanitize_text_field( $attributes['buttonText'] ) : 'Search';
    $alignment   = isset( $attributes['alignment'] ) ? sanitize_text_field( $attributes['alignment'] ) : 'left';
    $post_type   = isset( $attributes['postType'] ) ? sanitize_text_field( $attributes['postType'] ) : 'post';

    // Get current search term from URL
    $current_search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';

    // Alignment classes
    $align_class = '';
    if ( $alignment === 'center' ) {
        $align_class = ' justify-content-center';
    } elseif ( $alignment === 'right' ) {
        $align_class = ' justify-content-start';
    }

    // Preserve other query parameters
    $preserve_query = [];
    foreach ( $_GET as $k => $v ) {
        if ( in_array( $k, [ 'search', 'post_type', 'paged' ], true ) ) {
            continue;
        }
        if ( is_scalar( $v ) ) {
            $preserve_query[ $k ] = $v;
        }
    }

    // Build markup
    $block_class = 'wp-block-ts-theme-search-filter filter-blog-controls search-filter-block';
    $html = '<div class="' . esc_attr( $block_class ) . '">';
    
    $html .= '<form class="search-filter-form d-flex' . esc_attr( $align_class ) . '" method="get" role="search">';
    
    // Preserve other query parameters
    foreach ( $preserve_query as $k => $v ) {
        $html .= '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
    }
    
    // Include post_type as hidden input
    $html .= '<input type="hidden" name="post_type" value="' . esc_attr( $post_type ) . '">';
    
    // Search input with Bootstrap styling
    $html .= '<div class="search filter-item d-flex align-items-center overflow-hidden">';
    $html .= '<button type="submit" class="has-primary-background-color border-0">' . '<i class="fa fa-search has-white-color"></i>' . '</button>';
    $html .= '<input type="search" name="search" class="filter-blog-search form-control border-0" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $current_search ) . '" aria-label="' . esc_attr( $placeholder ) . '">';
    $html .= '</div>';
    
    $html .= '</form>';
    $html .= '</div>';

    return $html;
}

/**
 * Filter Query Loop blocks to respect URL-based search filtering
 */
add_filter( 'query_loop_block_query_vars', function( $query_vars, $block ) {
    // Only apply on frontend (not in editor)
    if ( is_admin() ) {
        return $query_vars;
    }

    // Check for search parameter
    $search_query = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
    $post_type    = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';

    if ( ! empty( $search_query ) ) {
        $query_vars['s'] = $search_query;
        
        if ( $post_type ) {
            $query_vars['post_type'] = $post_type;
        }
    }

    return $query_vars;
}, 10, 2 );

/**
 * pre_get_posts for main query search filtering
 */
add_action( 'pre_get_posts', function( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    // Grab GET vars if present
    $search_query = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
    $post_type    = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';

    if ( ! empty( $search_query ) ) {
        $query->set( 's', $search_query );
        
        if ( $post_type ) {
            $query->set( 'post_type', $post_type );
        }
    }
}, 20 );
