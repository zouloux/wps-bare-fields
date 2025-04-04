<?php

namespace BareFields\objects;

use BareFields\helpers\WPSHelper;
use JsonSerializable;
use WP_Term;

class Term implements JsonSerializable
{
  public static bool $SERIALIZE_HREF = false;

	protected WP_Term $_source;
	public function getSource ():WP_Term { return $this->_source; }

	public int $id;
	public string $name;
	public string $slug;
	public string $href;
	public array $children = [];
	public int $parentID;

	public function __construct ( WP_Term $source ) {
		$this->_source = $source;
		$this->id = $source->term_id;
		$this->name = $source->name;
		$this->slug = $source->slug;
		$this->href = WPSHelper::removeBaseFromHref( get_category_link( $source ), WPSHelper::getBase() );
		$this->parentID = $source->parent;
	}

	public function jsonSerialize ():array {
		$json = [
			"id"    => $this->id,
			"name"  => $this->name,
			"slug"  => $this->slug,
		];
    if ( self::$SERIALIZE_HREF )
      $json["href"] = $this->href;
    if ( $this->parentID )
      $json["parent"] = $this->parentID;
    return $json;
	}
}
