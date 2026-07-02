# AGENTS.md — CartBay

**Read this file completely before writing any code.**

CartBay is a premium WooCommerce plugin that recovers abandoned carts via a 3-email recovery sequence. It uses WooCommerce-native storage (order objects as sessions), Action Scheduler for background jobs, a REST API for checkout capture, and WP Anchor Bay License Server for licensing. No custom database tables in v1.

---

## Completion Verification

- Before claiming a task, phase, commit, push, or documentation update is complete, re-read the exact target files from disk and confirm the expected content is present.
- For overlay-managed files (`.meta/`, `.agents/`, `.github/`, `AGENTS.md`, `GEMINI.md`, `graphify-out/`), also verify the relevant `config-assets` branch object with `git show config-assets:path/to/file` after committing.
- Do not treat a clean `main` working tree as proof that ignored overlay files are correct.
- If pushing, separate the push result from remote verification. Only say remote verification succeeded after a successful remote ref/content check.

---

## Distribution, License, and Updater Policy

- CartBay is a privately distributed premium plugin. It is not intended for WordPress.org hosting.
- Keep the plugin license metadata proprietary/commercial. Do not change it to `GPL-2.0-or-later` solely to satisfy WordPress.org Plugin Check.
- Keep `app/Core/Updater.php` and the `yahnis-elsts/plugin-update-checker` dependency. The private updater is required for licensed update delivery through WP Anchor Bay License Server.
- WordPress.org-only Plugin Check findings for `plugin_updater_detected`, `plugin_header_no_license`, and `outdated_tested_upto_header` are intentional for this product model. Do not remove the updater, remove license enforcement, or change the license model to clear those codes.
- When running Plugin Check for QA, use the project script `composer plugin-check`, which ignores the intentional WordPress.org-only codes while leaving other checks active.

---

## Architecture Constants (never guess these)

| Constant | Value |
|---|---|
| PHP Namespace root | `WPAnchorBay\CartBay\` |
| PSR-4 source directory | `app/` |
| Text domain | `cartbay` |
| Plugin slug | `cartbay` |
| Option prefix | `cartbay_` |
| Meta key prefix | `_cartbay_` |
| Transient prefix | `cartbay_` |
| Hook prefix | `cartbay_` |
| Nonce action prefix | `cartbay_` |
| Plugin entry file | `cartbay.php` |
| REST namespace | `cartbay/v1` |
| License server base URL constant | `CARTBAY_LICENSE_SERVER_URL` |
| License server API slug | `cartbay` |
| Documentation URL constant | `CARTBAY_DOCS_URL` |
| WC custom status prefix | `wc-cartbay-` |

---

## Repository Layout (key paths)

```
app/
  Admin/Pages/        ← PHP admin page controllers
  Admin/Wizard/       ← setup wizard step controllers
  Api/Routes/         ← REST route classes
  Core/
    Plugin.php        ← bootstrap and hook registration
    Container.php     ← service container
    Installer.php     ← activation/deactivation/upgrade
    Updater.php       ← PUC update checker
  Email/              ← WC_Email subclasses
  License/
    LicenseClient.php ← WP Anchor Bay API client
  Recovery/
    CaptureService.php
    AbandonmentScheduler.php
    EmailSequenceService.php
    CouponService.php
    RestoreService.php
    RecoveryMatcher.php
  Analytics/
    AnalyticsService.php
  Data/
    SessionRepository.php
  Utils/
    TokenHelper.php
    RateLimiter.php
    Logger.php
assets/               ← compiled JS/CSS (committed to repo)
src/dashboard/        ← React/TS source for dashboard widget only
templates/emails/     ← WC email HTML templates
languages/            ← .pot and .po/.mo files
tasks/                ← phase task files (your work queue)
cartbay.php           ← plugin entry point
uninstall.php         ← runs on plugin deletion
```

---

## PHP Rules

### Always
- Namespace every class: `namespace WPAnchorBay\CartBay\Recovery;`
- Add `@since 1.0.0` to every PHPDoc block (class, method, property).
- Use tabs for indentation (not spaces) per WordPress standards.
- Name classes `PascalCase`, methods and variables `snake_case`.
- Sanitize every input immediately on receipt:
  - Text: `sanitize_text_field()`
  - Integer: `absint()`
  - Float: `floatval()`
  - Multi-line: `sanitize_textarea_field()`
  - HTML: `wp_kses_post()`
  - Email: `sanitize_email()`
- Escape every output:
  - Plain text: `esc_html()`
  - Attributes: `esc_attr()`
  - URLs: `esc_url()`
- Wrap all user-facing strings: `__( 'Text', 'cartbay' )` or `_e( 'Text', 'cartbay' )`.
- Use early returns instead of deeply nested if/else.
- Check `current_user_can( 'manage_woocommerce' )` before every admin action.
- Verify nonces on every form submission and admin AJAX: `check_admin_referer( 'cartbay_action', 'cartbay_nonce' )`.

### Never
- Never query `wp_posts`, `wp_postmeta`, or `_order_itemmeta` directly. Use WC CRUD APIs only.
- Never use procedural global-style code. Every feature lives in a class.
- Never echo unsanitized data anywhere.
- Never store the full license key anywhere except the option (`cartbay_license_data`). Display only masked form.
- Never call `WC()->cart->add_discount()` if any cart item is `WC_Product_Subscription` or `WC_Product_Subscription_Variation`.
- Never block checkout capture, email sending, or cart restore because the license server is unreachable. Fail open on license checks.
- Never add sitewide frontend assets. Enqueue capture script on checkout page only; enqueue restore script on CartBay restore page only.
- Never access `$_SERVER['HTTP_X_FORWARDED_FOR']` for IP resolution unless explicit proxy trust is configured in settings. Use `$_SERVER['REMOTE_ADDR']` only.

---

## WooCommerce Rules

- Access order data exclusively through WC CRUD: `wc_get_order()`, `$order->get_meta()`, `$order->update_meta_data()`, `$order->save()`, `wc_get_orders()`.
- Declare HPOS compatibility in `Plugin.php` at `plugins_loaded` using `FeaturesUtil::declare_compatibility( 'custom_order_tables', CARTBAY_BASENAME, true )`.
- Register custom order statuses via `register_post_status()` AND the WC `woocommerce_register_shop_order_statuses` filter.
- Extend `WC_Email` for all recovery email classes. Use WC email wrappers and templates.
- Create coupons only via `WC_Coupon` object. Set `_cartbay_session_id` and `_cartbay_email` meta on each coupon.
- For the Block Checkout capture: register the consent field using `woocommerce_register_additional_checkout_field()` with `location: 'contact'`. Both classic and block checkout share the single `POST /wp-json/cartbay/v1/capture` endpoint.

---

## REST API Rules

- Register all routes in dedicated route classes under `app/Api/Routes/`.
- Public endpoints (`/capture`, `/restore/{token}`, `/unsubscribe/{token}`): `permission_callback` returns `true`. Protection comes from rate limiting + input validation.
- Admin endpoints: `permission_callback` must return `current_user_can( 'manage_woocommerce' )`.
- Rate limiting for public endpoints: transient key `cartbay_rl_{endpoint}_{md5($_SERVER['REMOTE_ADDR'])}`, TTL 600 seconds, limit 10 requests. Return HTTP 429 with `Retry-After: 600` header on breach.
- All REST request params: sanitize before use, validate types, reject unexpected shapes.

---

## License Client Rules

Follow exactly (from WP Anchor Bay docs):

- Domain value: always `wp_parse_url( home_url(), PHP_URL_HOST )` — same value for activate and check.
- Activate: `POST {CARTBAY_LICENSE_SERVER_URL}/activate` — JSON body with `license_key`, `slug`, `domain`.
- Check: `GET {CARTBAY_LICENSE_SERVER_URL}/check` — query params `license_key`, `slug`, `domain`.
- Update check handled by PUC library pointing to `{CARTBAY_LICENSE_SERVER_URL}/update-check/cartbay/{license_key}`.
- Cache valid status in transient `cartbay_license_valid` for 12 hours.
- If `wp_remote_get/post` returns `WP_Error` (network failure), return `true` from `is_valid()` — never lock site on server outage.
- Browser UI → local admin REST endpoint → PHP → License Server. Never browser → License Server directly.
- Dev domains (`localhost`, `*.local`, `*.dev`, `*.test`, `*.staging.*`) must still verify through the license server. Do not skip activation or validation solely because of the source domain.

---

## Security Rules

- Tokens: `wp_generate_password( 64, false )`. Store hash: `hash( 'sha256', $token )`. Never store plain token.
- Suppression lookup: `hash( 'sha256', strtolower( trim( $email ) ) )`.
- Do not log full license keys, raw tokens, or email addresses in plain error logs.
- Every public REST endpoint has rate limiting active before any database operation runs.

---

## Background Jobs (Action Scheduler)

- Always use `as_schedule_recurring_action()` and `as_schedule_single_action()`, never `wp_schedule_event()`.
- All job callbacks must be idempotent — safe to run twice without side effects.
- Use `as_has_scheduled_action()` before scheduling to prevent duplicates.
- Recovery email jobs: before sending, verify session is still in `wc-cartbay-abandoned` status AND email is not suppressed AND no recovered order exists for that email.

---

## i18n Rules

- PHP: wrap all user-facing strings with `__( 'Text', 'cartbay' )` or `_e( 'Text', 'cartbay' )`.
- React/JS: `import { __ } from '@wordpress/i18n';` then `__( 'Text', 'cartbay' )`.
- Do not concatenate translatable strings.
- After adding new strings, run: `bun run i18n:make-pot` to update `languages/cartbay.pot`.

---

## PHPDoc Requirements

Every class, method, and non-obvious property must have a complete PHPDoc block including:
- `@since 1.0.0`
- `@param` for every parameter (with type)
- `@return` with type and brief description
- One-line summary

Example:
```php
/**
 * Capture a cart session from the REST endpoint payload.
 *
 * @since 1.0.0
 *
 * @param string $email     Sanitized email address.
 * @param array  $cart_data Sanitized cart snapshot data.
 * @param string $source    Checkout type: 'classic' or 'block'.
 *
 * @return int|WP_Error Session order ID on success, WP_Error on failure.
 */
public function capture( string $email, array $cart_data, string $source ): int|WP_Error {
```

---

## File Naming

- PHP class files: match class name exactly. `CaptureService.php` → `class CaptureService`.
- Template files: lowercase with hyphens. `recovery-email-1.php`.
- JS entry points: `cartbay-{feature}.js` (compiled to `assets/js/`).

---

## Before Submitting Any Work

Run these locally and fix all violations before asking for human review:

```bash
composer phpcs        # zero violations required
composer phpstan      # zero errors at level 5 required
```

If adding or changing JS:
```bash
bun run build         # must complete without errors
```

If adding translatable strings:
```bash
bun run i18n:make-pot
```


## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- ALWAYS read graphify-out/GRAPH_REPORT.md before reading any source files, running grep/glob searches, or answering codebase questions. The graph is your primary map of the codebase.
- IF graphify-out/wiki/index.md EXISTS, navigate it instead of reading raw files
- For cross-module "how does X relate to Y" questions, prefer `graphify query "<question>"`, `graphify path "<A>" "<B>"`, or `graphify explain "<concept>"` over grep — these traverse the graph's EXTRACTED + INFERRED edges instead of scanning files
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).

---

## Architecture Documentation

The implemented architecture is documented in `.meta/architecture/`. Start with [`.meta/architecture/index.md`](.meta/architecture/index.md) for the table of contents.

Rules:
- Before implementing a feature that changes the system shape (new subsystem, new service, new lifecycle stage, new data entity, new REST route, new admin section, changed storage strategy, changed hook topology), read the relevant architecture note first.
- After completing any change that alters architecture as documented there, update or create the corresponding `.meta/architecture/` file. Keep it current with the actual code on disk — the architecture notes are a living record, not aspirational.
- If a change introduces a new subsystem that isn't covered by an existing file, create a new `.meta/architecture/<topic>.md` and add a reference to `index.md`.
- Note notable implementation mismatches (e.g., namespace drift, PHP version header vs composer.json mismatch) in the relevant file or in `system-overview.md` under "Notable Implementation Facts".

---

## Reference Docs (read when implementing related features)

- WC HPOS extension recipe: https://developer.woocommerce.com/docs/hpos-extension-recipe-book/
- WC Additional Checkout Fields (Block): https://developer.woocommerce.com/docs/block-development/cart-and-checkout-blocks/additional-checkout-fields/
- WC Subscriptions coupons: https://woocommerce.com/document/subscriptions/subscriptions-coupons/
- WP Anchor Bay integration guide: https://docs.wpanchorbay.com/license-server-for-woocommerce/integrations/wordpress-plugin/
- WP Anchor Bay REST reference: https://docs.wpanchorbay.com/license-server-for-woocommerce/agents/rest-api/
- WP Anchor Bay agent integration: https://docs.wpanchorbay.com/license-server-for-woocommerce/agents/integrate-plugins/
- WordPress Nonces: https://developer.wordpress.org/apis/security/nonces/
- WordPress Transients: https://developer.wordpress.org/apis/transients/
- Action Scheduler: https://actionscheduler.org/
- Plugin Update Checker: https://github.com/YahnisElsts/plugin-update-checker

- WP-CLI: https://developer.wordpress.org/cli/commands/
