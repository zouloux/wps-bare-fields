<?php

namespace BareFields\multilang;

use BareFields\helpers\ACFFieldsPatcher;
use BareFields\requests\DocumentFilter;
use Extended\ACF\Fields\Checkbox;
use Extended\ACF\Fields\Field;
use Extended\ACF\Fields\Group;

class TranslatedFields
{
  protected static function patchLocaleField ( $settingsProperty, Field $localeField, $locale ) {
    $settings = $settingsProperty->getValue($localeField);
    $wrapper = $settings[ "wrapper" ] ?? [];
    $settingsProperty->setValue($localeField, [
      ...$settings,
      "label" => $settings["label"].'<span class="BareFields_locale"><span>'.$locale.'</span></span>',
      "name" => $locale,
      "wrapper" => [
        ...$wrapper,
        "class" => "BareFields_translatedField BareFields_translatedField__".$locale." ".($wrapper["class"] ?? "")
      ]
    ]);
  }

	public static function one ( callable $generator, string $layout = "row", string $groupFieldLabel = null, bool $toggle = true ) : Field {
    if ( !$toggle )
      return $generator();
		$settingsProperty = ACFFieldsPatcher::patchSettingsAccessibility();
		$locales = Locales::getLocalesKeys();

		$output = [];
    $fieldName = null;
    $fieldLabel = null;
		foreach ( $locales as $locale ) {
			$localeField = $generator( $locale );
      $settings = $settingsProperty->getValue($localeField);
      $fieldName ??= $settings["name"];
      $fieldLabel ??= $settings["label"];
      self::patchLocaleField( $settingsProperty, $localeField, $locale );
      $output[] = $localeField;
		}

    $multilangFieldName = ($fieldName ?? "").DocumentFilter::TRANSLATED_GROUP_MARKER;
		return Group::make($groupFieldLabel ?? $fieldLabel ?? " ", $multilangFieldName)
			->wrapper([ "class" => "BareFields_translatedGroup clean" ])
			->layout($layout)
			->fields($output);
  }

  public static function many ( callable $generator, string $layout = "row", bool $toggle = true ) : array {
    if ( !$toggle )
      return $generator();
    $settingsProperty = ACFFieldsPatcher::patchSettingsAccessibility();
		$locales = Locales::getLocalesKeys();

    $fieldLabels = [];
    $translatedFields = [];
    foreach ( $locales as $locale ) {
      $localeFields = $generator( $locale );
      foreach ( $localeFields as $localeField ) {
        $settings = $settingsProperty->getValue($localeField);
        $fieldName = $settings["name"];
        $fieldLabels[ $fieldName ] ??= $settings["label"];
        self::patchLocaleField( $settingsProperty, $localeField, $locale );
        if ( !isset($translatedFields[$fieldName]) )
          $translatedFields[$fieldName] = [];
        $translatedFields[$fieldName][ $locale ] = $localeField;
      }
    }
    $output = [];
    foreach ( $translatedFields as $fieldName => $localeFields ) {
      $label = $fieldLabels[$fieldName];
      $multilangFieldName = $fieldName.DocumentFilter::TRANSLATED_GROUP_MARKER;
      $output[] = Group::make($label, $multilangFieldName)
        ->wrapper([ "class" => "BareFields_translatedGroup clean" ])
        ->layout($layout)
        ->fields( array_values($localeFields) );
    }
    return $output;
  }

  public static function table ( string $label, callable $generator, bool $toggle = true ) {
    $newGenerator = fn () => $generator()->wrapper(["class" => "inBlockTable"]);
    $label = str_replace("*", "<span class=\"acf-required\">*</span>", $label);
    $locales = Locales::getLocalesKeys();
    foreach ( $locales as $locale )
      $label .= "<span class=\"BareFields_locale BareFields_locale__all BareFields_locale__$locale\">$locale</span>";
    return self::one( $newGenerator, "row", $label, $toggle );
  }

	// ---------------------------------------------------------------------------

	public static function localeEnabled ( array $defaults = null, string $layout = "horizontal" ) {
		if (is_null($defaults)) {
			$defaults = [];
			foreach ( Locales::getLocalesKeys() as $locale )
				$defaults[] = $locale;
		}
		return Checkbox::make("Enabled in", "enabled")
			->choices(Locales::getLocales())
			->default($defaults)
			->layout($layout);
	}

	// TODO : Finish this locale enabled based group of fields
//	public static function localeEnabledGroups (array $fields) {
//		$groups = [];
//		foreach ( Locales::getLocales() as $locale ) {
//			$groups[] = Group::make($locale, "group_$locale")
//				->fields($fields)
//				->conditionalLogic([
//					ConditionalLogic::where("enabled", "==", $locale)
//				]);
//		}
//		return [
//			Checkbox::make("Enabled in locales", "enabled")
//				->choices(Locales::getLocales()),
//			...$groups,
//		];
//	}
}

