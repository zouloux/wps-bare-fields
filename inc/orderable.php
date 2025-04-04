<?php

use BareFields\blueprints\BlueprintsManager;

// Add a drag and drop handle on compatible post lists to set post order easily
// post type has to have $blueprint->orderable(); to have this feature

add_action("wp_ajax_wps_cpt_order_save", function () {
	if ( !current_user_can( 'edit_posts' ) )
		wp_die( 'Unauthorized.' );
	$postOrder = isset( $_POST['order'] ) ? array_map( 'absint', $_POST['order'] ) : [];
	global $wpdb;
	foreach ( $postOrder as $menuOrder => $postID )
		$wpdb->update( $wpdb->posts, [ 'menu_order' => $menuOrder ], [ 'ID' => $postID ] );
	wp_send_json_success();
});


add_action("admin_enqueue_scripts", function () {
	global $pagenow;
	// Only load on edit screens.
	if ( 'edit.php' !== $pagenow )
		return;
	wp_enqueue_script(
		'orderable',
		WPS_BARE_FIELDS_PLUGIN_DIR.'assets/orderable.js',
		[ 'jquery', 'jquery-ui-sortable' ],
		'1.0', true
	);
	wp_localize_script( 'orderable', 'wpsCptOrder', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
	]);
	wp_enqueue_style(
		'orderable',
		WPS_BARE_FIELDS_PLUGIN_DIR.'assets/orderable.css',
		[],
		'1.0'
	);
});


add_action("admin_init", function () {
  $orderablePostTypes = BlueprintsManager::getOrderablePostTypes();
  if ( count($orderablePostTypes) === 0 ) return;

  add_action('pre_get_posts', function ( $query ) use ( $orderablePostTypes ) {
    $postType = $query->get("post_type");
    if ( is_admin() && $query->is_main_query() && in_array($postType, $orderablePostTypes) ) {
      $query->set('orderby', 'menu_order');
      $query->set('order', 'ASC');
    }
  });

  function inject_order_column ( $columns ) {
    return [ "order" => "", ...$columns ];
  }

  function populate_order_column ( $column ) {
    if ( $column === "order" )
		  echo '<div class="BareFields_orderHandle"><span></span><span></span><span></span></div>';
  }

  // Add the column to posts, pages, and custom post types list
	foreach ( $orderablePostTypes as $type ) {
		add_filter("manage_edit-{$type}_columns", "inject_order_column");
		if ( $type === "post" || $type === "page" )
			$actionKey = "manage_{$type}s_custom_column";
		else
			$actionKey = "manage_{$type}_posts_custom_column";
		add_action($actionKey, "populate_order_column");
    // Disable other sortable columns
    add_filter("manage_edit-{$type}_sortable_columns", fn () => []);
	}
});
