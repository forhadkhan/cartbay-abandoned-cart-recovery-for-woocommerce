# System Overview

Snapshot: implemented code in the current CartBay codebase.

## Product Shape
CartBay is a WooCommerce abandoned-cart recovery plugin. The free host captures opted-in checkout visitors, stores each captured cart as a WooCommerce order session, detects abandonment through Action Scheduler, sends a three-email recovery sequence, restores carts through tokenized links, and marks sessions recovered when a matching WooCommerce order completes. CartBay Pro is a separate add-on for licensing, private updates, and future premium extensions.

## Runtime Boundaries
- WordPress plugin entrypoint: `cartbay.php`.
- PHP namespace root: `WPAnchorBay\CartBay\`.
- Composer PSR-4 source root: `app/`.
- REST namespace: `cartbay/v1`.
- Text domain and slug: `cartbay`.
- Frontend source entries: `src/capture/index.js` and `src/block/index.js`.
- Built frontend assets: `assets/js/cartbay-capture.js` and `assets/js/cartbay-block.js`.
- Email templates: `templates/emails/recovery-email-1.php`, `recovery-email-2.php`, and `recovery-email-3.php`.

## Major Subsystems
- Core: `app/Core/Plugin.php`, `Container.php`, `Constants.php`, `Installer.php`, and `CheckoutFields.php`.
- Data: `app/Data/SessionRepository.php`.
- Recovery: `app/Recovery/*` services for capture, abandonment, email sequence, coupons, restore, notification tracking, and recovery matching.
- Email: `app/Email/*` WooCommerce email classes.
- API: `app/Api/Routes/*` REST routes.
- Admin: `app/Admin/Settings/*` and `app/Admin/Wizard/WizardController.php`.
- Pro add-on: `cartbay-pro/app/License/LicenseClient.php`, `app/Api/Routes/LicenseRoute.php`, `app/Core/Updater.php`, and `app/Admin/Settings/LicenseSettings.php`.
- Analytics: `app/Analytics/AnalyticsService.php`.
- Utilities: `app/Utils/TokenHelper.php`, `RateLimiter.php`, and `Logger.php`.

## End-To-End Lifecycle
1. A checkout page loads the capture asset only when capture is enabled and the page is checkout, not order-received.
2. Classic checkout JS injects a consent checkbox near billing email; Block Checkout uses a WooCommerce additional checkout field in the contact location.
3. JS posts email, consent, cart hash, cart total, currency, source, and optional session ID to `POST /wp-json/cartbay/v1/capture`.
4. `CaptureRoute` rate-limits the request and delegates to `CaptureService`.
5. `CaptureService` creates or updates a WooCommerce order-backed session through `SessionRepository` with status `wc-cartbay-captured`.
6. Recurring Action Scheduler hook `cartbay_detect_abandonment` finds inactive captured sessions and moves them to `wc-cartbay-abandoned`.
7. `AbandonmentScheduler` queues `cartbay_send_recovery_email` actions for the three configured sequence steps.
8. `EmailSequenceService` validates state, creates restore and unsubscribe tokens, optionally creates a coupon, and triggers the matching WooCommerce email class.
9. Restore links use `?cartbay_restore={token}` to validate the token, rebuild the cart from the stored server-side snapshot or session line items, record restore result events, optionally apply the coupon, store checkout attribution identity, and redirect to checkout.
10. Unsubscribe links use `?cartbay_unsubscribe={token}` to create a suppression record, suppress the session, and cancel future work.
11. Completed or processing WooCommerce orders are matched back to abandoned sessions by strongest available attribution: session ID, restore token, coupon, then email hash fallback. Pre-abandonment completions are logged separately and are not counted as recovered abandoned revenue.
12. Analytics aggregates tracked, abandoned, recovered, restored, email funnel, restore click, sequence performance, value, revenue, and recovery-rate metrics for admin reporting.

## Architectural Constraints Already Reflected In Code
- Session storage is WooCommerce-native and HPOS-safe through WC CRUD APIs.
- Public REST write paths are protected by rate limiting and validation rather than authentication.
- Admin REST routes and admin UI actions use `manage_woocommerce` capability checks.
- Recovery email delivery uses WooCommerce `WC_Email` subclasses and WooCommerce email templates.
- Background work uses Action Scheduler hooks, not WordPress cron events.
- Tokens are generated as 64-character opaque strings and stored as SHA-256 hashes.

## Notable Implementation Facts
- The implemented namespace is `WPAnchorBay\CartBay\`, which differs from older project notes that mention `CartBay\`.
- `cartbay_template` is registered both by the general CPT registration path and a dedicated template CPT registration method.
- `wp_mail_succeeded` is wired to mark CartBay notifications as sent when WordPress accepts the mail send; provider-confirmed delivery remains future integration scope.
- Capture stores a restore-ready server-side cart snapshot and WooCommerce session order line items; restore prefers the snapshot and falls back to line items.
