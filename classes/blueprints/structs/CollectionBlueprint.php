<?php

namespace BareFields\blueprints\structs;

use BareFields\blueprints\abstract\AbstractBlueprint;
use BareFields\blueprints\abstract\BlueprintMenu;
use BareFields\blueprints\abstract\BlueprintOptions;
use BareFields\blueprints\abstract\BlueprintOrderable;

class CollectionBlueprint extends AbstractBlueprint
{
  // --------------------------------------------------------------------------- TRAITS

  use BlueprintMenu;
  use BlueprintOptions;
  use BlueprintOrderable;

  // --------------------------------------------------------------------------- CONSTRUCT

  public static function create ( string $name ) : static {
    return new static( $name );
  }

  public function __construct ( string $name ) {
    parent::__construct( "collection", $name );
    $this->_menuLabel   = $name;
    $this->_menuTitle   = $name;
    $this->_menuPosition = 6;
  }

  // --------------------------------------------------------------------------- SHOW IN PAGES

  protected bool $_showInPages = true;
  public function getShowInPages () : bool { return $this->_showInPages; }

  /**
   * CustomPostType will have a href if true and will be publicly available
   * @param bool $value
   * @return $this
   */
  public function showInPages ( bool $value = true ) : CollectionBlueprint {
    $this->_showInPages = $value;
    return $this;
  }

  // --------------------------------------------------------------------------- SHOW IN REST

  protected bool $_showInRest = true;
  public function getShowInRest () : bool { return $this->_showInRest; }

  public function showInRest ( bool $value = true ) : CollectionBlueprint {
    $this->_showInRest = $value;
    return $this;
  }

  // --------------------------------------------------------------------------- SHOW IN ADMIN UI

  protected bool $_showInAdminUI = true;
  public function getShowInAdminUI () : bool { return $this->_showInAdminUI; }

  public function showInAdminUI ( bool $value = true ) : CollectionBlueprint {
    $this->_showInAdminUI = $value;
    return $this;
  }

  // --------------------------------------------------------------------------- SLUG

  protected string $_slug = "";
  public function getSlug () : string { return $this->_slug; }

  public function slug ( string $slug ) : CollectionBlueprint {
    $this->_slug = $slug;
    return $this;
  }

  // --------------------------------------------------------------------------- LIST COLUMN

  public function listColumn ( string $columnTitle, string $width, callable $handler ) {
    $name = $this->name;
    $columnSlug = acf_slugify($columnTitle);
    add_filter("admin_head", function () use ($columnSlug, $width) {
      echo "<style>.column-".$columnSlug."{ width: $width }<style>";
    });
    add_filter("manage_edit-{$name}_columns", function ( $columns ) use ($columnSlug, $columnTitle) {
      $columns[$columnSlug] = $columnTitle;
      return $columns;
    });
    add_action("manage_{$name}_posts_custom_column", function ( $columnName, $postID ) use ( $columnSlug, $handler ) {
      if ( $columnName === $columnSlug ) {
        $return = $handler($postID);
        if ( is_string($return) )
          echo $return;
      }
    }, 10, 2);
  }

}
