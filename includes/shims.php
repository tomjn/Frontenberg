<?php

function use_block_editor_for_post_type( $post_type ) { return true; }
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

function wp_check_post_lock( $post_id ) { return false; }
function wp_set_post_lock() {}
function add_meta_box() {}
function get_page_templates() { return []; }
function the_block_editor_meta_boxes() {}

require_once ABSPATH . '/wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . '/wp-admin/includes/screen.php';
