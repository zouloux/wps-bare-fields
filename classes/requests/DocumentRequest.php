<?php

namespace BareFields\requests;

use BareFields\blueprints\BlueprintsManager;
use BareFields\helpers\WPSHelper;
use BareFields\multilang\Locales;
use BareFields\objects\Document;
use WP_Post;

class DocumentRequest {

  public static array $__forbiddenPostTypes = ["attachment"];

	// --------------------------------------------------------------------------- NANO DEBUG

  // Check if Nano is loaded and trigger a debug profiling
  // Otherwise, return a simple mock function to close the profile so the usage is the same
  protected static function nanoDebugProfile ( string $name, bool $forceProfiling = false ) {
    if ( !class_exists("\Nano\debug\Debug") )
      return fn () => null;
    return \Nano\debug\Debug::profile( $name, $forceProfiling );
  }

	// --------------------------------------------------------------------------- GET WP POST

	/**
	 * Get a Wordpress Post from its path.
	 * @param string $path Path can be relative from base or absolute with scheme and host.
	 * @return WP_Post|null
	 */
	protected static function getWPPostByPath ( string $path ) :? WP_Post {
		$postID = url_to_postid( $path );
		if ( $postID === 0 ) {
			// Get host
			$homeURL = get_home_url();
			$homeURL = WPSHelper::extractHost( $homeURL );
			// Prepend with request
			$path = $homeURL.$path;
			// Try to get
			$postID = url_to_postid( $path );
		}
		if ( $postID === 0 )
			return null;
		$post = get_post( $postID );
		if ( is_null($post) || $post->post_type === 'attachment' )
			return null;
		return $post;
	}

  // --------------------------------------------------------------------------- MISC

  public static function getSiteName () {
    return get_bloginfo("name");
  }

  public static function getAdminEmail () {
    return get_option("admin_email");
  }

  // --------------------------------------------------------------------------- SYSTEM

  protected static function getSiteMapForPostTypes ( string $absoluteBase, array $postTypes, string $locale = "" ) {
    $absoluteBase = rtrim($absoluteBase, "/");
    $sitemap = [];
    $documents = DocumentRequest::getDocumentsByPostType( $postTypes );
    foreach ( $documents as $document ) {
      $documentMetaSitemap = $document->getField("meta.sitemap");
      if ( $documentMetaSitemap === "hidden" )
        continue;
      $sitemap[] = [
        "href" => "$absoluteBase/$locale".$document->href,
        "date" => $document->date->getTimestamp(),
      ];
    }
    return $sitemap;
  }

  public static function getSitemaps ( string $absoluteBase, array $postTypes ) {
    $sitemaps = [];
    $absoluteBase = rtrim($absoluteBase, "/");
    // In multi locale mode, we have 1 main sitemap that redirect to locale sitemaps
    if ( Locales::isMultilang() ) {
      // Generate main sitemap
      $locales = Locales::getLocalesKeys();
      $sitemaps["main"] = array_map(
        fn ( $locale ) => "$absoluteBase/$locale/sitemap.xml",
        array_values( $locales )
      );
      $oldLocale = Locales::getCurrentLocale();
      // Generate locales sitemaps
      foreach ( $locales as $locale ) {
        Locales::setCurrentLocale($locale);
        $sitemaps[$locale] = self::getSiteMapForPostTypes($absoluteBase, $postTypes, $locale);
      }
      Locales::setCurrentLocale($oldLocale);
    }
    // 1 locale, 1 main sitemap
    else {
      $sitemaps["main"] = self::getSiteMapForPostTypes($absoluteBase, $postTypes);
    }
    return $sitemaps;
  }

  // --------------------------------------------------------------------------- POSTS / PAGES / COLLECTIONS
  // Can return a post, a page, or a collection item

  /**
   * @param array $postTypes
   * @param int $fetchFields
   * @param array $queryOptions
   *
   * @return Document[]
   */
  static function getDocumentsByPostType ( array $postTypes, int $fetchFields = 0, array $queryOptions = [] ) : array {
    $postTypesAsString = implode(',', $postTypes);
    $profile = self::nanoDebugProfile("DocumentRequest::getDocumentsByPostType(['$postTypesAsString'], $fetchFields)");
    if ( empty($postTypes) )
			$postTypes = get_post_types('');
    foreach (self::$__forbiddenPostTypes as $postType)
		  unset( $postTypes[$postType] );
    // Get a list of all published pages from WordPress.
		$posts = [];
		foreach ( $postTypes as $postType ) {
      $posts = array_merge(
        $posts,
        get_posts([
          "numberposts" => -1,
          "post_type" => $postType,
          ...$queryOptions,
        ])
      );
    }
    $documents = [];
		foreach ( $posts as $post ) {
      $document = DocumentFilter::createDocumentFromPost( $post, $fetchFields );
      if ( !is_null($document) )
			  $documents[] = $document;
    }
    $profile();
		return $documents;
  }

  static function getDocumentByPath ( string $path, int $fetchFields = 0 ) :? Document {
		$profile = self::nanoDebugProfile("DocumentRequest::getDocumentByPath('$path', $fetchFields)");
		$post = self::getWPPostByPath( $path );
		$filtered = (
      is_null($post)
      ? null
      : DocumentFilter::createDocumentFromPost( $post, $fetchFields )
    );
		$profile();
		return $filtered;
  }

	static function getDocumentByID ( string|int $postID, int $fetchFields = 0, bool $onlyPublished = true ) :? Document {
		$profile = self::nanoDebugProfile("DocumentRequest::getDocumentByID('$postID', $fetchFields)");
		$post = get_post( $postID );
		if ( is_null($post) )
			return null;
		if ( in_array($post->post_type, self::$__forbiddenPostTypes) )
			return null;
		if ( $onlyPublished && $post->post_status !== "publish" )
			return null;
		$profile();
		return DocumentFilter::createDocumentFromPost( $post, $fetchFields );
	}

  // --------------------------------------------------------------------------- PAGE

  static function getPageDocumentsByTemplateName ( string $name, int $fetchFields = 0, $queryOptions = [] ) {
    $profile = self::nanoDebugProfile("DocumentRequest::getPageDocumentsByTemplateName('$name', $fetchFields)");
    $allDocuments = self::getDocumentsByPostType( ["page"], $fetchFields, $queryOptions );
    $filteredDocuments = array_values(array_filter( $allDocuments, fn($d) => $d->name === $name ));
    $profile();
    return $filteredDocuments;
  }

	static function getSubPagesOfPage ( string $pageID, int $fetchFields = 0, int $depth = 1, string $order = "menu_order", $queryOptions = [] ) {
    $profile = self::nanoDebugProfile("DocumentRequest::getSubPagesOfPage('$pageID', $fetchFields, $depth)");
		$pages = get_pages([
			'child_of' => $pageID,
			'depth' => $depth,
			'sort_column' => $order,
			...$queryOptions,
		]);
		$documents = [];
		foreach ( $pages as $page ) {
			$document = DocumentFilter::createDocumentFromPost( $page, $fetchFields );
			if ( !is_null($document) )
			  $documents[] = $document;
		}
    $profile();
		return $documents;
	}

  // --------------------------------------------------------------------------- SINGLETON

  static function getSingletonFields ( string $singletonName ) {
    $profile = self::nanoDebugProfile("DocumentRequest::getSingletonFields('$singletonName')");
		$singleton = BlueprintsManager::getInstalledSingletonByName( $singletonName );
    if ( !$singleton )
      return null;
		$groups = $singleton->getGroups();
		$fields = [];
		foreach ( $groups as $group ) {
			// FIXME : This is not super clean because we can have keys colliding
			// FIXME : Raw fields vs group fields are not stored the same way with ACF ...
      $parent = $singleton->getParentMenu();
			$firstPart = empty($parent) ? "toplevel" : $parent;
      $groupeName = $group->getName();
			$key = $firstPart.'_page_'.$singletonName.DocumentFilter::SCREEN_NAME_MARKER.$groupeName;
			$groupData = get_field( $key, 'option' );
			// Try with prefix, and without
//			if ( is_null($groupData) )
//				$groupData = get_field( $singletonName.DocumentFilter::SCREEN_NAME_MARKER.$groupeName, 'option' );
//			if ( is_null($groupData) )
//				$groupData = get_field( $key, 'option' );
			if ( is_null($groupData) )
				continue;
			$fields[ $groupeName ] = $groupData;
		}
    $fields = DocumentFilter::recursivePatchFields( $fields );
    DocumentFilter::filterRootGroupFields( $singleton, $fields );
    $handlers = $singleton->getRequestFilterHandlers();
    foreach ( $handlers as $handler )
      $fields = $handler( $fields );
		$profile();
		return $fields;
  }

  // --------------------------------------------------------------------------- COLLECTIONS

  static function getCollectionDocuments ( string $name, int $fetchFields = 0, $queryOptions = [] ) {
    $profile = self::nanoDebugProfile("DocumentRequest::getSingletonFields('$name', $fetchFields)");
		$documents = self::getDocumentsByPostType([$name], $fetchFields, $queryOptions);
    $profile();
    return $documents;
  }

  // --------------------------------------------------------------------------- DEEP SEARCH

	static function deepFieldsSearch ( string $searchTerm, string $postType = 'post', string $postStatus = 'publish' ) {
    // todo : add profile
		// FIXME : What about other locale fields ?
		global $wpdb;
		$like = '%' . $wpdb->esc_like( $searchTerm ) . '%';
		$sql = $wpdb->prepare(
			"SELECT DISTINCT p.*
				FROM {$wpdb->posts} AS p
				LEFT JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
				LEFT JOIN {$wpdb->term_relationships} AS tr ON p.ID = tr.object_id
				LEFT JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				LEFT JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
				WHERE p.post_type = %s
					AND p.post_status = %s
					AND (
						p.post_title LIKE %s
						OR pm.meta_value LIKE %s
						OR ( tt.taxonomy = 'category' AND t.name LIKE %s )
					)
			",
			$postType, $postStatus, $like, $like, $like
		);
    // fixme : return documents
		return $wpdb->get_results( $sql );
	}

  // --------------------------------------------------------------------------- PUBLISH SCHEDULED POSTS

	/**
	 * Publish scheduled posts that are now in a past date.
	 * Call this in a cron to publish scheduled posts.
	 * @param array $postTypes post types to publish
	 * @return int Will return the number of published posts. The cache should be cleared if > 0.
	 */
	static function publishScheduledPosts ( array $postTypes = ["post", "page"] ) {
		$timezoneOffset = get_option('gmt_offset') * HOUR_IN_SECONDS;
		$query = new WP_Query([
			'post_type' => $postTypes,
			'post_status' => 'future',
			'date_query' => [
				[
					'before' => gmdate('Y-m-d H:i:s', time() + $timezoneOffset),
					'inclusive' => true
				]
			]
		]);
		$total = 0;
		while ( $query->have_posts() ) {
			++$total;
			$query->the_post();
			$postID = get_the_ID();
			wp_publish_post( $postID );
		}
		wp_reset_postdata();
		return $total;
	}

}
