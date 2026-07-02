# WordPress.org Submission And Release Guide

Use this guide before submitting or releasing a WordPress.org plugin. It distills the user's pre-submission checklist into operational release rules for agents.

## When To Use

Load this guide when:

- Creating a plugin intended for WordPress.org.
- Preparing a release zip.
- Reviewing `readme.txt`, plugin headers, licensing, assets, SVN layout, or submission readiness.
- Responding to WordPress.org plugin review feedback.

## Critical Release Gates

Do not approve submission until these pass:

- WordPress.org account is active, contact details are current, and the account email is monitored without auto-replies or ticket-system autoresponders.
- Plugin is complete, production-ready, and not a placeholder, framework-only package, duplicate, or test-only plugin.
- Only one plugin is in the review queue unless the account qualifies for more.
- Zip is under 10 MB and installable through WP Admin upload.
- Zip contains one top-level folder matching the final plugin slug.
- Main plugin file name matches the slug.
- `readme.txt` exists in the plugin root and passes the official readme validator.
- Main PHP header `Version` exactly matches `readme.txt` `Stable tag`.
- PHP header license exactly matches `readme.txt` license.
- `Requires at least`, `Requires PHP`, and `Tested up to` are plain version numbers and reflect actual testing.
- `Text Domain` exactly matches the plugin slug.
- Slug is final, unique, not trademark-infringing, not versioned, and does not begin with prohibited brand terms.
- For Woo-related plugins, do not start the slug with `woo`; prefer `wc` or "for WooCommerce" naming where appropriate.
- No dev folders or artifacts are shipped: `.git`, `.svn`, `node_modules`, test data, logs, dumps, `.DS_Store`, IDE folders, or nested zips.
- Composer/NPM build metadata is included when required for transparency.

## Header Checklist

Required or strongly expected fields:

```php
/**
 * Plugin Name:       Example Plugin
 * Plugin URI:        https://example.com/example-plugin
 * Description:       A concise description under 150 characters.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Vendor
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       example-plugin
 * Domain Path:       /languages
 */
```

WooCommerce extensions should also declare Woo metadata where applicable:

```php
 * WC requires at least: 8.0
 * WC tested up to:      10.0
```

## Readme Rules

- File name is exactly `readme.txt`.
- First line is `=== Plugin Name ===`.
- `Contributors` are WordPress.org usernames.
- `Tags` are useful, not stuffed, not competitor names, and limited.
- `Stable tag` is a version number, not `trunk`.
- Short description is no more than 150 characters and has no markup.
- Keep `readme.txt` concise; move long history to `changelog.txt`.
- Include setup steps, FAQ, screenshots, changelog, and upgrade notices where relevant.
- If the plugin uses an external service, document:
  - Service name.
  - Service URL.
  - Terms of service URL.
  - Privacy policy URL.
  - What data is sent.
  - When data is sent.
  - Whether user opt-in is required.

## Security Rejection Sweep

The three most common rejection areas are unsanitized input, unescaped output, and form data without nonce verification. Before release:

- Sanitize only the request keys the plugin needs.
- Use `wp_unslash()` before sanitizing superglobal values.
- Escape every output late by context.
- Replace `_e()`/`_ex()` with escaped equivalents unless output is separately escaped.
- Use `wp_json_encode()` instead of raw `json_encode()` for emitted JSON.
- Verify nonces and capabilities for every privileged form/AJAX action.
- Every REST route has a real permission callback.
- Every custom SQL query uses `$wpdb->prepare()`.
- File uploads use WordPress upload APIs.
- No `eval`, dynamic code execution, short PHP tags, global `ini_set`, global `error_reporting`, or `date_default_timezone_set`.

## Licensing And Source Availability

- Use GPLv2-or-later or a GPL-compatible license.
- Ensure every bundled PHP, JS, CSS, image, font, and media asset is GPL-compatible or compatibly licensed.
- Preserve attribution for derived/forked code.
- Do not re-bundle libraries WordPress already provides unless there is a strong reason.
- Do not ship abandoned, dev, alpha, or beta dependencies.
- If minified or compiled assets ship, include unminified source or link to a public source repository in the readme.

## Admin And Privacy Rules

- No forced activation redirects except narrowly justified first-run setup.
- No persistent site-wide notices that cannot be dismissed.
- No admin ads or tracking without consent.
- No external calls without clear disclosure and opt-in where required.
- If personal data is stored, implement privacy policy content, exporter, and eraser hooks as appropriate.
- Public "powered by" links must default off and be opt-in.

## SVN Release Rules

After approval:

- Treat WordPress.org SVN as a release repository, not a development repository.
- Plugin files go directly in `trunk/`, not inside a nested folder.
- Push only production-ready code; first SVN commit makes the plugin public.
- Tags use numbers and periods only, such as `1.2.3`.
- Do not commit zip files or SVN externals.
- Banners and icons go in SVN `/assets/`, not plugin runtime assets.
- Update PHP header version and `Stable tag` together for code releases.
- No new version is required for readme-only or directory asset-only changes.

## Distribution Ignore Template

Use a `.distignore` or build script with equivalent behavior. Adjust for the project, but do not ship development-only artifacts.

```gitignore
.git
.github
.svn
node_modules
tests
vendor/bin
coverage
playwright-report
.phpunit.result.cache
phpunit.xml
phpstan.neon
playwright.config.ts
tsconfig.json
webpack.config.js
*.map
*.ts
*.tsx
*.scss
README.md
*.zip
debug.log
error_log
.DS_Store
Thumbs.db
```

Keep required runtime files such as compiled assets, `vendor/` without dev tools when needed, `languages/`, templates, `readme.txt`, license files, and generated asset dependency files.

## Review Process Rules

- Monitor `plugins@wordpress.org` email during review.
- Respond to reviewer feedback promptly.
- If rejected for guideline/code issues, reply to the review email instead of resubmitting a duplicate plugin.
- Do not ask for priority review or submit deadline requests.
- Security vulnerabilities should be reported through the proper WordPress security channel.
- Do not use fake accounts for support posts, reviews, or review manipulation.

## Final Agent Review Output

```markdown
## WordPress.org Submission Review
Decision: pass | changes required

Critical blockers:
- ...

Header/readme:
- ...

Security:
- ...

Licensing/source:
- ...

Zip/SVN:
- ...

Tests run:
- PHPCS:
- Plugin Check:
- Clean install:
- Activate/deactivate/uninstall:

Residual risk:
```
