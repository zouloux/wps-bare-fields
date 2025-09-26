<?php

namespace BareFields\helpers;

use Extended\ACF\Fields\Field;
use ReflectionClass;
use ReflectionProperty;

class ACFFieldsPatcher extends Field
{
	protected static ReflectionProperty $__accessibleSettingsProperty;

	public static function patchSettingsAccessibility (): ReflectionProperty {
		if ( !isset(self::$__accessibleSettingsProperty) ) {
			$instance = new static("_a", "_b");
			$reflection = new ReflectionClass($instance);
			$property = $reflection->getProperty("settings");
			$property->setAccessible(true);
			self::$__accessibleSettingsProperty = $property;
		}
		return self::$__accessibleSettingsProperty;
	}
}
