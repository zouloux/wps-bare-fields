<?php

namespace BareFields\blueprints\structs;

use BareFields\blueprints\abstract\AbstractBlueprint;
use BareFields\blueprints\abstract\BlueprintMenu;
use BareFields\blueprints\abstract\BlueprintOptions;
use BareFields\blueprints\abstract\BlueprintOrderable;
use BareFields\blueprints\abstract\BlueprintSubPaths;

class CollectionBlueprint extends AbstractBlueprint
{
  // --------------------------------------------------------------------------- TRAITS

  use BlueprintMenu;
  use BlueprintOptions;
  use BlueprintOrderable;
  use BlueprintSubPaths;

  // --------------------------------------------------------------------------- CONSTRUCT

  public static function create ( string $name ) : static {
    return new static( $name );
  }

  public function __construct ( string $name ) {
    parent::__construct( "collection", $name );
    $this->_menuLabel   = $name;
    $this->_menuTitle   = $name;
    $this->_menuPosition = 6;
  }

  // --------------------------------------------------------------------------- SHOW IN PAGES

  protected bool $_showInPages = true;
  public function getShowInPages () : bool { return $this->_showInPages; }

  /**
   * CustomPostType will have a href if true and will be publicly available
   * @param bool $value
   * @return $this
   */
  public function showInPages ( bool $value = true ) : CollectionBlueprint {
    $this->_showInPages = $value;
    return $this;
  }

  // --------------------------------------------------------------------------- SHOW IN REST

  protected bool $_showInRest = true;
  public function getShowInRest () : bool { return $this->_showInRest; }

  public function showInRest ( bool $value = true ) : CollectionBlueprint {
    $this->_showInRest = $value;
    return $this;
  }

  // --------------------------------------------------------------------------- SHOW IN ADMIN UI

  protected bool $_showInAdminUI = true;
  public function getShowInAdminUI () : bool { return $this->_showInAdminUI; }

  public function showInAdminUI ( bool $value = true ) : CollectionBlueprint {
    $this->_showInAdminUI = $value;
    return $this;
  }

  // --------------------------------------------------------------------------- SLUG

  protected string $_slug = "";
  public function getSlug () : string { return $this->_slug; }

  public function slug ( string $slug ) : CollectionBlueprint {
    $this->_slug = $slug;
    return $this;
  }

  // --------------------------------------------------------------------------- LIST COLUMN

  public function listColumn ( string $columnTitle, string $width, callable $handler ) {
    $name = $this->name;
    $columnSlug = acf_slugify($columnTitle);
    add_filter("admin_head", function () use ($columnSlug, $width) {
      echo "<style>.column-".$columnSlug."{ width: $width }</style>";
    });
    add_filter("manage_edit-{$name}_columns", function ( $columns ) use ($columnSlug, $columnTitle) {
      $columns[$columnSlug] = $columnTitle;
      return $columns;
    });
    add_action("manage_{$name}_posts_custom_column", function ( $columnName, $postID ) use ( $columnSlug, $handler ) {
      if ( $columnName === $columnSlug ) {
        $return = $handler($postID);
        if ( is_string($return) )
          echo $return;
      }
    }, 10, 2);
  }

	// --------------------------------------------------------------------------- LIST COLUMN FILTER

	/**
	 * @param string $columnTitle Column name in table's header
	 * @param string $width Column width in table's header
	 * @param string $selectorDefaultLabel First and default label of the filter selector
	 * @param callable $valuesHandler Handler to get associative array [key => value] of all possible filtering values.
	 * @param string $filterKey ACF key to filter the document. With underscores for deep elements.
	 * @param callable $handler $postId => $filterKey or $postId => $filterKeys[]. Return null to disable link.
	 * @param boolean $strictSearch apply strict equal or "like" as SQL request on field. Default is true.
	 * @return void
	 */
	public function listColumnFilter ( string $columnTitle, string $width, string $selectorDefaultLabel, callable $valuesHandler, string $filterKey, callable $handler, bool $strictSearch = true ) {
		$collectionName = $this->name;
		$columnSlug = acf_slugify($columnTitle);
		$filterName = "{$collectionName}_{$columnSlug}_filter";

		$this->listColumn($columnTitle, $width, function ($id) use ($handler, $collectionName, $filterName, $valuesHandler) {
			$values = $valuesHandler();
			$selectedKey = $handler($id);
			if ( empty($selectedKey) )
				return "-";
			if ( !is_array($selectedKey) )
				$selectedKey = [$selectedKey];
			$links = [];
			foreach ($selectedKey as $k) {
				$value = $values[$k] ?? "";
				if ( empty($value) ) {
					$links[] = "-";
					continue;
				}
				$baseUrl = admin_url("edit.php?post_type={$collectionName}");
				$filterUrl = add_query_arg($filterName, $k, $baseUrl);
				$links[] = '<a href="'.esc_url($filterUrl).'">'.esc_html($value).'</a>';
			}
			return implode(", ", $links);
		});

		// Add filter dropdown to the admin screen
		add_action('restrict_manage_posts', function ($postType) use ($collectionName, $valuesHandler, $filterName, $selectorDefaultLabel) {
			$values = $valuesHandler();
			// Only apply to our custom post type
			if ( $postType !== $collectionName || empty($values) )
				return;
			// Get the current filter value
			$current_filter = (
				isset($_GET[$filterName])
				? sanitize_text_field($_GET[$filterName])
				: ''
			);
			// Print dropdown
			?>
			<select name="<?php echo $filterName ?>">
				<option value=""><?php echo $selectorDefaultLabel ?></option>
				<?php foreach ( $values as $key => $name ) : ?>
					<option value="<?php echo esc_attr($key); ?>" <?php selected($current_filter, $key); ?>>
						<?php echo esc_html($name); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
		}, 10, 1);

		// Filter the posts based on the selected filter value
		add_filter('pre_get_posts', function ($query) use($collectionName, $filterName, $filterKey, $strictSearch) {
			// Only run in admin and for main query
			if ( !is_admin() || !$query->is_main_query() )
				return $query;
			// Only apply to our custom post type
			if ( !isset($query->query['post_type']) || $query->query['post_type'] !== $collectionName )
				return $query;
			// Check if we have a filter
			if ( empty($_GET[$filterName]) )
				return $query;
			$filter_value = sanitize_text_field($_GET[$filterName]);
			$fieldKey = $collectionName."___".$filterKey;
			if ( $strictSearch ) {
				$queryArguments = [
					'compare' => '=',
					'value' => sanitize_text_field($_GET[$filterName]),
				];
			} else {
				$queryArguments = [
					'compare' => 'LIKE',
					'value' => '%'.$filter_value.'%',
				];
			}
			$query->set('meta_query', [
				[
					'key' => $fieldKey,
					...$queryArguments,
				]
			]);
			return $query;
		});

	}

}
