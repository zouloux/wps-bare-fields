<?php

use Ramsey\Uuid\Uuid;


// -----------------------------------------------------------------------------


// ----------------------------------------------------------------------------- TINY MCE EDITOR

add_action( 'customize_register', function ( $wp_customize ) {
	if ( defined('BOWL_REMOVE_THEME_CUSTOMIZE_SECTIONS') && is_array(BOWL_REMOVE_THEME_CUSTOMIZE_SECTIONS) )
		foreach ( BOWL_REMOVE_THEME_CUSTOMIZE_SECTIONS as $sectionName )
			$wp_customize->remove_section( $sectionName );
}, 30);

// Add a class to offset screen-meta if we have multilang plugin
add_filter("admin_body_class", function ($classes) {
	if ( function_exists('wpm_get_language') )
		$classes .= " has-wpm-plugin";
	return $classes;
});







// Remove ?ver= query from styles and scripts.
function _woolkit_remove_script_version(string $url): string
{
	if (is_admin()) {
		return $url;
	}

	if ($url) {
		return esc_url(remove_query_arg('ver', $url));
	}

	return $url;
}
// Remove contributor, subscriber and author roles.
function _woolkit_remove_roles(): void
{
	remove_role('author');
	remove_role('contributor');
	remove_role('subscriber');
}

// Disable attachment template loading and redirect to 404.
// WordPress 6.4 introduced an update to disable attachment pages, but this
// implementation is not as robust as the current one.
// https://github.com/joppuyo/disable-media-pages/issues/41
// https://make.wordpress.org/core/2023/10/16/changes-to-attachment-pages/
function _woolkit_attachment_redirect_not_found(): void
{
	if (is_attachment()) {
		global $wp_query;
		$wp_query->set_404();
		status_header(404);
		nocache_headers();
	}
}

// Disable attachment canonical redirect links.
function _woolkit_disable_attachment_canonical_redirect_url(string $url): string
{
	attachment_redirect_not_found();

	return $url;
}

// Disable attachment links.
add_filter('attachment_link', '_woolkit_disable_attachment_link', 10, 2);
function _woolkit_disable_attachment_link(string $url, int $id): string
{
	if ($attachment_url = wp_get_attachment_url($id)) {
		return $attachment_url;
	}

	return $url;
}



function _woolkit_clean_wp () {
	add_filter('script_loader_src', '_woolkit_remove_script_version', 15, 1);
	add_filter('style_loader_src', '_woolkit_remove_script_version', 15, 1);
	add_action('init', '_woolkit_remove_roles');
	add_filter('template_redirect', '_woolkit_attachment_redirect_not_found');
	add_filter('redirect_canonical', '_woolkit_disable_attachment_canonical_redirect_url', 0, 1);
}
