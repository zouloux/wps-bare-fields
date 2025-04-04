<?php
/**
 * Plugin Name:       Woolkit
 * Plugin URI:        https://github.com/zouloux/woolkit
 * GitHub Plugin URI: https://github.com/zouloux/woolkit
 * Description:       W-ordpress T-oolkit
 * Author:            ZoulouX
 * Author URI: 		  https://zouloux.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       Woolkit
 * Domain Path:       /cms
 * Version:           1.0.0
 * Copyright:         © 2024 Alexis Bouhet
 */

// No overload when blog is not installed yet
use Nano\core\App;
use Nano\core\Loader;

if ( !is_blog_installed() ) return;

// Register Woolkit plugin dir for other components
define('WOOLKIT_PLUGIN_DIR', plugin_dir_url(__FILE__));

// Load woolkit
Loader::loadFunctions( __DIR__.'/functions' );
Loader::loadFunctions( __DIR__.'/modules' );

function woolkit_init ( string $locale = "" ) {
	// Get global local and init before installing acf fields
	// In admin, $locale is defined automatically by the wpm plugin
	// NOTE : This is the only way to have post fields translated correctly ...
	if ( !empty($locale) )
		woolkit_locale_set( $locale );
	// Install registered fields from theme
	WoolkitFields::install();
}

// Wait for all plugins to be loaded before starting code injection
add_action('plugins_loaded', function () {
	// Load Wordpress application
	Loader::loadFunctions( App::$rootPath.'/app/wordpress' );
}, 999);

add_action('init', function () {
	if ( is_admin() )
		woolkit_init();
});