<?php

namespace BareFields\admin;

class AdminMenu {

//  public static function create ( string $position ) : static {
//    return new static( $position );
//  }

  public static function createSingleton () {
    return new static("singleton");
  }
  public static function createCollection () {
    return new static("collection");
  }

  protected string $_position;
  public function __construct (string $position ) {

  }

  public static function setMenu () {

//    add_action('admin_menu', function () {
//      global $menu;
//      dd($menu);
//    }, 99, 3);
  }


  public function addMenu ( string $position ) {
    add_action('admin_menu', function () use ( $title, $icon, $slug, $callable, $position, $capability ) {
      add_menu_page( $title, $title, $capability, $slug, $callable, $icon, $position );
    });
    add_action('admin_menu', function () use ($slug) {
      global $submenu;
      unset( $submenu[$slug][0] );
    }, 999);
  }
}
