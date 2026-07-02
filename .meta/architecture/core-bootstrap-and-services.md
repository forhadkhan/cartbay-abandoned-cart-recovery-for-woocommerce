# Core Bootstrap and Services

## Entry Point
`cartbay.php` is the plugin entrypoint. It defines runtime constants, loads Composer autoloading from `vendor/autoload.php` when available, waits for `plugins_loaded`, checks that WooCommerce is loaded, and initializes `WPAnchorBay\CartBay\Core\Plugin::instance()->init()`.

Activation and deactivation are routed to `WPAnchorBay\CartBay\Core\Installer::activate()` and `Installer::deactivate()` when the class is available.

## Constants
`app/Core/Constants.php` defines the shared constants used across the free host plugin:

- `CARTBAY_VERSION`
- `CARTBAY_SLUG`
- `CARTBAY_DIR`
- `CARTBAY_URL`
- `CARTBAY_BASENAME`
- `CARTBAY_PLUGIN_URL`
- `CARTBAY_AUTHOR_URL`
- `CARTBAY_GET_PRO_URL`
- `CARTBAY_DOCS_URL`

## Plugin Singleton
`app/Core/Plugin.php` is the central runtime coordinator. `Plugin::init()` is idempotent and performs this sequence:

1. Register HPOS compatibility declaration on `before_woocommerce_init`.
2. Register service singletons in the container.
3. Register WordPress, WooCommerce, REST, frontend, and background hooks.
4. Fire `cartbay_loaded`.

CartBay Pro is a separate add-on loaded after the host on `plugins_loaded` priority `20`. It checks for `CARTBAY_VERSION` and `WPAnchorBay\CartBay\Core\Plugin`, requires host version `>= 1.0.0`, and otherwise shows admin-only dependency notices without initializing Pro modules.

## Settings Helpers
`app/Core/Settings.php` provides a static utility for normalizing plugin option values. `Settings::is_capture_enabled()` centralises the `capture_enabled` flag check so stored checkbox values (`yes`, `no`, booleans, numeric) are interpreted consistently across enqueues, REST routes, the Block Checkout field gate, and the capture service layer. Defaults to `true` when the option is missing.

## Container
`app/Core/Container.php` is a minimal service container with `bind()`, `singleton()`, and `make()`.

The implemented host singleton graph includes:

- `CheckoutFields`
- `SessionRepository`
- `AbandonmentScheduler`
- `CaptureService`
- `CouponService`
- `NotificationService`
- `EmailSequenceService`
- `RestoreService`
- `RecoveryMatcher`
- `AnalyticsService`
- `SettingsUrl`
- `AdminEnvironment`
- `FieldRenderer`
- `SettingsPage`

## Hook Topology
Core and setup hooks:

- `init` -> custom order statuses.
- `woocommerce_register_shop_order_statuses` -> WooCommerce status registration.
- `wc_order_statuses` -> WooCommerce status list registration.
- `init` -> private CPT registration.
- `init` priority `20` -> recurring job scheduling.
- `action_scheduler_init` -> recurring job scheduling (self-healing when Action Scheduler becomes available later).
- `admin_menu` -> admin menu and hidden wizard page.
- `plugin_action_links_{CARTBAY_BASENAME}` -> plugin row action links (Overview, Settings, Docs).
- `admin_init` -> first-run wizard redirect.
- `rest_api_init` -> REST route registration.

Background and recovery hooks:

- `cartbay_detect_abandonment` -> `AbandonmentScheduler::run()`.
- `cartbay_detect_session_abandonment` -> `AbandonmentScheduler::run_for_session()`.
- `cartbay_send_recovery_email` -> `EmailSequenceService::send_step()`.
- `cartbay_refresh_analytics` -> `AnalyticsService::refresh()`.
- `cartbay_prune_sessions` -> `SessionRepository::prune_expired()`.
- `init` -> restore and unsubscribe query-arg handlers.
- `woocommerce_payment_complete` and `woocommerce_order_status_changed` -> `RecoveryMatcher::handle_order()` and `handle_status_change()`.
- `woocommerce_checkout_create_order` -> `RecoveryMatcher::attach_checkout_attribution()` — stores checkout attribution identity on the new order for session matching.
- `woocommerce_coupon_is_valid` -> `CouponService::validate_cartbay_coupon_use()` — validates CartBay-generated coupons against the restored session identity and checkout email.

Frontend and email hooks:

- `wp_enqueue_scripts` -> classic checkout capture asset.
- `woocommerce_blocks_enqueue_checkout_block_scripts_after` -> Block Checkout capture asset.
- `woocommerce_email_classes` -> recovery email class registration.
- WooCommerce email preview filters -> CartBay placeholder/content preview support.
- `wp_mail_failed` -> notification failure handling.
- `wp_mail_succeeded` -> marks notifications as sent when WordPress accepts the mail for delivery.
- `cartbay_mark_notification_delivered` -> notification delivery marking (reserved for provider integrations).
- `woocommerce_checkout_get_value` -> `prefill_restored_email` — pre-fills billing email on checkout for restored sessions.
- `wp` -> `display_frontend_notices` — shows CartBay frontend notices (e.g. after restore).

## Custom Statuses
The plugin registers five WooCommerce order session statuses:

- `wc-cartbay-captured`
- `wc-cartbay-abandoned`
- `wc-cartbay-recovered`
- `wc-cartbay-expired`
- `wc-cartbay-suppressed`

## Custom Post Types
The implemented private CPTs are:

- `cartbay_template` for email template records.
- `cartbay_suppressed` for suppression entries keyed by hashed email.

## Installer
`app/Core/Installer.php` handles activation, deactivation, default options, template seeding, and recurring Action Scheduler jobs. Recurring job scheduling is attempted on activation, normal `init`, and `action_scheduler_init` so CartBay self-heals when Action Scheduler becomes available later than plugin activation.

Activation does the following:

- Creates default options.
- Seeds default email templates.
- Registers statuses and CPTs for install-time availability.
- Schedules recurring jobs.
- Flushes rewrite rules.

Deactivation unschedules recurring CartBay actions and flushes rewrite rules.

Recurring jobs scheduled by the free host under Action Scheduler group `cartbay`:

- `cartbay_detect_abandonment` every 5 minutes.
- `cartbay_refresh_analytics` hourly.
- `cartbay_prune_sessions` daily.

## Pro Licensing and Updater Ownership
The free WordPress.org-bound host no longer ships a license client or private updater. CartBay Pro owns:

- `cartbay_license_data`
- `cartbay_license_valid`
- `cartbay_check_license`
- private update checks through `yahnis-elsts/plugin-update-checker`

Pro injects its license UI through `cartbay_settings_section_pre_fields`, saves license keys through `cartbay_settings_section_saved`, registers the `/cartbay/v1/license/*` REST routes only while Pro is active, and inserts the setup wizard License step through `cartbay_wizard_steps`.

## Uninstall
`uninstall.php` is guarded by `cartbay_settings['remove_data_on_uninstall']`. The setting defaults to false, so deleting the plugin preserves CartBay data for a later reinstall unless the merchant explicitly enables deletion from WooCommerce > Settings > Cart > Settings.

When enabled, uninstall removes local options:

- `cartbay_campaign_settings`
- `cartbay_settings`
- `cartbay_wizard_complete`
- `cartbay_sequence_defaults_version`
- `woocommerce_cartbay_recovery_1_settings`
- `woocommerce_cartbay_recovery_2_settings`
- `woocommerce_cartbay_recovery_3_settings`

It also deletes known local transients:

- `cartbay_analytics_cache`

With the delete setting enabled, uninstall also unschedules CartBay Action Scheduler hooks, force-deletes CartBay order-backed recovery sessions, CartBay-generated recovery coupons, `cartbay_template` posts, `cartbay_suppressed` posts, and CartBay-owned file log artifacts under uploads.
