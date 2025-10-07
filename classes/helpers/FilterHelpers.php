<?php

namespace BareFields\helpers;

class FilterHelpers
{
	/**
	 * Ex : $data["sub"]["sub"] to $data["sub]
	 * @param array $data Data (as reference) to collapse from
	 * @param string $key Key of the doubled array
	 * @param mixed|null $default Default value if not found
	 * @return void
	 */
	public static function collapseDoubledArrays ( array &$data, string $key, mixed $default = null ) {
		$data[$key] = $data[$key][$key] ?? $default;
	}

	/**
	 * Convert indexed arrays with an id field to an associative array
	 * Ex : [
	 * 		["id" => "123", "name" => "First"],
	 * 		["id" => "abc", "name" => "Second"]
	 * ]
	 * will output
	 * [
	 * 	"123" => ["name" => "First"],
	 *  "abc" => ["name" => "Second"]
	 * ]
	 * Can also collapse the value array to 1 field with $keyName
	 * [
	 *   "123" => "First",
	 *   "abc" => "Second"
	 * ]
	 * @param array $array
	 * @param string|null $keyName
	 * @return array
	 */
	public static function idToParent ( array &$array, string $keyName = null ) {
		$output = [];
		foreach ( $array as $item ) {
			$id = $item["id"];
			unset($item["id"]);
			if ( is_null($keyName) )
				$output[$id] = $item;
			else
				$output[$id] = $item[$keyName];
		}
		return $output;
	}

	/**
	 * Value cannot be false.
	 * @param mixed $input
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public static function neverFalse ( mixed $input, mixed $defaultValue ) {
		if ( $input === false )
			return $defaultValue;
		else
			return $input;
	}

	/**
	 * Clean prop from an array.
	 * - Rename prop (ex from "generic_flexible" to "flexible")
	 * - Uses FilterHelper::neverFalse to normalize default value
	 * - Mutates &array refs
	 * @param array $array Mutated array as ref
	 * @param string $fromKey Key to grab data from
	 * @param string $toKey Key to put normalized data to
	 * @param mixed|null $defaultValue Default value if false / null
	 * @return void
	 */
	public static function cleanKey ( array &$array, string $fromKey, string $toKey, mixed $defaultValue = null ) {
		$value = self::neverFalse($array[$fromKey] ?? $defaultValue, $defaultValue);
		unset($array[$fromKey]);
		$array[$toKey] = $value;
	}
}
