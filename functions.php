<?php
/**
 * UnderStrap functions and definitions — UPDATED for the ECE (CircuitShala) design.
 *
 * WHAT CHANGED vs your pasted functions.php (search "CS-CHANGE"):
 *  1. Text-domain fixed everywhere => 'ts-block-theme' (was 'your-txt' / 'carolina').
 *  2. Paragraph "badge" block style moved INSIDE init (top-level call was too early).
 *  3. Google Fonts (Open Sans + Montserrat) now enqueued properly here —
 *     so the 3 "@import url(...)" lines were REMOVED from fonts.scss (faster page load).
 *  4. Editor stylesheet path fixed (assets/css/editor.css does not exist in your tree)
 *     => uses css/custom-editor-style.min.css if present, else css/theme.min.css.
 *  5. NEW Customizer settings: WhatsApp number, Contact Email, City (Site Contact Info).
 *     Phone default (US number) removed => empty. Set YOUR details in Customizer.
 *  6. NEW shortcodes: [site_email] [circuitshala_site_name] [circuitshala_contact_links].
 *  7. NEW: ECE pattern category + button style "whatsapp" + image sizes for cards.
 *  8. NEW: loads inc/custom-post-type/ece-cpt.php (ECE Notes / Projects / GATE PYQ).
 *  9. Gravity Forms: JS single-slash comments fixed + enqueue args corrected. Logic unchanged.
 * 10. Removed no-op block_editor_settings_all filter. The render_block "!important" filter
 *     is KEPT (your colors.scss depends on it) — marked with a TODO to remove later.
 * Everything else (Swiper, video shortcode, Font Awesome, menus, custom blocks) = UNCHANGED.
 *
 * @package Understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// UnderStrap's includes directory.
$understrap_inc_dir = 'inc';

// Array of files to include.
$understrap_includes = array(
	'/theme-settings.php',                  // Initialize theme default settings.
	'/setup.php',                           // Theme setup and custom theme supports.
	'/widgets.php',                         // Register widget area.
	'/enqueue.php',                         // Enqueue scripts and styles.
	'/template-tags.php',                   // Custom template tags for this theme.
	'/pagination.php',                      // Custom pagination for this theme.
	'/hooks.php',                           // Custom hooks.
	'/extras.php',                          // Custom functions that act independently of the theme templates.
	'/customizer.php',                      // Customizer additions.
	'/custom-comments.php',                 // Custom Comments file.
	'/class-wp-bootstrap-navwalker.php',    // Load custom WordPress nav walker.
	'/editor.php',                          // Load Editor functions.
	'/block-editor.php',                    // Load Block Editor functions.
	'/deprecated.php',                      // Load deprecated functions.
);

// Load WooCommerce functions if WooCommerce is activated.
if ( class_exists( 'WooCommerce' ) ) {
	$understrap_includes[] = '/woocommerce.php';
}

// Load Jetpack compatibility file if Jetpack is activated.
if ( class_exists( 'Jetpack' ) ) {
	$understrap_includes[] = '/jetpack.php';
}

// Include files.
foreach ( $understrap_includes as $file ) {
	require_once get_theme_file_path( $understrap_inc_dir . $file );
}

// -----------------------------------------------------------------------------
// CS-CHANGE: Theme supports + editor style (hooked properly, editor path fixed)
// -----------------------------------------------------------------------------
add_action( 'after_setup_theme', function () {
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	// NOTE: 'appearance-tools' is controlled from theme.json ("appearanceTools": true).

	// Editor stylesheet: prefer the file your build actually produces.
	if ( file_exists( get_theme_file_path( '/css/custom-editor-style.min.css' ) ) ) {
		add_editor_style( 'css/custom-editor-style.min.css' );
	} elseif ( file_exists( get_theme_file_path( '/css/theme.min.css' ) ) ) {
		add_editor_style( 'css/theme.min.css' );
	}

	// Card/hero image sizes used by ECE Notes & Projects.
	add_image_size( 'cs-card', 800, 520, true );
	add_image_size( 'cs-hero', 1280, 720, true );
} );

// -----------------------------------------------------------------------------
// CS-CHANGE: Google Fonts via enqueue (replaces SCSS @import). Montserrat = headings,
// Open Sans = body. Dancing Script removed (ECE site does not use it).
// -----------------------------------------------------------------------------
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'ts-google-fonts',
		'https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Open+Sans:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);
}, 5 );
add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_style(
		'ts-google-fonts',
		'https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Open+Sans:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);
} );

// -----------------------------------------------------------------------------
// Block styles (CS-CHANGE: text-domain fixed, badge inside init, + whatsapp)
// -----------------------------------------------------------------------------
add_action( 'init', function () {
	register_block_style( 'core/group', array(
		'name'  => 'container',
		'label' => __( 'Container', 'ts-block-theme' ),
	) );
	register_block_style( 'core/group', array(
		'name'  => 'container-fluid',
		'label' => __( 'Container Fluid', 'ts-block-theme' ),
	) );
	register_block_style( 'core/group', array(
		'name'  => 'row',
		'label' => __( 'Row', 'ts-block-theme' ),
	) );
	register_block_style( 'core/columns', array(
		'name'  => 'gutters',
		'label' => __( 'With Gutters', 'ts-block-theme' ),
	) );
	register_block_style( 'core/paragraph', array(
		'name'  => 'badge',
		'label' => __( 'Badge', 'ts-block-theme' ),
	) );
} );

add_action( 'init', function () {
	// Define your main variants and modifiers.
	$variants  = array( 'primary', 'secondary', 'tertiary' );
	$modifiers = array( '', 'alt' ); // base + "-alt".

	foreach ( $variants as $variant ) {
		foreach ( $modifiers as $mod ) {
			$style_name  = $variant . ( $mod ? '-' . $mod : '' );
			$style_label = ucwords( $variant ) . ( $mod ? ' Alt' : '' );
			register_block_style( 'core/button', array(
				'name'  => $style_name,
				'label' => $style_label,
			) );
		}
	}

	// Size & shape styles + CS-CHANGE: WhatsApp / Ghost buttons for the ECE design.
	$extras = array(
		array( 'sm', 'Small' ),
		array( 'lg', 'Large' ),
		array( 'pill', 'Pill' ),
		array( 'whatsapp', 'WhatsApp (Green)' ),
		array( 'ghost', 'Ghost (Outline)' ),
	);
	foreach ( $extras as list( $name, $label ) ) {
		register_block_style( 'core/button', array(
			'name'  => $name,
			'label' => $label,
		) );
	}
} );

// CS-CHANGE: Pattern category so all ECE patterns sit in one place in the inserter.
add_action( 'init', function () {
	if ( function_exists( 'register_block_pattern_category' ) ) {
		register_block_pattern_category( 'circuitshala-ece', array(
			'label'       => __( 'ECE / CircuitShala', 'ts-block-theme' ),
			'description' => __( 'ECE notes, projects, GATE, embedded & VLSI sections.', 'ts-block-theme' ),
		) );
	}
}, 9 );

// -----------------------------------------------------------------------------
// Inline color !important filter — KEPT as-is (your colors.scss generates
// .has-*-color rules with !important, so block inline colours need this today).
// TODO (later cleanup): remove !important from colors.scss, then delete this filter.
// -----------------------------------------------------------------------------
add_filter( 'render_block', function ( $block_content, $block ) {
	if ( is_admin() ) {
		return $block_content;
	}
	if ( strpos( $block_content, 'style="' ) !== false ) {
		$block_content = preg_replace_callback(
			'/style="([^"]+)"/',
			function ( $matches ) {
				$style = $matches[1];
				$style = preg_replace_callback(
					'/(background-color\s*:\s*[^;!]+)(;|$)/i',
					fn( $m ) => trim( $m[1] ) . ' !important' . $m[2],
					$style
				);
				$style = preg_replace_callback(
					'/(color\s*:\s*[^;!]+)(;|$)/i',
					fn( $m ) => trim( $m[1] ) . ' !important' . $m[2],
					$style
				);
				return 'style="' . $style . '"';
			},
			$block_content
		);
	}
	return $block_content;
}, 10, 2 );

// -----------------------------------------------------------------------------
// Register Custom Blocks & Patterns (UNCHANGED — plus one CS-CHANGE line: ECE CPT)
// -----------------------------------------------------------------------------
require_once get_template_directory() . '/inc/responsive-bg/responsive-bg-init.php';
require_once get_template_directory() . '/inc/override-button/override-button-init.php';
require_once get_template_directory() . '/inc/image-position/image-init.php';
require_once get_template_directory() . '/inc/affiliation/affiliation-init.php';
require_once get_template_directory() . '/inc/card/card-init.php';
require_once get_template_directory() . '/inc/swiper-card/swiper-card-init.php';
require_once get_template_directory() . '/inc/blog/recent-blog-init.php';
require_once get_template_directory() . '/inc/blog/filter-blog-init.php';
require_once get_template_directory() . '/inc/accordion/accordion-init.php';
require_once get_template_directory() . '/inc/tab/tab-init.php';
require_once get_template_directory() . '/inc/read-more-toggle/read-more-toggle-init.php';
require_once get_template_directory() . '/inc/post-content-image/post-content-image.php';
require_once get_template_directory() . '/inc/category-filter/category-filter-init.php';
require_once get_template_directory() . '/inc/search-filter/search-filter-init.php';
require_once get_template_directory() . '/inc/pattern-categories/register-pattern-categories.php';
require_once get_template_directory() . '/inc/custom-post-type/ece-cpt.php';
require_once get_template_directory() . '/inc/custom-post-type/register-cpt.php';
// CS-CHANGE: ECE post types (Notes / Projects / GATE PYQ) — NEW FILE, nothing overwritten.
// Silicon Geek Step 1: hierarchical Course Structure taxonomy + ordering helpers.
require_once get_template_directory() . '/inc/custom-post-type/ece-course-structure.php';
// Silicon Geek Step 2: automatic /learn/ course index renderer + [sg_course_index] Shortcode.
require_once get_template_directory() . '/inc/custom-post-type/ece-course-render.php';
require_once get_template_directory() . '/inc/swiper-slider/swiper-slider-init.php';
require_once get_template_directory() . '/inc/team-block/team-block.php';
// Include shortcodes.
require_once get_template_directory() . '/inc/shortcodes/bootstrap-accordion-shortcode.php';

// Nav dropdown / mobile menu (CS-CHANGE: file_exists guard for filemtime).
add_action( 'wp_enqueue_scripts', function () {
	$path = get_template_directory() . '/assets/js/mobile-menu.js';
	if ( file_exists( $path ) ) {
		wp_enqueue_script(
			'mobile-menu-fix',
			get_template_directory_uri() . '/assets/js/mobile-menu.js',
			array(),
			filemtime( $path ),
			true
		);
	}
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'wp-block-navigation' );
} );

// Menu Locations (CS-CHANGE: same KEYS so saved menus keep working; labels updated).
function my_custom_menu_locations() {
	register_nav_menus( array(
		'primary_mobile_menu' => __( 'Primary Mobile Menu', 'ts-block-theme' ),
		'company_menu'        => __( 'Company Menu (Footer)', 'ts-block-theme' ),
		'services_menu'       => __( 'Learn Menu (Footer)', 'ts-block-theme' ),
		'social_menu'         => __( 'Social Menu', 'ts-block-theme' ),
	) );
}
add_action( 'after_setup_theme', 'my_custom_menu_locations' );

// -----------------------------------------------------------------------------
// Site Contact Info in Customizer (CS-CHANGE: WhatsApp + Email + City added,
// US default phone removed. Set these at: Appearance → Customize → Site Contact Info)
// -----------------------------------------------------------------------------
function theme_customizer_phone_number( $wp_customize ) {
	$wp_customize->add_section( 'site_contact_info', array(
		'title'       => __( 'Site Contact Info', 'ts-block-theme' ),
		'priority'    => 30,
		'description' => __( 'Used in header/footer via shortcodes. Leave blank to hide.', 'ts-block-theme' ),
	) );

	$fields = array(
		'site_phone_number'    => array( 'label' => __( 'Phone Number (display)', 'ts-block-theme' ), 'sanitize' => 'sanitize_text_field' ),
		'site_whatsapp_number' => array( 'label' => __( 'WhatsApp Number (with country code, e.g. 91XXXXXXXXXX)', 'ts-block-theme' ), 'sanitize' => 'sanitize_text_field' ),
		'site_email'           => array( 'label' => __( 'Contact Email', 'ts-block-theme' ), 'sanitize' => 'sanitize_email' ),
		'site_city'            => array( 'label' => __( 'City / Area (e.g. Lucknow, Uttar Pradesh)', 'ts-block-theme' ), 'sanitize' => 'sanitize_text_field' ),
	);

	foreach ( $fields as $id => $cfg ) {
		$wp_customize->add_setting( $id, array(
			'default'           => '',
			'sanitize_callback' => $cfg['sanitize'],
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( $id, array(
			'label'    => $cfg['label'],
			'section'  => 'site_contact_info',
			'settings' => $id,
			'type'     => 'text',
		) );
	}
}
add_action( 'customize_register', 'theme_customizer_phone_number' );

// [site_info_phone_number] — UNCHANGED behaviour; blank default now hides output.
function site_info_phone_number_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'link' => 'false' ), $atts, 'site_info_phone_number' );

	$phone_number = get_theme_mod( 'site_phone_number', '' );
	if ( empty( $phone_number ) ) {
		return '';
	}
	$display_phone = esc_html( $phone_number );
	$tel_phone     = preg_replace( '/[^0-9\+]/', '', $phone_number );

	if ( $atts['link'] !== 'true' ) {
		return $display_phone;
	}
	return '<a href="tel:' . esc_attr( $tel_phone ) . '" class="site-phone-number">' . $display_phone . '</a>';
}
add_shortcode( 'site_info_phone_number', 'site_info_phone_number_shortcode' );

// CS-CHANGE: [site_email link="true"]
function ts_cs_site_email_shortcode( $atts ) {
	$atts  = shortcode_atts( array( 'link' => 'true' ), $atts, 'site_email' );
	$email = get_theme_mod( 'site_email', '' );
	if ( empty( $email ) ) {
		return '';
	}
	return $atts['link'] === 'true'
		? '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>'
		: esc_html( $email );
}
add_shortcode( 'site_email', 'ts_cs_site_email_shortcode' );

// CS-CHANGE: [circuitshala_site_name] — brand name from Settings → General (no hardcoding).
add_shortcode( 'circuitshala_site_name', function () {
	return esc_html( get_bloginfo( 'name' ) );
} );

// CS-CHANGE: [circuitshala_contact_links] — prints only filled contact methods (used in footer).
function ts_cs_contact_links_shortcode() {
	$phone    = get_theme_mod( 'site_phone_number', '' );
	$whatsapp = preg_replace( '/\D/', '', (string) get_theme_mod( 'site_whatsapp_number', '' ) );
	if ( strlen( $whatsapp ) === 10 ) {
		$whatsapp = '91' . $whatsapp; // Default India country code for 10-digit numbers.
	}
	$email = get_theme_mod( 'site_email', '' );
	$city  = get_theme_mod( 'site_city', '' );

	$items = array();
	if ( $whatsapp ) {
		$items[] = '<li>📲 <a href="https://wa.me/' . esc_attr( $whatsapp ) . '" target="_blank" rel="noopener">WhatsApp: ' . esc_html( $whatsapp ) . '</a></li>';
	}
	if ( $phone ) {
		$items[] = '<li>📞 <a href="tel:' . esc_attr( preg_replace( '/[^0-9\+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a></li>';
	}
	if ( $email ) {
		$items[] = '<li>✉️ <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></li>';
	}
	if ( $city ) {
		$items[] = '<li>📍 ' . esc_html( $city ) . '</li>';
	}

	return $items ? '<ul class="cs-contact-links">' . implode( '', $items ) . '</ul>' : '';
}
add_shortcode( 'circuitshala_contact_links', 'ts_cs_contact_links_shortcode' );

// Swiper (UNCHANGED).
function understrap_enqueue_swiper_assets() {
	wp_enqueue_style( 'swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), null );
	wp_enqueue_script( 'swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), null, true );
	wp_enqueue_script( 'swiper-init', get_template_directory_uri() . '/assets/js/swiper-init.js', array( 'swiper' ), null, true );
}
add_action( 'wp_enqueue_scripts', 'understrap_enqueue_swiper_assets' );

function enqueue_show_more_script() {
	wp_enqueue_script( 'show-more-script', get_template_directory_uri() . '/assets/js/show-more.js', array(), '1.0', true );
}
add_action( 'wp_enqueue_scripts', 'enqueue_show_more_script' );

function ts_current_year_shortcode() {
	return date( 'Y' );
}
add_shortcode( 'current_year', 'ts_current_year_shortcode' );

// -----------------------------------------------------------------------------
// Gravity Forms submit button (CS-CHANGE: JS comments fixed, enqueue args fixed.
// Logic identical — this "works", but long-term move this JS into
// src/js/custom-select-option.js fully and delete the inline <script>.)
// -----------------------------------------------------------------------------
add_filter( 'gform_submit_button', 'gravity_submit_button', 10, 2 );

function gravity_submit_button( $button, $form ) { ?>
	<script>
		var x, i, j, selElmnt, a, b, c;
		/* Look for any elements with the class "custom-select": */
		function bc_update_select_design() {
			x = document.querySelectorAll(".dropdown-select .ginput_container_select");
			for (i = 0; i < x.length; i++) {
				selElmnt = x[i].getElementsByTagName("select")[0];
				if (!selElmnt) { continue; }
				/* For each element, create a new DIV that will act as the selected item: */
				a = document.createElement("DIV");
				a.setAttribute("class", "select-selected rounded-0");
				a.setAttribute("tabindex", 0);
				a.innerHTML = selElmnt.options[selElmnt.selectedIndex].innerHTML;
				x[i].appendChild(a);
				/* For each element, create a new DIV that will contain the option list: */
				b = document.createElement("DIV");
				b.setAttribute("class", "select-items select-hide");
				for (j = 1; j < selElmnt.length; j++) {
					c = document.createElement("DIV");
					c.innerHTML = selElmnt.options[j].innerHTML;
					b.appendChild(c);
				}
				x[i].appendChild(b);
			}
		}
		function closeAllSelect(elmnt) {
			var x, y, i, arrNo = [];
			x = document.getElementsByClassName("select-items");
			y = document.getElementsByClassName("select-selected");
			for (i = 0; i < y.length; i++) {
				if (elmnt == y[i]) { arrNo.push(i); } else { y[i].classList.remove("select-arrow-active"); }
			}
			for (i = 0; i < x.length; i++) {
				if (arrNo.indexOf(i)) { x[i].classList.add("select-hide"); }
			}
		}
		window.onload = (event) => {
			bc_update_select_design();
			document.addEventListener("click", closeAllSelect);
		};
	</script>
	<?php
	wp_enqueue_script( 'select-script', get_template_directory_uri() . '/src/js/custom-select-option.js', array( 'jquery' ), '1.0.0', true );
	return "<div class='submit-button-wrapper wp-block-button is-style-primary'><button class='wp-block-button__link wp-element-button' id='gform_submit_button_{$form['id']}'>" . $form['button']['text'] . '</button></div>';
}

// Video shortcode (UNCHANGED).
function video_thumbnail_shortcode( $atts ) {
	$default_thumb = get_stylesheet_directory_uri() . '/assets/images/video-thumb.png';
	$atts = shortcode_atts( array(
		'thumbnail' => '',
		'video'     => '',
		'width'     => '100%',
		'height'    => '315',
	), $atts );

	$thumbnail = $atts['thumbnail'] ? $atts['thumbnail'] : $default_thumb;
	if ( ! $atts['video'] ) {
		return '<p>Video URL missing</p>';
	}
	ob_start(); ?>
	<div class="video-wrapper" style="max-width:<?php echo esc_attr( $atts['width'] ); ?>;">
		<div class="vide-thumb" data-video-url="<?php echo esc_url( $atts['video'] ); ?>?autoplay=1">
			<img src="<?php echo esc_url( $thumbnail ); ?>" alt="" style="width:<?php echo esc_attr( $atts['width'] ); ?>; height:<?php echo esc_attr( $atts['height'] ); ?>px;">
		</div>
		<div class="video-box" style="display:none;">
			<iframe width="<?php echo esc_attr( $atts['width'] ); ?>" height="<?php echo esc_attr( $atts['height'] ); ?>" src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'video_section', 'video_thumbnail_shortcode' );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'video-js', get_stylesheet_directory_uri() . '/assets/js/video.js', array(), null, true );
} );

// Testimonials archive paging (UNCHANGED).
add_action( 'pre_get_posts', function ( $query ) {
	if ( ! is_admin() && $query->is_main_query() && is_post_type_archive( 'testimonials' ) ) {
		$query->set( 'posts_per_page', 4 );
	}
} );

// Font Awesome (UNCHANGED — same handle, front + admin).
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css', array(), '6.5.0' );
} );
add_action( 'admin_enqueue_scripts', function () {
	wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css', array(), '6.5.0' );
} );
