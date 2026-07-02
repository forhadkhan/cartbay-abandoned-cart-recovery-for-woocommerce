# Windsurf Adapter Guide

Use this guide to adapt the skill to Windsurf Cascade.

## Recommended Placement

Current Windsurf docs describe Rules, Workflows, Skills, Memories, and `AGENTS.md`. Use:

```text
AGENTS.md
.windsurf/rules/wp-woocommerce-plugin-engineer.md
.windsurf/rules/woocommerce-high-risk.md
.windsurf/workflows/wp-review.md
.windsurf/workflows/wp-release.md
docs/ai/error-knowledge-base.md
```

Prefer version-controlled Rules or `AGENTS.md` over relying on auto-generated Memories.

## Workspace Rule

Create `.windsurf/rules/wp-woocommerce-plugin-engineer.md`:

```markdown
---
trigger: model_decision
description: WordPress plugin and WooCommerce extension engineering guardrails.
---

For WordPress/WooCommerce work, classify the task as generic WordPress, WooCommerce-specific, or mixed.
Use native APIs first.
Require capability checks, nonces, sanitization, validation, late escaping, REST permission callbacks, scoped assets, i18n, and accessibility.
For WooCommerce, use CRUD APIs, HPOS-safe order access, Store API/Checkout Blocks guidance, and QIT where available.
Document repeated errors in docs/ai/error-knowledge-base.md.
```

## High-Risk Rule

Create `.windsurf/rules/woocommerce-high-risk.md`:

```markdown
---
trigger: glob
globs:
  - "src/WooCommerce/**"
  - "includes/**woocommerce**"
  - "tests/woocommerce/**"
description: Extra checks for WooCommerce transactional surfaces.
---

Treat checkout, payments, orders, subscriptions, inventory, refunds, taxes, shipping, and migrations as high risk.
Audit HPOS, checkout blocks, Store API schemas, idempotency, logging, Action Scheduler, and rollback.
```

## Workflows

Create `.windsurf/workflows/wp-review.md`:

```markdown
Review the current diff using the WordPress + WooCommerce Plugin Engineer skill.
Lead with findings by severity.
Check security, performance, compatibility, accessibility, HPOS, checkout, Store API, PCP/QIT, tests, and release risk.
```

Create `.windsurf/workflows/wp-error-log.md`:

```markdown
Update docs/ai/error-knowledge-base.md for the resolved problem.
Include symptom, exact error, environment, root cause, fix, prevention, detection query, tests, and official sources.
```

## Windsurf Usage Notes

- Keep global rules short.
- Use workspace rules for team-shared standards.
- Use workflows for repeated review/release/error-log procedures.
- Use `AGENTS.md` for cross-tool compatibility.

