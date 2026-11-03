<?php
/**
 * Silicon Geek — ECE Projects Archive + Single Project Guide (Step 6).
 *
 * NEW FILE:
 * wp-content/themes/ts-block-theme/inc/custom-post-type/ece-project-render.php
 *
 * Load it from functions.php after ece-gate-render.php:
 * require_once get_template_directory() . '/inc/custom-post-type/ece-project-render.php';
 *
 * Provides:
 *  - [sg_project_index]  /projects/ filterable archive
 *  - [sg_project_page]   single project build guide
 *
 * Project cards use the v4 locked media frame: one 16:10 media box,
 * the difficulty badge at its top-left, and the card body immediately below.
 *
 * Authoring model (fields-free, like Step 5R):
 *  - One ECE Project post is one complete build guide.
 *  - Author only selects Subject / Difficulty / Study Year / Topics once.
 *  - Guide is written with normal H2 headings:
 *      Project Overview, Components Required, Circuit & Connections,
 *      Code, Build Steps, Working & Testing, Viva Questions, Cost & Kit.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'TS_SG_PROJECT_PT' ) ) {
	define( 'TS_SG_PROJECT_PT', 'ece_project' );
}

if ( ! defined( 'TS_SG_PROJECT_SUBJECT_TAX' ) ) {
	define( 'TS_SG_PROJECT_SUBJECT_TAX', 'ece_subject' );
}

if ( ! defined( 'TS_SG_PROJECT_DIFFICULTY_TAX' ) ) {
	define( 'TS_SG_PROJECT_DIFFICULTY_TAX', 'ece_difficulty' );
}

if ( ! defined( 'TS_SG_PROJECT_YEAR_TAX' ) ) {
	define( 'TS_SG_PROJECT_YEAR_TAX', 'ece_year' );
}

if ( ! defined( 'TS_SG_PROJECT_TOPIC_TAX' ) ) {
	define( 'TS_SG_PROJECT_TOPIC_TAX', 'ece_topic' );
}

add_shortcode( 'sg_project_index', 'ts_sg_project_render_archive_shortcode' );
add_shortcode( 'sg_project_page', 'ts_sg_project_render_single_shortcode' );

/**
 * Project order field (10, 20, 30...) for curated archive ordering.
 * This is optional for the author; date/title remain fallbacks.
 */
add_action( 'init', 'ts_sg_project_add_order_support', 20 );

function ts_sg_project_add_order_support() {
	if ( post_type_exists( TS_SG_PROJECT_PT ) ) {
		add_post_type_support( TS_SG_PROJECT_PT, 'page-attributes' );
	}
}

function ts_sg_project_is_ready() {
	return post_type_exists( TS_SG_PROJECT_PT );
}

function ts_sg_project_archive_url() {
	$url = get_post_type_archive_link( TS_SG_PROJECT_PT );

	return $url ? $url : home_url( '/projects/' );
}

function ts_sg_project_help_url() {
	return home_url( '/project-help/' );
}

/**
 * WhatsApp deep link from Appearance → Customize → Site Contact Info.
 * If no number is saved, fall back to the Project Help page.
 */
function ts_sg_project_whatsapp_url( $post = null ) {
	$number = preg_replace( '/\D/', '', (string) get_theme_mod( 'site_whatsapp_number', '' ) );

	if ( strlen( $number ) === 10 ) {
		$number = '91' . $number;
	}

	if ( ! $number ) {
		return ts_sg_project_help_url();
	}

	$message = 'Hi Silicon Geek, mujhe ECE project me help chahiye.';

	if ( $post ) {
		$post = is_object( $post ) ? $post : get_post( (int) $post );

		if ( $post ) {
			$message = sprintf(
				'Hi Silicon Geek, mujhe is project me help chahiye: %s (%s)',
				get_the_title( $post ),
				get_permalink( $post )
			);
		}
	}

	return 'https://wa.me/' . $number . '?text=' . rawurlencode( $message );
}

function ts_sg_project_get_terms( $post_id, $taxonomy ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}

	$terms = get_the_terms( (int) $post_id, $taxonomy );

	return is_array( $terms ) ? $terms : array();
}

function ts_sg_project_first_term( $post_id, $taxonomy ) {
	$terms = ts_sg_project_get_terms( $post_id, $taxonomy );

	return ! empty( $terms ) ? $terms[0] : false;
}

function ts_sg_project_term_names( $post_id, $taxonomy ) {
	$names = array();

	foreach ( ts_sg_project_get_terms( $post_id, $taxonomy ) as $term ) {
		$names[] = $term->name;
	}

	return $names;
}

function ts_sg_project_filtered_archive_url( $args = array() ) {
	$url = ts_sg_project_archive_url();

	if ( ! empty( $args ) ) {
		$url = add_query_arg( $args, $url );
	}

	return $url . '#sg-projects-list';
}

function ts_sg_project_icon( $post ) {
	$title   = strtolower( get_the_title( $post ) );
	$subject = ts_sg_project_first_term( $post->ID, TS_SG_PROJECT_SUBJECT_TAX );
	$slug    = $subject ? $subject->slug : '';

	$haystack = $title . ' ' . $slug;

	if ( false !== strpos( $haystack, 'robot' ) || false !== strpos( $haystack, 'car' ) ) {
		return '🤖';
	}

	if ( false !== strpos( $haystack, 'agriculture' ) || false !== strpos( $haystack, 'plant' ) || false !== strpos( $haystack, 'soil' ) ) {
		return '🌱';
	}

	if ( false !== strpos( $haystack, 'verilog' ) || false !== strpos( $haystack, 'vlsi' ) || false !== strpos( $haystack, 'fpga' ) || false !== strpos( $haystack, 'uart' ) ) {
		return '💠';
	}

	if ( false !== strpos( $haystack, 'iot' ) || false !== strpos( $haystack, 'esp32' ) || false !== strpos( $haystack, 'wifi' ) ) {
		return '🌐';
	}

	if ( false !== strpos( $haystack, 'solar' ) || false !== strpos( $haystack, 'power' ) ) {
		return '☀️';
	}

	if ( false !== strpos( $haystack, 'health' ) || false !== strpos( $haystack, 'heart' ) || false !== strpos( $haystack, 'medical' ) ) {
		return '❤️';
	}

	if ( false !== strpos( $haystack, 'home' ) || false !== strpos( $haystack, 'automation' ) ) {
		return '🏠';
	}

	if ( false !== strpos( $haystack, 'communication' ) || false !== strpos( $haystack, 'antenna' ) || false !== strpos( $haystack, 'rf' ) ) {
		return '📡';
	}

	return '🛠️';
}

function ts_sg_project_reading_time( $post ) {
	if ( function_exists( 'ts_cs_course_index_reading_time' ) ) {
		return ts_cs_course_index_reading_time( $post );
	}

	$text  = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
	$words = preg_split( '/\s+/u', trim( $text ) );
	$count = is_array( $words ) ? count( array_filter( $words ) ) : 0;

	return max( 1, (int) ceil( $count / 180 ) );
}

function ts_sg_project_excerpt( $post, $words = 26 ) {
	if ( has_excerpt( $post ) ) {
		return wp_trim_words( get_the_excerpt( $post ), $words, '…' );
	}

	$content = (string) $post->post_content;
	$text    = $content;

	if ( preg_match( '/<h2\b[^>]*>/is', $content, $match, PREG_OFFSET_CAPTURE ) ) {
		$intro = substr( $content, 0, (int) $match[0][1] );
		if ( '' !== trim( wp_strip_all_tags( $intro ) ) ) {
			$text = $intro;
		}
	}

	$text = trim( wp_strip_all_tags( strip_shortcodes( $text ) ) );

	if ( '' === $text ) {
		$text = 'Is project ka complete circuit, code, build steps aur viva guide dekho.';
	}

	return wp_trim_words( $text, $words, '…' );
}

/* ------------------------------------------------------------
   Archive
   ------------------------------------------------------------ */

function ts_sg_project_get_filters() {
	return array(
		'subject'    => isset( $_GET['project_subject'] ) ? sanitize_title( wp_unslash( $_GET['project_subject'] ) ) : '',
		'difficulty' => isset( $_GET['project_difficulty'] ) ? sanitize_title( wp_unslash( $_GET['project_difficulty'] ) ) : '',
		'year'       => isset( $_GET['project_year'] ) ? sanitize_title( wp_unslash( $_GET['project_year'] ) ) : '',
		'search'     => isset( $_GET['project_search'] ) ? sanitize_text_field( wp_unslash( $_GET['project_search'] ) ) : '',
		'page'       => isset( $_GET['project_page'] ) ? max( 1, absint( wp_unslash( $_GET['project_page'] ) ) ) : 1,
	);
}

function ts_sg_project_filters_active( $filters ) {
	return '' !== $filters['subject']
		|| '' !== $filters['difficulty']
		|| '' !== $filters['year']
		|| '' !== $filters['search'];
}

function ts_sg_project_filter_query_args( $filters, $page = 0 ) {
	$args = array();

	if ( '' !== $filters['subject'] ) {
		$args['project_subject'] = $filters['subject'];
	}

	if ( '' !== $filters['difficulty'] ) {
		$args['project_difficulty'] = $filters['difficulty'];
	}

	if ( '' !== $filters['year'] ) {
		$args['project_year'] = $filters['year'];
	}

	if ( '' !== $filters['search'] ) {
		$args['project_search'] = $filters['search'];
	}

	if ( $page > 1 ) {
		$args['project_page'] = (int) $page;
	}

	return $args;
}

function ts_sg_project_fetch_projects( $filters, $per_page = 12 ) {
	$per_page  = max( 1, (int) $per_page );
	$page      = max( 1, (int) $filters['page'] );
	$tax_query = array( 'relation' => 'AND' );

	$tax_map = array(
		'subject'    => TS_SG_PROJECT_SUBJECT_TAX,
		'difficulty' => TS_SG_PROJECT_DIFFICULTY_TAX,
		'year'       => TS_SG_PROJECT_YEAR_TAX,
	);

	foreach ( $tax_map as $key => $taxonomy ) {
		if ( '' !== $filters[ $key ] && taxonomy_exists( $taxonomy ) ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => array( $filters[ $key ] ),
			);
		}
	}

	$args = array(
		'post_type'      => TS_SG_PROJECT_PT,
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'modified'   => 'DESC',
			'title'      => 'ASC',
		),
	);

	if ( count( $tax_query ) > 1 ) {
		$args['tax_query'] = $tax_query;
	}

	if ( '' !== $filters['search'] ) {
		$args['s'] = $filters['search'];
	}

	$query     = new WP_Query( $args );
	$max_pages = max( 1, (int) $query->max_num_pages );

	if ( $page > $max_pages && $query->found_posts ) {
		$args['paged'] = $max_pages;
		$query         = new WP_Query( $args );
		$page          = $max_pages;
		$max_pages     = max( 1, (int) $query->max_num_pages );
	}

	return array(
		'posts'     => $query->posts,
		'total'     => (int) $query->found_posts,
		'page'      => $page,
		'max_pages' => $max_pages,
		'per_page'  => $per_page,
	);
}

function ts_sg_project_filter_options( $taxonomy, $selected ) {
	static $project_ids = null;

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return;
	}

	if ( null === $project_ids ) {
		$project_ids = get_posts(
			array(
				'post_type'      => TS_SG_PROJECT_PT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
	}

	$terms = array();

	if ( ! empty( $project_ids ) ) {
		$project_terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'object_ids' => array_map( 'absint', $project_ids ),
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_array( $project_terms ) ) {
			$terms = $project_terms;
		}
	}

	// Keep an old/bookmarked filter visible even if its last project is drafted.
	if ( '' !== $selected ) {
		$selected_exists = false;

		foreach ( $terms as $term ) {
			if ( $selected === $term->slug ) {
				$selected_exists = true;
				break;
			}
		}

		if ( ! $selected_exists ) {
			$selected_term = get_term_by( 'slug', $selected, $taxonomy );
			if ( $selected_term && ! is_wp_error( $selected_term ) ) {
				$terms[] = $selected_term;
			}
		}
	}

	foreach ( $terms as $term ) {
		printf(
			'<option value="%1$s" %2$s>%3$s</option>',
			esc_attr( $term->slug ),
			selected( $selected, $term->slug, false ),
			esc_html( $term->name )
		);
	}
}

function ts_sg_project_render_filter_form( $filters ) {
	$action_url = ts_sg_project_archive_url();
	?>
	<form class="sg-projects-filters" method="get" action="<?php echo esc_url( $action_url ); ?>" role="search">
		<div class="sg-projects-field">
			<label for="sg-project-subject">Subject / Domain</label>
			<select id="sg-project-subject" name="project_subject">
				<option value="">All Subjects</option>
				<?php ts_sg_project_filter_options( TS_SG_PROJECT_SUBJECT_TAX, $filters['subject'] ); ?>
			</select>
		</div>

		<div class="sg-projects-field">
			<label for="sg-project-difficulty">Difficulty</label>
			<select id="sg-project-difficulty" name="project_difficulty">
				<option value="">All Levels</option>
				<?php ts_sg_project_filter_options( TS_SG_PROJECT_DIFFICULTY_TAX, $filters['difficulty'] ); ?>
			</select>
		</div>

		<div class="sg-projects-field">
			<label for="sg-project-year">Study Year</label>
			<select id="sg-project-year" name="project_year">
				<option value="">All Years</option>
				<?php ts_sg_project_filter_options( TS_SG_PROJECT_YEAR_TAX, $filters['year'] ); ?>
			</select>
		</div>

		<div class="sg-projects-field sg-projects-field--search">
			<label for="sg-project-search">Search Project</label>
			<input id="sg-project-search" type="search" name="project_search" value="<?php echo esc_attr( $filters['search'] ); ?>" placeholder="ESP32, robot, Verilog, sensor…" />
		</div>

		<div class="sg-projects-filter-actions">
			<button class="sg-course-btn sg-course-btn--primary" type="submit">Apply Filters</button>
			<?php if ( ts_sg_project_filters_active( $filters ) ) : ?>
				<a class="sg-projects-clear" href="<?php echo esc_url( $action_url ); ?>#sg-projects-list">Clear</a>
			<?php endif; ?>
		</div>
	</form>
	<?php
}

function ts_sg_project_render_card( $post ) {
	$url = get_permalink( $post );

	if ( ! $url ) {
		return;
	}

	$subject    = ts_sg_project_first_term( $post->ID, TS_SG_PROJECT_SUBJECT_TAX );
	$difficulty = ts_sg_project_first_term( $post->ID, TS_SG_PROJECT_DIFFICULTY_TAX );
	$year       = ts_sg_project_first_term( $post->ID, TS_SG_PROJECT_YEAR_TAX );
	$topics     = array_slice( ts_sg_project_get_terms( $post->ID, TS_SG_PROJECT_TOPIC_TAX ), 0, 3 );

	$difficulty_slug  = $difficulty ? sanitize_html_class( $difficulty->slug ) : '';
	$difficulty_color = '#7c2d12';

	if ( 'easy' === $difficulty_slug ) {
		$difficulty_color = '#166534';
	} elseif ( in_array( $difficulty_slug, array( 'intermediate', 'medium' ), true ) ) {
		$difficulty_color = '#92400e';
	} elseif ( in_array( $difficulty_slug, array( 'difficult', 'hard', 'advanced' ), true ) ) {
		$difficulty_color = '#991b1b';
	}

	/*
	 * Card Media v4 — structural lock.
	 *
	 * The old media/placeholder/level classes are intentionally not reused here.
	 * Those classes now have several stacked hot-fix layers in the theme CSS.
	 * This card instead prints one dedicated frame, and the critical geometry
	 * is inline so an older card rule cannot turn the image area into two boxes:
	 *
	 *   link (auto height)
	 *     └─ frame (the only 16:10 media box)
	 *          ├─ featured image OR placeholder (absolute inset: 0)
	 *          └─ difficulty badge (absolute top-left)
	 *   card body (starts immediately after the link)
	 */
	$media_link_style        = 'display:block!important;flex:0 0 auto!important;width:100%!important;height:auto!important;min-height:0!important;max-height:none!important;margin:0!important;padding:0!important;overflow:hidden!important;background:transparent!important;line-height:0!important;text-decoration:none!important;';
	$media_frame_style       = 'position:relative!important;display:block!important;width:100%!important;height:auto!important;min-height:0!important;max-height:none!important;margin:0!important;padding:0!important;overflow:hidden!important;aspect-ratio:16/10!important;background:#0b1226!important;line-height:1.3!important;';
	$media_image_style       = 'position:absolute!important;inset:0!important;display:block!important;width:100%!important;min-width:100%!important;max-width:none!important;height:100%!important;min-height:100%!important;max-height:none!important;margin:0!important;padding:0!important;border:0!important;border-radius:0!important;object-fit:cover!important;object-position:center!important;';
	$media_placeholder_style = 'position:absolute!important;inset:0!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;gap:10px!important;width:100%!important;height:100%!important;margin:0!important;padding:42px 18px 18px!important;box-sizing:border-box!important;overflow:hidden!important;background-color:#0b2239!important;background-image:radial-gradient(circle at 86% 12%,rgba(249,115,22,.42),transparent 150px),radial-gradient(circle at 8% 96%,rgba(6,182,212,.34),transparent 180px),linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(135deg,#0b1226,#12364d 58%,#431407)!important;background-size:auto,auto,32px 32px,32px 32px,auto!important;color:#fff!important;text-align:center!important;';
	$media_glyph_style       = 'display:flex!important;align-items:center!important;justify-content:center!important;width:58px!important;min-width:58px!important;max-width:58px!important;height:58px!important;min-height:58px!important;max-height:58px!important;margin:0 auto!important;padding:0!important;font-size:48px!important;line-height:1!important;text-align:center!important;filter:drop-shadow(0 10px 18px rgba(0,0,0,.28))!important;';
	$media_label_style       = 'display:inline-flex!important;align-items:center!important;justify-content:center!important;max-width:100%!important;margin:0 auto!important;padding:6px 11px 7px!important;box-sizing:border-box!important;border:1px solid rgba(255,255,255,.18)!important;border-radius:999px!important;background:rgba(255,255,255,.1)!important;color:#fff!important;font-size:15px!important;font-weight:900!important;line-height:1.3!important;text-align:center!important;';
	$media_badge_style       = 'position:absolute!important;top:12px!important;left:12px!important;right:auto!important;bottom:auto!important;z-index:5!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;width:auto!important;height:auto!important;min-height:32px!important;margin:0!important;padding:6px 11px 7px!important;box-sizing:border-box!important;border-radius:999px!important;background:rgba(255,255,255,.96)!important;color:' . $difficulty_color . '!important;font-size:12px!important;font-weight:900!important;line-height:1!important;box-shadow:0 8px 20px rgba(15,23,42,.18)!important;transform:none!important;';
	?>
	<article class="sg-project-card sg-project-card--v4">
		<a class="sg-project-card__media-v4" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( get_the_title( $post ) ); ?>" style="<?php echo esc_attr( $media_link_style ); ?>"><span class="sg-project-card__frame-v4" data-sg-card-media="v4" style="<?php echo esc_attr( $media_frame_style ); ?>"><?php if ( has_post_thumbnail( $post ) ) : ?><?php echo get_the_post_thumbnail( $post, 'cs-card', array( 'class' => 'sg-project-card__image-v4', 'loading' => 'lazy', 'decoding' => 'async', 'alt' => esc_attr( get_the_title( $post ) ), 'style' => $media_image_style ) ); ?><?php else : ?><span class="sg-project-card__placeholder-v4" aria-hidden="true" style="<?php echo esc_attr( $media_placeholder_style ); ?>"><span class="sg-project-card__glyph-v4" style="<?php echo esc_attr( $media_glyph_style ); ?>"><?php echo esc_html( ts_sg_project_icon( $post ) ); ?></span><strong style="<?php echo esc_attr( $media_label_style ); ?>"><?php echo esc_html( $subject ? $subject->name : 'ECE Project' ); ?></strong></span><?php endif; ?><?php if ( $difficulty ) : ?><span class="sg-project-card__level-v4 sg-project-card__level-v4--<?php echo esc_attr( $difficulty_slug ); ?>" style="<?php echo esc_attr( $media_badge_style ); ?>"><?php echo esc_html( $difficulty->name ); ?></span><?php endif; ?></span></a>

		<div class="sg-project-card__body">
			<div class="sg-project-card__meta">
				<?php if ( $subject ) : ?>
					<a href="<?php echo esc_url( ts_sg_project_filtered_archive_url( array( 'project_subject' => $subject->slug ) ) ); ?>"><?php echo esc_html( $subject->name ); ?></a>
				<?php endif; ?>
				<?php if ( $year ) : ?>
					<span><?php echo esc_html( $year->name ); ?></span>
				<?php endif; ?>
			</div>

			<h3 class="sg-project-card__title">
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
			</h3>

			<p class="sg-project-card__excerpt"><?php echo esc_html( ts_sg_project_excerpt( $post ) ); ?></p>

			<?php if ( ! empty( $topics ) ) : ?>
				<div class="sg-project-card__topics">
					<?php foreach ( $topics as $topic ) : ?>
						<span><?php echo esc_html( $topic->name ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="sg-project-card__foot">
				<span>🕒 <?php echo esc_html( sprintf( '%d min guide', ts_sg_project_reading_time( $post ) ) ); ?></span>
				<a class="sg-project-card__link" href="<?php echo esc_url( $url ); ?>">Build Guide →</a>
			</div>
		</div>
	</article>
	<?php
}

function ts_sg_project_render_pagination( $filters, $result ) {
	$page      = (int) $result['page'];
	$max_pages = (int) $result['max_pages'];
	$base_url  = ts_sg_project_archive_url();

	if ( $max_pages < 2 ) {
		return;
	}

	$url_for = function ( $target_page ) use ( $base_url, $filters ) {
		$args = ts_sg_project_filter_query_args( $filters, $target_page );
		$url  = ! empty( $args ) ? add_query_arg( $args, $base_url ) : $base_url;

		return $url . '#sg-projects-list';
	};

	$pages = array();

	for ( $i = 1; $i <= $max_pages; $i++ ) {
		if ( 1 === $i || $i === $max_pages || abs( $i - $page ) <= 1 ) {
			$pages[] = $i;
		}
	}

	$pages = array_values( array_unique( $pages ) );
	?>
	<nav class="sg-projects-pagination" aria-label="<?php esc_attr_e( 'Project pages', 'ts-block-theme' ); ?>">
		<?php if ( $page > 1 ) : ?>
			<a href="<?php echo esc_url( $url_for( $page - 1 ) ); ?>">← Prev</a>
		<?php endif; ?>

		<?php $previous_shown = 0; ?>
		<?php foreach ( $pages as $shown_page ) : ?>
			<?php if ( $previous_shown && $shown_page > $previous_shown + 1 ) : ?>
				<span class="sg-projects-pagination__dots">…</span>
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

function ts_sg_project_render_results_block( $result, $filters ) {
	$active = ts_sg_project_filters_active( $filters );
	?>
	<p class="sg-projects-results-meta" role="status">
		<?php
		echo esc_html(
			sprintf(
				'%d %s mile%s',
				(int) $result['total'],
				1 === (int) $result['total'] ? 'project' : 'projects',
				$active ? ' — filters applied' : ''
			)
		);
		?>
	</p>

	<?php if ( ! empty( $result['posts'] ) ) : ?>
		<div class="sg-projects-grid">
			<?php foreach ( $result['posts'] as $post ) : ?>
				<?php ts_sg_project_render_card( $post ); ?>
			<?php endforeach; ?>
		</div>

		<?php ts_sg_project_render_pagination( $filters, $result ); ?>
	<?php else : ?>
		<div class="sg-projects-empty">
			<span aria-hidden="true">🛠️</span>
			<h3>Is filter me koi project nahi mila</h3>
			<p>Search word badlo, filters clear karo, ya apna project topic humein bhejo — hum usi par guide banane ki koshish karenge.</p>
			<div class="sg-projects-empty__actions">
				<a class="sg-course-btn sg-course-btn--primary" href="<?php echo esc_url( ts_sg_project_archive_url() ); ?>#sg-projects-list">Clear Filters</a>
				<a class="sg-course-btn sg-projects-btn--outline" href="<?php echo esc_url( ts_sg_project_help_url() ); ?>">Project Topic Bhejo</a>
			</div>
		</div>
	<?php endif; ?>
	<?php
}

function ts_sg_project_count_subjects_in_projects() {
	$project_ids = get_posts(
		array(
			'post_type'      => TS_SG_PROJECT_PT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	if ( empty( $project_ids ) || ! taxonomy_exists( TS_SG_PROJECT_SUBJECT_TAX ) ) {
		return 0;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => TS_SG_PROJECT_SUBJECT_TAX,
			'object_ids' => array_map( 'absint', $project_ids ),
			'hide_empty' => true,
			'fields'     => 'ids',
		)
	);

	return is_array( $terms ) ? count( $terms ) : 0;
}

function ts_sg_project_render_archive_shortcode() {
	if ( ! ts_sg_project_is_ready() ) {
		return '';
	}

	$filters       = ts_sg_project_get_filters();
	$result        = ts_sg_project_fetch_projects( $filters, 12 );
	$counts        = wp_count_posts( TS_SG_PROJECT_PT );
	$total_projects = $counts && isset( $counts->publish ) ? (int) $counts->publish : 0;
	$subject_count = ts_sg_project_count_subjects_in_projects();

	ob_start();
	?>
	<section class="sg-course sg-projects sg-projects--archive">
		<div class="sg-course-hero sg-projects-hero">
			<div class="sg-course-wrap">
				<nav class="sg-course-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'ts-block-theme' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
					<span class="sg-course-breadcrumbs__sep" aria-hidden="true">›</span>
					<span aria-current="page">ECE Projects</span>
				</nav>

				<div class="sg-course-hero__main sg-course-hero__main--wide">
					<p class="sg-course-eyebrow">🛠️ Project Lab</p>
					<h1>ECE Projects with Circuit + Code</h1>
					<p class="sg-course-hero__desc">Minor aur major projects ke complete build guides — components, circuit connections, tested code, working, viva questions aur practical next steps ke saath.</p>

					<div class="sg-course-stats" aria-label="<?php esc_attr_e( 'Project statistics', 'ts-block-theme' ); ?>">
						<span><strong><?php echo esc_html( (string) $total_projects ); ?></strong> projects</span>
						<span><strong><?php echo esc_html( (string) $subject_count ); ?></strong> subjects/domains</span>
						<span><strong>Step-by-Step</strong> build guides</span>
					</div>

					<div class="sg-course-actions">
						<a class="sg-course-btn sg-course-btn--primary" href="#sg-projects-list">Projects Dekho</a>
						<a class="sg-course-btn sg-course-btn--dark-ghost" href="<?php echo esc_url( ts_sg_project_help_url() ); ?>">Get Project Help</a>
					</div>
				</div>
			</div>
		</div>

		<div class="sg-course-wrap sg-course-content">
			<section class="sg-projects-list" id="sg-projects-list">
				<div class="sg-course-section-head">
					<p>Choose, build and learn</p>
					<h2>Project Build Guides</h2>
				</div>

				<?php ts_sg_project_render_filter_form( $filters ); ?>
				<?php ts_sg_project_render_results_block( $result, $filters ); ?>
			</section>

			<aside class="sg-course-help sg-projects-help">
				<div>
					<p class="sg-course-help__eyebrow">Apna college project choose nahi ho raha?</p>
					<h2>Topic, components aur viva — teeno me sahi guidance lo.</h2>
					<p>Copy-paste project nahi. Hum tumhe topic selection, circuit, code samajhne, report outline aur viva preparation me guide karenge.</p>
				</div>
				<div class="sg-course-help__actions">
					<a class="sg-course-btn sg-course-btn--light" href="<?php echo esc_url( ts_sg_project_help_url() ); ?>">Free Consultation</a>
					<a class="sg-course-btn sg-course-btn--ghost" href="<?php echo esc_url( ts_sg_project_whatsapp_url() ); ?>" target="_blank" rel="noopener">WhatsApp Par Poochho</a>
				</div>
			</aside>
		</div>
	</section>
	<?php

	return ob_get_clean();
}

/* ------------------------------------------------------------
   Single project content parser
   ------------------------------------------------------------ */

function ts_sg_project_heading_text( $inner_html ) {
	$text = html_entity_decode( wp_strip_all_tags( (string) $inner_html ), ENT_QUOTES, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', $text );

	return trim( (string) $text );
}

function ts_sg_project_normalize_heading_key( $text ) {
	$text = strtolower( trim( (string) $text ) );
	$text = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $text );
	$text = preg_replace( '/\s+/u', ' ', (string) $text );

	return trim( (string) $text );
}

function ts_sg_project_section_type( $heading_text ) {
	$key = ts_sg_project_normalize_heading_key( $heading_text );

	$rules = array(
		'components'   => array( 'component', 'material', 'required', 'hardware', 'software required', 'tools', 'bill of material', 'bom', 'kya kya chahiye' ),
		'circuit'      => array( 'circuit', 'wiring', 'connection', 'schematic', 'pin diagram', 'block diagram' ),
		'code'         => array( 'code', 'program', 'sketch', 'source code', 'firmware', 'verilog code', 'rtl code' ),
		'steps'        => array( 'build step', 'procedure', 'construction', 'assembly', 'how to make', 'how to build', 'setup', 'upload', 'step by step' ),
		'working'      => array( 'working', 'output', 'result', 'testing', 'test', 'calibration', 'observation' ),
		'viva'         => array( 'viva', 'interview', 'faq', 'questions and answers', 'important questions' ),
		'mistakes'     => array( 'mistake', 'troubleshoot', 'problem', 'error', 'debug', 'safety', 'precaution' ),
		'kit'          => array( 'cost', 'budget', 'price', 'kit', 'buy', 'purchase', 'order' ),
		'applications' => array( 'application', 'uses', 'advantage', 'limitation', 'future scope', 'conclusion' ),
		'overview'     => array( 'overview', 'introduction', 'about', 'project idea', 'what is' ),
	);

	foreach ( $rules as $type => $needles ) {
		foreach ( $needles as $needle ) {
			if ( false !== strpos( $key, $needle ) ) {
				return $type;
			}
		}
	}

	return 'guide';
}

function ts_sg_project_section_icon( $type ) {
	$icons = array(
		'overview'     => '📌',
		'components'   => '🧰',
		'circuit'      => '🔌',
		'code'         => '💻',
		'steps'        => '🧱',
		'working'      => '✅',
		'viva'         => '🎤',
		'mistakes'     => '⚠️',
		'kit'          => '🛒',
		'applications' => '🚀',
		'guide'        => '📘',
	);

	return isset( $icons[ $type ] ) ? $icons[ $type ] : '📘';
}

function ts_sg_project_html_has_content( $html ) {
	if ( '' !== trim( wp_strip_all_tags( (string) $html ) ) ) {
		return true;
	}

	return (bool) preg_match( '/<(img|table|figure|pre|iframe|video|audio|svg|math)\b/i', (string) $html );
}

function ts_sg_project_split_content( $post ) {
	$html = apply_filters( 'the_content', $post->post_content );

	$parsed = array(
		'intro_html' => '',
		'sections'   => array(),
		'full_html'  => $html,
	);

	if ( ! preg_match_all( '/<h2\b[^>]*>(.*?)<\/h2>/is', $html, $matches, PREG_OFFSET_CAPTURE ) ) {
		$parsed['intro_html'] = $html;
		return $parsed;
	}

	$cursor = 0;
	$used   = array();

	foreach ( $matches[0] as $index => $full_match ) {
		$heading_html = $full_match[0];
		$start        = (int) $full_match[1];
		$inner_html   = $matches[1][ $index ][0];
		$text         = ts_sg_project_heading_text( $inner_html );

		$chunk = $start > $cursor ? substr( $html, $cursor, $start - $cursor ) : '';

		if ( empty( $parsed['sections'] ) ) {
			$parsed['intro_html'] .= $chunk;
		} else {
			$last_index                                = count( $parsed['sections'] ) - 1;
			$parsed['sections'][ $last_index ]['html'] .= $chunk;
		}

		if ( '' === $text ) {
			if ( empty( $parsed['sections'] ) ) {
				$parsed['intro_html'] .= $heading_html;
			} else {
				$last_index                                = count( $parsed['sections'] ) - 1;
				$parsed['sections'][ $last_index ]['html'] .= $heading_html;
			}
			$cursor = $start + strlen( $heading_html );
			continue;
		}

		$base_id = sanitize_title( $text );
		$id      = '' !== $base_id ? $base_id : 'section-' . ( count( $parsed['sections'] ) + 1 );
		$n       = 2;

		while ( isset( $used[ $id ] ) ) {
			$id = $base_id . '-' . $n;
			$n++;
		}

		$used[ $id ] = true;

		$parsed['sections'][] = array(
			'id'          => $id,
			'title'       => $text,
			'title_html'  => $inner_html,
			'type'        => ts_sg_project_section_type( $text ),
			'html'        => '',
		);

		$cursor = $start + strlen( $heading_html );
	}

	if ( $cursor < strlen( $html ) ) {
		$tail = substr( $html, $cursor );

		if ( empty( $parsed['sections'] ) ) {
			$parsed['intro_html'] .= $tail;
		} else {
			$last_index                                = count( $parsed['sections'] ) - 1;
			$parsed['sections'][ $last_index ]['html'] .= $tail;
		}
	}

	return $parsed;
}

/**
 * In a Viva section, each H3 question becomes a clean Q&A toggle.
 */
function ts_sg_project_render_viva_content( $html ) {
	if ( ! preg_match_all( '/<h3\b[^>]*>(.*?)<\/h3>/is', $html, $matches, PREG_OFFSET_CAPTURE ) ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content.
		return;
	}

	$cursor = 0;

	foreach ( $matches[0] as $index => $full_match ) {
		$start      = (int) $full_match[1];
		$inner_html = $matches[1][ $index ][0];
		$text       = ts_sg_project_heading_text( $inner_html );
		$end        = isset( $matches[0][ $index + 1 ] ) ? (int) $matches[0][ $index + 1 ][1] : strlen( $html );
		$body_start = $start + strlen( $full_match[0] );
		$body       = $end > $body_start ? substr( $html, $body_start, $end - $body_start ) : '';

		if ( 0 === $index && $start > 0 ) {
			$intro = substr( $html, 0, $start );
			if ( ts_sg_project_html_has_content( $intro ) ) {
				echo $intro; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content.
			}
		}

		if ( '' === $text ) {
			echo $full_match[0]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content.
			echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content.
			$cursor = $end;
			continue;
		}
		?>
		<details class="sg-project-viva-item">
			<summary><span><?php echo esc_html( $text ); ?></span><span class="sg-project-viva-item__icon" aria-hidden="true"></span></summary>
			<div class="sg-project-viva-item__body">
				<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content. ?>
			</div>
		</details>
		<?php
		$cursor = $end;
	}
}

function ts_sg_project_render_section( $section, $number ) {
	?>
	<section class="sg-project-section sg-project-section--<?php echo esc_attr( $section['type'] ); ?>" id="<?php echo esc_attr( $section['id'] ); ?>">
		<header class="sg-project-section__head">
			<span class="sg-project-section__icon" aria-hidden="true"><?php echo esc_html( ts_sg_project_section_icon( $section['type'] ) ); ?></span>
			<div>
				<p>Section <?php echo esc_html( str_pad( (string) $number, 2, '0', STR_PAD_LEFT ) ); ?></p>
				<h2><?php echo $section['title_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Heading from filtered post content. ?></h2>
			</div>
		</header>

		<div class="sg-project-section__body">
			<?php
			if ( 'viva' === $section['type'] ) {
				ts_sg_project_render_viva_content( $section['html'] );
			} else {
				echo $section['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content.
			}
			?>
		</div>
	</section>
	<?php
}

function ts_sg_project_get_related_projects( $post, $limit = 3 ) {
	$candidates = get_posts(
		array(
			'post_type'      => TS_SG_PROJECT_PT,
			'post_status'    => 'publish',
			'posts_per_page' => 30,
			'post__not_in'   => array( (int) $post->ID ),
			'orderby'        => array(
				'menu_order' => 'ASC',
				'modified'   => 'DESC',
				'title'      => 'ASC',
			),
			'no_found_rows'  => true,
		)
	);

	if ( empty( $candidates ) ) {
		return array();
	}

	$current = array(
		TS_SG_PROJECT_SUBJECT_TAX    => wp_list_pluck( ts_sg_project_get_terms( $post->ID, TS_SG_PROJECT_SUBJECT_TAX ), 'term_id' ),
		TS_SG_PROJECT_TOPIC_TAX      => wp_list_pluck( ts_sg_project_get_terms( $post->ID, TS_SG_PROJECT_TOPIC_TAX ), 'term_id' ),
		TS_SG_PROJECT_DIFFICULTY_TAX => wp_list_pluck( ts_sg_project_get_terms( $post->ID, TS_SG_PROJECT_DIFFICULTY_TAX ), 'term_id' ),
		TS_SG_PROJECT_YEAR_TAX       => wp_list_pluck( ts_sg_project_get_terms( $post->ID, TS_SG_PROJECT_YEAR_TAX ), 'term_id' ),
	);

	$weights = array(
		TS_SG_PROJECT_SUBJECT_TAX    => 3,
		TS_SG_PROJECT_TOPIC_TAX      => 2,
		TS_SG_PROJECT_DIFFICULTY_TAX => 1,
		TS_SG_PROJECT_YEAR_TAX       => 1,
	);

	$scored = array();

	foreach ( $candidates as $candidate ) {
		$score = 0;

		foreach ( $current as $taxonomy => $term_ids ) {
			if ( empty( $term_ids ) ) {
				continue;
			}

			$candidate_ids = wp_list_pluck( ts_sg_project_get_terms( $candidate->ID, $taxonomy ), 'term_id' );
			$score        += count( array_intersect( array_map( 'intval', $term_ids ), array_map( 'intval', $candidate_ids ) ) ) * $weights[ $taxonomy ];
		}

		$scored[] = array(
			'post'  => $candidate,
			'score' => $score,
			'time'  => (int) get_post_modified_time( 'U', true, $candidate ),
		);
	}

	usort(
		$scored,
		function ( $a, $b ) {
			if ( $a['score'] !== $b['score'] ) {
				return $b['score'] <=> $a['score'];
			}

			if ( $a['time'] !== $b['time'] ) {
				return $b['time'] <=> $a['time'];
			}

			return strnatcasecmp( get_the_title( $a['post'] ), get_the_title( $b['post'] ) );
		}
	);

	return wp_list_pluck( array_slice( $scored, 0, (int) $limit ), 'post' );
}

function ts_sg_project_render_sidebar( $post, $sections ) {
	$subject    = ts_sg_project_first_term( $post->ID, TS_SG_PROJECT_SUBJECT_TAX );
	$difficulty = ts_sg_project_first_term( $post->ID, TS_SG_PROJECT_DIFFICULTY_TAX );
	$year       = ts_sg_project_first_term( $post->ID, TS_SG_PROJECT_YEAR_TAX );
	$whatsapp   = ts_sg_project_whatsapp_url( $post );

	ob_start();
	?>
	<div class="sg-project-side-card">
		<div class="sg-project-side-card__head">
			<p>Project Guide</p>
			<strong><?php echo esc_html( wp_trim_words( get_the_title( $post ), 8, '…' ) ); ?></strong>
		</div>

		<dl class="sg-project-facts">
			<?php if ( $subject ) : ?>
				<div><dt>Subject / Domain</dt><dd><?php echo esc_html( $subject->name ); ?></dd></div>
			<?php endif; ?>
			<?php if ( $difficulty ) : ?>
				<div><dt>Difficulty</dt><dd><?php echo esc_html( $difficulty->name ); ?></dd></div>
			<?php endif; ?>
			<?php if ( $year ) : ?>
				<div><dt>Best For</dt><dd><?php echo esc_html( $year->name ); ?></dd></div>
			<?php endif; ?>
			<div><dt>Guide Time</dt><dd><?php echo esc_html( sprintf( '%d min read', ts_sg_project_reading_time( $post ) ) ); ?></dd></div>
			<?php if ( ! empty( $sections ) ) : ?>
				<div><dt>Sections</dt><dd><?php echo esc_html( (string) count( $sections ) ); ?></dd></div>
			<?php endif; ?>
			<div><dt>Last Updated</dt><dd><?php echo esc_html( get_the_modified_date( 'j M Y', $post ) ); ?></dd></div>
		</dl>

		<?php if ( ! empty( $sections ) ) : ?>
			<nav class="sg-project-toc" aria-label="<?php esc_attr_e( 'Project guide sections', 'ts-block-theme' ); ?>">
				<p>Is guide me</p>
				<ol>
					<?php foreach ( $sections as $section ) : ?>
						<li>
							<a href="#<?php echo esc_attr( $section['id'] ); ?>">
								<span aria-hidden="true"><?php echo esc_html( ts_sg_project_section_icon( $section['type'] ) ); ?></span>
								<?php echo esc_html( $section['title'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ol>
			</nav>
		<?php endif; ?>

		<div class="sg-project-side-actions">
			<a class="sg-project-btn sg-project-btn--primary" href="<?php echo esc_url( ts_sg_project_help_url() ); ?>">Get Project Help</a>
			<a class="sg-project-btn sg-project-btn--whatsapp" href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener">WhatsApp Par Poochho</a>
			<a class="sg-project-side-link" href="<?php echo esc_url( ts_sg_project_archive_url() ); ?>">← All Projects</a>
		</div>
	</div>
	<?php
	$html = ob_get_clean();
	?>
	<div class="sg-project-sidebar__desktop"><?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building above. ?></div>

	<details class="sg-project-sidebar__mobile">
		<summary><span>🛠️ Project Guide Menu</span><strong><?php echo esc_html( sprintf( '%d sections', count( $sections ) ) ); ?></strong></summary>
		<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building above. ?>
	</details>
	<?php
}

function ts_sg_project_render_single_shortcode() {
	static $rendering = false;

	if ( $rendering || ! ts_sg_project_is_ready() || ! is_singular( TS_SG_PROJECT_PT ) ) {
		return '';
	}

	$post = get_post();

	if ( ! $post || TS_SG_PROJECT_PT !== $post->post_type ) {
		return '';
	}

	$rendering = true;
	ob_start();
	ts_sg_project_render_page( $post );
	$html      = ob_get_clean();
	$rendering = false;

	return $html;
}

function ts_sg_project_render_page( $post ) {
	$parsed     = ts_sg_project_split_content( $post );
	$sections   = $parsed['sections'];
	$subject    = ts_sg_project_first_term( $post->ID, TS_SG_PROJECT_SUBJECT_TAX );
	$difficulty = ts_sg_project_first_term( $post->ID, TS_SG_PROJECT_DIFFICULTY_TAX );
	$year       = ts_sg_project_first_term( $post->ID, TS_SG_PROJECT_YEAR_TAX );
	$topics     = ts_sg_project_get_terms( $post->ID, TS_SG_PROJECT_TOPIC_TAX );
	$related    = ts_sg_project_get_related_projects( $post, 3 );

	$first_section_id = '';
	$components_id    = '';

	foreach ( $sections as $section ) {
		if ( '' === $first_section_id ) {
			$first_section_id = $section['id'];
		}

		if ( 'components' === $section['type'] ) {
			$components_id = $section['id'];
			break;
		}
	}

	$build_target = $components_id ? '#' . $components_id : ( $first_section_id ? '#' . $first_section_id : '#sg-project-guide' );
	?>
	<section class="sg-project-page">
		<div class="sg-project-wrap">
			<nav class="sg-project-breadcrumbs" aria-label="<?php esc_attr_e( 'Project breadcrumb', 'ts-block-theme' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
				<span aria-hidden="true">›</span>
				<a href="<?php echo esc_url( ts_sg_project_archive_url() ); ?>">Projects</a>
				<?php if ( $subject ) : ?>
					<span aria-hidden="true">›</span>
					<a href="<?php echo esc_url( ts_sg_project_filtered_archive_url( array( 'project_subject' => $subject->slug ) ) ); ?>"><?php echo esc_html( $subject->name ); ?></a>
				<?php endif; ?>
				<span aria-hidden="true">›</span>
				<span aria-current="page"><?php echo esc_html( wp_trim_words( get_the_title( $post ), 9, '…' ) ); ?></span>
			</nav>

			<div class="sg-project-layout">
				<aside class="sg-project-sidebar">
					<?php ts_sg_project_render_sidebar( $post, $sections ); ?>
				</aside>

				<article class="sg-project-article">
					<header class="sg-project-article__head">
						<p class="sg-project-kicker">
							<?php echo esc_html( $subject ? $subject->name : 'ECE Project' ); ?>
							<?php if ( $difficulty ) : ?> • <?php echo esc_html( $difficulty->name ); ?><?php endif; ?>
							<?php if ( $year ) : ?> • <?php echo esc_html( $year->name ); ?><?php endif; ?>
						</p>

						<h1><?php echo esc_html( get_the_title( $post ) ); ?></h1>

						<?php if ( has_excerpt( $post ) ) : ?>
							<p class="sg-project-lead"><?php echo esc_html( get_the_excerpt( $post ) ); ?></p>
						<?php endif; ?>

						<div class="sg-project-meta">
							<span>🕒 <?php echo esc_html( sprintf( '%d min guide', ts_sg_project_reading_time( $post ) ) ); ?></span>
							<?php if ( ! empty( $sections ) ) : ?>
								<span>🧩 <?php echo esc_html( sprintf( '%d sections', count( $sections ) ) ); ?></span>
							<?php endif; ?>
							<span>Updated: <?php echo esc_html( get_the_modified_date( 'j M Y', $post ) ); ?></span>
						</div>

						<div class="sg-project-head-actions">
							<a class="sg-project-btn sg-project-btn--primary" href="<?php echo esc_url( $build_target ); ?>">Start Building →</a>
							<a class="sg-project-btn sg-project-btn--outline" href="<?php echo esc_url( ts_sg_project_help_url() ); ?>">Get Project Help</a>
						</div>
					</header>

					<?php if ( has_post_thumbnail( $post ) ) : ?>
						<figure class="sg-project-featured">
							<?php echo get_the_post_thumbnail( $post, 'cs-hero', array( 'alt' => esc_attr( get_the_title( $post ) ), 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ) ); ?>
							<?php $caption = get_the_post_thumbnail_caption( $post ); ?>
							<?php if ( $caption ) : ?>
								<figcaption><?php echo esc_html( $caption ); ?></figcaption>
							<?php endif; ?>
						</figure>
					<?php endif; ?>

					<div class="sg-project-content" id="sg-project-guide">
						<?php if ( ! empty( $sections ) ) : ?>
							<?php if ( ts_sg_project_html_has_content( $parsed['intro_html'] ) ) : ?>
								<div class="sg-project-intro">
									<?php echo $parsed['intro_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content. ?>
								</div>
							<?php endif; ?>

							<?php foreach ( $sections as $index => $section ) : ?>
								<?php ts_sg_project_render_section( $section, $index + 1 ); ?>
							<?php endforeach; ?>
						<?php else : ?>
							<div class="sg-project-fallback-note">
								<strong>Complete build guide jald update hoga.</strong>
								<p>Abhi is project ka content normal article style me dikh raha hai.</p>
							</div>
							<?php echo $parsed['full_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content. ?>
						<?php endif; ?>
					</div>

					<?php if ( ! empty( $topics ) ) : ?>
						<div class="sg-project-tags">
							<span>Project topics:</span>
							<?php foreach ( $topics as $topic ) : ?>
								<a href="<?php echo esc_url( ts_sg_project_filtered_archive_url( array( 'project_search' => $topic->name ) ) ); ?>"><?php echo esc_html( $topic->name ); ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<aside class="sg-project-help-cta">
						<div>
							<p>Project me atak gaye?</p>
							<h2>Circuit photo aur error ke saath help lo.</h2>
							<span>Topic selection, components, code debugging, report outline aur viva preparation — seekhkar khud confident bano.</span>
						</div>
						<div class="sg-project-help-cta__actions">
							<a class="sg-project-btn sg-project-btn--light" href="<?php echo esc_url( ts_sg_project_help_url() ); ?>">Get Project Help</a>
							<a class="sg-project-btn sg-project-btn--whatsapp" href="<?php echo esc_url( ts_sg_project_whatsapp_url( $post ) ); ?>" target="_blank" rel="noopener">WhatsApp</a>
						</div>
					</aside>

					<?php if ( ! empty( $related ) ) : ?>
						<section class="sg-project-related">
							<div class="sg-project-related__head">
								<p>Keep building</p>
								<h2>Related Projects</h2>
							</div>
							<div class="sg-projects-grid sg-projects-grid--related">
								<?php foreach ( $related as $related_post ) : ?>
									<?php ts_sg_project_render_card( $related_post ); ?>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endif; ?>
				</article>
			</div>
		</div>
	</section>
	<?php
}
