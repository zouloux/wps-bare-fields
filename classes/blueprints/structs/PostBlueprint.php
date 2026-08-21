<?php

namespace BareFields\blueprints\structs;

use BareFields\blueprints\abstract\AbstractBlueprint;
use BareFields\blueprints\abstract\BlueprintEditor;
use BareFields\blueprints\abstract\BlueprintOrderable;

class PostBlueprint extends AbstractBlueprint
{
  // --------------------------------------------------------------------------- TRAITS

  use BlueprintOrderable;
  use BlueprintEditor;

  // --------------------------------------------------------------------------- CONSTRUCT

  public static function create () : static {
    return new static();
  }

  /**
   * Create fields blueprints for all posts ( not pages, not custom post types )
   */
  public function __construct () {
    parent::__construct( "post", "" );
  }

  // --------------------------------------------------------------------------- LIST COLUMN

  public function listColumn ( string $columnTitle, string $width, callable $handler ) {
    $columnSlug = acf_slugify($columnTitle);
    add_filter("admin_head", function () use ($columnSlug, $width) {
      echo "<style>.column-".$columnSlug."{ width: $width }</style>";
    });
    add_filter("manage_edit-post_columns", function ( $columns ) use ($columnSlug, $columnTitle) {
      $columns[$columnSlug] = $columnTitle;
      return $columns;
    });
    add_action("manage_post_posts_custom_column", function ( $columnName, $postID ) use ($columnSlug, $handler) {
      if ( $columnName === $columnSlug ) {
        $return = $handler($postID);
        if ( is_string($return) )
          echo $return;
      }
    }, 10, 2);
  }

}
