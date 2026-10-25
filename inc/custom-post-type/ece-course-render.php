<?php
/**
 * Silicon Geek — Course Index Pages (Step 2).
 *
 * NEW FILE:
 * wp-content/themes/ts-block-theme/inc/custom-post-type/ece-course-render.php
 *
 * Load it from functions.php immediately after ece-course-structure.php:
 * require_once get_template_directory() . '/inc/custom-post-type/ece-course-render.php';
 *
 * Provides one Shortcode:
 *  [sg_course_index]          → current Course Structure term page
 *  [sg_course_index mode="roots"] → optional main Learn page with all tracks
 *
 * The renderer is data-driven:
 *  BTech ECE → Years
 *  Year      → Subjects
 *  Subject   → Units
 *  Unit      → Topics
 *  Topic     → ordered ECE Notes (subtopics)
 *
 * Add/edit/delete/draft a term or note in wp-admin and the output updates
 * automatically. Nothing in the header or templates needs to be edited.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'TS_CS_COURSE_TAX' ) ) {
	define( 'TS_CS_COURSE_TAX', 'ece_course' );
}

add_shortcode( 'sg_course_index', 'ts_cs_course_index_shortcode' );

/**
 * Shortcode entry point.
 */
function ts_cs_course_index_shortcode( $atts = array() ) {
	if ( ! taxonomy_exists( TS_CS_COURSE_TAX ) || ! function_exists( 'ts_cs_course_get_children' ) ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'mode' => 'auto',
			'term' => '',
		),
		is_array( $atts ) ? $atts : array(),
		'sg_course_index'
	);

	$term = null;

	if ( 'roots' !== $atts['mode'] ) {
		if ( '' !== $atts['term'] ) {
			$term = is_numeric( $atts['term'] )
				? get_term( (int) $atts['term'], TS_CS_COURSE_TAX )
				: get_term_by( 'slug', sanitize_title( $atts['term'] ), TS_CS_COURSE_TAX );
		} elseif ( is_tax( TS_CS_COURSE_TAX ) ) {
			$queried = get_queried_object();
			if ( $queried instanceof WP_Term && TS_CS_COURSE_TAX === $queried->taxonomy ) {
				$term = $queried;
			}
		}
	}

	ob_start();

	if ( $term && ! is_wp_error( $term ) ) {
		ts_cs_course_index_render_term( $term );
	} else {
		ts_cs_course_index_render_roots();
	}

	return ob_get_clean();
}

/**
 * Return the root-track term for any course term.
 */
function ts_cs_course_index_get_root( $term ) {
	$path = function_exists( 'ts_cs_course_get_path' ) ? ts_cs_course_get_path( $term ) : array();

	return ! empty( $path ) ? $path[0] : $term;
}

/**
 * Human-friendly level labels for each learning track.
 */
function ts_cs_course_index_level_label( $term, $plural = false ) {
	if ( ! $term ) {
		return $plural ? 'Course Sections' : 'Course Section';
	}

	$root  = ts_cs_course_index_get_root( $term );
	$depth = function_exists( 'ts_cs_course_get_depth' ) ? ts_cs_course_get_depth( $term ) : 0;

	$labels = array(
		'btech-ece'        => array(
			'singular' => array( 'Learning Track', 'Study Year', 'Subject', 'Unit', 'Topic', 'Topic' ),
			'plural'   => array( 'Learning Tracks', 'Study Years', 'Subjects', 'Units', 'Topics', 'Topics' ),
		),
		'gate-ec'          => array(
			'singular' => array( 'Learning Track', 'Subject', 'Topic', 'Lesson Folder', 'Lesson Folder' ),
			'plural'   => array( 'Learning Tracks', 'Subjects', 'Topics', 'Lesson Folders', 'Lesson Folders' ),
		),
		'embedded-systems' => array(
			'singular' => array( 'Learning Path', 'Module', 'Topic', 'Lesson Folder', 'Lesson Folder' ),
			'plural'   => array( 'Learning Paths', 'Modules', 'Topics', 'Lesson Folders', 'Lesson Folders' ),
		),
		'vlsi-design'      => array(
			'singular' => array( 'Learning Path', 'Module', 'Topic', 'Lesson Folder', 'Lesson Folder' ),
			'plural'   => array( 'Learning Paths', 'Modules', 'Topics', 'Lesson Folders', 'Lesson Folders' ),
		),
	);

	$set = isset( $labels[ $root->slug ] ) ? $labels[ $root->slug ] : array(
		'singular' => array( 'Learning Track', 'Course Section', 'Course Section', 'Course Section', 'Course Section' ),
		'plural'   => array( 'Learning Tracks', 'Course Sections', 'Course Sections', 'Course Sections', 'Course Sections' ),
	);

	$key   = $plural ? 'plural' : 'singular';
	$index = min( $depth, count( $set[ $key ] ) - 1 );

	return $set[ $key ][ $index ];
}

/**
 * Small visual icon for a course folder.
 */
function ts_cs_course_index_icon( $term ) {
	if ( ! $term ) {
		return '📚';
	}

	if ( 0 === (int) $term->parent ) {
		if ( 'gate-ec' === $term->slug ) {
			return '🏆';
		}

		if ( 'embedded-systems' === $term->slug ) {
			return '🤖';
		}

		if ( 'vlsi-design' === $term->slug ) {
			return '💠';
		}

		return '🎓';
	}

	$root  = ts_cs_course_index_get_root( $term );
	$depth = function_exists( 'ts_cs_course_get_depth' ) ? ts_cs_course_get_depth( $term ) : 0;

	if ( 'btech-ece' === $root->slug ) {
		$icons = array( 1 => '📅', 2 => '📘', 3 => '🧩', 4 => '🎯' );
		return isset( $icons[ $depth ] ) ? $icons[ $depth ] : '📂';
	}

	$icons = array( 1 => '📘', 2 => '🎯', 3 => '📝', 4 => '📝' );
	return isset( $icons[ $depth ] ) ? $icons[ $depth ] : '📂';
}

/**
 * Count published ECE Notes in a term.
 */
function ts_cs_course_index_count_notes( $term_id, $include_children = true ) {
	$query = new WP_Query(
		array(
			'post_type'      => 'ece_note',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
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

	return (int) $query->found_posts;
}

/**
 * Count direct child folders only.
 */
function ts_cs_course_index_count_children( $term_id ) {
	$children = get_terms(
		array(
			'taxonomy'   => TS_CS_COURSE_TAX,
			'parent'     => (int) $term_id,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	return is_array( $children ) ? count( $children ) : 0;
}

/**
 * Short plain-text description for cards.
 */
function ts_cs_course_index_term_excerpt( $term, $words = 18 ) {
	$text = isset( $term->description ) ? trim( wp_strip_all_tags( $term->description ) ) : '';

	if ( '' === $text ) {
		$text = 'Is section ke folders aur notes step-by-step explore karo.';
	}

	return wp_trim_words( $text, $words, '…' );
}

/**
 * Short excerpt for a note row.
 */
function ts_cs_course_index_post_excerpt( $post, $words = 24 ) {
	if ( has_excerpt( $post ) ) {
		$text = get_the_excerpt( $post );
	} else {
		$text = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
	}

	return wp_trim_words( $text, $words, '…' );
}

/**
 * Approximate reading time for a note row.
 */
function ts_cs_course_index_reading_time( $post ) {
	$text  = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
	$words = preg_split( '/\s+/u', trim( $text ) );
	$count = is_array( $words ) ? count( array_filter( $words ) ) : 0;

	return max( 1, (int) ceil( $count / 180 ) );
}

/**
 * Breadcrumb path: Home → Track → Year → Subject → Unit → Topic.
 */
function ts_cs_course_index_render_breadcrumbs( $term = null ) {
	?>
	<nav class="sg-course-breadcrumbs" aria-label="<?php esc_attr_e( 'Course breadcrumb', 'ts-block-theme' ); ?>">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
		<?php if ( $term ) : ?>
			<?php foreach ( ts_cs_course_get_path( $term ) as $path_term ) : ?>
				<span class="sg-course-breadcrumbs__sep" aria-hidden="true">›</span>
				<?php if ( (int) $path_term->term_id === (int) $term->term_id ) : ?>
					<span aria-current="page"><?php echo esc_html( $path_term->name ); ?></span>
				<?php else : ?>
					<?php $url = get_term_link( $path_term ); ?>
					<?php if ( ! is_wp_error( $url ) ) : ?>
						<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $path_term->name ); ?></a>
					<?php endif; ?>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php else : ?>
			<span class="sg-course-breadcrumbs__sep" aria-hidden="true">›</span>
			<span aria-current="page">Learn</span>
		<?php endif; ?>
	</nav>
	<?php
}

/**
 * One clickable course-folder card.
 */
function ts_cs_course_index_render_term_card( $term ) {
	$url = get_term_link( $term );
	if ( is_wp_error( $url ) ) {
		return;
	}

	$child_count = ts_cs_course_index_count_children( $term->term_id );
	$note_count  = ts_cs_course_index_count_notes( $term->term_id, true );
	$label       = ts_cs_course_index_level_label( $term, false );
	?>
	<a class="sg-course-card" href="<?php echo esc_url( $url ); ?>">
		<span class="sg-course-card__top">
			<span class="sg-course-card__icon" aria-hidden="true"><?php echo esc_html( ts_cs_course_index_icon( $term ) ); ?></span>
			<span class="sg-course-card__badge"><?php echo esc_html( $label ); ?></span>
		</span>
		<span class="sg-course-card__title"><?php echo esc_html( $term->name ); ?></span>
		<span class="sg-course-card__text"><?php echo esc_html( ts_cs_course_index_term_excerpt( $term ) ); ?></span>
		<span class="sg-course-card__meta">
			<?php if ( $child_count > 0 ) : ?>
				<span><?php echo esc_html( sprintf( '%d sections', $child_count ) ); ?></span>
			<?php endif; ?>
			<span><?php echo esc_html( sprintf( '%d notes', $note_count ) ); ?></span>
		</span>
		<span class="sg-course-card__action">Open <span aria-hidden="true">→</span></span>
	</a>
	<?php
}

/**
 * One numbered lesson/subtopic row.
 */
function ts_cs_course_index_render_lesson_row( $post, $index ) {
	$url = get_permalink( $post );
	if ( ! $url ) {
		return;
	}
	?>
	<li class="sg-course-lesson">
		<a class="sg-course-lesson__link" href="<?php echo esc_url( $url ); ?>">
			<span class="sg-course-lesson__number"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
			<span class="sg-course-lesson__body">
				<span class="sg-course-lesson__title"><?php echo esc_html( get_the_title( $post ) ); ?></span>
				<span class="sg-course-lesson__excerpt"><?php echo esc_html( ts_cs_course_index_post_excerpt( $post ) ); ?></span>
				<span class="sg-course-lesson__meta">
					<span>📝 Note</span>
					<span><?php echo esc_html( sprintf( '%d min read', ts_cs_course_index_reading_time( $post ) ) ); ?></span>
					<span><?php echo esc_html( get_the_date( 'j M Y', $post ) ); ?></span>
				</span>
			</span>
			<?php if ( has_post_thumbnail( $post ) ) : ?>
				<span class="sg-course-lesson__thumb">
					<?php echo get_the_post_thumbnail( $post, 'thumbnail', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
				</span>
			<?php endif; ?>
			<span class="sg-course-lesson__arrow" aria-hidden="true">→</span>
		</a>
	</li>
	<?php
}

/**
 * Bottom help band used on index pages.
 */
function ts_cs_course_index_render_help_band() {
	?>
	<aside class="sg-course-help">
		<div>
			<p class="sg-course-help__eyebrow">Stuck in a topic?</p>
			<h2>Apna doubt ya project topic bhejo — hum sahi rasta batayenge.</h2>
			<p>Notes padhkar shuru karo, aur practical project ya viva preparation me help chahiye ho to guidance ke liye connect karo.</p>
		</div>
		<div class="sg-course-help__actions">
			<a class="sg-course-btn sg-course-btn--light" href="<?php echo esc_url( home_url( '/project-help/' ) ); ?>">Get Project Help</a>
			<a class="sg-course-btn sg-course-btn--ghost" href="<?php echo esc_url( home_url( '/notes/' ) ); ?>">All Notes</a>
		</div>
	</aside>
	<?php
}

/**
 * Render a single course term:
 * child folder cards first, then notes assigned directly to this term.
 */
function ts_cs_course_index_render_term( $term ) {
	$children     = ts_cs_course_get_children( $term->term_id, false );
	$direct_notes = function_exists( 'ts_cs_course_get_term_posts' )
		? ts_cs_course_get_term_posts( $term->term_id, 'ece_note', false )
		: array();
	$path         = ts_cs_course_get_path( $term );
	$root         = ts_cs_course_index_get_root( $term );
	$depth        = ts_cs_course_get_depth( $term );
	$total_notes  = ts_cs_course_index_count_notes( $term->term_id, true );
	$child_label  = ! empty( $children ) ? ts_cs_course_index_level_label( $children[0], true ) : 'Course Sections';
	$term_label   = ts_cs_course_index_level_label( $term, false );
	$description  = trim( (string) $term->description );
	?>
	<section class="sg-course sg-course--term sg-course--depth-<?php echo esc_attr( (string) $depth ); ?> sg-course--root-<?php echo esc_attr( $root->slug ); ?>">
		<div class="sg-course-hero">
			<div class="sg-course-wrap">
				<?php ts_cs_course_index_render_breadcrumbs( $term ); ?>

				<div class="sg-course-hero__grid">
					<div class="sg-course-hero__main">
						<p class="sg-course-eyebrow">
							<span aria-hidden="true"><?php echo esc_html( ts_cs_course_index_icon( $term ) ); ?></span>
							<?php echo esc_html( $term_label ); ?>
						</p>
						<h1><?php echo esc_html( $term->name ); ?></h1>
						<?php if ( '' !== $description ) : ?>
							<div class="sg-course-hero__desc"><?php echo wp_kses_post( wpautop( $description ) ); ?></div>
						<?php else : ?>
							<p class="sg-course-hero__desc">Is section ko step-by-step padho. Har folder ke andar ordered notes milengi, taaki tum bina confuse hue agle topic par badh sako.</p>
						<?php endif; ?>

						<div class="sg-course-stats" aria-label="<?php esc_attr_e( 'Course statistics', 'ts-block-theme' ); ?>">
							<?php if ( ! empty( $children ) ) : ?>
								<span><strong><?php echo esc_html( (string) count( $children ) ); ?></strong> <?php echo esc_html( strtolower( $child_label ) ); ?></span>
							<?php endif; ?>
							<span><strong><?php echo esc_html( (string) $total_notes ); ?></strong> notes</span>
							<?php if ( ! empty( $direct_notes ) && empty( $children ) ) : ?>
								<span><strong><?php echo esc_html( (string) count( $direct_notes ) ); ?></strong> subtopics</span>
							<?php endif; ?>
						</div>

						<div class="sg-course-actions">
							<?php if ( ! empty( $children ) ) : ?>
								<a class="sg-course-btn sg-course-btn--primary" href="#sg-course-content">Explore <?php echo esc_html( $child_label ); ?></a>
							<?php elseif ( ! empty( $direct_notes ) ) : ?>
								<a class="sg-course-btn sg-course-btn--primary" href="<?php echo esc_url( get_permalink( $direct_notes[0] ) ); ?>">Start First Note →</a>
							<?php endif; ?>

							<?php if ( ! empty( $term->parent ) ) : ?>
								<?php $parent = get_term( (int) $term->parent, TS_CS_COURSE_TAX ); ?>
								<?php if ( $parent && ! is_wp_error( $parent ) ) : ?>
									<?php $parent_url = get_term_link( $parent ); ?>
									<?php if ( ! is_wp_error( $parent_url ) ) : ?>
										<a class="sg-course-btn sg-course-btn--dark-ghost" href="<?php echo esc_url( $parent_url ); ?>">↑ <?php echo esc_html( $parent->name ); ?></a>
									<?php endif; ?>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>

					<aside class="sg-course-path" aria-label="<?php esc_attr_e( 'Your learning path', 'ts-block-theme' ); ?>">
						<p class="sg-course-path__title">Your Learning Path</p>
						<ol>
							<?php foreach ( $path as $path_term ) : ?>
								<li class="<?php echo (int) $path_term->term_id === (int) $term->term_id ? 'is-current' : ''; ?>">
									<span><?php echo esc_html( ts_cs_course_index_level_label( $path_term, false ) ); ?></span>
									<strong><?php echo esc_html( $path_term->name ); ?></strong>
								</li>
							<?php endforeach; ?>
						</ol>
					</aside>
				</div>
			</div>
		</div>

		<div class="sg-course-wrap sg-course-content" id="sg-course-content">
			<?php if ( ! empty( $children ) ) : ?>
				<div class="sg-course-section-head">
					<p>Select a section</p>
					<h2><?php echo esc_html( $child_label ); ?></h2>
				</div>
				<div class="sg-course-grid">
					<?php foreach ( $children as $child ) : ?>
						<?php ts_cs_course_index_render_term_card( $child ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $direct_notes ) ) : ?>
				<div class="sg-course-section-head">
					<p><?php echo empty( $children ) ? 'Learn step-by-step' : 'Notes in this folder'; ?></p>
					<h2><?php echo empty( $children ) ? 'Subtopics' : 'Direct Notes'; ?></h2>
				</div>
				<ol class="sg-course-lessons">
					<?php foreach ( $direct_notes as $index => $post ) : ?>
						<?php ts_cs_course_index_render_lesson_row( $post, $index ); ?>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>

			<?php if ( empty( $children ) && empty( $direct_notes ) ) : ?>
				<div class="sg-course-empty">
					<span aria-hidden="true">🚧</span>
					<h2>Content is being prepared</h2>
					<p>Is section ke notes jald publish honge. Tab tak dusre topics explore karo ya apna requested topic humein bhejo.</p>
					<a class="sg-course-btn sg-course-btn--primary" href="<?php echo esc_url( home_url( '/notes/' ) ); ?>">Browse All Notes</a>
				</div>
			<?php endif; ?>

			<?php ts_cs_course_index_render_help_band(); ?>
		</div>
	</section>
	<?php
}

/**
 * Render all top-level tracks for an optional /learn/ page.
 */
function ts_cs_course_index_render_roots() {
	$roots       = ts_cs_course_get_children( 0, false );
	$total_notes = 0;

	foreach ( $roots as $root ) {
		$total_notes += ts_cs_course_index_count_notes( $root->term_id, true );
	}
	?>
	<section class="sg-course sg-course--roots">
		<div class="sg-course-hero">
			<div class="sg-course-wrap">
				<?php ts_cs_course_index_render_breadcrumbs( null ); ?>
				<div class="sg-course-hero__main sg-course-hero__main--wide">
					<p class="sg-course-eyebrow">📚 Silicon Geek Learning Paths</p>
					<h1>Learn ECE Step-by-Step</h1>
					<p class="sg-course-hero__desc">Apna goal choose karo — BTech semester notes, GATE EC, Embedded Systems ya VLSI. Har path ke andar subjects, topics aur notes sahi order me milenge.</p>
					<div class="sg-course-stats">
						<span><strong><?php echo esc_html( (string) count( $roots ) ); ?></strong> learning paths</span>
						<span><strong><?php echo esc_html( (string) $total_notes ); ?></strong> notes</span>
					</div>
				</div>
			</div>
		</div>

		<div class="sg-course-wrap sg-course-content" id="sg-course-content">
			<div class="sg-course-section-head">
				<p>Choose your path</p>
				<h2>Learning Tracks</h2>
			</div>

			<?php if ( ! empty( $roots ) ) : ?>
				<div class="sg-course-grid sg-course-grid--roots">
					<?php foreach ( $roots as $root ) : ?>
						<?php ts_cs_course_index_render_term_card( $root ); ?>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="sg-course-empty">
					<span aria-hidden="true">📚</span>
					<h2>Learning paths are being prepared</h2>
					<p>Course Structure folders add hote hi ye page automatic update ho jayega.</p>
				</div>
			<?php endif; ?>

			<?php ts_cs_course_index_render_help_band(); ?>
		</div>
	</section>
	<?php
}
