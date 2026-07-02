# Data Model

## Storage Strategy
CartBay uses WooCommerce orders as cart recovery sessions. There are no custom session database tables in the implemented code. Session reads and writes go through WooCommerce CRUD helpers such as `wc_create_order()`, `wc_get_order()`, and `wc_get_orders()` via `app/Data/SessionRepository.php`.

## Session Entity
A CartBay session is a WooCommerce order with:

- `created_via` set to `cartbay`.
- Billing email set to the captured customer email.
- A CartBay lifecycle status.
- CartBay-specific metadata prefixed with `_cartbay_`.

The repository creates sessions initially as `cartbay-captured`; WooCommerce stores and filters statuses with the `wc-` prefixed names.

## Session Statuses
- `wc-cartbay-captured` - checkout visitor consented and CartBay captured a session.
- `wc-cartbay-abandoned` - session exceeded the configured inactivity timeout.
- `wc-cartbay-recovered` - a later order matched the abandoned session.
- `wc-cartbay-expired` - session passed retention and was pruned/expired.
- `wc-cartbay-suppressed` - session was suppressed after unsubscribe.

## Session Meta Keys
Implemented session metadata includes:

- `_cartbay_session_id`
- `_cartbay_email_hash`
- `_cartbay_email`
- `_cartbay_consent`
- `_cartbay_consent_text`
- `_cartbay_consent_at`
- `_cartbay_source`
- `_cartbay_cart_hash`
- `_cartbay_cart_fingerprint`
- `_cartbay_cart_total`
- `_cartbay_cart_snapshot`
- `_cartbay_cart_item_count`
- `_cartbay_currency`
- `_cartbay_captured_at`
- `_cartbay_last_activity_at`
- `_cartbay_abandoned_at`
- `_cartbay_recovered_at`
- `_cartbay_recovered_order_id`
- `_cartbay_recovered_revenue`
- `_cartbay_attribution_source`
- `_cartbay_matched_email_hash`
- `_cartbay_recovered_notification_id`
- `_cartbay_restored`
- `_cartbay_restore_clicked_at`
- `_cartbay_restore_result`
- `_cartbay_sequence_step`
- `_cartbay_sent_steps`
- `_cartbay_events`
- `_cartbay_notifications`
- `_cartbay_token_hash`
- `_cartbay_token_expires_at`
- `_cartbay_token_hashes`
- `_cartbay_unsub_token_hash`
- `_cartbay_coupon_code`
- `_cartbay_coupon_expires_at`

`_cartbay_email_hash` identifies the shopper. It is not the cart-session identity. Cart session identity is represented by `_cartbay_session_id` plus `_cartbay_cart_fingerprint`, so one shopper email may have multiple active CartBay sessions when the carts are materially different.

`_cartbay_cart_fingerprint` is a SHA-256 hash built from restore-relevant item identity: product ID, variation ID, variation attributes, and safe cart item data. Quantity and price are intentionally excluded so the same cart session can be updated when the shopper adjusts quantities before abandonment.

## Event Log
`SessionRepository::add_event()` appends structured events to `_cartbay_events`. Implemented event names include:

- `captured`
- `updated`
- `abandoned`
- `email_sent`
- `email_failed`
- `restore_clicked`
- `cart_restore_started`
- `cart_restored`
- `cart_restore_partial`
- `cart_restore_failed`
- `completed_before_abandonment`
- `recovered`
- `unsubscribed`

## Notifications
Notification state is stored on each session in `_cartbay_notifications`. `NotificationService` uses this session meta as the source of truth and creates transient context records keyed as `cartbay_notification_ctx_{notification_id}` for the configured retention window.

Implemented notification statuses include:

- `queued`
- `attempted`
- `sent`
- `failed`
- `retry_queued`
- `canceled`

`delivered` is reserved for explicit external provider integrations and is not used for ordinary WordPress/WooCommerce mail success.

## Suppression Records
Suppression entries use the private `cartbay_suppressed` CPT. The slug/title is the SHA-256 hash of the normalized email address. `TokenHelper::hash_email()` performs the email normalization and hashing.

## Coupons
`CouponService` creates recovery coupons with `WC_Coupon`. Coupon codes use the `CARTBAY-` prefix followed by a random suffix.

Coupon metadata includes:

- `_cartbay_session_id`
- `_cartbay_email`
- `_cartbay_generated`
- `_cartbay_offer_note`

The matching session stores `_cartbay_coupon_code` and `_cartbay_coupon_expires_at`. Generated coupons are scoped to one CartBay session and are validated against the restored session identity plus the restored/checkout email before use.

## Options
Implemented option storage includes:

- `cartbay_settings` - capture, consent, abandonment timeout, offers, retention, admin navigation, uninstall cleanup preference, test mode, and related operational settings.
- `cartbay_settings['log_enabled']`, `cartbay_settings['log_retention_days']`, and `cartbay_settings['log_max_size_mb']` - CartBay file log controls.
- `cartbay_campaign_settings` - recovery sequence enablement and per-step timing/coupon controls.
- `cartbay_license_data` - license key and cached status data.
- `cartbay_wizard_complete` - first-run wizard completion flag.
- `cartbay_sequence_defaults_version` - default sequence seed/version marker.

## Transients
Implemented transient usage includes:

- `cartbay_license_valid` - cached license validity for 12 hours.
- `cartbay_analytics_cache` - cached analytics metrics.
- `cartbay_rl_{endpoint}_{md5(REMOTE_ADDR)}` - public endpoint rate limit counters.
- `cartbay_notification_ctx_{notification_id}` - notification context for delivery/failure tracking.
- `cartbay_wizard_redirect` - short-lived (60s) flag to trigger first-run wizard redirect on `admin_init`.

## Retention
`SessionRepository` queries expired sessions by `date_created` and active CartBay statuses. The recurring `cartbay_prune_sessions` job delegates retention pruning to the repository.
