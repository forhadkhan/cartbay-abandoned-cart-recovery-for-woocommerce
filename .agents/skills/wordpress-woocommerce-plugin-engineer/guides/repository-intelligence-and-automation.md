# Repository Intelligence And Automation Guide

Use this guide when building agentic review loops, MCP tools, CI scans, or preflight commands for WordPress/WooCommerce repositories.

## Repository Map

Before making non-trivial changes, build or refresh a map of:

- Main plugin file, headers, constants, autoload.
- PHP namespaces and prefixes.
- Activation, deactivation, uninstall hooks.
- All `add_action()` and `add_filter()` registrations with priorities.
- REST routes and permission callbacks.
- AJAX handlers.
- WP-CLI commands.
- Blocks and `block.json` files.
- Admin pages and enqueue points.
- Woo feature declarations.
- Woo order/product/cart/checkout/payment/subscription surfaces.
- Migrations and background jobs.
- Tests and CI workflows.

## High-Value Static Searches

Run targeted searches before review:

```bash
rg "register_rest_route|wp_ajax_|admin_post_|check_ajax_referer|wp_verify_nonce|current_user_can" .
rg "echo |print |printf|wp_send_json|json_encode|wp_json_encode" src includes app
rg "\\$wpdb|get_results|get_var|get_row|query\\(" src includes app
rg "get_post_meta|update_post_meta|WP_Query|shop_order|wp_postmeta|wc_get_order|wc_get_orders" src includes app tests
rg "wp_enqueue_script|wp_enqueue_style|admin_enqueue_scripts|wp_enqueue_scripts|enqueue_block_editor_assets" src includes app
rg "register_activation_hook|register_deactivation_hook|uninstall.php|WP_UNINSTALL_PLUGIN" .
```

Adjust roots to the project layout.

## Review Automation Checks

Automated review should produce structured findings for:

- Missing capability checks on privileged operations.
- Nonce-only authorization.
- REST routes with permissive or missing permission callbacks.
- Superglobal access without `wp_unslash()` and sanitization.
- Echoed variables without context escaping.
- Raw SQL or prepared statements with interpolated identifiers.
- Woo order access through post/postmeta APIs.
- Global asset enqueues.
- Autoloaded large options.
- Long migrations in page requests.
- Direct filesystem writes where WP Filesystem or upload APIs are required.

## Semgrep Starter Rules

These are starter patterns, not a complete security scanner. Tune them per repository.

```yaml
rules:
  - id: wordpress-raw-superglobal
    message: "Superglobal access must be unslashed, sanitized, and validated."
    severity: WARNING
    languages: [php]
    pattern-either:
      - pattern: $_GET[...]
      - pattern: $_POST[...]
      - pattern: $_REQUEST[...]

  - id: wordpress-direct-order-meta
    message: "Possible HPOS violation: use WooCommerce order CRUD APIs for order data."
    severity: WARNING
    languages: [php]
    pattern-either:
      - pattern: get_post_meta(...)
      - pattern: update_post_meta(...)

  - id: wordpress-json-encode-output
    message: "Use wp_json_encode() for WordPress JSON output."
    severity: WARNING
    languages: [php]
    pattern: json_encode(...)
```

Do not blindly fail builds on broad starter rules. Promote tuned rules after false positives are understood.

## Agent Task Schema

For multi-agent systems, pass scoped task objects:

```json
{
  "task_id": "task_YYYYMMDD_001",
  "type": "implementation|review|test|release",
  "title": "Short task title",
  "domain": "wordpress|woocommerce|mixed",
  "risk_level": "low|medium|high",
  "affected_files": [],
  "acceptance_criteria": [],
  "test_requirements": [],
  "requires_human_review": false,
  "compatibility_targets": {
    "php": "",
    "wordpress": "",
    "woocommerce": ""
  }
}
```

Require human review for destructive migrations, payment/order state changes, customer exports, secrets, external-service data flows, and MCP write tools.

## Impact Assessment

For every diff, answer:

- Which hooks/routes/jobs/screens/blocks are affected?
- Which user roles/capabilities can reach the change?
- Which data is read/written/deleted?
- Which caches or scheduled jobs need invalidation?
- Which tests should be added or updated?
- Which docs/readme/changelog entries should change?
- Does this alter WP.org or Woo Marketplace compliance?

## Distribution Scan

Before building a release zip:

```bash
rg "\\.zip$|node_modules|\\.git|\\.svn|\\.DS_Store|error_log|debug\\.log" .
rg "TODO|FIXME|var_dump|print_r\\(|console\\.log|debugger" .
rg "Plugin Name:|Version:|Text Domain:|Requires PHP|Requires at least|WC requires at least|WC tested up to" .
```

Use the WordPress.org submission guide for final release validation.

