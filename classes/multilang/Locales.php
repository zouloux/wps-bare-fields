<?php

namespace BareFields\multilang;

class Locales {

	// --------------------------------------------------------------------------- DEFINE LOCALES

	protected static array $__locales = [];

	public static function setLocales ( array $locales ) {
		self::$__locales = $locales;
	}

	public static function getLocales () {
		return self::$__locales;
	}

	public static function getLocalesKeys () {
		return array_keys(self::$__locales);
	}

	public static function getDefaultLocaleKey () {
		return self::getLocalesKeys()[0];
	}

  public static function isMultilang () : bool {
    return count(self::getLocalesKeys()) > 1;
  }

	// --------------------------------------------------------------------------- CURRENT LOCALE
  // For non admin only

	protected static string $__currentLocale;

	public static function setCurrentLocale ( string $locale ) {
    // If wrong locale selected, use the default one
    if ( !in_array($locale, self::getLocalesKeys()) )
      $locale = self::getDefaultLocaleKey();
		self::$__currentLocale = $locale;
	}

	public static function getCurrentLocale () {
		return self::$__currentLocale;
	}

	public static function initCurrentLocale () {
    // Only if not already defined
    if ( isset(self::$__currentLocale) ) return;
    if ( !self::isMultilang() )
      return;
    self::setCurrentLocale(
      is_admin()
      ? self::readAdminLocale( false )
      : self::getDefaultLocaleKey()
    );
	}

  // --------------------------------------------------------------------------- ADMIN LOCALE IN SESSION
  // For admin only

  public static function readAdminLocale ( bool $allowAll = true ) : string {
		if ( !is_admin() ) return "";
    $currentUserId = get_current_user_id();
    $locale = get_user_meta($currentUserId, 'locale', true);
    if ( !$allowAll && $locale === "all" )
      return self::getDefaultLocaleKey();
    return (
      empty($locale)
      ? self::getDefaultLocaleKey()
      : $locale
    );
  }

	public static function writeAdminLocale ( string $locale ) {
		if ( !is_admin() ) return;
    $currentUserId = get_current_user_id();
    update_user_meta( $currentUserId, 'locale', $locale );
	}
}

