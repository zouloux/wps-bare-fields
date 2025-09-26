<?php

namespace BareFields\fields;

use BareFields\helpers\ACFFieldsPatcher;
use BareFields\requests\DocumentFilter;
use Nano\core\Env;

class EnvFields {

	static function getEnvName () {
		return Env::get("DOCKER_BRANCH", "dev");
	}

	public static function env ( callable $generator ) {
		$settingsProperty = ACFFieldsPatcher::patchSettingsAccessibility();
		$envName = self::getEnvName();
		$envField = $generator( $envName );
		$settings = $settingsProperty->getValue($envField);
    $settingsProperty->setValue($envField, [
      ...$settings,
      "label" => $settings["label"].'<span class="BareFields_envField">'.ucfirst($envName).' env</span>',
      "name" => $settings["name"]."__".$envName.DocumentFilter::ENV_FIELD_MARKER,
    ]);
		return $envField;
	}
}
