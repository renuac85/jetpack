<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

/**
 * Get orders backup endpoint class.
 *
 * /sites/%s/orders/%d/backup -> $blog_id, $post_id
 */
class Jetpack_JSON_API_Get_WC_Order_Backup_Endpoint extends Jetpack_JSON_API_Endpoint {

	/**
	 * Needed capabilities.
	 *
	 * @var array
	 */
	protected $needed_capabilities = array(); // This endpoint is only accessible using a site token

	/**
	 * The order ID.
	 *
	 * @var int
	 */
	protected $order_id;

	/**
	 * Validate the input.
	 *
	 * @param int $order_id - the order ID.
	 */
	public function validate_input( $order_id ) {
		if ( empty( $order_id ) || ! is_numeric( $order_id ) ) {
			return new WP_Error( 'order_id_not_specified', __( 'You must specify a Order ID', 'jetpack' ), 400 );
		}

		$this->order_id = (int) $order_id;

		return true;
	}

	/**
	 * The result.
	 *
	 * @return array|WP_Error
	 */
	protected function result() {
		// Disable Sync as this is a read-only operation and triggered by sync activity.
		\Automattic\Jetpack\Sync\Actions::mark_sync_read_only();

		$order = WooCommerce_HPOS_Orders::get_object_by_id( 'order', $this->order_id );
		if ( empty( $order ) ) {
			return new WP_Error( 'order_not_found', __( 'Order not found', 'jetpack' ), 404 );
		}

		return array(
			'order' => (array) $order,
		);
	}
}
