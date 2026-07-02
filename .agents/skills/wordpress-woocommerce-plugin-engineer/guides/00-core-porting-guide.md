# Core Porting Guide

This guide turns the blueprint into practical instructions for any AI coding platform.

## Re-Evaluation Summary

The full blueprint is strong as a research and policy artifact, but too large to load on every coding task. The production shape should be:

- `SKILL.md`: short always-loaded runtime workflow.
- Platform guides: loaded only when installing or adapting the skill.
- Deep blueprint: source of truth for detailed architecture and future updates.
- Error knowledge base: project-local operational memory.
- Templates: copy-ready rule files for tools without native skills.

## Universal Installation Model

For any platform:

1. Put the concise runtime rules where the platform loads persistent instructions.
2. Keep the full blueprint in the repository as reference, not always-on context.
3. Add platform-specific commands/workflows for review, QA, release, and error logging.
4. Add `docs/ai/error-knowledge-base.md` to every real plugin repository.
5. Make official documentation refresh part of release work.

## Universal Agent Startup Prompt

Use this as the first instruction when a platform does not support skills:

```markdown
You are using the WordPress + WooCommerce Plugin Engineer skill.
First classify the task as generic WordPress, WooCommerce-specific, or mixed.
Use native WordPress/WooCommerce APIs first.
Treat security, REST writes, migrations, checkout, payments, orders, subscriptions, inventory, and customer data as high risk.
Run relevant tests and document recurring errors in docs/ai/error-knowledge-base.md.
If the task depends on current WordPress, Gutenberg, WooCommerce, Plugin Check, QIT, or platform behavior, verify official docs before implementing.
```

## Repository Layout for Consumers

Recommended target repository additions:

```text
AGENTS.md
docs/ai/
  error-knowledge-base.md
  decision-log.md
  compatibility-notes.md
  recovery-playbook.md
  test-failures.md
```

For platform-specific tools, add one of:

```text
.cursor/rules/
.windsurf/rules/
.claude/commands/
.gemini/skills/
.roo/
.agent/rules/
```

## What To Keep Out Of Always-On Rules

Do not paste the full blueprint into every platform memory. Keep always-on files under the platform's practical limit and focus on:

- Task classification.
- Non-negotiable security and WooCommerce rules.
- Testing gates.
- Error logging.
- Source-of-truth links.

## Update Rule

When the blueprint changes, update downstream artifacts in this order:

1. `SKILL.md`
2. `AGENTS.md` or equivalent global project rule
3. Platform rules/skills
4. MCP tool permissions
5. CI/review/release templates
6. Error knowledge base patterns

