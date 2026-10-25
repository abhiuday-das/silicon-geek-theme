<?php
/**
 * Swiper Slider Block Registration
 */

// // Enqueue Swiper CSS and JS
// add_action('wp_enqueue_scripts', function() {
//     // Swiper CSS
//     wp_enqueue_style(
//         'swiper-bundle-css',
//         'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
//         [],
//         '11.0.0'
//     );
    
//     // Swiper JS
//     wp_enqueue_script(
//         'swiper-bundle-js',
//         'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
//         [],
//         '11.0.0',
//         true
//     );
// });

// Register Swiper Slider Block
add_action('init', function() {
    wp_register_script(
        'understrap-swiper-slider-block',
        get_template_directory_uri() . '/assets/js/swiper-slider/swiper-slider.js',
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data'],
        filemtime(get_template_directory() . '/assets/js/swiper-slider/swiper-slider.js')
    );

    register_block_type('understrap/swiper-slider', [
        'editor_script' => 'understrap-swiper-slider-block',
        'render_callback' => 'render_swiper_slider_block',
        'attributes' => [
            'slideSource' => ['type' => 'string', 'default' => 'posts'],
            'postType' => ['type' => 'string', 'default' => 'post'],
            'postsPerPage' => ['type' => 'number', 'default' => 6],
            'orderBy' => ['type' => 'string', 'default' => 'date'],
            'order' => ['type' => 'string', 'default' => 'DESC'],
            'taxonomies' => ['type' => 'object', 'default' => []],
            'staticSlides' => ['type' => 'array', 'default' => []],
            'slidesPerView' => ['type' => 'number', 'default' => 1],
            'spaceBetween' => ['type' => 'number', 'default' => 30],
            'loop' => ['type' => 'boolean', 'default' => true],
            'autoplay' => ['type' => 'boolean', 'default' => false],
            'autoplayDelay' => ['type' => 'number', 'default' => 3000],
            'speed' => ['type' => 'number', 'default' => 300],
            'navigation' => ['type' => 'boolean', 'default' => true],
            'pagination' => ['type' => 'boolean', 'default' => true],
            'paginationType' => ['type' => 'string', 'default' => 'bullets'],
            'scrollbar' => ['type' => 'boolean', 'default' => false],
            'effect' => ['type' => 'string', 'default' => 'slide'],
            'direction' => ['type' => 'string', 'default' => 'horizontal'],
            'centeredSlides' => ['type' => 'boolean', 'default' => false],
            'grabCursor' => ['type' => 'boolean', 'default' => true],
            'freeMode' => ['type' => 'boolean', 'default' => false],
            'breakpointMobile' => ['type' => 'number', 'default' => 1],
            'breakpointTablet' => ['type' => 'number', 'default' => 2],
            'breakpointDesktop' => ['type' => 'number', 'default' => 3],
        ],
    ]);
});

/**
 * Render Swiper Slider Block
 */
function render_swiper_slider_block($attributes) {
    $slide_source = $attributes['slideSource'] ?? 'posts';
    $post_type = $attributes['postType'] ?? 'post';
    $posts_per_page = $attributes['postsPerPage'] ?? 6;
    $order_by = $attributes['orderBy'] ?? 'date';
    $order = $attributes['order'] ?? 'DESC';
    $taxonomies = $attributes['taxonomies'] ?? [];
    $static_slides = $attributes['staticSlides'] ?? [];
    
    // Swiper settings
    $slides_per_view = $attributes['slidesPerView'] ?? 1;
    $space_between = $attributes['spaceBetween'] ?? 30;
    $loop = $attributes['loop'] ?? true;
    $autoplay = $attributes['autoplay'] ?? false;
    $autoplay_delay = $attributes['autoplayDelay'] ?? 3000;
    $speed = $attributes['speed'] ?? 300;
    $navigation = $attributes['navigation'] ?? true;
    $pagination = $attributes['pagination'] ?? true;
    $pagination_type = $attributes['paginationType'] ?? 'bullets';
    $scrollbar = $attributes['scrollbar'] ?? false;
    $effect = $attributes['effect'] ?? 'slide';
    $direction = $attributes['direction'] ?? 'horizontal';
    $centered_slides = $attributes['centeredSlides'] ?? false;
    $grab_cursor = $attributes['grabCursor'] ?? true;
    $free_mode = $attributes['freeMode'] ?? false;
    $breakpoint_mobile = $attributes['breakpointMobile'] ?? 1;
    $breakpoint_tablet = $attributes['breakpointTablet'] ?? 2;
    $breakpoint_desktop = $attributes['breakpointDesktop'] ?? 3;
    
    $slider_id = 'swiper-' . uniqid();
    
    // Build Swiper config
    $swiper_config = [
        'slidesPerView' => $slides_per_view,
        'spaceBetween' => $space_between,
        'loop' => $loop,
        'speed' => $speed,
        'effect' => $effect,
        'direction' => $direction,
        'centeredSlides' => $centered_slides,
        'grabCursor' => $grab_cursor,
        'freeMode' => $free_mode,
        'breakpoints' => [
            '375' => [
                'slidesPerView' => $breakpoint_mobile,
                'spaceBetween' => 20,
            ],
            '640' => [
                'slidesPerView' => $breakpoint_mobile,
                'spaceBetween' => 20,
            ],
            '768' => [
                'slidesPerView' => $breakpoint_tablet,
                'spaceBetween' => 30,
            ],
            '1024' => [
                'slidesPerView' => $breakpoint_desktop,
                'spaceBetween' => $space_between,
            ],
        ],
    ];
    
    if ($autoplay) {
        $swiper_config['autoplay'] = [
            'delay' => $autoplay_delay,
            'disableOnInteraction' => false,
        ];
    }
    
    if ($navigation) {
        $swiper_config['navigation'] = [
            'nextEl' => '.swiper-button-next',
            'prevEl' => '.swiper-button-prev',
        ];
    }
    
    if ($pagination) {
        $swiper_config['pagination'] = [
            'el' => '.swiper-pagination',
            'type' => $pagination_type,
            'clickable' => true,
        ];
    }
    
    if ($scrollbar) {
        $swiper_config['scrollbar'] = [
            'el' => '.swiper-scrollbar',
            'draggable' => true,
        ];
    }
    
    // Get slides
    $slides = [];
    
    if ($slide_source === 'posts') {
        $query_args = [
            'post_type' => $post_type,
            'posts_per_page' => $posts_per_page,
            'orderby' => $order_by,
            'order' => $order,
            'post_status' => 'publish',
        ];
        
        // Add taxonomy filters
        if (!empty($taxonomies) && is_array($taxonomies)) {
            $tax_query = ['relation' => 'AND'];
            
            foreach ($taxonomies as $taxonomy => $term_ids) {
                if (!empty($term_ids) && is_array($term_ids)) {
                    $tax_query[] = [
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => $term_ids,
                        'operator' => 'IN',
                    ];
                }
            }
            
            if (count($tax_query) > 1) {
                $query_args['tax_query'] = $tax_query;
            }
        }
        
        $query = new WP_Query($query_args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_content = get_the_content();
                $post_content = apply_filters('the_content', $post_content);
                $slides[] = $post_content;
            }
            wp_reset_postdata();
        }
    } else {
        // Static slides
        foreach ($static_slides as $slide) {
            if (!empty($slide['content'])) {
                $slides[] = wp_kses_post($slide['content']);
            }
        }
    }
    
    if (empty($slides)) {
        return '<div class="swiper-slider-empty"><p>No slides to display.</p></div>';
    }
    
    // Output
    ob_start();
    ?>
    <div class="swiper-slider-wrapper" id="<?php echo esc_attr($slider_id); ?>">
        <div class="swiper">
            <div class="swiper-wrapper">
                <?php foreach ($slides as $index => $slide_content): ?>
                    <div class="swiper-slide h-auto">
                        <div class="swiper-slide-content h-100">
                            <?php echo $slide_content; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($navigation): ?>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            <?php endif; ?>
            
            <?php if ($pagination): ?>
                <div class="swiper-pagination"></div>
            <?php endif; ?>
            
            <?php if ($scrollbar): ?>
                <div class="swiper-scrollbar"></div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swiper !== 'undefined') {
            new Swiper('#<?php echo esc_js($slider_id); ?> .swiper', <?php echo wp_json_encode($swiper_config); ?>);
        }
    });
    </script>
    
    <style>
    #<?php echo esc_attr($slider_id); ?> .swiper {
        width: 100%;
        height: auto;
    }
    #<?php echo esc_attr($slider_id); ?> .swiper-slide {
        display: flex;
        align-items: stretch;
    }
    #<?php echo esc_attr($slider_id); ?> .swiper-slide-content {
        width: 100%;
    }
    #<?php echo esc_attr($slider_id); ?> .swiper-button-next,
    #<?php echo esc_attr($slider_id); ?> .swiper-button-prev {
        color: #333;
        background: rgba(255,255,255,0.8);
        width: 44px;
        height: 44px;
        border-radius: 50%;
    }
    #<?php echo esc_attr($slider_id); ?> .swiper-pagination-bullet-active {
        background: #007bff;
    }
    </style>
    <?php
    return ob_get_clean();
}
