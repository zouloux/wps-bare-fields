<?php

namespace BareFields\admin;

use BareFields\helpers\AdminHelper;

class AdminTemplates
{

	// --------------------------------------------------------------------------- INIT

	// Template root path, relative to app root.
	protected static string $__templateRootPath;

	static function init ( string $templateRootPath ) {
		self::$__templateRootPath = $templateRootPath;
		// Load admin related css files
		AdminHelper::injectStyleFile(WPS_BARE_FIELDS_PLUGIN_DIR . 'assets/admin-templates.css');
	}

	// --------------------------------------------------------------------------- RENDER VIEW

	protected static \Closure|null $__registeredViewHandlers = null;

	static function renderView ( string $view, array $variables ) {
		$path = self::$__templateRootPath . $view . ".php";
		$realPath = realpath($path);
		$rootPath = dirname(realpath(self::$__templateRootPath));
		if ( !file_exists($path) || !$realPath || !str_starts_with($realPath, $rootPath) )
			throw new \Exception("Template not found: $path");
		require_once $realPath;
		if ( is_null(static::$__registeredViewHandlers) || !is_callable(static::$__registeredViewHandlers) )
			throw new \Exception("Template not registered: $path");
		$output = ( static::$__registeredViewHandlers )($variables);
		// fixme : do something with output ? echo it ?
		self::$__registeredViewHandlers = null;
	}


	static function registerView ( \Closure $handler ) {
		self::$__registeredViewHandlers = $handler;
	}

	// ---------------------------------------------------------------------------

	static function start () {
		if ( !is_admin() ) {
			self::showError("Not authorized");
			exit;
		}
		// Load Nano app without starting it
		if ( class_exists('\Nano\core\App') )
			\Nano\core\App::load();
		// Get query parameters
		$id = empty($_GET[ 'id' ]) ? -1 : intval($_GET[ 'id' ]);
		$query = trim(stripslashes($_GET[ 'query' ] ?? ""));
		$pageIndex = intval($_GET[ 'page-index' ] ?? "0");
		// Connected user
		$user = wp_get_current_user();
		//
		return [
			"id" => $id,
			"query" => $query,
			"pageIndex" => $pageIndex,
			"user" => $user,
		];
	}

	static function showError ( string $message ) {
		?>
		<div class="wrap"><h1>
				<?php echo htmlentities($message); ?>
			</h1></div>
		<?php
	}

	// --------------------------------------------------------------------------- ADMIN COMPONENTS

	/**
	 * Render admin page
	 * @param string|null $backHref - URL for the back button (optional)
	 * @param string|null $title - Title to display (optional)
	 * @param callable|null $header - Function to render header content (optional)
	 * @param callable|null $right - Function to render right content (optional)
	 * @return \Closure Call to close page tags
	 */
	static function renderAdminPage (
		?string   $backHref, ?string $title,
		?callable $header = null, ?callable $right = null
	) {
		?>
		<div class="wrap">
		<div style="display: flex; gap: 20px; align-items: center;">
			<?php if ( !empty($backHref) ) : ?>
				<a class="button button-important"
					 href="<?php echo $backHref === "auto" ? "javascript:navigation.back()" : $backHref; ?>">
					<span class="dashicons dashicons-arrow-left-alt2"></span><span>Back</span>
				</a>
			<?php endif; ?>

			<?php if ( !empty($title) ) : ?>
				<h1><?php echo $title; ?></h1>
			<?php endif; ?>

			<span style="flex-grow: 1;"></span>

			<?php if ( !empty($header) ) : echo $header(); endif; ?>

			<?php if ( !empty($right) ) : ?>
				<span style="flex-grow: 1;"></span>
				<?php echo $right(); ?>
			<?php endif; ?>
		</div>
		<div id="poststuff">
		<?php
		return function () {
			echo "</div></div>";
		};
	}

	/**
	 * Render table list
	 * @param array $rows
	 * @param callable $renderRow
	 * @param array $header - Array of strings for the table headers
	 * @param array|null $columnWidths - Optional array of numbers for column widths
	 */
	static function renderTableList ( array $rows, callable $renderRow, array $header, ?array $columnWidths = null ) {
		?>
		<table class="widefat fixed striped AdminTable">
			<thead>
			<tr>
				<?php foreach ( $header as $index => $headerItem ): ?>
					<?php
						$style = "";
						if ( isset($columnWidths) && $columnWidths[ $index ] > 0 ) {
							$w = $columnWidths[ $index ];
							$style = "width: {$w}px;";
						}
					?>
					<th style="<?php echo $style ?>">
						<?php echo $headerItem; ?>
					</th>
				<?php endforeach; ?>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $rows as $row ): ?>
				<?php $columns = $renderRow($row); ?>
				<tr>
					<?php foreach ( $columns as $column ): ?>
						<td class="AdminTable_cell"><?php echo $column; ?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Paginate
	 * @param int $totalPages - The total number of pages
	 * @param int $totalCount - The total count of items
	 * @param int $pageIndex - The current page index
	 */
	static function renderPaginate ( int $totalPages, int $totalCount, int $pageIndex )
	{
		?>
		<div class="AdminPaginate">
		<div class="AdminPaginate_container">
			<?php if ( $pageIndex > 0 ) : ?>
				<?php for ( $i = 0; $i < $totalPages; ++$i ) : ?>
					<?php
					$currentUrl = $_SERVER[ 'REQUEST_URI' ];
					$pageUrl = remove_query_arg('page-index', $currentUrl);
					$pageUrl = add_query_arg('page-index', $i, $pageUrl);
					?>
					<a
						class="button <?php echo ( $i == $pageIndex ) ? 'button-primary' : ''; ?>"
						href="<?php echo $pageUrl; ?>"
					>
						<?php echo $i + 1; ?>
					</a>
				<?php endfor; ?>
			<?php endif; ?>
			<div class="AdminPaginate_more">
				<?php
				$itemsPerPage = 20; // Default items per page
				$startItem = ( $pageIndex * $itemsPerPage ) + 1;
				$endItem = min(( $pageIndex + 1 ) * $itemsPerPage, $totalCount);
				echo "{$startItem}-{$endItem} of {$totalCount} items";
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render grid
	 * @param array $elements - 2D array of elements to display in the grid
	 * @param array|null $columnWidths - Optional array of column widths
	 */
	static function renderGrid ( array $elements, ?array $columnWidths = null ) {
		$longest = 0;
		$filteredElements = [];
		// Filter out empty elements and find the longest row
		foreach ( $elements as $e ) {
			if ( $e ) {
				$filteredElements[] = $e;
				if ( count($e) > $longest ) {
					$longest = count($e);
				}
			}
		}
		?>
		<table class="form-table AdminGrid" role="presentation">
			<thead style="visibility: collapse;">
			<tr>
				<?php if ( isset($columnWidths) && $columnWidths ): ?>
					<?php foreach ( $columnWidths as $width ): ?>
						<th style="width: <?php echo $width === null ? 'auto' : $width . 'px'; ?>; padding: 0;"></th>
					<?php endforeach; ?>
				<?php endif; ?>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $filteredElements as $element ): ?>
				<tr>
					<?php
					$count = count($element);
					foreach ( $element as $index => $col ):
						$isLast = $index === $count - 1;
						$colspan = $isLast ? $longest - $count + 1 : 1;
						?>
						<td colspan="<?php echo $colspan; ?>">
							<?php echo $col; ?>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render post box
	 * @param string $title - The title of the post box
	 * @param boolean $noInside - Optional boolean to determine if the content should be wrapped in a div with class "inside"
	 * @param array $style - Optional array for additional styles
	 * @param boolean $alert - Optional boolean to add alert styling (orange dashed border)
	 */
	static function renderPostBox ( string $title, bool $noInside = false, array $style = [], bool $alert = false ) {
		$finalStyle = $style ?: [];
		if ( $alert ) {
			$finalStyle = array_merge($finalStyle, [
				'border' => '2px dashed orange',
				'border-radius' => '4px'
			]);
		}
		$styleString = '';
		foreach ( $finalStyle as $key => $value )
			$styleString .= $key . ':' . $value . ';';
		?>
		<div class="postbox AdminPostBox" <?php if ( $styleString ): ?>style="<?php echo $styleString; ?>"<?php endif; ?>>
			<div class="postbox-header">
				<h2><?php echo $title; ?></h2>
			</div>
			<?php if ( !$noInside ): ?>
			<div class="inside">
			<?php endif; ?>
		<?php
		// Close post box
		return function ( ?callable $footer = null ) use ( $noInside ) {
			// Add a customer footer
			if ( !is_null($footer) ) {
				?>
				<div class="AdminPostBox_footer <?php if ( $noInside ): ?>AdminPostBox_footer__noInside<?php endif; ?>">
				<?php
				$footer();
				echo "</div>";
			}
			if ( !$noInside )
				echo "</div>";
			echo "</div>";
		};
	}
}
