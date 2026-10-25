<?php
/**
 * Bootstrap Accordion Shortcode
 * 
 * Usage:
 * [bootstrap_accordion]
 *   [accordion_item title="Item 1"]Content for item 1[/accordion_item]
 *   [accordion_item title="Item 2" open="true"]Content for item 2[/accordion_item]
 * [/bootstrap_accordion]
 * 
 * @package ts-block-theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bootstrap Accordion Container Shortcode
 */
function bootstrap_accordion_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'id' => 'accordion-' . uniqid(),
        'class' => '',
        'allow_multiple' => 'false',
        'first_open' => 'true'
    ), $atts, 'bootstrap_accordion');

    // Store accordion ID and settings for child items
    global $accordion_id, $accordion_allow_multiple, $accordion_item_counter, $accordion_first_open;
    $accordion_id = $atts['id'];
    $accordion_allow_multiple = $atts['allow_multiple'] === 'true';
    $accordion_item_counter = 0;
    $accordion_first_open = $atts['first_open'] === 'true';

    $classes = 'bootstrap-accordion-wrapper ' . $atts['class'];
    
    ob_start();
    ?>
    <div class="<?php echo esc_attr($classes); ?>">
        <div class="accordion" id="<?php echo esc_attr($atts['id']); ?>" data-bs-allow-multiple="<?php echo esc_attr($atts['allow_multiple']); ?>">
            <?php echo do_shortcode($content); ?>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const accordion = document.getElementById('<?php echo esc_js($atts['id']); ?>');
        if (accordion && '<?php echo esc_js($atts['allow_multiple']); ?>' === 'false') {
            // Ensure only one item can be open at a time
            accordion.addEventListener('show.bs.collapse', function (event) {
                const openItems = accordion.querySelectorAll('.accordion-collapse.show');
                openItems.forEach(function(item) {
                    if (item !== event.target) {
                        const button = accordion.querySelector('[data-bs-target="#' + item.id + '"]');
                        if (button) {
                            button.classList.add('collapsed');
                            button.setAttribute('aria-expanded', 'false');
                        }
                        item.classList.remove('show');
                    }
                });
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('bootstrap_accordion', 'bootstrap_accordion_shortcode');

/**
 * Accordion Item Shortcode
 */
function accordion_item_shortcode($atts, $content = null) {
    global $accordion_id, $accordion_allow_multiple, $accordion_item_counter, $accordion_first_open;
    
    $accordion_item_counter++;
    
    $atts = shortcode_atts(array(
        'title' => 'Accordion Item',
        'open' => 'auto', // 'auto' means first item open, others closed
        'id' => 'item-' . uniqid()
    ), $atts, 'accordion_item');

    $item_id = $accordion_id . '-' . $atts['id'];
    
    // Determine if this item should be open
    if ($atts['open'] === 'auto') {
        $is_open = ($accordion_item_counter === 1 && $accordion_first_open);
    } else {
        $is_open = $atts['open'] === 'true';
    }
    
    $collapsed_class = $is_open ? '' : 'collapsed';
    $show_class = $is_open ? 'show' : '';
    $expanded = $is_open ? 'true' : 'false';

    ob_start();
    ?>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button <?php echo esc_attr($collapsed_class); ?>" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#<?php echo esc_attr($item_id); ?>" 
                    aria-expanded="<?php echo esc_attr($expanded); ?>" 
                    aria-controls="<?php echo esc_attr($item_id); ?>">
                <?php echo esc_html($atts['title']); ?>
            </button>
        </h2>
        <div id="<?php echo esc_attr($item_id); ?>" 
             class="accordion-collapse collapse <?php echo esc_attr($show_class); ?>" 
             data-bs-parent="#<?php echo esc_attr($accordion_id); ?>">
            <div class="accordion-body">
               <h6> <?php echo do_shortcode($content); ?></h6>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('accordion_item', 'accordion_item_shortcode');

/**
 * Simple Bootstrap Accordion Shortcode (single shortcode with items as attributes)
 * 
 * Usage:
 * [simple_accordion 
 *   item1_title="First Item" 
 *   item1_content="Content for first item"
 *   item2_title="Second Item" 
 *   item2_content="Content for second item"
 *   allow_multiple="false"
 *   first_open="true"
 * ]
 */
function simple_accordion_shortcode($atts) {
    $defaults = array(
        'id' => 'simple-accordion-' . uniqid(),
        'class' => '',
        'allow_multiple' => 'false',
        'first_open' => 'true'
    );
    
    $atts = shortcode_atts($defaults, $atts, 'simple_accordion');
    
    $classes = 'bootstrap-accordion-wrapper ' . $atts['class'];
    $items = array();
    $first_open = $atts['first_open'] === 'true';
    
    // Extract items from attributes
    foreach ($atts as $key => $value) {
        if (preg_match('/^item(\d+)_title$/', $key, $matches)) {
            $item_num = $matches[1];
            $items[$item_num]['title'] = $value;
        } elseif (preg_match('/^item(\d+)_content$/', $key, $matches)) {
            $item_num = $matches[1];
            $items[$item_num]['content'] = $value;
        } elseif (preg_match('/^item(\d+)_open$/', $key, $matches)) {
            $item_num = $matches[1];
            $items[$item_num]['open'] = $value === 'true';
        }
    }
    
    if (empty($items)) {
        return '<p>No accordion items found.</p>';
    }
    
    // Sort items by number
    ksort($items);
    
    ob_start();
    ?>
    <div class="<?php echo esc_attr($classes); ?>">
        <div class="accordion" id="<?php echo esc_attr($atts['id']); ?>" data-bs-allow-multiple="<?php echo esc_attr($atts['allow_multiple']); ?>">
            <?php 
            $item_counter = 0;
            foreach ($items as $index => $item): 
                if (empty($item['title']) || empty($item['content'])) continue;
                
                $item_counter++;
                $item_id = $atts['id'] . '-item-' . $index;
                
                // First item is open by default (unless specifically set)
                if (isset($item['open'])) {
                    $is_open = $item['open'];
                } else {
                    $is_open = ($item_counter === 1 && $first_open);
                }
                
                $collapsed_class = $is_open ? '' : 'collapsed';
                $show_class = $is_open ? 'show' : '';
                $expanded = $is_open ? 'true' : 'false';
            ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button <?php echo esc_attr($collapsed_class); ?>" 
                            type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#<?php echo esc_attr($item_id); ?>" 
                            aria-expanded="<?php echo esc_attr($expanded); ?>" 
                            aria-controls="<?php echo esc_attr($item_id); ?>">
                        <?php echo esc_html($item['title']); ?>
                    </button>
                </h2>
                <div id="<?php echo esc_attr($item_id); ?>" 
                     class="accordion-collapse collapse <?php echo esc_attr($show_class); ?>" 
                     data-bs-parent="#<?php echo esc_attr($atts['id']); ?>">
                    <div class="accordion-body">
                        <?php echo wp_kses_post($item['content']); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const accordion = document.getElementById('<?php echo esc_js($atts['id']); ?>');
        if (accordion && '<?php echo esc_js($atts['allow_multiple']); ?>' === 'false') {
            // Ensure only one item can be open at a time
            accordion.addEventListener('show.bs.collapse', function (event) {
                const openItems = accordion.querySelectorAll('.accordion-collapse.show');
                openItems.forEach(function(item) {
                    if (item !== event.target) {
                        const button = accordion.querySelector('[data-bs-target="#' + item.id + '"]');
                        if (button) {
                            button.classList.add('collapsed');
                            button.setAttribute('aria-expanded', 'false');
                        }
                        item.classList.remove('show');
                    }
                });
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('simple_accordion', 'simple_accordion_shortcode');