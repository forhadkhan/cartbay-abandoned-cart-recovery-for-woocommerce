# WooCommerce Patterns Guide

Use this guide for concrete WooCommerce implementation work. It adds patterns from the Claude comparison pass, adjusted to keep public APIs, HPOS safety, and compatibility first.

## Woo Extension Lifecycle

| Hook | Use |
|---|---|
| `before_woocommerce_init` | Declare feature compatibility such as HPOS and Cart/Checkout Blocks |
| `plugins_loaded` | Detect WooCommerce availability and show dependency notice |
| `woocommerce_loaded` | Bootstrap Woo services that require Woo APIs |
| `woocommerce_init` | Work needing session/cart/customer availability |
| `woocommerce_blocks_loaded` | Register Blocks integrations |
| `rest_api_init` | Register REST controllers |

Never load Woo classes unguarded in a plugin that can activate without WooCommerce.

## HPOS Declaration

Declare HPOS compatibility only after an audit confirms all order access uses Woo APIs.

```php
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				MY_PLUGIN_FILE,
				true
			);
		}
	}
);
```

If the extension supports Cart and Checkout Blocks, declare that separately when the current WooCommerce docs recommend it for the target version.

## HPOS-Safe Order Access

Do:

```php
$order = wc_get_order( $order_id );

if ( ! $order instanceof \WC_Order ) {
	return;
}

$status = $order->get_status();
$email  = $order->get_billing_email();

$order->update_meta_data( '_my_plugin_key', $value );
$order->save();
```

Query with:

```php
$orders = wc_get_orders(
	array(
		'limit'      => 20,
		'status'     => 'processing',
		'meta_key'   => '_my_plugin_flag',
		'meta_value' => '1',
		'return'     => 'ids',
	)
);
```

Do not use `get_post_meta()`, `update_post_meta()`, `WP_Query` with `shop_order`, or direct SQL against order tables for order logic.

## Cart And Checkout Blocks

For checkout/cart extensions:

- Check whether the store uses block checkout, shortcode checkout, or both.
- Use official Blocks integration surfaces and Store API extension points.
- Register frontend and editor scripts with dependency asset files.
- Pass frontend data through Woo settings/script data where supported.
- Validate everything server-side; client data is advisory.
- Test both checkout flows if both are supported.

Acceptance criteria:

- Blocks integration registers only after Woo Blocks is loaded.
- Store API schema is explicit.
- Data callbacks do not leak private customer/order data.
- Cart/checkout hooks are cheap and side-effect safe.
- E2E tests cover at least add-to-cart, checkout display, validation failure, successful order, and reload/resume behavior.

## Payment Gateway Pattern

Payment gateways must cover:

- `WC_Payment_Gateway` implementation.
- Blocks payment method integration if block checkout is supported.
- Admin settings with secret fields masked.
- Idempotent API calls.
- Webhook signature verification.
- Transaction IDs stored through order CRUD/meta APIs.
- Refund handling through `process_refund()` where supported.
- Logs through `WC_Logger`, with secrets redacted.
- Customer-facing errors that do not expose gateway internals.
- Order notes that avoid sensitive payloads.

High-risk tests:

- Successful payment.
- Declined payment.
- Duplicate callback/webhook.
- Network timeout.
- Refund success/failure.
- HPOS enabled and disabled.
- Checkout Blocks and shortcode checkout if both are supported.

## Action Scheduler Pattern

Use Action Scheduler for Woo background work:

- Group actions under a plugin-specific group.
- Avoid duplicate recurring actions by checking for existing scheduled actions.
- Make handlers idempotent.
- Store only small scalar IDs in action args.
- Add retry-safe logging and failure handling.
- Unschedule recurring actions on deactivation when they are runtime tasks.

## Product Data Pattern

For product data:

- Support simple and variable products intentionally.
- Use Woo product CRUD where possible: `wc_get_product()`, getters/setters, `update_meta_data()`, `save()`.
- Product admin panels may use Woo field helpers, but save routines should still validate, sanitize, and write through the product object when feasible.
- Do not assume product type, price type, stock management, tax status, or variation structure.

## Woo Settings, Emails, And Admin

- Prefer Woo settings sections for configuration merchants expect under WooCommerce.
- Use Woo email classes/templates for commerce email flows.
- Use Woo admin/navigation patterns only when the extension has a real Woo workflow.
- Provide fallbacks for Woo admin APIs that are absent in older supported versions.

## Subscriptions

Subscriptions support is never implicit:

- Detect WooCommerce Subscriptions.
- Declare gateway support only when renewal, cancellation, suspension, reactivation, amount/date changes, and payment method changes are implemented as appropriate.
- Test renewals, failed renewals, manual renewals, payment method changes, and cancellation flows.

