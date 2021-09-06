<?php

if ( ! function_exists( 'use_block_editor_for_post_type' ) ) {
	function use_block_editor_for_post_type( $post_type ) { return true; }
}

if ( ! function_exists( 'get_block_editor_server_block_settings' ) ) {
	function get_block_editor_server_block_settings() {
		$block_registry = WP_Block_Type_Registry::get_instance();
		$blocks         = array();
		$fields_to_pick = array(
			'api_version'      => 'apiVersion',
			'title'            => 'title',
			'description'      => 'description',
			'icon'             => 'icon',
			'attributes'       => 'attributes',
			'provides_context' => 'providesContext',
			'uses_context'     => 'usesContext',
			'supports'         => 'supports',
			'category'         => 'category',
			'styles'           => 'styles',
			'textdomain'       => 'textdomain',
			'parent'           => 'parent',
			'keywords'         => 'keywords',
			'example'          => 'example',
		);

		foreach ( $block_registry->get_all_registered() as $block_name => $block_type ) {
			foreach ( $fields_to_pick as $field => $key ) {
				if ( ! isset( $block_type->{ $field } ) ) {
					continue;
				}

				if ( ! isset( $blocks[ $block_name ] ) ) {
					$blocks[ $block_name ] = array();
				}

				$blocks[ $block_name ][ $key ] = $block_type->{ $field };
			}
		}

		return $blocks;
	}
}

if ( ! function_exists( 'get_block_categories' ) ) {
	function get_block_categories( $post ) {
		$default_categories = array(
			array(
				'slug'  => 'text',
				'title' => _x( 'Text', 'block category' ),
				'icon'  => null,
			),
			array(
				'slug'  => 'media',
				'title' => _x( 'Media', 'block category' ),
				'icon'  => null,
			),
			array(
				'slug'  => 'design',
				'title' => _x( 'Design', 'block category' ),
				'icon'  => null,
			),
			array(
				'slug'  => 'widgets',
				'title' => _x( 'Widgets', 'block category' ),
				'icon'  => null,
			),
			array(
				'slug'  => 'embed',
				'title' => _x( 'Embeds', 'block category' ),
				'icon'  => null,
			),
			array(
				'slug'  => 'reusable',
				'title' => _x( 'Reusable Blocks', 'block category' ),
				'icon'  => null,
			),
		);

		/**
		 * Filters the default array of block categories.
		 *
		 * @since 5.0.0
		 *
		 * @param array[] $default_categories Array of block categories.
		 * @param WP_Post $post               Post being loaded.
		 */
		return apply_filters( 'block_categories', $default_categories, $post );
	}
}

if ( ! function_exists( 'wp_check_post_lock' ) ) {
	function wp_check_post_lock( $post_id ) { return false; }
}

if ( ! function_exists( 'wp_set_post_lock' ) ) {
	function wp_set_post_lock() {}
}

if ( ! function_exists( 'add_meta_box' ) ) {
	function add_meta_box() {}
}

if ( ! function_exists( 'get_page_templates' ) ) {
	function get_page_templates() { return []; }
}

if ( ! function_exists( 'the_block_editor_meta_boxes' ) ) {
	function the_block_editor_meta_boxes() {}
}

if ( ! function_exists( 'get_sample_permalink' ) ) {
	function get_sample_permalink( $id, $title = null, $name = null ) {
		$post = get_post( $id );
		if ( ! $post ) {
			return array( '', '' );
		}

		$ptype = get_post_type_object( $post->post_type );

		$original_status = $post->post_status;
		$original_date   = $post->post_date;
		$original_name   = $post->post_name;

		// Hack: get_permalink() would return plain permalink for drafts, so we will fake that our post is published.
		if ( in_array( $post->post_status, array( 'draft', 'pending', 'future' ), true ) ) {
			$post->post_status = 'publish';
			$post->post_name   = sanitize_title( $post->post_name ? $post->post_name : $post->post_title, $post->ID );
		}

		// If the user wants to set a new name -- override the current one.
		// Note: if empty name is supplied -- use the title instead, see #6072.
		if ( ! is_null( $name ) ) {
			$post->post_name = sanitize_title( $name ? $name : $title, $post->ID );
		}

		$post->post_name = wp_unique_post_slug( $post->post_name, $post->ID, $post->post_status, $post->post_type, $post->post_parent );

		$post->filter = 'sample';

		$permalink = get_permalink( $post, true );

		// Replace custom post_type token with generic pagename token for ease of use.
		$permalink = str_replace( "%$post->post_type%", '%pagename%', $permalink );

		// Handle page hierarchy.
		if ( $ptype->hierarchical ) {
			$uri = get_page_uri( $post );
			if ( $uri ) {
				$uri = untrailingslashit( $uri );
				$uri = strrev( stristr( strrev( $uri ), '/' ) );
				$uri = untrailingslashit( $uri );
			}

			/** This filter is documented in wp-admin/edit-tag-form.php */
			$uri = apply_filters( 'editable_slug', $uri, $post );
			if ( ! empty( $uri ) ) {
				$uri .= '/';
			}
			$permalink = str_replace( '%pagename%', "{$uri}%pagename%", $permalink );
		}

		/** This filter is documented in wp-admin/edit-tag-form.php */
		$permalink         = array( $permalink, apply_filters( 'editable_slug', $post->post_name, $post ) );
		$post->post_status = $original_status;
		$post->post_date   = $original_date;
		$post->post_name   = $original_name;
		unset( $post->filter );

		/**
		* Filters the sample permalink.
		*
		* @since 4.4.0
		*
		* @param array   $permalink {
		*     Array containing the sample permalink with placeholder for the post name, and the post name.
		*
		*     @type string $0 The permalink with placeholder for the post name.
		*     @type string $1 The post name.
		* }
		* @param int     $post_id   Post ID.
		* @param string  $title     Post title.
		* @param string  $name      Post name (slug).
		* @param WP_Post $post      Post object.
		*/
		return apply_filters( 'get_sample_permalink', $permalink, $post->ID, $title, $name, $post );
	}
}

require_once ABSPATH . '/wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . '/wp-admin/includes/screen.php';
