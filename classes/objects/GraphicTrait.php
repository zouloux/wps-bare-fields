<?php

namespace BareFields\objects;

trait GraphicTrait
{
	public int $width;
	public int $height;
	public float $ratio = 1;

	protected function initGraphicTrait ( array $source ) {
		// Width / height / ratio
		$this->width = $source["width"];
		$this->height = $source["height"];
		if ( $this->width > 0 && $this->height > 0 )
			$this->ratio = ($this->width / $this->height);
	}
}
