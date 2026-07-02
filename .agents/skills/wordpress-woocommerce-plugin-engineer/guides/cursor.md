# Cursor Adapter Guide

Use this guide to adapt the skill to Cursor.

## Recommended Placement

Current Cursor docs recommend Project Rules in `.cursor/rules`, support `AGENTS.md`, and mark `.cursorrules` as legacy/deprecated.

Use:

```text
AGENTS.md
.cursor/rules/wp-woocommerce-plugin-engineer.mdc
.cursor/rules/woocommerce-high-risk.mdc
docs/ai/error-knowledge-base.md
```

## AGENTS.md

Use `AGENTS.md` for simple shared instructions:

```markdown
# Agent Instructions

Use the WordPress + WooCommerce Plugin Engineer rules for plugin work.
Classify work as generic WordPress, WooCommerce-specific, or mixed.
Use official APIs first.
Never bypass security checks.
Use WooCommerce CRUD APIs for orders and test HPOS.
Run PCP/QIT where relevant.
Document recurring errors in docs/ai/error-knowledge-base.md.
```

## Cursor Project Rule

Create `.cursor/rules/wp-woocommerce-plugin-engineer.mdc`:

```markdown
---
description: Apply to WordPress plugin and WooCommerce extension work.
alwaysApply: false
---

When editing WordPress or WooCommerce code:
- Inspect existing architecture before editing.
- Prefer native WordPress and WooCommerce APIs.
- Require capabilities, nonces, validation, sanitization, late escaping, and REST permission callbacks.
- Scope assets to the relevant admin, editor, frontend, or checkout surface.
- For WooCommerce, use CRUD APIs, audit HPOS, and test Cart/Checkout Blocks when relevant.
- Run or request PHPCS/WPCS, PHPStan/Psalm, PHPUnit, Playwright, PCP, and QIT as appropriate.
- Add recurring failures to docs/ai/error-knowledge-base.md.
```

## High-Risk Woo Rule

Create `.cursor/rules/woocommerce-high-risk.mdc` and attach it to WooCommerce files or invoke manually:

```markdown
---
description: Extra guardrails for WooCommerce checkout, payment, order, subscription, inventory, and migration work.
alwaysApply: false
---

Treat this change as high risk.
Check WooCommerce lifecycle, CRUD APIs, HPOS compatibility, Store API schemas, checkout block support, shortcode checkout support if applicable, payment idempotency, logging without secrets, Action Scheduler usage, and QIT readiness.
Do not use WooCommerce Internal APIs unless documented as an accepted risk.
```

## Cursor Usage Notes

- Prefer Project Rules over legacy `.cursorrules`.
- Use `AGENTS.md` when the repository needs a simple cross-tool rule.
- Keep rule files focused; do not paste the full blueprint into Cursor rules.

