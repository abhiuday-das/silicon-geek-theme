<?php
/**
 * Silicon Geek — Single Note Page (Step 3).
 *
 * NEW FILE:
 * wp-content/themes/ts-block-theme/inc/custom-post-type/ece-note-render.php
 *
 * Load it from functions.php immediately after ece-course-render.php:
 * require_once get_template_directory() . '/inc/custom-post-type/ece-note-render.php';
 *
 * Provides [sg_note_page], used by templates/single-ece_note.html.
 *
 * Layout:
 *  - Left: complete Subject syllabus (Units → Topics → Notes)
 *  - Centre: the current Note
 *  - Bottom: Previous / Next + Continue Learning across the full
 *    ordered Subject sequence
 *
 * Everything is generated from the Step 1 Course Structure data.
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'sg_note_page', 'ts_cs_note_page_shortcode' );

/**
 * Main shortcode. Renders one complete ECE Note page.
 */
function ts_cs_note_page_shortcode() {
	static $rendering = false;

	if ( $rendering || ! is_singular( 'ece_note' ) ) {
		return '';
	}

	$post = get_post();
	if ( ! $post || 'ece_note' !== $post->post_type ) {
		return '';
	}

	$rendering = true;
	ob_start();
	ts_cs_note_render_page( $post );
	$html      = ob_get_clean();
	$rendering = false;

	return $html;
}

/**
 * Cached direct-note lookup so the sidebar and sequence do not repeat
 * the same queries again and again on one page load.
 */
function ts_cs_note_get_direct_posts( $term_id ) {
	static $cache = array();

	$term_id = (int) $term_id;
	if ( ! isset( $cache[ $term_id ] ) ) {
		$cache[ $term_id ] = function_exists( 'ts_cs_course_get_term_posts' )
			? ts_cs_course_get_term_posts( $term_id, 'ece_note', false )
			: array();
	}

	return $cache[ $term_id ];
}

/**
 * Pick the folder whose full tree becomes the left syllabus menu.
 *
 * BTech ECE path: Track → Year → SUBJECT → Unit → Topic (use Subject)
 * Other paths:   Track → SUBJECT/MODULE → Topic       (use level 1)
 */
function ts_cs_note_get_syllabus_root( $primary_term, $path ) {
	if ( ! $primary_term || empty( $path ) ) {
		return $primary_term;
	}

	$root = $path[0];

	if ( 'btech-ece' === $root->slug && isset( $path[2] ) ) {
		return $path[2];
	}

	if ( isset( $path[1] ) ) {
		return $path[1];
	}

	return $primary_term;
}

/**
 * Flatten every note under a syllabus folder into learning order:
 * folder order → direct notes (menu_order) → child folders recursively.
 */
function ts_cs_note_collect_sequence( $term_id, &$seen_posts = array(), &$seen_terms = array() ) {
	$term_id = (int) $term_id;

	if ( isset( $seen_terms[ $term_id ] ) ) {
		return array();
	}

	$seen_terms[ $term_id ] = true;
	$sequence               = array();

	foreach ( ts_cs_note_get_direct_posts( $term_id ) as $post ) {
		if ( ! isset( $seen_posts[ $post->ID ] ) ) {
			$seen_posts[ $post->ID ] = true;
			$sequence[]              = $post;
		}
	}

	if ( function_exists( 'ts_cs_course_get_children' ) ) {
		foreach ( ts_cs_course_get_children( $term_id, false ) as $child ) {
			$sequence = array_merge(
				$sequence,
				ts_cs_note_collect_sequence( $child->term_id, $seen_posts, $seen_terms )
			);
		}
	}

	return $sequence;
}

/**
 * Prepare the note content and collect an automatic table of contents
 * from its H2/H3 headings (IDs are added when missing).
 */
function ts_cs_note_prepare_content( $post, &$toc ) {
	$toc     = array();
	$content = apply_filters( 'the_content', $post->post_content );
	$used    = array();

	$content = preg_replace_callback(
		'/<h([23])\b([^>]*)>(.*?)<\/h\1>/is',
		function ( $matches ) use ( &$toc, &$used ) {
			$level = (int) $matches[1];
			$attrs = $matches[2];
			$inner = $matches[3];
			$text  = trim( html_entity_decode( wp_strip_all_tags( $inner ), ENT_QUOTES, 'UTF-8' ) );

			if ( '' === $text ) {
				return $matches[0];
			}

			if ( preg_match( '/\bid\s*=\s*([\"\'])(.*?)\1/i', $attrs, $id_match ) ) {
				$id = $id_match[2];
			} else {
				$base = sanitize_title( $text );
				$id   = '' !== $base ? $base : 'section-' . ( count( $toc ) + 1 );
				$n    = 2;

				while ( isset( $used[ $id ] ) ) {
					$id = $base . '-' . $n;
					$n++;
				}

				$attrs .= ' id="' . esc_attr( $id ) . '"';
			}

			$used[ $id ] = true;
			$toc[]       = array(
				'level' => $level,
				'id'    => $id,
				'text'  => $text,
			);

			return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
		},
		$content
	);

	return $content;
}

/**
 * Compact breadcrumb: Home → course path → note title.
 */
function ts_cs_note_render_breadcrumbs( $post, $path ) {
	?>
	<nav class="sg-note-breadcrumbs" aria-label="<?php esc_attr_e( 'Note breadcrumb', 'ts-block-theme' ); ?>">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
		<?php if ( ! empty( $path ) ) : ?>
			<?php foreach ( $path as $term ) : ?>
				<?php $url = get_term_link( $term ); ?>
				<span aria-hidden="true">›</span>
				<?php if ( ! is_wp_error( $url ) ) : ?>
					<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $term->name ); ?></a>
				<?php else : ?>
					<span><?php echo esc_html( $term->name ); ?></span>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php else : ?>
			<span aria-hidden="true">›</span>
			<a href="<?php echo esc_url( home_url( '/notes/' ) ); ?>">Notes</a>
		<?php endif; ?>
		<span aria-hidden="true">›</span>
		<span aria-current="page"><?php echo esc_html( wp_trim_words( get_the_title( $post ), 8, '…' ) ); ?></span>
	</nav>
	<?php
}

/**
 * Ordered note links directly inside one folder.
 */
function ts_cs_note_render_tree_notes( $term, $current_post ) {
	$notes = ts_cs_note_get_direct_posts( $term->term_id );

	if ( empty( $notes ) ) {
		return;
	}
	?>
	<ul class="sg-note-tree__notes">
		<?php foreach ( $notes as $index => $note ) : ?>
			<?php $is_current = (int) $note->ID === (int) $current_post->ID; ?>
			<li>
				<a
					href="<?php echo esc_url( get_permalink( $note ) ); ?>"
					class="<?php echo $is_current ? 'is-current' : ''; ?>"
					<?php echo $is_current ? 'aria-current="page"' : ''; ?>
				>
					<span class="sg-note-tree__num"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
					<span class="sg-note-tree__label"><?php echo esc_html( get_the_title( $note ) ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * One folder inside the recursive syllabus tree.
 */
function ts_cs_note_render_tree_term( $term, $current_post, $path_ids, $level = 0 ) {
	$children = function_exists( 'ts_cs_course_get_children' )
		? ts_cs_course_get_children( $term->term_id, false )
		: array();
	$notes    = ts_cs_note_get_direct_posts( $term->term_id );
	$url      = get_term_link( $term );
	$is_open  = in_array( (int) $term->term_id, $path_ids, true );

	if ( empty( $children ) && empty( $notes ) ) {
		if ( ! is_wp_error( $url ) ) :
			?>
			<a class="sg-note-tree__folder-link sg-note-tree__level-<?php echo esc_attr( (string) $level ); ?>" href="<?php echo esc_url( $url ); ?>">
				<span><?php echo esc_html( $term->name ); ?></span>
				<span class="sg-note-tree__count"><span class="sg-note-tree__count-text">Soon</span></span>
			</a>
			<?php
		endif;
		return;
	}
	?>
	<details class="sg-note-tree__folder sg-note-tree__level-<?php echo esc_attr( (string) $level ); ?>" <?php echo $is_open ? 'open' : ''; ?>>
		<summary>
			<span class="sg-note-tree__folder-name"><?php echo esc_html( $term->name ); ?></span>
			<span class="sg-note-tree__count"><span class="sg-note-tree__count-text"><?php echo esc_html( ! empty( $notes ) ? sprintf( '%d notes', count( $notes ) ) : sprintf( '%d sections', count( $children ) ) ); ?></span></span>
		</summary>

		<div class="sg-note-tree__body">
			<?php if ( ! is_wp_error( $url ) ) : ?>
				<a class="sg-note-tree__overview" href="<?php echo esc_url( $url ); ?>">Folder overview ↗</a>
			<?php endif; ?>

			<?php ts_cs_note_render_tree_notes( $term, $current_post ); ?>

			<?php foreach ( $children as $child ) : ?>
				<?php ts_cs_note_render_tree_term( $child, $current_post, $path_ids, $level + 1 ); ?>
			<?php endforeach; ?>
		</div>
	</details>
	<?php
}

/**
 * Complete left sidebar (desktop card + mobile collapsible version).
 */
function ts_cs_note_render_sidebar( $post, $syllabus_root, $path, $sequence, $position ) {
	$path_ids = array();
	foreach ( (array) $path as $term ) {
		$path_ids[] = (int) $term->term_id;
	}

	$total    = count( $sequence );
	$percent  = $total > 0 && false !== $position ? (int) round( ( ( $position + 1 ) / $total ) * 100 ) : 0;
	$root_url = $syllabus_root ? get_term_link( $syllabus_root ) : home_url( '/notes/' );

	ob_start();
	?>
	<div class="sg-note-tree-card">
		<div class="sg-note-tree-card__head">
			<p class="sg-note-tree-card__eyebrow">Course Syllabus</p>
			<?php if ( $syllabus_root && ! is_wp_error( $root_url ) ) : ?>
				<a class="sg-note-tree-card__subject" href="<?php echo esc_url( $root_url ); ?>">
					<span class="sg-note-tree-card__subject-main">
						<span class="sg-note-tree-card__subject-kicker">Subject Home</span>
						<span class="sg-note-tree-card__subject-name"><?php echo esc_html( $syllabus_root->name ); ?></span>
					</span>
					<span class="sg-note-tree-card__subject-arrow" aria-hidden="true">↗</span>
				</a>
			<?php else : ?>
				<a class="sg-note-tree-card__subject" href="<?php echo esc_url( home_url( '/notes/' ) ); ?>">
					<span class="sg-note-tree-card__subject-main">
						<span class="sg-note-tree-card__subject-kicker">Notes Home</span>
						<span class="sg-note-tree-card__subject-name">All Notes</span>
					</span>
					<span class="sg-note-tree-card__subject-arrow" aria-hidden="true">↗</span>
				</a>
			<?php endif; ?>

			<?php if ( $total > 0 && false !== $position ) : ?>
				<div class="sg-note-progress" aria-label="<?php echo esc_attr( sprintf( 'Note %d of %d', $position + 1, $total ) ); ?>">
					<span style="width: <?php echo esc_attr( (string) $percent ); ?>%"></span>
				</div>
				<small><?php echo esc_html( sprintf( 'Note %d of %d in this subject', $position + 1, $total ) ); ?></small>
			<?php endif; ?>
		</div>

		<nav class="sg-note-tree" aria-label="<?php esc_attr_e( 'Course syllabus', 'ts-block-theme' ); ?>">
			<?php if ( $syllabus_root ) : ?>
				<?php ts_cs_note_render_tree_notes( $syllabus_root, $post ); ?>
				<?php foreach ( ts_cs_course_get_children( $syllabus_root->term_id, false ) as $child ) : ?>
					<?php ts_cs_note_render_tree_term( $child, $post, $path_ids, 0 ); ?>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="sg-note-tree__empty">Is note ko abhi koi Course Structure folder assign nahi hai.</p>
			<?php endif; ?>
		</nav>
	</div>
	<?php
	$tree_html = ob_get_clean();
	?>
	<div class="sg-note-sidebar__desktop"><?php echo $tree_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

	<details class="sg-note-sidebar__mobile">
		<summary>
			<span>📚 Course Menu</span>
			<?php if ( $syllabus_root ) : ?>
				<strong><?php echo esc_html( $syllabus_root->name ); ?></strong>
			<?php endif; ?>
		</summary>
		<?php echo $tree_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</details>
	<?php
}

/**
 * Previous / next cards from the complete subject sequence.
 */
function ts_cs_note_render_prev_next( $sequence, $position, $syllabus_root ) {
	$previous = false !== $position && isset( $sequence[ $position - 1 ] ) ? $sequence[ $position - 1 ] : false;
	$next     = false !== $position && isset( $sequence[ $position + 1 ] ) ? $sequence[ $position + 1 ] : false;
	?>
	<nav class="sg-note-prevnext" aria-label="<?php esc_attr_e( 'Previous and next notes', 'ts-block-theme' ); ?>">
		<?php if ( $previous ) : ?>
			<a class="sg-note-prevnext__card" href="<?php echo esc_url( get_permalink( $previous ) ); ?>">
				<span>← Previous Note</span>
				<strong><?php echo esc_html( get_the_title( $previous ) ); ?></strong>
			</a>
		<?php endif; ?>

		<?php if ( $next ) : ?>
			<a class="sg-note-prevnext__card sg-note-prevnext__card--next" href="<?php echo esc_url( get_permalink( $next ) ); ?>">
				<span>Next Note →</span>
				<strong><?php echo esc_html( get_the_title( $next ) ); ?></strong>
			</a>
		<?php elseif ( $syllabus_root ) : ?>
			<?php $url = get_term_link( $syllabus_root ); ?>
			<?php if ( ! is_wp_error( $url ) ) : ?>
				<a class="sg-note-prevnext__card sg-note-prevnext__card--next" href="<?php echo esc_url( $url ); ?>">
					<span>Subject Complete ✓</span>
					<strong>Back to <?php echo esc_html( $syllabus_root->name ); ?></strong>
				</a>
			<?php endif; ?>
		<?php endif; ?>
	</nav>
	<?php
}

/**
 * Continue Learning band inside the main article.
 * It intentionally mirrors the next item in the subject sequence and
 * remains visible on desktop, tablet and mobile.
 */
function ts_cs_note_render_continue_learning( $sequence, $position, $syllabus_root ) {
	$next = false !== $position && isset( $sequence[ $position + 1 ] ) ? $sequence[ $position + 1 ] : false;

	if ( ! $next && ! $syllabus_root ) {
		return;
	}
	?>
	<aside class="sg-note-continue" aria-label="<?php esc_attr_e( 'Continue learning', 'ts-block-theme' ); ?>">
		<div class="sg-note-continue__copy">
			<p class="sg-note-continue__eyebrow">Continue learning</p>
			<?php if ( $next ) : ?>
				<h2><?php echo esc_html( get_the_title( $next ) ); ?></h2>
				<p>Ye note khatm karke seedha agle subtopic par badho.</p>
			<?php else : ?>
				<h2>Subject complete 🎉</h2>
				<p>Tum is subject ke available notes ke end par ho. Revision ke liye subject home par wapas jao.</p>
			<?php endif; ?>
		</div>

		<?php if ( $next ) : ?>
			<a class="sg-note-btn sg-note-btn--primary" href="<?php echo esc_url( get_permalink( $next ) ); ?>">Next Note →</a>
		<?php elseif ( $syllabus_root ) : ?>
			<?php $url = get_term_link( $syllabus_root ); ?>
			<?php if ( ! is_wp_error( $url ) ) : ?>
				<a class="sg-note-btn sg-note-btn--primary" href="<?php echo esc_url( $url ); ?>">Subject Home</a>
			<?php endif; ?>
		<?php endif; ?>
	</aside>
	<?php
}

/**
 * Render the complete single-note layout.
 */
function ts_cs_note_render_page( $post ) {
	$primary_term  = function_exists( 'ts_cs_course_get_primary_term' )
		? ts_cs_course_get_primary_term( $post->ID )
		: false;
	$path          = $primary_term && function_exists( 'ts_cs_course_get_path' )
		? ts_cs_course_get_path( $primary_term )
		: array();
	$syllabus_root = $primary_term ? ts_cs_note_get_syllabus_root( $primary_term, $path ) : false;
	$sequence      = $syllabus_root ? ts_cs_note_collect_sequence( $syllabus_root->term_id ) : array();
	$sequence_ids  = array_map( 'intval', wp_list_pluck( $sequence, 'ID' ) );
	$position      = array_search( (int) $post->ID, $sequence_ids, true );
	$toc           = array();
	$content       = ts_cs_note_prepare_content( $post, $toc );
	$reading_time  = function_exists( 'ts_cs_course_index_reading_time' )
		? ts_cs_course_index_reading_time( $post )
		: 1;
	$topic_terms   = get_the_terms( $post, 'ece_topic' );
	?>
	<section class="sg-note-page">
		<div class="sg-note-wrap">
			<?php ts_cs_note_render_breadcrumbs( $post, $path ); ?>

			<div class="sg-note-layout">
				<aside class="sg-note-sidebar">
					<?php ts_cs_note_render_sidebar( $post, $syllabus_root, $path, $sequence, $position ); ?>
				</aside>

				<article class="sg-note-article">
					<header class="sg-note-article__head">
						<p class="sg-note-kicker">
							<?php
							if ( $syllabus_root ) {
								echo esc_html( $syllabus_root->name );
							}
							if ( $primary_term && $syllabus_root && (int) $primary_term->term_id !== (int) $syllabus_root->term_id ) {
								echo ' • ' . esc_html( $primary_term->name );
							}
							if ( ! $syllabus_root ) {
								echo esc_html__( 'ECE Note', 'ts-block-theme' );
							}
							?>
						</p>

						<h1><?php echo esc_html( get_the_title( $post ) ); ?></h1>

						<?php if ( has_excerpt( $post ) ) : ?>
							<p class="sg-note-lead"><?php echo esc_html( get_the_excerpt( $post ) ); ?></p>
						<?php endif; ?>

						<div class="sg-note-meta">
							<span>🕒 <?php echo esc_html( sprintf( '%d min read', $reading_time ) ); ?></span>
							<span>Updated: <?php echo esc_html( get_the_modified_date( 'j M Y', $post ) ); ?></span>
							<?php if ( false !== $position && ! empty( $sequence ) ) : ?>
								<span>Note <?php echo esc_html( (string) ( $position + 1 ) ); ?> of <?php echo esc_html( (string) count( $sequence ) ); ?></span>
							<?php endif; ?>
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

					<div class="sg-note-article__content">
						<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content. ?>
					</div>

					<?php if ( $topic_terms && ! is_wp_error( $topic_terms ) ) : ?>
						<div class="sg-note-tags">
							<span>Related topics:</span>
							<?php foreach ( $topic_terms as $topic_term ) : ?>
								<?php $topic_url = get_term_link( $topic_term ); ?>
								<?php if ( ! is_wp_error( $topic_url ) ) : ?>
									<a href="<?php echo esc_url( $topic_url ); ?>"><?php echo esc_html( $topic_term->name ); ?></a>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<?php ts_cs_note_render_prev_next( $sequence, $position, $syllabus_root ); ?>

					<?php ts_cs_note_render_continue_learning( $sequence, $position, $syllabus_root ); ?>

					<aside class="sg-note-article-cta">
						<div>
							<h2>Is topic ko project me use karna hai?</h2>
							<p> Sirf theory nahi — sahi components, circuit aur code approach ke saath practical guidance lo.</p>
						</div>
						<a class="sg-note-btn sg-note-btn--light" href="<?php echo esc_url( home_url( '/project-help/' ) ); ?>">Get Project Help</a>
					</aside>
				</article>

			</div>
		</div>
	</section>
	<?php
}
