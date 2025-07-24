<?php

namespace BareFields\objects;

use BareFields\blueprints\abstract\AbstractBlueprint;
use BareFields\blueprints\structs\CollectionBlueprint;
use BareFields\blueprints\structs\PageBlueprint;
use BareFields\helpers\WPSHelper;
use BareFields\multilang\Locales;
use BareFields\multilang\Multilang;
use BareFields\requests\DocumentFilter;
use DateTime;
use WP_Post;

class Document {
  // --------------------------------------------------------------------------- BASICS

	protected WP_Post $_source;
	public function getSource ():WP_Post { return $this->_source; }

	public int $id;
	public string $type; // collection / page / post
	public string $name;
	public string $href;
	public string $title;
  public bool $isPublished;
  public int $parentID;
  public DateTime $date;
	public DateTime $modified;

	public string $content;
	public string $excerpt;

  public array $fields;

  public array $locales;

  public bool $hasSubPaths;

  // --------------------------------------------------------------------------- CONSTRUCT

  public function __construct ( WP_Post $post, array $blueprints = [] ) {
    // Save original WP post and id
		$this->_source = $post;
    $this->id = $post->ID;
    // Compute type and name from post type
    $postType = $post->post_type;
    if ( $postType === "page" ) {
      $name = get_page_template_slug($this->id);
      $this->name = is_string($name) ? $name : "";
    }
    else if ( $postType === "post" ) {
      $this->name = "";
    }
    else {
      $this->name = $postType;
      $postType = "collection";
    }
    $this->type = $postType;
		// Remove base from href
		$this->href = get_permalink( $post );
		if ( !empty($this->href) && defined('WP_CONTENT_URL') )
      $this->href = WPSHelper::removeBaseFromHref( $this->href, WP_CONTENT_URL );
		// Add locale if multilang
		if ( Locales::isMultilang() ) {
			if ( str_starts_with($this->href, "/") ) {
				$this->href = "/".Locales::getCurrentLocale().$this->href;
			}
			// TODO : Insert locale in absolute link
			else {}
		}
    // Parse translated title
    $this->title = $post->post_title ?? "";
    if ( Locales::isMultilang() )
      $this->title = Multilang::parseInlinedValue( $this->title, Locales::getCurrentLocale() );
    // Get other properties
		$this->isPublished = $post->post_status == "publish";
		$this->parentID    = $post->post_parent;
		$this->date        = new \DateTime( $post->post_date );
		$this->modified    = new \DateTime( $post->post_modified );
    /** @var AbstractBlueprint $blueprint */
    foreach ($blueprints as $blueprint) {
      if ( $blueprint instanceof PageBlueprint or $blueprint instanceof CollectionBlueprint ) {
        if ( $blueprint->getHasSubPaths() )
          $this->hasSubPaths = true;
      }
		}
  }

  // --------------------------------------------------------------------------- FIELDS

  /**
   * Format is "group.group.fieldName".
   * @param string $path
   * @return mixed
   */
  public function getField ( string $path ) {
    // fixme : maybe use acf key finder here ?
    $path = DocumentFilter::SCREEN_NAME_MARKER.str_replace(".", "_", $path);
    $value = get_field($path, $this->id);
    if ( acf_is_field_key( $value ) )
      return get_field($value, $this->id);
    else
      return $value;
  }


  // --------------------------------------------------------------------------- CONTENT

  public function fetchContent () {
    // TODO
//    $this->content = WoolkitFilters::filterRichContent( $this->_source->post_content );
  }

  public function fetchExcerpt () {
    // TODO
//    $this->excerpt = WoolkitFilters::filterRichContent( $this->_source->post_excerpt );
  }

  // --------------------------------------------------------------------------- THUMBNAIL

  public $thumbnail;

  public function fetchThumbnail () {
    // Get thumbnail
		$thumbnailID = get_post_thumbnail_id( $this->id );
		if ( !$thumbnailID )
      return;
    $src = wp_get_attachment_image_src( $thumbnailID, 0 );
    $image = wp_get_attachment_metadata( $thumbnailID );
    if ( is_array($src) && is_array($image) && !empty($image["file"]) ) {
      $this->thumbnail = new ImageAttachment([
        "ID" => $thumbnailID,
        "type" => "image",
        "filename" => $image["file"],
        "filesize" => 0, // FIXME
        "url" => $src[0],
        "width" => $image["width"],
        "height" => $image["height"],
        "sizes" => $image["sizes"],
      ]);
    }
  }

  // --------------------------------------------------------------------------- TAGS

	public array $tags = [];

  // TODO
  public function fetchTags () {
		throw new \Exception("Not implemented");
//    $tags = wp_get_post_terms( $this->id, 'post_tag', [ "fields" => "all" ] );
//		$this->tags = WoolkitRequest::filterTags( $tags );
  }

  // --------------------------------------------------------------------------- CATEGORIES

	public array $categories = [];

  // TODO
  public function fetchCategories () {
		throw new \Exception("Not implemented");
//		$categoryIDS = wp_get_post_categories( $this->id );
//		$this->categories = [];
//		if ( !empty($categoryIDS) )
//			foreach ( $categoryIDS as $categoryID )
//				$this->categories[] = WoolkitRequest::getCategoryById( $categoryID );
  }

  // --------------------------------------------------------------------------- AUTHOR

  public array $author;

  public function fetchAuthor ( bool $fetchAvatar = true, bool $fetchDescription = false, bool $fetchEmail = false ) {
		$authorID = intval( $this->_source->post_author );
    $authorInfo = get_userdata( $authorID );
    $author = [
      "name"      => $authorInfo->display_name,
      "nickname"  => $authorInfo->nickname,
    ];
    if ( $fetchAvatar ) {
      $avatar = get_avatar_url( $authorID );
      if ( !empty($avatar) ) {
        $author["avatar"] = $avatar;
      }
    }
    if ( $fetchDescription ) {
      $author["description"] = get_the_author_meta( 'description', $authorID );
    }
    if ( $fetchEmail ) {
      $author["email"] = $authorInfo->user_email;
    }
    return $author;
  }

	// --------------------------------------------------------------------------- TO ARRAY

	public function jsonSerialize ( int $fetchFields = 0 ):array {
		$json = [
			"id"    => $this->id,
			"href"  => $this->href,
			"title" => $this->title,
			"type"  => $this->type,
			"name"  => $this->name,
			"date"  => $this->date->getTimestamp(),
			// NOTE : use DocumentFilter::registerObjectSerializer to include more
		];
		if ( $this->parentID !== 0 )
			$json["parent"] = $this->parentID;
		if ( !empty($this->author) )
			$json["author"] = $this->author;
		if ( !empty($this->tags) )
			$json["tags"] = DocumentFilter::recursiveSerialize( $this->tags, $fetchFields );
		if ( !empty($this->categories) )
			$json["categories"] = DocumentFilter::recursiveSerialize( $this->categories, $fetchFields );
		if ( !empty($this->fields) )
			$json["fields"] = DocumentFilter::recursiveSerialize( $this->fields, $fetchFields );
		if ( !empty($this->locales) )
			$json["locales"] = $this->locales;
    if ( !empty($this->hasSubPaths) )
      $json["hasSubPaths"] = $this->hasSubPaths;
		return $json;
	}
}
