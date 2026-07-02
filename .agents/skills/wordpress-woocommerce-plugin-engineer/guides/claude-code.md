# Claude Code Adapter Guide

Use this guide to adapt the skill to Claude Code.

## Recommended Placement

Use the platform's current memory and command mechanisms:

```text
CLAUDE.md
.claude/commands/wp-review.md
.claude/commands/wp-release.md
.claude/commands/wp-error-log.md
docs/ai/error-knowledge-base.md
```

Current Claude Code docs describe `CLAUDE.md` memory files and custom slash commands stored as Markdown under `.claude/commands/`. Keep this adapter small and verify current Claude Code docs before relying on newer features.

## CLAUDE.md Content

Put only the always-on subset in `CLAUDE.md`:

```markdown
# WordPress/WooCommerce Engineering Rules

For WordPress and WooCommerce work, follow the WordPress + WooCommerce Plugin Engineer skill.
Classify tasks as generic WordPress, WooCommerce-specific, or mixed.
Use official WordPress/WooCommerce APIs first.
Never bypass capability checks, nonces, sanitization, validation, escaping, REST permission callbacks, or WooCommerce CRUD APIs.
Treat checkout, payments, orders, subscriptions, inventory, migrations, customer data, and AI write tools as high risk.
Run relevant tests and document recurring errors in docs/ai/error-knowledge-base.md.
```

## Slash Commands

Create focused commands instead of loading the full blueprint every session.

`.claude/commands/wp-review.md`:

```markdown
Review this WordPress/WooCommerce diff using the skill rules.
Lead with findings by severity.
Check security, performance, compatibility, accessibility, HPOS, checkout blocks, Store API, PCP/QIT readiness, and tests.
```

`.claude/commands/wp-release.md`:

```markdown
Prepare a release review for this plugin.
Check versioning, readme/changelog, build zip contents, PCP, QIT if WooCommerce, upgrade path, rollback path, compatibility matrix, and known risks.
```

`.claude/commands/wp-error-log.md`:

```markdown
Create or update docs/ai/error-knowledge-base.md for the error we just resolved.
Include environment, exact symptom, root cause, fix, prevention rule, detection query, tests added, and official sources.
```

## Claude Code Agent Pattern

If using subagents, create distinct reviewer roles:

- WordPress standards reviewer.
- WooCommerce compatibility reviewer.
- Security reviewer.
- Test/release reviewer.

Do not let multiple agents edit the same files without an integrator pass.

