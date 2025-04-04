<?php

namespace BareFields\multilang;

use Exception;

class TranslationHelpers
{
  public static function getMonths ( string $locale ) {
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
      throw new Exception("Multilang::getMonths // Invalid locale $locale");
  }

  public static function getDays ( string $locale ) {
    if ( $locale === "en" )
      return ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
    else if ( $locale === "fr" )
      return ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche"];
    else
      throw new Exception("Multilang::getMonths // Invalid locale $locale");
  }

  // todo
  public static function getDateFormat () {

  }
}
