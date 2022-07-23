<?php
/**
 * Functions file.
 *
 * @package tomjn/frontenberg
 */

if ( wp_is_xml_request() ) {
	return;
}

// load the code.
require_once __DIR__ . '/includes/jetpack.php';
require_once __DIR__ . '/includes/navigation.php';
require_once __DIR__ . '/includes/restrictions.php';
require_once __DIR__ . '/includes/widgets.php';

// run the code.
\frontenberg\jetpack\bootstrap();
\frontenberg\navigation\bootstrap();
\frontenberg\restrictions\bootstrap();
\frontenberg\widgets\bootstrap();


add_action( 'after_setup_theme', 'frontenberg_after_setup_theme' );
function frontenberg_after_setup_theme() : void {
	// Add support for block styles.
	add_theme_support( 'wp-block-styles' );
}

/**
 * Get the version string for Gutenberg.
 *
 * @return string
 */
function frontenberg_get_block_editor_version() : string {
	$version = 'WP Core';
	if ( function_exists( 'gutenberg_dir_path' ) ) {
		$version = 'Gutenberg Plugin';
		if ( function_exists( 'get_plugin_data' ) ) {
			$data    = get_plugin_data( WP_PLUGIN_DIR . '/gutenberg/gutenberg.php' );
			$version = 'Gutenberg v' . $data['Version'];
		}
	}
	return $version;
}

add_action(
	'wp_enqueue_scripts',
	function() {
		wp_enqueue_script(
			'postbox',
			admin_url( 'js/postbox.min.js' ),
			[
				'jquery-ui-sortable',
			],
			false,
			1
		);
		wp_enqueue_style(
			'frontenberg',
			get_template_directory_uri() . '/style.css',
			[
				'dashicons',
				'common',
				'forms',
				'dashboard',
				'media',
				'admin-menu',
				'admin-bar',
				'nav-menus',
				'buttons',
				'wp-edit-post',
				'wp-format-library',
			],
			false
		);

		wp_tinymce_inline_scripts();
		wp_enqueue_script( 'heartbeat' );
		wp_enqueue_script( 'wp-edit-post' );
		wp_enqueue_script( 'wp-format-library' );
		wp_dequeue_style('global-styles');
		do_action( 'enqueue_block_editor_assets' );
	}
);

add_action(
	'init',
	function () {
		add_theme_support( 'align-wide' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );

		if ( is_admin() || wp_is_xml_request() || wp_is_json_request() ) {
			return;
		}

		if ( 'xmlrpc' === basename( $_SERVER['SCRIPT_FILENAME'], '.php' ) ) {
			return;
		}

		show_admin_bar( true );
	}
);

/**
 * Loads the editor.
 *
 * @return void
 */
function frontenberg_load_editor() : void {
	global $post;
	the_post();
	if ( empty( $post ) ) {
		wp_die( 'No post to edit :(' );
	}

	require_once 'includes/shims.php';

	if ( isset( $_GET['experiment'] ) ) {

		if ( 'menus' === $_GET['experiment'] ) {
			wp_die( 'The menu navigation experiment is not ready yet.' );
			gutenberg_navigation_init( 'gutenberg_page_gutenberg-navigation' );
			get_header();
			gutenberg_navigation_page();
			get_footer();
			exit;
		}

		if ( 'widgets' === $_GET['experiment'] ) {
			gutenberg_widgets_init( 'appearance_page_gutenberg-widgets' );
			get_header();
			the_gutenberg_widgets();
			get_footer();
			exit;
		}

	}
	set_current_screen( 'post' );

	// Necessary for query monitor
	remove_all_filters( 'admin_init' );
	do_action( 'admin_init' );

	require_once 'edit-form-blocks.php';
}

/**
 * Adjust the admin toolbar.
 *
 * @param object $wp_admin_bar the admin bar object.
 * @return void
 */
function frontenberg_remove_toolbar_node( $wp_admin_bar ) : void {
	if ( is_user_logged_in() ) {
		return;
	}

	$wp_admin_bar->remove_node( 'view' );
	$wp_admin_bar->remove_node( 'wpseo-menu' );
	$wp_admin_bar->remove_node( 'new-content' );
	$wp_admin_bar->remove_node( 'comments' );
	$wp_admin_bar->remove_node( 'wp-logo' );
	$wp_admin_bar->remove_node( 'bar-about' );
	$wp_admin_bar->remove_node( 'search' );
	$wp_admin_bar->remove_node( 'wp-logo-external' );
	$wp_admin_bar->remove_node( 'about' );
	$wp_admin_bar->add_menu(
		[
			'id'    => 'wp-logo',
			'title' => '<span class="ab-icon"></span>',
			'href'  => home_url(),
			'meta'  => array(
				'class' => 'wp-logo',
				'title' => __( 'Frontenberg' ),
			),
		]
	);
	$wp_admin_bar->add_menu(
		[
			'id'    => 'frontenberg',
			'title' => 'Frontenberg',
			'href'  => home_url(),
			'meta'  => array(
				'title' => __( 'Frontenberg' ),
			),
		]
	);

}
add_action( 'admin_bar_menu', 'frontenberg_remove_toolbar_node', 999 );

add_action( 'wp_ajax_nopriv_query-attachments', 'frontenberg_wp_ajax_nopriv_query_attachments' );

/**
 * Ajax handler for querying attachments.
 *
 * @since 3.5.0
 */
function frontenberg_wp_ajax_nopriv_query_attachments() {
	$query = isset( $_REQUEST['query'] ) ? (array) $_REQUEST['query'] : [];
	$keys = [
		's',
		'order',
		'orderby',
		'posts_per_page',
		'paged',
		'post_mime_type',
		'post_parent',
		'post__in',
		'post__not_in',
		'year',
		'monthnum',
	];

	foreach ( get_taxonomies_for_attachments( 'objects' ) as $t ) {
		if ( $t->query_var && isset( $query[ $t->query_var ] ) ) {
			$keys[] = $t->query_var;
		}
	}

	$query              = array_intersect_key( $query, array_flip( $keys ) );
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
