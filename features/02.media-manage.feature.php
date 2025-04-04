<?php

function bare_fields_feature_media_use_year_month_folders ( bool $value ) {
	add_filter('pre_option_uploads_use_yearmonth_folders', $value ? '__return_true' : '__return_zero');
}

function bare_fields_feature_media_enable_slugify_names () {
	add_filter('wp_handle_upload_prefilter', function ( $file ) {
		$fileParts = explode('.', $file['name']);
		foreach ( $fileParts as $key => $part )
			$fileParts[$key] = sanitize_title_with_dashes( $part );
		$file['name'] = implode('.', $fileParts);
		return $file;
	});
}

function bare_fields_feature_media_sizes ( $bigThreshold, $customSizes ) {
	// Set maximum size threshold
	add_filter( 'big_image_size_threshold', fn () => $bigThreshold );
	// Add custom image sizes
	foreach ( $customSizes as $key => $value )
		add_image_size( $key, $value[0] ?? 0, $value[1] ?? 0, $value[2] ?? false );
	// Remove all default image sizes and return only custom sizes
	add_filter(
		'intermediate_image_sizes',
		fn ( $sizes ) => array_keys($customSizes),
		9999, 2
	);
}


function bare_fields_feature_media_process_media ( $jpgQuality = 84, int|bool $webpQuality = 80, bool|array $blurHash = [8, 8], bool|array $colorThief = [6, 10] ) {
	if ( !function_exists('imagecreatetruecolor') )
		throw new Exception("bare_fields_feature_media_process_media // imagecreatetruecolor function ( gd ) is not available");
	if ( $webpQuality !== false && !function_exists('imagewebp') )
		throw new Exception("bare_fields_feature_media_process_media // imagewebp function ( gd ) is not available");
	// Set jpeg quality
	add_filter('jpeg_quality', fn () => $jpgQuality );
	if ( $blurHash !== false && !class_exists("kornrunner\Blurhash\Blurhash") )
		throw new Exception("bare_fields_feature_media_process_media // Blurhash class is not available. Please composer require kornrunner/blurhash");
	if ( $colorThief !== false && !class_exists("ColorThief\ColorThief") )
		throw new Exception("bare_fields_feature_media_process_media // ColorThief class is not available. Please composer require ksubileau/color-thief-php");
	// Filter every upload
	add_filter('wp_generate_attachment_metadata', function ( $file, $attachmentID ) use ( $jpgQuality, $webpQuality, $blurHash, $colorThief ) {
		// Continue only on images
		if ( !is_array($file) || !isset($file['sizes']) || !is_array($file['sizes']) || !isset($file['image_meta']) || !is_array($file['image_meta']) )
			return $file;
		// Compute uploaded file path and upload dir path
		$uploadDir   = wp_upload_dir();
		$uploadDirPath = rtrim($uploadDir['basedir'],'/').'/';
		$uploadDirWithDate = $uploadDirPath.pathinfo($file["file"])["dirname"]."/";
		$imagePath = $uploadDirPath.$file['file'];
		// Get image quality from settings
		$imageQuality = $webpQuality;
		// This file is not an image
		try {
			// Get image file extension and sizes
			$extension      = strtolower( pathinfo( $imagePath, PATHINFO_EXTENSION ) );
			$uploadedWidth  = $file[ 'width' ];
			$uploadedHeight = $file[ 'height' ];
			// Open uploaded image with gd if mime type is OK
			if ( $extension === 'png' ) {
				$gdImage = imagecreatefrompng( $imagePath );
			} else if ( $extension === 'jpg' || $extension === 'jpeg' ) {
				$gdImage = imagecreatefromjpeg( $imagePath );
			} else if ( $extension === 'gif' ) {
				$gdImage = imagecreatefromgif( $imagePath );
			}
			// Not an image we can convert to web or create blurhash from
			else {
				return $file;
			}
		}
		catch ( Exception $e ) {
			$encodedError = json_encode($e);
			error_log("Unable to open image $imagePath // $encodedError");
		}
		// Compress to WebP
		if ( $webpQuality !== false ) {
			try {
				$webpImages = [];
				// Browse all image sizes
				foreach ( $file['sizes'] as $key => $value ) {
					// Double check it's an image process on images
					if ( !isset($value['mime-type']) ) continue;
					// Resize
					// FIXME : Check the crop parameter ? What about 0 or -1 heights ?
					$w = $value['width'];
					$h = $value['height'];
					// Create a copy to resize image
					$imageCopy = imagecreatetruecolor($w, $h);
					// Source is likely transparent, preserve transparent channel
					if ( $extension === 'png' ) {
						imagealphablending($imageCopy, false);
						imagesavealpha($imageCopy, true);
						$transparency = imagecolorallocatealpha($imageCopy, 0, 0, 0, 127);
						imagefill($imageCopy, 0, 0, $transparency);
					}
					imagecopyresampled($imageCopy, $gdImage, 0, 0, 0, 0, $w, $h, $uploadedWidth, $uploadedHeight);
					// Convert to webp
					$fileName = pathinfo($value['file'], PATHINFO_FILENAME);
					$fullPath = $uploadDirWithDate.$fileName.'.webp';
					imagewebp( $imageCopy, $fullPath, $imageQuality );
					// Destroy copied image
					imagedestroy($imageCopy);
					// Add to meta
					$webpImages[$key] = [
						"mime_type" => "image/webp",
						"file" => "$fileName.webp",
						"width" => $w,
						"height" => $h,
					];
				}
				update_post_meta($attachmentID, "webp_sizes", json_encode($webpImages));
			}
			catch ( Exception $e ) {
				$encodedError = json_encode($e);
				error_log("Unable to compress image $imagePath to webp // $encodedError");
			}
		}
		// Create BlurHash version
		if ( $blurHash !== false ) {
			try {
				// Get resolution down a bit so blur hash is less memory intensive
				$maxWidth = $blurHash[0] * 10; // TODO : Config
				if( $uploadedWidth > $maxWidth ) {
					$gdImage = imagescale($gdImage, $maxWidth);
					$uploadedWidth = imagesx($gdImage);
					$uploadedHeight = imagesy($gdImage);
				}
				// Get all colors of image
				$pixels = [];
				for ( $y = 0; $y < $uploadedHeight; ++$y ) {
					$row = [];
					for ( $x = 0; $x < $uploadedWidth; ++$x ) {
						$i = imagecolorat($gdImage, $x, $y);
						$c = imagecolorsforindex($gdImage, $i);
						$row[] = [$c['red'], $c['green'], $c['blue']];
					}
					$pixels[] = $row;
				}
				// Compute blur hash and store it to meta
				$blurHash = array_merge(
					$blurHash,
					[ kornrunner\Blurhash\Blurhash::encode($pixels, $blurHash[0], $blurHash[1]) ]
				);
				update_post_meta( $attachmentID, 'blur_hash', json_encode($blurHash) );
			}
			// Silently fail into php logs
			catch ( Exception $e ) {
				$encodedError = json_encode($e);
				error_log("Unable to generate Blurhash for image $imagePath // $encodedError");
			}
		}
		// Grab color palette
		if ( $colorThief !== false ) {
			$mainColor = null;
			$palette = null;
			try {
				$mainColor = ColorThief\ColorThief::getColor($gdImage, $colorThief[1] ?? 10, null, 'hex' );
			}
			catch ( Exception $e ) {}
			try {
				$palette = ColorThief\ColorThief::getPalette($gdImage, $colorThief[0] ?? 6, $colorThief[1] ?? 10, null, 'hex' );
			}
			catch ( Exception $e ) {}
			update_post_meta($attachmentID, "palette", json_encode([
				"main" => $mainColor ?? '',
				"palette" => $palette ?? [],
			]));
		}
		// Destroy pending source gd image
		if ( isset($gdImage) && function_exists('imagedestroy' ))
			imagedestroy( $gdImage );
		return $file;
	}, 10, 2);
	// Delete generated webp files when deleting attachment
	add_action('delete_attachment', function($postId) {
		$metadata = wp_get_attachment_metadata($postId);
		if ( empty($metadata['file']) )
			return;
		$path = pathinfo($metadata['file']);
		$uploadDir = wp_upload_dir();
		$uploadsDirPath = trailingslashit($uploadDir['basedir']) . $path['dirname'];
		$webpImages = get_post_meta($postId, 'webp_sizes', true);
		if ( !$webpImages )
			return;
		$webpImages = json_decode($webpImages, true);
		foreach ( $webpImages as $file ) {
			$webpFilePath = $uploadsDirPath . '/' . $file['file'];
			if ( file_exists($webpFilePath) )
				unlink($webpFilePath);
		}
	});
}
