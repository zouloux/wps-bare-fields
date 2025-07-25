<?php
/**
 * Plugin Name:       WPS Bare-fields
 * Plugin URI:        https://github.com/zouloux/wps-bare-fields
 * GitHub Plugin URI: https://github.com/zouloux/wps-bare-fields
 * Description:       ACF Pro with blueprints
 * Author:            Alexis Bouhet
 * Author URI:        https://zouloux.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       Bare Fields
 * Domain Path:       /cms
 * Version:           0.7.6
 * Copyright:         © 2024 Alexis Bouhet
 */


// No overload when blog is not installed yet
if ( !is_blog_installed() ) return;

// Register plugin dir
define('WPS_BARE_FIELDS_PLUGIN_DIR', plugin_dir_url(__FILE__));

require __DIR__.'/vendor/autoload.php';

use BareFields\blueprints\BlueprintsManager;
use BareFields\helpers\AdminHelper;

add_action('init', function () {
	BlueprintsManager::install();
});

// todo : in bare_fields_feature_admin_inject_style_override ?
add_action("admin_head", function () {
  AdminHelper::injectStyleFile(WPS_BARE_FIELDS_PLUGIN_DIR.'assets/fields.css');
});

// Wait for all plugins to be loaded before loading bare field app
add_action('plugins_loaded', function () {
  if ( defined("WPS_BARE_FIELDS_NANO_APP_PATH") )
    Nano\core\Loader::loadFunctions( WPS_BARE_FIELDS_NANO_APP_PATH );
}, 999);

// Include required files that manage internal features
require_once __DIR__."/inc/multilang.php";
require_once __DIR__."/inc/orderable.php";

// Include feature flag files
require_once __DIR__."/features/00.global.feature.php";
require_once __DIR__."/features/01.admin.feature.php";
require_once __DIR__."/features/02.media-manage.feature.php";
require_once __DIR__."/features/03.cache.feature.php";

