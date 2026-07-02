# Architecture

This directory records the architecture that is already implemented in the current CartBay codebase.

## Implemented Product Architecture
- [System Overview](./system-overview.md) - runtime shape, code layout, namespaces, and end-to-end lifecycle.
- [Core Bootstrap and Services](./core-bootstrap-and-services.md) - entrypoint, constants, service container, hook topology, custom statuses, installer, Pro add-on ownership boundaries, and uninstall behavior.
- [Data Model](./data-model.md) - WooCommerce order-backed sessions, custom statuses, meta keys, suppression records, coupons, options, and transients.
- [Capture and Checkout](./capture-and-checkout.md) - classic checkout capture, Block Checkout consent field, capture REST route, consent withdrawal, and rate limiting.
- [Recovery Engine](./recovery-engine.md) - abandonment detection, recovery sequence scheduling, email jobs, restore links, unsubscribe, matching, and coupons.
- [Email and Notifications](./email-and-notifications.md) - WooCommerce recovery email classes, templates, placeholders, notification tracking, and mail failure handling.
- [Admin and Wizard](./admin-and-wizard.md) - WooCommerce settings tab, section architecture, setup wizard, inline admin assets, and test tools.
- [REST API and Licensing](./rest-api-and-licensing.md) - REST route inventory, permissions, license server client, update checker, and development-domain behavior.
- [AI Agent Access](./ai-agent-access.md) - CartBay agent REST endpoints, WordPress Abilities, token scopes, MCP exposure policy, and audit logging.
- [Analytics and Operations](./analytics-and-operations.md) - analytics aggregation, scheduled jobs, logging, retention, build tooling, and verification gates.

## Git Architecture: Orphan Overlay
This project uses an **Orphan Overlay** strategy to manage dot-prefixed directories independently from the main application codebase. Here is the [detailed explanation](./git-orphan-overlay.md)

## WooCommerce Compatibility
CartBay follows WooCommerce HPOS and Block Checkout integration guidance. Details: [woocommerce-compatibility.md](./woocommerce-compatibility.md)
