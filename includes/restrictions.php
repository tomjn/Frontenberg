<?php

namespace frontenberg\restrictions;

function bootstrap() : void {
	// Disable the post locked dialog.
	add_filter( 'show_post_locked_dialog', '__return_false' );
	add_filter( 'wp_check_post_lock_window', '__return_false' );

	// Attempt to disable post locking.
	add_filter( 'update_post_metadata', __NAMESPACE__ . '\\update_post_metadata', 10, 3 );

	// Disable use XML-RPC.
	add_filter( 'xmlrpc_enabled', '__return_false' );

	// Disable X-Pingback to header.
	add_filter( 'wp_headers', __NAMESPACE__ . '\\disable_x_pingback' );

	add_filter( 'user_has_cap', __NAMESPACE__ . '\\filter_user_permissions', 10, 1 );

	add_filter( 'get_post_metadata', __NAMESPACE__ . '\\override_post_lock', 100, 3 );

	add_action( 'init', __NAMESPACE__ . '\\init' );

	add_action( 'wp_footer', __NAMESPACE__ . '\\wp_footer', 99 );

	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\block_apifetch_modifications', 99 );
}

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
function filter_user_permissions( array $allcaps ) : array {
	if ( is_user_logged_in() ) {
		return $allcaps;
	}

	// give author some permissions.
	$allcaps['read']                 = true;
	$allcaps['manage_categories']    = false;
	$allcaps['edit_post']            = true;
	$allcaps['edit_posts']           = true;
	$allcaps['edit_others_posts']    = true;
	$allcaps['edit_published_posts'] = true;

	// remove some capabilities the user should never have.
	$allcaps['edit_pages']           = false;
	$allcaps['switch_themes']        = false;
	$allcaps['edit_themes']          = false;
	$allcaps['edit_pages']           = false;
	$allcaps['activate_plugins']     = false;
	$allcaps['edit_plugins']         = false;
	$allcaps['edit_users']           = false;
	$allcaps['import']               = false;
	$allcaps['unfiltered_html']      = false;
	$allcaps['edit_plugins']         = false;
	$allcaps['unfiltered_upload']    = false;

	return $allcaps;
}

/**
 * Prevent the creation of various types of content.
 *
 * @return void
 */
function init() : void {
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

/**
 * Override the post locks.
 *
 * @param mixed  $metadata value
 * @param mixed  $object_id ID
 * @param string $meta_key key
 *
 * @return mixed
 */
function override_post_lock( $metadata, $object_id, $meta_key ) {
	// Here is the catch, add additional controls if needed (post_type, etc).
	$meta_needed = '_edit_lock';
	if ( isset( $meta_key ) && $meta_needed === $meta_key ){
		return false;
	}
	// Return original if the check does not pass.
	return $metadata;
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
function wp_footer() : void {
	?>
<script>
	if ( window._wpLoadBlockEditor ) {
		window._wpLoadBlockEditor.then( function( editor ) {
			wp.data.dispatch( 'core/editor' ).lockPostAutosaving( 'no-autosave' );
			wp.data.dispatch( 'core/editor' ).lockPostSaving( 'no-publish' );
			wp.data.select( 'core/editor' ).isEditedPostDirty = function() {
				return false;
			}
		} );
	}
</script>
	<?php
}

function block_apifetch_modifications() : void {
	$script = <<<SCRIPT
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
	SCRIPT;
	wp_add_inline_script( 'wp-api-fetch', $script );
}

function update_post_metadata( $check, $object_id, $meta_key ) {
	if ( '_edit_lock' === $meta_key ) {
		return false;
	}
	return $check;
}
