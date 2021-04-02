<?php

namespace frontenberg\widgets;

function bootstrap() : void {
	add_action( 'widgets_init', __NAMESPACE__ . '\\widgets_init' );
}

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
}
