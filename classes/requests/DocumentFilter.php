<?php

namespace BareFields\requests;

use BareFields\blueprints\abstract\AbstractBlueprint;
use BareFields\blueprints\BlueprintsManager;
use BareFields\helpers\WPSHelper;
use BareFields\multilang\Locales;
use BareFields\objects\Attachment;
use BareFields\objects\Document;
use BareFields\objects\ImageAttachment;
use BareFields\objects\VideoAttachment;
use WP_Post;

class DocumentFilter
{
  const string SCREEN_NAME_MARKER = "___";

  const string TRANSLATED_GROUP_MARKER = "__$$";

  const string HIDDEN_MARKER = "__##";

  const string BOOLEAN_FIELD_MARKER = "__@@";

  const string COMPLEX_ID_MARKER = "__%%";

	/**
	 * Patch all "${screenName}___${fieldName}" to "$fieldName"
	 */
	protected static function patchScreenNameFields ( &$data ) {
		foreach ( $data as $key => $value ) {
			$split = explode(self::SCREEN_NAME_MARKER, $key, 2);
			if ( count($split) != 2 ) continue;
			$data[ $split[1] ] = $value;
			unset( $data[$key] );
		}
		return $data;
	}

  protected static $_fieldsCache = [];

  public static function createDocumentFromPost ( WP_Post $post, int $fetchFields ) {
    // Get associated blueprints to this post
    $blueprints = BlueprintsManager::getMatchingBlueprintsForPost( $post );
    // Check if this post has a multilang blueprint that is not forced to all locales
    $isPostMultilang = !!array_filter(
      $blueprints,
      fn ($b) => $b->getMultilang() && !$b->getMultilangForceAllLocales()
    );
    // Do not process post if it does not exists in current locale
		if ( count(Locales::getLocalesKeys()) > 1 && $isPostMultilang ) {
      $locales = get_field("locales", $post->ID);
      $locale = Locales::getCurrentLocale();
      if ( !in_array($locale, $locales) )
        return null;
    }
    // Convert to document
    $document = new Document( $post );
    // Check if fields are available in cache
    $fieldsCacheKey = $post->ID."__".$fetchFields."__".(Locales::isMultilang() ? Locales::getCurrentLocale() : "");
    if ( isset(self::$_fieldsCache[$fieldsCacheKey]) ) {
      $fields = self::$_fieldsCache[$fieldsCacheKey];
    }
    else {
      // Grab fields
      $fields = [];
      // FIXME : Set it now to avoid infinite loop in get_fields that can trigger
      //          $blueprint->addFieldFilter which makes request to this function
      if ( $fetchFields > 0 ) {
        $fields = get_fields( $post->ID );
        if ( $fields === false )
          $fields = [];
        // Patch screen names to remove uniqueness part
        self::patchScreenNameFields( $fields );
      }
      // Patch fields
      if ( !empty( $fields ) )
        $fields = self::recursivePatchFields( $fields );
      // Grab request filter handlers
      $handlers = [];
      foreach ( $blueprints as $blueprint )
        $handlers = [ ...$handlers, ...$blueprint->getRequestFilterHandlers() ];
      // Call filters for root groups
      foreach ( $blueprints as $blueprint )
        self::filterRootGroupFields( $blueprint, $fields );
      // Run all filters
      foreach ( $handlers as $handler )
        $fields = $handler( $fields, $fetchFields, $document );
      // Save in cache
      self::$_fieldsCache[$fieldsCacheKey] = $fields;
    }
    // Inject fields and return document
    $document->fields = $fields;
    return $document;
  }

  public static function createAttachement ( array $source ) : Attachment {
		if ( $source["type"] === "image" )
			return new ImageAttachment( $source );
		else if ( $source["type"] === "video")
			return new VideoAttachment( $source );
		else
			return new Attachment( $source );
  }

  // ---------------------------------------------------------------------------

  public static function filterRootGroupFields ( AbstractBlueprint $blueprint, array &$fields ) {
    $groups = $blueprint->getGroups();
    foreach ( $groups as $group ) {
      $filter = $group->getRequestFilter();
      if ( is_null($filter) )
        continue;
      $groupName = $group->getName();
      $groupFields = $fields[ $groupName ] ?? [];
      $fields[ $groupName ] = $filter($groupFields, $fields);
    }
  }

  // ---------------------------------------------------------------------------

  public static function recursivePatchFields ( &$data ) : array {
    // First, convert recursively all locales
    // This way we can filter later for translated enabled fields
    if ( Locales::isMultilang() )
      $data = self::recursivePatchLocale( $data, Locales::getCurrentLocale() );
    $data = self::recursivePatchMisc( $data );
    return $data;
  }

  public static function recursivePatchLocale ( &$data, string $locale ) : array {
    foreach ( $data as $key => &$node ) {
      if ( !is_array($node) )
        continue;
      if ( str_ends_with( $key, self::TRANSLATED_GROUP_MARKER ) ) {
        unset( $data[ $key ] );
        $newKey = substr( $key, 0, -strlen( self::TRANSLATED_GROUP_MARKER ) );
        $data[$newKey] = $node[ $locale ] ?? null;
        unset( $data[ $key ] );
        continue;
      }
      // Recursive
      $data[ $key ] = self::recursivePatchLocale( $data[$key], $locale );
    }
    return $data;
  }

  public static function recursivePatchMisc ( &$data ) : array {
    $patchIndexedArray = false;
    foreach ( $data as $key => &$node ) {
			// Remove messages, sub-titles, accordions ...
			if ( str_ends_with($key, self::HIDDEN_MARKER) ) {
				unset( $data[$key] );
				continue;
			}
			// Convert booleans
			if ( str_ends_with( $key, self::BOOLEAN_FIELD_MARKER ) ) {
				$newKey = substr( $key, 0, -strlen( self::BOOLEAN_FIELD_MARKER ) );
				$data[$newKey] = WPSHelper::booleanInput( $node );
				unset( $data[ $key ] );
				continue;
			}
      // Remove !enabled fields
      if ( is_array($node) && isset($node['enabled']) && !is_array($node['enabled']) ) {
        $isEnabled = $node['enabled'];
        $disable = $isEnabled !== "enabled" && !($isEnabled === true || WPSHelper::booleanInput($isEnabled));
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
      // Convert conditional fields and links
      if (
        is_array($node)
        && isset($node['selected']) && is_string($node['selected'])
      ) {
        $selected = $node["selected"];
        $data[ $key ] = [
          "selected" => $selected,
          ...($node[$selected] ?? [])
        ];
				// Set link text from the page title if empty
				if ( isset($node["href"]) && empty($node["text"]) ) {
					$document = DocumentRequest::getDocumentByPath($node["href"]);
					$node["text"] = $document->title;
				}
      }
      // Convert value objects
      if ( $node instanceof WP_Post ) {
        // todo : fetch fields should be configurable
				$data[ $key ] = self::createDocumentFromPost( $node, 0 );
				continue;
			}
			if (
				is_array($node)
				&& isset($node['type'])
				&& isset($node['subtype'])
				&& isset($node['mime_type'])
			) {
				$data[$key] = self::createAttachement( $node );
				continue;
			}
			// Replace ids
			if (
				is_array($node)
				&& isset($node["id".self::COMPLEX_ID_MARKER])
			) {
				$id = $node["id".self::COMPLEX_ID_MARKER];
				unset($node["id".self::COMPLEX_ID_MARKER]);
				$node["id"] = $id;
			}
      // Recursive
      if ( is_array($node) )
        $data[ $key ] = self::recursivePatchMisc($node);
    }
		if ( $patchIndexedArray )
			$data = array_values( $data );
    return $data;
  }

  // ---------------------------------------------------------------------------

	public static function recursiveSerialize ( mixed $object, int $fetchFields = 0 ) {
		// --- NULL
    if ( is_null($object) )
      return null;
		// --- ARRAY -> Recursive
		if ( is_array($object) ) {
      return array_map(
        fn ( $value ) => self::recursiveSerialize( $value, $fetchFields ),
        $object
      );
		}
		// --- OBJECT -> Serialize
		if ( is_object($object) ) {
			// Check if we have some registered serializer
			foreach ( self::$__objectSerializers as $serializer ) {
				$returnedArray = $serializer( $object, $fetchFields );
				// Null mean continue to next serializer
				if ( is_null($returnedArray))
					continue;
				// False mean remove this object from the list
				else if ( $returnedArray === false )
					return null;
				// Array mean it's serialized, use it and do not process next serializers
				else if ( is_array($returnedArray) )
					return $returnedArray;
			}
			// Default serializer will try to use jsonSerialize method
			// or the default php object to array serializer if not found
			return (
  			method_exists($object, 'jsonSerialize')
				? $object->jsonSerialize()
				: (array) $object
			);
		}
		return $object;
	}

  // ---------------------------------------------------------------------------

	protected static array $__objectSerializers = [];

	/**
	 * Filter all recursively serializable objects.
	 * Useful to override jsonSerialize behavior on all serializable objects.
	 *
	 * @param callable $callback function (mixed $object, int $fetchFields = 0) {
	 *    return false; // to consider a null (can be removed from the list if not published, for example)
	 *    return null;  // to continue to the next or default serializer
	 *    return array; // to use serialized and stop at this serializer
	 * }
	 * @return void
	 */
	public static function registerObjectSerializer ( callable $callback ) {
		self::$__objectSerializers[] = $callback;
	}

}
