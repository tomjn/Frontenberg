<?php

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
			$data = get_plugin_data( WP_PLUGIN_DIR . '/gutenberg/gutenberg.php' );
			$version = 'Gutenberg v' . $data['Version'];
		}
	}
	return $version;
}

add_action(
	'init',
	function () {
		add_theme_support( 'align-wide' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5' );
		if ( is_admin() ) {
			return;
		}
		show_admin_bar( true );

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
						//'i10n',
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
			}
		);
		do_action( 'enqueue_block_editor_assets' );

		if ( function_exists( 'gutenberg_dir_path' ) ) {
			if ( ! function_exists( 'get_current_screen' ) && ! ( php_sapi_name() == 'cli' ) ) {
				function get_current_screen() : string {
					return '';
				}
			}
		}
		add_action( 'template_redirect', 'frontenberg_load_wp5_editor' );
	}
);

// Prevent the creation of various types of content.
add_action(
	'init',
	function () {
		if ( ! is_user_logged_in() ) {
			add_filter(
				'wp_insert_post_empty_content',
				'__return_true',
				PHP_INT_MAX -1,
				2
			);
			add_filter(
				'pre_insert_term',
				function( $t ) {
					return '';
				}
			);
			add_filter( 'update_post_metadata', '__return_false' );
			add_filter( 'add_post_metadata', '__return_false' );
			add_filter( 'delete_post_metadata', '__return_false' );
		}
	}
);

function frontenberg_load_wp5_editor() {
	global $post;
	the_post();
	if ( empty( $post ) ) {
		wp_die( 'No post to edit :(' );
	}

	// Gutenberg isn't active, fall back to WP 5+ internal block editor.
	wp_add_inline_script(
		'wp-blocks',
		sprintf(
			'wp.blocks.setCategories( %s );',
			wp_json_encode( frontenberg_get_block_categories( $post ) )
		),
		'after'
	);

	/*
	 * Assign initial edits, if applicable. These are not initially assigned to the persisted post,
	 * but should be included in its save payload.
	 */
	$initial_edits = null;
	$is_new_post   = false;

	// Preload server-registered block schemas.
	wp_add_inline_script(
		'wp-blocks',
		'wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode( frontenberg_get_block_editor_server_block_settings() ) . ');'
	);
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
	$available_templates = wp_get_theme()->get_page_templates( $post );
	$available_templates = ! empty( $available_templates ) ? array_merge(
		array(
			/** This filter is documented in wp-admin/includes/meta-boxes.php */
			'' => apply_filters( 'default_page_template_title', __( 'Default template' ), 'rest-api' ),
		),
		$available_templates
	) : $available_templates;

	// Get editor settings.
	$max_upload_size = wp_max_upload_size();
	if ( ! $max_upload_size ) {
		$max_upload_size = 0;
	}

	// This filter is documented in wp-admin/includes/media.php.
	$image_size_names      = apply_filters(
		'image_size_names_choose',
		array(
			'thumbnail' => __( 'Thumbnail', 'gutenberg' ),
			'medium'    => __( 'Medium', 'gutenberg' ),
			'large'     => __( 'Large', 'gutenberg' ),
			'full'      => __( 'Full Size', 'gutenberg' ),
		)
	);
	$available_image_sizes = array();
	foreach ( $image_size_names as $image_size_slug => $image_size_name ) {
		$available_image_sizes[] = array(
			'slug' => $image_size_slug,
			'name' => $image_size_name,
		);
	}

	$editor_settings = [
		'alignWide'              => get_theme_support( 'align-wide' ),
		'disableCustomColors'    => get_theme_support( 'disable-custom-colors' ),
		'disableCustomFontSizes' => get_theme_support( 'disable-custom-font-sizes' ),
		'imageSizes'             => $available_image_sizes,
		'isRTL'                  => is_rtl(),
		'maxUploadFileSize'      => $max_upload_size,

		'availableTemplates' => [],
		'allowedBlockTypes' => true,
		'disablePostFormats' => false,
		'titlePlaceholder' => 'Add title',
		'bodyPlaceholder' => 'Start writing or type / to choose a block',
		'autosaveInterval' => 1000000,
		'allowedMimeTypes' => wp_get_mime_types(),
		'richEditingEnabled' => true,
		'postLock' => [
			'isLocked' => false,
			'activePostLock' => '1583173255:1',
		],
		'postLockUtils' => [
			'nonce' => '1234567890',
			'unlockNonce' => '1234567890',
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		],
		'enableCustomFields' => true,
		'__experimentalEnableLegacyWidgetBlock' => false,
		'__experimentalBlockDirectory' => false,
		'__experimentalEnableFullSiteEditing' => false,
		'__experimentalEnableFullSiteEditingDemo' => false,
		'disableCustomGradients' => false,
		'hasPermissionsToManageWidgets' => true,
		'availableLegacyWidgets' => [],
		'imageDimensions' => [
			'thumbnail' => [
				'width' => 150,
				'height' => 150,
				'crop' => true,
			],
			'medium' => [
				'width' => 300,
				'height' => 300,
				'crop' => false,
			],
			'large' => [
				'width' => 1024,
				'height' => 1024,
				'crop' => false,
			],
		],
	];
	/*
	$editor_settings = "{\"imageDimensions\":{},\"availableLegacyWidgets\":{\"WP_Widget_Pages\":{\"name\":\"Pages\",\"description\":\"A list of your site’s Pages.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Calendar\":{\"name\":\"Calendar\",\"description\":\"A calendar of your site’s posts.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Archives\":{\"name\":\"Archives\",\"description\":\"A monthly archive of your site’s Posts.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Media_Audio\":{\"name\":\"Audio\",\"description\":\"Displays an audio player.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Media_Image\":{\"name\":\"Image\",\"description\":\"Displays an image.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Media_Gallery\":{\"name\":\"Gallery\",\"description\":\"Displays an image gallery.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Media_Video\":{\"name\":\"Video\",\"description\":\"Displays a video from the media library or from YouTube, Vimeo, or another provider.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Meta\":{\"name\":\"Meta\",\"description\":\"Login, RSS, & WordPress.org links.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Search\":{\"name\":\"Search\",\"description\":\"A search form for your site.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Text\":{\"name\":\"Text\",\"description\":\"Arbitrary text.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Categories\":{\"name\":\"Categories\",\"description\":\"A list or dropdown of categories.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Recent_Posts\":{\"name\":\"Recent Posts\",\"description\":\"Your site’s most recent Posts.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Recent_Comments\":{\"name\":\"Recent Comments\",\"description\":\"Your site’s most recent comments.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_RSS\":{\"name\":\"RSS\",\"description\":\"Entries from any RSS or Atom feed.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Tag_Cloud\":{\"name\":\"Tag Cloud\",\"description\":\"A cloud of your most used tags.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Nav_Menu_Widget\":{\"name\":\"Navigation Menu\",\"description\":\"Add a navigation menu to your sidebar.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Custom_HTML\":{\"name\":\"Custom HTML\",\"description\":\"Arbitrary HTML code.\",\"isReferenceWidget\":false,\"isHidden\":true},\"Akismet_Widget\":{\"name\":\"Akismet Widget\",\"description\":\"Display the number of spam comments Akismet has caught\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Subscriptions_Widget\":{\"name\":\"Blog Subscriptions (Jetpack)\",\"description\":\"Add an email signup form to allow people to subscribe to your blog.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Widget_Authors\":{\"name\":\"Authors (Jetpack)\",\"description\":\"Display blogs authors with avatars and recent posts.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Blog_Stats_Widget\":{\"name\":\"Blog Stats (Jetpack)\",\"description\":\"Show a hit counter for your blog.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Contact_Info_Widget\":{\"name\":\"Contact Info & Map (Jetpack)\",\"description\":\"Display a map with your location, hours, and contact information.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_EU_Cookie_Law_Widget\":{\"name\":\"Cookies & Consents Banner (Jetpack)\",\"description\":\"Display a banner for EU Cookie Law and GDPR compliance.\",\"isReferenceWidget\":false,\"isHidden\":false},\"WPCOM_Widget_Facebook_LikeBox\":{\"name\":\"Facebook Page Plugin (Jetpack)\",\"description\":\"Use the Facebook Page Plugin to connect visitors to your Facebook Page\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Flickr_Widget\":{\"name\":\"Flickr (Jetpack)\",\"description\":\"Display your recent Flickr photos.\",\"isReferenceWidget\":false,\"isHidden\":false},\"WPCOM_Widget_Goodreads\":{\"name\":\"Goodreads (Jetpack)\",\"description\":\"Display your books from Goodreads\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Google_Translate_Widget\":{\"name\":\"Google Translate (Jetpack)\",\"description\":\"Provide your readers with the option to translate your site into their preferred language.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Gravatar_Profile_Widget\":{\"name\":\"Gravatar Profile (Jetpack)\",\"description\":\"Display a mini version of your Gravatar Profile\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Internet_Defense_League_Widget\":{\"name\":\"Internet Defense League (Jetpack)\",\"description\":\"Show your support for the Internet Defense League.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_MailChimp_Subscriber_Popup_Widget\":{\"name\":\"MailChimp Subscriber Popup (Jetpack)\",\"description\":\"Allows displaying a popup subscription form to visitors.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Milestone_Widget\":{\"name\":\"Milestone (Jetpack)\",\"description\":\"Display a countdown to a certain date.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_My_Community_Widget\":{\"name\":\"My Community (Jetpack)\",\"description\":\"Display members of your site&#039;s community.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_RSS_Links_Widget\":{\"name\":\"RSS Links (Jetpack)\",\"description\":\"Links to your blog's RSS feeds\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Widget_Social_Icons\":{\"name\":\"Social Icons (Jetpack)\",\"description\":\"Add social-media icons to your site.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Top_Posts_Widget\":{\"name\":\"Top Posts & Pages (Jetpack)\",\"description\":\"Shows your most viewed posts and pages.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Twitter_Timeline_Widget\":{\"name\":\"Twitter Timeline (Jetpack)\",\"description\":\"Display an official Twitter Embedded Timeline widget.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Upcoming_Events_Widget\":{\"name\":\"Upcoming Events (Jetpack)\",\"description\":\"Display upcoming events from an iCalendar feed.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Display_Posts_Widget\":{\"name\":\"Display WordPress Posts (Jetpack)\",\"description\":\"Displays a list of recent posts from another WordPress.com or Jetpack-enabled blog.\",\"isReferenceWidget\":false,\"isHidden\":false}},}";
	*/
	list( $color_palette, ) = (array) get_theme_support( 'editor-color-palette' );
	list( $font_sizes, )    = (array) get_theme_support( 'editor-font-sizes' );
	if ( false !== $color_palette ) {
		$editor_settings['colors'] = $color_palette;
	}
	if ( ! empty( $font_sizes ) ) {
		$editor_settings['fontSizes'] = $font_sizes;
	}
	$editor_settings['styles'] = gutenberg_get_editor_styles();

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

	$settings = apply_filters( 'block_editor_settings', $editor_settings, $post );

	//$editor_settings = "{\"alignWide\":false,\"availableTemplates\":[],\"allowedBlockTypes\":true,\"disableCustomColors\":false,\"disableCustomFontSizes\":false,\"disablePostFormats\":false,\"titlePlaceholder\":\"Add title\",\"bodyPlaceholder\":\"Start writing or type / to choose a block\",\"isRTL\":false,\"autosaveInterval\":60,\"maxUploadFileSize\":2097152,\"allowedMimeTypes\":{\"jpg|jpeg|jpe\":\"image/jpeg\",\"png\":\"image/png\",\"gif\":\"image/gif\",\"mov|qt\":\"video/quicktime\",\"avi\":\"video/avi\",\"mpeg|mpg|mpe\":\"video/mpeg\",\"3gp|3gpp\":\"video/3gpp\",\"3g2|3gp2\":\"video/3gpp2\",\"mid|midi\":\"audio/midi\",\"pdf\":\"application/pdf\",\"doc\":\"application/msword\",\"docx\":\"application/vnd.openxmlformats-officedocument.wordprocessingml.document\",\"docm\":\"application/vnd.ms-word.document.macroEnabled.12\",\"pot|pps|ppt\":\"application/vnd.ms-powerpoint\",\"pptx\":\"application/vnd.openxmlformats-officedocument.presentationml.presentation\",\"pptm\":\"application/vnd.ms-powerpoint.presentation.macroEnabled.12\",\"odt\":\"application/vnd.oasis.opendocument.text\",\"ppsx\":\"application/vnd.openxmlformats-officedocument.presentationml.slideshow\",\"ppsm\":\"application/vnd.ms-powerpoint.slideshow.macroEnabled.12\",\"xla|xls|xlt|xlw\":\"application/vnd.ms-excel\",\"xlsx\":\"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet\",\"xlsm\":\"application/vnd.ms-excel.sheet.macroEnabled.12\",\"xlsb\":\"application/vnd.ms-excel.sheet.binary.macroEnabled.12\",\"key\":\"application/vnd.apple.keynote\",\"mp3|m4a|m4b\":\"audio/mpeg\",\"ogg|oga\":\"audio/ogg\",\"wma\":\"audio/x-ms-wma\",\"wav\":\"audio/wav\",\"mp4|m4v\":\"video/mp4\",\"webm\":\"video/webm\",\"ogv\":\"video/ogg\",\"wmv\":\"video/x-ms-wmv\",\"flv\":\"video/x-flv\"},\"styles\":[{\"css\":\"body{font-family:\\\"Noto Serif\\\",serif;font-size:16px;line-height:1.8;color:#191e23}h1{font-size:2.44em}h2{font-size:1.95em}h3{font-size:1.56em}h4{font-size:1.25em}h5{font-size:1em}h6{font-size:.8em}h1,h2,h3{line-height:1.4}h4{line-height:1.5}h1{margin-top:.67em;margin-bottom:.67em}h2{margin-top:.83em;margin-bottom:.83em}h3{margin-top:1em;margin-bottom:1em}h4{margin-top:1.33em;margin-bottom:1.33em}h5{margin-top:1.67em;margin-bottom:1.67em}h6{margin-top:2.33em;margin-bottom:2.33em}h1,h2,h3,h4,h5,h6{color:inherit}p{font-size:inherit;line-height:inherit;margin-top:28px}ol,p,ul{margin-bottom:28px}ol,ul{padding:inherit;padding-left:1.3em;margin-left:1.3em}ol li,ol ol,ol ul,ul li,ul ol,ul ul{margin-bottom:0}ul{list-style-type:disc}ol{list-style-type:decimal}ol ul,ul ul{list-style-type:circle}\"},{\"css\":\"body { font-family: 'Noto Serif' }\"}],\"imageSizes\":[{\"slug\":\"thumbnail\",\"name\":\"Thumbnail\"},{\"slug\":\"medium\",\"name\":\"Medium\"},{\"slug\":\"large\",\"name\":\"Large\"},{\"slug\":\"full\",\"name\":\"Full Size\"}],\"richEditingEnabled\":true,\"postLock\":{\"isLocked\":false,\"activePostLock\":\"1583173255:1\"},\"postLockUtils\":{\"nonce\":\"ed1189fa0c\",\"unlockNonce\":\"e03c5ad4ae\",\"ajaxUrl\":\"https://tomjn.com/wp-admin/admin-ajax.php\"},\"enableCustomFields\":true,\"imageDimensions\":{\"thumbnail\":{\"width\":150,\"height\":150,\"crop\":true},\"medium\":{\"width\":300,\"height\":300,\"crop\":false},\"large\":{\"width\":1024,\"height\":1024,\"crop\":false}},\"hasPermissionsToManageWidgets\":true,\"availableLegacyWidgets\":{\"WP_Widget_Pages\":{\"name\":\"Pages\",\"description\":\"A list of your site’s Pages.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Calendar\":{\"name\":\"Calendar\",\"description\":\"A calendar of your site’s posts.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Archives\":{\"name\":\"Archives\",\"description\":\"A monthly archive of your site’s Posts.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Media_Audio\":{\"name\":\"Audio\",\"description\":\"Displays an audio player.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Media_Image\":{\"name\":\"Image\",\"description\":\"Displays an image.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Media_Gallery\":{\"name\":\"Gallery\",\"description\":\"Displays an image gallery.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Media_Video\":{\"name\":\"Video\",\"description\":\"Displays a video from the media library or from YouTube, Vimeo, or another provider.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Meta\":{\"name\":\"Meta\",\"description\":\"Login, RSS, & WordPress.org links.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Search\":{\"name\":\"Search\",\"description\":\"A search form for your site.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Text\":{\"name\":\"Text\",\"description\":\"Arbitrary text.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Categories\":{\"name\":\"Categories\",\"description\":\"A list or dropdown of categories.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Recent_Posts\":{\"name\":\"Recent Posts\",\"description\":\"Your site’s most recent Posts.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Recent_Comments\":{\"name\":\"Recent Comments\",\"description\":\"Your site’s most recent comments.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_RSS\":{\"name\":\"RSS\",\"description\":\"Entries from any RSS or Atom feed.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Tag_Cloud\":{\"name\":\"Tag Cloud\",\"description\":\"A cloud of your most used tags.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Nav_Menu_Widget\":{\"name\":\"Navigation Menu\",\"description\":\"Add a navigation menu to your sidebar.\",\"isReferenceWidget\":false,\"isHidden\":true},\"WP_Widget_Custom_HTML\":{\"name\":\"Custom HTML\",\"description\":\"Arbitrary HTML code.\",\"isReferenceWidget\":false,\"isHidden\":true},\"Akismet_Widget\":{\"name\":\"Akismet Widget\",\"description\":\"Display the number of spam comments Akismet has caught\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Subscriptions_Widget\":{\"name\":\"Blog Subscriptions (Jetpack)\",\"description\":\"Add an email signup form to allow people to subscribe to your blog.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Widget_Authors\":{\"name\":\"Authors (Jetpack)\",\"description\":\"Display blogs authors with avatars and recent posts.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Blog_Stats_Widget\":{\"name\":\"Blog Stats (Jetpack)\",\"description\":\"Show a hit counter for your blog.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Contact_Info_Widget\":{\"name\":\"Contact Info & Map (Jetpack)\",\"description\":\"Display a map with your location, hours, and contact information.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_EU_Cookie_Law_Widget\":{\"name\":\"Cookies & Consents Banner (Jetpack)\",\"description\":\"Display a banner for EU Cookie Law and GDPR compliance.\",\"isReferenceWidget\":false,\"isHidden\":false},\"WPCOM_Widget_Facebook_LikeBox\":{\"name\":\"Facebook Page Plugin (Jetpack)\",\"description\":\"Use the Facebook Page Plugin to connect visitors to your Facebook Page\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Flickr_Widget\":{\"name\":\"Flickr (Jetpack)\",\"description\":\"Display your recent Flickr photos.\",\"isReferenceWidget\":false,\"isHidden\":false},\"WPCOM_Widget_Goodreads\":{\"name\":\"Goodreads (Jetpack)\",\"description\":\"Display your books from Goodreads\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Google_Translate_Widget\":{\"name\":\"Google Translate (Jetpack)\",\"description\":\"Provide your readers with the option to translate your site into their preferred language.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Gravatar_Profile_Widget\":{\"name\":\"Gravatar Profile (Jetpack)\",\"description\":\"Display a mini version of your Gravatar Profile\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Internet_Defense_League_Widget\":{\"name\":\"Internet Defense League (Jetpack)\",\"description\":\"Show your support for the Internet Defense League.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_MailChimp_Subscriber_Popup_Widget\":{\"name\":\"MailChimp Subscriber Popup (Jetpack)\",\"description\":\"Allows displaying a popup subscription form to visitors.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Milestone_Widget\":{\"name\":\"Milestone (Jetpack)\",\"description\":\"Display a countdown to a certain date.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_My_Community_Widget\":{\"name\":\"My Community (Jetpack)\",\"description\":\"Display members of your site&#039;s community.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_RSS_Links_Widget\":{\"name\":\"RSS Links (Jetpack)\",\"description\":\"Links to your blog's RSS feeds\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Widget_Social_Icons\":{\"name\":\"Social Icons (Jetpack)\",\"description\":\"Add social-media icons to your site.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Top_Posts_Widget\":{\"name\":\"Top Posts & Pages (Jetpack)\",\"description\":\"Shows your most viewed posts and pages.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Twitter_Timeline_Widget\":{\"name\":\"Twitter Timeline (Jetpack)\",\"description\":\"Display an official Twitter Embedded Timeline widget.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Upcoming_Events_Widget\":{\"name\":\"Upcoming Events (Jetpack)\",\"description\":\"Display upcoming events from an iCalendar feed.\",\"isReferenceWidget\":false,\"isHidden\":false},\"Jetpack_Display_Posts_Widget\":{\"name\":\"Display WordPress Posts (Jetpack)\",\"description\":\"Displays a list of recent posts from another WordPress.com or Jetpack-enabled blog.\",\"isReferenceWidget\":false,\"isHidden\":false}},\"__experimentalEnableLegacyWidgetBlock\":false,\"__experimentalBlockDirectory\":false,\"__experimentalEnableFullSiteEditing\":false,\"__experimentalEnableFullSiteEditingDemo\":false,\"disableCustomGradients\":false}";

	$initial_edits = null;
	$script = sprintf(
		$init_script,
		$post->post_type,
		$post->ID,
		wp_json_encode( $settings ),
		wp_json_encode( $initial_edits )
	);
	wp_add_inline_script( 'wp-edit-post', $script );

	/*wp_add_inline_script(
		'wp-blocks',
		sprintf( 'wp.blocks.unstable__bootstrapServerSideBlockDefinitions( %s );', wp_json_encode( get_block_editor_server_block_settings() ) ),
		'after'
	);

	wp_add_inline_script(
		'wp-blocks',
		sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( get_block_categories( $post ) ) ),
		'after'
	);*/

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

/**
 * Register a sidebar menu.
 *
 * @return void
 */
function register_my_menu() {
	register_nav_menu( 'sidebar', __( 'Side Menu', 'frontenberg' ) );
}

// Disable use XML-RPC.
add_filter( 'xmlrpc_enabled', '__return_false' );

// Disable X-Pingback to header.
add_filter( 'wp_headers', 'disable_x_pingback' );

/**
 * Unset the X-Pingback header.
 *
 * @param array $headers HTTP headers.
 *
 * @return array
 */
function disable_x_pingback( array $headers ) : array {
	unset( $headers['X-Pingback'] );
	return $headers;
}

/**
 * Fake permissions, pretend the user can do everything, so that we can then prevent it at a lower level.
 *
 * @param array $allcaps the capabilities the current user has.
 *
 * @return array
 */
function frontenberg_give_permissions( array $allcaps ) : array {
	if ( is_user_logged_in() ) {
		return $allcaps;
	}

	// give author some permissions.
	$allcaps['read'] = true;
	$allcaps['manage_categories'] = false;
	$allcaps['edit_post'] = true;
	$allcaps['edit_posts'] = true;
	$allcaps['edit_others_posts'] = true;
	$allcaps['edit_published_posts'] = true;

	// remove some capabilities the user should never have.
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
add_filter( 'user_has_cap', 'frontenberg_give_permissions', 10, 1 );

/**
 * Adjust the admin toolbar.
 *
 * @param object $wp_admin_bar
 * @return void
 */
function frontenberg_remove_toolbar_node( $wp_admin_bar ) {
	if ( is_user_logged_in() ) {
		return;
	}

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

/**
 * Trying to make changes on the server won't work, so why bother contacting the REST API anyway?
 * This function hooks into the apiFetch and adds a middleware. This middleware intercepts all
 * PATCH PUT DELETE etc requests and replaces them with "empty promises" that resolve immediately
 * without consequence.
 *
 * Sure, the requests would fail anyway, but this way there's fewer pings to the server to deal
 * with, and failure is now instantaneous
 */
add_action(
	'wp_footer',
	function() {
		?>
<script>
	window._wpLoadBlockEditor.then( function( editor ) {
		wp.apiFetch.use( function ( options, next ) {
			if ( 'method' in options ) {
				if ( [ 'PATCH', 'PUT', 'DELETE' ].indexOf( options.method.toUpperCase() ) >= 0 ) {
					return new Promise( function( resolve, reject ) {
						// Save Data
						resolve(data);
					} );
				}
			}
			const result = next( options );
			return result;
		} );
		wp.data.select( 'core/editor' ).isEditedPostDirty = function() {
			return false;
		}
	} );
</script>
		<?php
	},
	99
);

// Attempt to disable post locking.
add_filter(
	'update_post_metadata',
	function( $check, $object_id, $meta_key ) {
		if ( '_edit_lock' === $meta_key ) {
			return false;
		}
		return $check;
	},
	10,
	3
);

add_filter( 'wp_check_post_lock_window', '__return_false' );

/**
 * Override the post locks.
 *
 * @param mixed  $metadata value
 * @param mixed  $object_id ID
 * @param string $meta_key key
 *
 * @return mixed
 */
function tomjn_override_post_lock( $metadata, $object_id, $meta_key ) {
	// Here is the catch, add additional controls if needed (post_type, etc).
	$meta_needed = '_edit_lock';
	if ( isset( $meta_key ) && $meta_needed === $meta_key ){
		return false;
	}
	// Return original if the check does not pass.
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

function frontenberg_get_block_editor_server_block_settings() {
	$block_registry = WP_Block_Type_Registry::get_instance();
	$blocks         = array();
	$keys_to_pick   = array( 'title', 'description', 'icon', 'category', 'keywords', 'supports', 'attributes' );

	foreach ( $block_registry->get_all_registered() as $block_name => $block_type ) {
		foreach ( $keys_to_pick as $key ) {
			if ( ! isset( $block_type->{ $key } ) ) {
				continue;
			}

			if ( ! isset( $blocks[ $block_name ] ) ) {
				$blocks[ $block_name ] = array();
			}

			$blocks[ $block_name ][ $key ] = $block_type->{ $key };
		}
	}

	return $blocks;
}
