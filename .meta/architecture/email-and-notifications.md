# Email and Notifications

## WooCommerce Email Classes
Recovery emails are implemented as WooCommerce email classes under `app/Email/`:

- `AbstractCartBayRecoveryEmail`
- `CartBay_Email_Recovery_1`
- `CartBay_Email_Recovery_2`
- `CartBay_Email_Recovery_3`

`Plugin::register_email_classes()` adds the three concrete recovery emails to WooCommerce through the `woocommerce_email_classes` filter.

## Email Base Class
`AbstractCartBayRecoveryEmail` extends `WC_Email` and centralizes shared behavior:

- Template lookup for `templates/emails/recovery-email-{step}.php`.
- Placeholder replacement.
- Restore URL and unsubscribe URL generation.
- Coupon code and coupon expiry placeholders.
- WooCommerce email header/footer wrapping.
- Notification ID header support through `X-CartBay-Notification`.
- WooCommerce email preview placeholder support.

## Concrete Recovery Emails
The concrete classes map the three sequence steps to WooCommerce email IDs:

- `cartbay_email_recovery_1`
- `cartbay_email_recovery_2`
- `cartbay_email_recovery_3`

These IDs are referenced from the admin templates section and WooCommerce email settings links.

## Templates
Template files live in `templates/emails/`:

- `recovery-email-1.php`
- `recovery-email-2.php`
- `recovery-email-3.php`

Templates use WooCommerce email wrappers and include:

- Hidden preheader when configured.
- Main body content.
- Restore-cart CTA.
- Plain restore-link fallback for email clients that do not handle the CTA button.
- Optional coupon line.
- Additional content.
- Optional unsubscribe link.

## Placeholders
Implemented placeholders include:

- `{site_title}`
- `{site_name}`
- `{store_name}`
- `{customer_email}`
- `{restore_url}`
- `{coupon_code}`
- `{coupon_expiry}`
- `{unsubscribe_url}`

## Notification Tracking
`app/Recovery/NotificationService.php` stores notification tracking records in session meta `_cartbay_notifications`.

Notification records support queueing, send attempts, WordPress/WooCommerce send success, failure, retry, cancellation, restore click linkage, and recovered order linkage. Transient context records use the key shape `cartbay_notification_ctx_{notification_id}` so mail failure/success hooks can resolve the session and notification across the retention window.

## Mail Hooks
Implemented mail-related hook wiring includes:

- `wp_mail_failed` -> marks notification failures.
- `wp_mail_succeeded` -> marks notifications as sent when WordPress processed the send without error.
- `cartbay_mark_notification_delivered` -> marks delivered notifications.

Provider-confirmed delivery is future scope unless an external integration explicitly fires CartBay's delivery hook.

## Test Email Support
`app/Api/Routes/TestEmailRoute.php` exposes an admin-only route for sending a basic test email or a recovery email preview by step. The templates admin section links to this test flow.
