<?php

get_header();

$gutenberg_version = 'Core';
if ( ! function_exists('gutenberg_editor_scripts_and_styles') ) {
	$data = get_plugin_data( WP_PLUGIN_DIR . '/gutenberg/gutenberg.php' );
	$gutenberg_version = $data['Version'];
}

?>
<div id="wpwrap">
	<div id="adminmenumain" role="navigation" aria-label="Main menu">
		<a href="#wpbody-content" class="screen-reader-shortcut">Skip to main content</a>
		<a href="#wp-toolbar" class="screen-reader-shortcut">Skip to toolbar</a>
		<div id="adminmenuback"></div>
		<div id="adminmenuwrap">
			<ul id="adminmenu">
				<li class="wp-not-current-submenu menu-top menu-icon-performance menu-top-last" id="menu-comments">
					<a href="#" class="wp-not-current-submenu menu-top menu-icon-performance menu-top-last"><div class="wp-menu-arrow"><div></div></div><div class="wp-menu-image dashicons-before dashicons-performance"><br></div><div class="wp-menu-name">Gutenberg v<?php echo esc_html( $gutenberg_version ); ?></div></a></li>
				<?php
					if ( has_nav_menu( 'sidebar' ) ) {
						wp_nav_menu( [
							'menu' => 'sidebar',
							'container' => '',
							'items_wrap' => '%3$s',
							'link_before' => '<div class="wp-menu-arrow"><div></div></div><div class="wp-menu-image dashicons-before dashicons-admin-site"><br></div><div class="wp-menu-name">',
							'link_after' => '</div>'
						] );
					}
				?>
			</ul>
		</div>
	</div>
	<div id="wpcontent">
		<div id="wpbody" role="main">
			<div id="wpbody-content" aria-label="Main content" tabindex="0">
				<div class="nvda-temp-fix screen-reader-text">&nbsp;</div>
				<div class="block-editor gutenberg">
					<div id="editor" class="block-editor__container gutenberg__editor"></div>
					<div id="metaboxes" class="hidden">
						<?php /*
						this is commented out because it causes a fatal with do_metaboxes not being found
						the_block_editor_meta_boxes(); */ ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php

get_footer();
