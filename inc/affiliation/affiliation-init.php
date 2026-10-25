<?php
/**
 * Affiliation Block Registration
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register block + scripts
 */
add_action( 'init', function() {

    // Register editor JS
    wp_register_script(
        'understrap-affiliation-block',
        get_template_directory_uri() . '/assets/js/affiliation/affiliation.js',
        [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data' ],
        filemtime( get_template_directory() . '/assets/js/affiliation/affiliation.js' )
    );

    // Enqueue Swiper CSS/JS globally (frontend)
    wp_register_style(
        'swiper-css',
        'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css',
        [],
        null
    );

    wp_register_script(
        'swiper-js',
        'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js',
        [],
        null,
        true
    );

    register_block_type(
        'understrap/affiliation',
        [
            'editor_script'   => 'understrap-affiliation-block',
            'render_callback' => 'render_affiliation_block',
            'attributes'      => [
                'images' => [
                    'type'    => 'array',
                    'default' => []
                ],
                'slidesPerViewDesktop' => [
                    'type'    => 'number',
                    'default' => 4
                ],
                'slidesPerViewMobile' => [
                    'type'    => 'number',
                    'default' => 1
                ],
                'spaceBetween' => [
                    'type'    => 'number',
                    'default' => 30
                ],
                'loop' => [
                    'type'    => 'boolean',
                    'default' => true
                ],
                'autoplay' => [
                    'type'    => 'boolean',
                    'default' => true
                ],
                'autoplayDelay' => [
                    'type'    => 'number',
                    'default' => 3000
                ],
            ],
        ]
    );
});

/**
 * Render callback for Affiliation block
 */
function render_affiliation_block( $attributes ) {

    wp_enqueue_style( 'swiper-css' );
    wp_enqueue_script( 'swiper-js' );

    $attributes = wp_parse_args( $attributes, [
        'images' => [],
        'slidesPerViewDesktop' => 4,
        'slidesPerViewMobile' => 1,
        'spaceBetween' => 30,
        'loop' => true,
        'autoplay' => true,
        'autoplayDelay' => 3000,
    ]);

    if ( empty( $attributes['images'] ) ) {
        return '<div class="affiliation-block"><div class="no-images">No affiliation images added.</div></div>';
    }

    $swiper_id = 'affiliation-swiper-' . uniqid();

    ob_start();
    ?>
    <div class="affiliation-block">
        <div class="swiper <?php echo esc_attr( $swiper_id ); ?>"
            data-mobile="<?php echo esc_attr( $attributes['slidesPerViewMobile'] ); ?>"
            data-desktop="<?php echo esc_attr( $attributes['slidesPerViewDesktop'] ); ?>"
            data-space="<?php echo esc_attr( $attributes['spaceBetween'] ); ?>"
            data-loop="<?php echo esc_attr( $attributes['loop'] ? 'true' : 'false' ); ?>"
            data-autoplay="<?php echo esc_attr( $attributes['autoplay'] ? 'true' : 'false' ); ?>"
            data-delay="<?php echo esc_attr( $attributes['autoplayDelay'] ); ?>"
        >

            <div class="swiper-wrapper">
                <?php foreach ( $attributes['images'] as $image ) : ?>
                    <div class="swiper-slide">
                        <div class="affiliation-image">
                            <?php if ( ! empty( $image['url'] ) ) : ?>
                                <a href="<?php echo esc_url( $image['url'] ); ?>" target="_blank" rel="noopener">
                            <?php endif; ?>

                            <img src="<?php echo esc_url( $image['src'] ); ?>"
                                 alt="<?php echo esc_attr( $image['alt'] ); ?>"
                                 loading="lazy">

                            <?php if ( ! empty( $image['url'] ) ) : ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-pagination"></div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const swiperEl = document.querySelector(".<?php echo esc_js($swiper_id); ?>");
        if (!swiperEl || swiperEl.swiper) return;

        const config = {
            slidesPerView: parseInt(swiperEl.dataset.mobile),
            spaceBetween: parseInt(swiperEl.dataset.space),
            loop: swiperEl.dataset.loop === "true",
            allowTouchMove: true,

            navigation: {
                nextEl: swiperEl.querySelector('.swiper-button-next'),
                prevEl: swiperEl.querySelector('.swiper-button-prev'),
            },

            pagination: {
                el: swiperEl.querySelector('.swiper-pagination'),
                clickable: true,
            },

            autoplay: swiperEl.dataset.autoplay === "true" ? {
                delay: parseInt(swiperEl.dataset.delay),
                disableOnInteraction: false,
            } : false,

            breakpoints: {
                992: {
                    slidesPerView: parseInt(swiperEl.dataset.desktop),
                    spaceBetween: parseInt(swiperEl.dataset.space),
                    allowTouchMove: false
                }
            }
        };

        new Swiper(swiperEl, config);
    });
    </script>

    <?php
    return ob_get_clean();
}

