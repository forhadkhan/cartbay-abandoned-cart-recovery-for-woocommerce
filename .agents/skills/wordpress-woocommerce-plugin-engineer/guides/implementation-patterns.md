# Implementation Patterns Guide

Use this guide when generating or refactoring actual WordPress plugin code. It enriches the core skill with concrete patterns while staying conservative about official API stability.

## Architecture Choices

Choose the smallest architecture that keeps dependencies explicit:

| Situation | Recommended pattern |
|---|---|
| Tiny utility under a few hundred lines | Prefixed functions or one service class |
| Normal production plugin | Namespaced service classes with a thin bootstrap |
| Large plugin with many modules | Lightweight container or service provider registry |
| Shared helper logic | Pure functions or stateless utility classes |
| Extensible public API | Interfaces plus documented hooks/filters |

Avoid singletons as the main design. If a legacy singleton exists, do not spread it further; wrap new behavior in testable services.

## Hook Timing Reference

Use the earliest hook that has the dependencies you need:

| Hook | Use |
|---|---|
| `before_woocommerce_init` | Woo feature compatibility declarations |
| `plugins_loaded` | Inter-plugin compatibility checks, load text domain, bootstrap services |
| `woocommerce_loaded` | WooCommerce public APIs are available |
| `woocommerce_blocks_loaded` | Woo Blocks package/integration registration |
| `init` | Post types, taxonomies, rewrite tags, shortcodes |
| `rest_api_init` | REST route registration |
| `admin_menu` | Admin menu pages |
| `admin_enqueue_scripts` | Admin assets, with `get_current_screen()` checks |
| `enqueue_block_editor_assets` | Editor-only assets when metadata is insufficient |
| `wp_enqueue_scripts` | Frontend assets, conditionally loaded |
| `template_redirect` | Frontend redirects after query is known |

Prefer named methods/functions for hooks that may need removal. Anonymous callbacks are acceptable only for local bootstrap glue that never needs unhooking.

## Security Gates

Every data path must pass the relevant gates:

1. Capability check: minimum required capability.
2. Nonce or REST permission check.
3. `wp_unslash()` request data.
4. Sanitize and validate by type/domain.
5. Prepared SQL if touching the database.
6. Escape late by output context.

For REST controllers:

- Register routes only on `rest_api_init`.
- Provide schema for request and response data when practical.
- Use `permission_callback` for every route.
- Return `WP_Error` with an appropriate status for denied operations.
- Never expose write endpoints as public routes.

## Database And Migration Pattern

Use native storage first:

- Options for settings, with `autoload = false` for large or rarely used values.
- Metadata for per-object data when query volume is modest.
- Custom tables only for high-volume, relational, or performance-critical data.
- Woo orders through Woo CRUD/order query APIs only.

Migration rules:

- Store schema version separately from plugin version.
- Make every migration idempotent.
- Use `dbDelta()` for table creation/updates.
- Batch large data migrations with WP-CLI or Action Scheduler.
- Do not run long migrations during normal page loads.
- Add dry-run and rollback/repair commands for risky migrations.

## Asset And Build Pattern

- Use `@wordpress/scripts` unless the project has a documented alternative.
- Externalize WordPress packages such as `@wordpress/element`, `@wordpress/components`, `@wordpress/data`, `@wordpress/api-fetch`, and `@wordpress/i18n`.
- Externalize Woo packages where the official integration expects globals.
- Use generated `.asset.php` dependency/version files when registering built scripts.
- Scope admin CSS under a plugin root class or ID.
- Scope frontend CSS to the block, shortcode wrapper, or plugin-owned container.
- Do not globally override `body`, headings, links, buttons, tables, or Woo templates.

## Block Compatibility Pattern

- Use `block.json` as the source of truth.
- Keep editor, style, view, and render assets separate.
- Add block deprecations when saved markup changes.
- Use server rendering for dynamic data, permissions, or cache-sensitive output.
- Treat DataViews/DataForm and other fast-moving package APIs as version-gated until the target WordPress baseline is clear.

## Native Admin UI Pattern

Prefer:

- Settings API for simple settings.
- `WP_List_Table` or DataViews for tabular data, depending on target WP version and project stack.
- `add_meta_box()` for post/product edit panels where appropriate.
- WordPress media/color/date controls for familiar admin interactions.
- `@wordpress/components` for React admin or editor UIs.

Use custom SPA-style admin screens only for complex workflows that cannot be expressed through native screens.

