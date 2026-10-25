<?php
/**
 * Filter Blog Block Registration with Search and Category Dropdown (Full)
 *
 * Place in your theme's functions or include file. Expects these JS files:
 *  - assets/js/blog/filter-blog.js           (editor)
 *  - assets/js/blog/filter-blog-frontend.js  (frontend AJAX)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Register block scripts and block type
 */
add_action( 'init', function() {
    // Editor Script
    wp_register_script(
        'understrap-filter-blog-block',
        get_template_directory_uri() . '/assets/js/blog/filter-blog.js',
        [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-api-fetch' ],
        filemtime( get_template_directory() . '/assets/js/blog/filter-blog.js' )
    );

    // Frontend Script (AJAX)
    wp_register_script(
        'understrap-filter-blog-frontend',
        get_template_directory_uri() . '/assets/js/blog/filter-blog-frontend.js',
        [ 'jquery' ],
        filemtime( get_template_directory() . '/assets/js/blog/filter-blog-frontend.js' ),
        true
    );

    // Localize AJAX
    wp_localize_script(
        'understrap-filter-blog-frontend',
        'filterBlogAjax',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'filter_blog_nonce' )
        )
    );

    // Register block type
    register_block_type( 'understrap/filter-blog', array(
        'editor_script'   => 'understrap-filter-blog-block',
        'script'          => 'understrap-filter-blog-frontend',
        'render_callback' => 'render_filter_blog_block',
        'attributes'      => array(
            'postsPerPage'          => array( 'type' => 'number', 'default' => 9 ),
            'displayFeaturedImage'  => array( 'type' => 'boolean', 'default' => true ),
            'displayDate'           => array( 'type' => 'boolean', 'default' => true ),
            'displayAuthor'         => array( 'type' => 'boolean', 'default' => true ),
            'displayCategory'       => array( 'type' => 'boolean', 'default' => true ),
            'displayExcerpt'        => array( 'type' => 'boolean', 'default' => true ),
            'excerptLength'         => array( 'type' => 'number', 'default' => 150 ),
            'showFilterTabs'        => array( 'type' => 'boolean', 'default' => true ),
            'selectedCategories'    => array( 'type' => 'array', 'default' => array() ),
            'enableSearch'          => array( 'type' => 'boolean', 'default' => true ),
            'enableCategoryDropdown'=> array( 'type' => 'boolean', 'default' => true ),
        ),
    ) );
} );

/**
 * Render Filter Blog Block (server-side)
 */
function render_filter_blog_block( $attributes ) {
    $attributes = wp_parse_args( $attributes, array(
        'postsPerPage'          => 9,
        'displayFeaturedImage'  => true,
        'displayDate'           => true,
        'displayAuthor'         => true,
        'displayCategory'       => true,
        'displayExcerpt'        => true,
        'excerptLength'         => 150,
        'showFilterTabs'        => true,
        'selectedCategories'    => array(),
        'enableSearch'          => true,
        'enableCategoryDropdown'=> true,
    ) );

    $block_id = 'filter-blog-' . wp_rand();

    ob_start();
    ?>
    <div class="wp-block-understrap-filter-blog blogs-wrapper" id="<?php echo esc_attr( $block_id ); ?>">
        <!-- Search + Category Dropdown -->
        <?php if ( $attributes['enableSearch'] || $attributes['enableCategoryDropdown'] ) : ?>
        <div class="filter-blog-controls">
            <div class="d-flex flex-wrap">
                <div class="search filter-item d-flex align-items-center overflow-hidden">
                    <?php if ( $attributes['enableSearch'] ) : ?>
                    <button class="has-tertiary-background-color border-0">
                        <i class="fa fa-search has-white-color"></i>
                    </button>
                    <input type="text" class="filter-blog-search form-control border-0"
                        placeholder="<?php esc_attr_e( 'SEARCH', 'understrap' ); ?>"
                        aria-label="<?php esc_attr_e( 'SEARCH', 'understrap' ); ?>" />
                    <?php endif; ?>
                </div>

                <div class="filter filter-item d-flex align-items-center overflow-hidden">
                    <?php if ( $attributes['enableCategoryDropdown'] ) : ?>
                        <button class="has-tertiary-background-color border-0">
                            <i class="fa fa-bars has-white-color"></i>
                        </button>
                        <select class="filter-blog-category-dropdown form-control border-0" aria-label="<?php esc_attr_e( 'Select category', 'understrap' ); ?>">
                            <option value="all"><?php esc_html_e( 'CATEGORIES', 'understrap' ); ?></option>
                            <?php
                            $categories = get_categories( array(
                                'orderby'    => 'name',
                                'order'      => 'ASC',
                                'hide_empty' => true,
                            ) );
                            foreach ( $categories as $category ) {
                                if ( ! empty( $attributes['selectedCategories'] ) && ! in_array( $category->term_id, $attributes['selectedCategories'] ) ) {
                                    continue;
                                }
                                echo '<option value="' . esc_attr( $category->slug ) . '">' . esc_html( $category->name ) . '</option>';
                            }
                            ?>
                        </select>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <?php if ( $attributes['showFilterTabs'] ) : ?>
        <div class="filter-blog-tabs mb-4">
            <div class="wp-block-buttons is-layout-flex">
                <?php echo render_filter_blog_tabs( $attributes ); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Loading Indicator -->
        <div class="filter-blog-loading" style="display: none;">
            <div class="spinner"></div>
            <p><?php esc_html_e( 'Loading posts...', 'understrap' ); ?></p>
        </div>

        <!-- Posts Container (data attributes passed so JS uses block settings) -->
        <div class="filter-blog-posts blogs-grid"
                data-posts-per-page="<?php echo esc_attr( $attributes['postsPerPage'] ); ?>"
                data-display-featured-image="<?php echo esc_attr( $attributes['displayFeaturedImage'] ? '1' : '0' ); ?>"
                data-display-date="<?php echo esc_attr( $attributes['displayDate'] ? '1' : '0' ); ?>"
                data-display-author="<?php echo esc_attr( $attributes['displayAuthor'] ? '1' : '0' ); ?>"
                data-display-category="<?php echo esc_attr( $attributes['displayCategory'] ? '1' : '0' ); ?>"
                data-display-excerpt="<?php echo esc_attr( $attributes['displayExcerpt'] ? '1' : '0' ); ?>"
                data-excerpt-length="<?php echo esc_attr( $attributes['excerptLength'] ); ?>"
                data-show-filter-tabs="<?php echo esc_attr( $attributes['showFilterTabs'] ? '1' : '0' ); ?>"
                data-enable-search="<?php echo esc_attr( $attributes['enableSearch'] ? '1' : '0' ); ?>"
                data-enable-category-dropdown="<?php echo esc_attr( $attributes['enableCategoryDropdown'] ? '1' : '0' ); ?>">
            <?php
            // Initial render: show page 1, all categories, no search
            echo render_filter_blog_posts( $attributes, 1, 'all', '' );
            ?>
        </div>

        <!-- Pagination -->
        <div class="filter-blog-pagination">
            <?php echo render_filter_blog_pagination( $attributes, 1 ); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render filter tabs
 */
function render_filter_blog_tabs( $attributes ) {
    $categories = get_categories( array(
        'orderby'    => 'name',
        'order'      => 'ASC',
        'hide_empty' => true,
    ) );

    $output  = '<div class="wp-block-button filter-blog-tab active">';
    $output .= '<a href="#" class="wp-block-button__link wp-element-button" data-category="all">';
    $output .= esc_html__( 'All', 'understrap' );
    $output .= '</a></div>';

    foreach ( $categories as $category ) {
        if ( ! empty( $attributes['selectedCategories'] ) && ! in_array( $category->term_id, $attributes['selectedCategories'] ) ) {
            continue;
        }
        $output .= '<div class="wp-block-button filter-blog-tab">';
        $output .= '<a href="#" class="wp-block-button__link wp-element-button" data-category="' . esc_attr( $category->slug ) . '">';
        $output .= esc_html( $category->name );
        $output .= '</a></div>';
    }

    return $output;
}

/**
 * Render blog posts grid
 */
function render_filter_blog_posts( $attributes, $paged = 1, $category = 'all', $search = '' ) {
    $query_args = array(
        'post_type'      => 'post',
        'posts_per_page' => intval( $attributes['postsPerPage'] ?? 9 ),
        'paged'          => max( 1, intval( $paged ) ),
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    // Add search filter if provided
    if ( ! empty( $search ) ) {
        $query_args['s'] = sanitize_text_field( $search );
    }

    // Add category filter if not "all"
    if ( ! empty( $category ) && $category !== 'all' ) {
        $query_args['category_name'] = sanitize_text_field( $category );
    }

    $query = new WP_Query( $query_args );

    if ( ! $query->have_posts() ) {
        return '<div class="filter-blog-no-posts"><p>' . esc_html__( 'No posts found.', 'understrap' ) . '</p></div>';
    }

    ob_start();
    echo '<div class="posts blogs">';
    while ( $query->have_posts() ) {
        $query->the_post();
        echo render_filter_blog_card( $attributes );
    }
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
}

/**
 * Render individual blog card (with Keep Reading)
 */
function render_filter_blog_card( $attributes ) {
    $post_id    = get_the_ID();
    $categories = get_the_category();

    ob_start();
    ?>
    <article class="post blog-post d-flex flex-column">
        <div class="blog-thumbnail  w-100 text-center overflow-hidden">
            <a href="<?php the_permalink(); ?>">
                <?php if ( ! empty( $attributes['displayFeaturedImage'] ) && has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
                <?php else: ?>
                    <img width="350" height="200" src="<?php echo home_url(); ?>/wp-content/uploads/2025/11/blog-default-thumbnail.png" class="attachment-medium_large size-medium_large wp-post-image" alt="" loading="lazy" decoding="async">
                <?php endif; ?>
            </a>
        </div>


        <div class="blog-content">
            <?php if ( ! empty( $attributes['displayCategory'] ) && ! empty( $categories ) ) : ?>
            <div class="post-category">
                <span class="category-badge"><?php echo esc_html( $categories[0]->name ); ?></span>
            </div>
            <?php endif; ?>

            <a href="<?php the_permalink(); ?>" class="blog-title d-inline-block text-decoration-none">
                <h5 class="has-tertiary-color"><?php the_title(); ?></h5>
            </a>

            <?php if ( ! empty( $attributes['displayExcerpt'] ) ) : ?>
            <div class="blog-excerpt">
                <p class="has-darkgray-color mb-0"><?php echo wp_trim_words( get_the_excerpt(), intval( $attributes['excerptLength'] ) / 7, '...' ); ?></p>
            </div>
            <?php endif; ?>

            <div class="blog-read-more">
                <div class="wp-block-button is-style-tertiary">
                    <a class="wp-block-button__link wp-element-button p-0" href="<?php the_permalink(); ?>">
                        <?php esc_html_e( 'Learn More', 'understrap' ); ?> 
                        <span class="icon">&plus;</span>
                    </a>
                </div>
            </div>

            <?php if ( ! empty( $attributes['displayAuthor'] ) || ! empty( $attributes['displayDate'] ) ) : ?>
            <div class="blog-meta">
                <?php if ( ! empty( $attributes['displayAuthor'] ) ) : ?>
                <span class="blog-author">
                    <?php echo get_avatar( get_the_author_meta( 'ID' ), 24 ); ?>
                    <?php echo esc_html( get_the_author() ); ?>
                </span>
                <?php endif; ?>

                <?php if ( ! empty( $attributes['displayDate'] ) ) : ?>
                <span class="blog-published-date"><?php echo esc_html( get_the_date() ); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

/**
 * Render pagination
 */
function render_filter_blog_pagination( $attributes, $paged = 1, $category = 'all', $search = '' ) {
    $query_args = array(
        'post_type'      => 'post',
        'posts_per_page' => intval( $attributes['postsPerPage'] ?? 9 ),
        'post_status'    => 'publish',
    );

    if ( ! empty( $search ) ) {
        $query_args['s'] = sanitize_text_field( $search );
    }

    if ( ! empty( $category ) && $category !== 'all' ) {
        $query_args['category_name'] = sanitize_text_field( $category );
    }

    $query       = new WP_Query( $query_args );
    $total_pages = $query->max_num_pages;

    if ( $total_pages <= 1 ) {
        return '';
    }

    $output  = '<div class="filter-blog-pagination-wrapper"><nav class="wp-block-query-pagination is-layout-flex">';

    if ( $paged > 1 ) {
        $output .= '<a href="#" class="wp-block-query-pagination-previous page-numbers" data-page="' . ( $paged - 1 ) . '">'
        . '<i class="icon-angles-left4"></i></a>';
    }

    for ( $i = 1; $i <= $total_pages; $i++ ) {
        $active_class = ( $i == $paged ) ? ' current' : '';
        $output .= '<a href="#" class="wp-block-query-pagination-numbers page-numbers' . esc_attr( $active_class ) . '" data-page="' . $i . '">' . $i . '</a>';
    }

    if ( $paged < $total_pages ) {
        $output .= '<a href="#" class="wp-block-query-pagination-next page-numbers" data-page="' . ( $paged + 1 ) . '">'
        . '<i class="icon-angles-right4"></i></a>';
    }

    $output .= '</nav></div>';

    wp_reset_postdata();
    return $output;
}

/**
 * AJAX handler for filtering + search
 */
add_action( 'wp_ajax_filter_blog_posts', 'handle_filter_blog_ajax' );
add_action( 'wp_ajax_nopriv_filter_blog_posts', 'handle_filter_blog_ajax' );

function handle_filter_blog_ajax() {
    check_ajax_referer( 'filter_blog_nonce', 'nonce' );

    $category = sanitize_text_field( $_POST['category'] ?? 'all' );
    $paged    = max( 1, intval( $_POST['paged'] ?? 1 ) );
    $search   = sanitize_text_field( $_POST['search'] ?? '' );

    $attributes = array(
        'postsPerPage'         => intval( $_POST['postsPerPage'] ?? 9 ),
        'displayFeaturedImage' => ( isset( $_POST['displayFeaturedImage'] ) && ( $_POST['displayFeaturedImage'] === '1' || $_POST['displayFeaturedImage'] === 1 || $_POST['displayFeaturedImage'] === 'true' ) ),
        'displayDate'          => ( isset( $_POST['displayDate'] ) && ( $_POST['displayDate'] === '1' || $_POST['displayDate'] === 1 || $_POST['displayDate'] === 'true' ) ),
        'displayAuthor'        => ( isset( $_POST['displayAuthor'] ) && ( $_POST['displayAuthor'] === '1' || $_POST['displayAuthor'] === 1 || $_POST['displayAuthor'] === 'true' ) ),
        'displayCategory'      => ( isset( $_POST['displayCategory'] ) && ( $_POST['displayCategory'] === '1' || $_POST['displayCategory'] === 1 || $_POST['displayCategory'] === 'true' ) ),
        'displayExcerpt'       => ( isset( $_POST['displayExcerpt'] ) && ( $_POST['displayExcerpt'] === '1' || $_POST['displayExcerpt'] === 1 || $_POST['displayExcerpt'] === 'true' ) ),
        'excerptLength'        => intval( $_POST['excerptLength'] ?? 150 ),
    );

    $posts_html      = render_filter_blog_posts( $attributes, $paged, $category, $search );
    $pagination_html = render_filter_blog_pagination( $attributes, $paged, $category, $search );

    wp_send_json_success( array(
        'posts'      => $posts_html,
        'pagination' => $pagination_html,
    ) );
}
