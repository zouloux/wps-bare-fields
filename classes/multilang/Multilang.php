<?php

namespace BareFields\multilang;

use BareFields\requests\DocumentFilter;

class Multilang
{

  public static function isTranslatedGroupName ( string $name ) {
    return str_ends_with( $name, DocumentFilter::TRANSLATED_GROUP_MARKER );
  }

  public static function doesFieldHasTranslatedParent ( array $acfField ) {
    $parentKey = $acfField["parent"] ?? null;
    $i = 50; // fixme : sometimes we have an infinite loop, this is a temporary fix
    while ( $parentKey && --$i > 0 ) {
      $parentField = acf_get_field( $parentKey );
      $name = $parentField["name"];
      if ( $parentField && self::isTranslatedGroupName( $name ) )
        return true;
      $parentKey = $acfField["parent"] ?? null;
    }
    return false;
  }

	public static function parseInlinedValue ( string $string, string $locale ) {
		if ( preg_match('/\[:'.$locale.'\](.*?)\[:/', $string, $matches) )
			return $matches[ 1 ];
		else if (stripos($string, "[:]") !== false)
      return "";
    else
      return $string;
	}
}
