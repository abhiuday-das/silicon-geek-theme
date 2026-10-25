<?php
/**
 * Category Filter Block
 * URL-based filtering (no AJAX)
 */

// Enqueue frontend CSS and JS for custom select
add_action( 'wp_enqueue_scripts', function() {
    $js_path  = get_stylesheet_directory() . '/assets/js/category-filter/category-filter-frontend.js';
    $js_url   = get_stylesheet_directory_uri() . '/assets/js/category-filter/category-filter-frontend.js';
       
    if ( file_exists( $js_path ) ) {
        wp_enqueue_script(
            'ts-cat-filter-frontend-js',
            $js_url,
            [],
            filemtime( $js_path ),
            true
        );
    }
} );

add_action( 'init', function() {

    // Register editor script
    $editor_js_path = get_stylesheet_directory() . '/assets/js/category-filter/category-filter.js';
    $editor_js_url  = get_stylesheet_directory_uri() . '/assets/js/category-filter/category-filter.js';

    if ( file_exists( $editor_js_path ) ) {
        wp_register_script(
            'ts-cat-filter-editor-js',
            $editor_js_url,
            [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-api-fetch' ],
            filemtime( $editor_js_path )
        );
    }


    // Register block type
    register_block_type( 'ts-theme/category-filter', [
        'editor_script'   => 'ts-cat-filter-editor-js',
        'render_callback' => 'ts_render_category_filter_block',
        'attributes'      => [
            'postType'     => [ 'type' => 'string', 'default' => '' ],
            'taxonomy'     => [ 'type' => 'string', 'default' => '' ],
            'display'      => [ 'type' => 'string', 'default' => 'buttons' ],
            'showAllLabel' => [ 'type' => 'string', 'default' => 'Categories' ],
            'alignment'    => [ 'type' => 'string', 'default' => 'left' ],
        ],
    ] );
} );

/**
 * Server render callback - outputs Bootstrap-styled filter links/selects
 * URLs include post_type & taxonomy to allow pre_get_posts to filter
 */
function ts_render_category_filter_block( $attributes ) {
    $post_type      = isset( $attributes['postType'] ) ? sanitize_text_field( $attributes['postType'] ) : '';
    $taxonomy       = isset( $attributes['taxonomy'] ) ? sanitize_text_field( $attributes['taxonomy'] ) : '';
    $display        = isset( $attributes['display'] ) ? sanitize_text_field( $attributes['display'] ) : 'buttons';
    $show_all_label = isset( $attributes['showAllLabel'] ) ? sanitize_text_field( $attributes['showAllLabel'] ) : 'Categories';
    $alignment      = isset( $attributes['alignment'] ) ? sanitize_text_field( $attributes['alignment'] ) : 'left';

    if ( empty( $post_type ) || empty( $taxonomy ) ) {
        return '<div class="wp-block-ts-theme-category-filter"><div class="alert alert-warning">Block requires a Post Type and Taxonomy.</div></div>';
    }

    // Get terms for selected taxonomy
    $terms = get_terms( [
        'taxonomy'   => $taxonomy,
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ] );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return '<div class="wp-block-ts-theme-category-filter"><div class="alert alert-info">No terms found for this taxonomy.</div></div>';
    }

    // Get active term from URL (?cat=term-slug)
    $raw_cat     = isset( $_GET['cat'] ) ? sanitize_text_field( wp_unslash( $_GET['cat'] ) ) : '';
    $active_slug = $raw_cat;

    // Alignment classes
    $align_class = '';
    if ( $alignment === 'center' ) {
        $align_class = ' justify-content-center';
    } elseif ( $alignment === 'right' ) {
        $align_class = ' justify-content-end';
    }

    // Build markup
    $block_class = 'wp-block-ts-theme-category-filter category-filter-block';
    $html = '<div class="' . esc_attr( $block_class ) . '" data-display="' . esc_attr( $display ) . '">';

    // helper to preserve other query args (except cat, post_type, taxonomy, paged)
    $preserve_query = [];
    foreach ( $_GET as $k => $v ) {
        if ( in_array( $k, [ 'cat', 'post_type', 'taxonomy', 'paged' ], true ) ) {
            continue;
        }
        // only scalar values here
        if ( is_scalar( $v ) ) {
            $preserve_query[ $k ] = $v;
        }
    }

    if ( $display === 'select' ) {
        // Dropdown/Select mode using Bootstrap form-select
        $html .= '<form class="category-filter-form d-flex' . esc_attr( $align_class ) . '" method="get"> 
        <div class="input-group"><div class="input-group-prepend select-height">
            <span class="input-group-text"><i class="icon-bars1" ></i></span>
          </div>';
        
        // Preserve other query parameters
        foreach ( $preserve_query as $k => $v ) {
            $html .= '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
        }
        
        // Include post_type & taxonomy as hidden inputs
        $html .= '<input type="hidden" name="post_type" value="' . esc_attr( $post_type ) . '">';
        $html .= '<input type="hidden" name="taxonomy" value="' . esc_attr( $taxonomy ) . '">';
        
        // Hidden input for category value
        $html .= '<input type="hidden" name="cat" value="' . esc_attr( $active_slug ) . '">';
        
        // Custom select wrapper
        $html .= '<div class="custom-select-wrapper">';
        
        // Get current selected label
        $current_label = $show_all_label;
        if ( $active_slug !== '' ) {
            foreach ( $terms as $term ) {
                if ( $term->slug === $active_slug ) {
                    $current_label = $term->name;
                    break;
                }
            }
        }
        

        // Custom select button
        $html .= '<button type="button" class="custom-select-button">';
        $html .= esc_html( $current_label );
        // $html .= '<span class="custom-select-arrow"></span>';
        $html .= '</button>';
        
        // Custom select dropdown
        $html .= '<div class="custom-select-dropdown">';
        
        // "All" option
        $all_selected = ( $active_slug === '' ) ? ' selected' : '';
        $html .= '<div class="custom-select-option' . esc_attr( $all_selected ) . '" data-value="">';
        $html .= esc_html( $show_all_label );
        $html .= '</div>';
        
        // Term options
        foreach ( $terms as $term ) {
            $selected = ( $term->slug === $active_slug ) ? ' selected' : '';
            $html .= '<div class="custom-select-option' . esc_attr( $selected ) . '" data-value="' . esc_attr( $term->slug ) . '">';
            $html .= esc_html( $term->name );
            $html .= '</div>';
        }
        
        $html .= '</div>'; // .custom-select-dropdown
        $html .= '</div>'; // .custom-select-wrapper
        $html .= '</form>';
    } else {
        // Button mode using Bootstrap button group
        $html .= '<div class="category-filter-buttons d-flex flex-wrap gap-2' . esc_attr( $align_class ) . '" role="group" aria-label="Category filter">';
        
        // "All" button
        $all_args = $preserve_query;
        $all_args['post_type'] = $post_type;
        $all_args['taxonomy']  = $taxonomy;
        $all_url = add_query_arg( $all_args );
        $all_class = ( $active_slug === '' ) ? ' active' : '';
        $html .= '<a href="' . esc_url( $all_url ) . '" class="btn btn-outline-primary category-filter-btn' . esc_attr( $all_class ) . '" data-cat="">' . esc_html( $show_all_label ) . '</a>';

        // Term buttons
        foreach ( $terms as $term ) {
            $args = $preserve_query;
            $args['post_type'] = $post_type;
            $args['taxonomy']  = $taxonomy;
            $args['cat']       = $term->slug;
            $url = add_query_arg( $args );
            $active_class = ( $term->slug === $active_slug ) ? ' active' : '';
            $html .= '<a href="' . esc_url( $url ) . '" class="btn btn-outline-primary category-filter-btn' . esc_attr( $active_class ) . '" data-cat="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</a>';
        }

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}

/**
 * Filter Query Loop blocks to respect URL-based category filtering
 * This hooks into the query loop's query to add taxonomy filtering based on URL parameters
 */
add_filter( 'query_loop_block_query_vars', function( $query_vars, $block ) {
    // Only apply on frontend (not in editor)
    if ( is_admin() ) {
        return $query_vars;
    }

    // Check for URL parameters
    $post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
    $taxonomy  = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';
    $cat_slug  = isset( $_GET['cat'] ) ? sanitize_text_field( wp_unslash( $_GET['cat'] ) ) : '';

    // If we have a category filter active
    if ( $post_type && $taxonomy && $cat_slug !== '' ) {
        // Verify the term exists
        $term = get_term_by( 'slug', $cat_slug, $taxonomy );
        
        if ( $term && ! is_wp_error( $term ) ) {
            // Set post type
            $query_vars['post_type'] = $post_type;
            
            // Add or merge tax_query
            if ( ! isset( $query_vars['tax_query'] ) ) {
                $query_vars['tax_query'] = [];
            }
            
            $query_vars['tax_query'][] = [
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => $cat_slug,
            ];
        }
    } elseif ( $post_type && $taxonomy && $cat_slug === '' ) {
        // "All" button clicked - just set post type, no taxonomy filter
        $query_vars['post_type'] = $post_type;
    }

    return $query_vars;
}, 10, 2 );

/**
 * pre_get_posts — convert ?post_type, ?taxonomy, ?cat=slug into query vars for main query
 *
 * It expects URL form:  ?post_type=...&taxonomy=...&cat=term-slug
 * - If cat is present (slug) it will inject a tax_query for that taxonomy and set post_type.
 * - If cat is empty but post_type present, it will set post_type only.
 */
add_action( 'pre_get_posts', function( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    // Grab GET vars if present
    $post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
    $taxonomy  = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';
    $cat_slug  = isset( $_GET['cat'] ) ? sanitize_text_field( wp_unslash( $_GET['cat'] ) ) : '';

    if ( $post_type ) {
        $query->set( 'post_type', $post_type );
    }

    if ( $taxonomy && $cat_slug !== '' ) {
        $term = get_term_by( 'slug', $cat_slug, $taxonomy );
        if ( $term ) {
            $query->set( 'tax_query', [
                [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $cat_slug,
                ],
            ] );
        } else {
            // If term not found, optionally return no posts:
            // $query->set( 'post__in', [0] );
        }
    }
});
