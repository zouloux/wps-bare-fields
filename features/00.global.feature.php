<?php

use BareFields\helpers\AdminHelper;
use BareFields\helpers\WPSHelper;
use PHPMailer\PHPMailer\PHPMailer;


/**
 * Global features are front, api and backend related
 */

// ----------------------------------------------------------------------------- CLEAN WP JUNK

function bare_fields_feature_global_clean_junk () {
	// Remove all WP Junk, front and admin
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'rest_output_link_wp_head');
	remove_action('template_redirect', 'rest_output_link_header', 11);
	remove_action('wp_head', 'rsd_link');
	add_filter('the_generator', fn () => '' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action('admin_print_scripts', 'print_emoji_detection_script');
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	add_action( 'wp_enqueue_scripts', function () {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'wc-block-style' );
	}, 100 );
	add_action('wp_print_styles', function () {
		global $wp_styles;
		$wp_styles->queue = [];
	}, 100);
	add_filter( 'wpseo_debug_markers', '__return_false' );
	remove_action( 'wp_head', 'wp_resource_hints', 2 );
	remove_action('wp_head', 'wp_shortlink_wp_head');
	add_filter( 'rank_math/frontend/remove_credit_notice', '__return_true' );
	add_theme_support( 'admin-bar', array( 'callback' => '__return_false' ) );
	remove_action('wp_head', 'wp_generator');
	remove_action('wp_head', 'wp_site_icon', 99);
	remove_action('template_redirect', 'wp_shortlink_header', 11);
	remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
	remove_filter('the_content_feed', 'wp_staticize_emoji');
	remove_filter('comment_text_rss', 'wp_staticize_emoji');
	remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
	// Remove classic theme styles.
	// https://github.com/WordPress/WordPress/commit/143fd4c1f71fe7d5f6bd7b64c491d9644d861355
	add_action('wp_enqueue_scripts', fn () => wp_dequeue_style('classic-theme-styles'));
}

// ----------------------------------------------------------------------------- THEME

function bare_fields_feature_global_disable_theme () {
	add_action('template_redirect', function() {
		if ( !is_admin() )
			exit;
	});
}

// ----------------------------------------------------------------------------- OEMBED

// Disable OEmbed support = emoji support in back and front-end
// https://kinsta.com/fr/base-de-connaissances/desactiver-embeds-wordpress/#disable-embeds-code
function bare_fields_feature_global_disable_oembed () {
	add_filter('init', function () {
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );
		add_filter( 'embed_oembed_discover', '__return_false' );
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_filter( 'tiny_mce_plugins', function ($plugins) {
			return array_diff($plugins, ['wpembed']);
		});
		add_filter( 'rewrite_rules_array', function ($rules) {
			foreach($rules as $rule => $rewrite)
				if( str_contains($rewrite, 'embed=true') )
					unset($rules[$rule]);
			return $rules;
		});
		remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
	}, '999');
}

// ----------------------------------------------------------------------------- PATCHES

// Patch shutdown with wrong ob flush on some hostings
function bare_fields_feature_global_patch_shutdown () {
	remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
	add_action( 'shutdown', function() {
		while ( @ob_end_flush() );
	});
}

// ----------------------------------------------------------------------------- HTACCESS

function bare_fields_feature_global_disable_htaccess () {
	if ( !defined('WP_ALLOW_REWRITE_RULES') )
		define('WP_ALLOW_REWRITE_RULES', false);
}

// ----------------------------------------------------------------------------- FEEDS & XML

function bare_fields_cancel_route () {
  wp_redirect(home_url());
  exit;
}

function bare_fields_feature_global_disable_feed () {
	// Disable feeds.
	add_action('do_feed', 'bare_fields_cancel_route', 1);
	add_action('do_feed_rdf', 'bare_fields_cancel_route', 1);
	add_action('do_feed_rss', 'bare_fields_cancel_route', 1);
	add_action('do_feed_rss2', 'bare_fields_cancel_route', 1);
	add_action('do_feed_atom', 'bare_fields_cancel_route', 1);
	// Disable feed links
	remove_action('wp_head', 'feed_links', 2);
	remove_action('wp_head', 'feed_links_extra', 3);
}

function bare_fields_feature_global_disable_xmlrpc () {
	// Disable XML RPC for security.
	add_filter('xmlrpc_enabled', '__return_false');
	add_filter('xmlrpc_methods', '__return_false');
}

// ----------------------------------------------------------------------------- GUTENBERG

function bare_fields_feature_global_disable_gutenberg () {
	add_filter('use_block_editor_for_post', '__return_false', 10, 0 );
	// Remove Gutenberg's front-end block styles.
	add_action('wp_enqueue_scripts', function () {
		wp_deregister_style('wp-block-library');
		wp_deregister_style('wp-block-library-theme');
	});
	// Remove core block styles.
	// https://github.com/WordPress/gutenberg/issues/56065
	add_action('wp_footer', fn () => wp_dequeue_style('core-block-supports'));
	// Remove Gutenberg's global styles.
	// https://github.com/WordPress/gutenberg/pull/34334#issuecomment-911531705
	add_action('wp_enqueue_scripts', fn () => wp_dequeue_style('global-styles'));
	// Remove the SVG Filters that are mostly if not only used in Full Site Editing/Gutenberg
	// Detailed discussion at: https://github.com/WordPress/gutenberg/issues/36834
	add_action('init', function () {
		remove_action('wp_body_open', 'gutenberg_global_styles_render_svg_filters');
		remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
	});
}

// ----------------------------------------------------------------------------- JSON

// Disable completely WP json API
function bare_fields_feature_global_disable_wp_json () {
	add_filter('json_enabled', '__return_false');
	add_filter('json_jsonp_enabled', '__return_false');
	// Patch site health and remove rest test
	add_filter( 'site_status_tests', function( $tests ) {
		unset( $tests['direct']['rest_availability'] );
		return $tests;
	});
}

// Move wp-json origin.
// Should be something like "backend/wp-json" to work
function bare_fields_feature_global_move_wp_json_origin ( $origin ) {
	add_filter( 'rest_url_prefix', fn () => $origin );
}

// Disable default users API endpoints for security.
function bare_fields_feature_global_disable_author_wp_json () {
	// https://www.wp-tweaks.com/hackers-can-find-your-wordpress-username/
	add_filter('rest_endpoints', function ( $endpoints ) {
		if (!is_user_logged_in()) {
			if (isset($endpoints['/wp/v2/users']))
				unset($endpoints['/wp/v2/users']);
			if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)']))
				unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
		}
		return $endpoints;
	});
}

// ----------------------------------------------------------------------------- COMMENTS

// NOTE : Remove all comments and empty trash before disable comments
function bare_fields_feature_global_disable_comments () {
	// Disable support for comments and trackbacks in post types
	add_action('admin_init', function () {
		$postTypes = get_post_types();
		foreach ($postTypes as $postType) {
			if ( post_type_supports($postType, 'comments') ) {
				remove_post_type_support($postType, 'comments');
				remove_post_type_support($postType, 'trackbacks');
			}
		}
	});
	// Close comments on the front-end
	add_filter('comments_open', '__return_false', 20, 2);
	add_filter('pings_open', '__return_false', 20, 2);
	// Disable comments feeds.
	add_action('do_feed_rss2_comments', 'bare_fields_cancel_route', 1);
	add_action('do_feed_atom_comments', 'bare_fields_cancel_route', 1);
	// Hide comments from DB
	add_filter('comments_array', fn () => [], 10, 2);
	// Remove comments page in menu
	add_action('admin_menu', fn () => remove_menu_page('edit-comments.php') );
	AdminHelper::disableAdminPage("edit-comments.php");
	// Remove comments metabox from dashboard
	add_action('admin_init', function () {
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
	});
	// Remove comments links from admin bar
	add_action('init', function () {
		if ( is_admin_bar_showing() )
			remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
	});
	// Remove shortcut from admin bar
	add_action('admin_bar_menu', fn ($adminBar) => $adminBar->remove_node('comments'), 999);
}

// ----------------------------------------------------------------------------- NESTED PAGES

// FIXME : not really disabled
function bare_fields_feature_global_disable_nested_pages () {
	//	add_action( 'init', function () {
	//		remove_post_type_support('page','page-attributes');
	//	});
	add_action( 'admin_init', function () {
		$style = "#pageparentdiv .inside .parent-id-label-wrapper, #parent_id { display: none; }";
		AdminHelper::injectCustomAdminResourceForScreen(null, $style, "");
	});
}

// ----------------------------------------------------------------------------- NEWS

/**
 * Disable news features.
 * Will disable :
 * - news post listing
 * - news post add / editing
 * - news post categories listing / add / editing
 * - news post tags listing / add / editing
 * Will also remove "posts" from menu
 * @return void
 */
function bare_fields_feature_global_disable_news () {
	add_action('admin_init', function () {
		global $pagenow;
		$postType = $_GET['post_type'] ?? '';
		$blockAccess = false;
		// Block access to news post listing
		if ( $pagenow == 'edit.php' && (empty($postType) || $postType == 'post') )
			$blockAccess = true;
		// Block access to news post add / edit
		if ( $pagenow == 'post-new.php' && (empty($postType) || $postType == 'post') )
			$blockAccess = true;
		// Block access to news post category and tags listing
		if ( $pagenow == 'edit-tags.php' )
			$blockAccess = true;
		// Block access to news post category and tags add / edit
		if ( $pagenow == 'term.php' )
			$blockAccess = true;
		if ( $blockAccess ) {
			wp_redirect(admin_url());
			exit;
		}
	});
	// Remove from menu with CSS, because we cannot remove with regular PHP function
	add_action( 'admin_init', function () {
		$style = "#menu-posts { display: none; }";
		AdminHelper::injectCustomAdminResourceForScreen(null, $style, "");
	});
	// Remove admin bar shortcut
	add_action('wp_before_admin_bar_render', function () {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu('new-post');
	});
	// Remove dashboard blocks
	add_action('wp_dashboard_setup', function () {
		global $wp_meta_boxes;
		unset($wp_meta_boxes[ 'dashboard' ][ 'side' ][ 'core' ][ 'dashboard_quick_press' ]);
		unset($wp_meta_boxes[ 'dashboard' ][ 'normal' ][ 'core' ][ 'dashboard_recent_comments' ]);
	});
}

/**
 * Note : use it along bare_fields_feature_global_set_permalink_structure('/news/%postname%/')
 * And flush rewrite
 * @param string $base
 * @return void
 */
function bare_fields_feature_global_set_news_base ( string $base = "/news" ) {
	add_filter( 'post_link', function ($url, $post, $leaveName) use ($base) {
		if ( $post->post_type === 'post' )
			return home_url( $base."/".( $leaveName ? '%pagename%' : $post->post_name )."/" );
		else
			return $url;
	}, 10, 3 );
}

//function bare_fields_feature_global_disable_news_feature_image () {
//	add_action( 'init', function () {
//		remove_post_type_support( 'post', 'thumbnail' );
//	});
//}

// ----------------------------------------------------------------------------- PERMALINKS

function bare_fields_feature_global_set_permalink_structure ( $structure = '/%postname%/', $flush = false ) {
	add_action('init', function () use ( $structure, $flush ) {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( $structure );
		$flush && $wp_rewrite->flush_rules();
	});
}

// ----------------------------------------------------------------------------- INDEXING

/**
 * Discourage search engines from indexing in non-production environments.
 * @param bool|string $allowIndexing
 *          true : Will allow search engines to index
 *          false : Will disallow search engines to index
 *          "auto" : Will allow only if WP_ENVIRONMENT_TYPE is "production"
 * @param bool $hideOptionInAdmin
 * 			Also remove checkbox in admin
 * @return void
 */
function bare_fields_feature_global_disable_indexing ( $allowIndexing = "auto", bool $hideOptionInAdmin = true ) {
	if ( $hideOptionInAdmin )
		AdminHelper::injectCustomAdminResourceForScreen('options-reading', 'tr.option-site-visibility { display: none; }');
	add_action('pre_option_blog_public', function () use ($allowIndexing) {
		return (
			$allowIndexing == "auto"
			? (wp_get_environment_type() === 'production' ? 1 : 0)
			: $allowIndexing
		);
	});
}

// ----------------------------------------------------------------------------- PHP MAILER

function bare_fields_feature_global_enable_phpmailer () {
	// Register SMTP email with HTML support.
	add_action('phpmailer_init', function (PHPMailer $mail) {
		$mail->isSMTP();
		$mail->SMTPAutoTLS = false;
		$mail->SMTPAuth = WPSHelper::getEnv('WPS_MAIL_USERNAME') && WPSHelper::getEnv('WPS_MAIL_PASSWORD');
		$mail->SMTPSecure = WPSHelper::getEnv('WPS_MAIL_ENCRYPTION', 'tls');
		$mail->Host = WPSHelper::getEnv('WPS_MAIL_HOST');
		$mail->Port = WPSHelper::getEnv('WPS_MAIL_PORT', 587);
		$mail->Username = WPSHelper::getEnv('WPS_MAIL_USERNAME');
		$mail->Password = WPSHelper::getEnv('WPS_MAIL_PASSWORD');
		return $mail;
	});

	add_filter('wp_mail_content_type', fn () => 'text/html');
	add_filter('wp_mail_from_name', fn () => WPSHelper::getEnv('WPS_MAIL_FROM_NAME', WPSHelper::getEnv('WPS_MAIL_USERNAME')));
	add_filter('wp_mail_from', fn () => WPSHelper::getEnv('WPS_MAIL_FROM_ADDRESS', WPSHelper::getEnv('WPS_MAIL_USERNAME')));
}

// ----------------------------------------------------------------------------- USERS

function bare_fields_feature_global_set_users_can_register ( $value = false ) {
	add_filter('pre_option_users_can_register', $value ? '__return_true' : '__return_zero');
}

function bare_fields_feature_global_force_users_default_role ( $role = 'subscriber' ) {
	add_filter('pre_option_default_role', fn ($default_role) => $role );
}
