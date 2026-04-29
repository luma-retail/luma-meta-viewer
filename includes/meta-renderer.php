<?php

namespace Luma\Metaviewer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the AJAX response for a supported object.
 */
function render_meta_response( $object_type, $object_id ) {
	if ( 'user' === $object_type ) {
		if ( ! \get_userdata( $object_id ) ) {
			\wp_die( 'User not found.' );
		}

		echo '<h2>User meta values</h2>';
		output_meta_list( 'user', $object_id );

		return;
	}

	if ( 'order' === $object_type ) {
		$order = get_order_object( $object_id );

		if ( ! $order ) {
			\wp_die( 'Order not found.' );
		}

		$order_details = get_order_property_values( $order );
		$order_meta    = get_order_meta_values( $object_id );

		render_collapsible_section(
			sprintf( 'Order details (%d)', count( $order_details ) ),
			function () use ( $order_details ) {
				output_normalized_meta_list( $order_details );
			},
			true
		);

		render_collapsible_section(
			sprintf( 'Order meta values (%d)', count( $order_meta ) ),
			function () use ( $order_meta ) {
				output_normalized_meta_list( $order_meta );
			},
			should_expand_meta_section( $order_meta, 12 )
		);

		render_order_item_meta_sections( $order );

		return;
	}

	if ( ! \get_post( $object_id ) ) {
		\wp_die( 'Post not found.' );
	}

	$heading = 'Meta values';
	$product = function_exists( 'wc_get_product' ) ? \wc_get_product( $object_id ) : false;

	if ( $product ) {
		$heading = 'Product meta values';
	}

	echo '<h2>' . esc_html( $heading ) . '</h2>';
	output_meta_list( 'post', $object_id );

	if ( $product && $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $variation_id ) {
			echo '<h3>Variation: ' . esc_html( \get_the_title( $variation_id ) ) . '</h3>';
			output_meta_list( 'post', $variation_id );
		}
	}
}

/**
 * Returns all meta for a supported object type.
 */
function get_meta_values( $object_type, $object_id ) {
	if ( 'user' === $object_type ) {
		return \get_user_meta( $object_id );
	}

	if ( 'order' === $object_type ) {
		return get_order_meta_values( $object_id );
	}

	return \get_post_meta( $object_id );
}

/**
 * Returns the WooCommerce order object when available.
 */
function get_order_object( $object_id ) {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return false;
	}

	return \wc_get_order( $object_id );
}

/**
 * Normalizes WooCommerce order meta into the same shape as post and user meta.
 */
function get_order_meta_values( $object_id ) {
	$order = get_order_object( $object_id );

	if ( ! $order ) {
		return array();
	}

	$meta = array();

	foreach ( $order->get_meta_data() as $meta_item ) {
		if ( is_object( $meta_item ) && method_exists( $meta_item, 'get_data' ) ) {
			$data = $meta_item->get_data();

			if ( isset( $data['key'] ) ) {
				$key = (string) $data['key'];

				if ( ! isset( $meta[ $key ] ) ) {
					$meta[ $key ] = array();
				}

				$meta[ $key ][] = isset( $data['value'] ) ? $data['value'] : '';
			}
		}
	}

	return $meta;
}

/**
 * Returns selected order properties in the same shape as meta values.
 */
function get_order_property_values( $order ) {
	return array(
		'id'                   => array( $order->get_id() ),
		'status'               => array( $order->get_status() ),
		'type'                 => array( $order->get_type() ),
		'created_via'          => array( $order->get_created_via() ),
		'currency'             => array( $order->get_currency() ),
		'payment_method'       => array( $order->get_payment_method() ),
		'payment_method_title' => array( $order->get_payment_method_title() ),
		'customer_id'          => array( $order->get_customer_id() ),
		'billing_email'        => array( $order->get_billing_email() ),
		'date_created'         => array( format_datetime_value( $order->get_date_created() ) ),
		'date_paid'            => array( format_datetime_value( $order->get_date_paid() ) ),
		'discount_total'       => array( $order->get_discount_total() ),
		'shipping_total'       => array( $order->get_shipping_total() ),
		'total_tax'            => array( $order->get_total_tax() ),
		'total'                => array( $order->get_total() ),
	);
}

/**
 * Formats WooCommerce date objects for display.
 */
function format_datetime_value( $date ) {
	if ( is_object( $date ) && method_exists( $date, 'date_i18n' ) ) {
		return $date->date_i18n( 'Y-m-d H:i:s' );
	}

	if ( is_object( $date ) && method_exists( $date, 'date' ) ) {
		return $date->date( 'Y-m-d H:i:s' );
	}

	return '';
}

/**
 * Renders order item sections and their meta data.
 */
function render_order_item_meta_sections( $order ) {
	$items = $order->get_items( array( 'line_item', 'shipping', 'fee', 'coupon', 'tax' ) );

	if ( empty( $items ) ) {
		return;
	}

	render_collapsible_section(
		sprintf( 'Order item meta (%d items)', count( $items ) ),
		function () use ( $items ) {
			foreach ( $items as $item_id => $item ) {
				$item_label = get_order_item_label( $item, $item_id );
				$item_meta  = get_wc_meta_values( $item );

				render_collapsible_section(
					sprintf( '%s (%d)', $item_label, count( $item_meta ) ),
					function () use ( $item_meta ) {
						output_normalized_meta_list( $item_meta );
					},
					should_expand_meta_section( $item_meta, 6 )
				);
			}
		},
		count( $items ) <= 3
	);
}

/**
 * Builds a readable label for an order item section.
 */
function get_order_item_label( $item, $item_id ) {
	$item_type = method_exists( $item, 'get_type' ) ? $item->get_type() : 'item';
	$item_name = method_exists( $item, 'get_name' ) ? $item->get_name() : '';

	if ( '' === $item_name ) {
		$item_name = ucfirst( str_replace( '_', ' ', $item_type ) );
	}

	return sprintf( '%s (#%d, %s)', $item_name, $item_id, $item_type );
}

/**
 * Normalizes WooCommerce meta data into the same shape as post and user meta.
 */
function get_wc_meta_values( $object ) {
	$meta = array();

	foreach ( $object->get_meta_data() as $meta_item ) {
		if ( is_object( $meta_item ) && method_exists( $meta_item, 'get_data' ) ) {
			$data = $meta_item->get_data();

			if ( isset( $data['key'] ) ) {
				$key = (string) $data['key'];

				if ( ! isset( $meta[ $key ] ) ) {
					$meta[ $key ] = array();
				}

				$meta[ $key ][] = isset( $data['value'] ) ? $data['value'] : '';
			}
		}
	}

	return $meta;
}

/**
 * Returns whether a section should be expanded by default.
 */
function should_expand_meta_section( $meta, $threshold ) {
	return count( $meta ) <= $threshold;
}

/**
 * Renders a collapsible section for large data sets.
 */
function render_collapsible_section( $title, $callback, $is_open ) {
	echo $is_open ? '<details open style="margin:0 0 12px;">' : '<details style="margin:0 0 12px;">';
	echo '<summary style="cursor:pointer;font-weight:600;">' . esc_html( $title ) . '</summary>';
	echo '<div style="margin-top:10px;">';
	call_user_func( $callback );
	echo '</div>';
	echo '</details>';
}

/**
 * Formats a meta value for display.
 */
function format_meta_value( $value ) {
	if ( \is_serialized( $value ) ) {
		$value = \maybe_unserialize( $value );
	}

	if ( is_array( $value ) || is_object( $value ) ) {
		$json = \wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		return false !== $json ? $json : print_r( $value, true );
	}

	if ( true === $value ) {
		return 'true';
	}

	if ( false === $value ) {
		return 'false';
	}

	if ( null === $value ) {
		return 'null';
	}

	return (string) $value;
}

/**
 * Renders a single meta list for a supported object type.
 */
function output_meta_list( $object_type, $object_id ) {
	$meta = get_meta_values( $object_type, $object_id );

	echo '<ul>';

	foreach ( $meta as $key => $values ) {
		echo '<li><strong>' . esc_html( $key ) . ':</strong> ';

		$val = isset( $values[0] ) ? $values[0] : '';

		echo '<code>' . esc_html( format_meta_value( $val ) ) . '</code>';

		if ( count( $values ) > 1 ) {
			echo '<br><span style="color:#888;font-size:0.85em;">Multiple values: ';
			foreach ( $values as $i => $v ) {
				echo esc_html( $i . ' => ' . format_meta_value( $v ) ) . ', ';
			}
			echo '</span>';
		}

		echo '</li>';
	}

	echo '</ul>';
}

/**
 * Renders a normalized meta array.
 */
function output_normalized_meta_list( $meta ) {
	if ( empty( $meta ) ) {
		echo '<p><em>No meta found.</em></p>';

		return;
	}

	echo '<ul>';

	foreach ( $meta as $key => $values ) {
		echo '<li><strong>' . esc_html( $key ) . ':</strong> ';

		$val = isset( $values[0] ) ? $values[0] : '';

		echo '<code>' . esc_html( format_meta_value( $val ) ) . '</code>';

		if ( count( $values ) > 1 ) {
			echo '<br><span style="color:#888;font-size:0.85em;">Multiple values: ';
			foreach ( $values as $i => $v ) {
				echo esc_html( $i . ' => ' . format_meta_value( $v ) ) . ', ';
			}
			echo '</span>';
		}

		echo '</li>';
	}

	echo '</ul>';
}