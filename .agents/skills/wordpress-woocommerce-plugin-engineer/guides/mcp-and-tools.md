# MCP And Tool Server Guide

Use this guide when exposing WordPress or WooCommerce operations to AI agents through MCP, Abilities API, CLI wrappers, or internal tools.

## Tool Design Principles

- Prefer narrow tools over raw shell, raw SQL, or broad REST passthroughs.
- Separate read, write, destructive, and financial actions.
- Map every operation to a WordPress capability and enforce it at runtime.
- Include nonce/session/auth checks where applicable.
- Validate and sanitize all tool inputs.
- Return structured output with stable schemas.
- Log actor, tool name, input summary, affected object IDs, result, and rollback token where possible.
- Never return secrets, raw payment payloads, auth tokens, or private customer data unless explicitly authorized and audited.

## Recommended Tool Groups

Generic WordPress:

- `wp_get_site_context`
- `wp_list_plugins`
- `wp_get_plugin_health`
- `wp_run_plugin_check`
- `wp_run_tests`
- `wp_get_rest_routes`
- `wp_create_diagnostic_bundle`

WooCommerce:

- `wc_get_store_context`
- `wc_check_hpos_compatibility`
- `wc_get_order_summary`
- `wc_list_checkout_extensions`
- `wc_run_qit`
- `wc_get_action_scheduler_status`
- `wc_create_test_order` with sandbox/test mode only

Release:

- `build_plugin_zip`
- `validate_release_artifact`
- `generate_release_notes`
- `create_rollback_plan`

## Forbidden Tool Shapes

- Raw SQL executor.
- Arbitrary PHP evaluator.
- Unscoped filesystem writer.
- Payment capture/refund without confirmation and audit.
- Order status mutation without dry-run and permission check.
- Product bulk edit without preview and rollback plan.
- Customer data export without explicit capability and audit.

## MCP Prompt Contract

Every MCP server or tool pack should include:

```markdown
This tool server follows the WordPress + WooCommerce Plugin Engineer skill.
Use read tools before write tools.
Use dry-run for risky operations.
Require confirmation for destructive, financial, or customer-data operations.
Respect WordPress capabilities and WooCommerce lifecycle constraints.
```

## Testing MCP Tools

For every write tool:

- Unit-test schema validation.
- Integration-test capability denial.
- Integration-test happy path.
- Integration-test dry-run behavior.
- Integration-test rollback or compensating action where possible.
- Audit-log test with redaction.

