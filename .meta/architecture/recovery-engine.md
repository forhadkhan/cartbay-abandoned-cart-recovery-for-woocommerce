# Recovery Engine

## Purpose
The recovery engine moves captured sessions through abandonment, email sequencing, restore, unsubscribe, and recovery matching.

## Abandonment Detection
`app/Recovery/AbandonmentScheduler.php` runs from two Action Scheduler hooks:

- `cartbay_detect_session_abandonment` for an exact per-session timeout check scheduled when a cart is captured or updated.
- `cartbay_detect_abandonment` every 5 minutes as a fallback scanner for missed or legacy sessions.

Implemented behavior:

- Reads `cartbay_settings['abandonment_timeout']`, defaulting to 30 minutes.
- For per-session checks, validates the session is still `wc-cartbay-captured` and compares `_cartbay_last_activity_at` to the configured timeout.
- For fallback scans, queries inactive `wc-cartbay-captured` sessions through `SessionRepository::get_inactive_captured()`.
- Sets qualifying sessions to `wc-cartbay-abandoned`.
- Writes `_cartbay_abandoned_at`.
- Appends an `abandoned` event.
- Schedules recovery email actions for the configured sequence when the campaign is enabled. Sessions are still marked abandoned when campaigns are disabled so analytics remain accurate.

## Sequence Settings
`app/Recovery/SequenceSettings.php` defines the three-step default recovery sequence:

- Step 1: 45 minutes.
- Step 2: 24 hours.
- Step 3: 72 hours.

Coupons are enabled by default only for step 3.

## Email Job Execution
`app/Recovery/EmailSequenceService.php` handles `cartbay_send_recovery_email` with arguments `[session_id, step_index]`.

Recovery email jobs are Action Scheduler single actions. If the site, server, cron runner, or network is unavailable at the scheduled time, the action remains pending and is processed when Action Scheduler runs again. CartBay does not drop overdue pending actions; they send late after the queue resumes, subject to the guards below.

Guards before sending:

- Session must still exist.
- Session must still be `cartbay-abandoned` as returned by `WC_Order::get_status()`.
- Email must not be suppressed.
- Step must not already be present in `_cartbay_sent_steps`.

Send flow:

1. Create a hashed restore token entry and unsubscribe token.
2. Optionally create a coupon for the step through `CouponService`.
3. Create notification tracking state through `NotificationService`.
4. Trigger the WooCommerce email class for the step.
5. Append `email_sent` or `email_failed` to the session event log.
6. Retry failed sends up to three attempts, requeueing after `15 * attempts` minutes. These retries cover WordPress/WooCommerce mail send failures, not downstream provider bounces unless a provider integration reports delivery/failure state.

## Restore Flow
Restore links use query parameter `cartbay_restore` and are handled on `template_redirect` by `Plugin::handle_restore_request()` so WooCommerce cart/session APIs are available.

`app/Recovery/RestoreService.php`:

- Hashes the supplied token and looks up `_cartbay_token_hash`.
- Falls back to `_cartbay_token_hashes` so earlier recovery email links remain valid until their individual expiry.
- Validates token expiry before any cart mutation.
- Validates that the saved session has restorable items before emptying the current WooCommerce cart.
- Empties the current WooCommerce cart only after at least one saved item is available to restore.
- Rebuilds the cart from `_cartbay_cart_snapshot`, with session order line items as a fallback.
- Validates products are available and purchasable before adding them to the cart.
- Records `restore_clicked`, `cart_restore_started`, `_cartbay_restore_result`, and final restore result events (`cart_restored`, `cart_restore_partial`, or `cart_restore_failed`).
- Stores restore identity in the WooCommerce session so checkout orders can be attributed by session ID/token/notification.
- Sets the restored email on the WooCommerce customer object and stores it in the WooCommerce session so checkout can pre-fill billing email and WooCommerce core coupon email restrictions can pass.
- Applies `_cartbay_coupon_code` when present and safe.
- Avoids applying CartBay coupons when cart items include subscription product types.
- Redirects to WooCommerce checkout.

## Unsubscribe Flow
Unsubscribe links use query parameter `cartbay_unsubscribe` and are handled on `init` by `Plugin::handle_unsubscribe_request()`.

Implemented behavior:

- Hashes and validates the unsubscribe token against `_cartbay_unsub_token_hash`.
- Creates a `cartbay_suppressed` post using the normalized email hash.
- Marks the session `wc-cartbay-suppressed`.
- Cancels pending recovery email jobs and notification records.
- Appends an `unsubscribed` event.

## Coupon Service
`app/Recovery/CouponService.php` creates and invalidates recovery coupons.

Coupon settings are read from `cartbay_settings`:

- `coupon_type`, default `fixed_cart`.
- `coupon_amount`, default `10`.
- `coupon_expiry_days`, default `7`.

Coupon codes use `CARTBAY-{random}`. Session meta stores the generated code and expiry. Coupon meta stores the CartBay session ID, email, generated flag, and offer note. Generated coupons are one active coupon per CartBay session, single-use, individual-use, and email-restricted. CartBay additionally validates generated coupons against the restored session identity and restored/checkout email so a coupon code cannot be used outside the matching recovery flow.

Invalidation either caps usage or deletes unused generated coupons, depending on coupon state.

## Recovery Matching
`app/Recovery/RecoveryMatcher.php` registers WooCommerce order hooks for payment completion, order status changes, and checkout order creation.

Registered hooks:

- `woocommerce_payment_complete` -> `handle_order()` — matches on payment completion.
- `woocommerce_order_status_changed` -> `handle_status_change( 10, 4 )` — matches on status transitions to processing/completed.
- `woocommerce_checkout_create_order` -> `attach_checkout_attribution( 20 )` — stores checkout attribution identity (session ID, restore token, notification ID) on the order for later session matching.

Implemented matching behavior:

- Watches completed/processing orders.
- Prefers attribution by CartBay session ID, restore token hash, coupon metadata, then billing email hash fallback.
- When fallback attribution by email is required, picks the latest recoverable session for that shopper, preferring abandoned sessions over captured sessions. Restore-token, session-ID, and coupon attribution remain the authoritative paths for shoppers with multiple active carts.
- Only marks sessions recovered when they are abandoned or have `_cartbay_abandoned_at` set.
- Records `completed_before_abandonment` when checkout completes before abandonment timeout instead of counting recovered revenue.
- Marks the session `wc-cartbay-recovered`.
- Writes recovered order ID, recovery timestamp, recovered revenue, attribution source, matched email hash, related notification ID, restored flag, and restore click timestamp when available.
- Cancels future recovery email jobs.
- Cancels pending notifications with reason `recovered`.
- Invalidates the recovery coupon.
- Appends a `recovered` event.

## Token Model
`app/Utils/TokenHelper.php` generates 64-character opaque tokens using `wp_generate_password( 64, false )` and stores SHA-256 hashes. Restore token TTL defaults to 48 hours. Each email token hash is appended to `_cartbay_token_hashes`, allowing older recovery emails in the same sequence to keep working without storing plain tokens.
