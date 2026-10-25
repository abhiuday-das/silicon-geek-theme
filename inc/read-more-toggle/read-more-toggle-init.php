<?php
/**
 * Read More / Read Less Toggle Block
 * 
 * Allows content to be collapsed/expanded with accessible toggle buttons.
 * Works with block themes and supports InnerBlocks for flexible content.
 * 
 * @package ts-block-theme
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register Read More Toggle Block
 */
function ts_register_read_more_toggle_block() {
    // Register editor script
    wp_register_script(
        'ts-read-more-toggle-editor',
        get_template_directory_uri() . '/assets/js/read-more-toggle/read-more-toggle.js',
        array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'),
        filemtime(get_template_directory() . '/assets/js/read-more-toggle/read-more-toggle.js'),
        true
    );

    // Register frontend script
    wp_register_script(
        'ts-read-more-toggle-frontend',
        get_template_directory_uri() . '/assets/js/read-more-toggle/read-more-frontend.js',
        array(),
        filemtime(get_template_directory() . '/assets/js/read-more-toggle/read-more-frontend.js'),
        true
    );

    // Register the block type
    register_block_type('ts-block-theme/read-more-toggle', array(
        'editor_script'   => 'ts-read-more-toggle-editor',
        'script'          => 'ts-read-more-toggle-frontend',
        'style'           => 'ts-read-more-toggle-style',
        'render_callback' => 'ts_render_read_more_toggle_block',
        'attributes'      => array(
            'readMoreText' => array(
                'type'    => 'string',
                'default' => 'Read More'
            ),
            'readLessText' => array(
                'type'    => 'string',
                'default' => 'Read Less'
            ),
            'expanded' => array(
                'type'    => 'boolean',
                'default' => false
            )
        )
    ));
}
add_action('init', 'ts_register_read_more_toggle_block');

/**
 * Render callback for Read More Toggle block
 * 
 * @param array $attributes Block attributes
 * @param string $content Block content (InnerBlocks)
 * @return string Rendered HTML
 */
function ts_render_read_more_toggle_block($attributes, $content) {
    // Extract attributes with defaults
    $read_more_text = isset($attributes['readMoreText']) ? esc_html($attributes['readMoreText']) : 'Read More';
    $read_less_text = isset($attributes['readLessText']) ? esc_html($attributes['readLessText']) : 'Read Less';
    $expanded = isset($attributes['expanded']) ? $attributes['expanded'] : false;
    
    // Generate unique ID for this instance
    $block_id = 'read-more-toggle-' . wp_unique_id();
    
    // Parse inner blocks content
    // The content comes as rendered HTML from InnerBlocks
    // We need to split it into visible and hidden sections
    $blocks = parse_blocks($content);
    
    // Build output
    ob_start();
    ?>
    <div class="read-more-toggle-wrapper" id="<?php echo esc_attr($block_id); ?>" data-read-more-text="<?php echo esc_attr($read_more_text); ?>" data-read-less-text="<?php echo esc_attr($read_less_text); ?>">
        <!-- Always Visible Content -->
        <div class="read-more-visible-content">
            <?php 
            // Render the content - it includes both sections from editor
            // We'll handle the separation via CSS classes added in editor
            echo $content; 
            ?>
        </div>
        
        <!-- Toggle Buttons -->
        <div class="read-more-toggle-buttons">
            <button 
                type="button"
                class="read-more-button" 
                aria-expanded="<?php echo $expanded ? 'true' : 'false'; ?>"
                aria-controls="<?php echo esc_attr($block_id); ?>-hidden"
            >
                <span class="read-more-text"><?php echo esc_html($read_more_text); ?></span>
                <span class="read-more-icon" aria-hidden="true">+</span>
            </button>
            
            <button 
                type="button"
                class="read-less-button" 
                aria-expanded="<?php echo $expanded ? 'true' : 'false'; ?>"
                aria-controls="<?php echo esc_attr($block_id); ?>-hidden"
                style="display: none;"
            >
                <span class="read-less-text"><?php echo esc_html($read_less_text); ?></span>
                <span class="read-less-icon" aria-hidden="true">−</span>
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
