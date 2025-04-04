<?php

namespace BareFields\blueprints\abstract;

trait BlueprintOrderable
{

  // --------------------------------------------------------------------------- ORDERABLE

  protected bool $_orderable = false;
  public function getOrderable () : bool { return $this->_orderable; }

  public function orderable ( bool $value = true ) : static {
    $this->_orderable = $value;
    return $this;
  }

}
