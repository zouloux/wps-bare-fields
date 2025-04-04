<?php

namespace BareFields\blueprints\structs;

use BareFields\blueprints\abstract\AbstractBlueprint;
use BareFields\blueprints\abstract\BlueprintEditor;
use BareFields\blueprints\abstract\BlueprintOrderable;

class PageBlueprint extends AbstractBlueprint
{
  // --------------------------------------------------------------------------- TRAITS

  use BlueprintEditor;
  use BlueprintOrderable;

  // --------------------------------------------------------------------------- CONSTRUCT

  public static function create ( string $name = "", string $niceTemplateName = "" ) : static {
    return new static( $name, $niceTemplateName );
  }

  /**
   * Create fields blueprint for a specific page template or for all pages
   * @param string $name Page template name if defined, all pages if empty
   * @param string $niceTemplateName Nice template name in admin dropdown
   * @throws \Exception
   */
  public function __construct ( string $name = "", string $niceTemplateName = "" ) {
    parent::__construct( "page", $name );
    if ( empty($name) && !empty( $niceTemplateName ) )
      throw new \Exception("Template name not defined");
    $this->_niceTemplateName = $niceTemplateName;
  }

  // --------------------------------------------------------------------------- TEMPLATE NAME

  protected string $_niceTemplateName = "";
  public function getNiceTemplateName(): string { return $this->_niceTemplateName; }

}
