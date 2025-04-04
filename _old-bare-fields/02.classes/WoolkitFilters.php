<?php

use Nano\core\URL;
use Nano\core\Utils;

class WoolkitFilters
{
	// ------------------------------------------------------------------------- PATCHES

	/**
	 * Patch all "${screenName}___${fieldName}" to "$fieldName"
	 */
	protected static function patchScreenNameFields ( &$data ) {
		foreach ( $data as $key => $value ) {
			$split = explode("___", $key, 2);
			if ( count($split) != 2 ) continue;
			$data[ $split[1] ] = $value;
			unset( $data[$key] );
		}
		return $data;
	}

	// Patch translated keys
	// Out of main loop because altering $data (otherwise node can be duplicated)
	// We create a new array because unsetting in loop can skip keys
	static function patchTranslateKeysOfArray ( string $locale, array &$data ) {
		$newData = [];
		foreach ( $data as $key => &$value ) {
			// Only patch string keys
			if ( is_string($key) ) {
				// Patch translated keys
				if ( str_starts_with($key, $locale.'_') )
					$key = substr($key, strlen($locale) + 1);
				// Patched translated conditional groups
				else if ( str_starts_with($key, "\$_".$locale."_") )
					$key = "\$_".substr($key, strlen($locale) + 3);
				// Convert flexible layouts keys to "type"
				else if ( $key === "acf_fc_layout" )
					$key = "type";
			}
			$newData[ $key ] = $value;
		}
		return $newData;
	}

	protected static string $__recursiveCachedLocale;

	/**
	 * Will patch fields recursively :
	 * - Translated keys (fr_keyName to keyName)
	 * - Clear nodes with enabled=false
	 * - Convert media to WoolkitAttachment objects
	 * - Convert WP_Post to WoolkitPost
	 * - Convert links generated with woolkit_create_link ...
	 * - Key of flexibles "acf_fc_layout" to "type"
	 * @param array $data
	 * @return array
	 */
	public static function recursivePatchFields ( array &$data ):array  {
		// Cache locale to avoid querying explosion
		self::$__recursiveCachedLocale = woolkit_locale_get();
		return self::_recursivePatchFields( $data );
	}

	protected static function _recursivePatchFields ( array &$data ):array {
		$locale = self::$__recursiveCachedLocale;
		$data = self::patchTranslateKeysOfArray( $locale, $data );
		$patchIndexedArray = false;
		// Browse node properties
		foreach ( $data as $key => &$node ) {
			// Remove messages, sub titles, accordions ...
			if ( str_starts_with($key, "@hidden_") ) {
				unset($data[ $key ] );
				continue;
			}
			// Remove all data for a node when field enabled=false
			if ( is_array($node) && isset($node['enabled']) ) {
				// Locale selector is an array
				$disable = false;
				if ( is_array($node['enabled']) && !empty($locale) ) {
					$index = intval( $node['enabled']["value"] ) - 1;
					$locales = array_keys( woolkit_locale_get_languages_list() );
					$currentLocale = woolkit_locale_get();
					// Disabled
					if ( $index === -1 )
						$disable = true;
					// Selected locale is not the same as current locale
					else if ( isset($locales[$index]) && $locales[$index] !== $currentLocale )
						$disable = true;
				}
				// Otherwise just cast and check ( should be "0" or "1" )
				else {
					$isEnabled = $node['enabled'];
					$disable = $isEnabled !== "enabled" && !($isEnabled === true || Utils::booleanInput($isEnabled));
				}
				// Remove from array and do not continue parsing of this element
				if ( $disable ) {
					// Check if it is an indexed array ( enabled field in a repeater )
					if ( array_keys($data) === range(0, count($data) - 1) )
						$patchIndexedArray = true;
					unset( $data[ $key ] );
					continue;
				}
				// Not disabled, just remove the enabled value
				unset( $node['enabled'] );
			}
			// Filter conditional groups generated with ...woolkit_create_conditional_group()
			// Convert field groups like _webAppCapabilities_group_selected = 'ok'
			// To something clean : webAppCapabilities => ["selected" => true, ...]
			if ( is_array($node) ) {
				// Get all keys of this node
				$nk = array_keys($node);
				// Browse keys
				foreach ( $nk as $k ) {
					// Check if it looks like a conditional group key
					if ( !str_starts_with($k, "\$_") ) continue;
					$parts = explode("_", $k, 5);
					// Check with a locale modifier ( it adds an underscore )
					// Because at this point patchTranslateKeysOfArray
					// Has not been called for this $node array.
					$localeMode = false;
					if ( count($parts) === 5 && $parts[1] === $locale ) {
						$localeMode = true;
						// Remove locale part from the key
						unset($parts[1]);
						$parts = array_values( $parts );
					}
					if ( count($parts) != 4 )
						continue;
					// Compute the destination key ( without locale part )
					$toKeyName = $parts[1];
					// If the search is not defined, use the same ( no locale )
					$searchedKeyName = (
						// We take the data from the locale
						$localeMode ? $locale."_".$parts[1]
						// Otherwise keep the same key
						: $toKeyName
					);
//					dump(["----", $searchedKeyName, $toKeyName, $parts]);
					// This is a conditional group key
					// Extract name, value
					$lastPart = $parts[3];
					$extractedValue = $node[ $k ];
					// Always unset original variables because we'll recreate a clean array
					unset( $node[$k] );
					// If we are on the selected node
					if ( $lastPart !== "selected" )
						continue;
					// Inject value of selected node
					$searchedSelectedKey = "\$_".$searchedKeyName.'_group_'.$extractedValue;
					// Extract node
					$fromNode = $node[ $searchedSelectedKey ] ?? [];
					// Translate keys if we are in locale mode
					if ( $localeMode )
						$fromNode = self::patchTranslateKeysOfArray( $locale ,$fromNode );
					// Inject new translated and patched node
					$node[ $toKeyName ] = [
						"selected" => $extractedValue,
						...$fromNode
					];
				}
			}
			if ( str_ends_with($key, '-type') && is_string($node['selected']) && function_exists('woolkit_filter_link_group') ) {
				$k = explode('-type', $key)[0];
				$data = woolkit_filter_link_fields( $k, $node, $locale );
				continue;
			}
			// Process links created with woolkit helper
			// Output is compatible with native Link field
			/*if ( $key === "link-type" && isset($node['link_text']) ) {
				$node['title'] = $node['link_text'];
				unset( $node['link_text'] );
				$isInternal = $node['selected'] === "internal";
				$href = $node["link_".$node['selected']];
				if ( $node['selected'] === "internal" ) {
					$locale = woolkit_locale_get();
					if ( !empty($locale) )
						$href = '/'.$locale.$href;
				}
				unset( $node['link_external'] );
				unset( $node['link_internal'] );
				$node['url'] = $href;
				$node['selected'] = $isInternal ? 'internal' : 'external';
				if ( !$isInternal )
					$node['target'] = '_blank';
				unset( $node['selected'] );
				unset( $data['link-type'] );
				$data['link'] = $node;
				continue;
			}*/
			// Without the group helper ( directly using external or internal helper )
			else if ( $key === "link_text" ) {
				$external = $data['link_external'] ?? "";
				$internal = $data['link_internal'] ?? "";
				unset( $data['link_text'] );
				unset( $data['link_external'] );
				unset( $data['link_internal'] );
				$isInternal = !empty($internal);
				if ( $isInternal ) {
					if ( !empty($locale) )
						$internal = '/'.$locale.$internal;
				}
				$href = $isInternal ? $internal : $external;
//				if ( !empty($href) && defined('WP_HOME') )
//					$href = URL::removeBaseFromHref( $href, WP_HOME );
				$data['link'] = [
					'selected' => $isInternal ? 'internal' : 'external',
					'text' => $node,
					'target' => $isInternal ? '' : '_blank',
					'href' => $href,
				];

			}
			// Filter WP_Post to WoolkitPost and auto fetch fields and sub posts
			if ( $node instanceof WP_Post ) {
				// todo : make this configurable, with a callback in arguments or something
				$data[ $key ] = WoolkitFilters::filterPost( $node  );
				continue;
			}
			// Filter media
			if (
				is_array($node)
				&& isset($node['type'])
				&& isset($node['subtype'])
				&& isset($node['mime_type'])
			) {
				$data[$key] = self::filterAttachment( $node );
				continue;
			}
			// Recursive patch filter
			if ( is_array($node) )
				$data[ $key ] = WoolkitFilters::recursivePatchFields( $node );
		}
		if ( $patchIndexedArray )
			$data = array_values( $data );
		return $data;
	}

	// ------------------------------------------------------------------------- WOOLKIT POST FILTER

	protected static array $__woolkitPostFilters = [];
	static function registerWoolkitPostsFieldsFilter ( callable $handler, $afterFieldFiltering = true ) {
		self::$__woolkitPostFilters[] = [$afterFieldFiltering, $handler];
	}

	/**
	 * Filter a WP_Post and convert it to a filtered WoolkitPost.
	 * @param WP_Post|null $post
	 * @param int $fetchFields 0 to fetch no fields. 1 or more to fetch all fields.
	 *                           This int is available in filters, so filters can
	 *                           different levels of filtering
	 *                           Ex : 1 for excerpt, 2 for complete
	 * @param bool $fetchTerms
	 * @param bool $fetchAuthor
	 * @return WoolkitPost|null
	 */
	static function filterPost ( WP_Post|null $post, int $fetchFields = 0, bool $fetchTerms = false, bool $fetchAuthor = false ):?WoolkitPost {
		if ( is_null($post) )
			return null;
		// Do not fetch fields
		if ( $fetchFields === 0 )
			return new WoolkitPost( $post, [], $fetchTerms, $fetchAuthor );
		// Get raw fields associated to this post
		//wp_cache_delete( $post->ID, 'post_meta' );
//		global $wp_object_cache;
//		$wp_object_cache->flush();
		$fields = get_fields( $post->ID );
		if ( $fields === false ) $fields = [];
		// Patch screen names to remove uniqueness part
		self::patchScreenNameFields( $fields );
		// Filter with global before filter
		foreach ( self::$__woolkitPostFilters as $filter )
			if ( !$filter[0] )
				$fields = $filter[1]( $fields, $post );
		// Recursive patch fields after pre-filter be before fields filter
		$fields = self::recursivePatchFields( $fields );
		// Get matching installed fields and browse them
		$matchingFields = WoolkitFields::getMatchingInstalledFieldsForPost( $post );
//			dd($matchingFields);
		/** @var WoolkitFields $field */
		foreach ( $matchingFields as $field ) {
			// Get field filter and filter raw fields through them
			$handlers = WoolkitFields::getFieldsFilterHandlers( $field );
			foreach ( $handlers as $handler )
				$fields = $handler( $fields, $fetchFields, $post );
		}
		// Filter with global after filter
		foreach ( self::$__woolkitPostFilters as $filter )
			if ( $filter[0] )
				$fields = $filter[1]( $fields, $post );
		// Create a new woolkit post from original WP_Post and parsed fields
		return new WoolkitPost( $post, $fields, $fetchTerms, $fetchAuthor );
	}

	// ------------------------------------------------------------------------- FILTER SINGLETON

	/**
	 * Filter a Singleton Field and its data.
	 * Should not be used directly.
	 * Data will be recursively patched and filtered by WoolkitFields filters.
	 * @see WoolkitRequest::getSingleton()
	 * @param WoolkitFields $singletonFields WoolkitFields to filter (holds filter handlers)
	 * @param array $singletonData Singleton data, gathered with get_field.
	 * @return array
	 */
	static function filterSingletonData ( WoolkitFields $singletonFields, array $singletonData ) {
		// Recursive patch fields after pre-filter be before fields filter
		$singletonData = self::recursivePatchFields( $singletonData );
		// Get filters and apply them
		$filters = WoolkitFields::getFieldsFilterHandlers( $singletonFields );
		foreach ( $filters as $filter )
			$singletonData = $filter( $singletonData );
		return $singletonData;
	}

	// ------------------------------------------------------------------------- OTHER FILTERS

	/**
	 * Filter rich content and
	 * @param string $content
	 * @return string
	 */
	static function filterRichContent ( string $content ):string {
		// Remove HTML comments
		$content = preg_replace("/<!--(.*)-->/Uis", "", $content);
		// Remove multiple line jumps
		return preg_replace("/[\r\n]+/", "\n", $content);
	}

	/**
	 * Convert WP Attachment to a WoolkitAttachment
	 * @param array $node
	 * @return WoolkitAttachment
	 */
	static function filterAttachment ( array $node ):WoolkitAttachment {
		if ( $node["type"] === "image" )
			return new WoolkitImage( $node );
		else if ( $node["type"] === "video")
			return new WoolkitVideo( $node );
		else
			return new WoolkitAttachment( $node );
	}
}
