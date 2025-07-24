<?php

namespace BareFields\blueprints\abstract;

trait BlueprintSubPaths {

  // --------------------------------------------------------------------------- SUB PATHS

  protected bool $_hasSubPaths = false;
  public function getHasSubPaths () : bool { return $this->_hasSubPaths; }

  public function hasSubPaths ( bool $value = true ) : static {
    $this->_hasSubPaths = $value;
    return $this;
  }

}
