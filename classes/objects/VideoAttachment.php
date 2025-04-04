<?php

namespace BareFields\objects;

class VideoAttachment extends Attachment {
  use GraphicTrait;

	public function __construct ( array $source ) {
		// Relay to WoolkitAttachment and init GraphicTrait
		parent::__construct($source);
		$this->initGraphicTrait( $source );
	}

	public function jsonSerialize ():array {
		// todo : add statics to configure this
		return [
			'href' => $this->href,
			'width' => $this->width,
			'height' => $this->height,
		];
	}
}
