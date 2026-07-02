# Analytics and Operations

## Analytics Service
`app/Analytics/AnalyticsService.php` aggregates abandoned-cart reporting from WooCommerce order-backed sessions.

Implemented periods:

- 7 days.
- 30 days.
- 90 days.

Implemented metrics:

- `tracked`.
- `abandoned`.
- `recovered`.
- `restored`.
- `abandoned_value`.
- `revenue`.
- `recovery_rate`.
- `restore_clicks`.
- `click_to_recovery_rate`.
- `emails_queued`.
- `emails_sent`.
- `emails_failed`.
- `email_send_rate`.
- `revenue_by_step`.
- `recoveries_by_step`.
- `best_step`.
- `average_time_to_recovery`.
- `returning_shopper_count`.
- `repeat_abandoned_shopper_count`.
- `failed_restore_count`.
- `completed_before_abandonment`.

Analytics are exposed through the admin overview section, the Notifications section, and `GET /cartbay/v1/analytics` for users with `manage_woocommerce`. The overview cards show recovery, restore-link, and revenue performance. Email funnel and sequence performance metrics are shown in Notifications.

`restore_clicks` and `failed_restore_count` are counted from session event timestamps inside the selected reporting period. `click_to_recovery_rate` is calculated as link-restored purchases divided by restore-link clicks, not all recovered carts divided by clicks.

## Analytics Cache
Analytics results are cached in the `cartbay_analytics_cache` transient. The recurring `cartbay_refresh_analytics` action refreshes analytics hourly.

Runtime code invalidates this transient when reporting inputs change: capture create/update/delete, abandonment, restore link clicks, notification state changes, and recovery matching. This keeps overview cards current after checkout capture and recovery actions instead of waiting for the hourly refresh.

## Scheduled Operations
Action Scheduler is the implemented background job system.

Recurring jobs:

- `cartbay_detect_abandonment` every 5 minutes.
- `cartbay_refresh_analytics` hourly.
- `cartbay_prune_sessions` daily.

CartBay Pro additionally schedules `cartbay_check_license` daily while the Pro add-on is active.

Single jobs:

- `cartbay_detect_session_abandonment` with args `[session_id]`.
- `cartbay_send_recovery_email` with args `[session_id, step_index]`.

All recurring and single CartBay background jobs are scheduled in the `cartbay` group.

## Logging
`app/Utils/Logger.php` wraps the WooCommerce logger with source `cartbay`.

Implemented levels:

- `info`.
- `warning`.
- `error`.

The architecture avoids logging raw tokens, full license keys, and email addresses in the documented flows.

CartBay also writes its own sanitized JSON-line troubleshooting log in `wp-content/uploads/cartbay/cartbay.log` when `cartbay_settings['log_enabled']` is enabled. It is enabled by default. Default retention is 7 days and default file size cap is 5 MB. Older entries are removed first when retention or size limits are exceeded.

Each log entry contains `timestamp`, `level`, `message`, `context`, and `system` fields. The `system` attribute identifies the CartBay subsystem that produced the entry, enabling level filtering by subsystem group. Subsystem values include: `core`, `capture`, `restore`, `recovery`, `emails`, `abandonment`, `license`, `test`, `analytics`, `checkout`, `unsubscribe`, `logs`.

The Settings section links to a hidden Logs section that is not shown in the main CartBay settings sub-navigation. The Logs section exposes CartBay file log controls and shows sanitized entries in a table with pagination (25 per page), time-based sorting (asc/desc), level filter (info/warning/error), details modal, and copy controls for support workflows. WooCommerce native logging remains available separately through WooCommerce Status logs.

Logged operations include public/admin REST API calls, license server requests, capture, abandonment, restore, recovery matching, notification lifecycle failures, checkout field registration, and test flows.

## Rate Limiting
Public REST rate limiting uses transient keys in the shape `cartbay_rl_{endpoint}_{md5(REMOTE_ADDR)}`. The default limit is 10 requests per 600 seconds per endpoint and IP address.

## Retention and Cleanup
`cartbay_prune_sessions` delegates to `SessionRepository::prune_expired()`. Expired sessions are located by configured retention days and CartBay lifecycle statuses.

Uninstall cleanup is intentionally local-option/transient focused and does not remove WooCommerce order sessions, generated coupons, or CPT records.

## Build Tooling
Composer configuration:

- Package: `wpanchorbay/cartbay`.
- Runtime PHP requirement: `>=8.3`.
- Autoload: `WPAnchorBay\CartBay\` -> `app/`.

The free host has no private updater runtime dependency. CartBay Pro carries the private update checker dependency for licensed Pro updates.

Composer scripts:

- `composer phpcs`.
- `composer phpstan`.
- `composer test`.

JavaScript configuration:

- Package name: `cartbay`.
- Build system: `@wordpress/scripts`.
- Build entries are configured in `webpack.config.js`.
- Entries: `cartbay-capture` and `cartbay-block`.
- Output directory: `assets/js`.

NPM/Bun scripts:

- `start`.
- `build`.
- `dev`.
- `release`.
- `i18n:make-pot`.
- `i18n:make-json`.

## Static Analysis and Standards
Implemented quality gates include:

- PHPCS with WordPress rules and PHPCompatibilityWP.
- PHPStan level 5 with WordPress/WooCommerce bootstrap stubs.
- PHPUnit configuration through Composer.

## Notable Operational Mismatches
- `cartbay.php` declares a plugin header requiring PHP `8.2`, while `composer.json` requires PHP `>=8.3`.
- The implemented namespace is `WPAnchorBay\CartBay\`, while some project guidance still references `CartBay\`.
- Some Action Scheduler cancellation paths use direct updates to Action Scheduler tables to cancel all step jobs for a session.
