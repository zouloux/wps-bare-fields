<?php

namespace BareFields\objects;

use BareFields\helpers\WPSHelper;
use Exception;

class ImageAttachment extends Attachment {

	protected static function mimeTypeToSimpleType ( string $mimeType ) {
		$mimes = [
			"image/jpeg" => "jpg",
			"image/jpg" => "jpg",
			"image/gif" => "gif",
			"image/png" => "png",
			"image/webp" => "webp",
			"image/svg+xml" => "svg",
		];
		return $mimes[ $mimeType ] ?? "unknown";
	}

	/** @var ImageFormat[] $formats */
	public array $formats = [];

	public array $blurhash = [];

	public string $colorMain = "";

	public array $colorPalette = [];

	public string $href;

	public string $type;

	use GraphicTrait;

	public function __construct ( array $source ) {
		// Relay to WoolkitAttachment and init GraphicTrait
		parent::__construct($source);
		$this->initGraphicTrait( $source );
		// Get blur hash
		$blurHash = get_post_meta($this->id, "blur_hash", true);
		if ( is_string($blurHash) ) {
			try {
				$blurHash = json_decode($blurHash, true);
			}
			catch ( Exception $e ) {}
			if ( is_array($blurHash) )
				$this->blurhash = $blurHash;
		}
		// Get palette
		$palette = get_post_meta($this->id, "palette", true);
		if ( is_string($palette) ) {
			try {
				$palette = json_decode($palette, true);
			}
			catch ( Exception $e ) {}
			if ( is_array($palette) ) {
				$this->colorMain = $palette['main'];
				$this->colorPalette = $palette['palette'];
			}
		}
		$this->type = self::mimeTypeToSimpleType( $source['mime_type'] );
//		dump($source['mime_type']);
//		dump($this->type);
		// Do not treat svg like rasterized images
		if ( $source['subtype'] === 'svg+xml' )
			return;
		// NOTE : From now, only for raster images
		// Native formats (same as original mime types but resized)
//		dd($source);
		foreach ( $source['sizes'] as $key => $size ) {
			if ( isset($source['sizes'][$key.'-width']) && isset($source['sizes'][$key.'-height']) ) {
				$href = $size;
				if ( !empty($href) && defined('WP_HOME') ) {
          /** @noinspection PhpUndefinedConstantInspection */
          $href = WPSHelper::removeBaseFromHref( $href, WP_HOME );
        }
				if ( $href === $this->href )
					continue;
				$this->formats[] = new ImageFormat([
					'name' => $key,
					'href' => $href,
					'width' => $source['sizes'][$key.'-width'],
					'height' => $source['sizes'][$key.'-height'],
					'type' => self::mimeTypeToSimpleType( $source['mime_type'] ),
				]);
			}
		}
		// Converted to other formats
		$webpSizes = get_post_meta($this->id, "webp_sizes", true);
		if ( is_string($webpSizes) ) {
			try {
				$webpSizes = json_decode($webpSizes, true);
			}
			catch ( Exception $e ) {}
			if ( is_array($webpSizes) ) {
				$folderPath = dirname($this->href);
				foreach ( $webpSizes as $key => $size ) {
					$this->formats[] = new ImageFormat([
						'name' => $key,
						// Href already without base
						// fixme : use WoolkitAttachment::processRelativeHref and add base if needed
						'href' => $folderPath.'/'.$size['file'],
						'width' => $size['width'],
						'height' => $size['height'],
						'type' => self::mimeTypeToSimpleType( $size['mime_type'] ),
					]);
				}
			}
		}
	}

	public function jsonSerialize ():array {
		// todo : add statics to configure this
		$json = [
//			'id' => $this->id,
			//'name' => $this->name,
			'href' => $this->href,
			'alt' => $this->alt,
			'type' => $this->type,
		];
		if ( !empty($this->formats) )
//			$json['formats'] = array_map( fn ($format) => $format->jsonSerialize(), $this->formats );
			$json['formats'] = $this->formats;
		if ( !empty($this->blurhash) )
			$json['blurhash'] = $this->blurhash;
		if ( !empty($this->colorMain) )
			$json['colorMain'] = $this->colorMain;
		if ( !empty($this->colorPalette) )
			$json['colorPalette'] = $this->colorPalette;
		if ( !empty($this->ratio) )
			$json['ratio'] = $this->ratio;
		return $json;
	}
}
