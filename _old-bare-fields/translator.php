<?php
/*
Plugin Name: Translator
Description: WP multi-lang plugin compatible with ACF-Pro
Version: 1.0
Author: Alexis Bouhet
*/

//function admin_edit_modify_post_loaded( $post ) {
//	dd("ok");
//        $post->post_title = 'Custom Title'; // Modify title
//    if ( is_admin() && isset( $post->ID ) && $post->ID == 4 ) {
//    }
//	return $post;
//}
//add_action( 'edit_form_after_title', function() use ( &$post ) {
//	dd($post);
//});

//
//add_filter('the_posts', function ( $posts ) {
//    $locale = Locales::getCurrentLocale();
//    foreach ( $posts as $post ) {
//        if ( isset( $post->post_title ) ) {
//            $post->post_title = TranslatableFields::parseInlined( $post->post_title, $locale );
//        }
//    }
//    return $posts;
//});
//
//add_filter('default_title', function ( $title, $post ) {
//    $locale = Locales::getCurrentLocale();
//    return TranslatableFields::parseInlined( $title, $locale );
//}, 10, 2 );
//
//add_filter('wp_insert_post_data', function ( $data, $postarr ) {
//    if ( is_admin() && isset( $data['post_title'] ) ) {
//        $locale = Locales::getCurrentLocale();
//        $data['post_title'] = TranslatableFields::parseInlined( $data['post_title'], $locale );
//    }
//    return $data;
//}, 10, 2 );

use Translator\Locales;
use Translator\TranslationAdminUI;

require __DIR__ . '/vendor/autoload.php';


function inject_plugin_style_script ( $cssFileName = '', $jsFileName = '' ) {
	$pluginDirectory = __FILE__;
	add_action('admin_enqueue_scripts', function () use ( $pluginDirectory, $cssFileName, $jsFileName ) {
		if ( is_admin_bar_showing() ) {
			if ( !empty($cssFileName) ) {
				$styleUrl = plugins_url($cssFileName, $pluginDirectory);
				wp_enqueue_style('plugin-inline-style', $styleUrl);
			}
			if ( !empty($jsFileName) ) {
				$scriptUrl = plugins_url($jsFileName, $pluginDirectory);
				wp_enqueue_script('plugin-inline-script', $scriptUrl);
			}
		}
	});
}


// todo : move those outside plugins
Locales::setLocales([
	"en" => "English",
	"fr" => "Français",
]);
//Locales::setCurrentLocale("en");

// When admin is ready, update UI for translations
add_action("admin_init", function () {
	if ( !is_admin() ) return;


	// todo : move
	//	add_action( 'save_post', function ($postId) {
	//
	//	});

});


add_action("init", function () {
	//	dump("init");
	Locales::initCurrentLocale();
	TranslationAdminUI::patchTranslatedTitle();
	//	add_filter("the_title", function ($title, $id) {
	//		dump("a");
	//		return $title;
	//	}, 100, 2 );

	if ( !is_admin() ) {
	} else {
		if ( isset($_GET[ 'setAdminLocale' ]) ) {
			$newLocale = sanitize_text_field($_GET[ 'setAdminLocale' ]);
			Locales::changeLocale($newLocale);
			wp_safe_redirect(remove_query_arg('setAdminLocale'));
			exit;
		}

		Locales::initCurrentLocale();

		inject_plugin_style_script("assets/translator.css", "assets/translator.js");



		//	dd($out);

//		TranslationUI::injectLocaleSelector();
	}
});




add_filter( 'admin_body_class', function( $classes ) {
	$locale = Locales::getCurrentLocale();
	$classes .= " Translator_body__".$locale;
	return $classes;
});


add_action("admin_head", function() {
	if ( !is_admin() ) return;

	$locales = Locales::getLocales();
	$currentLocale = Locales::getCurrentLocale();

	echo "<style>";
	foreach ( $locales as $localeKey => $value ) {
		echo ".Translator_body__$localeKey .Translator_group .Translator_field.Translator_field__$localeKey {";
		echo "  display: block;";
		echo "}";
	}
	echo "</style>";

	$screen = get_current_screen();

	$translatableTemplates = apply_filters("translator_translatable_fields", []);
//	dump();
//	dump();
//	dump();
//	dump($translatableTemplates);
//	dump($screen);
	$isMultiLang = false;
	$isListing = false;

	if ( $screen->id === "edit-page" || $screen->id === "edit-post" ) {
		$isMultiLang = true;
		$isListing = true;
	}
	// todo : for multilang collections
	// todo : for multilang site options
	else if ( $screen->id === "page" ) {
		$postID = $_GET["post"] ?? null;
		$post = get_post($postID);
		$template = get_page_template_slug($post);
		if ( in_array($template, $translatableTemplates) ) {
			$isMultiLang = true;
//			TranslationAdminUI::injectLocaleSelector();
		}
	}
	else {
		dump($screen);
//		if ( $screen->id )
	}
	if ( $isMultiLang ) {
		TranslationAdminUI::injectLocaleSelector();
		$translator = [
			"locales" => $locales,
			"currentLocale" => $currentLocale,
			"isListing" => $isListing,
		];
		echo "<script>window.__Translator = ".json_encode($translator)."</script>";
	}
//	$screen->
	//	dump($screen);
	//    if ( $screen->id !== 'edit-page' && $screen->id !== 'edit-post' ) return;
	// Your PHP code here
});


//add_action("admin_init", function () {
//	$postID = $_GET["post"] ?? null;
//	$action = $_GET["action"] ?? null;
////	$screen = get_current_screen();
////	dump($screen);
//
////    if ( $screen->id !== 'edit-page' && $screen->id !== 'edit-post' ) return;
//
//
////	dump($action, $postID);
//
//	if ( $action !== "edit" || !$postID ) return;
//
//	$post = get_post($postID);
//	if (!$post) return;
//	$template = get_page_template_slug($post);
//	$translatableTemplates = apply_filters("translator_translatable_fields", []);
////	dump($translatableTemplates);
////	dump($template);
//	if ( in_array($template, $translatableTemplates) ) {
////		dd("in array");
//	}
//});
////add_action( 'admin_head', function() {
////	$screen = get_current_screen();
////	dd($screen);
//
////	if ( $screen->base === 'post' ) {
////			echo "<script>
////					window.isEditingPost = true;
////			</script>";
////	}
////});
//
//
////function save_multilang_title( $post_id ) {
////    if ( isset( $_POST['post_title'] ) ) {
////        update_post_meta( $post_id, '_multilang_title', $_POST['post_title'] );
////    }
////}
