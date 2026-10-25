<?php
function testimonial_register_testimonial_type() {
    $labels = array(
        'name'                  => __( 'Testimonials', 'testimonial-domain' ),
        'singular_name'         => __( 'Testimonial', 'testimonial-domain' ),
        'menu_name'             => __( 'Testimonials', 'testimonial-domain' ),
        'name_admin_bar'        => __( 'Testimonial', 'testimonial-domain' ),
        'add_new'               => __( 'Add New', 'testimonial-domain' ),
        'add_new_item'          => __( 'Add New Testimonial', 'testimonial-domain' ),
        'new_item'              => __( 'New Testimonial', 'testimonial-domain' ),
        'edit_item'             => __( 'Edit Testimonial', 'testimonial-domain' ),
        'view_item'             => __( 'View Testimonial', 'testimonial-domain' ),
        'all_items'             => __( 'All Testimonials', 'testimonial-domain' ),
        'search_items'          => __( 'Search Testimonials', 'testimonial-domain' ),
        'not_found'             => __( 'No testimonials found.', 'testimonial-domain' ),
        'not_found_in_trash'    => __( 'No testimonials found in Trash.', 'testimonial-domain' ),
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'has_archive'           => 'about-us/reviews',
        'rewrite'               => array( 'slug' => 'about-us/reviews', 'with_front' => false ),
        'menu_icon'             => 'dashicons-format-quote',
        'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'show_in_rest'          => true,
        'show_admin_column'     => true,
        'publicly_queryable'    => true,
        'menu_position'         => 20,
    );

    register_post_type( 'testimonials', $args );

    // Register taxonomy for testimonials
    register_taxonomy(
        'testimonial_category',
        'testimonials',
        array(
            'labels' => array(
                'name'              => __( 'Testimonial Categories', 'testimonial-domain' ),
                'singular_name'     => __( 'Testimonial Category', 'testimonial-domain' ),
            ),
            'show_admin_column' => true,
            'hierarchical'      => true,
            'show_in_rest'      => true,
            'rewrite'           => array(
                'slug'       => 'testimonial-category',
                'with_front' => false,
            ),
        )
    );
}
add_action( 'init', 'testimonial_register_testimonial_type' );

