# WooCommerce Compatibility

## Decision

CartBay declares WooCommerce HPOS compatibility from the main plugin bootstrap on the `before_woocommerce_init` hook, using `Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CARTBAY_BASENAME, true )` so the declaration references the plugin basename constant used across the codebase.

CartBay registers Block Checkout additional fields after the `woocommerce_init` action using `woocommerce_register_additional_checkout_field()`.

## Rationale

WooCommerce's current HPOS extension recipe places compatibility declarations on `before_woocommerce_init`. Declaring compatibility there prevents WooCommerce from showing HPOS incompatibility warnings for CartBay. In our runtime verification, `FeaturesUtil::get_compatible_features_for_plugin( 'cartbay/cartbay.php' )` reported `custom_order_tables` in the `compatible` array, confirming the declaration is effective.

WooCommerce's current Block Checkout additional fields guide recommends calling `woocommerce_register_additional_checkout_field()` after `woocommerce_init`, so CartBay registers its checkout consent field from `CartBay\Core\CheckoutFields` on that hook.

## Logging

Checkout field registration and submitted consent values are written to the WooCommerce logger with source `cartbay`. Logs intentionally avoid raw email addresses, license keys, or tokens.

## Verification

On the local staging environment, WooCommerce `10.7.0` confirmed the `cartbay/marketing-consent` field registers in the `contact` location, renders in Block Checkout, submits successfully, and writes CartBay entries to the WooCommerce log source `cartbay`.
