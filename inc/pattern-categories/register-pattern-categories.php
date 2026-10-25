<?php
// Register pattern categories
add_action('init', function () {
    register_block_pattern_category('ts-block-theme-subpage-banner', [
        'label' => __('Subpage Banner Patterns', 'ts-block-theme'),
    ]);

    register_block_pattern_category('ts-block-theme-about-us', [
        'label' => __('About Us Patterns', 'ts-block-theme'),
    ]);




    register_block_pattern_category('ts-block-theme-contact-us', [
        'label' => __('Contact Us Patterns', 'ts-block-theme'),
    ]);
    
    register_block_pattern_category('ts-block-theme-content-and-image', [
        'label' => __('Content And Image Patterns', 'ts-block-theme'),
    ]);

    register_block_pattern_category('ts-block-theme-sidebar-form', [
        'label' => __('Sidebar Form Patterns', 'ts-block-theme'),
    ]);


    register_block_pattern_category('ts-block-theme-thank-you', [
        'label' => __('Thank You Patterns', 'ts-block-theme'),
    ]);


    register_block_pattern_category('ts-block-theme/ece-hero', [
        'label' => __('ECE Hero', 'ts-block-theme'),
    ]);

    register_block_pattern_category('ts-block-theme/ece-projects-gate-cta', [
        'label' => __('ECE Projects Gate CTA', 'ts-block-theme'),
    ]);

    register_block_pattern_category('ts-block-theme/ece-sections', [
        'label' => __('ECE Sections', 'ts-block-theme'),
    ]);
    register_block_pattern_category('ts-block-theme/ece-shop-training-faq', [
        'label' => __('ECE Shop Training FAQ', 'ts-block-theme'),
    ]);
    register_block_pattern_category('ts-block-theme/ece-topic-content', [
        'label' => __('ECE Topic Content', 'ts-block-theme'),
    ]);

     register_block_pattern_category('ts-block-theme/inner-focus-starter', [
        'label' => __('Inner focus starter', 'ts-block-theme'),
    ]);

     register_block_pattern_category('ts-block-theme/inner-notes-starter', [
        'label' => __('Inner notes starter', 'ts-block-theme'),
    ]);

     register_block_pattern_category('ts-block-theme/inner-project-starter', [
        'label' => __('inner project starter', 'ts-block-theme'),
    ]);
});

