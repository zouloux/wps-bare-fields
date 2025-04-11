<?php

namespace BareFields\fields;

use Extended\ACF\Fields\ButtonGroup;
use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\PageLink;
use Extended\ACF\Fields\WYSIWYGEditor;

class BasicFields
{
  public static function getMime ( string $type, $others = [] ) {
    if ( $type === "image" )
      return ["jpg", "png", "jpeg", ...$others];
    if ( $type === "svg" )
      return ["svg"];
    else if ( $type === "video" )
      return ["mp4", "webm", ...$others];
    else if ( $type === "document" )
      return ["pdf", ...$others];
    else
      throw new \Exception("BasicFields::getMime // Invalid type $type");
  }

  public static function createEnabled ( $title = "Enabled", $default = "enabled", $choices = [ "Disabled", "Enabled" ], $key = "enabled", bool $styled = true ) {
    return ButtonGroup::make($title, $key)
      ->choices( $choices )->default( $default )
      ->wrapper($styled ? ["class" => "BareFields__enabledField"] : []);
  }

  public static function createEnabledCompact ( $title = "Enabled", $default = "1", $choices = [ "0", "1" ], $key = "enabled", bool $styled = true ) {
    return ButtonGroup::make($title, $key)
      ->choices( $choices )->default( $default )
      ->wrapper($styled ? ["class" => "BareFields__enabledField"] : []);
  }

  // todo : with recursive filter that converts to a real boolean
  public static function createBoolean ( string $title, string $key, bool $checked = false ) {
    return ButtonGroup::make($title, $key)
      ->default( $checked ? "true" : "false" )
      ->choices([
        "false" => "No",
        "true" => "Yes",
      ]);
  }

  public static function createImage ( $label = "Image", $key = "image", $imageSizeClass = "smallImage" ) {
    return Image::make($label, $key)
      ->wrapper(['class' => $imageSizeClass]);
  }

  public static function createEditor ( $label = "Content", $key = "content", $allowMedia = false, $class = 'clean' ) {
    $editor = WYSIWYGEditor::make( $label, $key )
      ->tabs('visual')
      ->wrapper(['class' => $class]);
    if ( !$allowMedia )
      $editor->disableMediaUpload();
    return $editor;
  }

  /**
   * @deprecated Use ConditionalFields::createLink instead
   *
   * @param $title
   * @param $key
   * @param $postTypes
   * @param $allowArchives
   *
   * @return PageLink
   */
  public static function createPageLink ( $title = "Link to page", $key = 'link', $postTypes = ['page'], $allowArchives = false ) {
    $field = PageLink::make( $title, $key )
      ->nullable()
      ->required()
      ->postTypes( $postTypes );
    if ( !$allowArchives )
      $field->disableArchives();
    return $field;
  }

}
