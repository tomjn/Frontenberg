<?php

add_action( 'init', function() {
	add_theme_support( 'align-wide' );
	show_admin_bar( true );
	require_once( ABSPATH.'/wp-admin/includes/post.php' );
	require_once( ABSPATH.'/wp-admin/includes/plugin.php' );
	require_once( ABSPATH.'/wp-admin/includes/class-wp-screen.php' );
	require_once( ABSPATH.'/wp-admin/includes/screen.php' );
	require_once( ABSPATH.'/wp-admin/includes/template.php' );
	require_once( ABSPATH.'/wp-admin/includes/theme.php' );
	add_action( 'wp_enqueue_scripts', function() {
		wp_enqueue_script('postbox',admin_url("js/postbox.min.js"),array( 'jquery-ui-sortable' ),false, 1 );
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
	} );
	add_action( 'wp_enqueue_scripts', 'gutenberg_editor_scripts_and_styles' );

	if ( ! is_user_logged_in() ) {
		add_filter( 'wp_insert_post_empty_content', '__return_true', PHP_INT_MAX -1, 2 );
		add_filter( 'pre_insert_term', function( $t ) {return ''; });
	}
	if ( !is_admin() ) {
		if ( class_exists('WPSEO_Metabox') ) {
			$yoastbox = new WPSEO_Metabox();
			$yoastbox->add_meta_box();
			$asset_manager = new WPSEO_Admin_Asset_Manager();
			$asset_manager->register_assets();
			$asset_manager->enqueue_style( 'metabox-css' );
			$asset_manager->enqueue_style( 'scoring' );
			$asset_manager->enqueue_style( 'snippet' );
			$asset_manager->enqueue_style( 'select2' );
			$asset_manager->enqueue_script( 'metabox' );
			$asset_manager->enqueue_script( 'help-center' );
			$asset_manager->enqueue_script( 'admin-media' );
			$asset_manager->enqueue_script( 'post-scraper' );
			$asset_manager->enqueue_script( 'replacevar-plugin' );
			$asset_manager->enqueue_script( 'shortcode-plugin' );
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			$_REQUEST['post'] = 1;//get_queried_object_id();
			gutenberg_collect_meta_box_data();
		} 
	}
});

add_action( 'wp_head', function() {
	global $wp_scripts;
	$wp_scripts->print_inline_script('wp-edit-post');
} );

add_filter( 'filter_gutenberg_meta_boxes', function ( $_meta_boxes_copy ) {
	global $post;
	$locations = array( 'normal', 'advanced', 'side' );
	$meta_box_data = array();
	// If the meta box should be empty set to false.
	foreach ( $locations as $location ) {
		if ( gutenberg_is_meta_box_empty( $_meta_boxes_copy, $location, $post->post_type ) ) {
			$meta_box_data[ $location ] = false;
		} else {
			$meta_box_data[ $location ] = true;
			$incompatible_meta_box      = false;
			// Check if we have a meta box that has declared itself incompatible with the block editor.
			foreach ( $_meta_boxes_copy[ $post->post_type ][ $location ] as $boxes ) {
				foreach ( $boxes as $box ) {
					/*
					 * If __block_editor_compatible_meta_box is declared as a false-y value,
					 * the meta box is not compatible with the block editor.
					 */
					if ( is_array( $box['args'] )
						&& isset( $box['args']['__block_editor_compatible_meta_box'] )
						&& ! $box['args']['__block_editor_compatible_meta_box'] ) {
							$incompatible_meta_box = true;
							break 2;
					}
				}
			}
			// Incompatible meta boxes require an immediate redirect to the classic editor.
			if ( $incompatible_meta_box ) {
				?>
				<script type="text/javascript">
					var joiner = '?';
					if ( window.location.search ) {
						joiner = '&';
					}
					window.location.href += joiner + 'classic-editor';
				</script>
				<?php
				exit;
			}
		}
	}
	add_action( 'wp_footer', function() use ( $meta_box_data ) {
		echo '<script>';
		echo 'setTimeout( function() { console.log("test");window._wpLoadGutenbergEditor.then( function( editor ) { editor.initializeMetaBoxes( ' . wp_json_encode( $meta_box_data ) . ' ) } ); }, 500 );';
		echo '</script>';
	});
	return $_meta_boxes_copy;
} );
add_action( 'after_setup_theme', 'register_my_menu' );
function register_my_menu() {
	register_nav_menu( 'sidebar', __( 'Side Menu', 'frontenberg' ) );
}

// Disable use XML-RPC
add_filter( 'xmlrpc_enabled', '__return_false' );

// Disable X-Pingback to header
add_filter( 'wp_headers', 'disable_x_pingback' );
function disable_x_pingback( $headers ) {
	unset( $headers['X-Pingback'] );

	return $headers;
}

function frontenberg_give_permissions( $allcaps, $cap, $args ) {
	if ( is_user_logged_in() ) {
		return $allcaps;
	}
	// give author some permissions
	$allcaps['read'] = true;
	$allcaps['manage_categories'] = false;
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
add_filter( 'user_has_cap', 'frontenberg_give_permissions', 10, 3 );

function frontenberg_remove_toolbar_node($wp_admin_bar) {
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
add_action('admin_bar_menu', 'frontenberg_remove_toolbar_node', 999);



add_action( 'wp_ajax_nopriv_query-attachments', 'frontenberg_wp_ajax_nopriv_query_attachments' );
/**
 * Ajax handler for querying attachments.
 *
 * @since 3.5.0
 */
function frontenberg_wp_ajax_nopriv_query_attachments() {

	$query = isset( $_REQUEST['query'] ) ? (array) $_REQUEST['query'] : array();
	$keys = array(
		's', 'order', 'orderby', 'posts_per_page', 'paged', 'post_mime_type',
		'post_parent', 'post__in', 'post__not_in', 'year', 'monthnum'
	);
	foreach ( get_taxonomies_for_attachments( 'objects' ) as $t ) {
		if ( $t->query_var && isset( $query[ $t->query_var ] ) ) {
			$keys[] = $t->query_var;
		}
	}

	$query = array_intersect_key( $query, array_flip( $keys ) );
	$query['post_type'] = 'attachment';
	if ( MEDIA_TRASH
		&& ! empty( $_REQUEST['query']['post_status'] )
		&& 'trash' === $_REQUEST['query']['post_status'] ) {
		$query['post_status'] = 'trash';
	} else {
		$query['post_status'] = 'inherit';
	}

	// Filter query clauses to include filenames.
	if ( isset( $query['s'] ) ) {
		add_filter( 'posts_clauses', '_filter_query_attachment_filenames' );
	}

	/**
	 * Filters the arguments passed to WP_Query during an Ajax
	 * call for querying attachments.
	 *
	 * @since 3.7.0
	 *
	 * @see WP_Query::parse_query()
	 *
	 * @param array $query An array of query variables.
	 */
	$query = apply_filters( 'ajax_query_attachments_args', $query );
	$query = new WP_Query( $query );

	$posts = array_map( 'wp_prepare_attachment_for_js', $query->posts );
	$posts = array_filter( $posts );

	wp_send_json_success( $posts );
}
