<?php


use BareFields\helpers\AdminHelper;
use BareFields\helpers\WPSHelper;
use Nano\core\Env;


// ----------------------------------------------------------------------------- BANNER

function bare_fields_feature_admin_print_env_banner() {
  $banner = Env::get( 'WPS_ADMIN_BANNER', '' );
  if ( empty( $banner ) )
    return;
  add_action( 'admin_bar_menu', function ( WP_Admin_Bar $adminBar ) use ( $banner ) {
		$split       = explode( "|", $banner, 2 );
		$bannerName  = $split[ 0 ];
		$bannerColor = $split[ 1 ] ?? "black";
		$adminBar->add_node([
      'id'     => 'top-env-banner',
			'title' => '<div style="background-color: '.$bannerColor.';">'.$bannerName.'</div>',
      'parent' => 'top-secondary',
      //'meta'  => ['class' => 'admin-left-div2', 'style' => 'pointer-events: none;', 'tabindex' => '-1'],
    ]);
	}, 100); // Low priority to be on the left of avatar
}

// ----------------------------------------------------------------------------- STYLE OVERRIDE

/**
 * Inject patched admin style
 * @return void
 */
function bare_fields_feature_admin_inject_style_override () {
	// Style and script override
	AdminHelper::injectStyleFile(WPS_BARE_FIELDS_PLUGIN_DIR.'assets/clean-admin.css');
	// Remove the admin footer text and version
	add_filter('admin_footer_text', function () { return ''; });
	add_action('admin_menu', function () {
		remove_filter('update_footer', 'core_update_footer');
	});
	// Remove WP logo on top left
	add_action('admin_bar_menu', function ($wp_admin_bar) {
		$wp_admin_bar->remove_node('wp-logo');
	}, 11);
	// Cleaner login
	add_filter('login_headerurl', fn () => home_url());
	add_filter('login_headertext', fn () => get_bloginfo('name'));
	add_filter('login_display_language_dropdown', '__return_false');
	add_action('login_enqueue_scripts', function () { ?>
		<style>
			#login h1 a {
				background-image: none;
				pointer-events: none;
				cursor: auto;
				font-size: 40px;
				width: auto;
				height: auto;
				text-indent: 0;
			}
		</style>
	<?php });
	// disable admin-theme selection in admin
	add_action('admin_init', function() {
		remove_action('admin_color_scheme_picker', 'admin_color_scheme_picker');
	});
}

// ----------------------------------------------------------------------------- DASHBOARD

function bare_fields_feature_admin_disable_dashboard_boxes ( $welcome = true, $events = true, $quickDraft = true, $activity = true, $atGlance = true, $health = true ) {
	add_action('wp_dashboard_setup', function () use ($welcome, $events, $quickDraft, $activity, $atGlance, $health ){
		$welcome && remove_action('welcome_panel', 'wp_welcome_panel');
		$events && remove_meta_box('dashboard_primary', 'dashboard', 'side');
		$quickDraft && remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
		$activity && remove_meta_box('dashboard_activity', 'dashboard', 'normal');
		$atGlance && remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
		$health && remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
	}, 999);
}

// ----------------------------------------------------------------------------- APPEARANCE

function bare_fields_feature_admin_disable_menu_appearance () {
	add_action('admin_menu', function () {
		remove_submenu_page('themes.php', 'customize.php');
		remove_submenu_page('themes.php', 'themes.php');
		remove_submenu_page('themes.php', 'theme-editor.php');
		remove_menu_page('themes.php');
	}, 999);
	AdminHelper::disableAdminPage('customize.php');
	AdminHelper::disableAdminPage('themes.php');
	AdminHelper::disableAdminPage('theme-editor.php');
}

// ----------------------------------------------------------------------------- PLUGINS

function bare_fields_feature_admin_patch_plugin_page () {
	// Replace plugin page by mu-plugins
	add_action('admin_menu', function () {
		remove_menu_page('plugins.php');
		add_menu_page(
			'Plugins',
			'Plugins',
			'manage_options',
			'plugins.php?plugin_status=mustuse',
			'',
			'dashicons-admin-plugins',
			60
		);
	});
	add_action('parent_file', function ($parent_file) {
		global $pagenow;
		if ( $pagenow == 'plugins.php' && isset($_REQUEST['plugin_status']) && $_REQUEST['plugin_status'] == 'mustuse' )
			$parent_file = 'plugins.php?plugin_status=mustuse';
		return $parent_file;
	});
	// Hide drop-in
	add_filter('show_advanced_plugins', fn ($show, $type) => ($type == 'dropins') ? false : $show, 10, 2);
}

// ----------------------------------------------------------------------------- HELP TAB

function bare_fields_feature_admin_remove_help_tab () {
	add_action('admin_head', function () {
		$screen = get_current_screen();
		$screen->remove_help_tabs();
	});
}
// ----------------------------------------------------------------------------- TOP BAR BUTTONS

function bare_fields_feature_admin_remove_topbar_new_button () {
	add_action('admin_bar_menu', function (WP_Admin_Bar $adminBar) {
		$adminBar->remove_node('new-content');
	}, 999);
}

function bare_fields_feature_admin_remove_topbar_sqlite_button () {
	add_action( 'admin_bar_menu', function ( WP_Admin_Bar $adminBar ) {
		$adminBar->remove_node('sqlite-db-integration');
	}, 1000000000);
}

// ----------------------------------------------------------------------------- MENU

// TOOLS MENU
function bare_fields_feature_admin_remove_tools_submenu_available () {
	add_action('admin_menu', fn () => remove_submenu_page('tools.php', 'tools.php') );
	AdminHelper::disableAdminPage("tools.php");
}
function bare_fields_feature_admin_remove_tools_submenu_import () {
	add_action('admin_menu', fn () => remove_submenu_page('tools.php', 'import.php') );
	AdminHelper::disableAdminPage("import.php");
}
function bare_fields_feature_admin_remove_tools_submenu_export () {
	add_action('admin_menu', fn () => remove_submenu_page('tools.php', 'export.php') );
	AdminHelper::disableAdminPage("export.php");
}
function bare_fields_feature_admin_remove_tools_submenu_export_personal_data () {
	add_action('admin_menu', fn () => remove_submenu_page('tools.php', 'export-personal-data.php') );
	AdminHelper::disableAdminPage("export-personal-data.php");
}
function bare_fields_feature_admin_remove_tools_submenu_erase_personal_data () {
	add_action('admin_menu', fn () => remove_submenu_page('tools.php', 'erase-personal-data.php') );
	AdminHelper::disableAdminPage("erase-personal-data.php");
}
function bare_fields_feature_admin_remove_tools_submenu_health () {
	add_action('admin_menu', fn () => remove_submenu_page('tools.php', 'site-health.php') );
	AdminHelper::disableAdminPage("site-health.php");
}
function bare_fields_feature_admin_remove_tools_mastermenu () {
	add_action('admin_menu', fn () => remove_menu_page('tools.php') );
}

// SETTINGS MENU
function bare_fields_feature_admin_remove_settings_submenu_writing () {
	add_action('admin_menu', fn () => remove_submenu_page('options-general.php', 'options-writing.php') );
	AdminHelper::disableAdminPage("options-writing.php");
}
function bare_fields_feature_admin_remove_settings_submenu_reading () {
	add_action('admin_menu', fn () => remove_submenu_page('options-general.php', 'options-reading.php') );
	AdminHelper::disableAdminPage("options-reading.php");
}
function bare_fields_feature_admin_remove_settings_submenu_discussion () {
	add_action('admin_menu', fn () => remove_submenu_page('options-general.php', 'options-discussion.php') );
	AdminHelper::disableAdminPage("options-discussion.php");
}
function bare_fields_feature_admin_remove_settings_submenu_media () {
	add_action('admin_menu', fn () => remove_submenu_page('options-general.php', 'options-media.php') );
	AdminHelper::disableAdminPage("options-media.php");
}
function bare_fields_feature_admin_remove_settings_submenu_permalink () {
	add_action('admin_menu', fn () => remove_submenu_page('options-general.php', 'options-permalink.php') );
	AdminHelper::disableAdminPage("options-permalink.php");
}
function bare_fields_feature_admin_remove_settings_submenu_privacy () {
	add_action('admin_menu', fn () => remove_submenu_page('options-general.php', 'options-privacy.php') );
	AdminHelper::disableAdminPage("options-privacy.php");
}

// ----------------------------------------------------------------------------- HEALTH MENU

// Move health to settings
// Health should not be removed from tools, otherwise the page will not work.
// Useful if health is the last item remaining in tools
function bare_fields_feature_admin_move_submenu_health_to_settings () {
	add_action('admin_menu', fn () => remove_submenu_page('tools.php', 'site-health.php') );
	add_action('admin_menu', function () {
		add_submenu_page(
			'options-general.php',
			__('Site Health'),
			__('Site Health'),
			'manage_options',
			'site-health.php'
		);
	});
}

// Patch site health tests
function bare_fields_feature_admin_patch_site_health ( $phpExtensions = true, $scheduledEvents = true, $availableDiskSpace = true, $useDefaultTheme = true, $rest = true ) {
	add_filter( 'site_status_tests', function( $tests ) use ( $phpExtensions, $scheduledEvents, $availableDiskSpace, $useDefaultTheme, $rest ) {
		if ( $phpExtensions )
			unset( $tests['direct']['php_extensions'] );
		if ( $scheduledEvents )
			unset( $tests['direct']['scheduled_events'] );
		if ( $availableDiskSpace )
			unset( $tests['direct']['available_updates_disk_space'] );
		if ( $useDefaultTheme )
			unset( $tests['direct']['theme_version'] );
		if ( $rest )
	    unset( $tests['direct']['rest_availability'] );
	  return $tests;
	});
}

// ----------------------------------------------------------------------------- ACF MENU

function _bare_fields_feature_admin_disable_acf_page ( $postType ) {
	add_action('admin_menu', fn () => remove_submenu_page('edit.php?post_type=acf-field-group', 'edit.php?post_type='.$postType) );
	add_action('admin_init', function () use ( $postType ) {
		global $pagenow;
		$type = $_GET['post_type'] ?? '';
		if ( ($pagenow == 'edit.php' || $pagenow == 'post-new.php') && $type == $postType ) {
			wp_redirect(admin_url());
			exit;
		}
	});
}
function _bare_fields_feature_admin_disable_acf_tool ( $tool ) {
	add_action('admin_menu', fn () => remove_submenu_page('edit.php?post_type=acf-field-group', $tool) );
	add_action('admin_init', function () use ( $tool ) {
		global $pagenow;
		$pt = $_GET['post_type'] ?? '';
		$page = $_GET['page'] ?? '';
		if ( $pagenow == 'edit.php' && $pt == 'acf-field-group' && $page == $tool ) {
			wp_redirect(admin_url());
			exit;
		}
	});
}

function bare_fields_feature_admin_remove_submenu_acf_post_types () {
	_bare_fields_feature_admin_disable_acf_page('acf-post-type');
}
function bare_fields_feature_admin_remove_submenu_acf_fields_group () {
	_bare_fields_feature_admin_disable_acf_page('acf-field-group');
}
function bare_fields_feature_admin_remove_submenu_acf_taxonomy () {
	_bare_fields_feature_admin_disable_acf_page('acf-taxonomy');
}
function bare_fields_feature_admin_remove_submenu_acf_ui_option_page () {
	_bare_fields_feature_admin_disable_acf_page('acf-ui-options-page');
}
function bare_fields_feature_admin_remove_submenu_acf_tools () {
	_bare_fields_feature_admin_disable_acf_tool('acf-tools');
}
function bare_fields_feature_admin_remove_submenu_acf_updates () {
	_bare_fields_feature_admin_disable_acf_tool('acf-settings-updates');
}
function bare_fields_feature_admin_remove_mastermenu_acf () {
	add_action('admin_menu', fn () => remove_menu_page('edit.php?post_type=acf-field-group') );
}

// ----------------------------------------------------------------------------- META BOXES

// Disable meta box draggable. Custom admin-script will add back open / close feature
function bare_fields_feature_admin_disable_meta_box_draggable () {
	add_action( 'admin_init', function () {
		// Check if we are on an edit / create page in admin
		global $pagenow;
		if ( !in_array($pagenow, ['post-new.php', 'post.php', 'admin.php']) ) return;
		// Remove original drag and drop and open / close for all meta boxes
		$style = ".postbox .handle-order-higher, .postbox .handle-order-lower { display: none }\n";
		//$style .= ".postbox .postbox-header .handlediv { display: none; }\n";
		$style .= ".postbox .postbox-header .hndle { pointer-events: none; }\n";
		// IMPORTANT NOTE : Do not use this, it will prevent usage of all "edit" buttons on admin !
		// wp_deregister_script('postbox');
		$script = "window._customMetaboxBehavior = true;";
		// IMPORTANT NOTE : Do not remove this class, ACF will crashes when changing template in admin
		// Remove hndle class will disable draggable on meta boxes
		//$script = "jQuery(document).ready(function (\$) {\$('.postbox .postbox-header .hndle').removeClass('hndle');});";
		AdminHelper::injectCustomAdminResourceForScreen(null, $style, $script);
	});
}

// Add main image meta box on articles
function bare_fields_feature_admin_enable_image_meta_box ( $screenIDs = ["post"] ) {
	add_action( 'current_screen', function () use ( $screenIDs ) {
		$screen = get_current_screen();
		if (isset($screen->id) && in_array($screen->id, $screenIDs) )
			add_theme_support( 'post-thumbnails' );
	});
}

// Clean meta box on sidebar
function bare_fields_feature_admin_enable_clean_meta_box_sidebar ( $excerptOnSide = true, $authorOnSide = true, $disableTags = true, $disableSlug = true ) {
	add_action('add_meta_boxes', function () use ($excerptOnSide, $authorOnSide, $disableTags, $disableSlug) {
		global $wp_meta_boxes;
		//dump($wp_meta_boxes);exit;
		foreach ( $wp_meta_boxes as $value ) {
			// Move excerpt on size
			if ( $excerptOnSide ) {
				if ( isset($value['normal']['core']['postexcerpt']) ) {
					$value['side']['core']['postexcerpt'] = $value['normal']['core']['postexcerpt'];
					unset($value['normal']['core']['postexcerpt']);
				}
			}
			// Move author meta box on side
			if ( $authorOnSide ) {
				if ( isset($value['normal']['core']['authordiv']) ) {
					$value['side']['core']['authordiv'] = $value['normal']['core']['authordiv'];
					unset($value['normal']['core']['authordiv']);
				}
			}
			// Remove tags box
			if ( $disableTags )
				unset($value['side']['core']['tagsdiv-post_tag']);
			// Remove slug box
			if ( $disableSlug )
				unset($value['normal']['core']['slugdiv']);
		}
	}, 0);
}

function bare_fields_feature_admin_remove_visibility_in_meta_box () {
	AdminHelper::injectCustomAdminResourceForScreen(
		null,
		"#visibility { display: none !important; }"
	);
}

// ----------------------------------------------------------------------------- CLASSIC EDITOR

function bare_fields_admin_set_editor_block_formats ( array $blocks ) {
	add_filter("tiny_mce_before_init", function ($settings) use ($blocks) {
		$blockFormats = [];
		foreach ($blocks as $key => $value)
			$blockFormats[] = "$value=$key";
		$settings["block_formats"] = implode(";", $blockFormats);
		return $settings;
	});
}

function bare_fields_admin_set_editor_style_formats ( array $styles ) {
	add_filter('tiny_mce_before_init', function ( $init ) use ( $styles ) {
		// Declare new styles formats
		$init['style_formats'] = wp_json_encode( $styles );
		// Init style rendering of those formats in TinyMCE
		if (!isset($init['content_style']))
			$init['content_style'] = '';
		// Browser formats
		foreach ( $styles as $format ) {
			// Generate style for TinyMce
			$computedStyle = '';
			foreach ( $format['style'] as $key => $value )
				$computedStyle .= $key.': '.$value.'; ';
			$init['content_style'] .= " .".$format['classes']." {".$computedStyle."} ";
		}
		return $init;
	});
}

function bare_fields_feature_admin_set_editor_buttons ( $buttons1, $buttons2 ) {
	add_filter( 'mce_buttons', fn ($buttons) => $buttons1 ?? $buttons );
	add_filter( 'mce_buttons_2', fn ($buttons) => $buttons2 ?? $buttons );
}

// ----------------------------------------------------------------------------- PUBLISH AND PREVIEW

function bare_fields_feature_add_preview_parameter ( string $key, string $value ) {
	add_filter('preview_post_link', function ( $link ) use ( $key, $value )  {
		return add_query_arg($key, $value, $link);
	});
}

function bare_fields_feature_disable_preview_button () {
	add_action('admin_head', function () {
		echo '<style>#preview-action { display: none }</style>';
	});
	function remove_preview_button($actions) {
    unset($actions['view']);
    return $actions;
	}
	add_filter('post_row_actions', 'remove_preview_button');
	add_filter('page_row_actions', 'remove_preview_button');
}

function bare_fields_feature_disable_quick_edit () {
	function remove_quick_edit($actions) {
		unset($actions['inline hide-if-no-js']);
		return $actions;
	}
	add_filter('post_row_actions', 'remove_quick_edit');
	add_filter('page_row_actions', 'remove_quick_edit');
}


// ----------------------------------------------------------------------------- MOST USED CATEGORIES

function bare_fields_feature_admin_remove_most_used_categories_selector () {
	add_action('admin_head', function () {
		if ( !is_admin() ) return;
		?><style>
			#category-tabs {
				display: none;
			}
			#category-all {
				border: 0;
			}
			#category-adder {
				text-align: center;
			}
			#categorydiv .inside {
				padding: 0;
				margin: 0;
			}
		</style>
		<?php
	});
	add_action( 'admin_footer', function () {
		if ( !is_admin() ) return;
		?><script type="text/javascript">
			jQuery(document).ready(function($) {
				setTimeout(function () {
					$('#category-tabs > li:first-child > a').trigger('click');
				}, 200)
			});
		</script>
		<?php
	});
}

// ----------------------------------------------------------------------------- SVG UPLOAD

function bare_fields_feature_admin_enable_svg_file_upload () {
	add_filter("upload_mimes", function ($mimes) {
		$mimes['svg'] = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';
		return $mimes;
	});
}

function bare_fields_feature_admin_enable_doc_file_upload () {
	add_filter("upload_mimes", function ($mimes) {
		$mimes['doc'] = 'application/msword';
		$mimes['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
		return $mimes;
	});
}

// ----------------------------------------------------------------------------- ANALYTICS BUTTON

function bare_fields_feature_enable_analytics_button ( string $href ) {
	add_action('admin_bar_menu', function ($adminBar) use ( $href ) {
		$adminBar->add_node([
			'id' => 'open-analytics',
			'title' => 'Open Analytics',
			'href' => $href,
			'meta' => [ 'class' => 'open-analytics-top-button', "target" => "_blank" ]
		]);
	}, 40);
}

// ----------------------------------------------------------------------------- COPY FIELDS BUTTONS

function bare_fields_feature_enable_copy_fields_buttons () {
	AdminHelper::injectScriptFile(WPS_BARE_FIELDS_PLUGIN_DIR.'assets/copy-fields.js');
}

// ----------------------------------------------------------------------------- POST LISTING COLUMNS

// Add a template column on pages listing
function bare_fields_feature_enable_page_template_column () {
	add_filter( 'manage_pages_columns', function ( $columns ) {
		$columns['template'] = "Template";
		return $columns;
	});
	add_action( 'manage_pages_custom_column', function ( $columnName, $postId ) {
		if ( 'template' === $columnName ) {
			$templateSlug = get_post_meta( $postId, '_wp_page_template', true );
			if ( 'default' === $templateSlug || empty( $templateSlug ) ) {
				echo 'Default Template';
			} else {
				$post = get_post( $postId );
				$templates = get_page_templates( $post );
				$templates = array_flip($templates);
				$templateName = $templates[ $templateSlug ] ?? $templateSlug;
				echo esc_html( $templateName );
			}
		}
	}, 10, 2 );
}

// Remove the author column in all post types
function bare_fields_feature_disable_author_column () {
	add_action( 'admin_init', function () {
    $postTypes = get_post_types([ "show_ui" => true ]);
    foreach ( $postTypes as $postType ) {
			add_filter("manage_{$postType}_posts_columns", function ( $columns ) {
				unset( $columns['author'] );
				return $columns;
			});
    }
	});
}

// Add a slug column in all public post types
function bare_fields_feature_enable_slug_column () {
	add_action( 'admin_init', function () {
		$postTypes = get_post_types([ "show_ui" => true, "public" => true ]);
		foreach ( $postTypes as $postType ) {
			add_filter( "manage_{$postType}_posts_columns", function ( $columns ) {
				$columns['href'] = "Slug";
				return $columns;
			});
			add_action( "manage_{$postType}_posts_custom_column", function ( $columnName, $postId ) {
				if ( 'href' === $columnName ) {
					$url = get_permalink( $postId );
					$urlPart = WPSHelper::removeBaseFromHref( $url, WPSHelper::getBase() );
					echo '<a href="'.esc_url( $url ).'" target="_blank">'.$urlPart.'</a>';
				}
			}, 10, 2 );
		}
	});
}

