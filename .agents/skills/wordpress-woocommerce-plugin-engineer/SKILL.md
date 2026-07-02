---
name: wordpress-woocommerce-plugin-engineer
description: Use when building, reviewing, testing, maintaining, or releasing production WordPress plugins and WooCommerce extensions. Covers native WordPress APIs, Gutenberg/block editor work, WooCommerce HPOS, Store API, Cart and Checkout Blocks, payment/order safety, PCP, QIT, CI/release gates, AI-agent error logging, and platform adapters for Codex, Claude Code, Gemini CLI, Cursor, Windsurf, RooCode, Antigravity, and MCP-based tool systems.
metadata:
  short-description: Production WordPress and WooCommerce plugin engineering
---

# WordPress + WooCommerce Plugin Engineer

Use this skill for professional plugin engineering, not quick snippets. Optimize for official APIs, backward compatibility, marketplace compliance, shared hosting, maintainability, accessibility, security, and deterministic QA.

The deep source blueprint is [wp-woocommerce-ai-skill-system-blueprint-2026.md](wp-woocommerce-ai-skill-system-blueprint-2026.md). Load it only for deep architecture, policy updates, release-system design, or unresolved edge cases.

## Reference Map

Load only the guide that matches the task:

- Platform-neutral porting: [guides/00-core-porting-guide.md](guides/00-core-porting-guide.md)
- Codex skill installation/use: [guides/codex.md](guides/codex.md)
- Claude Code: [guides/claude-code.md](guides/claude-code.md)
- Gemini CLI: [guides/gemini-cli.md](guides/gemini-cli.md)
- Cursor: [guides/cursor.md](guides/cursor.md)
- Windsurf: [guides/windsurf.md](guides/windsurf.md)
- RooCode: [guides/roocode.md](guides/roocode.md)
- Antigravity: [guides/antigravity.md](guides/antigravity.md)
- MCP/tool servers: [guides/mcp-and-tools.md](guides/mcp-and-tools.md)
- Implementation patterns: [guides/implementation-patterns.md](guides/implementation-patterns.md)
- WooCommerce patterns: [guides/woocommerce-patterns.md](guides/woocommerce-patterns.md)
- Repository intelligence and automated scans: [guides/repository-intelligence-and-automation.md](guides/repository-intelligence-and-automation.md)
- WordPress.org submission/release: [guides/wordpress-org-submission.md](guides/wordpress-org-submission.md)
- Project-specific overrides: [guides/project-specific-overrides.md](guides/project-specific-overrides.md)
- Error logging and skill maintenance: [guides/maintenance-and-error-knowledge.md](guides/maintenance-and-error-knowledge.md)
- Copy-ready adapter templates: [guides/templates.md](guides/templates.md)

## Task Routing

Classify every request before editing:

- **Generic WordPress**: admin plugins, settings pages, REST routes, WP-CLI, blocks, editor integrations, content workflows, performance/SEO/security/utilities.
- **WooCommerce-specific**: products, cart, checkout, Store API, payment gateways, orders, HPOS, subscriptions, shipping, taxes, inventory, Woo admin/analytics, QIT.
- **Mixed**: a WordPress plugin that optionally extends WooCommerce. Keep generic services loadable without WooCommerce and gate Woo services behind Woo availability checks.

If the task touches checkout, payments, orders, subscriptions, inventory, migrations, customer data, authentication, REST writes, AI/MCP write tools, or destructive operations, treat it as high risk.

## Operating Workflow

1. Inspect the repository first: plugin headers, Composer/NPM setup, build tools, tests, CI, minimum PHP/WP/WC versions, Woo headers, block metadata, REST routes, migrations, and existing architecture.
   - If the repository contains `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `.cursor/rules`, `.windsurfrules`, `.roo/rules`, or another local agent instruction file, read it before editing and treat it as the project profile when it does not weaken official platform or security rules.
2. Choose native extension points first: hooks, Settings API, REST API, WP-CLI, `block.json`, WordPress packages, WooCommerce CRUD, Store API, Action Scheduler, Woo settings/navigation.
3. Check official docs for current or unstable areas: Gutenberg packages, Script Modules, Interactivity API, Block Bindings, DataViews, Plugin Check, Woo HPOS, Cart/Checkout Blocks, QIT, MCP/Abilities.
4. Plan scoped edits. Do not rewrite unrelated architecture.
5. Implement with namespaced PHP, late escaping, strict input validation, scoped assets, i18n, accessibility, and compatibility guards.
6. Add or update tests proportional to risk.
7. Run repository-intelligence scans for affected surfaces when the change touches hooks, REST, checkout, orders, migrations, assets, or release packaging.
8. Run the strongest available local checks.
9. Record reusable errors or hard-won fixes using the maintenance guide.

## Non-Negotiable Rules

- Never use nonce checks as authorization. Always check capabilities.
- Never trust request, REST, AJAX, CLI, webhook, block attribute, Store API, or AI-tool input.
- Always `wp_unslash()` request data before sanitizing WordPress superglobals.
- Sanitize and validate before storage or domain use. Escape late by output context.
- Every REST route must have a real `permission_callback`.
- Use `$wpdb->prepare()` and whitelist identifiers for SQL.
- Use the WordPress HTTP API for plugin-owned HTTP calls; do not use raw cURL in plugin code.
- Do not enqueue global admin/frontend assets unless the behavior is intentionally global.
- Do not load remote runtime code or third-party CDN assets in WordPress.org-bound plugins.
- Do not use `eval`, dynamic code execution, PHP short tags, HEREDOC/NOWDOC for generated markup, global `ini_set`, or `date_default_timezone_set`.
- Do not create custom tables unless the data shape, scale, or query model justifies it.
- Do not run long or destructive migrations during normal page requests.
- Do not use WooCommerce internal namespaces or `@internal` APIs without an explicit risk note and fallback plan.
- Do not access WooCommerce order data through `wp_posts` or `wp_postmeta`; use Woo CRUD APIs.
- Do not declare HPOS compatibility until audited and tested.
- Do not create SaaS-style wp-admin dashboards for simple settings.
- Do not ship without PCP for WordPress.org-bound plugins.
- Do not ship WooCommerce changes without HPOS and checkout compatibility review.

## Preferred Stack

- PHP: broad plugins may support PHP 7.4; controlled/commercial plugins may prefer PHP 8.1+ with clear requirements.
- Build: `@wordpress/scripts` by default; pilot `@wordpress/build` only with current official guidance.
- UI: native WordPress admin patterns, `@wordpress/components`, `@wordpress/data`, `@wordpress/api-fetch`, `@wordpress/i18n`.
- Blocks: `block.json`, server rendering for dynamic output, block supports, dependency asset files.
- Frontend interactivity: Interactivity API and Script Modules only with version guards or clear minimum WP requirements.
- Woo async: Action Scheduler.
- Static checks: WPCS/PHPCS, PHPStan or Psalm, ESLint, stylelint where present.
- Release gates: Plugin Check for WordPress, QIT for WooCommerce where available.

## Testing Gates

Run what exists, and add missing coverage when risk warrants it:

- PHP lint, Composer validate, PHPCS/WPCS.
- PHPStan or Psalm with WordPress/Woo stubs.
- PHPUnit with WordPress test suite.
- `wp-env` integration tests where present.
- Playwright E2E for admin/editor/frontend/checkout flows.
- Accessibility checks plus manual keyboard review for UI changes.
- Plugin Check for WordPress.org-facing plugins.
- WordPress.org release review: readme validator, zip structure, license/header consistency, slug/trademark checks, external-service disclosure, and source availability for compiled assets.
- QIT and Woo matrix for WooCommerce-facing extensions.
- HPOS enabled/disabled tests for order work.
- Cart/Checkout Blocks and shortcode checkout tests if both are supported.

## Review Output

For reviews, lead with findings ordered by severity:

```markdown
## Findings
- [P0] ...
- [P1] ...
- [P2] ...

## Open Questions
- ...

## Tests Run
- ...

## Release Risk
Low | Medium | High, with reason.
```

## Skill Maintenance

When an error takes meaningful time to solve, recurs, affects release quality, or reveals missing standards, update the project error knowledge base. Read [guides/maintenance-and-error-knowledge.md](guides/maintenance-and-error-knowledge.md) before creating or updating those notes.

Promote repeated fixes into rules, tests, snippets, CI gates, or review checklist items. Prefer mechanical prevention over prose-only reminders.
