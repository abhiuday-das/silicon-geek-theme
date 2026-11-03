<?php
/**
 * Silicon Geek — SEO, Performance & Launch Polish (Step 9).
 *
 * NEW FILE:
 * wp-content/themes/ts-block-theme/inc/custom-post-type/ece-seo-launch.php
 *
 * Load it from functions.php immediately after ece-search-nav.php:
 * require_once get_template_directory() . '/inc/custom-post-type/ece-seo-launch.php';
 *
 * What it adds:
 *  - Automatic SEO titles and meta descriptions for the homepage, ECE Notes,
 *    GATE PYQ Sets, Projects, Course Structure folders and archives.
 *  - Canonical URLs, Open Graph/Twitter cards and JSON-LD structured data.
 *  - noindex for internal Search, 404, attachment pages and filtered
 *    Project/GATE archive result pages.
 *  - Sitemap URL inside the virtual robots.txt (WordPress core sitemap).
 *  - Optional Customizer controls for a default description/share image,
 *    X/Twitter handle, Google site verification and GA4 measurement ID.
 *  - Small safe performance help: font/CDN resource hints, deferred
 *    non-critical theme scripts, async image decoding and an eager preload
 *    for the featured image on single learning pages.
 *  - [sg_launch_404] Shortcode used by templates/404.html.
 *
 * Duplicate-SEO guard:
 *  If Yoast SEO, Rank Math, All in One SEO or SEOPress is active, this file
 *  will not print its own title/meta/schema layer. Performance helpers and
 *  the 404 Shortcode remain available.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'TS_CS_COURSE_TAX' ) ) {
	define( 'TS_CS_COURSE_TAX', 'ece_course' );
}

add_action( 'customize_register', 'ts_sg_seo_customize_register' );
add_action( 'init', 'ts_sg_seo_remove_core_canonical', 20 );
add_filter( 'pre_get_document_title', 'ts_sg_seo_document_title', 20 );
add_action( 'wp_head', 'ts_sg_seo_output_head', 5 );
add_filter( 'wp_robots', 'ts_sg_seo_filter_robots' );
add_filter( 'robots_txt', 'ts_sg_seo_robots_txt', 10, 2 );
add_action( 'wp_enqueue_scripts', 'ts_sg_seo_enqueue_analytics', 30 );
add_filter( 'wp_resource_hints', 'ts_sg_perf_resource_hints', 10, 2 );
add_filter( 'script_loader_tag', 'ts_sg_perf_defer_scripts', 10, 3 );
add_filter( 'wp_get_attachment_image_attributes', 'ts_sg_perf_image_attributes', 10, 3 );
add_shortcode( 'sg_launch_404', 'ts_sg_launch_404_shortcode' );

/* -------------------------------------------------------------------------
 * Customizer settings
 * ---------------------------------------------------------------------- */

/**
 * Validate a GA4 measurement ID.
 *
 * @param mixed $value Raw customizer value.
 * @return string
 */
function ts_sg_seo_sanitize_ga4_id( $value ) {
	$value = strtoupper( trim( (string) $value ) );

	return preg_match( '/^G-[A-Z0-9]+$/', $value ) ? $value : '';
}

/**
 * Accept either a raw Google verification token or the full meta tag.
 *
 * @param mixed $value Raw customizer value.
 * @return string
 */
function ts_sg_seo_sanitize_verification( $value ) {
	$value = trim( (string) $value );

	if ( preg_match( '/content\s*=\s*["\']([^"\']+)["\']/i', $value, $matches ) ) {
		$value = $matches[1];
	}

	return sanitize_text_field( $value );
}

/**
 * Add launch/SEO controls to the Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function ts_sg_seo_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'sg_seo_launch',
		array(
			'title'       => __( 'Silicon Geek SEO & Launch', 'ts-block-theme' ),
			'priority'    => 35,
			'description' => __( 'Default search/social settings. Leave verification and GA4 blank until the real domain is live.', 'ts-block-theme' ),
		)
	);

	$text_fields = array(
		'sg_seo_default_description' => array(
			'label'       => __( 'Default SEO Description', 'ts-block-theme' ),
			'description' => __( 'Used on the homepage and whenever a page has no excerpt/description. Aim for about 140–160 characters.', 'ts-block-theme' ),
			'type'        => 'textarea',
			'sanitize'    => 'sanitize_textarea_field',
		),
		'sg_seo_twitter_handle'      => array(
			'label'       => __( 'X / Twitter Handle (optional)', 'ts-block-theme' ),
			'description' => __( 'Example: @silicongeek. Leave blank if there is no account yet.', 'ts-block-theme' ),
			'type'        => 'text',
			'sanitize'    => 'sanitize_text_field',
		),
		'sg_google_site_verification' => array(
			'label'       => __( 'Google Site Verification Token (optional)', 'ts-block-theme' ),
			'description' => __( 'Paste only the content token from Search Console, or the full meta tag. Leave blank on localhost.', 'ts-block-theme' ),
			'type'        => 'text',
			'sanitize'    => 'ts_sg_seo_sanitize_verification',
		),
		'sg_ga4_measurement_id'      => array(
			'label'       => __( 'GA4 Measurement ID (optional)', 'ts-block-theme' ),
			'description' => __( 'Example: G-XXXXXXXXXX. Logged-in users are not tracked. Add this only after the live privacy policy is ready.', 'ts-block-theme' ),
			'type'        => 'text',
			'sanitize'    => 'ts_sg_seo_sanitize_ga4_id',
		),
	);

	foreach ( $text_fields as $id => $field ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => '',
				'sanitize_callback' => $field['sanitize'],
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$id,
			array(
				'label'       => $field['label'],
				'description' => $field['description'],
				'section'     => 'sg_seo_launch',
				'settings'    => $id,
				'type'        => $field['type'],
			)
		);
	}

	$wp_customize->add_setting(
		'sg_seo_default_image',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'sg_seo_default_image',
			array(
				'label'       => __( 'Default Social Share Image', 'ts-block-theme' ),
				'description' => __( 'Recommended: 1200 × 630 JPG/PNG. Used when a page has no featured image.', 'ts-block-theme' ),
				'section'     => 'sg_seo_launch',
				'settings'    => 'sg_seo_default_image',
			)
		)
	);
}

/* -------------------------------------------------------------------------
 * SEO helpers
 * ---------------------------------------------------------------------- */

/**
 * Whether a full SEO plugin should own title/meta/schema output.
 *
 * @return bool
 */
function ts_sg_seo_has_external_plugin() {
	return defined( 'WPSEO_VERSION' )
		|| defined( 'RANK_MATH_VERSION' )
		|| defined( 'AIOSEO_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| class_exists( 'WPSEO_Frontend' )
		|| class_exists( 'RankMath' )
		|| class_exists( 'AIOSEO\\Plugin\\AIOSEO' );
}

/**
 * Whether this file should print its own SEO layer.
 *
 * @return bool
 */
function ts_sg_seo_is_enabled() {
	return ! is_admin() && ! ts_sg_seo_has_external_plugin();
}

/**
 * Remove the core canonical only when our canonical will replace it.
 */
function ts_sg_seo_remove_core_canonical() {
	if ( ! ts_sg_seo_has_external_plugin() ) {
		remove_action( 'wp_head', 'rel_canonical' );
	}
}

/**
 * Clean text and optionally trim it for a meta description.
 *
 * @param string $text  Raw text/HTML.
 * @param int    $limit Character limit, or 0 for no limit.
 * @return string
 */
function ts_sg_seo_clean_text( $text, $limit = 0 ) {
	$text = html_entity_decode(
		wp_strip_all_tags( strip_shortcodes( (string) $text ) ),
		ENT_QUOTES,
		get_bloginfo( 'charset' ) ? get_bloginfo( 'charset' ) : 'UTF-8'
	);
	$text = preg_replace( '/\s+/u', ' ', (string) $text );
	$text = trim( (string) $text );

	if ( ! $limit ) {
		return $text;
	}

	$length = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );

	if ( $length <= $limit ) {
		return $text;
	}

	$cut = function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $limit ) : substr( $text, 0, $limit );
	$space = function_exists( 'mb_strrpos' ) ? mb_strrpos( $cut, ' ' ) : strrpos( $cut, ' ' );

	if ( $space && $space > (int) ( $limit * 0.65 ) ) {
		$cut = function_exists( 'mb_substr' ) ? mb_substr( $cut, 0, $space ) : substr( $cut, 0, $space );
	}

	return rtrim( trim( $cut ), " \t\n\r\0\x0B,;:-" ) . '…';
}

/**
 * Default site description from the Customizer/tagline.
 *
 * @return string
 */
function ts_sg_seo_default_description() {
	$custom = trim( (string) get_theme_mod( 'sg_seo_default_description', '' ) );

	if ( '' !== $custom ) {
		return ts_sg_seo_clean_text( $custom, 165 );
	}

	$tagline = trim( (string) get_bloginfo( 'description' ) );

	if ( '' !== $tagline && false === stripos( $tagline, 'Just another WordPress' ) ) {
		return ts_sg_seo_clean_text( $tagline, 165 );
	}

	return 'Silicon Geek par BTech ECE notes, GATE EC PYQs, Embedded Systems, VLSI aur practical ECE projects simple Hinglish me seekho.';
}

/**
 * Normalised X/Twitter handle without the leading @ for internal checks.
 *
 * @return string
 */
function ts_sg_seo_twitter_handle() {
	$handle = ltrim( trim( (string) get_theme_mod( 'sg_seo_twitter_handle', '' ) ), '@' );

	return preg_match( '/^[A-Za-z0-9_]{1,15}$/', $handle ) ? $handle : '';
}

/**
 * Return image data for SEO/social cards.
 *
 * @param WP_Post|null $post Optional singular post.
 * @return array{url:string,width:int,height:int,alt:string}
 */
function ts_sg_seo_get_image_data( $post = null ) {
	$image = array(
		'url'    => '',
		'width'  => 0,
		'height' => 0,
		'alt'    => '',
	);

	if ( $post && has_post_thumbnail( $post ) ) {
		$attachment_id = get_post_thumbnail_id( $post );
		$src           = wp_get_attachment_image_src( $attachment_id, 'cs-hero' );

		if ( ! $src ) {
			$src = wp_get_attachment_image_src( $attachment_id, 'large' );
		}

		if ( $src ) {
			$image['url']    = $src[0];
			$image['width']  = isset( $src[1] ) ? (int) $src[1] : 0;
			$image['height'] = isset( $src[2] ) ? (int) $src[2] : 0;
			$image['alt']    = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );

			return $image;
		}
	}

	$default_url = trim( (string) get_theme_mod( 'sg_seo_default_image', '' ) );

	if ( '' !== $default_url ) {
		$image['url'] = $default_url;
		$attachment_id = attachment_url_to_postid( $default_url );

		if ( $attachment_id ) {
			$src = wp_get_attachment_image_src( $attachment_id, 'full' );

			if ( $src ) {
				$image['width']  = isset( $src[1] ) ? (int) $src[1] : 0;
				$image['height'] = isset( $src[2] ) ? (int) $src[2] : 0;
				$image['alt']    = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
			}
		}

		return $image;
	}

	$logo_id = (int) get_theme_mod( 'custom_logo', 0 );

	if ( $logo_id ) {
		$src = wp_get_attachment_image_src( $logo_id, 'full' );

		if ( $src ) {
			$image['url']    = $src[0];
			$image['width']  = isset( $src[1] ) ? (int) $src[1] : 0;
			$image['height'] = isset( $src[2] ) ? (int) $src[2] : 0;
			$image['alt']    = get_bloginfo( 'name' );

			return $image;
		}
	}

	$icon = get_site_icon_url( 512 );

	if ( $icon ) {
		$image['url']    = $icon;
		$image['width']  = 512;
		$image['height'] = 512;
		$image['alt']    = get_bloginfo( 'name' );
	}

	return $image;
}

/**
 * Return the actual site logo/icon URL for Organization schema.
 *
 * @return string
 */
function ts_sg_seo_get_logo_url() {
	$logo_id = (int) get_theme_mod( 'custom_logo', 0 );

	if ( $logo_id ) {
		$logo = wp_get_attachment_image_url( $logo_id, 'full' );

		if ( $logo ) {
			return $logo;
		}
	}

	return (string) get_site_icon_url( 512 );
}

/**
 * Check for one non-empty GET parameter without treating pagination as a filter.
 *
 * @param string[] $keys Parameter names.
 * @return bool
 */
function ts_sg_seo_has_filter_params( $keys ) {
	foreach ( $keys as $key ) {
		if ( ! isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			continue;
		}

		$value = wp_unslash( $_GET[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Read a positive integer pagination parameter safely.
 *
 * @param string $key GET key.
 * @return int
 */
function ts_sg_seo_get_positive_int_param( $key ) {
	if ( ! isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return 1;
	}

	$value = wp_unslash( $_GET[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return is_scalar( $value ) ? max( 1, absint( $value ) ) : 1;
}

/**
 * Whether the current view should be noindex,follow.
 *
 * @return bool
 */
function ts_sg_seo_is_noindex_context() {
	if ( is_search() || is_404() || is_attachment() ) {
		return true;
	}

	if ( is_singular() ) {
		$post = get_post();

		return $post && post_password_required( $post );
	}

	if ( is_post_type_archive( 'ece_project' ) ) {
		return ts_sg_seo_has_filter_params( array( 'project_subject', 'project_difficulty', 'project_year', 'project_search' ) );
	}

	if ( is_post_type_archive( 'ece_gate_pyq' ) ) {
		return ts_sg_seo_has_filter_params( array( 'pyq_course', 'pyq_search' ) );
	}

	return false;
}

/**
 * Build breadcrumbs for the current context.
 *
 * @param WP_Post|WP_Term|null $object     Current post/term.
 * @param string               $title      Current page title.
 * @param string               $current_url Canonical/current URL.
 * @return array<int,array{name:string,url:string}>
 */
function ts_sg_seo_build_breadcrumbs( $object, $title, $current_url ) {
	$items = array(
		array(
			'name' => 'Home',
			'url'  => home_url( '/' ),
		),
	);

	if ( $object instanceof WP_Term && function_exists( 'ts_cs_course_get_path' ) ) {
		foreach ( ts_cs_course_get_path( $object ) as $term ) {
			$url = get_term_link( $term );

			if ( ! is_wp_error( $url ) ) {
				$items[] = array(
					'name' => $term->name,
					'url'  => $url,
				);
			}
		}

		return $items;
	}

	if ( $object instanceof WP_Post ) {
		if ( in_array( $object->post_type, array( 'ece_note', 'ece_gate_pyq' ), true ) && function_exists( 'ts_cs_course_get_primary_term' ) ) {
			$primary = ts_cs_course_get_primary_term( $object->ID );

			if ( $primary && function_exists( 'ts_cs_course_get_path' ) ) {
				foreach ( ts_cs_course_get_path( $primary ) as $term ) {
					$url = get_term_link( $term );

					if ( ! is_wp_error( $url ) ) {
						$items[] = array(
							'name' => $term->name,
							'url'  => $url,
						);
					}
				}
			}
		} elseif ( 'ece_project' === $object->post_type ) {
			$archive = get_post_type_archive_link( 'ece_project' );

			if ( $archive ) {
				$items[] = array(
					'name' => 'Projects',
					'url'  => $archive,
				);
			}
		} elseif ( 'page' === $object->post_type ) {
			foreach ( array_reverse( get_post_ancestors( $object ) ) as $ancestor_id ) {
				$url = get_permalink( $ancestor_id );

				if ( $url ) {
					$items[] = array(
						'name' => get_the_title( $ancestor_id ),
						'url'  => $url,
					);
				}
			}
		}

		if ( '' !== $current_url ) {
			$items[] = array(
				'name' => $title,
				'url'  => $current_url,
			);
		}

		return $items;
	}

	if ( '' !== $current_url && ! is_front_page() ) {
		$items[] = array(
			'name' => $title,
			'url'  => $current_url,
		);
	}

	return $items;
}

/**
 * Build all SEO data for the current request once.
 *
 * @return array<string,mixed>
 */
function ts_sg_seo_get_current_data() {
	static $data = null;

	if ( null !== $data ) {
		return $data;
	}

	$site_name = get_bloginfo( 'name' );
	$object    = null;

	$data = array(
		'site_name'    => $site_name,
		'title'        => $site_name,
		'description'  => ts_sg_seo_default_description(),
		'canonical'    => home_url( '/' ),
		'type'         => 'website',
		'schema_page'  => 'WebPage',
		'image'        => ts_sg_seo_get_image_data(),
		'noindex'      => ts_sg_seo_is_noindex_context(),
		'breadcrumbs'  => array(),
		'post'         => null,
		'published'    => '',
		'modified'     => '',
		'author'       => '',
		'author_url'   => '',
	);

	if ( is_404() ) {
		$data['title']       = 'Page Not Found';
		$data['description'] = 'Ye page nahi mila. Silicon Geek par ECE notes, GATE PYQs, Embedded, VLSI aur practical projects search karke aage badho.';
		$data['canonical']   = '';
	} elseif ( is_search() ) {
		$query               = get_search_query( false );
		$data['title']       = '' !== trim( (string) $query ) ? sprintf( 'Search results for “%s”', $query ) : 'Search';
		$data['description'] = 'Silicon Geek par ECE notes, GATE PYQ practice sets, projects aur course folders search karo.';
		$data['canonical']   = '';
	} elseif ( is_front_page() ) {
		$data['title']     = $site_name;
		$data['canonical'] = home_url( '/' );
	} elseif ( is_tax( TS_CS_COURSE_TAX ) ) {
		$term   = get_queried_object();
		$object = $term instanceof WP_Term ? $term : null;

		if ( $object ) {
			$path  = function_exists( 'ts_cs_course_get_path' ) ? ts_cs_course_get_path( $object ) : array( $object );
			$root  = ! empty( $path[0] ) ? $path[0] : $object;
			$depth = max( 0, count( $path ) - 1 );
			$url   = get_term_link( $object );

			if ( ! is_wp_error( $url ) ) {
				$paged = max( (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );

				if ( $paged > 1 ) {
					$url = trailingslashit( $url ) . 'page/' . $paged . '/';
				}

				$data['canonical'] = $url;
			}

			if ( 0 === $depth ) {
				$root_titles = array(
					'btech-ece'        => 'BTech ECE Notes – Year-wise Subjects, Units & Topics',
					'gate-ec'          => 'GATE EC Notes & PYQ Practice Sets',
					'embedded-systems' => 'Embedded Systems Learning Path – Modules & Topics',
					'vlsi-design'      => 'VLSI Design Learning Path – Modules & Topics',
				);

				$data['title'] = isset( $root_titles[ $root->slug ] ) ? $root_titles[ $root->slug ] : $object->name;
			} elseif ( 'btech-ece' === $root->slug ) {
				$data['title'] = 1 === $depth ? $object->name . ' ECE Subjects & Notes' : $object->name . ' Notes';
			} elseif ( 'gate-ec' === $root->slug ) {
				$data['title'] = $object->name . ' GATE Notes & PYQs';
			} elseif ( in_array( $root->slug, array( 'embedded-systems', 'vlsi-design' ), true ) ) {
				$data['title'] = $object->name . ( 1 === $depth ? ' Module' : ' Lessons' );
			} else {
				$data['title'] = $object->name;
			}

			if ( '' !== trim( (string) $object->description ) ) {
				$data['description'] = ts_sg_seo_clean_text( $object->description, 165 );
			} else {
				$path_names          = implode( ' › ', wp_list_pluck( $path, 'name' ) );
				$data['description'] = ts_sg_seo_clean_text(
					sprintf( 'Learn %s step by step on Silicon Geek. %s ke ordered folders, notes aur practice resources ek sahi learning order me explore karo.', $object->name, $path_names ),
					165
				);
			}

			$data['schema_page'] = 'CollectionPage';
		}
	} elseif ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );

		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}

		$archive = $post_type ? get_post_type_archive_link( $post_type ) : '';

		if ( $archive ) {
			$data['canonical'] = $archive;

			if ( 'ece_project' === $post_type && ! $data['noindex'] ) {
				$page = ts_sg_seo_get_positive_int_param( 'project_page' );

				if ( $page > 1 ) {
					$data['canonical'] = add_query_arg( 'project_page', $page, $archive );
				}
			}

			if ( 'ece_gate_pyq' === $post_type && ! $data['noindex'] ) {
				$page = ts_sg_seo_get_positive_int_param( 'pyq_page' );

				if ( $page > 1 ) {
					$data['canonical'] = add_query_arg( 'pyq_page', $page, $archive );
				}
			}
		}

		$archive_data = array(
			'ece_note'     => array(
				'title'       => 'ECE Notes – Subject-wise & Topic-wise',
				'description' => 'BTech ECE, GATE EC, Embedded aur VLSI notes ko subject, unit aur topic-wise sahi order me padho.',
			),
			'ece_project'  => array(
				'title'       => 'ECE Projects with Circuit, Code & Viva Guide',
				'description' => 'Practical ECE projects ke components, circuit connections, code, build steps, testing, troubleshooting aur viva questions explore karo.',
			),
			'ece_gate_pyq' => array(
				'title'       => 'GATE EC PYQ Practice Sets with Answers & Solutions',
				'description' => 'Topic-wise GATE EC previous year questions ko pehle khud solve karo, phir answer aur step-by-step solution check karo.',
			),
		);

		if ( isset( $archive_data[ $post_type ] ) ) {
			$data['title']       = $archive_data[ $post_type ]['title'];
			$data['description'] = $archive_data[ $post_type ]['description'];
		} else {
			$data['title'] = ts_sg_seo_clean_text( get_the_archive_title() );
		}

		$data['schema_page'] = 'CollectionPage';
	} elseif ( is_singular() ) {
		$post   = get_post();
		$object = $post;

		if ( $post ) {
			$data['post']      = $post;
			$data['title']     = get_the_title( $post );
			$data['type']      = 'article';
			$data['canonical'] = wp_get_canonical_url( $post );
			$data['image']     = ts_sg_seo_get_image_data( $post );
			$data['published']  = get_post_time( 'c', true, $post );
			$data['author_url'] = get_author_posts_url( (int) $post->post_author );
			$data['modified']  = get_post_modified_time( 'c', true, $post );
			$data['author']    = get_the_author_meta( 'display_name', (int) $post->post_author );

			if ( post_password_required( $post ) ) {
				$data['description'] = ts_sg_seo_default_description();
				$data['image']       = ts_sg_seo_get_image_data();
			} elseif ( has_excerpt( $post ) ) {
				$data['description'] = ts_sg_seo_clean_text( get_the_excerpt( $post ), 165 );
			} elseif ( 'ece_note' === $post->post_type ) {
				$data['description'] = ts_sg_seo_clean_text(
					sprintf( 'Learn %s in simple Hinglish with clear concepts, examples and exam-focused explanation on Silicon Geek.', get_the_title( $post ) ),
					165
				);
			} elseif ( 'ece_project' === $post->post_type ) {
				$data['description'] = ts_sg_seo_clean_text(
					sprintf( 'Build %s step by step with components, circuit connections, code, testing, troubleshooting and viva preparation.', get_the_title( $post ) ),
					165
				);
			} elseif ( 'ece_gate_pyq' === $post->post_type ) {
				$count = function_exists( 'ts_sg_gate_set_get_question_count' ) ? ts_sg_gate_set_get_question_count( $post ) : 0;
				$years = function_exists( 'ts_sg_gate_set_get_year_range' ) ? ts_sg_gate_set_get_year_range( $post, true ) : '';
				$meta  = array();

				if ( $count ) {
					$meta[] = sprintf( '%d questions', $count );
				}

				if ( '' !== $years ) {
					$meta[] = 'GATE ' . $years;
				}

				$data['description'] = ts_sg_seo_clean_text(
					sprintf(
						'Practice %s with answers and step-by-step solutions.%s Pehle khud solve karo, phir solution check karo.',
						get_the_title( $post ),
						$meta ? ' Includes ' . implode( ' • ', $meta ) . '.' : ''
					),
					165
				);
			} else {
				$content_text        = ts_sg_seo_clean_text( $post->post_content, 165 );
				$data['description'] = '' !== $content_text ? $content_text : ts_sg_seo_default_description();
			}
		}
	} elseif ( is_home() ) {
		$page_id = (int) get_option( 'page_for_posts' );

		$data['title']     = $page_id ? get_the_title( $page_id ) : 'Latest Articles';
		$data['canonical'] = $page_id ? get_permalink( $page_id ) : home_url( '/' );
		$data['schema_page'] = 'CollectionPage';
	} elseif ( is_archive() ) {
		$title               = ts_sg_seo_clean_text( get_the_archive_title() );
		$title               = preg_replace( '/^(Category|Tag|Author|Archives?):\s*/i', '', $title );
		$data['title']       = $title ? $title : 'Archive';
		$archive_description = ts_sg_seo_clean_text( get_the_archive_description(), 165 );

		if ( '' !== $archive_description ) {
			$data['description'] = $archive_description;
		}
		$data['schema_page'] = 'CollectionPage';
		$queried             = get_queried_object();

		if ( $queried instanceof WP_Term ) {
			$object = $queried;
			$url    = get_term_link( $queried );

			if ( ! is_wp_error( $url ) ) {
				$data['canonical'] = $url;
			}
		}
	}

	$data['breadcrumbs'] = ts_sg_seo_build_breadcrumbs( $object, $data['title'], $data['canonical'] );

	return $data;
}

/**
 * Full document title.
 *
 * @param string $title Existing title.
 * @return string
 */
function ts_sg_seo_document_title( $title ) {
	if ( ! ts_sg_seo_is_enabled() ) {
		return $title;
	}

	$data      = ts_sg_seo_get_current_data();
	$site_name = $data['site_name'];
	$paged     = max( (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	$suffix    = $paged > 1 ? sprintf( ' – Page %d', $paged ) : '';

	if ( is_front_page() ) {
		$tagline = trim( (string) get_bloginfo( 'description' ) );

		if ( '' === $tagline || false !== stripos( $tagline, 'Just another WordPress' ) ) {
			$tagline = 'ECE Notes, GATE EC, Embedded & VLSI';
		}

		return $site_name . ' | ' . $tagline . $suffix;
	}

	if ( '' === $data['title'] || $data['title'] === $site_name ) {
		return $site_name . $suffix;
	}

	// Avoid "Title | Silicon Geek | Silicon Geek" when a post title already has the brand suffix.
	if ( false !== stripos( $data['title'], ' | ' . $site_name ) || false !== stripos( $data['title'], ' – ' . $site_name ) ) {
		return $data['title'] . $suffix;
	}

	return $data['title'] . $suffix . ' | ' . $site_name;
}

/**
 * Robots directives.
 *
 * @param array $robots WordPress robots directives.
 * @return array
 */
function ts_sg_seo_filter_robots( $robots ) {
	if ( ts_sg_seo_has_external_plugin() ) {
		return $robots;
	}

	if ( ts_sg_seo_is_noindex_context() ) {
		$robots['noindex']  = true;
		$robots['follow']   = true;
		$robots['nofollow'] = false;
	} else {
		$robots['max-image-preview'] = 'large';
	}

	return $robots;
}

/**
 * Add the WordPress core sitemap to the virtual robots.txt.
 *
 * @param string $output Robots output.
 * @param bool   $public Whether search engines are allowed.
 * @return string
 */
function ts_sg_seo_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}

	$sitemap = home_url( '/wp-sitemap.xml' );

	if ( false === stripos( $output, 'Sitemap:' ) ) {
		$output = rtrim( $output ) . "\nSitemap: " . esc_url_raw( $sitemap ) . "\n";
	}

	return $output;
}

/**
 * Build JSON-LD graph for the current page.
 *
 * @param array $data SEO data.
 * @return array<string,mixed>
 */
function ts_sg_seo_build_schema( $data ) {
	$home       = home_url( '/' );
	$site_id    = trailingslashit( $home ) . '#website';
	$org_id     = trailingslashit( $home ) . '#organization';
	$image      = $data['image'];
	$graph      = array();
	$org_schema = array(
		'@type' => 'Organization',
		'@id'   => $org_id,
		'name'  => $data['site_name'],
		'url'   => $home,
	);

	$logo_url = ts_sg_seo_get_logo_url();

	if ( '' !== $logo_url ) {
		$org_schema['logo'] = $logo_url;
	}

	$email = trim( (string) get_theme_mod( 'site_email', '' ) );

	if ( is_email( $email ) ) {
		$org_schema['email'] = $email;
	}

	$city = trim( (string) get_theme_mod( 'site_city', '' ) );

	if ( '' !== $city ) {
		$org_schema['address'] = array(
			'@type'           => 'PostalAddress',
			'addressLocality' => $city,
			'addressCountry'  => 'IN',
		);
	}

	$graph[] = $org_schema;
	$graph[] = array(
		'@type'           => 'WebSite',
		'@id'             => $site_id,
		'name'            => $data['site_name'],
		'url'             => $home,
		'publisher'       => array( '@id' => $org_id ),
		'inLanguage'      => get_bloginfo( 'language' ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	$page_url = '' !== $data['canonical'] ? $data['canonical'] : $home;

	$graph[] = array(
		'@type'       => $data['schema_page'],
		'@id'         => $page_url . '#webpage',
		'name'        => $data['title'],
		'description' => $data['description'],
		'url'         => $page_url,
		'isPartOf'    => array( '@id' => $site_id ),
		'inLanguage'  => get_bloginfo( 'language' ),
	);

	if ( count( $data['breadcrumbs'] ) > 1 ) {
		$items = array();

		foreach ( $data['breadcrumbs'] as $index => $crumb ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $crumb['name'],
				'item'     => $crumb['url'],
			);
		}

		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $page_url . '#breadcrumb',
			'itemListElement' => $items,
		);
	}

	if ( ! empty( $data['post'] ) && ! post_password_required( $data['post'] ) ) {
		$post = $data['post'];
		$type = 'ece_note' === $post->post_type ? array( 'Article', 'LearningResource' ) : 'Article';
		$article = array(
			'@type'            => $type,
			'@id'              => $page_url . '#article',
			'headline'         => $data['title'],
			'description'      => $data['description'],
			'url'              => $page_url,
			'mainEntityOfPage' => array( '@id' => $page_url . '#webpage' ),
			'datePublished'    => $data['published'],
			'dateModified'     => $data['modified'],
			'author'           => array(
				'@type' => 'Person',
				'name'  => $data['author'] ? $data['author'] : $data['site_name'],
				'url'   => isset( $data['author_url'] ) ? $data['author_url'] : '',
			),
			'publisher'        => array( '@id' => $org_id ),
			'inLanguage'       => get_bloginfo( 'language' ),
		);

		if ( ! empty( $image['url'] ) ) {
			$article['image'] = array( $image['url'] );
		}

		$graph[] = $article;
	}

	return array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);
}

/**
 * Print one meta tag when its value is non-empty.
 *
 * @param string $attribute property|name.
 * @param string $key       Meta key.
 * @param string $value     Meta value.
 */
function ts_sg_seo_print_meta( $attribute, $key, $value ) {
	if ( '' === trim( (string) $value ) ) {
		return;
	}

	printf(
		'<meta %s="%s" content="%s">' . "\n",
		esc_attr( $attribute ),
		esc_attr( $key ),
		esc_attr( $value )
	);
}

/**
 * Print Step 9 head tags and featured-image preload.
 */
function ts_sg_seo_output_head() {
	if ( is_admin() || is_feed() ) {
		return;
	}

	echo "<!-- Silicon Geek SEO -->\n";
	echo '<meta name="theme-color" content="#0B1226">' . "\n";

	$verification = ts_sg_seo_sanitize_verification( get_theme_mod( 'sg_google_site_verification', '' ) );

	if ( '' !== $verification ) {
		ts_sg_seo_print_meta( 'name', 'google-site-verification', $verification );
	}

	// Performance: preload the likely LCP featured image on single learning pages.
	if ( is_singular( array( 'ece_note', 'ece_gate_pyq', 'ece_project' ) ) ) {
		$post = get_post();

		if ( $post && has_post_thumbnail( $post ) && ! post_password_required( $post ) ) {
			$attachment_id = get_post_thumbnail_id( $post );
			$url           = wp_get_attachment_image_url( $attachment_id, 'cs-hero' );

			if ( $url ) {
				$srcset = wp_get_attachment_image_srcset( $attachment_id, 'cs-hero' );
				$sizes  = '(max-width: 767px) 100vw, 1100px';

				printf(
					'<link rel="preload" as="image" href="%s"%s%s fetchpriority="high">' . "\n",
					esc_url( $url ),
					$srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '"' : '',
					$srcset ? ' imagesizes="' . esc_attr( $sizes ) . '"' : ''
				);
			}
		}
	}

	if ( ! ts_sg_seo_is_enabled() ) {
		return;
	}

	$data  = ts_sg_seo_get_current_data();
	$image = $data['image'];

	ts_sg_seo_print_meta( 'name', 'description', $data['description'] );

	if ( '' !== $data['canonical'] ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $data['canonical'] ) );
	}

	ts_sg_seo_print_meta( 'property', 'og:locale', get_locale() );
	ts_sg_seo_print_meta( 'property', 'og:type', $data['type'] );
	ts_sg_seo_print_meta( 'property', 'og:site_name', $data['site_name'] );
	ts_sg_seo_print_meta( 'property', 'og:title', $data['title'] );
	ts_sg_seo_print_meta( 'property', 'og:description', $data['description'] );
	ts_sg_seo_print_meta( 'property', 'og:url', $data['canonical'] );

	if ( ! empty( $image['url'] ) ) {
		ts_sg_seo_print_meta( 'property', 'og:image', $image['url'] );

		if ( ! empty( $image['width'] ) ) {
			ts_sg_seo_print_meta( 'property', 'og:image:width', (string) $image['width'] );
		}

		if ( ! empty( $image['height'] ) ) {
			ts_sg_seo_print_meta( 'property', 'og:image:height', (string) $image['height'] );
		}

		ts_sg_seo_print_meta( 'property', 'og:image:alt', $image['alt'] ? $image['alt'] : $data['title'] );
	}

	if ( 'article' === $data['type'] ) {
		ts_sg_seo_print_meta( 'property', 'article:published_time', $data['published'] );
		ts_sg_seo_print_meta( 'property', 'article:modified_time', $data['modified'] );
		ts_sg_seo_print_meta( 'property', 'article:author', $data['author_url'] );
	}

	ts_sg_seo_print_meta( 'name', 'twitter:card', ! empty( $image['url'] ) ? 'summary_large_image' : 'summary' );
	ts_sg_seo_print_meta( 'name', 'twitter:title', $data['title'] );
	ts_sg_seo_print_meta( 'name', 'twitter:description', $data['description'] );

	if ( ! empty( $image['url'] ) ) {
		ts_sg_seo_print_meta( 'name', 'twitter:image', $image['url'] );
		ts_sg_seo_print_meta( 'name', 'twitter:image:alt', $image['alt'] ? $image['alt'] : $data['title'] );
	}

	$twitter = ts_sg_seo_twitter_handle();

	if ( '' !== $twitter ) {
		ts_sg_seo_print_meta( 'name', 'twitter:site', '@' . $twitter );
		ts_sg_seo_print_meta( 'name', 'twitter:creator', '@' . $twitter );
	}

	if ( ! $data['noindex'] ) {
		$schema = wp_json_encode(
			ts_sg_seo_build_schema( $data ),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);

		if ( $schema ) {
			echo '<script type="application/ld+json">' . $schema . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON generated from sanitized WP data.
		}
	}
}

/**
 * Enqueue GA4 only when a valid ID has been entered.
 */
function ts_sg_seo_enqueue_analytics() {
	if ( is_admin() || is_customize_preview() || is_user_logged_in() ) {
		return;
	}

	$id = ts_sg_seo_sanitize_ga4_id( get_theme_mod( 'sg_ga4_measurement_id', '' ) );

	if ( '' === $id ) {
		return;
	}

	wp_enqueue_script(
		'sg-ga4',
		'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $id ),
		array(),
		null,
		false
	);
	wp_script_add_data( 'sg-ga4', 'strategy', 'async' );

	wp_add_inline_script(
		'sg-ga4',
		"window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','" . esc_js( $id ) . "');",
		'after'
	);
}

/* -------------------------------------------------------------------------
 * Safe performance helpers
 * ---------------------------------------------------------------------- */

/**
 * Add preconnect/dns-prefetch hints for CDNs already used by the theme.
 *
 * @param array  $hints          Existing hints.
 * @param string $relation_type  Relation type.
 * @return array
 */
function ts_sg_perf_resource_hints( $hints, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$hints[] = 'https://fonts.googleapis.com';
		$hints[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}

	if ( 'dns-prefetch' === $relation_type ) {
		$hints[] = 'https://cdnjs.cloudflare.com';
		$hints[] = 'https://cdn.jsdelivr.net';
	}

	return $hints;
}

/**
 * Defer known non-critical theme scripts.
 *
 * WordPress block/navigation scripts are intentionally not touched.
 *
 * @param string $tag    Script tag.
 * @param string $handle Script handle.
 * @param string $src    Script source.
 * @return string
 */
function ts_sg_perf_defer_scripts( $tag, $handle, $src ) {
	if ( is_admin() ) {
		return $tag;
	}

	$defer_handles = array( 'swiper', 'swiper-init', 'show-more-script', 'video-js', 'mobile-menu-fix' );

	if ( ! in_array( $handle, $defer_handles, true ) ) {
		return $tag;
	}

	if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) ) {
		return $tag;
	}

	return str_replace( ' src=', ' defer src=', $tag );
}

/**
 * Ensure content images decode asynchronously.
 *
 * @param array        $attributes Image attributes.
 * @param WP_Post      $attachment Attachment post.
 * @param string|array $size       Image size.
 * @return array
 */
function ts_sg_perf_image_attributes( $attributes, $attachment, $size ) {
	if ( empty( $attributes['decoding'] ) ) {
		$attributes['decoding'] = 'async';
	}

	return $attributes;
}

/* -------------------------------------------------------------------------
 * Branded 404 Shortcode
 * ---------------------------------------------------------------------- */

/**
 * [sg_launch_404] Shortcode used by templates/404.html.
 *
 * @return string
 */
function ts_sg_launch_404_shortcode() {
	$roots = function_exists( 'ts_cs_course_get_children' )
		? ts_cs_course_get_children( 0, false )
		: array();

	$project_help_page  = get_page_by_path( 'project-help' );
	$project_help_ready = $project_help_page && 'publish' === $project_help_page->post_status;
	$secondary_url      = $project_help_ready ? get_permalink( $project_help_page ) : get_post_type_archive_link( 'ece_project' );
	$secondary_label    = $project_help_ready ? 'Get Project Help' : 'Explore Projects';

	$recent_notes = get_posts(
		array(
			'post_type'      => 'ece_note',
			'post_status'    => 'publish',
			'has_password'   => false,
			'posts_per_page' => 3,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	$recent_projects = get_posts(
		array(
			'post_type'      => 'ece_project',
			'post_status'    => 'publish',
			'has_password'   => false,
			'posts_per_page' => 3,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	ob_start();
	?>
	<section class="sg-launch-404" data-sg-step-9-404>
		<div class="sg-launch-404__wrap">
			<div class="sg-launch-404__hero">
				<p class="sg-launch-404__code" aria-hidden="true">404</p>
				<p class="sg-launch-404__eyebrow">Page not found</p>
				<h1>Ye page nahi mila — learning yahin se continue karo.</h1>
				<p class="sg-launch-404__lead">Link purana ho sakta hai ya page abhi publish nahi hua. Apna topic search karo, ya kisi learning path se step-by-step shuru karo.</p>

				<?php
				if ( function_exists( 'ts_sg_nav_search_form_html' ) ) {
					echo ts_sg_nav_search_form_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						array(
							'layout'      => 'hero',
							'placeholder' => 'Search notes, topics, projects…',
							'show_filter' => false,
							'value'       => '',
							'filter'      => 'all',
						)
					);
				} else {
					get_search_form();
				}
				?>

				<div class="sg-launch-404__actions">
					<a class="sg-launch-404__btn sg-launch-404__btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">Home Par Jao</a>
					<?php if ( $secondary_url ) : ?>
						<a class="sg-launch-404__btn" href="<?php echo esc_url( $secondary_url ); ?>"><?php echo esc_html( $secondary_label ); ?></a>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! empty( $roots ) ) : ?>
				<div class="sg-launch-404__section">
					<p>Start learning</p>
					<h2>Learning Paths</h2>
					<div class="sg-launch-404__grid">
						<?php foreach ( $roots as $root ) : ?>
							<?php $url = get_term_link( $root ); ?>
							<?php if ( ! is_wp_error( $url ) ) : ?>
								<a class="sg-launch-404__card" href="<?php echo esc_url( $url ); ?>">
									<strong><?php echo esc_html( $root->name ); ?></strong>
									<span><?php echo esc_html( $root->description ? ts_sg_seo_clean_text( $root->description, 105 ) : 'Step-by-step folders aur notes explore karo.' ); ?></span>
									<em>Open Path →</em>
								</a>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $recent_notes ) || ! empty( $recent_projects ) ) : ?>
				<div class="sg-launch-404__columns">
					<?php if ( ! empty( $recent_notes ) ) : ?>
						<div class="sg-launch-404__panel">
							<h2>Latest Notes</h2>
							<ul>
								<?php foreach ( $recent_notes as $note ) : ?>
									<li><a href="<?php echo esc_url( get_permalink( $note ) ); ?>"><?php echo esc_html( get_the_title( $note ) ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $recent_projects ) ) : ?>
						<div class="sg-launch-404__panel">
							<h2>Latest Projects</h2>
							<ul>
								<?php foreach ( $recent_projects as $project ) : ?>
									<li><a href="<?php echo esc_url( get_permalink( $project ) ); ?>"><?php echo esc_html( get_the_title( $project ) ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php

	return trim( ob_get_clean() );
}
