<?php


use Nano\core\URL;

// ----------------------------------------------------------------------------- LOCALES

/**
 * Remove locale prefix from an URL.
 * Note : Will always remove base.
 * ex : https://domain.com/fr/my-post.html -> /my-post.html
 * ex : /fr/my-post.html -> /my-post.html
 * @param string $href URL to remove locale from
 * @return string
 */
function woolkit_locale_remove_from_href ( string $href ) : string {
	$href = URL::removeBaseFromHref( $href, woolkit_get_base() );
	$localeStart = '/'.woolkit_locale_get();
	if ( stripos($href, $localeStart) !== false )
		$href = substr($href, strlen($localeStart));
	return $href;
}

/**
 * Get current locale code, as "fr" or "en"
 */
function woolkit_locale_get () {
	global $__woolkitLocale;
	if ( is_null($__woolkitLocale) && function_exists('wpm_get_language')) {
		return wpm_get_language();
	}
	return $__woolkitLocale ?? '';
}

/**
 * This monstruosity has been made because I didn't find the correct way to
 * set the user's locale for the next post and singletons dynamically.
 * I'm sorry, please forgive.
 * Hours wasted in :
 * - class-wpm-posts.php ( unable to override current user locale which is PRIVATE )
 * - wpm-translation-function.php ( unable to hook when a post is translated )
 * - wpm-language-functions.php ( tried to patch wpm_get_language )
 */
function woolkit_locale_set ( string $locale ) {
	global $__woolkitLocale;
	$__woolkitLocale = $locale;
	$instance = wpm()->setup;
	$reflectionClass = new ReflectionClass( WPM\Includes\WPM_Setup::class );
	$property1 = $reflectionClass->getProperty('user_language');
	$property1->setValue( $instance, $locale );
//	$property2 = $reflectionClass->getProperty('default_language');
//	$property2->setValue( $instance, $locale );
//	$property3 = $reflectionClass->getProperty('default_locale');
//	$property3->setValue( $instance, $locale );
}


function woolkit_get_locales_list ( bool $extended = false ) {
	if ( !function_exists('wpm') )
		return [];
	// Get locale list and default
	$setup = wpm()->setup;
	$locales = $setup->get_languages();
	$default = $setup->get_default_language();
	// Clean local keys and select only enabled
	// Remove default locale from list
	$cleanLocalesKeys = [];
	foreach ( $locales as $localeKey => $locale )
		if ( $locale['enable'] && $localeKey !== $default )
			$cleanLocalesKeys[] = $localeKey;
	// Prepend default locale key
	array_unshift( $cleanLocalesKeys, $default );
	// Create the output array with local code as key and locale name as value
	$output = [];
	foreach ( $cleanLocalesKeys as $localeKey ) {
		$value = $locales[ $localeKey ];
		$output[ $localeKey ] = $extended ? $value : $value['name'];
	}
	return $output;
}

/**
 * Get list of all locales.
 */
function woolkit_locale_get_languages_list () {
	if ( !function_exists('wpm') )
		return [];
	return wpm()->setup->get_languages();
}

/**
 * Get locale switcher menu data.
 */
function woolkit_get_locale_switcher ( int $postID, string $currentLocale = "" ) {
	$locales = woolkit_get_locales_list();
	if ( empty($locales) )
		return [];
	// Try to get current locale automatically if not provided
	$currentLocale = empty($currentLocale) ? woolkit_locale_get() : $currentLocale;
	// Get current post locales
	$postLocales = $postID === 0 ? [] : (get_post_meta( $postID, '_languages', true ));
	if ( empty($postLocales) )
		$postLocales = array_keys($locales);
	$output = [];
	foreach ( $locales as $key => $locale ) {
		// If this post exists in this locale,
		// point to the translated post when changing locale.
		// Otherwise, go back to the locale home.
		$postExistsInThisLocale = in_array($key, $postLocales);
		$postHref = get_permalink( $postID );
		$href = $postExistsInThisLocale ? "/$key$postHref" : "/$key/";
		$output[ $key ] = [
			"name" => $locale,
			"href" => $href,
			"current" => $key === $currentLocale,
		];
	}
	return $output;
}
