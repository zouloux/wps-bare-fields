<?php

namespace BareFields\objects;

use JsonSerializable;

class ImageFormat implements JsonSerializable
{
	use GraphicTrait;

	public string $href;
	public string $name;

	/**
	 * @var string "jpg" / "png" / "gif" / "webp"
	 */
	public string $type;

	public function __construct ( array $source ) {
		foreach ( $source as $key => $value )
			$this->$key = $value;
		$this->initGraphicTrait( $source );
	}

	public function jsonSerialize ():array {
		return [
			'href' => $this->href,
			'type' => $this->type,
			'width' => $this->width,
			'height' => $this->height,
			// NOTE : use DocumentFilter::registerObjectSerializer to include more
		];
	}
}
