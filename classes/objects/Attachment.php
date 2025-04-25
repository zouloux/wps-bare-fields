<?php

namespace BareFields\objects;

use BareFields\helpers\WPSHelper;
use JsonSerializable;

class Attachment implements JsonSerializable {

	protected static function mimeTypeToSimpleType ( string $mimeType ) {
		$mimes = [
			// image
			"image/jpeg" => "jpg",
			"image/jpg" => "jpg",
			"image/gif" => "gif",
			"image/png" => "png",
			"image/webp" => "webp",
			"image/svg+xml" => "svg",
			// video
			"video/mp4" => "mp4",
			"video/mov" => "mov",
			// other
			"application/zip" => "zip"
		];
		return $mimes[ $mimeType ] ?? $mimeType;
	}

	protected array $_source = [];
	public function getSource ():array { return $this->_source; }

	public int $id;
	public string $type;
	public string $href;
	public string $fileName;
	public int $fileSize;
	public string $title;
	public string $alt;
	public string $description;
	public string $caption;


	public function __construct ( array $source ) {
		$this->_source = $source;
		$this->id = $source['ID'];
		$this->type = $source['type'];
		$this->href = $source['url'];
		// Remove base from href
		if ( !empty($this->href) && defined('WP_HOME') ) {
      /** @noinspection PhpUndefinedConstantInspection */
      $this->href = WPSHelper::removeBaseFromHref( $this->href, WP_HOME );
    }
		$this->fileName = $source['filename'];
		$this->fileSize = $source['filesize'];
    //
		$this->title = $source['title'] ?? "";
		$this->alt = $source['alt'] ?? "";
		$this->description = $source['description'] ?? "";
		$this->caption = $source['caption'] ?? "";
	}

	public function jsonSerialize ():array {
		return [
			'id' => $this->id,
			'type' => $this->type,
			'href' => $this->href,
			'fileName' => $this->fileName,
			'fileSize' => $this->fileSize,
			// NOTE : use DocumentFilter::registerObjectSerializer to include more
//			'title' => $this->title,
//			'alt' => $this->alt,
//			'description' => $this->description,
//			'caption' => $this->caption,
		];
	}
}
