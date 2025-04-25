<?php

namespace BareFields\objects;

class VideoAttachment extends Attachment {
  use GraphicTrait;

	public string $type;

	public function __construct ( array $source ) {
		parent::__construct($source);
		$this->type = self::mimeTypeToSimpleType( $source['mime_type'] );
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
