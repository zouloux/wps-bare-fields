<?php

namespace BareFields\helpers;

class AdminHelper
{
  // --------------------------------------------------------------------------- DISABLE ADMIN PAGES

  /**
   * Redirect any admin page to the admin home.
   * Useful to disable access to specific pages.
   * $url should be something like "options-reading.php".
   * @param string $url starting URL of the admin page to disable.
   */
  public static function disableAdminPage ( string $url ) {
    add_action('admin_init', function () use ($url) {
      if ( stripos($_SERVER['REQUEST_URI'], $url) !== false ) {
        wp_redirect(admin_url());
        exit;
      }
    }, 999);
  }

  // --------------------------------------------------------------------------- ADMIN RESOURCES

  // Inject custom CSS / JS for a screen ID (page / options / etc ...)
  public static function injectCustomAdminResourceForScreen ( $screenID, $style = null, $script = null) {
    add_action('admin_head', function () use ($screenID, $style, $script) {
      $screen = get_current_screen();
      if ( !is_null($screenID) && $screen->id != $screenID ) return;
      if ( !is_null($style) )   echo '<style>'.$style.'</style>';
      if ( !is_null($script) )  echo '<script type="text/javascript">'.$script.'</script>';
    });
  }

  public static function injectStyleFile ( string $path ) {
    $html = '<link rel="stylesheet" href="'.$path.'" />';
    self::injectResource($html, "header");
  }

	public static function injectInlineStyle ( mixed $style ) {
		if ( is_array($style) )
			$style = implode("\n", $style);
    self::injectResource('<style>'.$style.'</style>', "header");
	}

  public static function injectScriptFile ( string $path ) {
    self::injectResource('<script src="'.$path.'"></script>', "footer");
  }

	public static function injectInlineScript ( mixed $script ) {
		if ( is_array($script) )
			$script = implode("\n", $script);
		self::injectResource('<script>'.$script.'</script>', "footer");
	}

	public static function injectResource ( string $html, string $location ) {
		if ( $location === "header" )
			$location = "admin_head";
		else
			$location = "admin_footer";
    if ( current_action() === $location ) {
      echo $html;
			exit;
		}
		add_action($location, function () use ($html) {
			echo $html;
		});
	}

  // --------------------------------------------------------------------------- EDITOR & EXCERPT

  public static function removeFieldForPost ( $postType, $field, $postID = null ) {
    add_filter( 'admin_head', function () use ($postType, $postID, $field) {
      global $post;
      if ( is_null($post) ) return;
      if ( !is_null($postID) && $post->ID != $postID ) return;
      if ( $postType != $post->post_type ) return;
      remove_post_type_support( $postType, $field );
    });
  }

  // --------------------------------------------------------------------------- ADMIN MENU SEPARATOR

  public static function adminMenuAddSeparator ( $position ) {
    add_action('admin_menu', function () use ( $position ) {
      global $menu;
      $menu[$position] = ['', 'read', "separator$position", '', 'wp-menu-separator'];
    });
  }

  // --------------------------------------------------------------------------- ADMIN MENU MASTER

  /**
   * Warning : For some reason, slugify($title) has to be === to $slug other screen will have a wrong path.
   * Add a master menu to group some ACF singletons
   * @param string $title
   * @param string $slug
   * @param string $icon $icon https://developer.wordpress.org/resource/dashicons/
   * @param int $position
   * @param callable|string $callable
   * @param string $capability
   * @return void
   */
  public static function adminMenuAddMasterWithoutSubmenu ( $title, $slug, $icon, $position, $callable = "", $capability = "manage_options" ) {
    add_action('admin_menu', function () use ( $title, $slug, $icon, $position, $callable, $capability ) {
      add_menu_page( $slug, $title, $capability, $slug, $callable, $icon, $position );
    });
    // Remove auto-added first, unless it's something else like a custom post type
    add_action('admin_menu', function () use ($title, $slug) {
      global $submenu;
      if ( isset($submenu[$slug][0]) && $submenu[$slug][0][0] === $title )
        unset( $submenu[$slug][0] );
    }, 999);
  }

  public static function adminMenuHierarchy ( $title, $slug, $icon, $position, $capability = "manage_options" ) {
    self::adminMenuAddMasterWithoutSubmenu( $title, $slug, $icon, $position, "", $capability );
    return function ( $title, $callable, $subSlug = null, $subCapability = null ) use ( $slug, $capability ) {
      $subSlug ??= acf_slugify( $title );
      $subCapability ??= $capability;
      add_action('admin_menu', function () use ( $title, $callable, $subSlug, $slug, $subCapability ) {
        add_submenu_page( $slug, $title, $title, $subCapability, $subSlug, $callable );
      });
    };
  }

  public static function removeDashboardMenu ( ?string $redirectURL = null ) {
    $redirectURL ??= admin_url('edit.php?post_type=page');
    add_action('admin_menu', fn () => remove_menu_page('index.php') );
    add_action( 'admin_init', function () use ( $redirectURL ) {
      global $pagenow;
      if ( 'index.php' === $pagenow ) {
        wp_redirect( $redirectURL );
        exit;
      }
    });
  }

  // --------------------------------------------------------------------------- ADMIN MENU MASTER

  public static function adminRenderCustomPostbox ( string $title, callable $handler, array $arguments = [] ) {
    echo '<div class="postbox">';
    echo '	<div class="postbox-header"><h2>'.esc_html($title).'</h2></div>';
    echo '	<div class="inside">';
    call_user_func($handler, ...$arguments);
    echo '	</div>';
    echo '</div>';
  }

  public static function adminCustomPostboxPage ( string $title, callable $afterTitle = null, $oneColumn = false ) {
    echo '<div class="wrap">';
    echo '	<h1>'.esc_html( $title ).'</h1>';
    echo '	<div id="poststuff">';
    if ( !is_null($afterTitle) )
      $afterTitle();
    echo '		<div id="post-body" class="metabox-holder columns-'.($oneColumn ? 1 : 2).'">';
    echo '			<div id="postbox-container-1" class="postbox-container">';

    $isSidebar = false;
    return function ( string $column, callable $callback = null, string $title = "", array $arguments = [] ) use ( &$isSidebar ) {
      if ( $column === "end" ) {
        echo '			</div>';
        echo '		</div>';
        echo '		<br class="clear">';
        echo '	</div>';
        echo '</div>';
        return;
      }
      if ( !is_null($callback) )
        self::adminRenderCustomPostbox( $title, $callback, $arguments );
      if ( $column === "sidebar" && !$isSidebar ) {
        echo '</div><div id="postbox-container-2" class="postbox-container">';
        $isSidebar = true;
      }
    };
  }

  public static function isRequestComingFromAdmin () {
    $user = wp_get_current_user();
    return $user->exists() && in_array( 'administrator', $user->roles );
  }

}
