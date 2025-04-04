<?php

function bare_fields_cache_clear () {
	if ( !class_exists("\Nano\helpers\Cache") )
		return;
	if ( \Nano\helpers\Cache::isActive() )
		\Nano\helpers\Cache::clear();
}

if ( class_exists("\Nano\helpers\Cache") && \Nano\helpers\Cache::isActive() ) {
  // todo : check if orderable triggers a cache clean, i don't think so
  // todo : add define killswitch
  // Clear Nano APCU cache on post and options saving
  add_action( 'save_post', "bare_fields_cache_clear", 0);
  add_action( 'updated_option', function ($key) {
    // Weird, we need to filter out those that can be fired in front for no reason
    if ( str_starts_with($key, "_site_transient_timeout_wp_theme_files_patterns") || !is_admin())
      return;
    bare_fields_cache_clear();
  }, 0, 1);
  $__bareFieldsClearCacheParam = "clear-cache";
  add_action('admin_bar_menu', function ( $adminBar ) use ( $__bareFieldsClearCacheParam ) {
    $adminBar->add_node([
      'id' => 'clear-cache',
      'title' => 'Clear cache',
      'href' => '-',
      'meta' => [
        'class' => 'clear-cache-top-button',
        'onclick' => implode("", [
          'if (!confirm("Are you sure to clear '.\Nano\helpers\Cache::count().' elements ?")) return false;',
          'event.preventDefault();',
          'fetch(location.pathname + "?' . $__bareFieldsClearCacheParam . '=1")',
          '.then( async r => alert(await r.text()));',
        ])
      ]
    ]);
  }, 50);

  // todo catch it
  if ( is_admin() && isset($_GET[$__bareFieldsClearCacheParam]) && $_GET[$__bareFieldsClearCacheParam] === "1" ) {
    bare_fields_cache_clear();
    die("Done");
  }
}
