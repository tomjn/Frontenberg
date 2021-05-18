<?php
/**
 * Widgets related code.
 *
 * @package tomjn/frontenberg
 */

namespace frontenberg\widgets;

/**
 * Add hooks and filters.
 *
 * @return void
 */
function bootstrap() : void {
	add_action( 'widgets_init', __NAMESPACE__ . '\\widgets_init' );
	add_action( 'rest_endpoints', __NAMESPACE__ . '\\rest_endpoints' );
}

/**
 * Register sidebars
 *
 * @return void
 */
function widgets_init() : void {
	register_sidebar(
		[
			'name'          => __( 'Main Sidebar', 'frontenberg' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'Widgets in this area will be shown on all posts and pages.', 'frontenberg' ),
			'before_widget' => '<li id="%1$s" class="widget %2$s">',
			'after_widget'  => '</li>',
			'before_title'  => '<h2 class="widgettitle">',
			'after_title'   => '</h2>',
		]
	);

	register_sidebar(
		[
			'name'          => __( 'Secondary Sidebar', 'frontenberg' ),
			'id'            => 'sidebar-2',
			'description'   => __( 'A second sidebar widget area.', 'frontenberg' ),
			'before_widget' => '<li id="%1$s" class="widget %2$s">',
			'after_widget'  => '</li>',
			'before_title'  => '<h2 class="widgettitle">',
			'after_title'   => '</h2>',
		]
	);
}

/**
 * Override the GET permission callbacks.
 *
 * @param array $endpoints registered rest api endpoints.
 * @return array
 */
function rest_endpoints( array $endpoints ) : array {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}
	$whitelisted = [
		'/wp/v2/sidebars',
		'/wp/v2/sidebars/(?P<id>[\\w-]+)',
		'/wp/v2/widgets',
		'/wp/v2/widgets/(?P<id>[\\w\\-]+)',
		'/wp/v2/widget-types',
		'/wp/v2/widget-types/(?P<id>[a-zA-Z0-9_-]+)',
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
