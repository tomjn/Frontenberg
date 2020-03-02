<?php

get_header();
?>
<div id="wpwrap">
	<div id="adminmenumain" role="navigation" aria-label="Main menu">
		<a href="#wpbody-content" class="screen-reader-shortcut">Skip to main content</a>
		<a href="#wp-toolbar" class="screen-reader-shortcut">Skip to toolbar</a>
		<div id="adminmenuback"></div>
		<div id="adminmenuwrap">
			<ul id="adminmenu">
				<li class="wp-not-current-submenu menu-top menu-icon-performance menu-top-last" id="menu-comments">
					<a href="#" class="wp-not-current-submenu menu-top menu-icon-performance menu-top-last">
						<div class="wp-menu-arrow">
							<div></div>
						</div>
						<div class="wp-menu-image dashicons-before dashicons-performance"><br></div>
						<div class="wp-menu-name"><?php echo esc_html( frontenberg_get_block_editor_version() ); ?></div>
					</a>
				</li>
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
			<div id="wpbody-content">
				<div class="block-editor">
					<h1 class="screen-reader-text hide-if-no-js">Edit Post</h1>
					<div id="editor" class="block-editor__container hide-if-no-js"></div>
					<div id="metaboxes" class="hidden">
						<?php /*
						this is commented out because it causes a fatal with do_metaboxes not being found
						the_block_editor_meta_boxes(); */ ?>
					</div>
					<div class="wrap hide-if-js block-editor-no-js">
						<h1 class="wp-heading-inline">Edit Post</h1>
						<div class="notice notice-error notice-alt">
							<p>The block editor requires JavaScript. Please enable JavaScript in your browser settings</p>
						</div>
					</div>
				</div>
			<div class="clear"></div></div><!-- wpbody-content -->
		<div class="clear"></div></div><!-- wpbody -->
	<div class="clear"></div></div><!-- wpcontent -->
<div class="clear"></div></div><!-- wpwrap -->

<?php

get_footer();
