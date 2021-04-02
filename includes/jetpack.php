<?php

namespace frontenberg\jetpack;

function bootstrap() : void {
	// No more free advertising for Jetpack.
	add_filter( 'jetpack_gutenberg', '__return_false' );

	if ( class_exists( 'Jetpack_WPCOM_Block_Editor' ) ) {
		$jpwpcombe = \Jetpack_WPCOM_Block_Editor::init();
		remove_action( 'enqueue_block_editor_assets', [ $jpwpcombe, 'enqueue_block_editor_assets' ], 9 );
		remove_action( 'enqueue_block_assets', [ $jpwpcombe, 'enqueue_block_assets' ] );
		remove_action( 'mce_external_plugins', [ $jpwpcombe, 'add_tinymce_plugins' ] );
		remove_action( 'block_editor_settings', 'Jetpack\EditorType\remember_block_editor' );
		remove_action( 'login_init', [ $jpwpcombe, 'allow_block_editor_login' ] );
	}
}

