<?php

use Extended\ACF\Fields\ButtonGroup;
use Extended\ACF\Fields\ColorPicker;
use Extended\ACF\Fields\Repeater;
use Extended\ACF\Fields\Text;
use Nano\core\URL;

// ----------------------------------------------------------------------------- DICTIONARIES

function woolkit_create_dictionary_repeater_field ( string $label, string $name, string $button = "Add translation" ) {
	return Repeater::make($label, $name)
        ->button( $button )
        ->layout('table')
		->wrapper(["class" => "clean"])
        ->fields([
	        Text::make('Key', 'key')->required(),
	        Text::make('Value', 'value')->required(),
        ]);
}

function woolkit_create_dictionaries_fields_group ( string $title = "Dictionaries" ) {
	$group = new WoolkitGroupFields( $title );
	$group->rawFields()->multiLang();
	$group->fields([
		Repeater::make(' ', 'dictionaries')
			->button("Add dictionary")
			->layout('row')
			->fields([
				woolkit_create_title_field('Dictionary ID', 'id'),
				woolkit_create_accordion_field(woolkit_translate_label('Translations')),
				woolkit_create_dictionary_repeater_field(' ', woolkit_translate_key('data'))
					->wrapper(['class' => 'clean'])
			])
	]);
	return $group;
}

function woolkit_filter_dictionary_translations ( mixed $data ) {
	if ( !is_array($data) )
		return [];
	$output = [];
	foreach ( $data as $translation )
		if ( !empty($translation['key']) )
			$output[$translation['key']] = $translation['value'] ?? "";
	return $output;
}

function woolkit_patch_dictionary_translations ( mixed &$data, string $key ) {
	$data[$key] = woolkit_filter_dictionary_translations( $data[$key] );
}

function woolkit_create_dictionaries_filter ( string $key, string $subKey = "data" ) {
	return function ( $data ) use ( $key, $subKey ) {
		if ( !isset($data[$key]) || !is_array($data[$key]) )
			return $data;
		//			throw new Exception("woolkit_create_dictionaries_filter // Cannot find $key in data.");
		$dictionaries = $data[ $key ];
		$newArray = [];
		foreach ( $dictionaries as $dictionary ) {
			$translations = $dictionary[$subKey];
			$newDictionaryArray = [];
			if (is_array($translations))
				foreach ( $translations as $translation )
					$newDictionaryArray[ $translation['key'] ] = $translation['value'];
			$newArray[ $dictionary['id'] ] = $newDictionaryArray;
		}
		$data[ $key ] = $newArray;
		return $data;
	};
}

// ----------------------------------------------------------------------------- KEYS

function woolkit_create_keys_fields_group ( string $title = "API and product keys" ) {
	$group = new WoolkitGroupFields( $title );
	$group->rawFields();
	$group->fields([
		Repeater::make(' ', 'keys')
			->helperText("List API and product keys here")
			->button("Add key")
			->layout('table')
			->fields([
				Text::make('Key', 'key')->required(),
				Text::make('Value', 'value')->required(),
			])
	]);
	return $group;
}

function woolkit_create_keys_filter ( string $key ) {
	return function ( $data ) use ( $key ) {
		if ( !isset($data[$key]) || !is_array($data[$key]) )
			return $data;
		//			throw new Exception("woolkit_create_keys_filter // Cannot find $key in data.");
		$keys = $data[$key];
		$newArray = [];
		foreach ($keys as $associativeKeyValue)
			$newArray[ $associativeKeyValue['key'] ] = $associativeKeyValue['value'];
		$data[$key] = $newArray;
		return $data;
	};
}

// ----------------------------------------------------------------------------- THEME OPTIONS

function woolkit_create_theme_options_fields_group ( string $title = "Theme options" ) {
	$group = new WoolkitGroupFields( $title );
	$group->multiLang();
	$group->fields([
		Text::make("Page title template", 'pageTitleTemplate')
			->placeholder("{{site}} - {{page}}")
			->helperText("<strong>{{site}}</strong> for site name<br><strong>{{page}}</strong> for page name."),
		woolkit_create_image_field("Icon 32", "icon32", "microImage")
			->helperText("Favicon<br>32x32px, png<br>For desktop"),
		woolkit_create_image_field("Icon 1024", "icon1024")
			->helperText("Favicon<br>1024x1024px, png<br>For mobile"),
		Text::make(woolkit_translate_label("Mobile App title"), woolkit_translate_key('appTitle'))
			->helperText("Shortcut name on mobile when added to home page."),
		ColorPicker::make("Theme color", "appColor")
			->helperText("Browser theme color, for desktop and mobile."),
		ButtonGroup::make("iOS title bar color", 'iosTitleBar')
			->choices([
				'none' => "Not set",
				'default' => 'Default',
				'black' => 'Black',
				'translucent' => 'Translucent',
			])
	]);
	return $group;
}

function woolkit_create_theme_filter ( $themeKey = "theme" ) {
	return function ( $data ) use ( $themeKey ) {
		woolkit_filter_image_to_href( $data[$themeKey], "icon32" );
		woolkit_filter_image_to_href( $data[$themeKey], "icon1024" );
		return $data;
	};
}

// ----------------------------------------------------------------------------- MENU FIELD

// FIXME : Add multi-level menu option, allow 2 levels deep

function woolkit_create_menu_fields_group ( string $id, string $title = "menu") {
	$group = new WoolkitGroupFields( $title );
	$group->rawFields()->multiLang();
	$group->fields([
		Repeater::make(' ', $id)->fields( [
			woolkit_create_page_link_field(),
			Text::make(' ', woolkit_translate_key('title'))
				->helperText(woolkit_translate_label('Title override (Optional)')) // TODO argument
		])
	]);
	return $group;
}

/**
 * Will get title for link to internal pages / post.
 * Will detect external links.
 * @param string $key
 * @return Closure
 */
function woolkit_create_menu_filter ( string $key ) {
	return function ( $data ) use ( $key ) {
		if ( !isset($data[$key]) || !is_array($data[$key]) )
			return $data;
		foreach ( $data[$key] as &$itemValue ) {
			// Link will be null if page does not exist in current locale
			if ( !isset($itemValue['link']) ) continue;
			$link = $itemValue['link'];
			// Get post from link
			$post = WoolkitRequest::getWPPostByPath(
				$link === "/" ? get_home_url() : $link
			);
			// Save short link
			$itemValue["link"] = URL::removeBaseFromHref( $link, woolkit_get_base() );
			// No title override, get from post
			if ( empty($itemValue['title']) && !is_null($post) )
				$itemValue["title"] = $post->post_title;
			// Patch title
			$itemValue["title"] = woolkit_translate_fix_string( $itemValue["title"] );
		}
		return $data;
	};
}

