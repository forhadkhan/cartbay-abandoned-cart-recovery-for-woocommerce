# REST API and Licensing

## REST Namespace
All implemented REST route classes register under `cartbay/v1` from `Plugin::register_rest_routes()`.

## Public Capture Route
`app/Api/Routes/CaptureRoute.php` registers:

- `POST /cartbay/v1/capture`.
- Permission: public.
- Protection: `RateLimiter::check( 'capture' )` before capture work.
- Args: `email`, `consent`, `cart`, `source`, and optional `session_id`.

When `consent` is true, the route delegates to `CaptureService::capture()`. When consent is withdrawn, it delegates to `CaptureService::delete_after_consent_withdrawal()`.

## Admin Analytics Route
`app/Api/Routes/AnalyticsRoute.php` registers:

- `GET /cartbay/v1/analytics`.
- Permission: `current_user_can( 'manage_woocommerce' )`.
- Arg: `days`, constrained to 7, 30, or 90.

The route delegates to `AnalyticsService`.

## Pro Admin License Routes
The free WordPress.org-bound host does not register license routes. When CartBay Pro is active, `cartbay-pro/app/Api/Routes/LicenseRoute.php` registers admin-only license operations under the host REST namespace:

- `POST /cartbay/v1/license/activate`.
- `GET /cartbay/v1/license/status`.
- `POST /cartbay/v1/license/deactivate`.

All require `manage_woocommerce` and delegate to the Pro-owned `LicenseClient`.

## Admin Test Routes
Implemented test routes are admin-only:

- `POST /cartbay/v1/test/trigger` in `TestRoute`.
- `POST /cartbay/v1/test/email` in `TestEmailRoute`.

`TestRoute` also requires `cartbay_settings['test_mode']` and creates a dummy abandoned session with a near-term first email action.

## Agent Routes
`app/Api/Routes/AgentRoute.php` registers protected agent endpoints under `/cartbay/v1/agent`. These endpoints are disabled unless `cartbay_settings['agent_access_enabled']` and `cartbay_settings['agent_rest_enabled']` are enabled. Authentication is either a normal authenticated WordPress user, such as an Application Password user, or a CartBay Bearer token accepted only by these agent routes.

Agent endpoints delegate to `AgentService` and enforce CartBay capabilities, token scopes, action-class settings, PII masking, and audit logging. Details are documented in [AI Agent Access](./ai-agent-access.md).

## Query-Arg Recovery Endpoints
Restore and unsubscribe are not REST routes. They are handled during `init` by query parameters:

- `?cartbay_restore={token}`.
- `?cartbay_unsubscribe={token}`.

## Pro License Client
`cartbay-pro/app/License/LicenseClient.php` communicates with the WP Anchor Bay license server. This code lives in Pro so the free plugin does not ship license-server-only functionality.

Local storage:

- Option: `cartbay_license_data`.
- Transient: `cartbay_license_valid`.

Domain source:

- `wp_parse_url( home_url(), PHP_URL_HOST )`.

Activation:

- Endpoint: `POST {CARTBAY_PRO_LICENSE_SERVER_URL}/activate`.
- JSON body: `license_key`, `slug`, and `domain`.

Validation:

- Endpoint: `GET {CARTBAY_PRO_LICENSE_SERVER_URL}/check`.
- Query args: `license_key`, `slug`, and `domain`.
- Valid status is cached for 12 hours.
- Network failures in `is_valid()` update local status to `server_error` and fail open by returning true.

Masked display:

- `LicenseClient::get_masked_key()` masks all but the final four characters.

Local removal:

- `LicenseClient::remove_local()` deletes the option and transient.

## Development Domains
`LicenseClient::is_dev_domain()` recognizes `localhost`, `.local`, `.dev`, `.test`, and staging domains for informational use only. Development and staging domains do not bypass WP Anchor Bay verification: activation always calls the remote `/activate` endpoint, and uncached validation always calls the remote `/check` endpoint with the same domain source used in production.

Activation fails closed when the license server cannot be reached because a key cannot be activated without server verification. Runtime validation still fails open on network errors so recovery flows are not interrupted by a temporary license-server outage.

## Update Checker
The free host does not ship Plugin Update Checker. `cartbay-pro/app/Core/Updater.php` uses Plugin Update Checker for private licensed Pro update delivery. It is inactive when no local license key exists. With a key, it points to:

`{CARTBAY_PRO_LICENSE_SERVER_URL}/update-check/{CARTBAY_PRO_LICENSE_SLUG}/{license_key}`

It also sends the current host as the `host` query argument.
