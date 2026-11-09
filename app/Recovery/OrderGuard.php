<?php
/**
 * Order guard.
 *
 * @package WPAnchorBay\CartBay\Recovery
 */

namespace WPAnchorBay\CartBay\Recovery;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Answers one question: has this shopper already placed an order?
 *
 * Recovery emails must never reach a shopper who bought. Order statuses that
 * count as "ordered" are exposed through one filter so every caller agrees.
 *
 * @since 1.1.3
 */
class OrderGuard {

	/**
	 * Session meta key holding the order that was placed before abandonment.
	 *
	 * @since 1.1.3
	 *
	 * @var string
	 */
	public const ORDERED_META_KEY = '_cartbay_ordered_order_id';

	/**
	 * Order statuses (unprefixed) that count as "the shopper ordered".
	 *
	 * @since 1.1.3
	 *
	 * @return array<int, string>
	 */
	public static function ordered_statuses(): array {
		/**
		 * Filter the order statuses that count as "the shopper placed an order".
		 *
		 * A matching order stops the recovery email sequence. Only processing and
		 * completed orders are attributed as recovered revenue.
		 *
		 * @since 1.1.3
		 *
		 * @param array<int, string> $statuses Unprefixed order statuses.
		 */
		$statuses = apply_filters( 'cartbay_ordered_order_statuses', array( 'processing', 'completed', 'on-hold' ) );

		if ( ! is_array( $statuses ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'strval', $statuses ) ) );
	}

	/**
	 * Find an order the session's shopper placed after the session was created.
	 *
	 * CartBay session orders use their own statuses, so they never match here.
	 *
	 * @since 1.1.3
	 *
	 * @param WC_Order $session CartBay session order.
	 *
	 * @return int Matching order ID, or 0 when none exists.
	 */
	public static function find_order_id( WC_Order $session ): int {
		$email    = sanitize_email( (string) $session->get_billing_email() );
		$statuses = self::ordered_statuses();
		if ( '' === $email || array() === $statuses || ! function_exists( 'wc_get_orders' ) ) {
			return 0;
		}

		$created = $session->get_date_created();
		$since   = $created ? $created->getTimestamp() : 0;

		$ids = wc_get_orders(
			array(
				'type'          => 'shop_order',
				'billing_email' => $email,
				'status'        => $statuses,
				'date_created'  => '>=' . $since,
				'limit'         => 1,
				'return'        => 'ids',
				'orderby'       => 'date',
				'order'         => 'DESC',
			)
		);

		return is_array( $ids ) && ! empty( $ids ) ? absint( reset( $ids ) ) : 0;
	}
}
