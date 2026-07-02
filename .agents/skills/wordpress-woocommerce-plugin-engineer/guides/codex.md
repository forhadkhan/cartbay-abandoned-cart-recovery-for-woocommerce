# Codex Adapter Guide

Use this guide to install and operate this as a Codex skill.

## Install As A Codex Skill

Create a skill directory:

```text
~/.codex/skills/wp-woocommerce-plugin-engineer/
  SKILL.md
  guides/
  wp-woocommerce-ai-skill-system-blueprint-2026.md
```

Copy the root `SKILL.md`, `guides/`, and the deep blueprint into that folder. Keep the relative links intact.

## Triggering

The skill should trigger for:

- WordPress plugin development or review.
- WooCommerce extension work.
- Gutenberg/block editor tasks.
- Plugin Check, QIT, HPOS, Store API, checkout, payment, order, migration, release, or CI work.
- Requests to create Codex/Claude/Cursor/Windsurf/Gemini/Roo/Antigravity rules for WordPress/WooCommerce.

## Codex Operating Notes

- Use repository inspection before edits.
- Use web/docs lookup for current official WordPress/WooCommerce/OpenAI/platform details.
- Use `rg` for source discovery.
- Use `apply_patch` for manual edits.
- Preserve user changes in dirty worktrees.
- For frontend changes, run a local server and use browser verification where available.
- For reviews, lead with findings and file/line references.
- For long tasks, update the user while working and finish with tests run plus residual risks.

## Codex Project Add-On

For each plugin repository, add a project `AGENTS.md` or equivalent:

```markdown
# Project Agent Rules

Use the WordPress + WooCommerce Plugin Engineer skill for plugin work.
Classify each task as generic WordPress, WooCommerce-specific, or mixed.
Do not bypass nonce/capability checks.
Use WooCommerce CRUD APIs for orders and test HPOS compatibility.
Run PCP before WordPress.org release and QIT before WooCommerce release where available.
Document recurring errors in docs/ai/error-knowledge-base.md.
```

## Codex Quality Gates

Before final response on code changes, report:

- Files changed.
- Security-sensitive surfaces touched.
- Tests/checks run.
- PCP/QIT status if relevant.
- Any error knowledge base entries added.

