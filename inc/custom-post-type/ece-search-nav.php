<?php
/**
 * Silicon Geek — Global Search + Dynamic Mega Menu (Step 8).
 *
 * NEW FILE:
 * wp-content/themes/ts-block-theme/inc/custom-post-type/ece-search-nav.php
 *
 * Load it from functions.php immediately after ece-learning-paths.php:
 * require_once get_template_directory() . '/inc/custom-post-type/ece-search-nav.php';
 *
 * Shortcodes:
 *  - [sg_mega_menu]       Dynamic desktop mega menu + mobile navigation.
 *                         Replace the old header Navigation block with this.
 *  - [sg_global_search]   Reusable search form for header/hero/search page.
 *  - [sg_search_results]  Used by templates/search.html.
 *
 * Dynamic behaviour:
 *  - Course Structure roots, child folders and grandchild folders are read
 *    from the ece_course taxonomy on every request.
 *  - Adding, renaming, reordering or deleting a course folder in WP Admin
 *    updates the navigation automatically. No header links need editing.
 *  - Shop and Training appear only when published pages with those slugs
 *    exist, so the menu does not create dead links before those pages exist.
 *
 * Global Search behaviour:
 *  - Published ECE Notes, GATE PYQ Sets, ECE Projects, pages and posts are
 *    searched together.
 *  - Matching Course Structure folders are shown above content results.
 *  - Results can be filtered with the sg_type query parameter.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'TS_CS_COURSE_TAX' ) ) {
	define( 'TS_CS_COURSE_TAX', 'ece_course' );
}

add_shortcode( 'sg_mega_menu', 'ts_sg_nav_mega_menu_shortcode' );
add_shortcode( 'sg_global_search', 'ts_sg_nav_global_search_shortcode' );
add_shortcode( 'sg_search_results', 'ts_sg_nav_search_results_shortcode' );
add_action( 'pre_get_posts', 'ts_sg_nav_filter_search_query', 30 );

/* -------------------------------------------------------------------------
 * Search query setup
 * ---------------------------------------------------------------------- */

/**
 * Post types for one public search filter.
 *
 * @param string $filter all|notes|projects|gate|pages.
 * @return string[]
 */
function ts_sg_nav_search_post_types( $filter = 'all' ) {
	$map = array(
		'all'      => array( 'ece_note', 'ece_gate_pyq', 'ece_project', 'page', 'post' ),
		'notes'    => array( 'ece_note' ),
		'projects' => array( 'ece_project' ),
		'gate'     => array( 'ece_gate_pyq' ),
		'pages'    => array( 'page', 'post' ),
	);

	return isset( $map[ $filter ] ) ? $map[ $filter ] : $map['all'];
}

/**
 * Search filters shown in the UI.
 *
 * @return array<string,string>
 */
function ts_sg_nav_search_filters() {
	return array(
		'all'      => 'All Content',
		'notes'    => 'Notes',
		'projects' => 'Projects',
		'gate'     => 'GATE PYQ Sets',
		'pages'    => 'Pages & Posts',
	);
}

/**
 * Current validated sg_type filter.
 *
 * @return string
 */
function ts_sg_nav_current_search_filter() {
	if ( ! isset( $_GET['sg_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return 'all';
	}

	$filter  = sanitize_key( wp_unslash( $_GET['sg_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$filters = ts_sg_nav_search_filters();

	return isset( $filters[ $filter ] ) ? $filter : 'all';
}

/**
 * Make the ordinary WordPress search endpoint search the learning platform.
 *
 * This also upgrades the existing header Search block automatically:
 * it already submits `?s=...`, and this hook widens that query to the
 * ECE post types while templates/search.html renders the results.
 *
 * @param WP_Query $query Main query.
 */
function ts_sg_nav_filter_search_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	$query->set( 'post_type', ts_sg_nav_search_post_types( ts_sg_nav_current_search_filter() ) );
	$query->set( 'post_status', 'publish' );
	$query->set( 'has_password', false );
	$query->set( 'posts_per_page', 12 );
	$query->set( 'ignore_sticky_posts', true );
}

/* -------------------------------------------------------------------------
 * Course Structure tree helpers
 * ---------------------------------------------------------------------- */

/**
 * Load the complete Course Structure tree once per request.
 *
 * One get_terms() call is grouped in memory. This avoids one database query
 * per folder when the menu is printed in the header of every page.
 *
 * @return array{by_parent: array<int, WP_Term[]>, by_id: array<int, WP_Term>}
 */
function ts_sg_nav_get_course_tree() {
	static $tree = null;

	if ( null !== $tree ) {
		return $tree;
	}

	$tree = array(
		'by_parent' => array(),
		'by_id'     => array(),
	);

	if ( ! taxonomy_exists( TS_CS_COURSE_TAX ) ) {
		return $tree;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => TS_CS_COURSE_TAX,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return $tree;
	}

	foreach ( $terms as $term ) {
		$tree['by_id'][ (int) $term->term_id ]                    = $term;
		$tree['by_parent'][ (int) $term->parent ][]               = $term;
	}

	foreach ( $tree['by_parent'] as $parent_id => $siblings ) {
		$tree['by_parent'][ $parent_id ] = function_exists( 'ts_cs_course_sort_terms' )
			? ts_cs_course_sort_terms( $siblings )
			: $siblings;
	}

	return $tree;
}

/**
 * Ordered direct children from the in-memory tree.
 *
 * @param array $tree      Course tree.
 * @param int   $parent_id Parent term ID; 0 means roots.
 * @return WP_Term[]
 */
function ts_sg_nav_tree_children( $tree, $parent_id ) {
	return isset( $tree['by_parent'][ (int) $parent_id ] )
		? $tree['by_parent'][ (int) $parent_id ]
		: array();
}

/**
 * Root-to-term path from the in-memory tree.
 *
 * @param WP_Term $term Course term.
 * @param array   $tree Course tree.
 * @return WP_Term[]
 */
function ts_sg_nav_tree_path( $term, $tree ) {
	$path    = array();
	$current = $term;
	$seen    = array();

	while ( $current && ! isset( $seen[ (int) $current->term_id ] ) ) {
		$seen[ (int) $current->term_id ] = true;
		array_unshift( $path, $current );

		if ( empty( $current->parent ) || ! isset( $tree['by_id'][ (int) $current->parent ] ) ) {
			break;
		}

		$current = $tree['by_id'][ (int) $current->parent ];
	}

	return $path;
}

/**
 * Safe term URL.
 *
 * @param WP_Term $term Term object.
 * @return string
 */
function ts_sg_nav_term_url( $term ) {
	$url = get_term_link( $term );

	return is_wp_error( $url ) ? '' : $url;
}

/**
 * Short header label for known roots. Unknown future roots use their
 * backend name automatically.
 *
 * @param WP_Term $term Root term.
 * @return string
 */
function ts_sg_nav_root_short_label( $term ) {
	$labels = array(
		'btech-ece'        => 'BTech ECE',
		'gate-ec'          => 'GATE EC',
		'embedded-systems' => 'Embedded',
		'vlsi-design'      => 'VLSI',
	);

	return isset( $labels[ $term->slug ] ) ? $labels[ $term->slug ] : $term->name;
}

/**
 * Existing Step 2 level label when available.
 *
 * @param WP_Term $term   Course term.
 * @param bool    $plural Plural label.
 * @return string
 */
function ts_sg_nav_level_label( $term, $plural = false ) {
	if ( function_exists( 'ts_cs_course_index_level_label' ) ) {
		return ts_cs_course_index_level_label( $term, $plural );
	}

	return $plural ? 'Course Sections' : 'Course Section';
}

/**
 * Existing Step 2 folder icon when available.
 *
 * @param WP_Term $term Course term.
 * @return string
 */
function ts_sg_nav_icon( $term ) {
	return function_exists( 'ts_cs_course_index_icon' )
		? ts_cs_course_index_icon( $term )
		: '📚';
}

/**
 * Published page URL by path, or an empty string when the page is absent.
 *
 * @param string $path Page slug/path.
 * @return string
 */
function ts_sg_nav_optional_page_url( $path ) {
	$page = get_page_by_path( sanitize_title( $path ) );

	if ( ! $page || 'publish' !== $page->post_status ) {
		return '';
	}

	$url = get_permalink( $page );

	return $url ? $url : '';
}

/**
 * Non-course links used by the desktop or mobile navigation.
 *
 * @param string $context desktop|mobile.
 * @return array<int,array{label:string,url:string,icon:string,class:string}>
 */
function ts_sg_nav_fixed_links( $context = 'desktop' ) {
	$links = array();

	$projects_url = get_post_type_archive_link( 'ece_project' );
	if ( $projects_url ) {
		$links[] = array(
			'label' => 'Projects',
			'url'   => $projects_url,
			'icon'  => '🛠️',
			'class' => 'is-projects',
		);
	}

	if ( 'mobile' === $context ) {
		$notes_url = get_post_type_archive_link( 'ece_note' );
		if ( $notes_url ) {
			$links[] = array(
				'label' => 'All Notes',
				'url'   => $notes_url,
				'icon'  => '📝',
				'class' => 'is-notes',
			);
		}

		$gate_url = get_post_type_archive_link( 'ece_gate_pyq' );
		if ( $gate_url ) {
			$links[] = array(
				'label' => 'GATE PYQ Sets',
				'url'   => $gate_url,
				'icon'  => '🏆',
				'class' => 'is-gate',
			);
		}
	}

	$optional_pages = array(
		'shop'     => array( 'Shop', '🛒' ),
		'training' => array( 'Training', '🏫' ),
	);

	foreach ( $optional_pages as $slug => $page_data ) {
		$url = ts_sg_nav_optional_page_url( $slug );

		if ( '' !== $url ) {
			$links[] = array(
				'label' => $page_data[0],
				'url'   => $url,
				'icon'  => $page_data[1],
				'class' => 'is-' . $slug,
			);
		}
	}

	return $links;
}

/* -------------------------------------------------------------------------
 * Search forms
 * ---------------------------------------------------------------------- */

/**
 * Return a reusable search form.
 *
 * @param array $args {
 *     Optional. Form settings.
 *
 *     @type string $layout      header|panel|hero|mobile.
 *     @type string $placeholder Input placeholder.
 *     @type bool   $show_filter Show the content-type select.
 *     @type string $value       Current search text.
 *     @type string $filter      Current sg_type filter.
 * }
 * @return string
 */
function ts_sg_nav_search_form_html( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'layout'      => 'panel',
			'placeholder' => 'Search notes, topics, projects…',
			'show_filter' => false,
			'value'       => is_search() ? get_search_query() : '',
			'filter'      => ts_sg_nav_current_search_filter(),
		)
	);

	$layout   = sanitize_html_class( $args['layout'] );
	$filters  = ts_sg_nav_search_filters();
	$filter   = isset( $filters[ $args['filter'] ] ) ? $args['filter'] : 'all';
	$input_id = wp_unique_id( 'sg-global-search-input-' );
	$select_id = wp_unique_id( 'sg-global-search-select-' );

	ob_start();
	?>
	<form class="sg-global-search sg-global-search--<?php echo esc_attr( $layout ); ?>" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php esc_html_e( 'Search Silicon Geek', 'ts-block-theme' ); ?></label>
		<span class="sg-global-search__icon" aria-hidden="true">⌕</span>
		<input
			class="sg-global-search__input"
			id="<?php echo esc_attr( $input_id ); ?>"
			type="search"
			name="s"
			value="<?php echo esc_attr( $args['value'] ); ?>"
			placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>"
			autocomplete="off"
		>
		<?php if ( $args['show_filter'] ) : ?>
			<label class="screen-reader-text" for="<?php echo esc_attr( $select_id ); ?>"><?php esc_html_e( 'Content type', 'ts-block-theme' ); ?></label>
			<select class="sg-global-search__select" id="<?php echo esc_attr( $select_id ); ?>" name="sg_type">
				<?php foreach ( $filters as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filter, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php elseif ( 'all' !== $filter ) : ?>
			<input type="hidden" name="sg_type" value="<?php echo esc_attr( $filter ); ?>">
		<?php endif; ?>
		<button class="sg-global-search__button" type="submit"><?php esc_html_e( 'Search', 'ts-block-theme' ); ?></button>
	</form>
	<?php

	return trim( ob_get_clean() );
}

/**
 * [sg_global_search] Shortcode.
 *
 * Examples:
 *  [sg_global_search]
 *  [sg_global_search layout="hero" show_filter="true"]
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function ts_sg_nav_global_search_shortcode( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'layout'      => 'panel',
			'placeholder' => 'Search notes, topics, projects…',
			'show_filter' => 'false',
		),
		is_array( $atts ) ? $atts : array(),
		'sg_global_search'
	);

	return ts_sg_nav_search_form_html(
		array(
			'layout'      => $atts['layout'],
			'placeholder' => $atts['placeholder'],
			'show_filter' => filter_var( $atts['show_filter'], FILTER_VALIDATE_BOOLEAN ),
		)
	);
}

/* -------------------------------------------------------------------------
 * Mega menu rendering
 * ---------------------------------------------------------------------- */

/**
 * Render the grouped child columns for one learning path.
 *
 * Desktop and mobile share this markup; CSS gives each context its own
 * layout. Only two levels below the root are shown in the header so the
 * panel stays readable: direct child = group, grandchild = quick link.
 *
 * @param WP_Term $root       Root term.
 * @param array   $tree       Course tree.
 * @param int     $link_limit Grandchild links per group.
 */
function ts_sg_nav_render_groups( $root, $tree, $link_limit = 6 ) {
	$groups = ts_sg_nav_tree_children( $tree, $root->term_id );

	if ( empty( $groups ) ) {
		return;
	}
	?>
	<div class="sg-nav-groups">
		<?php foreach ( $groups as $group ) : ?>
			<?php
			$group_url = ts_sg_nav_term_url( $group );
			$links     = ts_sg_nav_tree_children( $tree, $group->term_id );
			$shown     = array_slice( $links, 0, max( 1, (int) $link_limit ) );
			?>
			<section class="sg-nav-group" aria-label="<?php echo esc_attr( $group->name ); ?>">
				<?php if ( '' !== $group_url ) : ?>
					<a class="sg-nav-group__title" href="<?php echo esc_url( $group_url ); ?>">
						<span aria-hidden="true"><?php echo esc_html( ts_sg_nav_icon( $group ) ); ?></span>
						<span><?php echo esc_html( $group->name ); ?></span>
					</a>
				<?php endif; ?>

				<?php if ( ! empty( $shown ) ) : ?>
					<ul class="sg-nav-group__links">
						<?php foreach ( $shown as $link_term ) : ?>
							<?php $link_url = ts_sg_nav_term_url( $link_term ); ?>
							<li>
								<?php if ( '' !== $link_url ) : ?>
									<a href="<?php echo esc_url( $link_url ); ?>"><?php echo esc_html( $link_term->name ); ?></a>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>

					<?php if ( count( $links ) > count( $shown ) && '' !== $group_url ) : ?>
						<a class="sg-nav-group__more" href="<?php echo esc_url( $group_url ); ?>">
							<?php echo esc_html( sprintf( 'View all %d →', count( $links ) ) ); ?>
						</a>
					<?php endif; ?>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * [sg_mega_menu] Shortcode.
 *
 * Put this in a Shortcode block in place of the header Navigation block.
 * Existing logo, Search block, WhatsApp link and CTA remain untouched.
 *
 * @return string
 */
function ts_sg_nav_mega_menu_shortcode() {
	$tree        = ts_sg_nav_get_course_tree();
	$roots       = ts_sg_nav_tree_children( $tree, 0 );
	$fixed       = ts_sg_nav_fixed_links( 'desktop' );
	$mobile_links = ts_sg_nav_fixed_links( 'mobile' );
	$help_url    = ts_sg_nav_optional_page_url( 'project-help' );
	$help_url    = '' !== $help_url ? $help_url : home_url( '/project-help/' );

	ob_start();
	?>
	<div class="sg-site-nav" data-sg-step-8-nav>
		<nav class="sg-mega" aria-label="<?php esc_attr_e( 'Primary learning navigation', 'ts-block-theme' ); ?>">
			<ul class="sg-mega__list">
				<?php foreach ( $roots as $root ) : ?>
					<?php
					$root_url = ts_sg_nav_term_url( $root );
					$groups   = ts_sg_nav_tree_children( $tree, $root->term_id );
					?>
					<li class="sg-mega__item<?php echo ! empty( $groups ) ? ' has-panel' : ''; ?>">
						<?php if ( '' !== $root_url ) : ?>
							<a class="sg-mega__top" href="<?php echo esc_url( $root_url ); ?>">
								<span><?php echo esc_html( ts_sg_nav_root_short_label( $root ) ); ?></span>
								<?php if ( ! empty( $groups ) ) : ?>
									<span class="sg-mega__chevron" aria-hidden="true">▾</span>
								<?php endif; ?>
							</a>
						<?php endif; ?>

						<?php if ( ! empty( $groups ) && '' !== $root_url ) : ?>
							<div class="sg-mega__panel">
								<div class="sg-mega__panel-head">
									<span class="sg-mega__panel-icon" aria-hidden="true"><?php echo esc_html( ts_sg_nav_icon( $root ) ); ?></span>
									<div>
										<p><?php echo esc_html( ts_sg_nav_level_label( $root, false ) ); ?></p>
										<strong><?php echo esc_html( $root->name ); ?></strong>
										<?php if ( ! empty( $root->description ) ) : ?>
											<span><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $root->description ), 18, '…' ) ); ?></span>
										<?php endif; ?>
									</div>
									<a class="sg-mega__view" href="<?php echo esc_url( $root_url ); ?>">Open Path →</a>
								</div>

								<?php ts_sg_nav_render_groups( $root, $tree, 6 ); ?>

								<div class="sg-mega__panel-foot">
									<a href="<?php echo esc_url( $root_url ); ?>">View complete <?php echo esc_html( strtolower( ts_sg_nav_level_label( $root, false ) ) ); ?> →</a>
									<?php if ( 'gate-ec' === $root->slug ) : ?>
										<?php $gate_archive = get_post_type_archive_link( 'ece_gate_pyq' ); ?>
										<?php if ( $gate_archive ) : ?>
											<a href="<?php echo esc_url( $gate_archive ); ?>">Practice PYQ Sets →</a>
										<?php endif; ?>
									<?php else : ?>
										<?php $notes_archive = get_post_type_archive_link( 'ece_note' ); ?>
										<?php if ( $notes_archive ) : ?>
											<a href="<?php echo esc_url( $notes_archive ); ?>">Browse all notes →</a>
										<?php endif; ?>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>

				<?php foreach ( $fixed as $link ) : ?>
					<li class="sg-mega__item sg-mega__item--fixed <?php echo esc_attr( $link['class'] ); ?>">
						<a class="sg-mega__top" href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<details class="sg-mobile-nav">
			<summary class="sg-mobile-nav__toggle" aria-label="<?php esc_attr_e( 'Open navigation menu', 'ts-block-theme' ); ?>">
				<span class="sg-mobile-nav__bars" aria-hidden="true"><span></span><span></span><span></span></span>
				<span class="sg-mobile-nav__label sg-mobile-nav__label--open">Menu</span>
				<span class="sg-mobile-nav__label sg-mobile-nav__label--close">Close</span>
			</summary>

			<div class="sg-mobile-nav__panel">
				<div class="sg-mobile-nav__head">
					<p>Silicon Geek</p>
					<strong>Explore learning paths</strong>
					<?php
					echo ts_sg_nav_search_form_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						array(
							'layout'      => 'mobile',
							'placeholder' => 'Search notes, topics, projects…',
							'show_filter' => false,
							'value'       => '',
							'filter'      => 'all',
						)
					);
					?>
				</div>

				<nav class="sg-mobile-nav__links" aria-label="<?php esc_attr_e( 'Mobile learning navigation', 'ts-block-theme' ); ?>">
					<?php foreach ( $roots as $root ) : ?>
						<?php $root_url = ts_sg_nav_term_url( $root ); ?>
						<details class="sg-mobile-nav__section">
							<summary>
								<span class="sg-mobile-nav__section-icon" aria-hidden="true"><?php echo esc_html( ts_sg_nav_icon( $root ) ); ?></span>
								<span>
									<strong><?php echo esc_html( $root->name ); ?></strong>
									<small><?php echo esc_html( ts_sg_nav_level_label( $root, false ) ); ?></small>
								</span>
								<span class="sg-mobile-nav__arrow" aria-hidden="true">▾</span>
							</summary>
							<div class="sg-mobile-nav__section-body">
								<?php if ( '' !== $root_url ) : ?>
									<a class="sg-mobile-nav__root-link" href="<?php echo esc_url( $root_url ); ?>">Open <?php echo esc_html( $root->name ); ?> →</a>
								<?php endif; ?>
								<?php ts_sg_nav_render_groups( $root, $tree, 8 ); ?>
							</div>
						</details>
					<?php endforeach; ?>

					<div class="sg-mobile-nav__quick">
						<?php foreach ( $mobile_links as $link ) : ?>
							<a href="<?php echo esc_url( $link['url'] ); ?>">
								<span aria-hidden="true"><?php echo esc_html( $link['icon'] ); ?></span>
								<span><?php echo esc_html( $link['label'] ); ?></span>
								<span aria-hidden="true">→</span>
							</a>
						<?php endforeach; ?>
					</div>

					<a class="sg-mobile-nav__cta" href="<?php echo esc_url( $help_url ); ?>">📲 Get Project Help</a>
				</nav>
			</div>
		</details>
	</div>
	<?php

	return trim( ob_get_clean() );
}

/* -------------------------------------------------------------------------
 * Search results
 * ---------------------------------------------------------------------- */

/**
 * Find Course Structure folders matching a search query.
 *
 * WordPress post search does not search taxonomy names, so folder matches
 * are calculated separately and displayed above content results.
 *
 * @param string $search Raw search query.
 * @param array  $tree   Course tree.
 * @return WP_Term[]
 */
function ts_sg_nav_matching_course_terms( $search, $tree ) {
	$needle = trim( (string) $search );

	if ( '' === $needle || empty( $tree['by_id'] ) ) {
		return array();
	}

	$lower_needle = function_exists( 'mb_strtolower' ) ? mb_strtolower( $needle ) : strtolower( $needle );
	$matches      = array();

	foreach ( $tree['by_id'] as $term ) {
		$name        = function_exists( 'mb_strtolower' ) ? mb_strtolower( $term->name ) : strtolower( $term->name );
		$slug        = strtolower( str_replace( '-', ' ', $term->slug ) );
		$description = function_exists( 'mb_strtolower' ) ? mb_strtolower( wp_strip_all_tags( (string) $term->description ) ) : strtolower( wp_strip_all_tags( (string) $term->description ) );
		$score       = 0;

		if ( $name === $lower_needle ) {
			$score = 100;
		} elseif ( 0 === strpos( $name, $lower_needle ) ) {
			$score = 80;
		} elseif ( false !== strpos( $name, $lower_needle ) ) {
			$score = 60;
		} elseif ( false !== strpos( $slug, $lower_needle ) ) {
			$score = 40;
		} elseif ( false !== strpos( $description, $lower_needle ) ) {
			$score = 20;
		}

		if ( $score > 0 ) {
			$matches[] = array(
				'term'  => $term,
				'score' => $score,
				'depth' => max( 0, count( ts_sg_nav_tree_path( $term, $tree ) ) - 1 ),
				'order' => function_exists( 'ts_cs_course_get_order' ) ? ts_cs_course_get_order( $term->term_id ) : 0,
			);
		}
	}

	usort(
		$matches,
		function ( $a, $b ) {
			if ( $a['score'] === $b['score'] ) {
				if ( $a['depth'] === $b['depth'] ) {
					if ( $a['order'] === $b['order'] ) {
						return strnatcasecmp( $a['term']->name, $b['term']->name );
					}

					return $a['order'] < $b['order'] ? -1 : 1;
				}

				return $a['depth'] < $b['depth'] ? -1 : 1;
			}

			return $a['score'] > $b['score'] ? -1 : 1;
		}
	);

	return array_slice( wp_list_pluck( $matches, 'term' ), 0, 8 );
}

/**
 * Content-type badge data for one search result.
 *
 * @param WP_Post $post Result post.
 * @return array{label:string,icon:string,action:string,class:string}
 */
function ts_sg_nav_result_type_data( $post ) {
	$data = array(
		'ece_note'     => array( 'label' => 'Note', 'icon' => '📝', 'action' => 'Read Note', 'class' => 'is-note' ),
		'ece_gate_pyq' => array( 'label' => 'GATE PYQ Set', 'icon' => '🏆', 'action' => 'Practice Set', 'class' => 'is-gate' ),
		'ece_project'  => array( 'label' => 'Project', 'icon' => '🛠️', 'action' => 'View Project', 'class' => 'is-project' ),
		'page'         => array( 'label' => 'Page', 'icon' => '📄', 'action' => 'Open Page', 'class' => 'is-page' ),
		'post'         => array( 'label' => 'Article', 'icon' => '📰', 'action' => 'Read Article', 'class' => 'is-post' ),
	);

	return isset( $data[ $post->post_type ] ) ? $data[ $post->post_type ] : array(
		'label'  => 'Content',
		'icon'   => '📚',
		'action' => 'Open',
		'class'  => 'is-content',
	);
}

/**
 * Human context line for one result.
 *
 * Notes/PYQs show their Course Structure path. Projects show up to three
 * useful taxonomy values. Pages show ancestry when available.
 *
 * @param WP_Post $post Result post.
 * @return string
 */
function ts_sg_nav_result_context( $post ) {
	if ( in_array( $post->post_type, array( 'ece_note', 'ece_gate_pyq' ), true ) && function_exists( 'ts_cs_course_get_primary_term' ) ) {
		$primary = ts_cs_course_get_primary_term( $post->ID );

		if ( $primary && function_exists( 'ts_cs_course_get_path' ) ) {
			$names = wp_list_pluck( ts_cs_course_get_path( $primary ), 'name' );

			return implode( ' › ', array_map( 'sanitize_text_field', $names ) );
		}
	}

	if ( 'ece_project' === $post->post_type ) {
		$parts = array();

		foreach ( array( 'ece_subject', 'ece_year', 'ece_difficulty' ) as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$terms = wp_get_post_terms( $post->ID, $taxonomy, array( 'number' => 1 ) );

			if ( ! is_wp_error( $terms ) && ! empty( $terms[0] ) ) {
				$parts[] = $terms[0]->name;
			}
		}

		return implode( ' • ', $parts );
	}

	if ( 'page' === $post->post_type ) {
		$ancestors = array_reverse( get_post_ancestors( $post ) );
		$names     = array();

		foreach ( $ancestors as $ancestor_id ) {
			$names[] = get_the_title( $ancestor_id );
		}

		return implode( ' › ', array_filter( $names ) );
	}

	if ( 'post' === $post->post_type ) {
		$categories = get_the_category( $post->ID );

		return ! empty( $categories[0] ) ? $categories[0]->name : '';
	}

	return '';
}

/**
 * Short result excerpt.
 *
 * @param WP_Post $post Result post.
 * @return string
 */
function ts_sg_nav_result_excerpt( $post ) {
	if ( has_excerpt( $post ) ) {
		$text = get_the_excerpt( $post );
	} else {
		$text = wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) );
	}

	$text = trim( preg_replace( '/\s+/', ' ', (string) $text ) );

	return wp_trim_words( $text, 28, '…' );
}

/**
 * Search URL with an optional type filter.
 *
 * @param string $term   Search text.
 * @param string $filter Filter key.
 * @return string
 */
function ts_sg_nav_search_url( $term, $filter = 'all' ) {
	$args = array( 's' => $term );

	if ( 'all' !== $filter ) {
		$args['sg_type'] = $filter;
	}

	return add_query_arg( $args, home_url( '/' ) );
}

/**
 * [sg_search_results] Shortcode used by templates/search.html.
 *
 * @return string
 */
function ts_sg_nav_search_results_shortcode() {
	global $wp_query;

	$is_search_page = is_search();
	$search_query   = $is_search_page ? get_search_query() : '';
	$filter         = ts_sg_nav_current_search_filter();
	$filters        = ts_sg_nav_search_filters();
	$total          = $is_search_page && $wp_query ? (int) $wp_query->found_posts : 0;
	$tree           = ts_sg_nav_get_course_tree();
	$folder_matches = $is_search_page && in_array( $filter, array( 'all', 'notes', 'gate' ), true )
		? ts_sg_nav_matching_course_terms( $search_query, $tree )
		: array();
	$popular        = array( 'MOSFET', 'Verilog', 'UART', 'ESP32', 'Logic Gates', 'Op-Amp', 'ADC', 'K-Map' );

	ob_start();
	?>
	<section class="sg-search" data-sg-step-8-search>
		<div class="sg-search-hero">
			<div class="sg-search-wrap">
				<nav class="sg-search-breadcrumbs" aria-label="<?php esc_attr_e( 'Search breadcrumb', 'ts-block-theme' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
					<span aria-hidden="true">›</span>
					<span aria-current="page">Search</span>
				</nav>

				<p class="sg-search-eyebrow">🔎 Global Search</p>
				<?php if ( $is_search_page && '' !== trim( (string) $search_query ) ) : ?>
					<h1>Results for “<?php echo esc_html( $search_query ); ?>”</h1>
					<p class="sg-search-summary">
						<?php
						echo esc_html(
							sprintf(
								'%d content result%s mile. Course folders, notes, practice sets aur projects ek hi search me check karo.',
								$total,
								1 === $total ? '' : 's'
							)
						);
						?>
					</p>
				<?php else : ?>
					<h1>Search Silicon Geek</h1>
					<p class="sg-search-summary">Koi topic, subject, project ya GATE practice set search karo — sahi learning path yahin se milega.</p>
				<?php endif; ?>

				<?php
				echo ts_sg_nav_search_form_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					array(
						'layout'      => 'hero',
						'placeholder' => 'Search MOSFET, UART, Verilog, ESP32…',
						'show_filter' => true,
						'value'       => $search_query,
						'filter'      => $filter,
					)
				);
				?>

				<?php if ( $is_search_page && '' !== trim( (string) $search_query ) ) : ?>
					<div class="sg-search-tabs" aria-label="<?php esc_attr_e( 'Filter search results', 'ts-block-theme' ); ?>">
						<?php foreach ( $filters as $key => $label ) : ?>
							<a class="<?php echo $key === $filter ? 'is-active' : ''; ?>" href="<?php echo esc_url( ts_sg_nav_search_url( $search_query, $key ) ); ?>" <?php echo $key === $filter ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="sg-search-popular">
						<span>Popular:</span>
						<?php foreach ( $popular as $term ) : ?>
							<a href="<?php echo esc_url( ts_sg_nav_search_url( $term ) ); ?>"><?php echo esc_html( $term ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="sg-search-wrap sg-search-body">
			<?php if ( ! empty( $folder_matches ) ) : ?>
				<div class="sg-search-section-head">
					<p>Direct learning folders</p>
					<h2>Matching Course Folders</h2>
				</div>
				<div class="sg-search-folders">
					<?php foreach ( $folder_matches as $term ) : ?>
						<?php
						$url  = ts_sg_nav_term_url( $term );
						$path = ts_sg_nav_tree_path( $term, $tree );
						?>
						<?php if ( '' !== $url ) : ?>
							<a class="sg-search-folder" href="<?php echo esc_url( $url ); ?>">
								<span class="sg-search-folder__icon" aria-hidden="true"><?php echo esc_html( ts_sg_nav_icon( $term ) ); ?></span>
								<span class="sg-search-folder__body">
									<small><?php echo esc_html( ts_sg_nav_level_label( $term, false ) ); ?></small>
									<strong><?php echo esc_html( $term->name ); ?></strong>
									<span><?php echo esc_html( implode( ' › ', wp_list_pluck( $path, 'name' ) ) ); ?></span>
								</span>
								<span class="sg-search-folder__arrow" aria-hidden="true">→</span>
							</a>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $is_search_page ) : ?>
				<div class="sg-search-section-head">
					<p><?php echo esc_html( $filters[ $filter ] ); ?></p>
					<h2>Content Results</h2>
				</div>

				<?php if ( have_posts() ) : ?>
					<div class="sg-search-results">
						<?php
						while ( have_posts() ) :
							the_post();
							$post    = get_post();
							$type    = ts_sg_nav_result_type_data( $post );
							$context = ts_sg_nav_result_context( $post );
							$url     = get_permalink( $post );
							?>
							<article class="sg-search-card <?php echo esc_attr( $type['class'] ); ?>">
								<?php if ( has_post_thumbnail( $post ) && $url ) : ?>
									<a class="sg-search-card__media" href="<?php echo esc_url( $url ); ?>" aria-hidden="true" tabindex="-1">
										<?php echo get_the_post_thumbnail( $post, 'cs-card', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
									</a>
								<?php else : ?>
									<div class="sg-search-card__media sg-search-card__media--fallback" aria-hidden="true"><?php echo esc_html( $type['icon'] ); ?></div>
								<?php endif; ?>

								<div class="sg-search-card__body">
									<div class="sg-search-card__meta-top">
										<span class="sg-search-card__type"><?php echo esc_html( $type['icon'] . ' ' . $type['label'] ); ?></span>
										<?php if ( '' !== $context ) : ?>
											<span class="sg-search-card__context"><?php echo esc_html( $context ); ?></span>
										<?php endif; ?>
									</div>

									<h3>
										<?php if ( $url ) : ?>
											<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
										<?php else : ?>
											<?php echo esc_html( get_the_title( $post ) ); ?>
										<?php endif; ?>
									</h3>

									<p><?php echo esc_html( ts_sg_nav_result_excerpt( $post ) ); ?></p>

									<div class="sg-search-card__foot">
										<span><?php echo esc_html( get_the_date( 'j M Y', $post ) ); ?></span>
										<?php if ( 'ece_note' === $post->post_type && function_exists( 'ts_cs_course_index_reading_time' ) ) : ?>
											<span><?php echo esc_html( sprintf( '%d min read', ts_cs_course_index_reading_time( $post ) ) ); ?></span>
										<?php endif; ?>
										<?php if ( $url ) : ?>
											<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $type['action'] ); ?> →</a>
										<?php endif; ?>
									</div>
								</div>
							</article>
						<?php endwhile; ?>
					</div>

					<?php
					$pagination = paginate_links(
						array(
							'total'     => $wp_query ? (int) $wp_query->max_num_pages : 1,
							'current'   => max( 1, (int) get_query_var( 'paged' ) ),
							'type'      => 'list',
							'prev_text' => '← Previous',
							'next_text' => 'Next →',
							'add_args'  => array_filter(
								array(
									's'       => $search_query,
									'sg_type' => 'all' !== $filter ? $filter : false,
								)
							),
						)
					);
					?>
					<?php if ( $pagination ) : ?>
						<nav class="sg-search-pagination" aria-label="<?php esc_attr_e( 'Search results pages', 'ts-block-theme' ); ?>">
							<?php echo wp_kses_post( $pagination ); ?>
						</nav>
					<?php endif; ?>
				<?php else : ?>
					<div class="sg-search-empty">
						<span aria-hidden="true">🔍</span>
						<h3>Koi exact result nahi mila</h3>
						<p>Spelling check karo ya thoda chhota keyword try karo. Example: “MOSFET biasing” ki jagah pehle sirf “MOSFET” search karo.</p>
						<div class="sg-search-empty__links">
							<?php foreach ( $popular as $term ) : ?>
								<a href="<?php echo esc_url( ts_sg_nav_search_url( $term ) ); ?>"><?php echo esc_html( $term ); ?></a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>

			<aside class="sg-search-help">
				<div>
					<p>Topic nahi mil raha?</p>
					<h2>Learning path se step-by-step shuru karo.</h2>
					<p>BTech ECE, GATE EC, Embedded aur VLSI ke folders sahi order me rakhe gaye hain, taaki bina random search ke bhi agla topic clear rahe.</p>
				</div>
				<div class="sg-search-help__actions">
					<?php $first_root = ! empty( $tree['by_parent'][0][0] ) ? $tree['by_parent'][0][0] : null; ?>
					<?php if ( $first_root ) : ?>
						<?php $first_url = ts_sg_nav_term_url( $first_root ); ?>
						<?php if ( '' !== $first_url ) : ?>
							<a href="<?php echo esc_url( $first_url ); ?>">Start Learning →</a>
						<?php endif; ?>
					<?php endif; ?>
					<a href="<?php echo esc_url( home_url( '/project-help/' ) ); ?>">Get Project Help</a>
				</div>
			</aside>
		</div>
	</section>
	<?php

	return trim( ob_get_clean() );
}
