<?php

namespace BareFields\blueprints\abstract;

use BareFields\blueprints\RootGroup;

abstract class AbstractBlueprint {

  public string $id = "";

	public array $location = [];

  public readonly string $type;
  public readonly string $name;

  public function __construct ( string $type, string $name ) {
    $this->type = $type;
    $this->name = $name;
  }

	// --------------------------------------------------------------------------- MULTILANG

	protected bool $_multilang = false;
  public function getMultilang () : bool { return $this->_multilang; }

	public function multilang ( bool $multilang = true ) : static {
		$this->_multilang = $multilang;
		return $this;
	}


  protected bool $_multilangTitle = false;
  public function getMultilangTitle () : bool { return $this->_multilangTitle; }

  public function multilangTitle (bool $multilangTitle = true) : static {
		$this->_multilangTitle = $multilangTitle;
    return $this;
  }


  protected bool $_multilangForceAllLocales = false;
  public function getMultilangForceAllLocales () : bool { return $this->_multilangForceAllLocales; }

  public function multilangForceAllLocales ( bool $multilangForceAllLocales = true ) : static {
		$this->_multilangForceAllLocales = $multilangForceAllLocales;
    return $this;
  }


	// --------------------------------------------------------------------------- FILTERING DATA

	protected array $_requestFilterHandlers = [];
  public function getRequestFilterHandlers () : array { return $this->_requestFilterHandlers; }

	public function addRequestFilter ( callable $filterHandler ) : static {
		$this->_requestFilterHandlers[] = $filterHandler;
		return $this;
	}

	// --------------------------------------------------------------------------- GROUPS

	public array $_groups = [];
  /** @return RootGroup[] */
  public function getGroups () : array { return $this->_groups; }

	public function createGroup ( string $label, string $name ) : RootGroup {
    return $this->attachGroup( RootGroup::create($label, $name) );
	}

	public function attachGroup ( RootGroup $group ) : RootGroup {
		$this->_groups[] = $group;
		return $group;
	}

  // --------------------------------------------------------------------------- LOAD FIELD HOOK

  protected int $_fieldFilterLoopCounter = 0;

  public function addFieldFilter ( string $fieldName, callable $handler ) : static {
    // TODO : Scope it in this Blueprint, filter is too wide and can collide
    add_filter("acf/load_field/name=$fieldName", function ($field) use ($handler) {
      return $handler( $field, $this->_fieldFilterLoopCounter++ );
    });
    return $this;
  }

}
