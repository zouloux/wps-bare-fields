<?php

use BareFields\multilang\Locales;
use BareFields\requests\DocumentFilter;
use BareFields\requests\DocumentRequest;
use Nano\core\Loader;
use Nano\helpers\Cache;


class CachedDocumentRequest {

	public static function cache ():Cache {
		return Cache::getInstance("documents");
	}

  // --------------------------------------------------------------------------- MISC

  public static function getSiteName () {
    return self::cache()->define("misc_siteName", function () {
      Loader::loadWordpress();
      return DocumentRequest::getSiteName();
    });
  }

  public static function getAdminEmail () {
    return self::cache()->define("misc_adminEmail", function () {
      Loader::loadWordpress();
      return DocumentRequest::getAdminEmail();
    });
  }

	public static function getTimezoneOffset () {
		return self::cache()->define("misc_timezoneOffset", function () {
      Loader::loadWordpress();
			return get_option('gmt_offset') * HOUR_IN_SECONDS;
    });
	}

  // --------------------------------------------------------------------------- SITEMAP

  public static function getSitemaps ( string $absoluteBase, array $postTypes ) {
    $cacheKey = "system_sitemap_{$absoluteBase}__" . implode( "__", $postTypes );
    return self::cache()->define($cacheKey, function () use ( $absoluteBase, $postTypes ) {
      Loader::loadWordpress();
      return DocumentRequest::getSitemaps( $absoluteBase, $postTypes );
    });
  }

  // --------------------------------------------------------------------------- POSTS / PAGES / COLLECTIONS

  public static function getDocumentsByPostType ( array $postTypes, int $fetchFields = 0, string $locale = "" ) {
    $cacheKey = "documentsByPostType_{$locale}_{$fetchFields}__" . implode( "_", $postTypes );
    return self::cache()->define($cacheKey, function () use ( $postTypes, $fetchFields, $locale ) {
      Loader::loadWordpress();
      if ( !empty( $locale ) )
        Locales::setCurrentLocale( $locale );
      $document = DocumentRequest::getDocumentsByPostType( $postTypes, $fetchFields );
      return DocumentFilter::recursiveSerialize( $document, $fetchFields );
    });
  }

  public static function getDocumentByPath ( string $requestPath, int $fetchFields = 0, string $locale = "" ) {
    $cacheKey = "documentByPath_{$locale}_{$fetchFields}__$requestPath";
    return self::cache()->define($cacheKey, function () use ( $requestPath, $fetchFields, $locale ) {
      Loader::loadWordpress();
      if ( !empty( $locale ) )
        Locales::setCurrentLocale( $locale );
      $document = DocumentRequest::getDocumentByPath( $requestPath, $fetchFields );
      return DocumentFilter::recursiveSerialize( $document, $fetchFields );
    });
  }

  public static function getDocumentByID ( int|string $postID, int $fetchFields = 0, string $locale = "" ) {
    $cacheKey = "documentByID_{$locale}_{$fetchFields}__$postID";
    return self::cache()->define($cacheKey, function () use ( $postID, $fetchFields, $locale ) {
      Loader::loadWordpress();
      if ( !empty( $locale ) )
        Locales::setCurrentLocale( $locale );
      $document = DocumentRequest::getDocumentByID( $postID, $fetchFields );
      return DocumentFilter::recursiveSerialize( $document, $fetchFields );
    });
  }

  // --------------------------------------------------------------------------- PAGE

  public static function getPageDocumentsByTemplateName ( string $name, int $fetchFields = 0, string $locale = "" ) {
    $cacheKey = "pageDocumentsByTemplateName_{$locale}_{$fetchFields}__$name";
    return self::cache()->define($cacheKey, function () use ( $name, $fetchFields, $locale ) {
      Loader::loadWordpress();
      if ( !empty( $locale ) )
        Locales::setCurrentLocale( $locale );
      $document = DocumentRequest::getPageDocumentsByTemplateName( $name, $fetchFields );
      return DocumentFilter::recursiveSerialize( $document, $fetchFields );
    });
  }

  public static function getSubPagesOfPage ( int|string $postID, int $fetchFields = 0, int $depth = 1, string $order = "menu_order", string $locale = "" ) {
    $cacheKey = "subPagesOfPage_{$locale}_{$fetchFields}_{$depth}_{$order}__$postID";
    return self::cache()->define($cacheKey, function () use ( $postID, $fetchFields, $depth, $order, $locale ) {
      Loader::loadWordpress();
      if ( !empty( $locale ) )
        Locales::setCurrentLocale( $locale );
      $document = DocumentRequest::getSubPagesOfPage( $postID, $fetchFields, $depth, $order );
      return DocumentFilter::recursiveSerialize( $document, $fetchFields );
    });
  }

  // --------------------------------------------------------------------------- SINGLETON

  public static function getSingleton ( string $singletonName, int $fetchFields = 0, string $locale = "" ) {
    $cacheKey = "singleton_{$locale}_{$fetchFields}_$singletonName";
    return self::cache()->define($cacheKey, function () use ( $singletonName, $fetchFields, $locale ) {
      Loader::loadWordpress();
      if ( !empty( $locale ) )
        Locales::setCurrentLocale( $locale );
      $data = DocumentRequest::getSingletonFields( $singletonName, $fetchFields );
      return DocumentFilter::recursiveSerialize( $data, $fetchFields );
    });
  }

  // --------------------------------------------------------------------------- COLLECTIONS

  public static function getCollectionDocuments ( string $collectionName, int $fetchFields = 0, string $locale = "" ) {
    $cacheKey = "collection_{$locale}_{$fetchFields}_$collectionName";
    return self::cache()->define($cacheKey, function () use ( $collectionName, $fetchFields, $locale ) {
      Loader::loadWordpress();
      if ( !empty( $locale ) )
        Locales::setCurrentLocale( $locale );
      $data = DocumentRequest::getCollectionDocuments( $collectionName, $fetchFields );
      return DocumentFilter::recursiveSerialize( $data, $fetchFields );
    });
  }
}
