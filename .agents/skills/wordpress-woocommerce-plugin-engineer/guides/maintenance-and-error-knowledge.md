# Maintenance And Error Knowledge Guide

Use this guide after hard debugging sessions, CI failures, production incidents, release failures, or repeated agent mistakes.

## Required Files

Add these to real plugin repositories:

```text
docs/ai/error-knowledge-base.md
docs/ai/decision-log.md
docs/ai/compatibility-notes.md
docs/ai/recovery-playbook.md
docs/ai/test-failures.md
```

Small repositories may combine them into `docs/ai-notes.md`.

## Error Entry Template

````markdown
## Error: Short searchable title

Date: YYYY-MM-DD
Tags: security | wordpress-core | gutenberg | woocommerce-core | woocommerce-checkout | woocommerce-payment | performance | compatibility | ci | pcp | qit | release | ai-agent

Environment:
- WordPress:
- WooCommerce:
- PHP:
- Database:
- Multisite:
- HPOS:
- Checkout type:
- Theme:
- Relevant plugins:

Symptom:
Describe what failed.

Exact error:
```text
Smallest useful output. Redact secrets.
```

Root cause:
Explain the real cause.

Fix:
Describe the code/config/test change.

Files changed:
- path/to/file.php

Prevention:
- New test:
- New static rule:
- New checklist item:
- New agent rule:

Detection query:
```bash
rg "pattern that detects this issue"
```

Official sources:
- URL, title, retrieval date.

Status:
resolved | mitigated | unresolved | accepted-risk
````

## Promotion Rule

Promote an error note into the skill/rules when:

- It happens twice.
- It caused CI, PCP, QIT, release, or production failure.
- It touches security, checkout, payments, orders, migrations, inventory, customer data, or data loss.
- Official docs changed or clarified the behavior.

Promotion targets:

- Regression test.
- CI preflight.
- Static analysis rule.
- Review checklist item.
- Platform rule.
- MCP permission guard.
- Boilerplate change.

## Skill Update Procedure

1. Verify official sources first.
2. Classify the change: stable, stable-newer, emerging, experimental, deprecated, forbidden.
3. Update `wp-woocommerce-ai-skill-system-blueprint-2026.md` if the policy changed.
4. Update `SKILL.md` if runtime behavior changed.
5. Update platform guides or templates if installation/use changed.
6. Add or update regression tests where possible.
7. Record the update in the project's decision log.

## Monthly Maintenance Checklist

- Review unresolved errors.
- Search for repeated patterns.
- Check WordPress, Gutenberg, WooCommerce, Plugin Check, and QIT release notes.
- Refresh compatibility matrix.
- Remove obsolete workarounds.
- Confirm platform adapter files still match current platform docs.
- Ensure high-risk Woo rules still cover HPOS, Store API, checkout blocks, and QIT.

