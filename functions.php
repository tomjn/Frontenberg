<?php

function frontenberg_get_block_editor_version() {
	$version = 'WP Core';
	if ( function_exists('gutenberg_editor_scripts_and_styles') ) {
		$data = get_plugin_data( WP_PLUGIN_DIR . '/gutenberg/gutenberg.php' );
		$version = 'Gutenberg '.$data['Version'];
	}
	return $version;
}

add_action( 'init', function() {
	add_theme_support( 'align-wide' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5' );
	show_admin_bar( true );
	
	add_action( 'wp_enqueue_scripts', function() {
		wp_enqueue_script( 'postbox', admin_url("js/postbox.min.js"),array( 'jquery-ui-sortable' ),false, 1 );
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'common' );
		wp_enqueue_style( 'forms' );
		wp_enqueue_style( 'dashboard' );
		wp_enqueue_style( 'media' );
		wp_enqueue_style( 'admin-menu' );
		wp_enqueue_style( 'admin-bar' );
		wp_enqueue_style( 'nav-menus' );
		wp_enqueue_style( 'l10n' );
		wp_enqueue_style( 'buttons' );
		wp_enqueue_style( 'frontenberg', get_template_directory_uri() . '/style.css' );
		
		if ( ! function_exists('gutenberg_editor_scripts_and_styles') ) {
			wp_enqueue_script( 'heartbeat' );
			wp_enqueue_script( 'wp-edit-post' );
			wp_enqueue_script( 'wp-format-library' );
			do_action( 'enqueue_block_editor_assets' );
		}
	} );
	if ( function_exists('gutenberg_editor_scripts_and_styles') ) {
		add_action( 'wp_enqueue_scripts', 'gutenberg_editor_scripts_and_styles' );
	} else {
		frontenberg_load_wp5_editor();
	}

	if ( ! is_user_logged_in() ) {
		add_filter( 'wp_insert_post_empty_content', '__return_true', PHP_INT_MAX -1, 2 );
		add_filter( 'pre_insert_term', function( $t ) {return ''; });
		add_filter( 'update_post_metadata', '__return_false' );
		add_filter( 'add_post_metadata', '__return_false' );
		add_filter( 'delete_post_metadata', '__return_false' );
	}
});

function frontenberg_load_wp5_editor() {
	global $post;
	// Gutenberg isn't active, fall back to WP 5+ internal block editor
	wp_add_inline_script(
		'wp-blocks',
		sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( frontenberg_get_block_categories( $post ) ) ),
		'after'
	);
	/*
	 * Assign initial edits, if applicable. These are not initially assigned to the persisted post,
	 * but should be included in its save payload.
	 */
	$initial_edits = null;
	$is_new_post   = false;
	if ( 'auto-draft' === $post->post_status ) {
		$is_new_post = true;
		// Override "(Auto Draft)" new post default title with empty string, or filtered value.
		$initial_edits = array(
			'title'   => array(
				'raw' => $post->post_title,
			),
			'content' => array(
				'raw' => $post->post_content,
			),
			'excerpt' => array(
				'raw' => $post->post_excerpt,
			),
		);
	}
	// Preload server-registered block schemas.
	//wp_add_inline_script(
	//	'wp-blocks',
	//	'wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode( get_block_editor_server_block_settings() ) . ');'
	//);
	// Get admin url for handling meta boxes.
	$meta_box_url = admin_url( 'post.php' );
	$meta_box_url = add_query_arg(
		array(
			'post'            => $post->ID,
			'action'          => 'edit',
			'meta-box-loader' => true,
			'_wpnonce'        => wp_create_nonce( 'meta-box-loader' ),
		),
		$meta_box_url
	);
	wp_localize_script( 'wp-editor', '_wpMetaBoxUrl', $meta_box_url );

	// Populate default code editor settings by short-circuiting wp_enqueue_code_editor.
	wp_add_inline_script(
		'wp-editor',
		sprintf(
			'window._wpGutenbergCodeEditorSettings = %s;',
			wp_json_encode( wp_get_code_editor_settings( array( 'type' => 'text/html' ) ) )
		)
	);
	$align_wide    = get_theme_support( 'align-wide' );
	$color_palette = current( (array) get_theme_support( 'editor-color-palette' ) );
	$font_sizes    = current( (array) get_theme_support( 'editor-font-sizes' ) );

	/**
	 * Filters the allowed block types for the editor, defaulting to true (all
	 * block types supported).
	 *
	 * @since 5.0.0
	 *
	 * @param bool|array $allowed_block_types Array of block type slugs, or
	 *                                        boolean to enable/disable all.
	 * @param object $post                    The post resource data.
	 */
	$allowed_block_types = apply_filters( 'allowed_block_types', true, $post );
	// Get all available templates for the post/page attributes meta-box.
	// The "Default template" array element should only be added if the array is
	// not empty so we do not trigger the template select element without any options
	// besides the default value.
	$available_templates = wp_get_theme()->get_page_templates( get_post( $post->ID ) );
	$available_templates = ! empty( $available_templates ) ? array_merge(
		array(
			/** This filter is documented in wp-admin/includes/meta-boxes.php */
			'' => apply_filters( 'default_page_template_title', __( 'Default template' ), 'rest-api' ),
		),
		$available_templates
	) : $available_templates;
	// Media settings.
	$max_upload_size = wp_max_upload_size();
	if ( ! $max_upload_size ) {
		$max_upload_size = 0;
	}
	// Editor Styles.
	$styles = array(
		array(
			'css' => file_get_contents(
				ABSPATH . WPINC . '/css/dist/editor/editor-styles.css'
			),
		),
	);
	if ( $editor_styles && current_theme_supports( 'editor-styles' ) ) {
		foreach ( $editor_styles as $style ) {
			if ( preg_match( '~^(https?:)?//~', $style ) ) {
				$response = wp_remote_get( $style );
				if ( ! is_wp_error( $response ) ) {
					$styles[] = array(
						'css' => wp_remote_retrieve_body( $response ),
					);
				}
			} else {
				$file     = get_theme_file_path( $style );
				$styles[] = array(
					'css'     => file_get_contents( get_theme_file_path( $style ) ),
					'baseURL' => get_theme_file_uri( $style ),
				);
			}
		}
	}

	/**
	 * Filters the allowed block types for the editor, defaulting to true (all
	 * block types supported).
	 *
	 * @since 5.0.0
	 *
	 * @param bool|array $allowed_block_types Array of block type slugs, or
	 *                                        boolean to enable/disable all.
	 * @param object $post                    The post resource data.
	 */
	$allowed_block_types = apply_filters( 'allowed_block_types', true, $post );
	// Get all available templates for the post/page attributes meta-box.
	// The "Default template" array element should only be added if the array is
	// not empty so we do not trigger the template select element without any options
	// besides the default value.
	$available_templates = wp_get_theme()->get_page_templates( get_post( $post->ID ) );
	$available_templates = ! empty( $available_templates ) ? array_merge(
		array(
			/** This filter is documented in wp-admin/includes/meta-boxes.php */
			'' => apply_filters( 'default_page_template_title', __( 'Default template' ), 'rest-api' ),
		),
		$available_templates
	) : $available_templates;
	// Media settings.
	$max_upload_size = wp_max_upload_size();
	if ( ! $max_upload_size ) {
		$max_upload_size = 0;
	}
	// Editor Styles.
	$styles = array(
		array(
			'css' => file_get_contents(
				ABSPATH . WPINC . '/css/dist/editor/editor-styles.css'
			),
		),
	);
	if ( $editor_styles && current_theme_supports( 'editor-styles' ) ) {
		foreach ( $editor_styles as $style ) {
			if ( preg_match( '~^(https?:)?//~', $style ) ) {
				$response = wp_remote_get( $style );
				if ( ! is_wp_error( $response ) ) {
					$styles[] = array(
						'css' => wp_remote_retrieve_body( $response ),
					);
				}
			} else {
				$file     = get_theme_file_path( $style );
				$styles[] = array(
					'css'     => file_get_contents( get_theme_file_path( $style ) ),
					'baseURL' => get_theme_file_uri( $style ),
				);
			}
		}
	}

	if ( false !== $color_palette ) {
		$editor_settings['colors'] = $color_palette;
	}
	if ( ! empty( $font_sizes ) ) {
		$editor_settings['fontSizes'] = $font_sizes;
	}
	if ( ! empty( $post_type_object->template ) ) {
		$editor_settings['template']     = $post_type_object->template;
		$editor_settings['templateLock'] = ! empty( $post_type_object->template_lock ) ? $post_type_object->template_lock : false;
	}
	// If there's no template set on a new post, use the post format, instead.
	if ( $is_new_post && ! isset( $editor_settings['template'] ) && 'post' === $post->post_type ) {
		$post_format = get_post_format( $post );
		if ( in_array( $post_format, array( 'audio', 'gallery', 'image', 'quote', 'video' ), true ) ) {
			$editor_settings['template'] = array( array( "core/$post_format" ) );
		}
	}

	$init_script = <<<JS
( function() {
	window._wpLoadBlockEditor = new Promise( function( resolve ) {
		wp.domReady( function() {
			resolve( wp.editPost.initializeEditor( 'editor', "%s", %d, %s, %s ) );
		} );
	} );
} )();
JS;
	/**
	 * Filters the settings to pass to the block editor.
	 *
	 * @since 5.0.0
	 *
	 * @param array   $editor_settings Default editor settings.
	 * @param WP_Post $post            Post being edited.
	 */
	$editor_settings = apply_filters( 'block_editor_settings', $editor_settings, $post );
	$script = sprintf(
		$init_script,
		$post->post_type,
		$post->ID,
		wp_json_encode( $editor_settings ),
		wp_json_encode( $initial_edits )
	);
	wp_add_inline_script( 'wp-edit-post', $script );

	/**
	 * Scripts
	 */
	wp_enqueue_media(
		array(
			'post' => $post->ID,
		)
	);
	wp_enqueue_editor();
}


add_filter( 'show_post_locked_dialog', '__return_false' );

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

/**
 * Trying to make changes on the server won't work, so why bother contacting the REST API anyway?
 * This function hooks into the apiFetch and adds a middleware. This middleware intercepts all
 * PATCH PUT DELETE etc requests and replaces them with "empty promises" that resolve immediatley
 * without consequence.
 *
 * Sure, the requests would fail anyway, but this way there's fewer pings to the server to deal
 * with, and failure is now instantaneous*/
add_action( 'wp_footer', function() {
	?>
	<script>
	// @TODO: This setTimeout hack doesn't sit well with me, find a better solution that doesn't require jQuery
	setTimeout( function() {
		var fbeditor = window._wpLoadBlockEditor;
		if ( window._wpLoadGutenbergEditor ) {
			editor = window._wpLoadGutenbergEditor;
		}
		fbeditor.then( function( editor ) { 
			wp.apiFetch.use( function ( options, next ) {
				if ( 'method' in options ) {
					if ( [ 'PATCH', 'PUT', 'DELETE' ].indexOf( options.method.toUpperCase() ) >= 0 ) {
					        return new Promise(function(resolve, reject) {
							// Save Data
							resolve(data);
						});
					}
				}
				const result = next( options );
		    		return result;
			} );
		} );
	}, 500 );
	</script>
	<?php
});

// Attempt to disable post locking
add_filter( 'update_post_metadata', function(  $check, $object_id, $meta_key ) {
	if ( $meta_key == '_edit_lock' ) {
		return false;
	}
	return $check;
}, 10, 3 );
add_filter( 'wp_check_post_lock_window', '__return_false' );

function tomjn_override_post_lock( $metadata, $object_id, $meta_key ){
    // Here is the catch, add additional controls if needed (post_type, etc)
    $meta_needed = '_edit_lock';
    if ( isset( $meta_key ) && $meta_needed == $meta_key ){
        return false;
    }
    // Return original if the check does not pass
    return $metadata;
}

add_filter( 'get_post_metadata', 'tomjn_override_post_lock', 100, 3 );

/**
 * Returns all the block categories that will be shown in the block editor.
 *
 * @since 5.0.0
 *
 * @param WP_Post $post Post object.
 * @return array Array of block categories.
 */
function frontenberg_get_block_categories( $post ) {
	$default_categories = array(
		array(
			'slug'  => 'common',
			'title' => __( 'Common Blocks' ),
			'icon'  => 'screenoptions',
		),
		array(
			'slug'  => 'formatting',
			'title' => __( 'Formatting' ),
			'icon'  => null,
		),
		array(
			'slug'  => 'layout',
			'title' => __( 'Layout Elements' ),
			'icon'  => null,
		),
		array(
			'slug'  => 'widgets',
			'title' => __( 'Widgets' ),
			'icon'  => null,
		),
		array(
			'slug'  => 'embed',
			'title' => __( 'Embeds' ),
			'icon'  => null,
		),
		array(
			'slug'  => 'reusable',
			'title' => __( 'Reusable Blocks' ),
			'icon'  => null,
		),
	);
	/**
	 * Filter the default array of block categories.
	 *
	 * @since 5.0.0
	 *
	 * @param array   $default_categories Array of block categories.
	 * @param WP_Post $post               Post being loaded.
	 */
	return apply_filters( 'block_categories', $default_categories, $post );
}
