<?php
/**
 * Responsive mobile/desktop background support for core/group
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'usp_rgb_generate_overlay_css' ) ) {
    /**
     * Helper function to generate overlay CSS
     */
    function usp_rgb_generate_overlay_css( $type, $color, $gradient, $alpha ) {
        if ( $type === 'gradient' && ! empty( $gradient ) ) {
            return 'background:' . $gradient . ';';
        } elseif ( $type === 'color' && ! empty( $color ) ) {
            // Handle different color formats
            if ( strpos( $color, 'rgb' ) !== false ) {
                // Already in rgb/rgba format
                if ( strpos( $color, 'rgba' ) !== false ) {
                    return 'background:' . $color . ';';
                } else {
                    // Convert rgb to rgba
                    preg_match( '/rgb\((\d+),\s*(\d+),\s*(\d+)\)/', $color, $matches );
                    if ( $matches ) {
                        return 'background:rgba(' . $matches[1] . ',' . $matches[2] . ',' . $matches[3] . ',' . $alpha . ');';
                    }
                }
            } elseif ( strpos( $color, '#' ) === 0 ) {
                // Convert hex to rgba
                $hex = str_replace( '#', '', $color );
                if ( strlen( $hex ) === 6 ) {
                    $r = hexdec( substr( $hex, 0, 2 ) );
                    $g = hexdec( substr( $hex, 2, 2 ) );
                    $b = hexdec( substr( $hex, 4, 2 ) );
                    return 'background:rgba(' . $r . ',' . $g . ',' . $b . ',' . $alpha . ');';
                } elseif ( strlen( $hex ) === 3 ) {
                    $r = hexdec( str_repeat( substr( $hex, 0, 1 ), 2 ) );
                    $g = hexdec( str_repeat( substr( $hex, 1, 1 ), 2 ) );
                    $b = hexdec( str_repeat( substr( $hex, 2, 1 ), 2 ) );
                    return 'background:rgba(' . $r . ',' . $g . ',' . $b . ',' . $alpha . ');';
                }
            }
            // Fallback
            return 'background:' . $color . ';';
        }
        return '';
    }
}

if ( ! function_exists( 'usp_rgb_collect_rule' ) ) {
    /**
     * Collect a CSS rule (deduplicated by key).
     */
    function usp_rgb_collect_rule( $key, $rule ) {
        static $store = array();
        if ( $rule ) {
            $store[ $key ] = $rule;
        }
        return $store;
    }
}

/**
 * Get all collected rules (for both frontend and admin)
 */
function usp_rgb_get_all_rules() {
    return usp_rgb_collect_rule( null, null );
}

/**
 * Inject collected CSS in <head> for frontend
 */
function usp_rgb_print_collected_css() {
    // Only output on frontend, not in admin
    if ( is_admin() ) {
        return;
    }
    
    // Force processing of all blocks first
    global $post;
    if ( $post && has_blocks( $post->post_content ) ) {
        do_blocks( $post->post_content );
    }
    
    $rules = usp_rgb_collect_rule( null, null );
    if ( empty( $rules ) ) {
        return;
    }
    echo "<style id='understrap-responsive-group-bg'>\n" . implode( "\n", $rules ) . "\n</style>\n";
}
add_action( 'wp_head', 'usp_rgb_print_collected_css', 30 );

/**
 * Inject collected CSS in editor
 */
function usp_rgb_print_editor_css() {
    // Only output in admin/editor
    if ( ! is_admin() ) {
        return;
    }
    
    $rules = usp_rgb_collect_rule( null, null );
    if ( empty( $rules ) ) {
        return;
    }
    // Add editor-specific prefix for better specificity
    $editor_rules = '';
    foreach ( $rules as $key => $rule ) {
        $editor_rules .= str_replace( '.' . $key, '.block-editor-block-list__layout .' . $key, $rule ) . "\n";
    }
    echo "<style id='understrap-responsive-group-bg-editor'>\n" . $editor_rules . "</style>\n";
}
add_action( 'admin_head', 'usp_rgb_print_editor_css', 25 );

/**
 * Process blocks and generate CSS for both frontend and editor
 */
function usp_rgb_render_group_backgrounds( $block_content, $block ) {
    if ( empty( $block['blockName'] ) || 'core/group' !== $block['blockName'] ) {
        return $block_content;
    }

    $attrs = isset( $block['attrs'] ) ? $block['attrs'] : array();

    // Required flag
    if ( empty( $attrs['enableResponsiveBg'] ) ) {
        return $block_content;
    }

    // Get URLs
    $desktop = isset( $attrs['desktopBgUrl'] ) ? esc_url_raw( $attrs['desktopBgUrl'] ) : '';
    $mobile  = isset( $attrs['mobileBgUrl'] ) ? esc_url_raw( $attrs['mobileBgUrl'] ) : '';
    
    if ( ! $desktop && ! $mobile ) {
        return $block_content;
    }

    $key = ! empty( $attrs['responsiveBgKey'] ) ? preg_replace( '/[^a-zA-Z0-9_-]/', '', $attrs['responsiveBgKey'] ) : '';
    if ( ! $key ) {
        // Fallback: hash of image URLs (stable).
        $key = 'rbg-' . substr( md5( $desktop . '|' . $mobile ), 0, 8 );
    }

    $class_to_add = ' has-responsive-group-bg ' . $key;

    // Add classes to block content (avoid duplicates)
    if ( preg_match( '/class="([^"]*)"/', $block_content, $matches ) ) {
        $existing_classes = $matches[1];
        
        // Check if classes already exist to avoid duplicates
        if ( strpos( $existing_classes, 'has-responsive-group-bg' ) === false && strpos( $existing_classes, $key ) === false ) {
            $new_classes = $existing_classes . $class_to_add;
            $block_content = str_replace( 'class="' . $existing_classes . '"', 'class="' . $new_classes . '"', $block_content );
        }
    } else {
        // If no class attribute exists, add one
        $block_content = preg_replace(
            '/(<div[^>]*wp-block-group[^>]*)(>)/',
            '$1 class="' . esc_attr( trim( $class_to_add ) ) . '"$2',
            $block_content,
            1
        );
    }

    // Background config
    $pos    = isset( $attrs['bgPosition'] ) ? sanitize_text_field( $attrs['bgPosition'] ) : 'center center';
    $size   = isset( $attrs['bgSize'] ) ? sanitize_text_field( $attrs['bgSize'] ) : 'cover';
    $rep    = isset( $attrs['bgRepeat'] ) ? sanitize_text_field( $attrs['bgRepeat'] ) : 'no-repeat';
    $attach = isset( $attrs['bgAttachment'] ) ? sanitize_text_field( $attrs['bgAttachment'] ) : 'scroll';

    // Desktop overlay settings
    $desktopOverlayEnabled = ! empty( $attrs['desktopOverlayEnabled'] );
    $desktopOverlayType    = isset( $attrs['desktopOverlayType'] ) ? sanitize_text_field( $attrs['desktopOverlayType'] ) : 'color';
    $desktopOverlayColor   = isset( $attrs['desktopOverlayColor'] ) ? sanitize_text_field( $attrs['desktopOverlayColor'] ) : '';
    $desktopOverlayGradient = isset( $attrs['desktopOverlayGradient'] ) ? sanitize_text_field( $attrs['desktopOverlayGradient'] ) : '';
    $desktopOverlayOpacity = isset( $attrs['desktopOverlayOpacity'] ) ? intval( $attrs['desktopOverlayOpacity'] ) : 40;
    
    // Mobile overlay settings
    $mobileOverlayEnabled = ! empty( $attrs['mobileOverlayEnabled'] );
    $mobileOverlayType    = isset( $attrs['mobileOverlayType'] ) ? sanitize_text_field( $attrs['mobileOverlayType'] ) : 'color';
    $mobileOverlayColor   = isset( $attrs['mobileOverlayColor'] ) ? sanitize_text_field( $attrs['mobileOverlayColor'] ) : '';
    $mobileOverlayGradient = isset( $attrs['mobileOverlayGradient'] ) ? sanitize_text_field( $attrs['mobileOverlayGradient'] ) : '';
    $mobileOverlayOpacity = isset( $attrs['mobileOverlayOpacity'] ) ? intval( $attrs['mobileOverlayOpacity'] ) : 40;
    
    // Sanitize opacity values
    $desktopOverlayOpacity = max( 0, min( 100, $desktopOverlayOpacity ) );
    $mobileOverlayOpacity = max( 0, min( 100, $mobileOverlayOpacity ) );
    $desktopOverlayAlpha = $desktopOverlayOpacity / 100;
    $mobileOverlayAlpha = $mobileOverlayOpacity / 100;

    // Build CSS (mobile first)
    $selector = '.' . $key;
    $mobile_img  = $mobile ? "url('" . $mobile . "')" : ( $desktop ? "url('" . $desktop . "')" : 'none' );
    $desktop_img = $desktop ? "url('" . $desktop . "')" : $mobile_img;

    $rule  = $selector . '{';
    $rule .= "background-image:{$mobile_img};";
    $rule .= "background-position:{$pos};";
    $rule .= "background-size:{$size};";
    $rule .= "background-repeat:{$rep};";
    $rule .= "background-attachment:{$attach};";
    $rule .= 'position:relative;';
    $rule .= 'min-height:300px;';
    $rule .= '}';

    // Desktop override (Bootstrap lg ≥ 992px)
    if ( $desktop && $desktop !== $mobile ) {
        $rule .= '@media (min-width:992px){' . $selector . '{background-image:' . $desktop_img . ';}}';
    }

    // Mobile overlay (base styles)
    if ( $mobileOverlayEnabled ) {
        $mobileOverlayCSS = usp_rgb_generate_overlay_css( $mobileOverlayType, $mobileOverlayColor, $mobileOverlayGradient, $mobileOverlayAlpha );
        if ( ! empty( $mobileOverlayCSS ) ) {
            $rule .= $selector . '::before{';
            $rule .= 'content:"";';
            $rule .= 'position:absolute;';
            $rule .= 'top:0;left:0;right:0;bottom:0;';
            $rule .= $mobileOverlayCSS;
            $rule .= 'pointer-events:none;';
            $rule .= 'z-index:1;';
            $rule .= '}';
        }
    }

    // Desktop overlay (override at 992px+)
    if ( $desktopOverlayEnabled ) {
        $desktopOverlayCSS = usp_rgb_generate_overlay_css( $desktopOverlayType, $desktopOverlayColor, $desktopOverlayGradient, $desktopOverlayAlpha );
        if ( ! empty( $desktopOverlayCSS ) ) {
            $rule .= '@media (min-width:992px){' . $selector . '::before{' . $desktopOverlayCSS . '}}';
        }
    }

    // Z-index for content above overlay
    if ( $mobileOverlayEnabled || $desktopOverlayEnabled ) {
        $rule .= $selector . ' > *{position:relative;z-index:2;}';
    }

    usp_rgb_collect_rule( $key, $rule );

    return $block_content;
}
// Register for both frontend and admin
add_filter( 'render_block', 'usp_rgb_render_group_backgrounds', 20, 2 );

/**
 * Process blocks in admin/editor context too
 */
function usp_rgb_process_admin_blocks() {
    // The render_block filter is already registered globally above
    // This function can be used for additional admin-specific processing if needed
}
add_action( 'init', 'usp_rgb_process_admin_blocks' );

/**
 * Debug function - uncomment to debug
 */
function usp_rgb_debug_attrs( $block_content, $block ) {
    if ( empty( $block['blockName'] ) || 'core/group' !== $block['blockName'] ) {
        return $block_content;
    }
    
    $attrs = isset( $block['attrs'] ) ? $block['attrs'] : array();
    
    // Only debug if responsive bg is enabled
    if ( ! empty( $attrs['enableResponsiveBg'] ) ) {
        error_log( 'Responsive BG Debug: ' . print_r( $attrs, true ) );
        error_log( 'Desktop URL: ' . ( isset( $attrs['desktopBgUrl'] ) ? $attrs['desktopBgUrl'] : 'not set' ) );
        error_log( 'Mobile URL: ' . ( isset( $attrs['mobileBgUrl'] ) ? $attrs['mobileBgUrl'] : 'not set' ) );
        error_log( 'Desktop Overlay: ' . ( isset( $attrs['desktopOverlayEnabled'] ) ? ( $attrs['desktopOverlayEnabled'] ? 'enabled' : 'disabled' ) : 'not set' ) );
        error_log( 'Mobile Overlay: ' . ( isset( $attrs['mobileOverlayEnabled'] ) ? ( $attrs['mobileOverlayEnabled'] ? 'enabled' : 'disabled' ) : 'not set' ) );
        error_log( 'Key: ' . ( isset( $attrs['responsiveBgKey'] ) ? $attrs['responsiveBgKey'] : 'not set' ) );
        error_log( 'Block Content: ' . $block_content );
    }
    
    return $block_content;
}
// Uncomment for debugging:
// add_filter( 'render_block', 'usp_rgb_debug_attrs', 10, 2 );





// Enqueue existing editor scripts
add_action('enqueue_block_editor_assets', function () {
   
    // Enqueue responsive-bg-all-blocks.js
    $responsive_bg_file = get_template_directory() . '/assets/js/responsive-bg/responsive-bg-all-blocks.js';
    if (file_exists($responsive_bg_file)) {
        wp_enqueue_script(
            'understrap-parent-responsive-bg',
            get_template_directory_uri() . '/assets/js/responsive-bg/responsive-bg-all-blocks.js',
            [ 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-components', 'wp-element', 'wp-compose', 'wp-block-editor', 'wp-hooks' ],
            filemtime($responsive_bg_file),
            true
        );
    }
});