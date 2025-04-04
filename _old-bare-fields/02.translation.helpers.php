<?php

/**
 * Get translated field key.
 * For example : "description" in french locale will give "fr_description"
 */
function woolkit_translate_key ( string $key ) {
	return woolkit_locale_get().'_'.$key;
}

/**
 * Add a "translated field" next to translatable field titles
 */
function woolkit_translate_label ( string $label ) {
	$locales = woolkit_locale_get_languages_list();
	if ( count($locales) <= 1 )
		return $label;
	return $label.' <span class="woolkit_translated">['.strtoupper(woolkit_locale_get()).']</span>';
}

function woolkit_translate_field ( string $label, string $key ) {
	return [
		woolkit_translate_label( $label ),
		woolkit_translate_key( $key ),
	];
}

/**
 * Fix a translated wpm string containing [:fr] markers.
 * Will not fail plugin not enabled
 */
function woolkit_translate_fix_string ( $string ) {
	return (
		function_exists('wpm_translate_string')
		? wpm_translate_string( $string )
		: $string
	);
}

function woolkit_translate_get_months ( string $locale ) {
	if ( $locale === "en" )
		return [
			"January", "February", "March", "April", "May", "June", "July",
			"August", "September", "October", "November", "December",
		];
	else if ( $locale === "fr" )
		return [
			"Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet",
			"Aout", "Septembre", "Octobre", "Novembre", "Décembre",
		];
	else
		throw new Exception("woolkit_translate_get_months // Invalid locale $locale");
}

/**
 * Translate a date in French or English
 * @param DateTime $date
 * @param string $locale
 * @param string $format
 * @return array|string|string[]
 * @throws Exception
 */
function woolkit_translate_date ( \DateTime $date, string $locale, string $format = "j F Y" ) {
	$postDate = $date->format( $format );
	$translations = [
		"en" => [
			// Months
			"January", "February", "March", "April", "May", "June", "July",
			"August", "September", "October", "November", "December",
			// Days
			"Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday",
		],
		"fr" => [
			// Months
			"Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet",
			"Aout", "Septembre", "Octobre", "Novembre", "Décembre",
			// Days
			"Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche",
		]
	];
	if ( $locale === "fr" )
		return str_replace( $translations["en"], $translations["fr"], $postDate );
	else if ( $locale === "en" )
		return str_replace( $translations["fr"], $translations["en"], $postDate );
	else
		throw new \Exception("woolkit_format_and_translate_date // Locale $locale is not supported.");
}
