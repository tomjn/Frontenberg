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
}

/**
 * Register a sidebar menu.
 *
 * @return void
 */
function register_menus() {
	register_nav_menu( 'sidebar', __( 'Side Menu', 'frontenberg' ) );
}
