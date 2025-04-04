<?php

/**
 * Collapse a node into its parent.
 *
 * Useful when field data are like :
 * $data = [
 *   "dictionary" => [
 *     "dictionaries" => [
 *        "..." => "..."
 *     ]
 *   ]
 * ]
 *
 * Usage :
 * woolkit_collapse_node( $data["dictionary"], "dictionaries" );
 *
 * $data is mutated to :
 * [
 *    "dictionaries" => [
 *       "..." => "..."
 *    ]
 * ]
 *
 *
 * @param mixed $parent Parent node to mutate.
 * @param string $fieldName Key of node to collapse into parent node.
 * @return void
 */
function woolkit_collapse_node ( mixed &$parent, string $fieldName ) {
	if ( is_array($parent) && isset($parent[ $fieldName ]) )
		$parent = $parent[ $fieldName ];
}

function woolkit_collapse_same ( mixed &$parent, string $fieldName ) {
	if ( is_array($parent) && isset($parent[ $fieldName ][ $fieldName ]) && is_array($parent[ $fieldName ][ $fieldName ] ) )
		$parent[ $fieldName ] = $parent[ $fieldName ][ $fieldName ];
}
