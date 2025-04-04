<?php

namespace BareFields\fields;

use BareFields\blueprints\RootGroup;
use BareFields\multilang\TranslatedFields;
use Extended\ACF\Fields\Repeater;
use Extended\ACF\Fields\Text;

class DictionaryFields
{

  public static function createDictionaryField ( string $label, string $name, string $button, bool $translated ) {
    return Repeater::make($label, $name)
      ->button( $button )
      ->layout('table')
      ->wrapper(["class" => $label === "" ? "noLabel" : ""])
      ->fields([
        Text::make('Key', 'key')->required()->column(35),
        TranslatedFields::table(
          "Value",
          fn () => Text::make('Value', 'value')->required(),
          $translated
        )
      ]);
  }

  public static function createDictionariesField ( string $label = "Dictionaries", string $key = "dictionaries" ) {
    return Repeater::make($label, $key)
			->button("Add dictionary")
			->layout('row')
      ->wrapper(["class" => $label === "" ? "noLabel" : ""])
			->fields([
				Text::make("Dictionary identifier", 'id'),
				AdminFields::createAccordion("Translations"),
        self::createDictionaryField("", "data", "Add translation", true)
          ->wrapper(['class' => 'clean'])
			]);
  }

  public static function createTranslationDictionariesGroup ( string $label = "Dictionaries", string $name = "dictionaries" ) {
    return RootGroup::create( $label, $name )
      ->multiLang()
      ->filter(function ($data) {
        $output = [];
        $dictionaries = is_array($data["dictionaries"]) ? $data["dictionaries"] : [];
        foreach ( $dictionaries as $dictionary ) {
          $localOutput = [];
          foreach ( $dictionary["data"] as $value )
            $localOutput[$value["key"]] = $value["value"];
          $output[$dictionary["id"]] = $localOutput;
        }
        return $output;
      })
      ->fields([
        self::createDictionariesField("", $name)
      ]);
  }

  public static function createKeysDictionaryGroup ( string $label = "Keys", string $name = "keys", string $button = "Add key" ) {
    return RootGroup::create( $label, $name )
      ->filter(function ($data) {
        $keys = is_array($data["keys"]) ? $data["keys"] : [];
        $output = [];
        foreach ( $keys as $value )
          $output[$value["key"]] = $value["value"];
        return $output;
      })
      ->fields([
        self::createDictionaryField("", $name, $button, false)
      ]);
  }
}
