# Antigravity Adapter Guide

Use this guide to adapt the skill to Google Antigravity or Antigravity-style agent environments. Antigravity conventions have changed quickly in 2026, so verify current official product docs before publishing team-wide rules.

## Recommended Placement

Use the most portable form first:

```text
AGENTS.md
.agent/rules/wp-woocommerce-plugin-engineer.md
.agent/rules/woocommerce-high-risk.md
.agent/workflows/wp-review.md
.agent/workflows/wp-release.md
docs/ai/error-knowledge-base.md
```

If the product prefers `GEMINI.md` in your version, keep it as a short pointer to `AGENTS.md` and the local skill files.

## AGENTS.md

```markdown
# Agent Instructions

Use the WordPress + WooCommerce Plugin Engineer rules for plugin work.
Classify tasks as generic WordPress, WooCommerce-specific, or mixed.
Plan before editing non-trivial changes.
Use official APIs first.
Never bypass WordPress security checks or WooCommerce CRUD/HPOS rules.
Run relevant checks and document recurring errors in docs/ai/error-knowledge-base.md.
Ask for human approval before destructive migrations, payment/order state changes, customer data exports, or AI-tool write permissions.
```

## Rule File

Create `.agent/rules/wp-woocommerce-plugin-engineer.md`:

```markdown
For WordPress and WooCommerce repositories:
- Inspect plugin architecture, versions, headers, dependencies, hooks, REST routes, blocks, tests, and CI first.
- Use WordPress/WooCommerce public APIs.
- Keep edits scoped.
- Add tests for high-risk behavior.
- Verify with PCP/QIT when relevant.
- Add durable fixes to docs/ai/error-knowledge-base.md.
```

## Workflow Suggestions

Create workflows for:

- `wp-implementation-plan`: classify, inspect, choose APIs, identify tests.
- `wp-security-review`: nonce, capability, validation, escaping, REST, SQL, file handling, secrets.
- `wc-compatibility-review`: HPOS, CRUD, Store API, checkout blocks, payment/order lifecycle, QIT.
- `wp-release-review`: versioning, readme, changelog, zip, PCP, QIT, rollback.
- `wp-error-log`: create/update error knowledge base entry.

## Safety Notes

- Do not let autonomous multi-agent runs edit the same files without an integrator.
- Require explicit approval for destructive operations.
- Keep the deep blueprint out of always-on instructions; reference it only for complex decisions.

