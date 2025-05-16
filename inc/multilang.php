<?php

use BareFields\blueprints\abstract\AbstractBlueprint;
use BareFields\blueprints\BlueprintsManager;
use BareFields\helpers\AdminHelper;
use BareFields\multilang\Locales;
use BareFields\multilang\Multilang;

// Manage multilang features with $blueprint->multilang()
// - adds a locale selector on compatible post lists
// - adds a locale selector on compatible post edit
// - manage multi locale fields selection
// - hook page title on post lists


// Remove language tags from title slug on save.
add_filter("pre_wp_unique_post_slug", function ( $nope, $slug, $post_id, $post_status, $post_type, $post_parent ) {
	if ( empty($slug) )
		return null;
	$post = get_post($post_id);
	if ( is_null($post) )
		return null;
	$originalTitle = $post->post_title;
	if ( empty($originalTitle) )
		return null;
	if ( $slug !== sanitize_title( $originalTitle ) )
		return null;
	$newSlug = Multilang::parseInlinedValue( $originalTitle, Locales::getDefaultLocaleKey() );
	return sanitize_title( $newSlug );
}, 100000, 6 );


add_action("init", function () {
	if ( !Locales::isMultilang() ) return;
  if ( is_admin() && Locales::isMultilang() && isset($_GET["setAdminLocale"]) ) {
    $newLocale = sanitize_text_field($_GET["setAdminLocale"]);
    Locales::writeAdminLocale($newLocale);
    wp_safe_redirect(remove_query_arg("setAdminLocale"));
    exit;
  }
});

add_filter('the_title', function ( $title ) {
	if ( !Locales::isMultilang() ) return $title;
  if ( !is_admin() ) return $title;
	$locale = Locales::readAdminLocale( false );
	return Multilang::parseInlinedValue( $title, $locale );
});


add_filter("admin_body_class", function( $classes ) {
	if ( !Locales::isMultilang() ) return $classes;
	if ( !is_admin() ) return $classes;
	$locale = Locales::readAdminLocale();
	$classes .= " BareFields_body__".$locale;
	return $classes;
});

// todo : option to disable "all" ( if too many locales )
add_action("admin_head", function () {
	if ( !Locales::isMultilang() ) return;
	if ( !is_admin() ) return;
  // Get current WP screen
  $screen = get_current_screen();
  $isMultilang = false;
  // Detect multi-lang pages to inject the locale selector
  $blueprints = [];
  // Page / post / collection listing or editing
  if ( $screen->base === "edit" || $screen->base === "post" ) {
    // Extract post type and convert to collection if needed
    $type = $screen->post_type;
    $name = null;
    if ( $type !== "page" && $type !== "post" ) {
      $name = $type;
      $type = "collection";
    }
    // Edit is listing, otherwise it's page editing
		$isListing = $screen->base === "edit";
		// For pages, if not in listing, we have to check post template
		if ( !$isListing && $type === "page" ) {
			global $post;
			$name = get_page_template_slug($post);
			if ( !$name ) $name = "";
		}
    $blueprints = BlueprintsManager::getMatchingBlueprints($type, $name);
	}
  // Singleton
  else if ( $screen->post_type === "" ) {
		$isListing = false;
    $blueprints = BlueprintsManager::getMatchingBlueprints("singleton", blueprintID: $screen->id);
		// If not found with screen which can happens in option pages in sub-menus
		// Try with old-school way of targeting
		global $pagenow;
		if ( empty($blueprints) && $pagenow === 'admin.php' && isset( $_GET['page'] ) ) {
			$pageOptionName =  $_GET['post'] ?? "";
			$blueprints = BlueprintsManager::getMatchingBlueprints("singleton", $pageOptionName);
		}
  }
	else {
		$isListing = false;
	}
  // Check if any matching blueprint is multilang
  foreach ( $blueprints as $blueprint ) {
    if ( $blueprint->getMultilang() )
      $isMultilang = true;
  }
  // Inject locale selector in DOM with its JS
	if ( !$isMultilang ) return;
  // Grab locales, inject "all" on post pages
	$locales = Locales::getLocales();
	$currentLocale = Locales::readAdminLocale( !$isListing );
	if ( !$isListing )
		$locales = [ ...$locales, "all" => "All" ];
  $localesKeys = array_keys($locales);
  // Inject multilang JS and CSS
  AdminHelper::injectStyleFile(WPS_BARE_FIELDS_PLUGIN_DIR.'assets/multilang.css');
  AdminHelper::injectScriptFile(WPS_BARE_FIELDS_PLUGIN_DIR.'assets/multilang.js');
  // Here we inject CSS to hide fields from other locale in pure CSS
  // This allows us to have it work automatically in repeaters or flexible when adding fields
  echo "<style>";
  foreach ( $locales as $localeKey => $value ) {
		$bodyClass = "BareFields_body__$localeKey";
    echo ".$bodyClass .BareFields_translatedGroup .BareFields_translatedField.BareFields_translatedField__$localeKey,";
    echo ".$bodyClass .BareFields_translatedTitle__$localeKey {";
    echo "  display: block;";
    echo "}";
    echo ".$bodyClass .BareFields_locale__all.BareFields_locale__$localeKey {";
    echo "  display: inline-flex;";
    echo "}";
  }
  echo "</style>";
	if ( count(Locales::getLocalesKeys()) > 1 ) {
		// Generate HTML for selector in menu bar
		$selectorHTML = [
			'<div class="BareFields_localeSelector">',
			...array_map(
				fn( $l ) => '<a href="'.esc_url(add_query_arg('setAdminLocale', $l)).'" data-locale="'.$l.'" class="'.($l === $currentLocale ? "selected" : "").'">'.$locales[$l].'<span></span></a>',
				$localesKeys,
			),
			'</div>',
		];
		add_action('admin_bar_menu', function ( WP_Admin_Bar $adminBar ) use ( $selectorHTML ) {
			$adminBar->add_node([
				'id' => 'localeSelector',
				'title' => implode("", $selectorHTML),
				'parent' => 'top-secondary'
			]);
		}, 2);
	}
  // Inject locale info in JS
  $api = [
    "locales" => $locales,
    "currentLocale" => $currentLocale,
    "isListing" => $isListing,
  ];
  echo "<script>window.__BareFields = ".json_encode($api)."</script>";
});


// Fields that are in non-enabled locales are manually validated
add_filter('acf/validate_value', function( $valid, $value, $field ) {
	if ( !Locales::isMultilang() ) return $valid;
  // Continue only for invalid fields
  if ( $valid )
    return $valid;
  // Get the selected locales from a specific ACF field
  $postID = get_the_ID();
  if ( is_null($postID) || $postID === false )
    $postID = $_POST["post_ID"] ?? null;
	$post = get_post( $postID );
	if ( $post ) {
		$blueprints = BlueprintsManager::getMatchingBlueprintsForPost( $post );
		$forcedAllLocales = array_filter($blueprints, fn ($b) => $b->getMultilangForceAllLocales());
		if ( count($forcedAllLocales) > 0 ) {
			return $valid;
		}
	}
  $selectedLocales = get_field('locales', $postID) ?? null;
	if ( empty($selectedLocales) ) {
		$adminLocale = Locales::readAdminLocale();
		if ( $adminLocale === "all" )
			$selectedLocales = [ ...Locales::getLocalesKeys() ];
		else
			$selectedLocales = [ $adminLocale ];
	}
  if ( !is_array($selectedLocales) || empty($selectedLocales) )
    return $valid;
  if ( !is_array($field) )
    return $valid;
  // This field has a translatable parent
  $hasTranslatableParent = Multilang::doesFieldHasTranslatedParent( $field );
//	error_log(json_encode([ "p" => $hasTranslatableParent ]));
  // Now check if this field is a disabled locale
  if ( $hasTranslatableParent ) {
    $name = $field['name'];
    $inAnotherLocale = !in_array($name, $selectedLocales);
    if ( $inAnotherLocale )
      return true;
  }
  return $valid;
}, 10, 3);


// Enable current locale when creating a new post
add_action('acf/load_field/key=locales', function( $field ) {
	if ( !Locales::isMultilang() ) return $field;
	if ( !is_admin() ) return $field;
  global $post;
  if (is_null($post))
    return $field;
	// If this post blueprint has all locales forced
	$blueprints = BlueprintsManager::getMatchingBlueprintsForPost( $post );
	$forcedAllLocales = array_filter($blueprints, fn ($b) => $b->getMultilangForceAllLocales());
	if ( count($forcedAllLocales) > 0 ) {
		$locales = Locales::getLocalesKeys();
		$field['default_value'] = $locales;
		$field['value'] = $locales;
		return $field;
	}
	// By default, we select the current locale of the admin panel
	$adminLocale = Locales::readAdminLocale();
	if ( $adminLocale === "all" )
		$defaultValue = [ ...Locales::getLocalesKeys() ];
	else
		$defaultValue = [ $adminLocale ];
  $field['default_value'] = $defaultValue;
  return $field;
});

// Add meta box to select locales in blueprints that are multilang
add_action( 'init', function () {
	if ( !Locales::isMultilang() ) return;
	if ( count(Locales::getLocalesKeys()) <= 1 ) return;
	// Get multilang blueprints but do not show the meta box on blueprints
	// that have all locales forced
  $blueprints = BlueprintsManager::getMultilangBlueprints( true );
	$locations = BlueprintsManager::getLocationsOfBlueprints( $blueprints );
  acf_add_local_field_group([
    'key'      => 'group_locales',
    'title'    => 'Multilang',
    'location' => $locations,
    'position' => 'side',
    'style'    => 'default',
    'fields'   => [
      [
        'key'           => 'locales',
        'label'         => 'This post exists in :',
        'name'          => 'locales',
        'type'          => 'checkbox',
        'choices'       => Locales::getLocales(),
        'layout'        => 'vertical',
      ],
    ],
  ]);
});

// AJAX request when selecting locales for a post
add_action( 'admin_enqueue_scripts', function() {
	if ( !Locales::isMultilang() ) return;
  wp_enqueue_script( 'acf-ajax-save', get_template_directory_uri().'/js/acf-ajax-save.js', [ 'jquery' ], null, true );
  wp_localize_script( 'acf-ajax-save', 'acfAjax', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ] );
});
add_action( 'wp_ajax_acf_save_locales', function() {
	if ( !Locales::isMultilang() ) return;
  if ( ! isset( $_POST['post_id'], $_POST['locales'] ) ) {
    wp_send_json_error();
  }
  $postId = intval( $_POST['post_id'] );
  $locales = array_map( 'sanitize_text_field', $_POST['locales'] );
  update_field( 'locales', $locales, $postId );
  wp_send_json_success();
});

// Inject translatable titles
add_action("edit_form_after_title", function () {
	if ( !Locales::isMultilang() ) return;
	$blueprints = [];
	$screen = get_current_screen();
	$title = "";
	// Get matching blueprints of this created post
	// Editing a post
	// Has to be first for the parent_base
	if ( $screen->action === "add" ) {
		$type = $screen->post_type;
		$name = "";
		if ( $type !== "page" && $type !== "post" ) {
			$name = $type;
			$type = "collection";
		}
		$blueprints = BlueprintsManager::getMatchingBlueprints( $type, $name );
	}
	// Get matching blueprints of this edited post
	else if (
		$screen->action === "edit" || $screen->parent_base === "edit"
		|| (isset($_GET["action"]) && $_GET["action"] === "edit")
	) {
		// Get post
		$postId = $_GET["post"] ?? -1;
		$post = get_post( $postId );
  	$title = $post->post_title;
		if ( is_null($post) )
			return;
		// Get associated blueprints to this post
		$blueprints = BlueprintsManager::getMatchingBlueprintsForPost( $post );
	}
  // Only continue if multilang title
  $multilangBlueprints = array_filter($blueprints, fn (AbstractBlueprint $d) => $d->getMultilangTitle() );
  if ( count($multilangBlueprints) === 0 ) return;
  // Create I title field by locale
  $locales = Locales::getLocalesKeys();
	?>
		<style>#titlewrap { display: none; }</style>
		<div class="BareFields_translatedTitles">
	<?php
  foreach ( $locales as $locale ) {
    $value = Multilang::parseInlinedValue( $title, $locale );
    ?><div class="BareFields_translatedTitle BareFields_translatedTitle__<?php echo $locale ?>">
			<span>Title<span class="BareFields_locale"><?php echo $locale ?></span></span>
			<input
				type="text"
				name="title_<?php echo $locale; ?>"
				value="<?php echo esc_attr( $value ); ?>"
				autocomplete="off"
			/>
		</div>
		<?php
  }
	?></div><?php
});

// After post has been saved, we parse title and re-save it as translated
// Cannot use save_post hook because of recursive loop
add_action("wp_after_insert_post", function ($postId) {
	if ( !Locales::isMultilang() ) return;
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
  if ( wp_is_post_revision( $postId ) ) return;
  if ( !current_user_can( 'edit_post', $postId ) ) return;
	// Check if this post has multilang title
	global $post;
	if ( !is_null($post) ) {
		$blueprints = BlueprintsManager::getMatchingBlueprintsForPost( $post );
		$multilangBlueprints = array_filter($blueprints, fn (AbstractBlueprint $d) => $d->getMultilangTitle() );
		if ( count($multilangBlueprints) === 0 ) return;
	}
	// Encode title in multilang from the custom form
  $locales = Locales::getLocalesKeys();
  $encodedTitle = '';
  foreach ( $locales as $locale ) {
    $title = $_POST["title_".$locale] ?? "";
    if ( empty($title) ) continue;
    $encodedTitle .= "[:".$locale."]".sanitize_text_field( $title );
  }
	if ( !empty($encodedTitle) )
  	$encodedTitle .= "[:]";
  wp_update_post([
    "ID" => $postId,
    "post_title" => $encodedTitle,
  ], fire_after_hooks: false); // avoid recursive loop
});



// Add locale columns in list view of admin
add_action( "init", function () {
	if ( !Locales::isMultilang() ) return;
	if ( count(Locales::getLocalesKeys()) <= 1 ) return;

  function inject_locales_column ( $columns ) {
    $columns["locales"] = "Locales";
    return $columns;
  }

  function populate_locales_column ( $column, $postID ) {
    if ( $column !== "locales" )
			return;
		$selectedLocales = get_field("locales", $postID) ?? null;
		if ( !is_array( $selectedLocales ) )
			return;
		echo "<div>";
		if ( empty($selectedLocales) )
			echo "<span class='noLocale'>no locale</span>";
		else foreach ( $selectedLocales as $locale )
			echo '<span>'.$locale.'</span>';
		echo "</div>";
  }

  // Add the column to posts, pages, and custom post types list
	$multilangPostTypes = BlueprintsManager::getMultilangPostTypes();
	foreach ( $multilangPostTypes as $type ) {
		add_filter("manage_edit-{$type}_columns", "inject_locales_column" );
		if ( $type === "post" || $type === "page" )
			$actionKey = "manage_{$type}s_custom_column";
		else
			$actionKey = "manage_{$type}_posts_custom_column";
		add_action($actionKey, "populate_locales_column", 10, 2);
	}
});

