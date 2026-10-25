<?php
defined('ABSPATH') || exit;

add_action('init', function () {
    // Register Team CPT
    register_post_type('team', [
        'labels' => [
            'name' => 'Team Members',
            'singular_name' => 'Team Member',
            'add_new' => 'Add New Team Member',
            'add_new_item' => 'Add New Team Member',
            'edit_item' => 'Edit Team Member',
        ],
        'public' => true,
        'has_archive' => false,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-groups',
        'supports' => array('title', 'editor', 'thumbnail'),
        'rewrite' => array('slug' => 'team'),
    ]);

    // Register Team Member Details Block (used INSIDE Team CPT)
    wp_register_script(
        'understrap-team-details-block',
        get_template_directory_uri() . '/assets/js/team-block/team-details.js',
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data'],
        filemtime(get_template_directory() . '/assets/js/team-block/team-details.js')
    );

    register_block_type('understrap/team-details', [
        'editor_script'   => 'understrap-team-details-block',
        'render_callback' => null,
        'supports' => [
            'html' => false,
        ],
        'attributes' => [
            'jobTitle' => ['type' => 'string', 'default' => 'DDS'],
            'fullBio' => ['type' => 'string', 'default' => ''],
            'videoShortcode' => ['type' => 'string', 'default' => ''],
            'desktopImageId' => ['type' => 'number', 'default' => 0],
            'desktopImageUrl' => ['type' => 'string', 'default' => ''],
            'mobileImageId' => ['type' => 'number', 'default' => 0],
            'mobileImageUrl' => ['type' => 'string', 'default' => ''],
            'excerptLength' => ['type' => 'number', 'default' => 50],
        ],
    ]);

    // Register Team Grid Block (used on Team PAGE to display all members)
    wp_register_script(
        'understrap-team-grid-block',
        get_template_directory_uri() . '/assets/js/team-block/team-grid.js',
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data'],
        filemtime(get_template_directory() . '/assets/js/team-block/team-grid.js')
    );

    register_block_type('understrap/team-grid', [
        'editor_script'   => 'understrap-team-grid-block',
        'render_callback' => 'render_team_grid',
        'supports' => [
            'html' => false,
        ],
        'attributes' => [
            'bgColor' => ['type' => 'string', 'default' => ''],
            'textColor' => ['type' => 'string', 'default' => '#000000'],
        ],
    ]);
});

// Helper function to generate excerpt from HTML content
function generate_team_excerpt($html, $word_count = 50) {
    // Strip all HTML tags
    $text = wp_strip_all_tags($html);
    
    // Remove extra whitespace
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    // Split into words
    $words = explode(' ', $text);
    
    // If word count is less than or equal to limit, return as is
    if (count($words) <= $word_count) {
        return $text;
    }
    
    // Get only the specified number of words and add ellipsis
    $excerpt = implode(' ', array_slice($words, 0, $word_count));
    return $excerpt . '...';
}

// Render Team Grid
function render_team_grid($attr) {
    $bg = esc_attr($attr['bgColor'] ?? '');
    $textColor = esc_attr($attr['textColor'] ?? '#000000');
    
    // Query all team members
    $team_query = new WP_Query([
        'post_type' => 'team',
        'posts_per_page' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ]);
    
    if (!$team_query->have_posts()) {
        return '<div style="padding:20px; text-align:center; background:#f0f0f0; border:2px dashed #ccc;">
            <p>No team members found. Please add team members first.</p>
        </div>';
    }
    
    ob_start();
?>
<div class="team-section" style="background-color:<?php echo $bg; ?>; color:<?php echo $textColor; ?>;">
    <div class="container py-5">
        <div class="row">
            <?php while ($team_query->have_posts()): $team_query->the_post(); 
                $post_id = get_the_ID();
                $name = get_the_title();
                
                // Parse block content
                $content = get_the_content();
                $blocks = parse_blocks($content);
                
                $jobTitle = 'DDS';
                $fullBio = '';
                $videoShortcode = '';
                $desktopImageId = 0;
                $desktopImageUrl = '';
                $mobileImageId = 0;
                $mobileImageUrl = '';
                $excerptLength = 50;
                
                foreach ($blocks as $block) {
                    if ($block['blockName'] === 'understrap/team-details') {
                        $jobTitle = $block['attrs']['jobTitle'] ?? 'DDS';
                        $fullBio = $block['attrs']['fullBio'] ?? '';
                        $videoShortcode = $block['attrs']['videoShortcode'] ?? '';
                        $desktopImageId = $block['attrs']['desktopImageId'] ?? 0;
                        $desktopImageUrl = $block['attrs']['desktopImageUrl'] ?? '';
                        $mobileImageId = $block['attrs']['mobileImageId'] ?? 0;
                        $mobileImageUrl = $block['attrs']['mobileImageUrl'] ?? '';
                        $excerptLength = $block['attrs']['excerptLength'] ?? 50;
                        break;
                    }
                }
                
                // Generate excerpt from full bio
                $shortBio = generate_team_excerpt($fullBio, $excerptLength);
                
                // Get desktop image
                $desktop_image = '';
                if ($desktopImageId && $desktopImageUrl) {
                    $desktop_image = $desktopImageUrl;
                } elseif ($desktopImageId) {
                    $desktop_image = wp_get_attachment_image_url($desktopImageId, 'large');
                }
                
                // Determine mobile image - use mobile image if set, otherwise use desktop image
                $mobile_image = '';
                if ($mobileImageId && $mobileImageUrl) {
                    $mobile_image = $mobileImageUrl;
                } elseif ($mobileImageId) {
                    $mobile_image = wp_get_attachment_image_url($mobileImageId, 'medium');
                }
                
                // Fallback to desktop image if no mobile image
                if (empty($mobile_image)) {
                    $mobile_image = $desktop_image;
                }
                
                $modal_id = 'teamModal-' . $post_id;
            ?>
                <div class="col-lg-6 col-md-6 col-12 mb-4">
                    <div class="team-member-card h-100 rounded-4 overflow-hidden box-shadow bg-white">
                        <div class="row g-0 h-100">
                            <div class="col-md-5">
                                <?php if ($desktop_image): ?>
                                    <img src="<?php echo esc_url($desktop_image); ?>" 
                                         alt="<?php echo esc_attr($name); ?>" 
                                         class="team-member-image w-100 h-100"
                                         style="object-fit: cover; min-height: 300px;">
                                <?php else: ?>
                                    <div class="team-member-image h-100 bg-secondary d-flex align-items-center justify-content-center" style="min-height: 300px;">
                                        <i class="dashicons dashicons-admin-users" style="font-size: 64px; color: #999;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-7 d-flex flex-column">
                                <div class="p-4 flex-grow-1">
                                    <h3 class="team-member-name text-primary mb-2" style="font-size: 24px; font-weight: 700;"><?php echo esc_html($name); ?></h3>
                                    <p class="team-member-title fw-bold mb-3" style="font-size: 18px;"><?php echo esc_html($jobTitle); ?></p>
                                    <div class="team-member-excerpt" style="color: #666; font-size: 15px; line-height: 1.6;">
                                        <?php echo esc_html($shortBio); ?>
                                    </div>
                                </div>
                                <div class="p-4 pt-0">
                                    <button type="button" 
                                            class="btn-read-more text-primary fw-bold text-uppercase" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#<?php echo $modal_id; ?>"
                                            style="background: none; border: none; padding: 0; cursor: pointer; font-size: 14px; letter-spacing: 0.5px;">
                                        READ MORE <i class="icon-angle-right1 ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bootstrap Modal -->
                <div class="modal fade team-modal" id="<?php echo $modal_id; ?>" tabindex="-1" aria-labelledby="<?php echo $modal_id; ?>Label" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content position-relative">
                            
                            <!-- Custom Close Button -->
                            <button type="button" class="btn-close btn-close-white custom-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
                            
                            <!-- Scrollable Modal Body -->
                            <div class="modal-body py_32 px_24 px_sm_16 team-popup-detail">
                                <div class="moretext-section mx-auto">
                                    <div class="row justify-content-center">
                                        
                                        <!-- Header with Image and Name -->
                                        <div class="d-flex align-items-start mb-lg-2 mb-3">
                                            <?php if ($mobile_image): ?>
                                                <img src="<?php echo esc_url($mobile_image); ?>" 
                                                     class="img-fluid" 
                                                     alt="<?php echo esc_attr($name); ?>" 
                                                     width="90" 
                                                     height="102">
                                            <?php endif; ?>
                                            <div class="media-body ps-3">
                                                <span class="h2 d-none d-lg-block color_secondary"><?php echo esc_html($name); ?></span>
                                                <span class="h3 d-block d-lg-none color_secondary"><?php echo esc_html($name); ?></span>
                                                <span class="h4 d-block"><?php echo esc_html($jobTitle); ?></span>
                                            </div>
                                        </div>
                                        
                                        <!-- Video Section -->
                                        <?php if ($videoShortcode): ?>
                                            <div class="col-lg-12 position-relative video-play-main mb-4">
                                                <div class="video-wrapper">
                                                    <?php echo do_shortcode($videoShortcode); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Full Biography -->
                                        <div class="col-lg-12">
                                            <div class="d-block mb-4 color_black team-bio-full" style="color: #333; font-size: 15px; line-height: 1.8;">
                                                <?php echo wp_kses_post($fullBio); ?>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div><!-- /.modal-body -->
                            
                            <!-- Sticky Footer with Read Less Button -->
                            <div class="modal-footer sticky-footer">
                                <a href="javascript:void(0)" 
                                   data-bs-dismiss="modal" 
                                   aria-label="Close" 
                                   class="text_16 text-end w-100 d-inline-block fw-bold alt_font line_height_normal text-uppercase color_secondary color_secondary_hover text-decoration-none">
                                    <span class="hover-underline">Read less</span>
                                    <i class="icon-minus1 text_14 ms-2"></i>
                                </a>
                            </div>
                            
                        </div>
                    </div>
                </div>

            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</div>

<style>
/* Team Card Styles */
.team-member-card {
    border: 2px solid #e0e0e0;
    transition: all 0.3s ease;
}

.team-member-card:hover {
    border-color: #0066a1;
    box-shadow: 0 4px 12px rgba(0, 102, 161, 0.15) !important;
}

.btn-read-more:hover {
    opacity: 0.7;
}

/* Modal Styles */
.team-modal .modal-content {
    border: none;
    border-radius: 8px;
}

/* Custom Close Button */
.team-modal .custom-close-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 1050;
    opacity: 1;
}

.team-modal .custom-close-btn:hover {
    opacity: 0.7;
}

/* Modal Body Padding */
.team-modal .py_32 {
    padding-top: 32px;
    padding-bottom: 32px;
}

.team-modal .px_24 {
    padding-left: 24px;
    padding-right: 24px;
}

/* Video Wrapper */
.video-wrapper iframe,
.video-wrapper video {
    width: 100%;
    height: auto;
    aspect-ratio: 16/9;
    border-radius: 8px;
}

/* Sticky Footer */
.team-modal .modal-footer.sticky-footer {
    position: sticky;
    bottom: 0;
    background: white;
    z-index: 1030;
    border-top: 1px solid #e0e0e0;
    padding: 16px 24px;
}

.team-modal .modal-footer.sticky-footer a {
    transition: opacity 0.3s ease;
}

.team-modal .modal-footer.sticky-footer a:hover {
    opacity: 0.7;
}

.team-modal .modal-footer.sticky-footer .hover-underline:hover {
    text-decoration: underline !important;
}

/* Text Utilities */
.text_16 {
    font-size: 16px;
}

.text_14 {
    font-size: 14px;
}

.line_height_normal {
    line-height: normal;
}

.color_secondary {
    color: #0066a1;
}

.color_secondary_hover:hover {
    color: #004d7a;
}

.color_black {
    color: #000;
}

/* Mobile Responsive */
@media (max-width: 767px) {
    .team-member-image {
        min-height: 250px !important;
    }
    
    .team-modal .px_sm_16 {
        padding-left: 16px !important;
        padding-right: 16px !important;
    }
    
    .team-modal .custom-close-btn {
        top: 15px;
        right: 15px;
    }
    
    .team-modal .modal-footer.sticky-footer {
        padding: 12px 16px;
    }
    
    .team-modal .modal-footer.sticky-footer .text_16 {
        font-size: 14px;
    }
}

/* Modal Scrollable Content */
.team-modal .modal-dialog-scrollable .modal-body {
    overflow-y: auto;
}

/* Hide Default Bootstrap Close Button */
.team-modal .modal-header .btn-close:not(.custom-close-btn) {
    display: none;
}
</style>
<?php
    return ob_get_clean();
}