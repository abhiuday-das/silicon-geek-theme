<?php
/**
 * Silicon Geek — GATE PYQ Practice Set Renderer (Step 5R).
 *
 * REPLACES / CREATES:
 * wp-content/themes/ts-block-theme/inc/custom-post-type/ece-gate-render.php
 *
 * Load it immediately after ece-gate-pyq.php:
 * require_once get_template_directory() . '/inc/custom-post-type/ece-gate-render.php';
 *
 * Provides:
 *  - [sg_gate_pyq_index]  /gate-pyq/ Practice Set archive
 *  - [sg_gate_pyq_set]    single Practice Set page
 *  - ts_sg_gate_render_course_questions() for GATE course pages
 *    (old function name intentionally retained for compatibility)
 *
 * Author format inside one Practice Set post:
 *  H2: Q1. GATE 2023 | 1 Mark | MCQ
 *  Paragraph statement + List options
 *  H3: Correct Answer
 *  H3: Solution
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'sg_gate_pyq_index', 'ts_sg_gate_render_archive_shortcode' );
add_shortcode( 'sg_gate_pyq_set', 'ts_sg_gate_render_set_shortcode' );

function ts_sg_gate_render_is_ready() {
	return defined( 'TS_SG_GATE_PT' )
		&& function_exists( 'ts_sg_gate_set_get_question_count' )
		&& function_exists( 'ts_sg_gate_set_get_year_range' )
		&& function_exists( 'ts_sg_gate_count_questions' )
		&& function_exists( 'ts_cs_course_get_children' )
		&& function_exists( 'ts_cs_course_get_path' );
}

function ts_sg_gate_render_get_root_term() {
	$term = get_term_by( 'slug', 'gate-ec', 'ece_course' );

	return $term && ! is_wp_error( $term ) ? $term : false;
}

function ts_sg_gate_render_term_is_gate( $term ) {
	if ( ! $term || is_wp_error( $term ) || ! function_exists( 'ts_cs_course_get_path' ) ) {
		return false;
	}

	$path = ts_cs_course_get_path( $term );

	return ! empty( $path ) && 'gate-ec' === $path[0]->slug;
}

/**
 * Public GET filters for set listings. Old per-question Year/Type/Marks
 * parameters are intentionally ignored in the Practice Set model.
 */
function ts_sg_gate_render_get_filters() {
	return array(
		'course' => isset( $_GET['pyq_course'] ) ? absint( wp_unslash( $_GET['pyq_course'] ) ) : 0,
		'search' => isset( $_GET['pyq_search'] ) ? sanitize_text_field( wp_unslash( $_GET['pyq_search'] ) ) : '',
		'page'   => isset( $_GET['pyq_page'] ) ? max( 1, absint( wp_unslash( $_GET['pyq_page'] ) ) ) : 1,
	);
}

function ts_sg_gate_render_filters_active( $filters, $check_course = true ) {
	return ( $check_course && ! empty( $filters['course'] ) )
		|| '' !== $filters['search'];
}

function ts_sg_gate_render_filter_query_args( $filters, $include_course = false, $page = 0 ) {
	$args = array();

	if ( $include_course && ! empty( $filters['course'] ) ) {
		$args['pyq_course'] = (int) $filters['course'];
	}

	if ( '' !== $filters['search'] ) {
		$args['pyq_search'] = $filters['search'];
	}

	if ( $page > 1 ) {
		$args['pyq_page'] = (int) $page;
	}

	return $args;
}

/**
 * Fetch one filtered page of published Practice Sets.
 */
function ts_sg_gate_render_fetch_sets( $filters, $course_term_id = 0, $include_children = true, $per_page = 12 ) {
	$per_page         = max( 1, (int) $per_page );
	$page             = max( 1, (int) $filters['page'] );
	$effective_course = $course_term_id ? (int) $course_term_id : (int) $filters['course'];

	$args = array(
		'post_type'      => TS_SG_GATE_PT,
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'modified'   => 'DESC',
			'title'      => 'ASC',
		),
	);

	if ( $effective_course ) {
		$args['tax_query'] = array(
			array(
				'taxonomy'         => 'ece_course',
				'field'            => 'term_id',
				'terms'            => array( $effective_course ),
				'include_children' => (bool) $include_children,
			),
		);
	}

	if ( '' !== $filters['search'] ) {
		$args['s'] = $filters['search'];
	}

	$query = new WP_Query( $args );

	return array(
		'posts'     => $query->posts,
		'total'     => (int) $query->found_posts,
		'page'      => $page,
		'max_pages' => max( 1, (int) $query->max_num_pages ),
		'per_page'  => $per_page,
	);
}

function ts_sg_gate_render_course_path_label( $post_id ) {
	if ( ! function_exists( 'ts_cs_course_get_primary_term' ) ) {
		return '';
	}

	$primary = ts_cs_course_get_primary_term( (int) $post_id );

	if ( ! $primary ) {
		return '';
	}

	$names = array();

	foreach ( ts_cs_course_get_path( $primary ) as $term ) {
		if ( 'gate-ec' !== $term->slug ) {
			$names[] = $term->name;
		}
	}

	return implode( ' › ', $names );
}

/**
 * Card excerpt: manual excerpt first, otherwise the intro before Q1.
 */
function ts_sg_gate_render_set_excerpt( $post, $words = 27 ) {
	if ( has_excerpt( $post ) ) {
		return wp_trim_words( get_the_excerpt( $post ), $words, '…' );
	}

	$content = (string) $post->post_content;
	$intro   = $content;

	if ( preg_match_all( '/<h2\b[^>]*>(.*?)<\/h2>/is', $content, $matches, PREG_OFFSET_CAPTURE ) ) {
		foreach ( $matches[1] as $index => $heading ) {
			$text = function_exists( 'ts_sg_gate_set_heading_text' )
				? ts_sg_gate_set_heading_text( $heading[0] )
				: trim( wp_strip_all_tags( $heading[0] ) );

			if ( function_exists( 'ts_sg_gate_set_parse_question_heading' ) && ts_sg_gate_set_parse_question_heading( $text ) ) {
				$intro = substr( $content, 0, (int) $matches[0][ $index ][1] );
				break;
			}
		}
	}

	$text = trim( wp_strip_all_tags( strip_shortcodes( $intro ) ) );

	if ( '' === $text ) {
		$text = trim( wp_strip_all_tags( strip_shortcodes( $content ) ) );
		$text = preg_replace( '/\b(?:Question|Q)\s*\d+\b[^.]{0,80}(?:MCQ|MSQ|NAT)?/i', '', $text );
	}

	return wp_trim_words( trim( (string) $text ), $words, '…' );
}

function ts_sg_gate_render_set_card( $post ) {
	$url = get_permalink( $post );

	if ( ! $url ) {
		return;
	}

	$count      = ts_sg_gate_set_get_question_count( $post );
	$year_range = ts_sg_gate_set_get_year_range( $post, true );
	$path       = ts_sg_gate_render_course_path_label( $post->ID );
	?>
	<article class="sg-gate-card sg-gate-card--set">
		<div class="sg-gate-card__badges">
			<span class="sg-gate-badge sg-gate-badge--year">Practice Set</span>
			<?php if ( $count ) : ?>
				<span class="sg-gate-badge"><?php echo esc_html( sprintf( '%d Questions', $count ) ); ?></span>
			<?php else : ?>
				<span class="sg-gate-badge">Questions Soon</span>
			<?php endif; ?>
			<?php if ( '' !== $year_range ) : ?>
				<span class="sg-gate-badge">GATE <?php echo esc_html( $year_range ); ?></span>
			<?php endif; ?>
		</div>

		<h3 class="sg-gate-card__title">
			<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
		</h3>

		<?php if ( '' !== $path ) : ?>
			<p class="sg-gate-card__path"><?php echo esc_html( $path ); ?></p>
		<?php endif; ?>

		<p class="sg-gate-card__excerpt"><?php echo esc_html( ts_sg_gate_render_set_excerpt( $post ) ); ?></p>

		<div class="sg-gate-card__foot">
			<span>Updated: <?php echo esc_html( get_the_modified_date( 'j M Y', $post ) ); ?></span>
			<a class="sg-gate-card__link" href="<?php echo esc_url( $url ); ?>">Start Practice →</a>
		</div>
	</article>
	<?php
}

function ts_sg_gate_render_course_options( $parent_id, $selected, $depth = 0 ) {
	foreach ( ts_cs_course_get_children( (int) $parent_id, false ) as $term ) {
		printf(
			'<option value="%1$d" %2$s>%3$s</option>',
			(int) $term->term_id,
			selected( (int) $selected, (int) $term->term_id, false ),
			esc_html( str_repeat( '— ', (int) $depth ) . $term->name )
		);

		ts_sg_gate_render_course_options( (int) $term->term_id, $selected, (int) $depth + 1 );
	}
}

function ts_sg_gate_render_filter_form( $action_url, $filters, $show_course = false, $id_prefix = 'sg-gate' ) {
	$root = ts_sg_gate_render_get_root_term();
	?>
	<form class="sg-gate-filters sg-gate-filters--sets" method="get" action="<?php echo esc_url( $action_url ); ?>" role="search">
		<?php if ( $show_course && $root ) : ?>
			<div class="sg-gate-field sg-gate-field--course">
				<label for="<?php echo esc_attr( $id_prefix ); ?>-course">Subject / Topic</label>
				<select id="<?php echo esc_attr( $id_prefix ); ?>-course" name="pyq_course">
					<option value="">All Subjects &amp; Topics</option>
					<?php ts_sg_gate_render_course_options( (int) $root->term_id, (int) $filters['course'] ); ?>
				</select>
			</div>
		<?php endif; ?>

		<div class="sg-gate-field sg-gate-field--search">
			<label for="<?php echo esc_attr( $id_prefix ); ?>-search">Search Practice Sets</label>
			<input id="<?php echo esc_attr( $id_prefix ); ?>-search" type="search" name="pyq_search" value="<?php echo esc_attr( $filters['search'] ); ?>" placeholder="Logic Gates, Fourier, MOSFET…" />
		</div>

		<div class="sg-gate-filter-actions">
			<button class="sg-course-btn sg-course-btn--primary" type="submit">Apply Filters</button>
			<?php if ( ts_sg_gate_render_filters_active( $filters, $show_course ) ) : ?>
				<a class="sg-gate-clear" href="<?php echo esc_url( $action_url ); ?>#sg-gate-questions">Clear</a>
			<?php endif; ?>
		</div>
	</form>
	<?php
}

function ts_sg_gate_render_pagination( $base_url, $filters, $result, $include_course = false ) {
	$page      = (int) $result['page'];
	$max_pages = (int) $result['max_pages'];

	if ( $max_pages < 2 ) {
		return;
	}

	$url_for = function ( $target_page ) use ( $base_url, $filters, $include_course ) {
		$args = ts_sg_gate_render_filter_query_args( $filters, $include_course, $target_page );
		$url  = ! empty( $args ) ? add_query_arg( $args, $base_url ) : $base_url;

		return $url . '#sg-gate-questions';
	};

	$pages = array();

	for ( $i = 1; $i <= $max_pages; $i++ ) {
		if ( 1 === $i || $i === $max_pages || abs( $i - $page ) <= 1 ) {
			$pages[] = $i;
		}
	}

	$pages = array_values( array_unique( $pages ) );
	?>
	<nav class="sg-gate-pagination" aria-label="<?php esc_attr_e( 'Practice set pages', 'ts-block-theme' ); ?>">
		<?php if ( $page > 1 ) : ?>
			<a href="<?php echo esc_url( $url_for( $page - 1 ) ); ?>">← Prev</a>
		<?php endif; ?>

		<?php $previous_shown = 0; ?>
		<?php foreach ( $pages as $shown_page ) : ?>
			<?php if ( $previous_shown && $shown_page > $previous_shown + 1 ) : ?>
				<span class="sg-gate-pagination__dots">…</span>
			<?php endif; ?>

			<?php if ( $shown_page === $page ) : ?>
				<span class="is-current" aria-current="page"><?php echo esc_html( (string) $shown_page ); ?></span>
			<?php else : ?>
				<a href="<?php echo esc_url( $url_for( $shown_page ) ); ?>"><?php echo esc_html( (string) $shown_page ); ?></a>
			<?php endif; ?>

			<?php $previous_shown = $shown_page; ?>
		<?php endforeach; ?>

		<?php if ( $page < $max_pages ) : ?>
			<a href="<?php echo esc_url( $url_for( $page + 1 ) ); ?>">Next →</a>
		<?php endif; ?>
	</nav>
	<?php
}

function ts_sg_gate_render_results_block( $result, $filters, $base_url, $include_course = false ) {
	$active = ts_sg_gate_render_filters_active( $filters, $include_course );
	?>
	<p class="sg-gate-results-meta" role="status">
		<?php
		echo esc_html(
			sprintf(
				'%d %s mile%s',
				(int) $result['total'],
				1 === (int) $result['total'] ? 'practice set' : 'practice sets',
				$active ? ' — filters applied' : ''
			)
		);
		?>
	</p>

	<?php if ( ! empty( $result['posts'] ) ) : ?>
		<div class="sg-gate-grid">
			<?php foreach ( $result['posts'] as $post ) : ?>
				<?php ts_sg_gate_render_set_card( $post ); ?>
			<?php endforeach; ?>
		</div>

		<?php ts_sg_gate_render_pagination( $base_url, $filters, $result, $include_course ); ?>
	<?php else : ?>
		<div class="sg-gate-empty">
			<span aria-hidden="true">🔎</span>
			<h3>Koi PYQ practice set nahi mila</h3>
			<p>Search word badlo ya dusra subject/topic select karke phir try karo.</p>
			<a class="sg-course-btn sg-course-btn--primary" href="<?php echo esc_url( $base_url ); ?>#sg-gate-questions">Clear Filters</a>
		</div>
	<?php endif; ?>
	<?php
}

/**
 * GATE course pages par Practice Sets.
 * Function name purana rakha hai, isliye updated ece-course-render.php
 * bina kisi extra connection ke ise call kar sakta hai.
 */
function ts_sg_gate_render_course_questions( $term ) {
	if ( ! ts_sg_gate_render_is_ready() || ! ts_sg_gate_render_term_is_gate( $term ) ) {
		return false;
	}

	$term_url = get_term_link( $term );

	if ( is_wp_error( $term_url ) ) {
		return false;
	}

	$filters         = ts_sg_gate_render_get_filters();
	$total_sets      = function_exists( 'ts_sg_gate_count_sets' ) ? ts_sg_gate_count_sets( (int) $term->term_id, true ) : 0;
	$total_questions = function_exists( 'ts_sg_gate_count_questions' ) ? ts_sg_gate_count_questions( (int) $term->term_id, true ) : 0;
	$active          = ts_sg_gate_render_filters_active( $filters, false );
	$depth           = function_exists( 'ts_cs_course_get_depth' ) ? ts_cs_course_get_depth( $term ) : 0;
	?>
	<section class="sg-gate-course-practice" id="sg-gate-questions">
		<div class="sg-course-section-head">
			<p>Practice with previous year questions</p>
			<h2><?php echo 0 === $depth ? 'GATE PYQ Practice Sets' : 'PYQ Practice Sets'; ?></h2>
		</div>

		<?php if ( ! $total_sets && ! $active ) : ?>
			<div class="sg-gate-empty sg-gate-empty--course">
				<span aria-hidden="true">🏆</span>
				<h3>Is section ke PYQs jald aayenge</h3>
				<p>Pehle concept notes complete karo. Practice Set publish hote hi woh yahin automatic dikhega.</p>
				<a class="sg-course-btn sg-course-btn--primary" href="<?php echo esc_url( get_post_type_archive_link( TS_SG_GATE_PT ) ); ?>">Browse All PYQ Sets</a>
			</div>
		<?php else : ?>
			<p class="sg-gate-course-summary">
				<?php echo esc_html( sprintf( '%d practice sets • %d total questions', $total_sets, $total_questions ) ); ?>
			</p>
			<?php ts_sg_gate_render_filter_form( $term_url, $filters, false, 'sg-gate-course' ); ?>
			<?php
			$result = ts_sg_gate_render_fetch_sets( $filters, (int) $term->term_id, true, 6 );
			ts_sg_gate_render_results_block( $result, $filters, $term_url, false );
			?>
		<?php endif; ?>
	</section>
	<?php

	return true;
}

/**
 * Main /gate-pyq/ archive.
 */
function ts_sg_gate_render_archive_shortcode() {
	if ( ! ts_sg_gate_render_is_ready() ) {
		return '';
	}

	$root            = ts_sg_gate_render_get_root_term();
	$subjects        = $root ? ts_cs_course_get_children( (int) $root->term_id, false ) : array();
	$archive_url     = get_post_type_archive_link( TS_SG_GATE_PT );
	$filters         = ts_sg_gate_render_get_filters();
	$result          = ts_sg_gate_render_fetch_sets( $filters, 0, true, 12 );
	$total_sets      = function_exists( 'ts_sg_gate_count_sets' ) ? ts_sg_gate_count_sets( 0, true ) : 0;
	$total_questions = function_exists( 'ts_sg_gate_count_all_questions' ) ? ts_sg_gate_count_all_questions() : 0;

	ob_start();
	?>
	<section class="sg-course sg-course--root-gate-ec sg-gate sg-gate--archive">
		<div class="sg-course-hero">
			<div class="sg-course-wrap">
				<nav class="sg-course-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'ts-block-theme' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
					<span class="sg-course-breadcrumbs__sep" aria-hidden="true">›</span>
					<span aria-current="page">GATE EC PYQ Sets</span>
				</nav>

				<div class="sg-course-hero__main sg-course-hero__main--wide">
					<p class="sg-course-eyebrow">🏆 GATE EC Practice Zone</p>
					<h1>GATE EC PYQ Practice Sets</h1>
					<p class="sg-course-hero__desc">Har topic ke important previous year questions ek hi practice set me. Pehle khud try karo, phir answer aur step-by-step solution dekho.</p>

					<div class="sg-course-stats" aria-label="<?php esc_attr_e( 'PYQ statistics', 'ts-block-theme' ); ?>">
						<span><strong><?php echo esc_html( (string) $total_sets ); ?></strong> practice sets</span>
						<span><strong><?php echo esc_html( (string) $total_questions ); ?></strong> questions</span>
						<span><strong><?php echo esc_html( (string) count( $subjects ) ); ?></strong> subjects</span>
					</div>

					<div class="sg-course-actions">
						<a class="sg-course-btn sg-course-btn--primary" href="#sg-gate-questions">Practice Sets Dekho</a>
						<?php if ( $root ) : ?>
							<?php $root_url = get_term_link( $root ); ?>
							<?php if ( ! is_wp_error( $root_url ) ) : ?>
								<a class="sg-course-btn sg-course-btn--dark-ghost" href="<?php echo esc_url( $root_url ); ?>">Subject-wise Path</a>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<div class="sg-course-wrap sg-course-content">
			<?php if ( ! empty( $subjects ) ) : ?>
				<div class="sg-course-section-head">
					<p>Choose your subject</p>
					<h2>GATE Subjects</h2>
				</div>
				<div class="sg-course-grid">
					<?php foreach ( $subjects as $subject ) : ?>
						<?php
						if ( function_exists( 'ts_cs_course_index_render_term_card' ) ) {
							ts_cs_course_index_render_term_card( $subject );
						}
						?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<section class="sg-gate-practice" id="sg-gate-questions">
				<div class="sg-course-section-head">
					<p>Search and practice</p>
					<h2>All PYQ Practice Sets</h2>
				</div>

				<?php ts_sg_gate_render_filter_form( $archive_url, $filters, true, 'sg-gate-archive' ); ?>
				<?php ts_sg_gate_render_results_block( $result, $filters, $archive_url, true ); ?>
			</section>

			<aside class="sg-course-help">
				<div>
					<p class="sg-course-help__eyebrow">Concept weak lag raha hai?</p>
					<h2>Pehle note revise karo, phir PYQ set dobara try karo.</h2>
					<p>Har GATE subject page par concept notes aur usi topic ke practice sets saath milte hain.</p>
				</div>
				<div class="sg-course-help__actions">
					<a class="sg-course-btn sg-course-btn--light" href="<?php echo esc_url( home_url( '/notes/' ) ); ?>">All Notes</a>
					<a class="sg-course-btn sg-course-btn--ghost" href="<?php echo esc_url( home_url( '/project-help/' ) ); ?>">Get Project Help</a>
				</div>
			</aside>
		</div>
	</section>
	<?php

	return ob_get_clean();
}

/**
 * Check whether an HTML fragment has visible/useful content.
 */
function ts_sg_gate_set_html_has_content( $html ) {
	if ( '' !== trim( wp_strip_all_tags( (string) $html ) ) ) {
		return true;
	}

	return (bool) preg_match( '/<(img|table|figure|pre|iframe|video|audio|svg|math)\b/i', (string) $html );
}

/**
 * Split rendered content at Q/Answer/Solution headings.
 */
function ts_sg_gate_set_split_content( $post ) {
	$html = apply_filters( 'the_content', $post->post_content );

	$parsed = array(
		'intro_html' => '',
		'questions'  => array(),
		'outro_html' => '',
		'full_html'  => $html,
	);

	if ( ! preg_match_all( '/<h([23])\b[^>]*>(.*?)<\/h\1>/is', $html, $matches, PREG_OFFSET_CAPTURE ) ) {
		$parsed['intro_html'] = $html;
		return $parsed;
	}

	$mode    = 'intro';
	$section = 'statement_html';
	$cursor  = 0;

	$append = function ( $chunk ) use ( &$parsed, &$mode, &$section ) {
		if ( '' === $chunk ) {
			return;
		}

		if ( 'intro' === $mode ) {
			$parsed['intro_html'] .= $chunk;
			return;
		}

		if ( 'outro' === $mode ) {
			$parsed['outro_html'] .= $chunk;
			return;
		}

		$last_index = count( $parsed['questions'] ) - 1;

		if ( $last_index >= 0 ) {
			$parsed['questions'][ $last_index ][ $section ] .= $chunk;
		} else {
			$parsed['intro_html'] .= $chunk;
		}
	};

	foreach ( $matches[0] as $index => $full_match ) {
		$heading_html = $full_match[0];
		$start        = (int) $full_match[1];
		$level        = (int) $matches[1][ $index ][0];
		$inner_html   = $matches[2][ $index ][0];
		$text         = ts_sg_gate_set_heading_text( $inner_html );

		if ( $start > $cursor ) {
			$append( substr( $html, $cursor, $start - $cursor ) );
		}

		$question_heading = 2 === $level ? ts_sg_gate_set_parse_question_heading( $text ) : false;

		if ( $question_heading ) {
			$parsed['questions'][] = array(
				'number'        => (int) $question_heading['number'],
				'heading_text'  => $text,
				'rest'          => $question_heading['rest'],
				'meta'          => ts_sg_gate_set_parse_heading_meta( $text, $question_heading['rest'] ),
				'statement_html'=> '',
				'answer_html'   => '',
				'solution_html' => '',
			);
			$mode    = 'question';
			$section = 'statement_html';
		} elseif ( 3 === $level && 'question' === $mode && ts_sg_gate_set_is_solution_heading( $text ) ) {
			$section = 'solution_html';
		} elseif ( 3 === $level && 'question' === $mode && ts_sg_gate_set_is_answer_heading( $text ) ) {
			$section = 'answer_html';
		} elseif ( 2 === $level && ! empty( $parsed['questions'] ) ) {
			$mode = 'outro';
			$append( $heading_html );
		} else {
			$append( $heading_html );
		}

		$cursor = $start + strlen( $heading_html );
	}

	if ( $cursor < strlen( $html ) ) {
		$append( substr( $html, $cursor ) );
	}

	return $parsed;
}

/**
 * The first normal list in a non-NAT question becomes A/B/C/D options.
 */
function ts_sg_gate_set_extract_options( &$statement_html, $question_type = '' ) {
	if ( 'nat' === $question_type ) {
		return array();
	}

	if ( ! preg_match( '/<(ul|ol)\b[^>]*>(.*?)<\/\1>/is', $statement_html, $list_match ) ) {
		return array();
	}

	if ( ! preg_match_all( '/<li\b[^>]*>(.*?)<\/li>/is', $list_match[2], $items ) ) {
		return array();
	}

	$options = array();

	foreach ( $items[1] as $item_html ) {
		if ( ts_sg_gate_set_html_has_content( $item_html ) ) {
			$options[] = $item_html;
		}
	}

	if ( count( $options ) < 2 || count( $options ) > 8 ) {
		return array();
	}

	$statement_html = preg_replace( '/' . preg_quote( $list_match[0], '/' ) . '/', '', $statement_html, 1 );

	return $options;
}

function ts_sg_gate_set_render_question( $question, $position ) {
	$meta           = $question['meta'];
	$statement_html = $question['statement_html'];
	$options        = ts_sg_gate_set_extract_options( $statement_html, $meta['type'] );
	$question_id    = 'q-' . (int) $position;
	?>
	<article class="sg-pyq-question" id="<?php echo esc_attr( $question_id ); ?>">
		<header class="sg-pyq-question__head">
			<div class="sg-pyq-question__number">
				<span class="sg-pyq-question__badge">Q<?php echo esc_html( (string) $position ); ?></span>
				<div>
					<h2>Question <?php echo esc_html( (string) $position ); ?></h2>
					<?php if ( '' !== $meta['label'] ) : ?>
						<p><?php echo esc_html( $meta['label'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<div class="sg-pyq-question__tags">
				<?php if ( $meta['year'] ) : ?>
					<span class="sg-pyq-tag sg-pyq-tag--year">GATE <?php echo esc_html( (string) $meta['year'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $meta['set'] ) : ?>
					<span class="sg-pyq-tag"><?php echo esc_html( $meta['set'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $meta['marks_label'] ) : ?>
					<span class="sg-pyq-tag"><?php echo esc_html( $meta['marks_label'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $meta['type_label'] ) : ?>
					<span class="sg-pyq-tag"><?php echo esc_html( $meta['type_label'] ); ?></span>
				<?php endif; ?>
			</div>
		</header>

		<?php if ( ts_sg_gate_set_html_has_content( $statement_html ) ) : ?>
			<div class="sg-pyq-question__statement">
				<?php echo $statement_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content. ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $options ) ) : ?>
			<ol class="sg-pyq-options">
				<?php foreach ( $options as $option_html ) : ?>
					<li><div><?php echo $option_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content. ?></div></li>
				<?php endforeach; ?>
		</ol>
		<?php elseif ( 'nat' === $meta['type'] ) : ?>
			<p class="sg-pyq-nat-note">✍️ NAT Question — pehle apna numeric answer rough copy par nikalo, phir answer kholo.</p>
		<?php endif; ?>

		<?php if ( ts_sg_gate_set_html_has_content( $question['answer_html'] ) ) : ?>
			<details class="sg-pyq-reveal sg-pyq-reveal--answer">
				<summary><span>✅ Correct Answer dekho</span><span class="sg-pyq-reveal__icon" aria-hidden="true"></span></summary>
				<div class="sg-pyq-reveal__body">
					<?php echo $question['answer_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content. ?>
				</div>
		</details>
		<?php endif; ?>

		<?php if ( ts_sg_gate_set_html_has_content( $question['solution_html'] ) ) : ?>
			<details class="sg-pyq-reveal sg-pyq-reveal--solution">
				<summary><span>🧠 Step-by-step Solution</span><span class="sg-pyq-reveal__icon" aria-hidden="true"></span></summary>
				<div class="sg-pyq-reveal__body">
					<?php echo $question['solution_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content. ?>
				</div>
		</details>
		<?php endif; ?>
	</article>
	<?php
}

function ts_sg_gate_set_get_direct_sets( $term_id ) {
	static $cache = array();

	$term_id = (int) $term_id;

	if ( ! isset( $cache[ $term_id ] ) ) {
		$cache[ $term_id ] = function_exists( 'ts_cs_course_get_term_posts' )
			? ts_cs_course_get_term_posts( $term_id, TS_SG_GATE_PT, false )
			: array();
	}

	return $cache[ $term_id ];
}

function ts_sg_gate_set_collect_sequence( $term_id, &$seen_posts = array(), &$seen_terms = array() ) {
	$term_id = (int) $term_id;

	if ( isset( $seen_terms[ $term_id ] ) ) {
		return array();
	}

	$seen_terms[ $term_id ] = true;
	$sequence               = array();

	foreach ( ts_sg_gate_set_get_direct_sets( $term_id ) as $post ) {
		if ( ! isset( $seen_posts[ $post->ID ] ) ) {
			$seen_posts[ $post->ID ] = true;
			$sequence[]              = $post;
		}
	}

	if ( function_exists( 'ts_cs_course_get_children' ) ) {
		foreach ( ts_cs_course_get_children( $term_id, false ) as $child ) {
			$sequence = array_merge(
				$sequence,
				ts_sg_gate_set_collect_sequence( (int) $child->term_id, $seen_posts, $seen_terms )
			);
		}
	}

	return $sequence;
}

function ts_sg_gate_set_render_prev_next( $sequence, $position, $syllabus_root ) {
	$previous = false !== $position && isset( $sequence[ $position - 1 ] ) ? $sequence[ $position - 1 ] : false;
	$next     = false !== $position && isset( $sequence[ $position + 1 ] ) ? $sequence[ $position + 1 ] : false;

	if ( ! $previous && ! $next && ! $syllabus_root ) {
		return;
	}
	?>
	<nav class="sg-note-prevnext sg-pyq-set-prevnext" aria-label="<?php esc_attr_e( 'Previous and next practice sets', 'ts-block-theme' ); ?>">
		<?php if ( $previous ) : ?>
			<a class="sg-note-prevnext__card" href="<?php echo esc_url( get_permalink( $previous ) ); ?>">
				<span>← Previous Set</span>
				<strong><?php echo esc_html( get_the_title( $previous ) ); ?></strong>
			</a>
		<?php endif; ?>

		<?php if ( $next ) : ?>
			<a class="sg-note-prevnext__card sg-note-prevnext__card--next" href="<?php echo esc_url( get_permalink( $next ) ); ?>">
				<span>Next Set →</span>
				<strong><?php echo esc_html( get_the_title( $next ) ); ?></strong>
			</a>
		<?php elseif ( $syllabus_root ) : ?>
			<?php $url = get_term_link( $syllabus_root ); ?>
			<?php if ( ! is_wp_error( $url ) ) : ?>
				<a class="sg-note-prevnext__card sg-note-prevnext__card--next" href="<?php echo esc_url( $url ); ?>">
					<span>Set Sequence Complete ✓</span>
					<strong>Back to <?php echo esc_html( $syllabus_root->name ); ?></strong>
				</a>
			<?php endif; ?>
		<?php endif; ?>
	</nav>
	<?php
}

function ts_sg_gate_set_render_concept_notes( $notes ) {
	if ( empty( $notes ) ) {
		return;
	}
	?>
	<aside class="sg-pyq-concept-notes">
		<div class="sg-pyq-concept-notes__head">
			<p>Concept revision</p>
			<h2>Isi topic ki notes</h2>
		</div>
		<div class="sg-pyq-concept-notes__list">
			<?php foreach ( array_slice( $notes, 0, 4 ) as $note ) : ?>
				<?php $url = get_permalink( $note ); ?>
				<?php if ( $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>">
						<span>📘</span>
						<strong><?php echo esc_html( get_the_title( $note ) ); ?></strong>
						<small>Padho →</small>
					</a>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</aside>
	<?php
}

function ts_sg_gate_set_render_concept_reminder( $notes ) {
	if ( empty( $notes ) ) {
		return;
	}

	$note = $notes[0];
	$url  = get_permalink( $note );

	if ( ! $url ) {
		return;
	}
	?>
	<aside class="sg-pyq-concept-reminder">
		<div>
			<p>Concept weak lag raha hai?</p>
			<h3><?php echo esc_html( get_the_title( $note ) ); ?></h3>
			<span>Note ko 5 minute revise karke agle questions dobara try karo.</span>
		</div>
		<a class="sg-pyq-concept-reminder__btn" href="<?php echo esc_url( $url ); ?>">Concept Note Padho →</a>
	</aside>
	<?php
}

/**
 * Single Practice Set shortcode.
 */
function ts_sg_gate_render_set_shortcode() {
	static $rendering = false;

	if ( $rendering || ! ts_sg_gate_render_is_ready() || ! is_singular( TS_SG_GATE_PT ) ) {
		return '';
	}

	$post = get_post();

	if ( ! $post || TS_SG_GATE_PT !== $post->post_type ) {
		return '';
	}

	$rendering = true;
	ob_start();
	ts_sg_gate_set_render_page( $post );
	$html      = ob_get_clean();
	$rendering = false;

	return $html;
}

function ts_sg_gate_set_render_page( $post ) {
	$primary_term = function_exists( 'ts_cs_course_get_primary_term' )
		? ts_cs_course_get_primary_term( $post->ID )
		: false;
	$path         = $primary_term && function_exists( 'ts_cs_course_get_path' )
		? ts_cs_course_get_path( $primary_term )
		: array();

	$syllabus_root = false;
	if ( $primary_term && function_exists( 'ts_cs_note_get_syllabus_root' ) ) {
		$syllabus_root = ts_cs_note_get_syllabus_root( $primary_term, $path );
	}

	$note_sequence = $syllabus_root && function_exists( 'ts_cs_note_collect_sequence' )
		? ts_cs_note_collect_sequence( (int) $syllabus_root->term_id )
		: array();

	$related_notes = $primary_term && function_exists( 'ts_cs_course_get_term_posts' )
		? ts_cs_course_get_term_posts( (int) $primary_term->term_id, 'ece_note', false )
		: array();

	$seen_set_posts = array();
	$seen_set_terms = array();
	$set_sequence   = $syllabus_root
		? ts_sg_gate_set_collect_sequence( (int) $syllabus_root->term_id, $seen_set_posts, $seen_set_terms )
		: array();
	$set_ids        = array_map( 'intval', wp_list_pluck( $set_sequence, 'ID' ) );
	$set_position   = array_search( (int) $post->ID, $set_ids, true );

	$parsed         = ts_sg_gate_set_split_content( $post );
	$questions      = $parsed['questions'];
	$question_count = count( $questions );
	$year_range     = ts_sg_gate_set_get_year_range( $post, true );

	if ( ! $question_count ) {
		$question_count = ts_sg_gate_set_get_question_count( $post );
	}

	$kicker_parts = array( 'GATE PYQ Practice Set' );
	if ( $syllabus_root ) {
		$kicker_parts[] = $syllabus_root->name;
	}
	if ( $primary_term && ( ! $syllabus_root || (int) $primary_term->term_id !== (int) $syllabus_root->term_id ) ) {
		$kicker_parts[] = $primary_term->name;
	}
	?>
	<section class="sg-note-page sg-pyq-set-page">
		<div class="sg-note-wrap">
			<?php
			if ( function_exists( 'ts_cs_note_render_breadcrumbs' ) ) {
				ts_cs_note_render_breadcrumbs( $post, $path );
			} else {
				?>
				<nav class="sg-note-breadcrumbs" aria-label="<?php esc_attr_e( 'PYQ set breadcrumb', 'ts-block-theme' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
					<span aria-hidden="true">›</span>
					<a href="<?php echo esc_url( get_post_type_archive_link( TS_SG_GATE_PT ) ); ?>">GATE PYQ Sets</a>
					<span aria-hidden="true">›</span>
					<span aria-current="page"><?php echo esc_html( get_the_title( $post ) ); ?></span>
				</nav>
				<?php
			}
			?>

			<div class="sg-note-layout sg-pyq-set-layout">
				<aside class="sg-note-sidebar">
					<?php
					if ( function_exists( 'ts_cs_note_render_sidebar' ) ) {
						ts_cs_note_render_sidebar( $post, $syllabus_root, $path, $note_sequence, false );
					}
					?>
				</aside>

				<article class="sg-note-article sg-pyq-set-article">
					<header class="sg-note-article__head sg-pyq-set-head">
						<p class="sg-note-kicker"><?php echo esc_html( implode( ' • ', $kicker_parts ) ); ?></p>
						<h1><?php echo esc_html( get_the_title( $post ) ); ?></h1>

						<?php if ( has_excerpt( $post ) ) : ?>
							<p class="sg-note-lead"><?php echo esc_html( get_the_excerpt( $post ) ); ?></p>
						<?php endif; ?>

						<div class="sg-note-meta sg-pyq-set-meta">
							<span>🏆 <?php echo esc_html( sprintf( '%d Questions', $question_count ) ); ?></span>
							<?php if ( '' !== $year_range ) : ?>
								<span>GATE <?php echo esc_html( $year_range ); ?></span>
							<?php endif; ?>
							<span>Answer + Solution</span>
							<span>Updated: <?php echo esc_html( get_the_modified_date( 'j M Y', $post ) ); ?></span>
						</div>
					</header>

					<?php if ( has_post_thumbnail( $post ) ) : ?>
						<figure class="sg-note-featured">
							<?php echo get_the_post_thumbnail( $post, 'large', array( 'alt' => esc_attr( get_the_title( $post ) ) ) ); ?>
							<?php $caption = get_the_post_thumbnail_caption( $post ); ?>
							<?php if ( $caption ) : ?>
								<figcaption><?php echo esc_html( $caption ); ?></figcaption>
							<?php endif; ?>
						</figure>
					<?php endif; ?>

					<div class="sg-note-article__content sg-pyq-set-content">
						<?php if ( ! empty( $questions ) ) : ?>
							<?php if ( ts_sg_gate_set_html_has_content( $parsed['intro_html'] ) ) : ?>
								<div class="sg-pyq-set-intro">
									<?php echo $parsed['intro_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content. ?>
								</div>
							<?php endif; ?>

							<aside class="sg-pyq-how">
								<div class="sg-pyq-how__copy">
									<h2>Practice ka sahi tareeka</h2>
									<p>Seedha answer mat kholo — pehle rough copy par khud solve karo.</p>
								</div>
								<div class="sg-pyq-how__steps">
									<span><strong>1</strong> Khud solve karo</span>
									<span><strong>2</strong> Answer check karo</span>
									<span><strong>3</strong> Solution samjho</span>
								</div>
							</aside>

							<?php if ( count( $questions ) > 1 ) : ?>
								<nav class="sg-pyq-qnav" aria-label="<?php esc_attr_e( 'Question navigation', 'ts-block-theme' ); ?>">
									<strong>Questions:</strong>
									<div>
										<?php foreach ( $questions as $index => $question ) : ?>
											<a href="#q-<?php echo esc_attr( (string) ( $index + 1 ) ); ?>">Q<?php echo esc_html( (string) ( $index + 1 ) ); ?></a>
										<?php endforeach; ?>
									</div>
								</nav>
							<?php endif; ?>

							<?php foreach ( $questions as $index => $question ) : ?>
								<?php ts_sg_gate_set_render_question( $question, $index + 1 ); ?>
								<?php
								if ( 2 === $index && count( $questions ) > 4 ) {
									ts_sg_gate_set_render_concept_reminder( $related_notes );
								}
								?>
							<?php endforeach; ?>

							<?php if ( ts_sg_gate_set_html_has_content( $parsed['outro_html'] ) ) : ?>
								<div class="sg-pyq-set-outro">
									<?php echo $parsed['outro_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content. ?>
								</div>
							<?php endif; ?>
						<?php else : ?>
							<div class="sg-pyq-set-fallback-note">
								<strong>Practice questions abhi add ho rahe hain.</strong>
								<p>Is set ka content neeche normal article style me dikh raha hai.</p>
							</div>
							<?php echo $parsed['full_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content. ?>
						<?php endif; ?>
					</div>

					<?php ts_sg_gate_set_render_concept_notes( $related_notes ); ?>
					<?php ts_sg_gate_set_render_prev_next( $set_sequence, $set_position, $syllabus_root ); ?>

					<aside class="sg-note-article-cta sg-pyq-set-cta">
						<div>
							<h2>Set complete kar liya? 🎉</h2>
							<p>Ab isi topic ki note revise karo ya dusra GATE PYQ practice set try karo.</p>
						</div>
						<a class="sg-note-btn sg-note-btn--light" href="<?php echo esc_url( get_post_type_archive_link( TS_SG_GATE_PT ) ); ?>">All PYQ Sets</a>
					</aside>
				</article>
			</div>
		</div>
	</section>
	<?php
}
