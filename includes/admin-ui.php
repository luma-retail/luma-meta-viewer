<?php

namespace Luma\Metaviewer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the product and order meta boxes.
 */
function register_meta_boxes() {
	if ( current_user_can( 'manage_options' ) ) {
		$screens = array( 'shop_order' );

		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screens[] = \wc_get_page_screen_id( 'shop-order' );
		}

		$screens = array_filter( array_unique( $screens ) );

		\add_meta_box(
			'frukvist-show-meta',
			'Meta (Debug)',
			__NAMESPACE__ . '\\show_meta_button',
			'product',
			'normal',
			'default'
		);

		\add_meta_box(
			'frukvist-show-order-meta',
			'Order Meta (Debug)',
			__NAMESPACE__ . '\\show_order_meta_button',
			$screens,
			'normal',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\\register_meta_boxes', 9999 );

/**
 * Register the user meta debug section on profile pages.
 */
function register_user_meta_section( $user ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<h2>User Meta (Debug)</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">Meta viewer</th>
			<td>
				<?php
				render_meta_viewer(
					array(
						'object_type'  => 'user',
						'object_id'    => $user->ID,
						'button_label' => 'Show User Meta',
					)
				);
				?>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', __NAMESPACE__ . '\\register_user_meta_section' );
add_action( 'edit_user_profile', __NAMESPACE__ . '\\register_user_meta_section' );

/**
 * Meta box content: button + AJAX result container.
 */
function show_meta_button( $post ) {
	render_meta_viewer(
		array(
			'object_type'  => 'post',
			'object_id'    => $post->ID,
			'button_label' => 'Show Meta',
		)
	);
}

/**
 * Order meta box content: button + AJAX result container.
 */
function show_order_meta_button( $order_or_post ) {
	$order_id = get_editor_object_id( $order_or_post );

	if ( ! $order_id ) {
		echo '<em>Order not found.</em>';

		return;
	}

	render_meta_viewer(
		array(
			'object_type'  => 'order',
			'object_id'    => $order_id,
			'button_label' => 'Show Order Meta',
		)
	);
}

/**
 * Normalizes IDs from WP_Post, WC_Order, and similar editor objects.
 */
function get_editor_object_id( $object ) {
	if ( is_object( $object ) && method_exists( $object, 'get_id' ) ) {
		return (int) $object->get_id();
	}

	if ( is_object( $object ) && isset( $object->ID ) ) {
		return (int) $object->ID;
	}

	return 0;
}

/**
 * Renders a reusable meta viewer button, output container, and script.
 */
function render_meta_viewer( $args ) {
	static $instance = 0;

	$args = \wp_parse_args(
		$args,
		array(
			'object_type'  => 'post',
			'object_id'    => 0,
			'button_label' => 'Show Meta',
		)
	);

	$instance++;
	$output_id = 'frukvist-meta-output-' . $instance;
	?>
	<button type="button"
		class="button button-primary frukvist-load-meta"
		data-object-type="<?php echo esc_attr( $args['object_type'] ); ?>"
		data-object-id="<?php echo esc_attr( $args['object_id'] ); ?>"
		data-output-id="<?php echo esc_attr( $output_id ); ?>">
		<?php echo esc_html( $args['button_label'] ); ?>
	</button>

	<div id="<?php echo esc_attr( $output_id ); ?>" style="margin-top:20px;"></div>

	<?php render_meta_viewer_script(); ?>
	<?php
}

/**
 * Prints the shared JavaScript once per page.
 */
function render_meta_viewer_script() {
	static $script_rendered = false;

	if ( $script_rendered ) {
		return;
	}

	$script_rendered = true;
	?>
	<script type="text/javascript">
		jQuery(function($){
			$(document).on('click', '.frukvist-load-meta', function(){
				let button = $(this);
				let objectType = button.data('object-type');
				let objectId = button.data('object-id');
				let output = $('#' + button.data('output-id'));

				output.html('<em>Loading…</em>');

				$.post(ajaxurl, {
					action: 'luma_meta_viewer_get_meta',
					object_type: objectType,
					object_id: objectId,
					nonce: '<?php echo \wp_create_nonce( 'luma_meta_viewer_nonce' ); ?>'
				}, function(response){
					output.html(response);
				});
			});
		});
	</script>
	<?php
}