<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

get_header(); ?>

<div id="wpwrap">
	<div id="adminmenumain" role="navigation" aria-label="Main menu">
		<a href="#wpbody-content" class="screen-reader-shortcut">Skip to main content</a>
		<a href="#wp-toolbar" class="screen-reader-shortcut">Skip to toolbar</a>
		<div id="adminmenuback"></div>
		<div id="adminmenuwrap">
			<ul id="adminmenu">
				<li class="wp-has-submenu wp-not-current-submenu menu-top toplevel_page_gutenberg menu-top-last" id="toplevel_page_gutenberg"><a href="https://github.com/tomjn/Frontenberg" class="wp-has-submenu wp-not-current-submenu menu-top toplevel_page_gutenberg menu-top-last" aria-haspopup="true"><div class="wp-menu-arrow"><div></div></div><div class="wp-menu-image dashicons-before dashicons-edit"><br></div><div class="wp-menu-name">Frontenberg</div></a></li>
				<li class="wp-not-current-submenu menu-top menu-icon-comments menu-top-last" id="menu-comments">
	<a href="https://tomjn.com" class="wp-not-current-submenu menu-top menu-icon-comments menu-top-last"><div class="wp-menu-arrow"><div></div></div><div class="wp-menu-image dashicons-before dashicons-admin-site"><br></div><div class="wp-menu-name">tomjn.com</div></a></li>
			</ul>
		</div>
	</div>
	<div id="wpcontent">
		<div id="wpbody" role="main">
			<div id="wpbody-content" aria-label="Main content" tabindex="0">
				<div class="nvda-temp-fix screen-reader-text">&nbsp;</div>
				<div class="gutenberg">
					<div id="editor" class="gutenberg__editor"></div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer();
