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
}
