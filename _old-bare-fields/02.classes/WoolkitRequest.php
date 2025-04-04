<?php

use Nano\core\URL;
use Nano\debug\Debug;

class WoolkitRequest
{
	// ------------------------------------------------------------------------- GET WP POST BY PATH

	/**
	 * Get a Wordpress Post from its path.
	 * @param string $path Path can be relative from base or absolute with scheme and host.
	 * @return WP_Post|null
	 */
	static function getWPPostByPath ( string $path ):?WP_Post {
		$postID = url_to_postid( $path );
		if ( $postID === 0 ) {
			// Get host
			$homeURL = get_home_url();
			$homeURL = URL::extractHost( $homeURL );
			// Prepend with request
			$path = $homeURL.$path;
			// Try to get
			$postID = url_to_postid( $path );
		}
		if ( $postID === 0 )
			return null;
		$post = get_post( $postID );
//		dd($post);
		if ( is_null($post) || $post->post_type === 'attachment' )
			return null;
		return $post;
	}

	// ------------------------------------------------------------------------- GET PAGE DATA

	static function getPostByPath ( string $path, int $fetchFields = 0, $fetchTerms = false, $fetchAuthor = false ):?WoolkitPost {
		$profile = Debug::profile("WoolkitRequest::getPostByPath('$path')");
		$post = self::getWPPostByPath( $path );
		$filtered = WoolkitFilters::filterPost( $post, $fetchFields, $fetchTerms, $fetchAuthor );
		$profile();
		return $filtered;
	}

	static function getPostByID ( string|int $postID, int $fetchFields = 0, $fetchTerms = false, $fetchAuthor = false ):?WoolkitPost {
		$post = get_post( $postID );
		if ( $post->post_type === 'attachment' )
			return null;
		return WoolkitFilters::filterPost( $post, $fetchFields, $fetchTerms, $fetchAuthor );
	}

	// fixme : LIB : add a feature to in field to disable the "as page" listing for some post types
	static function getAllPosts ( array|bool $postTypes = ["page", "posts"], int $fetchFields = 0, $fetchTerms = false, $fetchAuthor = false, $queryOptions = [] ) {
		if ( $postTypes === true )
			$postTypes = get_post_types('');
		unset($postTypes["attachment"]);
		// Get a list of all published pages from WordPress.
		$posts = [];
		foreach ( $postTypes as $postType )
			$posts = array_merge($posts,
				get_posts([
					"numberposts" => -1,
					"post_type" => $postType,
					...$queryOptions,
				])
			);
		$woolkitPosts = [];
		foreach ( $posts as $page )
			$woolkitPosts[] = WoolkitFilters::filterPost( $page, $fetchFields, $fetchTerms, $fetchAuthor );
		return $woolkitPosts;
	}

	public static function getPostsByTemplate ( string $template, array|bool $postTypes = ["page", "posts"], int $fetchFields = 0, $fetchTerms = false, $fetchAuthor = false, $queryOptions = [] ) {
		$allPosts = self::getAllPosts( $postTypes, $fetchFields, $fetchTerms, $fetchAuthor, $queryOptions );
		$posts = [];
		foreach ( $allPosts as $post ) {
			if ( $post->template === $template )
				$posts[] = $post;
		}
		return $posts;
	}

	public static function recursiveSerialize ( $object ) {
//		$profile = Debug::profile("WoolkitRequest::recursiveSerialize");
		if ( is_array($object) ) {
			$output = [];
			foreach ( $object as $key => $value )
				$output[ $key ] = self::recursiveSerialize( $value );
			return $output;
		}
		if ( is_object($object) ) {
			return (
			method_exists($object, 'jsonSerialize')
				? $object->jsonSerialize()
				: (array) $object
			);
		}
//		$profile();
		return $object;
	}

	// ------------------------------------------------------------------------- GET CPT OBJECTS

	static function getSingleton ( string $singletonName ) {
		$profile = Debug::profile("WoolkitRequest::getSingleton('$singletonName')");
		$singletons = WoolkitFields::getInstalledSingletonsByName();
		if ( !isset($singletons[$singletonName]) )
			return null;
		/** @var WoolkitFields $singleton */
		$singleton = $singletons[ $singletonName ];
		$data = [];
		$groups = WoolkitFields::getFieldsGroups( $singleton );
		/** @var WoolkitGroupFields $group */
		foreach ( $groups as $groupKey => $group ) {
			// FIXME : This is not super clean because we can have keys colliding
			// FIXME : Raw fields vs group fields are not stored the same way with ACF ...
			$firstPart = $singleton->getIsSubMenuOf() ?? "toplevel";
			$key = $firstPart.'-page-'.$singletonName.'___'.$groupKey;
			$groupData = get_field( $key, 'option' );
			// Try with prefix, and without
			if ( is_null($groupData) )
				$groupData = get_field( $singletonName.'___'.$groupKey, 'option' );
			if ( is_null($groupData) )
				$groupData = get_field( $groupKey, 'option' );
			if ( is_null($groupData) )
				continue;
			$data[ $groupKey ] = $groupData;
		}
		// Filter all data
		$filtered = WoolkitFilters::filterSingletonData( $singleton, $data );
		$profile();
		return $filtered;
	}

	static function getCollection ( string $name, int $fetchFields = 0, $fetchTerms = false, $fetchAuthor = false, $queryOptions = [] ) {
		return self::getAllPosts([$name], $fetchFields, $fetchTerms, $fetchAuthor, $queryOptions);
	}

	// ------------------------------------------------------------------------- SEARCH

	// TODO : WP Search query
	//	static function searchPost ( string $query, array $options ) {}

	static function deepFieldsSearch ( string $searchTerm, string $postType = 'post', string $postStatus = 'publish' ) {
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
		// todo : update this to get full WoolkitPosts
		return $wpdb->get_results( $sql );
	}

	// ------------------------------------------------------------------------- SUB PAGES

	static function getSubPagesOfPage ( string $pageID, int $fetchFields = 0, $fetchTerms = false, $fetchAuthor = false, int $depth = 1, string $order = 'menu_order', $queryOptions = [] ) {
		$pages = get_pages([
			'child_of' => $pageID,
			'depth' => $depth,
			'sort_column' => $order,
			...$queryOptions,
		]);
		$woolkitPosts = [];
		foreach ( $pages as $page ) {
			$woolkitPost = WoolkitFilters::filterPost( $page, $fetchFields, $fetchTerms, $fetchAuthor );
			if ( is_null($woolkitPost) ) continue;
			$woolkitPosts[] = $woolkitPost;
		}
		return $woolkitPosts;
	}

	// ------------------------------------------------------------------------- CATEGORIES

	// Cache categories request because WP seems to not cache them
	protected static array $__cachedCategories = [];

	/**
	 * Get all categories and cache them.
	 * @param bool $forceRefresh Will force cache to be cleared.
	 * @param array $queryOptions
	 * @return array
	 */
	static function getCategories ( bool $forceRefresh = false, $queryOptions = [] ) {
		if ( $forceRefresh )
			self::$__cachedCategories = [];
		if ( empty(self::$__cachedCategories) ) {
			$categories = get_categories([
				'hide_empty' => false,
				...$queryOptions,
			]);
			// First, filter all categories and store them into the cache
			foreach ( $categories as $term )
				self::$__cachedCategories[] = new WoolkitTerm( $term );
			// Now we have all categories filtered and cached, we can query them
			foreach ( self::$__cachedCategories as $category ) {
				$children = get_term_children($category->id, 'category');
				foreach ( $children as $childID ) {
					$cat = self::getCategoryById( $childID );
					if (is_null($cat)) continue;
					$category->children[] = $cat;
				}
			}
		}
		return self::$__cachedCategories;
	}

	static function getCategoryHierarchy ( bool $forceRefresh = false, $queryOptions = [] ) {
		$hierarchyOutput = [];
		$categories = self::getCategories( $forceRefresh, $queryOptions );
		foreach ( $categories as $category ) {
			if ( $category->parentID == 0 )
				$hierarchyOutput[] = $category;
		}
		return $hierarchyOutput;
	}

	/**
	 * Get a category by its ID.
	 * Can be in a loop, categories are cached.
	 * todo : do it better
	 */
	static function getCategoryById ( int $id, bool $forceRefresh = false ) {
		$categories = self::getCategories( $forceRefresh );
		foreach ( $categories as $category ) {
			if ( $category->id == $id )
				return $category;
		}
		return null;
	}

	/**
	 * Get a category by its slug
	 *  todo : do it better
	 */
	static function getCategoryBySlug ( string $slug, bool $forceRefresh = false  ) {
		$categories = self::getCategories( $forceRefresh );
		foreach ( $categories as $category ) {
			if ( $category->slug == $slug )
				return $category;
		}
		return null;
	}

	// ------------------------------------------------------------------------- TAGS
	// todo : move them ?

	static function filterTag ( WP_Term $tag ) {
		return new WoolkitTerm( $tag );
	}

	static function filterTags ( array $tags ) {
		$filteredTags = [];
		foreach ( $tags as $tag )
			$filteredTags[] = self::filterTag( $tag );
		return $filteredTags;
	}
}
