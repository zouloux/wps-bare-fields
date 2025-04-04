<?php

namespace BareFields\blueprints\abstract;

trait BlueprintMenu
{
  // --------------------------------------------------------------------------- MENU

  protected string $_menuTitle;
  public function getMenuTitle () : string { return $this->_menuTitle; }

  protected string $_menuLabel;
  public function getMenuLabel () : string { return $this->_menuLabel; }

  protected string $_menuIcon = "";
  public function getMenuIcon () : string { return $this->_menuIcon; }

  protected int|null $_menuPosition = null;
  public function getMenuPosition () : int|null { return $this->_menuPosition; }

  // https://developer.wordpress.org/resource/dashicons/
  public function menu ( string $title, string $label = null, string $icon = "", int|null $position = null ) : static {
    $this->_menuTitle = $title;
    $this->_menuLabel = $label ?? $title;
    $this->_menuIcon   = $icon;
    if ( !is_null($position) )
      $this->_menuPosition = $position;
    return $this;
  }

  // --------------------------------------------------------------------------- PARENT MENU

  protected string $_parentMenu = "";
  public function getParentMenu () { return $this->_parentMenu; }
  public function parentMenu ( string $parentMenuId ) : static {
    $this->_parentMenu = $parentMenuId;
    return $this;
  }
}
