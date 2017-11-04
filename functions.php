<?php

add_action( 'init', function() {
	show_admin_bar( true );
	require( ABSPATH.'/wp-admin/includes/class-wp-screen.php' );
	require( ABSPATH.'/wp-admin/includes/screen.php' );
	require( ABSPATH.'/wp-admin/includes/template.php' );
	
	add_action( 'wp_enqueue_scripts', function() {
		//gutenberg.test/wp-admin/load-styles.php?c=1&amp;dir=ltr&amp;load%5B%5D=dashicons,admin-bar,common,forms,admin-menu,dashboard,list-tables,edit,revisions,media,themes,about,nav-menus,wp-pointer,widgets&amp;load%5B%5D=,site-icon,l10n,buttons,wp-auth-check&amp;ver=4.9-RC1-42056
		wp_enqueue_style('dashicons');
		wp_enqueue_style('common');
		wp_enqueue_style('forms');
		wp_enqueue_style('dashboard');
		wp_enqueue_style('list-tables');
		wp_enqueue_style('edit');
		wp_enqueue_style('revisions');
		wp_enqueue_style('media');
		wp_enqueue_style('admin-menu');
		wp_enqueue_style('admin-bar');
		wp_enqueue_style('themes');
		wp_enqueue_style('about');
		wp_enqueue_style('nav-menus');
		wp_enqueue_style('wp-pointer');
		wp_enqueue_style('widgets');
		wp_enqueue_style('l10n');
		wp_enqueue_style('buttons');
		/*wp_enqueue_style('');
		wp_enqueue_style('');
		wp_enqueue_style('');*/
	} );
	add_action( 'wp_enqueue_scripts', 'gutenberg_editor_scripts_and_styles' );

	if ( ! is_user_logged_in() ) {
		add_filter( 'wp_insert_post_empty_content', '__return_true', PHP_INT_MAX -1, 2 );
	}
});
function give_permissions( $allcaps, $cap, $args ) {
	if ( is_user_logged_in() ) {
		return $allcaps;
	}
	// give author some permissions
	$allcaps['read'] = true;
	$allcaps['manage_categories'] = true;
	$allcaps['edit_post'] = true;
	$allcaps['edit_posts'] = true;
	$allcaps['edit_others_posts'] = true;
	$allcaps['edit_published_posts'] = true;

	// better safe than sorry
	$allcaps['edit_pages'] = false;
	$allcaps['switch_themes'] = false;
	$allcaps['edit_themes'] = false;
	$allcaps['edit_pages'] = false;
	$allcaps['activate_plugins'] = false;
	$allcaps['edit_plugins'] = false;
	$allcaps['edit_users'] = false;
	$allcaps['import'] = false;
	$allcaps['unfiltered_html'] = false;
	$allcaps['edit_plugins'] = false;
	$allcaps['unfiltered_upload'] = false;

	return $allcaps;
}
add_filter( 'user_has_cap', 'give_permissions', 10, 3 );

function frontenburg_remove_toolbar_node($wp_admin_bar) {
	if ( is_user_logged_in() ) {
		return;
	}
	// replace 'updraft_admin_node' with your node id
	$wp_admin_bar->remove_node('wpseo-menu');
	$wp_admin_bar->remove_node('new-content');
	$wp_admin_bar->remove_node('comments');
	$wp_admin_bar->remove_node('wp-logo');
	$wp_admin_bar->remove_node('bar-about');
	$wp_admin_bar->remove_node('search');
	$wp_admin_bar->remove_node('wp-logo-external');
	$wp_admin_bar->remove_node('about');
	$wp_admin_bar->add_menu( array(
        'id'    => 'wp-logo',
        'title' => '<span class="ab-icon"></span>',
        'href'  => home_url(),
        'meta'  => array(
        	'class' => 'wp-logo',
            'title' => __('FrontenBerg'),            
        ),
    ));
	$wp_admin_bar->add_menu( array(
        'id'    => 'frontenderg',
        'title' => 'Frontenberg',
        'href'  => home_url(),
        'meta'  => array(
            'title' => __('FrontenBerg'),            
        ),
    ));
	
}
add_action('admin_bar_menu', 'frontenburg_remove_toolbar_node', 999);
