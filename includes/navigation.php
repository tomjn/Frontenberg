<?php

namespace frontenberg\navigation;

function bootstrap() : void {
	add_action( 'after_setup_theme', __NAMESPACE__ . '\\register_my_menu' );
}

/**
 * Register a sidebar menu.
 *
 * @return void
 */
function register_menus() {
	register_nav_menu( 'sidebar', __( 'Side Menu', 'frontenberg' ) );
}
