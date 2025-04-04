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

}
