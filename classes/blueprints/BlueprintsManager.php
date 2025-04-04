<?php

namespace BareFields\blueprints;

use BareFields\blueprints\abstract\AbstractBlueprint;
use BareFields\blueprints\structs\CollectionBlueprint;
use BareFields\blueprints\structs\PageBlueprint;
use BareFields\blueprints\structs\PostBlueprint;
use BareFields\blueprints\structs\SingletonBlueprint;
use BareFields\helpers\AdminHelper;
use Extended\ACF\Fields\Group;
use Extended\ACF\Key;
use Extended\ACF\Location;
use WP_Post;


class BlueprintsManager {

	// --------------------------------------------------------------------------- REGISTERING

	static protected array $__registeredBlueprints = [];
	static function register ( callable $handler ) : void {
		self::$__registeredBlueprints[] = $handler;
	}


	// --------------------------------------------------------------------------- INSTALLING

//	protected static array $__allFieldGroupOrders = [];

	protected static array $__installedBlueprints = [];
  /** @return AbstractBlueprint[] */
  static function getAllInstalledBlueprints () : array {
    return self::$__installedBlueprints;
  }

	protected static bool $__isInstalled = false;

	static function install () {
		if ( self::$__isInstalled )
			return false;
		self::$__isInstalled = true;
		$separatorPosition   = 0;
		foreach ( self::$__registeredBlueprints as $handler ) {
      /** @var AbstractBlueprint $blueprint */
			$blueprint = $handler();
      if ( !($blueprint instanceof AbstractBlueprint) ) {
        throw new \Exception("BlueprintsManager::install // Invalid registered blueprint");
      }
      if ( $blueprint instanceof SingletonBlueprint || $blueprint instanceof CollectionBlueprint ) {
        $position = $blueprint->getMenuPosition() ?? 0;
        $separatorPosition = max($position, $separatorPosition);
      }
			self::installBlueprints( $blueprint );
			self::$__installedBlueprints[] = $blueprint;
		}

		self::reorderMenu( $separatorPosition );
//		self::afterFunctions();
    return true;
	}

	protected static function installBlueprints ( AbstractBlueprint $blueprint ) {
		/**
		 * SINGLETON BLUEPRINT
		 */
		if ( $blueprint instanceof SingletonBlueprint) {
      // Set location, id and order
      $blueprint->location[] = Location::where( 'options_page', $blueprint->name );
      // Register options page with ACF
      $parentMenu = $blueprint->getParentMenu();
      $blueprintOptions = [
        ...$blueprint->getOptions(),
        'menu_slug'   => $blueprint->name,
        'menu_title'  => $blueprint->getMenuTitle(),
        'page_title'  => $blueprint->getMenuLabel(),
        'icon_url'    => $blueprint->getMenuIcon(),
        'position'    => $blueprint->getMenuPosition(),
      ];
      if ( !empty($parentMenu) ) {
        $blueprint->id = $parentMenu.'_page_'.$blueprint->name;
        acf_add_options_sub_page([
          ...$blueprintOptions,
          'parent_slug' => $parentMenu,
          'position'    => 10, // todo : configurable
        ]);
      } else {
        $blueprint->id = 'toplevel_page_'.$blueprint->name;
        acf_add_options_page($blueprintOptions);
      }
		}
		/**
		 * COLLECTION BLUEPRINT
		 */
		else if ( $blueprint instanceof CollectionBlueprint) {
			// Set location, id and order
			$blueprint->location[] = Location::where("post_type", $blueprint->name);
			$blueprint->id = $blueprint->name;
			$orderHookName = $blueprint->name;
      // Compute ACF options
      $options = [
        ...$blueprint->getOptions(),
        'label' => $blueprint->getMenuTitle(),
        'public' => $blueprint->getShowInPages(),
        'show_ui' => $blueprint->getShowInAdminUI(),
        'show_in_rest' => $blueprint->getShowInRest(),
        'has_archive' => false, // todo ?
        'supports' => ['title', 'page-attributes'], // todo ? /** 'title' 'editor' 'author' 'thumbnail' 'excerpt' 'trackbacks' 'custom-fields' 'comments' 'revisions' 'page-attributes' 'post-formats' */
        'menu_position' => $blueprint->getMenuPosition(),
        'menu_icon' => $blueprint->getMenuIcon(),
      ];
      $parentMenu = $blueprint->getParentMenu();
      if ( !empty($parentMenu) )
        $options['show_in_menu'] = $parentMenu;
      // Set slug for collection
      if ( !empty($blueprint->getSlug()) )
        $options['rewrite'] = [ "slug" => $blueprint->getSlug() ];
      // Register this post type at WP init
      register_post_type( $blueprint->name, $options );
		  // Patch admin screen for custom post type
		  self::patchAdminCustomScreen( $blueprint );
		}
    /**
     * PAGES OR POSTS BLUEPRINT
     */
    else if ( $blueprint instanceof PageBlueprint || $blueprint instanceof PostBlueprint ) {
      // Remove Wysiwyg editor
      if ( !$blueprint->getEditor() )
        AdminHelper::removeFieldForPost("page", "editor");
      if ( !$blueprint->getExcerpt() )
        AdminHelper::removeFieldForPost("page", "excerpt");
      // All posts
      if ( $blueprint instanceof PostBlueprint ) {
        $blueprint->location[] = Location::where("post_type", "==", "post");
      }
      // All pages
      else if ( empty($blueprint->name) ) {
        $blueprint->location[] = Location::where("post_type", "==", "page");
      }
      // Templates pages
      else {
        $blueprint->id = acf_slugify($blueprint->name);
        $blueprint->location[] = Location::where("post_template", "==", $blueprint->name);
        // Register template
        add_filter("theme_page_templates", function ($templates) use ($blueprint) {
          $templates[ $blueprint->name ] = $blueprint->getNiceTemplateName() ?? $blueprint->name;
          return $templates;
        });
      }
    }
		/**
		 * REGISTER GROUPS
		 */
		// Ordered IDs of field groups
//		$fieldGroupsIDOrders = [];
		// Process all groups for this screen
		$groupPositionCounter = 0;
    /** @var RootGroup[] $groups */
    $groups = $blueprint->getGroups();
		foreach ( $groups as $groupObject ) {
			$groupOptions = $groupObject->toArray();
			// Set a key from screen and group name to avoid collisions across screens
			// Separator with ___ here is important because it will be used to strip
      $groupName = $groupOptions['name'];
      $groupPosition = $groupOptions['position'] ?? $groupPositionCounter + 1;
      $groupPositionCounter = max($groupPositionCounter, $groupPosition);
			$key = ($blueprint->id ?? acf_slugify($blueprint->name)).'___'.$groupName;
			// Create FieldGroup
      $rootGroupKey = Key::generate(Key::sanitize($key), 'group');
			// Store key to order it later
//			$fieldGroupsIDOrders[] = 'acf-'.$rootGroupKey;
      $fields = (
        // If rawFields is enabled, directly show fields without parent group
        $groupOptions['rawFields']
        ? array_map( fn ($field) => $field->get(), $groupOptions['fields'] )
        // By default, show fields inside a nameless group
        : [
          // We use the unique key here to avoid collisions
          Group::make(" ", $key) // keep space in label
            ->layout('row')
            ->helperText( $groupOptions['instructions'] )
            ->fields( $groupOptions['fields'] )
            ->get()
        ]
      );
			// Register this field group
			register_field_group([
        ...$groupOptions['options'],
				'title' => $groupOptions['title'],
				'key' => $rootGroupKey,
				// Define menu order from declaration order
				'menu_order' => $groupPosition,
				'style' => $groupOptions["seamless"] ? "seamless" : "default",
				// Attach to document locations
				'location' => array_map( fn (Location $location) => $location->get(), $blueprint->location ),
				'fields' => $fields,
			]);
		}
		// If we have info on field group orders
//		if ( isset($orderHookName) ) {
//			if ( !isset(self::$__allFieldGroupOrders[$orderHookName]) )
//				self::$__allFieldGroupOrders[ $orderHookName ] = [];
//			// Add them by custom post type
//			// We do this because for the custom post type "page", we have only 1 hook
//			// So we will just concat all field orders for every pages into the CPT "pages"
//			// It works because WP admin will use only fields in current page
//			self::$__allFieldGroupOrders[ $orderHookName ][] = $fieldGroupsIDOrders;
//		}
	}

	// --------------------------------------------------------------------------- AFTER INSTALL

	/**
	 * Re-order custom items in menu.
	 * In order :
	 * - Singletons
	 * - -----
	 * - Posts
	 * - Pages
	 * - Collections
	 * - -----
	 * - ... Other options ...
	 */
	protected static function reorderMenu ( $separatorPosition ) {
		add_action( 'admin_init', function () use ($separatorPosition) {
			global $menu;
			// Sometime menu is not init at this time
			if (!$menu) return;
			$orderedMenu = [];
			// Get page section to move it after posts
			$pageSection = null;
			foreach ( $menu as $section ) {
				if ( $section[1] != "edit_pages" ) continue;
				$pageSection = $section;
			}
			// Browse and re-order menu
			$separatorIndex = 0;
			foreach ( $menu as $section ) {
				if ( $section[2] == "separator1" || $section[1] == "edit_pages" )
					continue;
				$isPost = $section[1] == "edit_posts" && $section[2] == "edit.php";
				if ( $isPost || $section[1] == "upload_files" && $section[2] == "upload.php" ) {
					$separatorIndex ++;
					$orderedMenu[] = ['','read',"separator$separatorIndex",'','wp-menu-separator'];
				}
				$orderedMenu[] = $section;
				if ( $isPost )
					$orderedMenu[] = $pageSection;
			}
			// Override ordered global menu
			$menu = $orderedMenu;
		});
	}

  /**
   * Patch admin title for a specific screen
   * @param SingletonBlueprint|CollectionBlueprint $blueprint
   * @return void
   */
  protected static function patchAdminCustomScreen ( SingletonBlueprint|CollectionBlueprint $blueprint ) {
    $titleClass = "h1.wp-heading-inline";
    $listSelector = ".edit-php $titleClass";
    $newSelector = ".post-new-php $titleClass";
    $editSelector = ".post-php $titleClass";
    $label = $blueprint->getMenuLabel();
    // Set titles for add or update actions
    $titles = [ "$label", "Add $label", "Edit $label" ];
    // Inject script which will inject correct title and show it
    $style = "$listSelector, $newSelector, $editSelector { opacity: 0; }";
    $script = <<<JS
      jQuery(function ($) {
        $('$listSelector').text("$titles[0]").css({ opacity: 1 });
        $('$newSelector').text("$titles[1]").css({ opacity: 1 });
        $('$editSelector').text("$titles[2]").css({ opacity: 1 });
      });
  JS;
    AdminHelper::injectCustomAdminResourceForScreen( $blueprint->id, $style, $script );
  }

	/**
	 * After function hook is listened to order field groups vertically.
	 */
//	protected static function afterFunctions () {
//		// We inject field group orders after all fields are declared
//		$allFieldGroupOrders = self::$__allFieldGroupOrders;
//    foreach ( $allFieldGroupOrders as $orderHookName => $fieldGroupOrders ) {
//      // Concat all field groups orders for this custom post type
//      $allFieldGroupOrdersForHook = [];
//      foreach ( $fieldGroupOrders as $currentFieldGroupOrder )
//        $allFieldGroupOrdersForHook = array_merge($allFieldGroupOrdersForHook, $currentFieldGroupOrder);
//      // Hook meta box order for this custom post type
//      $hookName = 'get_user_option_meta-box-order_'.$orderHookName;
//      add_filter($hookName , function () use ($allFieldGroupOrdersForHook) {
//        return [
//          // Force order with Yoast on top
//          'normal' => join(',', array_merge(
//            [ 'wpseo_meta' ], // fixme configurable
//            $allFieldGroupOrdersForHook
//          ))
//        ];
//      });
//    }
//	}

	// --------------------------------------------------------------------------- GET BLUEPRINTS

  /**
   * Get matching blueprints
   * @param string $type Type of blueprint can be "post" / "page" / "collection" / "singleton"
   * @param string|null $name Name of the blueprint, for pages it represent the page template name
   * @param string|null $blueprintID Or get with blueprint ID if name is omitted
   * @return AbstractBlueprint[]
   * @throws \Exception
   */
  static function getMatchingBlueprints ( string $type, string $name = null, string $blueprintID = null ) : array {
    if ( !in_array($type, ["post", "page", "collection", "singleton"]) ) {
      throw new \Exception("Invalid type $type");
    }
    return array_values(
      array_filter(
        self::$__installedBlueprints,
        fn ($blueprint) => (
          $blueprint->type === $type
          && (
            (is_null($name) || $blueprint->name === $name)
            || ($type === "page" && empty($blueprint->name) )
          )
          && (is_null($blueprintID) || $blueprint->id === $blueprintID)
        )
      )
    );
  }

  /**
   * Get matching blueprints for a WP post instance
   * @param WP_Post $post
   * @return AbstractBlueprint[]
   * @throws \Exception
   */
  static function getMatchingBlueprintsForPost ( WP_Post $post ) : array {
    $type = $post->post_type;
    if ( $type === "page" ) {
      $name = $post->page_template;
    } else if ( $type === "post" ) {
      $name = "";
    } else {
      $name = $type;
      $type = "collection";
    }
    return self::getMatchingBlueprints($type, $name);
  }

  /**
   * Extract ACF locations from a blueprint list
   * @param array $blueprints
   * @return array
   */
  static function getLocationsOfBlueprints ( array $blueprints ) : array {
    $locations = [];
    foreach ( $blueprints as $blueprint )
      $locations = [ ...$locations, ...$blueprint->location ];
    /** @var $l Location */
    return array_map(fn ($l) => $l->get(), $locations );
  }

  /**
   * Get all multilang blueprints
   * @return AbstractBlueprint[]
   */
  static function getMultilangBlueprints ( bool $excludeForcedLocales = false ) : array  {
    $blueprints = [];
    /** @var AbstractBlueprint $blueprint */
    foreach ( self::$__installedBlueprints as $blueprint )
      if ( $blueprint->getMultilang() && (!$excludeForcedLocales || !$blueprint->getMultilangForceAllLocales()) )
        $blueprints = [ ...$blueprints, $blueprint ];
    return $blueprints;
  }

  /**
   * Get post types of all multilang blueprints
   * @return string[]
   */
  static function getMultilangPostTypes () : array {
    $postTypes = [];
    /** @var AbstractBlueprint $blueprint */
    foreach ( self::$__installedBlueprints as $blueprint ) {
      if ( !$blueprint->getMultilang() )
        continue;
      $type = $blueprint->type;
      if ( $type === "singleton" )
        continue;
      if ( $type === "collection" )
        $type = $blueprint->name;
      if ( !in_array($type, $postTypes) )
        $postTypes[] = $type;
    }
    return $postTypes;
  }

  /**
   * Get post types of all orderable blueprints
   * @return string[]
   */
  static function getOrderablePostTypes () : array {
    $postTypes = [];
    /** @var AbstractBlueprint $blueprint */
    foreach ( self::$__installedBlueprints as $blueprint ) {
      if (
        $blueprint instanceof CollectionBlueprint
        || $blueprint instanceof PageBlueprint
        || $blueprint instanceof PostBlueprint
      ) {
        if ( !$blueprint->getOrderable() )
          continue;
        $type = $blueprint->type;
        if ( $type === "collection" )
          $type = $blueprint->name;
        if ( !in_array($type, $postTypes) )
          $postTypes[] = $type;
      }
    }
    return $postTypes;
  }

  /**
   * Get a singleton blueprint from its name
   * @param string $name
   * @return SingletonBlueprint|null
   */
  static function getInstalledSingletonByName ( string $name ) :? SingletonBlueprint {
    /** @var AbstractBlueprint $blueprint */
    foreach ( self::$__installedBlueprints as $blueprint )
      if ( $blueprint instanceof SingletonBlueprint && $blueprint->name === $name )
        return $blueprint;
    return null;
  }
}
