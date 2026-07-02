# Capture and Checkout

## Purpose
The capture subsystem records opted-in checkout visitors before they place an order. It supports classic checkout and Block Checkout, and both flows write through the same public REST endpoint.

## Block Checkout Consent Field
`app/Core/CheckoutFields.php` registers the WooCommerce additional checkout field:

- Field ID: `cartbay/marketing-consent`.
- Location: `contact`.
- Type: checkbox.
- Label source: `cartbay_settings['consent_text']` with fallback text.

The field registration is initialized from the core plugin bootstrap and is skipped when `cartbay_settings['capture_enabled']` is disabled.

Registered hooks:

- `woocommerce_init` -> `register_marketing_consent_field` — registers the additional checkout field.
- `woocommerce_blocks_validate_location_contact_fields` -> `log_marketing_consent_value` — logs submitted consent values via the WooCommerce logger.
- `woocommerce_get_default_value_for_cartbay/marketing-consent` -> `get_marketing_consent_default_value` — returns the configured default checked state.

## Classic Checkout Capture Asset
Classic checkout capture uses:

- Source: `src/capture/index.js`.
- Built asset: `assets/js/cartbay-capture.js`.
- Handle: `cartbay-capture`.

The asset is enqueued only on checkout pages, not order-received pages, and only when `cartbay_settings['capture_enabled']` is enabled.

The localized `cartbayCapture` object includes:

- REST endpoint URL.
- Cart hash.
- Cart total.
- Currency.
- Restore-safe cart items when available.
- Consent text.
- Default consent state.
- Whether checkout is already tied to a restored CartBay session.

Implemented behavior:

- Inserts a consent checkbox near `#billing_email_field`.
- Reads changes from `#billing_email`.
- Debounces capture requests.
- Stores `cartbay_session_id` in `sessionStorage`.
- Sends `source: classic`.
- Sends localized restore-safe item data with the capture payload.
- Captures a valid prefilled billing email shortly after checkout initialization when consent is checked.
- Skips initial prefilled-email capture for restored CartBay sessions, because restore identity already tracks that shopper/session.
- Sends a delete/withdrawal request when consent is unchecked.

## Block Checkout Capture Asset
Block checkout capture uses:

- Source: `src/block/index.js`.
- Built asset: `assets/js/cartbay-block.js`.
- Handle: `cartbay-block`.
- Enqueue hook: `woocommerce_blocks_enqueue_checkout_block_scripts_after`.
- Dependency: `wp-api-fetch`.

Implemented behavior:

- Finds the consent checkbox with `#contact-cartbay-marketing-consent`.
- Finds the contact email input in the block checkout DOM.
- Applies the configured default checked state using a `MutationObserver`.
- Sends `source: block`.
- Reads the current Store API cart and sends restore-safe item data with the capture payload.
- Captures a valid prefilled contact email after checkout initialization or Block Checkout DOM rendering when consent is checked.
- Skips initial prefilled-email capture for restored CartBay sessions, because restore identity already tracks that shopper/session.
- Stores `cartbay_session_id` in `sessionStorage`.
- Deletes the active capture when consent is withdrawn.

## Capture REST Route
`app/Api/Routes/CaptureRoute.php` registers:

- Method and path: `POST /wp-json/cartbay/v1/capture`.
- Permission callback: public.
- Protection: rate limiting and request validation.
- Args: `email`, `consent`, `cart`, `source`, and optional `session_id`.
- Source enum: `classic` or `block`.

The route checks the `capture` rate limit before delegating to `CaptureService`. Consented capture requests are ignored when `cartbay_settings['capture_enabled']` is disabled. The enabled flag is normalized so stored checkbox values such as `yes`, `no`, booleans, and numeric values are interpreted consistently.

## Capture Service
`app/Recovery/CaptureService.php` owns capture creation and updates.

`CaptureService::capture()` also refuses new capture when `cartbay_settings['capture_enabled']` is disabled, providing defense-in-depth for any internal callers outside the REST route.

Capture flow:

1. Reject suppressed emails by checking the `cartbay_suppressed` CPT through the hashed email.
2. Load retention and consent settings from `cartbay_settings`.
3. Build a server-side cart snapshot from `WC()->cart`, including product/variation IDs, quantities, variation attributes, safe cart item data, product snapshots, totals, coupons, currency, cart hash, and item count. If the server-side cart is unavailable or empty, build a restore-safe fallback snapshot from sanitized client cart data.
4. Build `_cartbay_cart_fingerprint` from product ID, variation ID, variation attributes, and safe cart item data. Shopper email identifies the shopper; the fingerprint identifies the cart.
5. Find an existing active session by explicit session ID when it can safely be updated. Captured sessions may update directly; abandoned sessions update only when their stored cart fingerprint matches the incoming cart fingerprint.
6. If no session ID match is usable, find an active session by the combination of email plus cart fingerprint within retention.
7. If no cart-identity match exists, create a new session even when the same shopper email already has other active sessions.
8. Build sanitized meta for email, consent, source, cart hash, cart fingerprint, cart total, currency, cart snapshot, item count, and activity time.
9. Update the matching cart session, or create a new one through `SessionRepository`.
10. Schedule `cartbay_detect_session_abandonment` at the configured inactivity timeout so the session is checked at its exact abandonment boundary.
11. Preserve the last non-empty snapshot when a later capture cannot resolve cart items.
12. Persist WooCommerce order line items on the CartBay session order from the active non-empty snapshot.
13. Log `updated` or `captured` events and invalidate analytics cache.

This means a single shopper email can own multiple CartBay sessions at the same time. Email is used for shopper grouping, suppression, and fallback attribution; it is not used as the sole active-cart identity.

## Consent Withdrawal
When consent is withdrawn, `CaptureService::delete_after_consent_withdrawal()`:

- Finds the active captured/abandoned session by session ID, falling back to the latest active session for the email when no session ID is available.
- Cancels pending `cartbay_detect_session_abandonment` Action Scheduler rows for that session.
- Cancels pending `cartbay_send_recovery_email` Action Scheduler rows for that session.
- Deletes the session order permanently.
- Logs the deletion through the WooCommerce logger source `cartbay`.

## Rate Limiting
`app/Utils/RateLimiter.php` stores counters in transients using this shape:

`cartbay_rl_{endpoint}_{md5(REMOTE_ADDR)}`

The default implemented public limit is 10 requests per 600 seconds.
