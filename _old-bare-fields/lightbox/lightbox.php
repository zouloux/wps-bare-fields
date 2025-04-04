<?php

// todo : add methods for lightbox plugin

/**
 * Load lightbox into Wordpress admin.
 * Usage :
 * - Call this function in any Wordpress admin page handler, before showing data.
 * - In html, create a gallery like this ( add as many as needed ) :
 * <a href="{imageHref}" data-lightbox="{galleryID}" target="_blank">
 *     <img src="{imageHref}" />
 * </a>
 * - After all gallery code, call the Closure returned by the load function.
 * @param array $options Lightbox 2 options https://lokeshdhakar.com/projects/lightbox2/#options
 * @return Closure Call to enable lightbox after your content
 */
function woolkit_module_lightbox_load ( array $options = [] ) {
	$options = [
		"resizeDuration" => 200,
        "fadeDuration" => 200,
        "imageFadeDuration" => 200,
        "wrapAround" => true,
		...$options,
	];
	?>
	<script type="text/javascript" src="<?= WOOLKIT_PLUGIN_DIR."plugins/lightbox/assets/lightbox.min.js" ?>"></script>
	<link rel="stylesheet" href="<?= WOOLKIT_PLUGIN_DIR."plugins/lightbox/assets/lightbox.min.css" ?>"></link>
	<?
	return function () use ( $options ) {
		$jsonOptions = json_encode( $options, JSON_NUMERIC_CHECK );
		echo "<script>lightbox.option($jsonOptions)</script>";
	};
}
