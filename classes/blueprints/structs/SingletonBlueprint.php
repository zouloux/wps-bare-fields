<?php

namespace BareFields\blueprints\structs;

use BareFields\blueprints\abstract\AbstractBlueprint;
use BareFields\blueprints\abstract\BlueprintMenu;
use BareFields\blueprints\abstract\BlueprintOptions;

class SingletonBlueprint extends AbstractBlueprint
{
  // --------------------------------------------------------------------------- TRAITS

  use BlueprintMenu;
  use BlueprintOptions;

  // --------------------------------------------------------------------------- CONSTRUCT

  public static function create ( string $name ) : static {
    return new static( $name );
  }

  public function __construct ( string $name ) {
    parent::__construct( "singleton", $name );
    $this->_menuLabel   = $name;
    $this->_menuTitle   = $name;
    $this->_menuPosition = 1;
    $this->_multilangForceAllLocales = true;
  }

}
