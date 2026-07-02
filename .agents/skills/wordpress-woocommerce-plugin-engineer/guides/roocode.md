# RooCode Adapter Guide

Roo Code official docs indicate the product is sunsetting on May 15, 2026. Treat this adapter as legacy support and prefer migrating rules to Cline, Codex, Cursor, Windsurf, Claude Code, Gemini CLI, or another maintained agent.

## Recommended Placement For Existing RooCode Users

Use project rules and custom modes if your installed version still supports them:

```text
.roo/rules/
.roo/rules-code/
.roo/rules-review/
.roomodes
AGENTS.md
docs/ai/error-knowledge-base.md
```

Verify the exact current RooCode rule/mode format in your installed version before relying on it.

## Code Mode Instructions

Use for `.roo/rules-code/wp-woocommerce-plugin-engineer.md`:

```markdown
For WordPress/WooCommerce code changes:
- Classify task as generic WordPress, WooCommerce-specific, or mixed.
- Inspect architecture before editing.
- Use official APIs and repository patterns.
- Never bypass capability checks, nonces, sanitization, validation, escaping, REST permission callbacks, scoped assets, or Woo CRUD.
- Add/update tests proportional to risk.
- Log recurring errors in docs/ai/error-knowledge-base.md.
```

## Review Mode Instructions

Use for `.roo/rules-review/wp-woocommerce-review.md`:

```markdown
Review WordPress/WooCommerce diffs by severity.
Check security, performance, compatibility, accessibility, HPOS, checkout blocks, Store API, payments/orders/subscriptions/inventory, migrations, PCP, QIT, and test coverage.
Lead with actionable findings and file references.
```

## Migration Advice

Before RooCode shutdown or replacement:

1. Export useful rules to `AGENTS.md`.
2. Move platform-specific files into the target platform format.
3. Preserve `docs/ai/error-knowledge-base.md`.
4. Re-run a review task in the new platform and compare findings.

