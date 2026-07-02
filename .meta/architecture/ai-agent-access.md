# AI Agent Access

## Purpose
CartBay now includes an agent access layer that lets store owners make abandoned-cart recovery manageable by authorized AI agents. The feature is closed by default and becomes active only when `cartbay_settings['agent_access_enabled']` is enabled.

## Runtime Shape
Agent code lives under `app/Agent/` using the `WPAnchorBay\CartBay\Agent` namespace.

Implemented services:

- `AccessPolicy` reads agent settings and maps scopes to CartBay capabilities.
- `TokenRepository` creates, hashes, validates, lists, and revokes CartBay-scoped Bearer tokens.
- `Authenticator` resolves REST callers from either WordPress users or CartBay Bearer tokens.
- `PermissionService` enforces global access, surface enablement, WordPress capabilities, token scopes, and action-class gates.
- `AuditLogger` stores a redacted rolling audit log in `cartbay_agent_audit_log`.
- `AgentService` is the shared service used by REST routes and WordPress Abilities.
- `Abilities` registers CartBay WordPress Abilities and optional MCP-public metadata.

## Settings
Agent settings are stored in `cartbay_settings`:

- `agent_access_enabled`
- `agent_rest_enabled`
- `agent_abilities_enabled`
- `agent_mcp_public_enabled`
- `agent_write_enabled`
- `agent_contact_enabled`
- `agent_sensitive_enabled`
- `agent_destructive_enabled`

The settings UI appears in the existing WooCommerce CartBay Settings section under `AI Agent Access`.

## Capabilities and Scopes
CartBay registers these capabilities for administrators, with read/write/contact also granted to shop managers:

- `cartbay_agent_read`
- `cartbay_agent_write`
- `cartbay_agent_contact`
- `cartbay_agent_sensitive`
- `cartbay_agent_destructive`
- `cartbay_agent_manage_tokens`
- `cartbay_agent_manage_access`

CartBay Bearer tokens use matching scopes:

- `read`
- `write`
- `contact`
- `sensitive`
- `destructive`
- `manage_tokens`
- `manage_access`

Bearer tokens authenticate only CartBay agent REST endpoints. WordPress Abilities and MCP Adapter access use normal WordPress authentication, normally Application Passwords for remote clients.

## REST Surface
`app/Api/Routes/AgentRoute.php` registers routes under `/wp-json/cartbay/v1/agent`:

- `GET /manifest`
- `GET /sessions`
- `GET /sessions/{id}`
- `POST /sessions/{id}/actions`
- `GET /analytics`
- `GET|PATCH /settings`
- `GET|PATCH /campaign`
- `GET|POST /tokens`
- `DELETE /tokens/{public_id}`
- `GET /audit-log`

All routes use the shared `AgentService` authorization and serialization paths. Session output masks PII by default. Sensitive fields require sensitive capability/scope and `agent_sensitive_enabled`.

## WordPress Abilities and MCP
`Abilities` registers the `cartbay-agent` category and these abilities when the Abilities API exists:

- `cartbay/get-agent-manifest`
- `cartbay/list-sessions`
- `cartbay/get-session`
- `cartbay/get-analytics`
- `cartbay/get-settings`
- `cartbay/update-settings`
- `cartbay/get-campaign`
- `cartbay/update-campaign`
- `cartbay/run-session-action`

Abilities set `meta.show_in_rest` based on `agent_abilities_enabled`. They set `meta.mcp.public` only when `agent_mcp_public_enabled` is enabled. CartBay does not bundle the MCP Adapter; site owners can install the official adapter to expose CartBay abilities as MCP tools.

## Session Actions
The first implementation supports these session actions:

- `mark_abandoned_now`
- `cancel_pending_emails`
- `send_email_step_now`
- `expire_session`
- `delete_session`

All actions validate that the target order is a CartBay-created WooCommerce order. Contact and destructive actions require their corresponding setting gates and capabilities/scopes.

## Security Notes
Agent access is off by default. CartBay masks PII by default, stores only hashed token secrets, never accepts Bearer tokens outside CartBay agent REST routes, and writes a redacted audit record for reads and writes.

WooCommerce REST API remains a companion surface for standard store data. CartBay recovery control stays in CartBay REST and Abilities so CartBay state validation, HPOS-safe CRUD usage, PII policy, and audit logging remain authoritative.
