# Admin and Wizard

## Admin UI Placement
CartBay uses a WooCommerce settings tab rather than a broad standalone admin application. By default it also registers a WooCommerce admin submenu shortcut that links to the same settings tab.

- Main class: `app/Admin/Settings/SettingsPage.php`.
- Tab slug: `cartbay`.
- Tab label: `Cart`.
- URL shape: `admin.php?page=wc-settings&tab=cartbay&section={section}`.
- WooCommerce menu shortcut: `WooCommerce > CartBay`, controlled by `cartbay_settings['wc_menu_enabled']` and enabled by default.
- Required capability: `manage_woocommerce`.

## Settings Page Composition
`SettingsPage` composes section classes through the container and a shared field renderer.

Implemented sections, in display order:

- `overview` -> `OverviewSection`.
- `capture` -> `CaptureSection`.
- `sequence` -> `RecoverySequenceSection`.
- `notifications` -> `NotificationsSection`.
- `templates` -> `TemplatesSection`.
- `offers` -> `OffersSection`.
- `coupon-history` -> `CouponHistorySection`.
- `settings` -> `SettingsSection`.
- `logs` -> `LogsSection` (hidden from main sub-navigation; accessible via the Settings section Logs link).

Shared helpers:

- `SettingsSectionInterface` defines section contracts.
- `AbstractSettingsSection` provides section base behavior.
- `FieldRenderer` renders field types.
- `SettingsUrl` builds settings URLs.
- `AdminEnvironment` centralizes admin-environment checks.
- `MailEnvironmentDetector` detects mail delivery and email logger plugins for admin warnings.

## Mail Environment Detection
`app/Admin/Settings/MailEnvironmentDetector.php` provides passive mail-environment detection for the settings page and setup wizard.

Detection states:

- Mail delivery detected: no settings/admin warning; wizard step 4 shows a success notice.
- Email logger detected without delivery: warning explains that logging is active but SMTP delivery was not detected.
- Neither delivery nor logger detected: warning recommends installing an SMTP plugin.

Delivery detection uses known active mail delivery plugin basenames, active plugin metadata keywords, and registered mail hook callbacks for `phpmailer_init`, `pre_wp_mail`, and delivery-like `wp_mail` callbacks. The detector does not execute mail hooks or send test mail during detection.

Logger detection uses known email logger plugin basenames and conservative active-plugin metadata keywords because email loggers do not expose a universal WordPress behavior signal. Detection lists and final status are extensible through `cartbay_mail_delivery_plugins`, `cartbay_email_logger_plugins`, and `cartbay_mail_environment_status` filters.

## Capture Section
`CaptureSection` stores capture configuration in `cartbay_settings`.

Implemented fields:

- `capture_enabled`
- `consent_text`
- `consent_default_state`
- `abandonment_timeout`

## Recovery Sequence Section
`RecoverySequenceSection` stores recovery campaign configuration in `cartbay_campaign_settings`.

Implemented UI behavior:

- Master enablement for the recovery sequence.
- Three step cards for delay configuration.
- Coupon enablement per step.
- Custom field type `cartbay_sequence_designer`.

## Offers Section
`OffersSection` stores coupon offer configuration in `cartbay_settings`.

Implemented fields:

- `coupon_type`
- `coupon_amount`
- `coupon_expiry_days`

The Discount Coupons heading links to the read-only Coupon History section.

## Coupon History Section
`CouponHistorySection` lists CartBay-generated coupons and their recovery context.

Implemented UI behavior:

- Summary cards for generated, active, used, and expired coupons.
- Table rows show masked coupon code, CartBay session, discount, usage, expiry, and status.
- A More button expands row-level details including full code, generated email, email restrictions, restore click timestamp, recovered order, and session recovery events.

## Settings Section
`SettingsSection` covers operational configuration:

- License status.
- Masked license key display.
- Activate new license key.
- Check license.
- Remove local license.
- Data retention days.
- WooCommerce admin menu shortcut toggle.
- Test mode.
- Logs link.
- AI Agent Access controls for CartBay agent REST, WordPress Abilities, MCP-public metadata, write/contact/sensitive/destructive action classes, and connection guidance.

## Overview Section
`OverviewSection` renders recovery reporting and session visibility:

- Metrics for tracked carts, abandoned carts, recoveries, abandoned value, recovered revenue, and recovery rate.
- Period buttons for 7, 30, and 90 days.
- Help panel with documentation link (`CARTBAY_DOCS_URL`), support email link (`support@wpanchorbay.com`), and an always-available `admin.php?page=cartbay-wizard` setup wizard link, even after `cartbay_wizard_complete` is true.
- Sessions table with filters and sorting by status, session ID, cart total, created date, and email count.

## Notifications Section
`NotificationsSection` renders recovery email activity:

- Primary cards for pending queue, sent emails, failed emails, email acceptance rate, and best email step.
- A More card opens a WooCommerce Backbone modal with a concise status-level stats list and tooltips for queued, attempted, sent, delivered, failed, retry-queued, and canceled notifications.
- Filters by notification status.
- Search support.
- Compact table rows show status, recipient/session, email, scheduled date, and a Details action.
- The row Details modal contains trigger source, lifecycle timestamps, attempts/retries, related activity, and error log details.

## Templates Section
`TemplatesSection` lists the three recovery email steps and links to WooCommerce email settings for:

- `cartbay_email_recovery_1`
- `cartbay_email_recovery_2`
- `cartbay_email_recovery_3`

It also displays placeholder reference material and exposes the test-flow trigger.

## Setup Wizard
`app/Admin/Wizard/WizardController.php` implements a first-run wizard under a hidden submenu page.

- Page slug: `cartbay-wizard`.
- Required capability: `manage_woocommerce`.
- Redirect trigger: `cartbay_wizard_complete` is false when an eligible administrator opens the CartBay admin entry (`page=cartbay`) or the WooCommerce CartBay settings tab (`page=wc-settings&tab=cartbay`).

Wizard steps:

1. Welcome.
2. License.
3. Consent & Timing.
4. Email Delivery.
5. Launch.

Wizard writes:

- `cartbay_settings['consent_text']`.
- `cartbay_settings['abandonment_timeout']`.
- `cartbay_campaign_settings['steps'][*]['delay_minutes']`.
- `cartbay_campaign_settings['enabled']`.
- `cartbay_wizard_complete`.

## Admin Assets
The settings admin screen enqueues WordPress/WooCommerce dependencies and prints inline UI code:

- `jquery`
- `wc-backbone-modal`
- `wp-util`

Inline JavaScript handles license removal confirmation, session filtering, test-flow triggering, and recovery sequence delay summaries. Inline CSS is printed for the settings UI.

The same WooCommerce Backbone modal pattern is used for the license removal confirmation, log details, and Notification section More stats dialog.
