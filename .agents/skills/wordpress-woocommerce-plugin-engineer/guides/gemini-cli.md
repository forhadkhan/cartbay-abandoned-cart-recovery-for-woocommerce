# Gemini CLI Adapter Guide

Use this guide to adapt the skill to Gemini CLI.

## Recommended Placement

Gemini CLI uses context files such as `GEMINI.md` and supports skills/extensions in current official docs. Use:

```text
GEMINI.md
.gemini/skills/wp-woocommerce-plugin-engineer/SKILL.md
.gemini/commands/
docs/ai/error-knowledge-base.md
```

Keep the concise rules in `GEMINI.md`; install the full skill as a workspace skill when available.

## GEMINI.md Content

```markdown
# WordPress/WooCommerce Plugin Engineering

Use the local WordPress + WooCommerce Plugin Engineer skill for plugin work.
Classify each task as generic WordPress, WooCommerce-specific, or mixed.
Use official APIs and current docs for WordPress, Gutenberg, WooCommerce, Plugin Check, QIT, and MCP/Abilities.
Never skip nonce/capability checks, REST permission callbacks, sanitization, validation, escaping, Woo CRUD, HPOS review, or scoped asset loading.
Record recurring errors and fixes in docs/ai/error-knowledge-base.md.
```

## Gemini CLI Commands

Useful interactive commands in current Gemini CLI docs include:

```text
/skills reload
/commands reload
/memory reload
/mcp reload
```

Run reload commands after editing local instructions, skills, commands, or MCP config.

## Auto Memory Guidance

Gemini CLI Auto Memory is documented as experimental. Use it only as a proposal system:

- Review every memory or skill patch before applying.
- Promote only repeated, durable WordPress/WooCommerce patterns.
- Never auto-accept rules that weaken security, WooCommerce CRUD, HPOS, PCP, or QIT gates.
- Prefer project-local memory for plugin-specific discoveries.

## Extension Packaging

If distributing internally as a Gemini CLI extension, include:

```text
extension root/
  gemini-extension.json
  skills/wp-woocommerce-plugin-engineer/SKILL.md
  skills/wp-woocommerce-plugin-engineer/guides/
  commands/wp-review.md
  commands/wp-release.md
```

Verify the current Gemini CLI extension manifest format before publishing.

