<?php
/**
 * Silicon Geek — Course Structure (Step 1).
 *
 * NEW FILE:
 * wp-content/themes/ts-block-theme/inc/custom-post-type/ece-course-structure.php
 *
 * Load it from functions.php immediately after ece-cpt.php:
 * require_once get_template_directory() . '/inc/custom-post-type/ece-course-structure.php';
 *
 * What this file does:
 *  - Adds one hierarchical "Course Structure" taxonomy (ece_course).
 *  - The tree can be: Track → Year → Subject → Unit → Topic.
 *  - An ECE Note is the subtopic/lesson. Assign each note only to the
 *    deepest Topic term; WordPress automatically includes it in every
 *    parent archive.
 *  - Adds an Order field to each course folder and enables the built-in
 *    Order field for Notes/PYQ sets.
 *  - Creates a small starter tree one time so the system can be tested.
 *
 * It intentionally does NOT remove the older Study Year / Subject /
 * Topics taxonomies created in ece-cpt.php. They remain untouched for
 * old content and future filters; new course lessons should use the
 * Course Structure taxonomy.
 *
 * After installing: WP Admin → Settings → Permalinks → Save Changes once.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'TS_CS_COURSE_TAX' ) ) {
	define( 'TS_CS_COURSE_TAX', 'ece_course' );
}

if ( ! defined( 'TS_CS_COURSE_ORDER_META' ) ) {
	define( 'TS_CS_COURSE_ORDER_META', 'ts_cs_course_order' );
}

/**
 * Register the Course Structure taxonomy and its ordering meta.
 */
add_action( 'init', 'ts_cs_register_course_structure', 11 );

function ts_cs_register_course_structure() {
	register_taxonomy(
		TS_CS_COURSE_TAX,
		array( 'ece_note', 'ece_gate_pyq' ),
		array(
			'labels'            => array(
				'name'                       => _x( 'Course Structure', 'taxonomy general name', 'ts-block-theme' ),
				'singular_name'              => _x( 'Course Folder', 'taxonomy singular name', 'ts-block-theme' ),
				'search_items'               => __( 'Search Course Folders', 'ts-block-theme' ),
				'popular_items'              => __( 'Popular Course Folders', 'ts-block-theme' ),
				'all_items'                  => __( 'All Course Folders', 'ts-block-theme' ),
				'parent_item'                => __( 'Parent Course Folder', 'ts-block-theme' ),
				'parent_item_colon'          => __( 'Parent Course Folder:', 'ts-block-theme' ),
				'edit_item'                  => __( 'Edit Course Folder', 'ts-block-theme' ),
				'view_item'                  => __( 'View Course Folder', 'ts-block-theme' ),
				'update_item'                => __( 'Update Course Folder', 'ts-block-theme' ),
				'add_new_item'               => __( 'Add New Course Folder', 'ts-block-theme' ),
				'new_item_name'              => __( 'New Course Folder Name', 'ts-block-theme' ),
				'separate_items_with_commas' => __( 'Separate course folders with commas', 'ts-block-theme' ),
				'add_or_remove_items'        => __( 'Add or remove course folders', 'ts-block-theme' ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'ts-block-theme' ),
				'not_found'                  => __( 'No course folders found.', 'ts-block-theme' ),
				'no_terms'                   => __( 'No course folders', 'ts-block-theme' ),
				'menu_name'                  => __( 'Course Structure', 'ts-block-theme' ),
				'items_list_navigation'      => __( 'Course folders list navigation', 'ts-block-theme' ),
				'items_list'                 => __( 'Course folders list', 'ts-block-theme' ),
				'back_to_items'              => __( '&larr; Back to Course Folders', 'ts-block-theme' ),
			),
			'public'            => true,
			'publicly_queryable' => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_nav_menus' => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
			'query_var'         => true,
			'rewrite'           => array(
				'slug'         => 'learn',
				'with_front'   => false,
				'hierarchical' => true,
			),
		)
	);

	register_term_meta(
		TS_CS_COURSE_TAX,
		TS_CS_COURSE_ORDER_META,
		array(
			'type'              => 'integer',
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
		)
	);

	/*
	 * WordPress already stores a numeric menu_order on every post.
	 * Enabling page-attributes exposes it so lessons can be ordered
	 * as 10, 20, 30... inside a Topic.
	 */
	add_post_type_support( 'ece_note', 'page-attributes' );
	add_post_type_support( 'ece_gate_pyq', 'page-attributes' );
}

/**
 * Return the configured order for a course term.
 */
function ts_cs_course_get_order( $term_id ) {
	return absint( get_term_meta( (int) $term_id, TS_CS_COURSE_ORDER_META, true ) );
}

/**
 * Add-form Order field.
 */
add_action( 'ece_course_add_form_fields', 'ts_cs_course_add_order_field' );

function ts_cs_course_add_order_field() {
	wp_nonce_field( 'ts_cs_course_order', 'ts_cs_course_order_nonce' );
	?>
	<div class="form-field term-ts-cs-course-order-wrap">
		<label for="ts-cs-course-order"><?php esc_html_e( 'Display Order', 'ts-block-theme' ); ?></label>
		<input
			name="ts_cs_course_order"
			id="ts-cs-course-order"
			type="number"
			min="0"
			max="9999"
			step="1"
			value="0"
		/>
		<p><?php esc_html_e( 'Lower numbers appear first. Use 10, 20, 30 so a new folder can be inserted later.', 'ts-block-theme' ); ?></p>
	</div>
	<?php
}

/**
 * Edit-form Order field.
 */
add_action( 'ece_course_edit_form_fields', 'ts_cs_course_edit_order_field', 10, 2 );

function ts_cs_course_edit_order_field( $term, $taxonomy ) {
	$value = ts_cs_course_get_order( $term->term_id );
	?>
	<tr class="form-field term-ts-cs-course-order-wrap">
		<th scope="row">
			<label for="ts-cs-course-order"><?php esc_html_e( 'Display Order', 'ts-block-theme' ); ?></label>
		</th>
		<td>
			<?php wp_nonce_field( 'ts_cs_course_order', 'ts_cs_course_order_nonce' ); ?>
			<input
				name="ts_cs_course_order"
				id="ts-cs-course-order"
				type="number"
				min="0"
				max="9999"
				step="1"
				value="<?php echo esc_attr( $value ); ?>"
			/>
			<p class="description">
				<?php esc_html_e( 'Lower numbers appear first. Use 10, 20, 30 so a new folder can be inserted later.', 'ts-block-theme' ); ?>
			</p>
		</td>
	</tr>
	<?php
}

/**
 * Save the Order field when a course term is created or edited.
 */
add_action( 'created_ece_course', 'ts_cs_course_save_order', 10, 2 );
add_action( 'edited_ece_course', 'ts_cs_course_save_order', 10, 2 );

function ts_cs_course_save_order( $term_id, $term_taxonomy_id = 0 ) {
	if ( ! isset( $_POST['ts_cs_course_order_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ts_cs_course_order_nonce'] ) ), 'ts_cs_course_order' ) ) {
		return;
	}

	$taxonomy = get_taxonomy( TS_CS_COURSE_TAX );
	if ( ! $taxonomy || ! current_user_can( $taxonomy->cap->edit_terms ) ) {
		return;
	}

	if ( isset( $_POST['ts_cs_course_order'] ) ) {
		update_term_meta(
			(int) $term_id,
			TS_CS_COURSE_ORDER_META,
			absint( wp_unslash( $_POST['ts_cs_course_order'] ) )
		);
	}
}

/**
 * Show the Order value in the Course Structure admin list.
 */
add_filter( 'manage_edit-ece_course_columns', 'ts_cs_course_add_order_column' );

function ts_cs_course_add_order_column( $columns ) {
	$new_columns = array();

	foreach ( $columns as $key => $label ) {
		$new_columns[ $key ] = $label;

		if ( 'name' === $key ) {
			$new_columns['ts_cs_course_order'] = __( 'Order', 'ts-block-theme' );
		}
	}

	return $new_columns;
}

add_action( 'manage_ece_course_custom_column', 'ts_cs_course_render_order_column', 10, 3 );

function ts_cs_course_render_order_column( $content, $column_name, $term_id ) {
	if ( 'ts_cs_course_order' === $column_name ) {
		return (string) ts_cs_course_get_order( $term_id );
	}

	return $content;
}

/**
 * Show each Note's built-in menu_order in the ECE Notes list.
 * The value itself can be edited from the post's Page Attributes /
 * Quick Edit controls after page-attributes support is enabled above.
 */
add_filter( 'manage_ece_note_posts_columns', 'ts_cs_course_add_note_order_column' );

function ts_cs_course_add_note_order_column( $columns ) {
	$new_columns = array();

	foreach ( $columns as $key => $label ) {
		$new_columns[ $key ] = $label;

		if ( 'title' === $key ) {
			$new_columns['ts_cs_lesson_order'] = __( 'Lesson Order', 'ts-block-theme' );
		}
	}

	return $new_columns;
}

add_action( 'manage_ece_note_posts_custom_column', 'ts_cs_course_render_note_order_column', 10, 2 );

function ts_cs_course_render_note_order_column( $column_name, $post_id ) {
	if ( 'ts_cs_lesson_order' === $column_name ) {
		$post = get_post( $post_id );
		echo $post ? esc_html( (string) absint( $post->menu_order ) ) : '0';
	}
}

/**
 * Sort frontend Course Structure archives by Lesson Order.
 *
 * WordPress normally displays the newest post first. On /learn/...
 * course pages we instead want the learning sequence to win:
 * menu_order 10, then 20, then 30. Date/title are only fallbacks
 * for posts sharing the same order value.
 */
add_action( 'pre_get_posts', 'ts_cs_course_sort_archive_query' );

function ts_cs_course_sort_archive_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_tax( TS_CS_COURSE_TAX ) ) {
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'date'       => 'ASC',
				'title'      => 'ASC',
			)
		);
	}
}

/**
 * Create one small starter tree so Step 1 can be tested immediately.
 */
add_action( 'admin_init', 'ts_cs_course_seed_starter_tree', 20 );

function ts_cs_course_seed_starter_tree() {
	if ( get_option( 'ts_cs_course_seed_v1' ) ) {
		return;
	}

	if ( ! taxonomy_exists( TS_CS_COURSE_TAX ) ) {
		return;
	}

	$tree = array(
		array(
			'name'        => 'BTech ECE',
			'slug'        => 'btech-ece',
			'order'       => 10,
			'description' => 'Year-wise and subject-wise BTech ECE notes.',
			'children'    => array(
				array(
					'name'        => 'Year 1',
					'slug'        => 'year-1',
					'order'       => 10,
					'description' => 'First-year subjects and notes.',
					'children'    => array(),
				),
				array(
					'name'        => 'Year 2',
					'slug'        => 'year-2',
					'order'       => 20,
					'description' => 'Second-year ECE subjects and notes.',
					'children'    => array(
						array(
							'name'        => 'Digital Electronics',
							'slug'        => 'digital-electronics',
							'order'       => 10,
							'description' => 'Logic gates, combinational and sequential circuits.',
							'children'    => array(
								array(
									'name'        => 'Unit 1: Number Systems and Boolean Algebra',
									'slug'        => 'unit-1-number-systems-boolean-algebra',
									'order'       => 10,
									'description' => 'Number systems, Boolean laws and logic gates.',
									'children'    => array(
										array(
											'name'        => 'Boolean Algebra Laws',
											'slug'        => 'boolean-algebra-laws',
											'order'       => 10,
											'description' => 'Basic laws and simplification rules of Boolean algebra.',
											'children'    => array(),
										),
										array(
											'name'        => 'Logic Gates',
											'slug'        => 'logic-gates',
											'order'       => 20,
											'description' => 'Basic and universal logic gates, symbols and truth tables.',
											'children'    => array(),
										),
									),
								),
								array(
									'name'        => 'Unit 2: Combinational Circuits',
									'slug'        => 'unit-2-combinational-circuits',
									'order'       => 20,
									'description' => 'Adders, multiplexers, decoders and encoders.',
									'children'    => array(
										array(
											'name'        => 'Multiplexers and Demultiplexers',
											'slug'        => 'multiplexers-demultiplexers',
											'order'       => 10,
											'description' => 'Working, truth tables and applications of MUX and DEMUX circuits.',
											'children'    => array(),
										),
									),
								),
							),
						),
						array(
							'name'        => 'Analog Circuits',
							'slug'        => 'analog-circuits',
							'order'       => 20,
							'description' => 'Amplifiers, op-amps and analog circuit analysis.',
							'children'    => array(),
						),
						array(
							'name'        => 'Signals & Systems',
							'slug'        => 'signals-systems',
							'order'       => 30,
							'description' => 'Continuous and discrete signals, systems and transforms.',
							'children'    => array(),
						),
					),
				),
				array(
					'name'        => 'Year 3',
					'slug'        => 'year-3',
					'order'       => 30,
					'description' => 'Third-year ECE subjects and notes.',
					'children'    => array(),
				),
				array(
					'name'        => 'Final Year',
					'slug'        => 'final-year',
					'order'       => 40,
					'description' => 'Final-year ECE subjects, electives and project resources.',
					'children'    => array(),
				),
			),
		),
		array(
			'name'        => 'GATE EC',
			'slug'        => 'gate-ec',
			'order'       => 20,
			'description' => 'GATE Electronics and Communication subjects, notes and PYQ practice.',
			'children'    => array(),
		),
		array(
			'name'        => 'Embedded Systems',
			'slug'        => 'embedded-systems',
			'order'       => 30,
			'description' => 'Microcontrollers, Embedded C, RTOS and IoT learning path.',
			'children'    => array(),
		),
		array(
			'name'        => 'VLSI Design',
			'slug'        => 'vlsi-design',
			'order'       => 40,
			'description' => 'Digital design, Verilog, CMOS and VLSI learning path.',
			'children'    => array(),
		),
	);

	ts_cs_course_seed_branch( $tree, 0 );

	update_option( 'ts_cs_course_seed_v1', 1 );
}

/**
 * Recursively insert one branch of the starter tree.
 */
function ts_cs_course_seed_branch( $nodes, $parent_id ) {
	foreach ( (array) $nodes as $node ) {
		if ( empty( $node['name'] ) || empty( $node['slug'] ) ) {
			continue;
		}

		$existing = get_term_by( 'slug', $node['slug'], TS_CS_COURSE_TAX );
		$term_id  = 0;

		if ( $existing && ! is_wp_error( $existing ) ) {
			$term_id = (int) $existing->term_id;
		} else {
			$inserted = wp_insert_term(
				$node['name'],
				TS_CS_COURSE_TAX,
				array(
					'slug'        => $node['slug'],
					'parent'      => (int) $parent_id,
					'description' => isset( $node['description'] ) ? $node['description'] : '',
				)
			);

			if ( ! is_wp_error( $inserted ) && isset( $inserted['term_id'] ) ) {
				$term_id = (int) $inserted['term_id'];
			}
		}

		if ( ! $term_id ) {
			continue;
		}

		update_term_meta(
			$term_id,
			TS_CS_COURSE_ORDER_META,
			isset( $node['order'] ) ? absint( $node['order'] ) : 0
		);

		if ( ! empty( $node['children'] ) ) {
			ts_cs_course_seed_branch( $node['children'], $term_id );
		}
	}
}

/**
 * Sort term objects by their custom Display Order, then by name.
 */
function ts_cs_course_sort_terms( $terms ) {
	if ( ! is_array( $terms ) ) {
		return array();
	}

	usort(
		$terms,
		function ( $a, $b ) {
			$order_a = ts_cs_course_get_order( $a->term_id );
			$order_b = ts_cs_course_get_order( $b->term_id );

			if ( $order_a === $order_b ) {
				return strnatcasecmp( $a->name, $b->name );
			}

			return ( $order_a < $order_b ) ? -1 : 1;
		}
	);

	return $terms;
}

/**
 * Get ordered direct children of a course folder.
 * Use parent ID 0 for the four learning tracks.
 */
function ts_cs_course_get_children( $parent_id = 0, $hide_empty = false ) {
	$terms = get_terms(
		array(
			'taxonomy'   => TS_CS_COURSE_TAX,
			'parent'     => (int) $parent_id,
			'hide_empty' => (bool) $hide_empty,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) ) {
		return array();
	}

	return ts_cs_course_sort_terms( $terms );
}

/**
 * Return a term path from the root track down to the given term.
 */
function ts_cs_course_get_path( $term ) {
	$current = is_object( $term ) ? $term : get_term( (int) $term, TS_CS_COURSE_TAX );
	$path    = array();
	$seen    = array();

	while ( $current && ! is_wp_error( $current ) ) {
		if ( isset( $seen[ $current->term_id ] ) ) {
			break;
		}

		$seen[ $current->term_id ] = true;
		array_unshift( $path, $current );

		if ( empty( $current->parent ) ) {
			break;
		}

		$current = get_term( (int) $current->parent, TS_CS_COURSE_TAX );
	}

	return $path;
}

/**
 * Return the hierarchy depth of a term. A root track is depth 0.
 */
function ts_cs_course_get_depth( $term ) {
	$path = ts_cs_course_get_path( $term );
	return max( 0, count( $path ) - 1 );
}

/**
 * Get the deepest Course Structure term assigned to a post.
 * Lessons should normally have only one assigned Topic term.
 */
function ts_cs_course_get_primary_term( $post_id ) {
	$terms = wp_get_post_terms( (int) $post_id, TS_CS_COURSE_TAX );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return false;
	}

	usort(
		$terms,
		function ( $a, $b ) {
			$depth_a = ts_cs_course_get_depth( $a );
			$depth_b = ts_cs_course_get_depth( $b );

			if ( $depth_a === $depth_b ) {
				$order_a = ts_cs_course_get_order( $a->term_id );
				$order_b = ts_cs_course_get_order( $b->term_id );

				if ( $order_a === $order_b ) {
					return strnatcasecmp( $a->name, $b->name );
				}

				return ( $order_a < $order_b ) ? -1 : 1;
			}

			return ( $depth_a > $depth_b ) ? -1 : 1;
		}
	);

	return $terms[0];
}

/**
 * Get published lessons in a course term, in menu_order sequence.
 */
function ts_cs_course_get_term_posts( $term_id, $post_type = 'ece_note', $include_children = false ) {
	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'ASC',
				'title'      => 'ASC',
			),
			'tax_query'      => array(
				array(
					'taxonomy'         => TS_CS_COURSE_TAX,
					'field'            => 'term_id',
					'terms'            => array( (int) $term_id ),
					'include_children' => (bool) $include_children,
				),
			),
		)
	);

	return is_array( $posts ) ? $posts : array();
}

/**
 * Return the previous and next published lessons inside the same Topic.
 * This helper will power the GFG-style Previous / Next buttons in Step 3.
 */
function ts_cs_course_get_adjacent_posts( $post_id ) {
	$result = array(
		'previous' => false,
		'next'     => false,
	);

	$post = get_post( (int) $post_id );
	if ( ! $post ) {
		return $result;
	}

	$primary_term = ts_cs_course_get_primary_term( $post_id );
	if ( ! $primary_term ) {
		return $result;
	}

	$posts   = ts_cs_course_get_term_posts( $primary_term->term_id, $post->post_type, false );
	$post_ids = wp_list_pluck( $posts, 'ID' );
	$index    = array_search( (int) $post_id, array_map( 'intval', $post_ids ), true );

	if ( false === $index ) {
		return $result;
	}

	if ( isset( $posts[ $index - 1 ] ) ) {
		$result['previous'] = $posts[ $index - 1 ];
	}

	if ( isset( $posts[ $index + 1 ] ) ) {
		$result['next'] = $posts[ $index + 1 ];
	}

	return $result;
}
