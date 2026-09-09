<?php
/**
 * Compatibility with WooCommerce core's own abandoned cart recovery email.
 *
 * @package WPAnchorBay\CartBay\Compat
 */

namespace WPAnchorBay\CartBay\Compat;

use WPAnchorBay\CartBay\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Prevents WooCommerce and CartBay both emailing the same shopper.
 *
 * WooCommerce 11.0 added an experimental "Abandoned cart recovery" feature
 * (WooCommerce > Settings > Advanced > Features). It works at a different point
 * in the funnel from CartBay: it acts on a real `pending` order created via
 * checkout or the Store API — a shopper who pressed Place Order and did not
 * pay — and sends one email two hours later. CartBay acts earlier, on a
 * consented checkout email with no order behind it yet.
 *
 * The two therefore do not fight over the same records: CartBay's session
 * orders carry `created_via = 'cartbay'`, and WooCommerce only considers
 * `checkout` and `store-api`. But one shopper can reach both paths — consent to
 * CartBay, then submit a checkout that goes unpaid — and would receive two sets
 * of recovery mail from one store.
 *
 * WooCommerce provides `woocommerce_abandoned_cart_recovery_suppress` for
 * exactly this, and defaults its own email off when it detects a plugin that
 * already does recovery. Its detection list is hardcoded to a couple of names,
 * so CartBay opts out explicitly rather than waiting to be listed.
 *
 * @since 1.1.1
 */
class CoreRecoveryEmail {

	/**
	 * Register hooks.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'woocommerce_abandoned_cart_recovery_suppress', array( $this, 'suppress_core_recovery_email' ) );
	}

	/**
	 * Tell WooCommerce not to send its own recovery email while CartBay is recovering.
	 *
	 * Only claims the shopper when CartBay capture is actually enabled. A store
	 * that has turned CartBay's capture off is not recovering anything, so
	 * WooCommerce's email is left alone rather than silently suppressed.
	 *
	 * @since 1.1.1
	 *
	 * @param bool $suppress Whether WooCommerce should skip its recovery email.
	 *
	 * @return bool
	 */
	public function suppress_core_recovery_email( $suppress ): bool {
		$suppress = (bool) $suppress;

		if ( ! Settings::is_capture_enabled() ) {
			return $suppress;
		}

		/**
		 * Filter whether CartBay suppresses WooCommerce's own abandoned cart recovery email.
		 *
		 * Return false to let both run — WooCommerce's single two-hour email on an
		 * unpaid order alongside CartBay's consented sequence. Shoppers who reach
		 * both paths will then receive both.
		 *
		 * @since 1.1.1
		 *
		 * @param bool $suppress Whether to suppress WooCommerce's recovery email. Default true.
		 */
		return (bool) apply_filters( 'cartbay_suppress_woocommerce_recovery_email', true );
	}
}
