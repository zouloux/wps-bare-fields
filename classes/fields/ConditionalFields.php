<?php

namespace BareFields\fields;

use BareFields\multilang\TranslatedFields;
use Extended\ACF\ConditionalLogic;
use Extended\ACF\Fields\ButtonGroup;
use Extended\ACF\Fields\Email;
use Extended\ACF\Fields\File;
use Extended\ACF\Fields\Group;
use Extended\ACF\Fields\PageLink;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\URL;

class ConditionalFields
{
  public static function createSelector (
    string $label, string $key,
    array $choiceFields,
    $tabMode = true,
    $layout = "row",
  ) {
    // Convert choices to "my-choice" => "My Choice"
    $choices = [];
    // Allow keys to be like "disabled/Désactivé" to convert to ["disabled" => "Désactivé"]
    foreach ( $choiceFields as $choice => $fields ) {
      $split = explode("/", $choice, 2);
      if ( count($split) === 2 )
        $choices[ acf_slugify($split[0]) ] = $split[1];
      else
        $choices[ acf_slugify($choice) ] = $choice;
    }
    $enabledKey = "selected";
    // First is empty
    $firstIsEmpty = count(array_values($choiceFields)[0] ?? []) === 0;
    // Generate button group
    $output = [
      ButtonGroup::make( " ", $enabledKey )
        ->wrapper(["class" => "noLabel ".($firstIsEmpty ? "firstIsEmpty" : "")])
        ->choices( $choices )
    ];
    // Browse choices and map to correct field
    $c = array_keys( $choices );
    $v = array_values( $choiceFields );
    foreach ( $c as $index => $choiceSlug ) {
      // Target fields from choice index
      $fields = $v[ $index ];
      // Do not create empty groups
      if ( empty($fields) )
        continue;
      // Create group and connect it to correct choice
      $output[] = Group::make(' ', $choiceSlug)
        ->layout( $layout )
        ->wrapper(['class' => 'conditionalGroup'.($tabMode ? ' tabMode' : '')])
        ->fields( $fields )
        ->conditionalLogic([
          ConditionalLogic::where( $enabledKey, "==", $choiceSlug )
        ]);
    }
    $noLabel = $label === "" ? " noLabel" : "";
    return Group::make($label, $key)
      ->layout("row")
      ->wrapper(['class' => "conditionalGroupContainer $noLabel ".($tabMode ? ' tabMode' : '')])
      ->fields( $output );
  }



  public static function createLink (
    string $label = "Link", string $key = "link",
    array $types = ["none", "internal", "external"],
    bool $translateText = false,
    array $internalPostTypes = ["post"],
  ) {
    $fields = [];
    $linkText = fn () => Text::make("Link text", "text")->required();
    $translatedLinkText = fn () => (
      $translateText ? TranslatedFields::table("Link text *", $linkText )->column(60) : $linkText()
    );
    if ( in_array("none", $types) )
      $fields["None"] = [];
    if ( in_array('anchor', $types) )
      $fields["Anchor"] = [
        $translatedLinkText(),
        Text::make("Anchor", "anchor")
          ->prefix("#")
          ->required(),
      ];
    if ( in_array("text", $types) )
      $fields["Text"] = [ $translatedLinkText() ];
    if ( in_array("internal", $types) )
      $fields["Internal"] = [
        $translatedLinkText(),
        PageLink::make("Page", "href")
          ->disableArchives()
          ->postTypes( $internalPostTypes )
          ->postStatus(["publish"])
          ->required(),
      ];
    if ( in_array("external", $types) )
      $fields["External"] = [
        $translatedLinkText(),
        URL::make("Link", "href")
          ->placeholder("https:// ...")
          ->required(),
      ];
    if ( in_array("email", $types) )
      $fields["Email"] = [
        $translatedLinkText(),
        Email::make("Email address", "href")->required()
      ];
    if ( in_array("file", $types) )
      $fields["File"] = [
        $translatedLinkText(),
        File::make("File", "file")->required(),
        ButtonGroup::make("Behavior", "behavior")
          ->column(20)
          ->choices([
            "blank" => "New tab",
            "download" => "Download"
          ])
      ];
    return self::createSelector($label, $key, $fields, true, "table");
  }
}
