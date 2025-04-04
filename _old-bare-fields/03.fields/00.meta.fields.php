<?php

use Extended\ACF\Fields\ButtonGroup;
use Extended\ACF\Fields\ColorPicker;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;
use Nano\core\Env;

// ----------------------------------------------------------------------------- META FIELDS

function woolkit_create_default_meta_fields_group ( string $title = 'Default meta', $showTitleOverride = true, $showKeywords = false ) {
	$group = new WoolkitGroupFields( $title );
	$group->multiLang();
	$fields = [
		Textarea::make(woolkit_translate_label("Meta description"), woolkit_translate_key('description'))
			->rows(3),
//			->helperText("For SEO only. Optional."),
		$showKeywords ? Textarea::make("Keywords", "keywords")->helperText("Comma separated.")->rows(2) : null,
		$showTitleOverride ? Text::make(woolkit_translate_label("Title override"), woolkit_translate_key('title'))
			->helperText("Override head title tag. Useful to separate post title from title tag. Title template will be ignored.") : null,
		Text::make(woolkit_translate_label("Share title"), woolkit_translate_key('shareTitle'))
			->helperText("Will use page title by default."),
		Textarea::make(woolkit_translate_label("Share description"), woolkit_translate_key('shareDescription'))
			->rows(3)
			->helperText("Will use meta description by default."),
		woolkit_create_image_field("Share image", 'shareImage')
			->helperText("For Facebook and Twitter sharing"),
	];
	$fields = array_filter( $fields, fn ($item) => $item !== null );
	$group->fields( $fields );
	return $group;
}

function woolkit_create_page_meta_fields_group ( string $title = "Page meta") {
	$group = new WoolkitGroupFields( $title );
	$group->multiLang();
	$fields = [
		Textarea::make(woolkit_translate_label("Description override"), woolkit_translate_key('description'))
			->rows(2),
//			->helperText("Optional."),
		Text::make(woolkit_translate_label("Title override"), woolkit_translate_key('title'))
			->helperText("Override head title tag."),
		Text::make("Canonical URL", "canonicalUrl")
			->placeholder("/other-page")
			->helperText("Starting with <strong>/</strong><br>Relative to <strong>".Env::get("WP_HOME")."</strong>"),
		ButtonGroup::make("Sitemap presence", "sitemap")
			->default("visible")
			->choices([
				"hidden" => "Hidden",
				"visible" => "Visible"
			]),
	];
	$fields = array_filter( $fields, fn ($item) => $item !== null );
	$group->fields( $fields );
	return $group;
}

function woolkit_create_theme_group ( string $title = 'Theme' ) {
	$group = new WoolkitGroupFields( $title );
	$group->fields([
		woolkit_create_title_field(...woolkit_translate_field("App title", "title"))
			->helperText("Mobile device application title"),
		woolkit_create_image_field("Favicon 32", "favicon32", "microImage")
			->helperText("Icon for browsers.<br>PNG - 32px x 32px"),
		woolkit_create_image_field("Favicon 1024", "favicon1024")
			->helperText("Icon for mobile devices.<br>PNG - 1024px x 1024px"),
		ColorPicker::make("Theme color", "color"),
		ButtonGroup::make("iOS title bar style", "iosTitleBarStyle")
			->default("default")
			->choices([
				"default" => "Default",
				"black" => "Black",
				"black-translucent" => "Translucent",
			])
	]);
	return $group;
}

function woolkit_create_meta_filter ( $metaKey = "meta" ) {
	return function ( $data ) use ( $metaKey ) {
		woolkit_filter_image_to_href( $data[$metaKey], "shareImage" );
		return $data;
	};
}

$__woolkitDefaultMeta = null;
function woolkit_create_meta_global_filter ( $metaKey = "meta" ) {
	return function ( $fields, $level = null, $post = null ) use ( $metaKey ) {
		// If we are in a filter with some meta
		// Check if we have a post to avoid infinite loops when filtering the default meta option
		// Because the default meta options has no post associated
		if ( isset($fields[$metaKey]) && is_array($fields[$metaKey]) && !is_null($post) ) {
			// Get global default meta
			global $__woolkitDefaultMeta;
			$__woolkitDefaultMeta ??= WoolkitRequest::getSingleton( $metaKey );
			// Get current post meta
			$fieldMeta = $fields[$metaKey];
			// Default title is post title, can be overridden by meta
			if ( empty($fieldMeta['title']) )
				$fieldMeta['title'] = $post->post_title;
			// Get meta defaults
			$defaultMeta = $__woolkitDefaultMeta["meta"] ?? [];
			// Merge
			$fields[$metaKey] = [
				...$fieldMeta,
				...$defaultMeta,
			];
		}
		return $fields;
	};
}
