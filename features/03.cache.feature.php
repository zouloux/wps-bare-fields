<?php

function bare_fields_document_cache_clear () {
	if ( !class_exists("\Nano\helpers\Cache") )
		return;
	if ( !\Nano\helpers\Cache::hasInstance("documents") )
		return;
	$instance = \Nano\helpers\Cache::getInstance("documents");
	if ( !$instance->isActive() )
		return;
	$instance->clear();
}

if ( class_exists("\Nano\helpers\Cache") ) {
	// Browse all instances
	$instances = \Nano\helpers\Cache::getInstances();
	/** @var \Nano\helpers\Cache $cache */
	foreach ($instances as $key => $cache) {
		// todo : check if orderable triggers a cache clean, i don't think so
		if ( !$cache->isActive() )
			continue;
		// Clear Nano APCU cache on post and options saving
		if ( $key === "documents" ) {
			add_action( 'save_post', "bare_fields_document_cache_clear", 0);
			add_action( 'updated_option', function ($key) {
				// Weird, we need to filter out those that can be fired in front for no reason
				if ( str_starts_with($key, "_site_transient_timeout_wp_theme_files_patterns") || !is_admin())
					return;
				bare_fields_document_cache_clear();
			}, 0, 1);
		}
		$cacheName = "clear-$key-cache";
		add_action('admin_bar_menu', function ( $adminBar ) use ( $key, $cacheName, $cache ) {
			$title = "Clear $key cache";
			$adminBar->add_node([
				'id' => $cacheName,
				'title' => $title,
				'href' => '-',
				'meta' => [
					'class' => 'clear-cache-top-button',
					'onclick' => implode("", [
//						'if (!confirm("Are you sure to clear '.\Nano\helpers\Cache::count().' elements ?")) return false;',
						'event.preventDefault();',
						'fetch(location.pathname + "?'.$cacheName.'=1")',
						'.then( async r => alert(await r.text()));',
					])
				]
			]);
		}, 50);
		// todo do it better
		if ( is_admin() && isset($_GET[$cacheName]) && $_GET[$cacheName] === "1" ) {
			$cache->clear();
			die("Done");
		}
	}
}
