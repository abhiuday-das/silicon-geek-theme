<?php
/**
 * Recent Blog Block Registration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Enqueue recent-blog.js
add_action( 'init', function() {
    wp_register_script(
        'understrap-recent-blog-block',
        get_template_directory_uri() . '/assets/js/blog/recent-blog.js',
        [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data' ],
        filemtime( get_template_directory() . '/assets/js/blog/recent-blog.js' )
    );

    register_block_type( 'understrap/recent-blog', array(
        'editor_script' => 'understrap-recent-blog-block',
        'render_callback' => 'render_recent_blog_block',
    ) );
});

/**
 * Server-side rendering for Recent Blog block
 */
function render_recent_blog_block( $attributes ) {
    $attributes = wp_parse_args( $attributes, array(
        'postsToShow' => 3,
        'displayFeaturedImage' => true,
        'displayDate' => true,
        'displayAuthor' => true,
        'displayAuthorAvatar' => true,
        'displayCategory' => true,
        'displayExcerpt' => false,
        'excerptLength' => 55,
        'orderBy' => 'date',
        'order' => 'desc',
        'categories' => array(),
        'layout' => 'grid',
        'columns' => 3,
    ));

    $query_args = array(
        'post_type' => 'post',
        'posts_per_page' => $attributes['postsToShow'],
        'post_status' => 'publish',
        'orderby' => $attributes['orderBy'],
        'order' => $attributes['order'],
    );

    if ( ! empty( $attributes['categories'] ) ) {
        $query_args['category__in'] = $attributes['categories'];
    }

    $posts = get_posts( $query_args );

    if ( empty( $posts ) ) {
        return '<div class="recent-blog-block my-0"><div class="no-posts">No posts found.</div></div>';
    }

    $wrapper_classes = array(
        'recent-blog-block',
        'layout-' . $attributes['layout'],
        'columns-' . $attributes['columns'],
    );

    // Generate unique ID for this swiper instance
    $swiper_id = 'recent-blog-swiper-' . uniqid();

    ob_start();
    ?>
    <div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
        <div class="swiper <?php echo esc_attr( $swiper_id ); ?>" 
             data-slides-desktop="<?php echo esc_attr( $attributes['columns'] ); ?>"
             data-slides-mobile="1"
             data-space-between="30"
             data-loop="false"
             data-autoplay="false">
            
            <div class="recent-posts">
                <?php foreach ( $posts as $post ) : ?>
                    <?php
                    $post_id = $post->ID;
                    $title = get_the_title( $post_id );
                    $permalink = get_permalink( $post_id );
                    $featured_image = get_the_post_thumbnail_url( $post_id, 'medium' );
                    $categories = get_the_category( $post_id );
                    $category_name = ! empty( $categories ) ? $categories[0]->name : 'Uncategorized';
                    $author_name = get_the_author_meta( 'display_name', $post->post_author );
                    $author_avatar = get_avatar_url( $post->post_author, array( 'size' => 40 ) );
                    $post_date = get_the_date( 'M j, Y', $post_id );
                    $excerpt = get_the_excerpt( $post_id );
                    
                    // Truncate excerpt if needed
                    if ( $attributes['displayExcerpt'] && $excerpt ) {
                        $words = explode( ' ', $excerpt );
                        if ( count( $words ) > $attributes['excerptLength'] ) {
                            $excerpt = implode( ' ', array_slice( $words, 0, $attributes['excerptLength'] ) ) . '...';
                        }
                    }
                    ?>
                    <article class="post blog-post d-flex flex-column">
                        <div class="blog-thumbnail  w-100 text-center overflow-hidden">
                            <a href="<?php echo $permalink; ?>">
                                <?php if ( ! empty( $featured_image )) : ?>
                                    <img width="350" height="200" src="<?php echo $featured_image; ?>" class="attachment-medium_large size-medium_large wp-post-image" alt="" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <img width="350" height="200" src="<?php echo home_url(); ?>/wp-content/themes/ts-block-theme/assets/images/blog_logo.jpg" class="attachment-medium_large size-medium_large wp-post-image" alt="" loading="lazy" decoding="async">
                                <?php endif; ?>
                            </a>
                        </div>


                        <div class="blog-content">
                            <?php if ( ! empty( $attributes['displayCategory'] ) && ! empty( $categories ) ) : ?>
                            <div class="post-category d-none d-lg-block">
                                <span class="category-badge"><?php echo esc_html( $categories[0]->name ); ?></span>
                            </div>
                            <?php endif; ?>

                            <a href="<?php echo $permalink; ?>" class="blog-title d-inline-block text-decoration-none">
                                <h5 class="has-primary-color"><?php echo $title; ?></h5>
                            </a>

                            <?php if ( ! empty( $attributes['displayExcerpt'] ) ) : ?>
                            <div class="blog-excerpt d-none d-lg-block">
                                <p class="has-darkgray-color mb-0"><?php echo wp_trim_words( $excerpt, intval( $attributes['excerptLength'] ) / 7, '...' ); ?></p>
                            </div>
                            <?php endif; ?>

                            <div class="blog-read-more">
                                <div class="">
                                    <a class=" p-0" href="<?php echo $permalink; ?>">
                                        <span class="align-bottom"><?php esc_html_e( 'Learn More', 'understrap' ); ?> </span>
                                        
</span>
                                    </a>
                                </div>
                            </div>

                            <?php if ( ! empty( $attributes['displayAuthor'] ) || ! empty( $attributes['displayDate'] ) ) : ?>
                            <div class="blog-meta d-none d-lg-block">
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
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swiper !== 'undefined') {
                const swiperElement = document.querySelector('.<?php echo esc_js( $swiper_id ); ?>');
                if (swiperElement && !swiperElement.swiper) {
                    const swiperConfig = {
                        slidesPerView: 1,
                        spaceBetween: 30,
                        loop: true,
                        // navigation: {
                        //     nextEl: '.<?php //echo esc_js( $swiper_id ); ?> .swiper-button-next',
                        //     prevEl: '.<?php //echo esc_js( $swiper_id ); ?> .swiper-button-prev',
                        // },
                        // pagination: {
                        //     el: '.<?php //echo esc_js( $swiper_id ); ?> .swiper-pagination',
                        //     clickable: true,
                        // },
                        breakpoints: {
                            768: {
                                slidesPerView: <?php echo esc_js( $attributes['columns'] ); ?>,
                                spaceBetween: 00,
                                navigation: false,
                                pagination: false,
                                loop:false

                            }
                        }
                    };

                    new Swiper(swiperElement, swiperConfig);
                }
            }
        });
    </script>
    <?php
    return ob_get_clean();
}