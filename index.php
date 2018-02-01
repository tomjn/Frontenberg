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

get_header();

$data = get_plugin_data( WP_PLUGIN_DIR . '/gutenberg/gutenberg.php' );
$gutenberg_version = $data['Version'];

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
				<div class="gutenberg">
					<div id="editor" class="gutenberg__editor"></div>
					<?php
						global $wp_meta_boxes, $current_screen;
						global $post;
						do_action( 'add_metaboxes', $post->post_type, $post );
						$current_screen = WP_Screen::get('post');//'post';
						?>
					<div id="metaboxes" style="display: none;">
						<?php the_gutenberg_metaboxes(); ?>
					</div>
						<?php
					?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer();
