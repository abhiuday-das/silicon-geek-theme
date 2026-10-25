<?php
/**
 * ECE Custom Post Types + Taxonomies (CircuitShala).
 *
 * NEW FILE: wp-content/themes/ts-block-theme/inc/custom-post-type/ece-cpt.php
 * Loaded by functions.php (line added right after register-cpt.php).
 * Does NOT touch your existing register-cpt.php (testimonials etc. keep working).
 *
 * Creates:
 *  - ECE Notes    => /notes/mosfet-working/
 *  - ECE Projects => /projects/esp32-dht22-iot/
 *  - GATE PYQ     => /gate-pyq/signals-systems-pyq/
 *  Taxonomies (auto archive pages):
 *  - Study Year   => /year/year-1/ ... /year/final-year/
 *  - Subject      => /subjects/analog-circuits/ etc.
 *  - Topics       => /topics/op-amp/ (tags)
 *  - Difficulty   => /difficulty/easy/ (projects only)
 *
 * AFTER ACTIVATING: WP Admin -> Settings -> Permalinks -> Save Changes (once).
 * Starter terms are created automatically on first admin load.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ts_cs_register_ece_content', 9 );

function ts_cs_register_ece_content() {
	$shared = array(
		'public'       => true,
		'show_in_rest' => true, // Required for block editor + Site Editor templates.
		'has_archive'  => true,
		'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author', 'comments' ),
	);

	register_post_type( 'ece_note', array_merge( $shared, array(
		'labels' => array(
			'name'          => __( 'ECE Notes', 'ts-block-theme' ),
			'singular_name' => __( 'Note', 'ts-block-theme' ),
			'add_new_item'  => __( 'Add New Note', 'ts-block-theme' ),
			'menu_name'     => __( 'ECE Notes', 'ts-block-theme' ),
		),
		'menu_icon'    => 'dashicons-welcome-learn-more',
		'menu_position'=> 5,
		'rewrite'      => array( 'slug' => 'notes', 'with_front' => false ),
	) ) );

	register_post_type( 'ece_project', array_merge( $shared, array(
		'labels' => array(
			'name'          => __( 'ECE Projects', 'ts-block-theme' ),
			'singular_name' => __( 'Project', 'ts-block-theme' ),
			'add_new_item'  => __( 'Add New Project', 'ts-block-theme' ),
			'menu_name'     => __( 'ECE Projects', 'ts-block-theme' ),
		),
		'menu_icon'    => 'dashicons-hammer',
		'menu_position'=> 6,
		'rewrite'      => array( 'slug' => 'projects', 'with_front' => false ),
	) ) );

	register_post_type( 'ece_gate_pyq', array_merge( $shared, array(
		'labels' => array(
			'name'          => __( 'GATE PYQ', 'ts-block-theme' ),
			'singular_name' => __( 'GATE PYQ Set', 'ts-block-theme' ),
			'add_new_item'  => __( 'Add PYQ Set', 'ts-block-theme' ),
			'menu_name'     => __( 'GATE PYQ', 'ts-block-theme' ),
		),
		'menu_icon'    => 'dashicons-awards',
		'menu_position'=> 7,
		'rewrite'      => array( 'slug' => 'gate-pyq', 'with_front' => false ),
	) ) );

	$tax_shared = array( 'hierarchical' => true, 'show_in_rest' => true, 'public' => true, 'show_admin_column' => true );
	$all_types  = array( 'ece_note', 'ece_project', 'ece_gate_pyq', 'post' );

	register_taxonomy( 'ece_year', $all_types, array_merge( $tax_shared, array(
		'labels'  => array( 'name' => __( 'Study Year', 'ts-block-theme' ), 'singular_name' => __( 'Year', 'ts-block-theme' ) ),
		'rewrite' => array( 'slug' => 'year', 'with_front' => false ),
	) ) );

	register_taxonomy( 'ece_subject', $all_types, array_merge( $tax_shared, array(
		'labels'  => array( 'name' => __( 'Subject', 'ts-block-theme' ), 'singular_name' => __( 'Subject', 'ts-block-theme' ) ),
		'rewrite' => array( 'slug' => 'subjects', 'with_front' => false ),
	) ) );

	register_taxonomy( 'ece_topic', $all_types, array_merge( $tax_shared, array(
		'hierarchical' => false, // Topics behave like tags: mosfet, op-amp, esp32, verilog...
		'labels'       => array( 'name' => __( 'Topics', 'ts-block-theme' ), 'singular_name' => __( 'Topic', 'ts-block-theme' ) ),
		'rewrite'      => array( 'slug' => 'topics', 'with_front' => false ),
	) ) );

	register_taxonomy( 'ece_difficulty', array( 'ece_project' ), array_merge( $tax_shared, array(
		'labels'  => array( 'name' => __( 'Difficulty', 'ts-block-theme' ), 'singular_name' => __( 'Difficulty', 'ts-block-theme' ) ),
		'rewrite' => array( 'slug' => 'difficulty', 'with_front' => false ),
	) ) );
}

// One-time: create starter Year / Subject / Difficulty terms.
add_action( 'admin_init', 'ts_cs_seed_ece_terms' );

function ts_cs_seed_ece_terms() {
	if ( get_option( 'ts_cs_ece_terms_v1' ) ) {
		return;
	}
	$terms = array(
		'ece_year' => array(
			array( 'Year 1', 'year-1' ), array( 'Year 2', 'year-2' ),
			array( 'Year 3', 'year-3' ), array( 'Final Year', 'final-year' ),
		),
		'ece_subject' => array(
			array( 'Analog Circuits', 'analog-circuits' ),
			array( 'Digital Electronics', 'digital-electronics' ),
			array( 'Signals & Systems', 'signals-systems' ),
			array( 'Communication', 'communication' ),
			array( 'EMFT', 'emft' ),
			array( 'Control Systems', 'control-systems' ),
			array( 'Network Theory', 'network-theory' ),
			array( 'EDC', 'edc' ),
			array( 'Microprocessors', 'microprocessors' ),
			array( 'DSP', 'dsp' ),
			array( 'VLSI Design', 'vlsi-design' ),
			array( 'Embedded & IoT', 'embedded-iot' ),
		),
		'ece_difficulty' => array(
			array( 'Easy', 'easy' ), array( 'Medium', 'medium' ), array( 'Hard', 'hard' ),
		),
	);
	foreach ( $terms as $taxonomy => $list ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}
		foreach ( $list as list( $name, $slug ) ) {
			if ( ! term_exists( $slug, $taxonomy ) ) {
				wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
			}
		}
	}
	update_option( 'ts_cs_ece_terms_v1', 1 );
}
