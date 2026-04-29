<?php

namespace Luma\Metaviewer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handler: returns meta for the requested object.
 */
function ajax_get_meta() {
	\check_ajax_referer( 'luma_meta_viewer_nonce', 'nonce' );

	$object_type = isset( $_POST['object_type'] ) ? \sanitize_key( $_POST['object_type'] ) : 'post';
	$object_id   = isset( $_POST['object_id'] ) ? \absint( $_POST['object_id'] ) : 0;

	// Backward compatibility with the old payload shape.
	if ( ! $object_id && isset( $_POST['post_id'] ) ) {
		$object_type = 'post';
		$object_id   = \absint( $_POST['post_id'] );
	}

	if ( ! $object_id && isset( $_POST['user_id'] ) ) {
		$object_type = 'user';
		$object_id   = \absint( $_POST['user_id'] );
	}

	if ( ! $object_id && isset( $_POST['order_id'] ) ) {
		$object_type = 'order';
		$object_id   = \absint( $_POST['order_id'] );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		\wp_die( 'No access.' );
	}

	if ( ! in_array( $object_type, array( 'post', 'user', 'order' ), true ) ) {
		\wp_die( 'Unsupported object type.' );
	}

	if ( ! $object_id ) {
		\wp_die( 'Invalid object.' );
	}

	ob_start();

	render_meta_response( $object_type, $object_id );

	echo ob_get_clean();
	\wp_die();
}
add_action( 'wp_ajax_luma_meta_viewer_get_meta', __NAMESPACE__ . '\\ajax_get_meta' );

/**
 * Legacy AJAX alias for older user-meta payloads.
 */
function ajax_get_user_meta() {
	$_POST['object_type'] = 'user';
	$_POST['object_id']   = isset( $_POST['user_id'] ) ? \absint( $_POST['user_id'] ) : 0;

	ajax_get_meta();
}
add_action( 'wp_ajax_luma_meta_viewer_get_user_meta', __NAMESPACE__ . '\\ajax_get_user_meta' );