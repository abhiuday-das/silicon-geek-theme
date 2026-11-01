<?php
/**
 * Silicon Geek — GATE EC PYQ Practice Sets Backend (Step 5R).
 *
 * REPLACES the Step 5A version of:
 * wp-content/themes/ts-block-theme/inc/custom-post-type/ece-gate-pyq.php
 *
 * New content model:
 *  - 1 ece_gate_pyq post = 1 Topic-wise PYQ Practice Set.
 *  - The author only chooses the deepest Course Structure Topic once.
 *  - Questions are written normally in the editor:
 *      H2  Q1. GATE 2023 | 1 Mark | MCQ
 *      Paragraph question + list options
 *      H3  Correct Answer
 *      H3  Solution
 *  - No per-question Marks / Type / Set / Answer fields are shown.
 *  - Question count + exam-year range are calculated automatically from
 *    the Q-headings when the set is saved.
 *
 * Old Step 5A fields are not deleted from the database. They are simply
 * hidden/unused so this change remains reversible.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'TS_SG_GATE_PT' ) ) {
	define( 'TS_SG_GATE_PT', 'ece_gate_pyq' );
}

if ( ! defined( 'TS_SG_GATE_YEAR_TAX' ) ) {
	define( 'TS_SG_GATE_YEAR_TAX', 'ece_gate_year' );
}

/* Automatic, author-invisible index meta. */
if ( ! defined( 'TS_SG_GATE_META_QUESTION_COUNT' ) ) {
	define( 'TS_SG_GATE_META_QUESTION_COUNT', '_sg_gate_question_count' );
}

if ( ! defined( 'TS_SG_GATE_META_YEAR_MIN' ) ) {
	define( 'TS_SG_GATE_META_YEAR_MIN', '_sg_gate_year_min' );
}

if ( ! defined( 'TS_SG_GATE_META_YEAR_MAX' ) ) {
	define( 'TS_SG_GATE_META_YEAR_MAX', '_sg_gate_year_max' );
}

/* Legacy Step 5A keys kept only for backward compatibility. */
if ( ! defined( 'TS_SG_GATE_META_EXAM_YEAR' ) ) {
	define( 'TS_SG_GATE_META_EXAM_YEAR', '_sg_gate_exam_year' );
}

if ( ! defined( 'TS_SG_GATE_META_MARKS' ) ) {
	define( 'TS_SG_GATE_META_MARKS', '_sg_gate_marks' );
}

if ( ! defined( 'TS_SG_GATE_META_TYPE' ) ) {
	define( 'TS_SG_GATE_META_TYPE', '_sg_gate_question_type' );
}

if ( ! defined( 'TS_SG_GATE_META_SET' ) ) {
	define( 'TS_SG_GATE_META_SET', '_sg_gate_set' );
}

if ( ! defined( 'TS_SG_GATE_META_QNO' ) ) {
	define( 'TS_SG_GATE_META_QNO', '_sg_gate_question_number' );
}

if ( ! defined( 'TS_SG_GATE_META_ANSWER' ) ) {
	define( 'TS_SG_GATE_META_ANSWER', '_sg_gate_correct_answer' );
}

if ( ! defined( 'TS_SG_GATE_META_DIFFICULTY' ) ) {
	define( 'TS_SG_GATE_META_DIFFICULTY', '_sg_gate_difficulty' );
}

/**
 * Labels retained for heading parsing and legacy data readers.
 */
function ts_sg_gate_question_types() {
	return array(
		'mcq' => __( 'MCQ', 'ts-block-theme' ),
		'msq' => __( 'MSQ', 'ts-block-theme' ),
		'nat' => __( 'NAT', 'ts-block-theme' ),
	);
}

function ts_sg_gate_difficulty_levels() {
	return array(
		'easy'   => __( 'Easy', 'ts-block-theme' ),
		'medium' => __( 'Medium', 'ts-block-theme' ),
		'hard'   => __( 'Hard', 'ts-block-theme' ),
	);
}

function ts_sg_gate_sanitize_question_type( $value ) {
	$value = sanitize_key( (string) $value );
	$types = ts_sg_gate_question_types();

	return isset( $types[ $value ] ) ? $value : '';
}

function ts_sg_gate_sanitize_difficulty( $value ) {
	$value  = sanitize_key( (string) $value );
	$levels = ts_sg_gate_difficulty_levels();

	return isset( $levels[ $value ] ) ? $value : '';
}

function ts_sg_gate_sanitize_marks( $value ) {
	$value = absint( $value );

	return in_array( $value, array( 1, 2 ), true ) ? $value : 0;
}

function ts_sg_gate_meta_auth_callback( $allowed, $meta_key, $post_id ) {
	return current_user_can( 'edit_post', (int) $post_id );
}

/**
 * Register the now-hidden legacy Exam Year taxonomy and post meta.
 *
 * The taxonomy stays registered so old assignments/data are not lost, but
 * it no longer appears in the editor or admin menu.
 */
add_action( 'init', 'ts_sg_gate_register_backend', 12 );

function ts_sg_gate_register_backend() {
	register_taxonomy(
		TS_SG_GATE_YEAR_TAX,
		array( TS_SG_GATE_PT ),
		array(
			'labels'             => array(
				'name'          => _x( 'Exam Years (Legacy)', 'taxonomy general name', 'ts-block-theme' ),
				'singular_name' => _x( 'Exam Year (Legacy)', 'taxonomy singular name', 'ts-block-theme' ),
				'menu_name'     => __( 'Exam Years (Legacy)', 'ts-block-theme' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => false,
			'show_admin_column'  => false,
			'show_tagcloud'      => false,
			'hierarchical'       => true,
			'query_var'          => false,
			'rewrite'            => false,
		)
	);

	$meta_args = array(
		TS_SG_GATE_META_QUESTION_COUNT => array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
		),
		TS_SG_GATE_META_YEAR_MIN       => array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
		),
		TS_SG_GATE_META_YEAR_MAX       => array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
		),
		/* Legacy Step 5A meta: registered but no editor UI is printed. */
		TS_SG_GATE_META_EXAM_YEAR      => array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
		),
		TS_SG_GATE_META_MARKS          => array(
			'type'              => 'integer',
			'sanitize_callback' => 'ts_sg_gate_sanitize_marks',
		),
		TS_SG_GATE_META_TYPE           => array(
			'type'              => 'string',
			'sanitize_callback' => 'ts_sg_gate_sanitize_question_type',
		),
		TS_SG_GATE_META_SET            => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		TS_SG_GATE_META_QNO            => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		TS_SG_GATE_META_ANSWER         => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		TS_SG_GATE_META_DIFFICULTY     => array(
			'type'              => 'string',
			'sanitize_callback' => 'ts_sg_gate_sanitize_difficulty',
		),
	);

	foreach ( $meta_args as $meta_key => $args ) {
		register_post_meta(
			TS_SG_GATE_PT,
			$meta_key,
			array_merge(
				array(
					'single'        => true,
					'default'       => 'integer' === $args['type'] ? 0 : '',
					'show_in_rest'  => false,
					'auth_callback' => 'ts_sg_gate_meta_auth_callback',
				),
				$args
			)
		);
	}
}

/**
 * One-time seed: GATE EC -> Digital Electronics -> Logic Gates.
 * This is unchanged from Step 5A.
 */
add_action( 'admin_init', 'ts_sg_gate_seed_starter_tree', 31 );

function ts_sg_gate_seed_starter_tree() {
	if ( get_option( 'ts_sg_gate_starter_tree_v1' ) || ! taxonomy_exists( 'ece_course' ) ) {
		return;
	}

	$tree = array(
		array(
			'name'        => 'GATE EC',
			'slug'        => 'gate-ec',
			'order'       => 20,
			'description' => 'GATE Electronics and Communication subjects, notes and PYQ practice.',
			'children'    => array(
				array(
					'name'        => 'Digital Electronics',
					'slug'        => 'gate-digital-electronics',
					'order'       => 10,
					'description' => 'GATE EC Digital Electronics concepts and previous year questions.',
					'children'    => array(
						array(
							'name'        => 'Logic Gates',
							'slug'        => 'gate-logic-gates',
							'order'       => 10,
							'description' => 'Basic and universal logic gates — GATE previous year questions.',
							'children'    => array(),
						),
					),
				),
			),
		),
	);

	ts_sg_gate_seed_branch( $tree, 0 );
	update_option( 'ts_sg_gate_starter_tree_v1', 1 );
}

function ts_sg_gate_seed_branch( $nodes, $parent_id ) {
	foreach ( (array) $nodes as $node ) {
		if ( empty( $node['name'] ) || empty( $node['slug'] ) ) {
			continue;
		}

		$existing = get_term_by( 'slug', $node['slug'], 'ece_course' );
		$term_id  = 0;

		if ( $existing && ! is_wp_error( $existing ) ) {
			$term_id = (int) $existing->term_id;
		} else {
			$inserted = wp_insert_term(
				$node['name'],
				'ece_course',
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

		if ( defined( 'TS_CS_COURSE_ORDER_META' ) ) {
			update_term_meta(
				$term_id,
				TS_CS_COURSE_ORDER_META,
				isset( $node['order'] ) ? absint( $node['order'] ) : 0
			);
		}

		if ( ! empty( $node['children'] ) ) {
			ts_sg_gate_seed_branch( $node['children'], $term_id );
		}
	}
}

/**
 * Clean visible text from one heading's inner HTML.
 */
function ts_sg_gate_set_heading_text( $inner_html ) {
	$text = html_entity_decode( wp_strip_all_tags( (string) $inner_html ), ENT_QUOTES, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', $text );

	return trim( (string) $text );
}

/**
 * Recognise headings such as:
 * Q1, Q.1, Question 1, Question No. 1, प्रश्न 1.
 *
 * Returns false or array(number, rest).
 */
function ts_sg_gate_set_parse_question_heading( $text ) {
	$text = trim( (string) $text );
	$text = preg_replace( '/^[^\p{L}\p{N}]+/u', '', $text );

	if ( ! preg_match( '/^(?:question|ques\.?|q|प्रश्न)\s*(?:no\.?|number|#)?\s*[\(\[]?\s*(\d+)\s*[\)\]]?\s*(?:[\.:|\-–—]\s*)?(.*)$/iu', $text, $matches ) ) {
		return false;
	}

	return array(
		'number' => absint( $matches[1] ),
		'rest'   => isset( $matches[2] ) ? trim( (string) $matches[2] ) : '',
	);
}

/**
 * Extract display chips from a Q heading.
 * Examples: GATE 2023 | Set 1 | 1 Mark | MCQ.
 */
function ts_sg_gate_set_parse_heading_meta( $text, $rest = '' ) {
	$text  = trim( (string) $text );
	$years = array();

	if ( preg_match_all( '/\b(19\d{2}|20\d{2})\b/', $text, $year_matches ) ) {
		foreach ( $year_matches[1] as $year ) {
			$year = absint( $year );
			if ( $year >= 1990 && $year <= 2099 ) {
				$years[] = $year;
			}
		}
	}

	$years = array_values( array_unique( $years ) );

	$marks       = 0;
	$marks_label = '';
	if ( preg_match( '/(\d+(?:\.\d+)?)\s*marks?\b/i', $text, $marks_match ) ) {
		$marks       = (float) $marks_match[1];
		$marks_text  = rtrim( rtrim( number_format( $marks, 2, '.', '' ), '0' ), '.' );
		$marks_label = sprintf( '%s %s', $marks_text, 1.0 === $marks ? 'Mark' : 'Marks' );
	}

	$type       = '';
	$type_label = '';
	if ( preg_match( '/\b(MCQ|MSQ|NAT)\b/i', $text, $type_match ) ) {
		$type       = strtolower( $type_match[1] );
		$type_label = strtoupper( $type_match[1] );
	} elseif ( preg_match( '/multiple\s+select/i', $text ) ) {
		$type       = 'msq';
		$type_label = 'MSQ';
	} elseif ( preg_match( '/multiple\s+choice/i', $text ) ) {
		$type       = 'mcq';
		$type_label = 'MCQ';
	} elseif ( preg_match( '/numerical\s+answer/i', $text ) ) {
		$type       = 'nat';
		$type_label = 'NAT';
	}

	$set = '';
	if ( preg_match( '/\b(set|shift)\s*[-–—:]?\s*([a-z0-9]+)\b/i', $text, $set_match ) ) {
		$set = ucfirst( strtolower( $set_match[1] ) ) . ' ' . strtoupper( $set_match[2] );
	}

	/*
	 * Keep a useful non-metadata part of the heading, if the author wrote
	 * one. Example: "Q1. Boolean Algebra | GATE 2023 | MCQ".
	 */
	$label_source = '' !== $rest ? $rest : $text;
	$labels       = array();
	$chunks       = preg_split( '/\s*(?:\||•)\s*/u', $label_source );

	foreach ( (array) $chunks as $chunk ) {
		$chunk = trim( (string) $chunk );
		if ( '' === $chunk ) {
			continue;
		}

		if (
			preg_match( '/\bGATE\b/i', $chunk ) ||
			preg_match( '/\b(19\d{2}|20\d{2})\b/', $chunk ) ||
			preg_match( '/\bmarks?\b/i', $chunk ) ||
			preg_match( '/\b(MCQ|MSQ|NAT)\b/i', $chunk ) ||
			preg_match( '/^(set|shift)\b/i', $chunk ) ||
			preg_match( '/^(question|ques\.?|q|प्रश्न)\b/iu', $chunk )
		) {
			continue;
		}

		$labels[] = $chunk;
	}

	return array(
		'year'        => ! empty( $years ) ? (int) $years[0] : 0,
		'years'       => $years,
		'marks'       => $marks,
		'marks_label' => $marks_label,
		'type'        => $type,
		'type_label'  => $type_label,
		'set'         => $set,
		'label'       => implode( ' • ', $labels ),
	);
}

function ts_sg_gate_set_normalize_heading_key( $text ) {
	$text = strtolower( trim( (string) $text ) );
	$text = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $text );
	$text = preg_replace( '/\s+/u', ' ', (string) $text );

	return trim( (string) $text );
}

function ts_sg_gate_set_is_answer_heading( $text ) {
	$key = ts_sg_gate_set_normalize_heading_key( $text );

	return in_array(
		$key,
		array(
			'answer',
			'answers',
			'correct answer',
			'correct answers',
			'answer key',
			'final answer',
		),
		true
	) || 0 === strpos( $key, 'correct answer ' );
}

function ts_sg_gate_set_is_solution_heading( $text ) {
	$key = ts_sg_gate_set_normalize_heading_key( $text );

	return false !== strpos( $key, 'solution' )
		|| false !== strpos( $key, 'explanation' )
		|| false !== strpos( $key, 'step by step' );
}

/**
 * Analyse raw post content without rendering it. Used for auto-counting.
 */
function ts_sg_gate_set_analyze_content( $content ) {
	$result = array(
		'count'    => 0,
		'years'    => array(),
		'year_min' => 0,
		'year_max' => 0,
		'headings' => array(),
	);

	if ( ! preg_match_all( '/<h2\b[^>]*>(.*?)<\/h2>/is', (string) $content, $matches ) ) {
		return $result;
	}

	foreach ( $matches[1] as $inner_html ) {
		$text   = ts_sg_gate_set_heading_text( $inner_html );
		$parsed = ts_sg_gate_set_parse_question_heading( $text );

		if ( ! $parsed ) {
			continue;
		}

		$meta                = ts_sg_gate_set_parse_heading_meta( $text, $parsed['rest'] );
		$result['headings'][] = array_merge(
			array(
				'number' => (int) $parsed['number'],
				'text'   => $text,
				'rest'   => $parsed['rest'],
			),
			$meta
		);
		$result['years']      = array_merge( $result['years'], $meta['years'] );
	}

	$result['count'] = count( $result['headings'] );
	$result['years'] = array_values( array_unique( array_map( 'absint', $result['years'] ) ) );
	sort( $result['years'] );

	if ( ! empty( $result['years'] ) ) {
		$result['year_min'] = (int) min( $result['years'] );
		$result['year_max'] = (int) max( $result['years'] );
	}

	return $result;
}

function ts_sg_gate_set_analyze_post( $post ) {
	$post = is_object( $post ) ? $post : get_post( (int) $post );

	if ( ! $post || TS_SG_GATE_PT !== $post->post_type ) {
		return array(
			'count'    => 0,
			'years'    => array(),
			'year_min' => 0,
			'year_max' => 0,
			'headings' => array(),
		);
	}

	return ts_sg_gate_set_analyze_content( $post->post_content );
}

/**
 * Store the invisible auto-index for one set.
 */
function ts_sg_gate_set_refresh_index( $post_id, $post = null ) {
	$post_id = (int) $post_id;
	$post    = is_object( $post ) ? $post : get_post( $post_id );

	if ( ! $post || TS_SG_GATE_PT !== $post->post_type || 'revision' === $post->post_type ) {
		return;
	}

	$analysis = ts_sg_gate_set_analyze_post( $post );

	update_post_meta( $post_id, TS_SG_GATE_META_QUESTION_COUNT, (int) $analysis['count'] );

	if ( $analysis['year_min'] ) {
		update_post_meta( $post_id, TS_SG_GATE_META_YEAR_MIN, (int) $analysis['year_min'] );
	} else {
		delete_post_meta( $post_id, TS_SG_GATE_META_YEAR_MIN );
	}

	if ( $analysis['year_max'] ) {
		update_post_meta( $post_id, TS_SG_GATE_META_YEAR_MAX, (int) $analysis['year_max'] );
	} else {
		delete_post_meta( $post_id, TS_SG_GATE_META_YEAR_MAX );
	}
}

add_action( 'save_post_' . TS_SG_GATE_PT, 'ts_sg_gate_set_auto_index_on_save', 20, 3 );

function ts_sg_gate_set_auto_index_on_save( $post_id, $post, $update ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	ts_sg_gate_set_refresh_index( $post_id, $post );
}

/**
 * One-time index for sets that already existed before Step 5R.
 */
add_action( 'admin_init', 'ts_sg_gate_set_backfill_indexes', 45 );

function ts_sg_gate_set_backfill_indexes() {
	if ( get_option( 'ts_sg_gate_set_index_v1' ) || ! post_type_exists( TS_SG_GATE_PT ) ) {
		return;
	}

	$post_ids = get_posts(
		array(
			'post_type'      => TS_SG_GATE_PT,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	foreach ( (array) $post_ids as $post_id ) {
		ts_sg_gate_set_refresh_index( $post_id );
	}

	update_option( 'ts_sg_gate_set_index_v1', 1 );
}

function ts_sg_gate_set_get_question_count( $post ) {
	$post = is_object( $post ) ? $post : get_post( (int) $post );

	if ( ! $post || TS_SG_GATE_PT !== $post->post_type ) {
		return 0;
	}

	if ( metadata_exists( 'post', (int) $post->ID, TS_SG_GATE_META_QUESTION_COUNT ) ) {
		return absint( get_post_meta( (int) $post->ID, TS_SG_GATE_META_QUESTION_COUNT, true ) );
	}

	$analysis = ts_sg_gate_set_analyze_post( $post );

	return (int) $analysis['count'];
}

function ts_sg_gate_set_get_year_range( $post, $as_label = true ) {
	$post = is_object( $post ) ? $post : get_post( (int) $post );

	if ( ! $post || TS_SG_GATE_PT !== $post->post_type ) {
		return $as_label ? '' : array( 0, 0 );
	}

	$min = absint( get_post_meta( (int) $post->ID, TS_SG_GATE_META_YEAR_MIN, true ) );
	$max = absint( get_post_meta( (int) $post->ID, TS_SG_GATE_META_YEAR_MAX, true ) );

	if ( ! $min && ! $max && ! metadata_exists( 'post', (int) $post->ID, TS_SG_GATE_META_QUESTION_COUNT ) ) {
		$analysis = ts_sg_gate_set_analyze_post( $post );
		$min      = (int) $analysis['year_min'];
		$max      = (int) $analysis['year_max'];
	}

	if ( ! $as_label ) {
		return array( $min, $max );
	}

	if ( $min && $max && $min !== $max ) {
		return sprintf( '%d–%d', $min, $max );
	}

	return $min ? (string) $min : ( $max ? (string) $max : '' );
}

/**
 * Fetch published Practice Set IDs, optionally inside one Course folder.
 */
function ts_sg_gate_get_published_set_ids( $course_term_id = 0, $include_children = true ) {
	$args = array(
		'post_type'      => TS_SG_GATE_PT,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	if ( $course_term_id ) {
		$args['tax_query'] = array(
			array(
				'taxonomy'         => 'ece_course',
				'field'            => 'term_id',
				'terms'            => array( (int) $course_term_id ),
				'include_children' => (bool) $include_children,
			),
		);
	}

	$post_ids = get_posts( $args );

	return is_array( $post_ids ) ? array_map( 'absint', $post_ids ) : array();
}

function ts_sg_gate_count_sets( $course_term_id = 0, $include_children = true ) {
	$args = array(
		'post_type'      => TS_SG_GATE_PT,
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
	);

	if ( $course_term_id ) {
		$args['tax_query'] = array(
			array(
				'taxonomy'         => 'ece_course',
				'field'            => 'term_id',
				'terms'            => array( (int) $course_term_id ),
				'include_children' => (bool) $include_children,
			),
		);
	}

	$query = new WP_Query( $args );

	return (int) $query->found_posts;
}

/**
 * Actual question total across every Practice Set in a Course folder.
 * This keeps the Step 5B function name so ece-course-render.php keeps
 * working, but it now sums Q-headings instead of counting posts.
 */
function ts_sg_gate_count_questions( $course_term_id = 0, $include_children = true ) {
	static $cache = array();

	$course_term_id = (int) $course_term_id;
	$key            = $course_term_id . ':' . ( $include_children ? '1' : '0' );

	if ( isset( $cache[ $key ] ) ) {
		return (int) $cache[ $key ];
	}

	$post_ids = ts_sg_gate_get_published_set_ids( $course_term_id, $include_children );
	$total    = 0;

	if ( ! empty( $post_ids ) ) {
		update_meta_cache( 'post', $post_ids );

		foreach ( $post_ids as $post_id ) {
			$total += ts_sg_gate_set_get_question_count( $post_id );
		}
	}

	$cache[ $key ] = $total;

	return $total;
}

function ts_sg_gate_count_all_questions() {
	return ts_sg_gate_count_questions( 0, true );
}

/**
 * Admin list: only useful set-level information. No manual PYQ fields.
 */
add_filter( 'manage_' . TS_SG_GATE_PT . '_posts_columns', 'ts_sg_gate_admin_columns' );

function ts_sg_gate_admin_columns( $columns ) {
	$new_columns = array();

	foreach ( $columns as $key => $label ) {
		$new_columns[ $key ] = $label;

		if ( 'title' === $key ) {
			$new_columns['ts_sg_gate_course']    = __( 'Course Topic', 'ts-block-theme' );
			$new_columns['ts_sg_gate_questions'] = __( 'Questions', 'ts-block-theme' );
			$new_columns['ts_sg_gate_years']     = __( 'Exam Years', 'ts-block-theme' );
		}
	}

	return $new_columns;
}

add_action( 'manage_' . TS_SG_GATE_PT . '_posts_custom_column', 'ts_sg_gate_render_admin_column', 10, 2 );

function ts_sg_gate_render_admin_column( $column_name, $post_id ) {
	if ( 'ts_sg_gate_course' === $column_name ) {
		$names = array();

		if ( function_exists( 'ts_cs_course_get_primary_term' ) && function_exists( 'ts_cs_course_get_path' ) ) {
			$primary = ts_cs_course_get_primary_term( (int) $post_id );

			if ( $primary ) {
				foreach ( ts_cs_course_get_path( $primary ) as $path_term ) {
					if ( 'gate-ec' !== $path_term->slug ) {
						$names[] = $path_term->name;
					}
				}
			}
		}

		echo ! empty( $names )
			? esc_html( implode( ' › ', $names ) )
			: '<strong style="color:#b32d2e;">' . esc_html__( 'Not assigned', 'ts-block-theme' ) . '</strong>';

		return;
	}

	if ( 'ts_sg_gate_questions' === $column_name ) {
		$count = ts_sg_gate_set_get_question_count( $post_id );
		echo $count ? esc_html( (string) $count ) : '<strong style="color:#b32d2e;">0</strong>';
		return;
	}

	if ( 'ts_sg_gate_years' === $column_name ) {
		$range = ts_sg_gate_set_get_year_range( $post_id, true );
		echo '' !== $range ? esc_html( $range ) : '—';
	}
}

add_filter( 'manage_edit-' . TS_SG_GATE_PT . '_sortable_columns', 'ts_sg_gate_sortable_columns' );

function ts_sg_gate_sortable_columns( $columns ) {
	$columns['ts_sg_gate_questions'] = 'sg_gate_question_count';

	return $columns;
}

add_action( 'pre_get_posts', 'ts_sg_gate_admin_sort_questions' );

function ts_sg_gate_admin_sort_questions( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || TS_SG_GATE_PT !== $query->get( 'post_type' ) ) {
		return;
	}

	if ( 'sg_gate_question_count' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', TS_SG_GATE_META_QUESTION_COUNT );
		$query->set( 'orderby', 'meta_value_num' );
	}
}

/**
 * Legacy Step 5A read helpers.
 * They remain harmless if any old code calls them, but Step 5R's
 * frontend does not need them.
 */
function ts_sg_gate_get_year_terms( $post_id ) {
	$terms = get_the_terms( (int) $post_id, TS_SG_GATE_YEAR_TAX );

	return is_array( $terms ) ? $terms : array();
}

function ts_sg_gate_get_exam_year( $post_id ) {
	$year = absint( get_post_meta( (int) $post_id, TS_SG_GATE_META_EXAM_YEAR, true ) );

	if ( $year ) {
		return $year;
	}

	$years = array();

	foreach ( ts_sg_gate_get_year_terms( $post_id ) as $term ) {
		$value = absint( $term->slug ? $term->slug : $term->name );

		if ( $value ) {
			$years[] = $value;
		}
	}

	return ! empty( $years ) ? max( $years ) : 0;
}

function ts_sg_gate_get_marks( $post_id ) {
	return ts_sg_gate_sanitize_marks( get_post_meta( (int) $post_id, TS_SG_GATE_META_MARKS, true ) );
}

function ts_sg_gate_get_question_type( $post_id, $label = false ) {
	$type  = ts_sg_gate_sanitize_question_type( get_post_meta( (int) $post_id, TS_SG_GATE_META_TYPE, true ) );
	$types = ts_sg_gate_question_types();

	if ( ! $label ) {
		return $type;
	}

	return isset( $types[ $type ] ) ? $types[ $type ] : '';
}

function ts_sg_gate_get_set( $post_id ) {
	return (string) get_post_meta( (int) $post_id, TS_SG_GATE_META_SET, true );
}

function ts_sg_gate_get_question_number( $post_id ) {
	return (string) get_post_meta( (int) $post_id, TS_SG_GATE_META_QNO, true );
}

function ts_sg_gate_get_correct_answer( $post_id ) {
	return (string) get_post_meta( (int) $post_id, TS_SG_GATE_META_ANSWER, true );
}

function ts_sg_gate_get_difficulty( $post_id, $label = false ) {
	$value  = ts_sg_gate_sanitize_difficulty( get_post_meta( (int) $post_id, TS_SG_GATE_META_DIFFICULTY, true ) );
	$levels = ts_sg_gate_difficulty_levels();

	if ( ! $label ) {
		return $value;
	}

	return isset( $levels[ $value ] ) ? $levels[ $value ] : '';
}

function ts_sg_gate_get_details( $post_id ) {
	return array(
		'year'             => ts_sg_gate_get_exam_year( $post_id ),
		'marks'            => ts_sg_gate_get_marks( $post_id ),
		'type'             => ts_sg_gate_get_question_type( $post_id ),
		'type_label'       => ts_sg_gate_get_question_type( $post_id, true ),
		'set'              => ts_sg_gate_get_set( $post_id ),
		'question_number'  => ts_sg_gate_get_question_number( $post_id ),
		'answer'           => ts_sg_gate_get_correct_answer( $post_id ),
		'difficulty'       => ts_sg_gate_get_difficulty( $post_id ),
		'difficulty_label' => ts_sg_gate_get_difficulty( $post_id, true ),
	);
}

function ts_sg_gate_format_source_label( $post_id ) {
	$details = ts_sg_gate_get_details( $post_id );
	$parts   = array();

	$parts[] = $details['year'] ? sprintf( 'GATE EC %d', $details['year'] ) : 'GATE EC';

	if ( '' !== $details['set'] ) {
		$parts[] = $details['set'];
	}

	if ( '' !== $details['question_number'] ) {
		$parts[] = sprintf( 'Q.%s', $details['question_number'] );
	}

	if ( $details['marks'] ) {
		$parts[] = sprintf( '%d %s', $details['marks'], 1 === $details['marks'] ? 'mark' : 'marks' );
	}

	if ( '' !== $details['type_label'] ) {
		$parts[] = $details['type_label'];
	}

	return implode( ' • ', $parts );
}
