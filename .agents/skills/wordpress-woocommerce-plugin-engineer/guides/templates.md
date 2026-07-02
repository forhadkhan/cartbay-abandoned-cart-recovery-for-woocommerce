# Adapter Templates

Use these when a platform only supports plain instruction files.

## AGENTS.md Template

```markdown
# Agent Instructions

Use the WordPress + WooCommerce Plugin Engineer skill for this repository.

## Routing
- Classify every task as generic WordPress, WooCommerce-specific, or mixed.
- Treat checkout, payments, orders, subscriptions, inventory, migrations, customer data, REST writes, and AI-tool writes as high risk.

## Standards
- Use official WordPress and WooCommerce APIs first.
- Preserve backward compatibility and shared-hosting reality.
- Keep edits scoped.
- Add tests proportional to risk.

## Security
- Always check capabilities and nonces for admin actions.
- Every REST route must have a real permission callback.
- Sanitize, validate, and escape by context.
- Use prepared SQL and whitelist identifiers.

## WooCommerce
- Use WooCommerce CRUD APIs for products, orders, customers, and coupons.
- Do not read/write order data through posts or postmeta.
- Audit HPOS before declaring compatibility.
- Test Cart/Checkout Blocks and Store API when checkout/cart behavior changes.

## QA
- Run PHPCS/WPCS, static analysis, PHPUnit, E2E, PCP, and QIT where available.
- Document recurring errors in docs/ai/error-knowledge-base.md.
```

## Review Prompt Template

```markdown
Review the current WordPress/WooCommerce diff.
Lead with findings by severity and include file/line references.
Check:
- Security: capabilities, nonces, REST permissions, validation, sanitization, escaping, SQL, files, secrets.
- WordPress compatibility: hooks, lifecycle, multisite, i18n, assets, performance, accessibility.
- WooCommerce compatibility: CRUD, HPOS, Store API, checkout blocks, shortcode checkout if supported, order/payment lifecycle, Action Scheduler, QIT.
- Tests and release risk.
Return findings first, then open questions, tests run, and release risk.
```

## Implementation Prompt Template

```markdown
Implement this WordPress/WooCommerce change using the project architecture.
First classify it as generic WordPress, WooCommerce-specific, or mixed.
Use official APIs and scoped edits.
Add or update tests proportional to risk.
Run relevant checks.
If you encounter a reusable failure, update docs/ai/error-knowledge-base.md.
```

## Release Prompt Template

```markdown
Prepare a production release review.
Check version bump, readme, changelog, build zip contents, dependencies, licenses, WordPress.org slug/trademark constraints, external service disclosure, readme validator, PCP, QIT if WooCommerce, compatibility matrix, upgrade path, rollback path, and known risks.
Do not approve release if security, migration, checkout, payment, order, HPOS, or artifact issues remain unresolved.
```

## Project Profile Template

```yaml
project:
  plugin_slug:
  text_domain:
  php_namespace:
  php_root:
  js_root:
  assets_root:
  package_manager:
  php_min:
  wordpress_min:
  woocommerce_min:
  css_scope:
commands:
  dev:
  build:
  release:
  phpcs:
  phpstan:
  phpunit:
  e2e:
  i18n_make_pot:
  i18n_make_json:
```
