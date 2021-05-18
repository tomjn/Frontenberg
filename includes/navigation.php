<?php
/**
 * Nav menus.
 *
 * @package tomjn/frontenberg
 */

namespace frontenberg\navigation;

/**
 * Sets up navigation related code.
 *
 * @return void
 */
function bootstrap() : void {
	add_action( 'after_setup_theme', __NAMESPACE__ . '\\register_menus' );
	add_action( 'rest_endpoints', __NAMESPACE__ . '\\rest_endpoints' );
}

/**
 * Register a sidebar menu.
 *
 * @return void
 */
function register_menus() {
	register_nav_menu( 'sidebar', __( 'Side Menu', 'frontenberg' ) );
}

/**
 * Override the GET peermission callbacks.
 *
 * @param array $endpoints registered rest api endpoints.
 * @return array
 */
function rest_endpoints( array $endpoints ) : array {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}
	$whitelisted = [
		'/__experimental/menu-items',
		'/__experimental/menu-items/(?P<id>[\\d]+)',
		'/wp/v2/menu-items/(?P<id>[\\d]+)/autosaves',
		'/wp/v2/menu-items/(?P<parent>[\\d]+)/autosaves/(?P<id>[\\d]+)',
		'/__experimental/menu-locations',
		'/__experimental/menu-locations/(?P<location>[\\w-]+)',
		'/__experimental/menus',
		'/__experimental/menus/(?P<id>[\\d]+)',
	];
	foreach ( $whitelisted as $endpoint ) {
		if ( empty( $endpoints[ $endpoint ] ) ) {
			continue;
		}
		$methods = $endpoints[ $endpoint ];
		foreach ( $methods as $key => $method ) {
			if ( isset( $method['permission_callback'] ) ) {
				if ( 'GET' === $method['methods'] ) {
					$methods[ $key ]['permission_callback'] = '__return_true';
				}
			}
		}
		$endpoints[ $endpoint ] = $methods;
	}

	return $endpoints;
}
