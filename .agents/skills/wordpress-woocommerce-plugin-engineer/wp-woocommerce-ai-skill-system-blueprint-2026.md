# Autonomous WordPress + WooCommerce Plugin Engineering Skill System Blueprint, May 2026

Version: 1.0  
Audience: autonomous coding agents, human maintainers, plugin architects, QA/release engineers  
Scope: production WordPress plugins, WooCommerce extensions, WordPress.org-ready packages, WooCommerce Marketplace-ready extensions, MCP/AI-agent engineering workflows  
Default assumption: shared hosting, multisite, PHP fragmentation, plugin/theme conflicts, strict marketplace review, and long-lived backward compatibility

## Source Policy

Official sources are the primary authority:

- WordPress Plugin Handbook: https://developer.wordpress.org/plugins/
- WordPress Coding Standards: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/
- WordPress Common APIs and Security: https://developer.wordpress.org/apis/
- WordPress REST API: https://developer.wordpress.org/rest-api/
- WordPress Block Editor Handbook: https://developer.wordpress.org/block-editor/
- Make/Core Field Guides: https://make.wordpress.org/core/
- Make/Performance: https://make.wordpress.org/performance/
- Make/Accessibility: https://make.wordpress.org/accessibility/
- Plugin Check: https://wordpress.org/plugins/plugin-check/ and https://github.com/WordPress/plugin-check
- WooCommerce Developer Docs: https://developer.woocommerce.com/docs/
- WooCommerce extension best practices: https://developer.woocommerce.com/docs/extensions/best-practices-extensions/extension-development-best-practices/
- WooCommerce HPOS: https://developer.woocommerce.com/docs/features/high-performance-order-storage
- WooCommerce MCP: https://developer.woocommerce.com/docs/features/mcp/
- QIT: https://qit.woo.com/docs/ and https://github.com/woocommerce/qit-cli

Ecosystem sources are treated as non-authoritative evidence for prior art only, not as standards.

Scoring convention used throughout:

- Production readiness: 1 unstable, 3 usable with guardrails, 5 recommended default.
- Implementation difficulty: 1 trivial, 3 moderate, 5 complex.
- Maintenance impact: low, medium, high.

---

## 1. Executive Summary

**Position:** Build AI engineering systems around native WordPress and WooCommerce extension points, not around generic SaaS application architecture. A production agent must default to conservative plugin engineering: small bootstraps, namespaced PHP, explicit capabilities, nonces, sanitization, escaping, local assets, well-scoped hooks, backward-compatible migrations, native admin UI patterns, block metadata, WooCommerce CRUD APIs, HPOS compatibility, PCP/QIT gates, and release blocking on compatibility matrices.

**Rationale:** WordPress plugins run inside uncontrolled host applications. They coexist with themes, caching layers, security plugins, page builders, object caches, multisite, multilingual systems, inconsistent cron, slow databases, and shared hosting limits. WooCommerce adds money movement, inventory, order lifecycle, checkout state, payment gateway compliance, Store API constraints, HPOS, and Marketplace review expectations.

**Tradeoffs:** Conservative architecture is slower than unconstrained generation, but it avoids breakage, review rejection, checkout regressions, data loss, and support debt.

**Alternatives:** Full React SPA dashboards, custom frameworks, direct SQL order access, remote asset loading, and one-off code generation can work privately, but they raise marketplace, compatibility, accessibility, performance, and maintenance risk.

**Risks:** The 2026 WordPress AI/MCP/Abilities ecosystem is emerging quickly. Treat Abilities/MCP as strategic but not a reason to weaken permission, audit, rollback, or human approval requirements.

**Production readiness:** 5  
**Implementation difficulty:** 5  
**Maintenance implications:** high upfront discipline, lower long-term defect rate.

---

## 2. Recommended 2026 Technology Stack

**Default WordPress plugin stack**

| Layer | Recommended | Status | Notes |
|---|---|---:|---|
| PHP runtime | PHP 7.4 minimum if broad distribution is required; PHP 8.1+ preferred for commercial/controlled contexts | stable | Check target marketplace and user base before raising minimums. |
| WordPress baseline | Support latest minus 2 major releases when feasible; use feature detection for 6.5+ APIs | stable | Do not hard-fail older sites unless feature requires it. |
| PHP architecture | Namespaced PSR-4 via Composer, WPCS-compatible formatting, small service classes | stable | Avoid excessive containers in simple plugins. |
| Build tooling | `@wordpress/scripts` default; monitor `@wordpress/build` as emerging 2026 tooling | stable/emerging | `@wordpress/build` is promising but still friction-heavy outside Gutenberg-style monorepos. |
| UI | Native WordPress admin patterns, `@wordpress/components`, `@wordpress/data`, `@wordpress/api-fetch` | stable | Prefer native look and accessibility. |
| Blocks | `block.json`, server rendering when dynamic, block supports, SlotFill where needed | stable | Avoid custom editor frameworks. |
| Frontend interactivity | Interactivity API for WP 6.5+ blocks with fallback or hard requirement | stable but newer | Use `viewScriptModule`; gate by WP version where needed. |
| REST | `register_rest_route` with schema, sanitize callbacks, permission callbacks | stable | Never expose unauthenticated writes. |
| CLI | WP-CLI commands for migrations, diagnostics, repair, QA | stable | Essential for autonomous and support workflows. |
| QA | PHPUnit, WP test suite, Playwright, PHPCS/WPCS, PHPStan, PCP | stable | PCP is a release gate. |

**Default WooCommerce extension stack**

| Layer | Recommended | Status | Notes |
|---|---|---:|---|
| WooCommerce baseline | Declare `WC requires at least` and `WC tested up to` | stable | Keep tested matrix current. |
| Orders | WooCommerce CRUD APIs, HPOS compatibility declaration after audit | stable | Never read/write order data through `wp_posts` or `wp_postmeta`. |
| Checkout | Cart/Checkout Blocks extensions and Store API for block checkout; shortcode fallback if required | stable/newer | Test both if extension supports both flows. |
| Payments | `WC_Payment_Gateway` plus Blocks payment method integration | stable | Tokenization, redirects, webhooks, idempotency, logging required. |
| Async | Action Scheduler | stable | WooCommerce ships and relies on it. |
| Testing | QIT managed tests, local E2E, Store API tests, HPOS matrix | stable/emerging access | QIT may require Woo Marketplace/Partner features for some tests. |
| AI/MCP | WooCommerce Abilities through WordPress MCP adapter where available | emerging | Treat as integration layer, not core extension architecture. |

**Rationale:** This stack aligns with official APIs and review expectations.

**Tradeoffs:** Native components constrain visual novelty but reduce admin friction.

**Alternatives:** Vite, Tailwind, Radix, shadcn/ui, Next.js, module federation, styled-components. These are acceptable only with strong justification, local bundling, accessibility testing, and bundle budgets.

**Risks:** `@wordpress/build`, Abilities API, MCP adapters, DataViews/DataForm, and parts of collaboration workflows are fast-moving. Use progressive enhancement.

**Production readiness:** 5 for default stack; 3 for emerging AI/MCP/DataViews-heavy systems.  
**Implementation difficulty:** 4  
**Maintenance implications:** medium-high; dependency updates and matrix testing are mandatory.

---

## 3. WordPress vs WooCommerce Capability Matrix

| Capability | Generic WordPress plugin | WooCommerce extension | Agent rule |
|---|---|---|---|
| Bootstrapping | Plugin header, activation/deactivation/uninstall hooks | Same, plus Woo active/version detection | Do not load WC services until WooCommerce is available. |
| Data model | Posts, terms, users, metadata, options, custom tables if justified | Products, orders, customers, coupons, carts, subscriptions via WC APIs | Use WC CRUD for commerce entities. |
| Admin UI | Settings API, admin pages, Site Editor/editor surfaces, native packages | Woo navigation, WC settings, product/order screens, analytics patterns | Do not create branded top-level menus unless justified. |
| Frontend | Shortcodes, blocks, templates, REST, Interactivity API | Cart, checkout, account, product templates, Store API, Blocks | Preserve checkout conversion and theme compatibility. |
| Security | Capabilities, nonces, sanitize/validate/escape, REST permissions | Same plus payment/order/customer data sensitivity | Treat order/payment flows as high risk. |
| Performance | Conditional hooks/assets, caching, transients, async jobs | HPOS, Action Scheduler, low query counts, avoid cart/session bloat | Every checkout hook must be cheap. |
| Testing | PHPUnit, Playwright, PHPCS, PHPStan, PCP | Same plus QIT, HPOS, Store API, payment gateway tests | Woo changes need WC matrix. |
| Release | WordPress.org readme/assets/SVN or custom updater | Woo Marketplace metadata/QIT/changelog | Separate WP and Woo release gates. |
| AI/MCP | WordPress Abilities/MCP adapter | WooCommerce purpose-built abilities | Require auth, capability checks, audit logs, dry run for writes. |

**Rationale:** WooCommerce is not merely “WordPress with products”; it adds transactional state and stricter lifecycle constraints.

**Tradeoffs:** A single plugin can contain both layers, but its AI skill model must route decisions differently.

**Alternatives:** Treat everything as posts/meta. This is forbidden for orders under HPOS.

**Risks:** Checkout, payment, subscription, and inventory bugs cause direct business harm.

**Production readiness:** 5  
**Implementation difficulty:** 3  
**Maintenance implications:** high for WooCommerce-heavy extensions.

---

## 4. AI-Agent Skill Hierarchy

**Root skill pack**

1. `wp-context-loader`: detect plugin type, WP/WC versions, PHP target, multisite, dependencies, build tools, tests.
2. `wp-standards-enforcer`: WPCS, security, i18n, asset, readme, license, directory rules.
3. `wp-architecture-planner`: choose hook/service/block/REST/WP-CLI architecture.
4. `wp-implementation-worker`: performs scoped edits with AST and repository context.
5. `wp-security-reviewer`: nonce/capability/sanitize/escape/SQL/file/REST review.
6. `wp-performance-reviewer`: asset, query, cache, cron, autoload, object cache review.
7. `wp-compatibility-reviewer`: multisite, PHP, WP versions, plugin conflicts, admin UX, i18n.
8. `wp-qa-runner`: runs lint, unit, integration, E2E, PCP.
9. `wp-release-engineer`: builds distributable zip, changelog, readme, tags, rollback plan.

**WooCommerce overlay skill pack**

1. `wc-context-loader`: detect HPOS, Blocks checkout, Store API, subscriptions, gateway/shipping/tax/product domain.
2. `wc-architecture-planner`: choose CRUD, Store API, Blocks, Action Scheduler, gateway APIs.
3. `wc-compatibility-reviewer`: HPOS, cart/session, checkout shortcode/block, WC version matrix.
4. `wc-qa-runner`: QIT, HPOS matrix, checkout/order/payment tests.
5. `wc-release-engineer`: Woo headers, QIT reports, Marketplace-specific review artifacts.

**Rationale:** Agent specialization reduces hallucinated cross-domain assumptions.

**Tradeoffs:** More roles add orchestration overhead.

**Alternatives:** Single monolithic coding prompt. Acceptable only for tiny patches.

**Risks:** Parallel agents may conflict; assign disjoint files and one final integrator.

**Production readiness:** 5  
**Implementation difficulty:** 4  
**Maintenance implications:** medium; skill updates track official API changes.

---

## 5. Learning Roadmap

**Phase 1: Core WordPress survivability**

- Plugin loading, headers, activation/deactivation/uninstall.
- Hooks, priorities, custom hooks, filter purity.
- Options, Settings, Metadata, HTTP, REST, Cron, WP-CLI.
- Capabilities, nonces, sanitization, validation, escaping.
- WPCS, WordPress.org guidelines, PCP.

**Phase 2: Modern editor and UI**

- `block.json`, dynamic blocks, SlotFill, data stores.
- `@wordpress/components`, `@wordpress/data`, `@wordpress/api-fetch`, `@wordpress/i18n`.
- Script Modules API, Interactivity API, Block Bindings, DataViews/DataForm.
- Accessibility and keyboard behavior.

**Phase 3: WooCommerce foundation**

- Woo lifecycle, extension boot, CRUD APIs, products, orders, customers.
- HPOS, Store API, Cart/Checkout Blocks, payment gateways, shipping/taxes.
- Action Scheduler, logging, System Status, QIT.

**Phase 4: Autonomous engineering**

- Semantic repository indexing, AST-aware edits, deterministic plans.
- Review agents, QA loops, release gates, rollback.
- Abilities API/MCP with permission mapping, audit logs, dry-run writes.

**Rationale:** Agents must first master the host runtime before generating features.

**Tradeoffs:** Slower initial enablement, fewer production incidents.

**Alternatives:** Start with boilerplate generation. This creates plausible code but weak operational judgment.

**Risks:** APIs added in WP 6.5+ and 6.9+ require version detection.

**Production readiness:** 5  
**Implementation difficulty:** 3  
**Maintenance implications:** medium.

---

## 6. Architecture Decision Matrix

| Problem | Default architecture | Use when | Avoid when |
|---|---|---|---|
| Simple admin setting | Settings API or Woo settings tab | Options-only configuration | Building SPA just for settings |
| Complex admin workflow | Native WP packages with REST controller | Multi-step or data-heavy UI | UI can be handled by existing screens |
| Editor content | Block with `block.json` | User places content in editor/site editor | Dynamic site-wide behavior only |
| Frontend dynamic output | Server-rendered block/shortcode/template | Needs SEO/cache compatibility | Pure client render needed for auth-only app |
| Interactive block | Interactivity API | WP 6.5+ target or progressive enhancement | Broad legacy support without fallback |
| Background job | Action Scheduler for WC, WP-Cron for generic | Async, retry, queue | Immediate user-facing transaction |
| Persistent data | Options/meta/posts/terms | Fits WP data model | High-volume relational data |
| Custom table | dbDelta + migration + rollback | Large relational or high-write data | Convenience only |
| Woo order extension | WC CRUD + HPOS declaration | Any order data read/write | Direct postmeta queries |
| Checkout UI | Checkout Blocks extension + Store API | Modern Woo checkout | Store uses shortcode-only flow without fallback |
| AI tool exposure | Abilities API + MCP adapter | Site-authorized tool usage | Direct unaudited database/tool access |

**Rationale:** Architecture must follow WordPress extension surfaces.

**Tradeoffs:** Some native APIs are verbose; verbosity buys interoperability.

**Alternatives:** Framework-first design. High risk unless isolated and justified.

**Risks:** Over-abstraction in small plugins is as harmful as under-architecture in large ones.

**Production readiness:** 5  
**Implementation difficulty:** 3  
**Maintenance implications:** low-medium if followed.

---

## 7. WordPress Engineering Standards

**Mandatory rules**

- Prefix or namespace every symbol. Use Composer PSR-4 for non-trivial plugins.
- Keep the main plugin file thin: constants, autoload, compatibility checks, bootstrap.
- Register hooks from service classes; avoid side effects during file load.
- Use activation only for cheap setup: schedule events, create options, maybe run idempotent schema setup.
- Use deactivation only for unscheduling runtime tasks, not deleting data.
- Use uninstall hook/file for explicit data removal only.
- Create custom hooks for extensibility and document parameters.
- Use WordPress APIs over direct globals where possible.
- Use REST schemas and permission callbacks.
- Use WP-CLI for diagnostics, migrations, repair, and batch jobs.
- Use `dbDelta` cautiously for custom tables and versioned migrations.
- Use options with `autoload = no` for large or rarely used values.
- Support multisite intentionally: per-site options by default, network options only when required.
- Internationalize all user-facing strings with a stable text domain matching the plugin slug.

**Stable technologies**

- Hooks, Settings API, Options API, Metadata API, REST API, WP-CLI, WP-Cron, transients, object cache API, `block.json`, WP packages, WPCS, Plugin Check.

**Stable but newer**

- Script Modules API, Interactivity API, Block Bindings, block metadata collections from WP 6.8, DataViews/DataForm in WP 6.9.

**Experimental/emerging**

- `@wordpress/build`, Abilities API/MCP-heavy workflows, SQLite feature work, Gutenberg Phase 3/4 collaboration/multilingual surfaces.

**Rationale:** Follow core APIs to survive updates and reviews.

**Tradeoffs:** Native patterns can feel less modern than standalone app frameworks.

**Alternatives:** Laravel/Symfony-style app inside a plugin. Use only for complex commercial plugins with careful boot isolation.

**Risks:** Direct file access, unguarded REST endpoints, global assets, autoload bloat, and direct SQL are common review and production failures.

**Production readiness:** 5  
**Implementation difficulty:** 3  
**Maintenance implications:** medium; standards evolve.

---

## 8. WooCommerce Engineering Standards

**Mandatory rules**

- Check WooCommerce availability before loading Woo-specific services.
- Include Woo headers: `WC requires at least`, `WC tested up to`.
- Never access order data through `wp_posts`/`wp_postmeta`; use WooCommerce CRUD and query APIs.
- Declare HPOS compatibility only after audit and tests.
- Avoid `Automattic\WooCommerce\Internal` and `@internal` APIs; official docs warn backward compatibility is not guaranteed.
- Use Action Scheduler for async/retry workflows.
- Use `WC_Logger` for opt-in diagnostics; never log secrets or full payment payloads.
- Add settings where merchants expect them: Woo settings, product/order screens, Woo navigation, or extension page when justified.
- Support Cart/Checkout Blocks for checkout-related extensions. If shortcode checkout remains supported, test both.
- Keep checkout/cart hooks fast and deterministic.
- Use Store API extension points for block checkout data.
- Use Woo templates only where template override is part of the extension contract.
- Use product CRUD and data stores for product fields.
- Treat subscriptions, payments, taxes, shipping, inventory, and order status transitions as high-risk domains.

**Rationale:** WooCommerce stores are transactional systems; compatibility and performance failures become revenue failures.

**Tradeoffs:** Supporting both shortcode and block checkout increases test burden.

**Alternatives:** Blocks-only extension is acceptable for new products if requirements and documentation are explicit.

**Risks:** HPOS, checkout blocks, payment gateway edge cases, inventory races, and subscription renewals require domain-specific tests.

**Production readiness:** 5  
**Implementation difficulty:** 4  
**Maintenance implications:** high.

---

## 9. UI/UX Engineering Manual

**WordPress admin UI**

- Prefer existing WordPress screens and settings locations.
- Use `@wordpress/components` for controls, notices, panels, modals, tabs, forms, and buttons.
- Use `@wordpress/data` and `@wordpress/core-data` for entity state where appropriate.
- Use `@wordpress/api-fetch` for REST calls with nonce handling.
- Use `@wordpress/notices` for admin feedback.
- Respect admin density; avoid marketing-style dashboards.
- Avoid branded top-level menus unless the plugin is a major standalone workflow.
- Enqueue admin assets only on plugin screens or required editor screens.
- Keep copy concise and task-oriented.

**WooCommerce admin UI**

- Follow WooCommerce extension UX guidance: keep the merchant task central, avoid unrelated branding, do not alter core interface shapes, use responsive layouts.
- Use Woo navigation and analytics patterns when extending Woo admin.
- Place configuration in relevant WooCommerce settings sections.
- Make setup tasks clear, reversible, and resumable.

**Accessibility**

- Keyboard navigation for every control.
- Visible focus states.
- Proper labels, descriptions, ARIA only when semantic HTML is insufficient.
- Color contrast meets WCAG AA.
- Motion is optional/reduced-motion aware.
- Data tables support headers, sorting labels, pagination, and screen-reader context.

**Rationale:** Native UI reduces cognitive load and support cost.

**Tradeoffs:** Native UI gives less brand expression.

**Alternatives:** Tailwind/Radix/shadcn can be used for isolated custom interfaces, but must be bundled locally, scoped, accessible, and visually harmonious.

**Risks:** Global CSS leakage, bundle bloat, inaccessible custom controls, SPA dashboards that fail review or frustrate merchants.

**Production readiness:** 5  
**Implementation difficulty:** 3  
**Maintenance implications:** medium.

---

## 10. Gutenberg Development Handbook

**Required block standards**

- Use `block.json` as the source of truth.
- Use `apiVersion: 3` unless compatibility requires otherwise.
- Use `render.php` for dynamic content, server-side permissions, and cache-friendly output.
- Use block supports instead of custom controls where possible.
- Keep editor scripts separate from frontend view scripts.
- Use `style`, `editorStyle`, `script`, `viewScript`, and `viewScriptModule` metadata instead of manual global enqueues.
- Use SlotFill for editor extensions.
- Use data stores instead of ad hoc global state.
- Use `theme.json` and Global Styles integration where relevant.
- Use Block Bindings for dynamic attribute binding on WP 6.5+ with fallback.
- Use Interactivity API for frontend block interactions on WP 6.5+; version-gate or degrade gracefully.
- Treat DataViews/DataForm as promising for structured admin/editor data UIs, but avoid requiring them unless WP 6.9+ is acceptable.

**Rationale:** Block metadata lets WordPress manage dependencies, loading, and editor integration.

**Tradeoffs:** Backward compatibility requires guards for newer APIs.

**Alternatives:** Shortcodes remain valid for legacy flows; use blocks for editor-first UX.

**Risks:** Hydration mismatch, unscoped styles, editor-only packages on frontend, heavy bundles, missing dependency asset files.

**Production readiness:** 4-5 depending on API.  
**Implementation difficulty:** 4  
**Maintenance implications:** medium-high due to Gutenberg velocity.

---

## 11. WooCommerce Extension Handbook

**Domain patterns**

- Products: use `WC_Product` subclasses and CRUD; register custom product data carefully; test variable/grouped/downloadable products.
- Orders: use `wc_get_order`, `WC_Order`, CRUD getters/setters, status transition hooks, HPOS-safe queries.
- Checkout: use Store API and Checkout Blocks extension points; validate server-side; never trust client-provided totals.
- Cart: mutate cart through Woo APIs; avoid expensive per-item hooks; respect coupons, taxes, shipping packages.
- Payment gateways: extend `WC_Payment_Gateway`; implement Blocks integration; handle idempotency, webhooks, redirects, tokenization, refunds, failed payments, and logging.
- Shipping: use package-aware calculations; cache rates carefully; expose debug logs.
- Taxes: avoid custom tax math unless domain requires it; use Woo tax APIs.
- Subscriptions: require explicit compatibility layer and renewal tests.
- Analytics/Admin: extend official Woo admin patterns; avoid direct internal imports.

**Rationale:** WooCommerce has domain invariants that generic WP agents will miss.

**Tradeoffs:** More integration tests and fixtures.

**Alternatives:** Private store-specific snippets can be narrower, but marketplace-grade extensions need broad matrices.

**Risks:** Misusing internal APIs, assuming checkout shortcode only, ignoring HPOS, race conditions in stock/payment/order status.

**Production readiness:** 5  
**Implementation difficulty:** 5 for payments/subscriptions, 3-4 for product/admin extensions.  
**Maintenance implications:** high.

---

## 12. Security Engineering Manual

**Non-negotiable checks**

- Every admin action checks capability and nonce.
- Every REST route has a `permission_callback`.
- Every input is unslashed, sanitized, and validated by type/domain.
- Every output is escaped late by context.
- SQL uses `$wpdb->prepare`; identifiers are whitelisted.
- File operations validate path, extension, MIME, size, and capability.
- Uploads use WordPress APIs.
- AJAX uses `check_ajax_referer` plus capabilities.
- Secrets are never logged, displayed, committed, translated, or sent to AI tools.
- Webhooks verify signatures and use idempotency keys.
- AI/MCP write tools require explicit capability mapping, audit logs, dry-run where possible, and human confirmation for destructive actions.
- Do not load remote code. WordPress.org forbids executing outside code and generally forbids third-party CDNs for non-service JS/CSS.

**Rationale:** WordPress plugin review and real-world exploitation patterns converge around these controls.

**Tradeoffs:** More boilerplate and tests.

**Alternatives:** Framework security middleware can supplement but not replace WP-native checks.

**Risks:** Nonces are not authorization; capability checks are still required.

**Production readiness:** 5  
**Implementation difficulty:** 4  
**Maintenance implications:** high vigilance, lower incident risk.

---

## 13. Performance Engineering Manual

**Rules**

- Load only what is needed, where it is needed.
- Avoid global frontend/admin enqueues.
- Avoid autoloading large options.
- Avoid synchronous remote calls during page render, cart, checkout, REST writes, and admin list tables.
- Cache expensive reads with invalidation.
- Use object cache APIs and transients; assume persistent object cache may be absent.
- Batch background work with Action Scheduler/WP-Cron.
- Keep DB queries indexed and bounded.
- Avoid `SELECT *` and unbounded meta queries.
- Never scan all posts/orders/products on request.
- Support low memory and CPU throttling.
- Keep checkout hooks especially cheap.
- Run performance budgets in CI for asset size and key flows.

**Rationale:** Shared hosting and large stores punish small inefficiencies.

**Tradeoffs:** Caching and async add invalidation complexity.

**Alternatives:** Custom tables can improve performance for high-volume relational data but add migration burden.

**Risks:** Object cache inconsistency, stale transients, slow cron, HPOS vs posts-table assumptions.

**Production readiness:** 5  
**Implementation difficulty:** 4  
**Maintenance implications:** medium-high.

---

## 14. Accessibility Compliance Manual

**Required**

- Use semantic HTML first.
- Provide labels for every form field.
- Use `@wordpress/components` where possible because many primitives already follow WP accessibility expectations.
- Verify keyboard flows for modals, menus, tabs, notices, tables, and custom controls.
- Do not rely on color alone.
- Respect reduced motion.
- Ensure block editor controls expose clear labels and help text.
- Test admin and frontend with automated checks plus manual keyboard review.

**Rationale:** Accessibility is a WordPress project value and a marketplace quality requirement.

**Tradeoffs:** Custom UI requires more testing than native controls.

**Alternatives:** Headless UI libraries can help, but styling/integration remains your burden.

**Risks:** ARIA misuse, inaccessible custom selects, focus traps, invisible focus, dynamic notices not announced.

**Production readiness:** 5  
**Implementation difficulty:** 3  
**Maintenance implications:** medium.

---

## 15. Compatibility Engineering Manual

**Compatibility targets**

- PHP versions: matrix test supported minimum through latest stable.
- WordPress: latest, previous major/minor targets, and beta/RC before release.
- WooCommerce: latest, previous supported versions, beta/RC for Woo extensions.
- Database: MySQL and MariaDB behavior; avoid engine-specific assumptions.
- Multisite: activation, network activation, per-site settings, uninstall.
- Multilingual: do not concatenate translated strings incorrectly; avoid URL/language assumptions.
- Caching/CDN: avoid user-specific cached output leaks; set cache headers for REST where needed.
- Page builders/themes: scope CSS and JS; use hooks/templates responsibly.
- Security plugins: avoid suspicious file writes, eval, remote code, broad admin-post endpoints.

**Rationale:** Plugins coexist in hostile ecosystems.

**Tradeoffs:** Broader matrix increases CI time.

**Alternatives:** Declare narrow support honestly for specialized commercial plugins.

**Risks:** Silent incompatibility is worse than explicit unsupported states.

**Production readiness:** 5  
**Implementation difficulty:** 4  
**Maintenance implications:** high.

---

## 16. Testing & QA Blueprint

**Minimum generic plugin pipeline**

1. Composer validate and install.
2. NPM install/build if assets exist.
3. PHPCS with WordPress, WordPress-Extra, WordPress-Docs as appropriate.
4. PHPStan or Psalm with WordPress stubs.
5. PHPUnit with WP test suite.
6. Integration tests in `wp-env`.
7. Playwright E2E for admin/editor/frontend critical flows.
8. Accessibility checks with axe plus manual keyboard scenarios.
9. Visual regression for complex UI.
10. Plugin Check.
11. Build zip and smoke install on clean WP.

**Minimum WooCommerce overlay**

1. WooCommerce active/inactive boot tests.
2. HPOS enabled/disabled compatibility tests.
3. Store API tests for checkout/cart extensions.
4. Cart/Checkout Blocks E2E.
5. Shortcode checkout E2E if supported.
6. Product/order CRUD integration tests.
7. Payment/shipping/tax/subscription domain tests where applicable.
8. QIT managed tests where available.

**Rationale:** Static checks catch standards; E2E catches ecosystem behavior.

**Tradeoffs:** Full matrix is slower; use tiered PR/nightly/release gates.

**Alternatives:** Manual QA only is unacceptable for autonomous systems.

**Risks:** Flaky E2E, external payment sandbox instability, Docker limits on developer machines.

**Production readiness:** 5  
**Implementation difficulty:** 5  
**Maintenance implications:** high, but essential.

---

## 17. PCP Integration Blueprint

**Plugin Check requirements**

- Install Plugin Check in CI or use `WordPress/plugin-check-action`.
- Run static checks on every PR.
- Run runtime checks before release where possible. The Plugin Check GitHub repository notes WP-CLI runtime checks may require `--require=./wp-content/plugins/plugin-check/cli.php`.
- Treat security, performance, accessibility, i18n, licensing, and guideline failures as release blockers.
- Archive PCP reports as CI artifacts.

**Example commands**

```bash
wp plugin check my-plugin
wp plugin check my-plugin --require=./wp-content/plugins/plugin-check/cli.php
wp plugin check ./build/my-plugin.zip
```

**Rationale:** PCP is the closest automated proxy for WordPress.org review expectations.

**Tradeoffs:** PCP is not a complete manual review replacement.

**Alternatives:** PHPCS alone misses many plugin-directory checks.

**Risks:** False positives require documented triage, not blanket ignores.

**Production readiness:** 5  
**Implementation difficulty:** 2  
**Maintenance implications:** low-medium.

---

## 18. QIT Integration Blueprint

**QIT requirements**

- Add `woocommerce/qit-cli` as a dev dependency when available for the project.
- Authenticate with WooCommerce.com Partner Developer account if managed tests require it.
- Add `qit.json` for extension metadata and test configuration.
- Run activation, security, PHPStan, PHP compatibility, Woo API, Woo E2E, HPOS-relevant configurations, and custom E2E tests.
- Test against WooCommerce latest, minimum supported, beta/RC, and selected PHP/WP versions.
- Archive QIT reports as release artifacts.

**Example commands**

```bash
composer require woocommerce/qit-cli --dev
./vendor/bin/qit run:activation my-extension --php=8.1 --wp=latest --woo=latest
./vendor/bin/qit run:woo-api my-extension --extension_set=compatibility
./vendor/bin/qit run:e2e my-extension
```

**Rationale:** QIT is WooCommerce’s official quality testing direction and supports Woo-specific compatibility concerns.

**Tradeoffs:** Some features are Marketplace/Partner gated or evolving.

**Alternatives:** Local Playwright/WP tests remain mandatory even when QIT is unavailable.

**Risks:** Treat QIT pass as necessary but not sufficient for payment/order correctness.

**Production readiness:** 4-5 depending on access.  
**Implementation difficulty:** 3  
**Maintenance implications:** medium.

---

## 19. CI/CD & Release Engineering Blueprint

**Branch gates**

- PR: lint, static analysis, unit tests, build, basic E2E, PCP static.
- Main/nightly: full matrix, visual/a11y, multisite, Woo HPOS, QIT where available.
- Release candidate: clean install, upgrade from previous versions, rollback, zip audit, PCP runtime, QIT, readme/changelog validation.

**Release artifacts**

- Production zip without dev files.
- `readme.txt`.
- `changelog.txt` for Woo Marketplace if required.
- SBOM/dependency inventory.
- PCP report.
- QIT report for Woo extensions.
- Migration plan and rollback notes.
- Tested WP/WC/PHP matrix.

**Rationale:** Autonomous systems need deterministic gates before publishing.

**Tradeoffs:** More CI minutes.

**Alternatives:** Fast release without matrix only for internal emergency hotfixes with human approval.

**Risks:** Build artifacts accidentally include tests, `.git`, `node_modules`, source maps, secrets, or unbuilt assets.

**Production readiness:** 5  
**Implementation difficulty:** 4  
**Maintenance implications:** medium-high.

---

## 20. Repository Structure Blueprint

```text
plugin-slug/
  plugin-slug.php
  readme.txt
  changelog.txt
  composer.json
  package.json
  phpcs.xml.dist
  phpstan.neon.dist
  .wp-env.json
  .github/workflows/
  bin/
    build-zip.sh
    install-wp-tests.sh
  src/
    Plugin.php
    ServiceProvider.php
    Admin/
    REST/
    CLI/
    Domain/
    Infrastructure/
    Migrations/
    Compatibility/
  includes/
    functions.php
  assets/
    src/
    build/
  blocks/
    example-block/
      block.json
      edit.js
      render.php
      view.js
  templates/
  languages/
  tests/
    phpunit/
    integration/
    e2e/
    fixtures/
  docs/
    architecture.md
    security.md
    release.md
```

**WooCommerce additions**

```text
src/WooCommerce/
  Bootstrap.php
  HPOS/
  Checkout/
  StoreApi/
  Payments/
  Products/
  Orders/
  Shipping/
  Admin/
  Analytics/
tests/woocommerce/
qit.json
```

**Rationale:** Separate generic WP from Woo overlays.

**Tradeoffs:** More directories than tiny plugins need.

**Alternatives:** Flat structure for single-file plugins, but still follow security and standards.

**Risks:** Over-engineering small plugins; under-structuring commercial extensions.

**Production readiness:** 5  
**Implementation difficulty:** 2  
**Maintenance implications:** low.

---

## 21. Approved Libraries Matrix

| Library/tool | Approval | Conditions |
|---|---:|---|
| WordPress core APIs | default | Prefer over custom solutions. |
| WooCommerce public APIs | default for Woo | Avoid internal namespace. |
| Composer | approved | No abandoned or incompatible licenses. |
| `@wordpress/scripts` | default | Use dependency asset files. |
| `@wordpress/build` | watch/adopt selectively | Emerging as of April 2026; avoid hard dependency for broad plugins until mature docs. |
| `@wordpress/components` | default UI | Match admin UX. |
| React via WordPress packages | approved | Use WP-provided React where possible. |
| TypeScript | approved | Compile to compatible JS; maintain types. |
| Vite | conditional | Ensure WP externals, asset files, translations, script modules. |
| TailwindCSS | conditional | Scope output, purge, avoid admin style collisions. |
| CSS Modules/SCSS | approved | Build locally, scope styles. |
| Radix/headless UI | conditional | Bundle locally, test accessibility, avoid visual mismatch. |
| shadcn/ui | high caution | Not native WP; use only for isolated custom UIs. |
| Emotion/styled-components | high caution | Runtime/bundle overhead; CSS injection concerns. |
| Next.js | discouraged inside plugin | Use for external app only, not wp-admin plugin UI. |
| Module federation | high risk | Complex loading/security/review story. |
| Action Scheduler | default for Woo async | Also acceptable in generic plugins if bundled responsibly. |
| PHPStan/Psalm/Rector | approved | Configure for WP dynamic APIs. |

**Rationale:** Dependencies must not fight WordPress.

**Tradeoffs:** Native-first may limit component richness.

**Alternatives:** External apps can use modern stacks, but plugin integration must stay safe.

**Risks:** Bundle size, global styles, licensing, abandoned packages, duplicate React.

**Production readiness:** 5 for native; 2-4 for conditional tools.  
**Implementation difficulty:** 2-5  
**Maintenance implications:** increases with each dependency.

---

## 22. Forbidden Patterns & Anti-Patterns

- Missing capability checks.
- Nonce-only authorization.
- Unsanitized `$_GET`, `$_POST`, `$_REQUEST`, `$_FILES`.
- Escaping before storage as a substitute for validation.
- Unescaped output.
- Direct order SQL/postmeta access in WooCommerce.
- Using WooCommerce `Internal` namespace or `@internal` APIs without explicit risk note.
- Global asset enqueue on every admin/frontend page.
- Remote JS/CSS for non-service assets in WordPress.org plugins.
- Large React SPA dashboards for simple settings.
- Top-level branded menus for minor Woo extensions.
- Custom DB tables for convenience.
- Destructive migrations without backup/rollback.
- Unbounded queries or scans.
- Autoloaded large options.
- Direct file access without `ABSPATH` guard.
- Hardcoded table prefixes.
- Hardcoded URLs, paths, credentials, currencies, time zones, or locales.
- Modifying unrelated code.
- Silent backward compatibility breaks.
- AI tools with unaudited writes or broad database access.

**Rationale:** These are high-frequency failure modes.

**Tradeoffs:** Some forbidden patterns have rare exceptions; require documented architecture decision records.

**Alternatives:** Feature-detected, tested, documented exceptions.

**Risks:** Review rejection, security closure, data loss, merchant revenue loss.

**Production readiness:** 5  
**Implementation difficulty:** 2  
**Maintenance implications:** low.

---

## 23. AI-Agent Operational Rulebook

**Agent must always**

- Read existing architecture before editing.
- Classify the task: generic WP, Woo-specific, or mixed.
- Identify affected runtime surfaces: admin, editor, REST, frontend, checkout, cron, CLI, migration.
- Create a small plan for non-trivial work.
- Use official docs for unstable/current APIs.
- Make scoped edits only.
- Add or update tests proportional to risk.
- Run applicable checks.
- Produce a release-risk summary.

**Agent must never**

- Bypass nonce or capability checks.
- Trust input.
- Use undocumented/internal APIs silently.
- Break backward compatibility silently.
- Add unnecessary tables, dependencies, dashboards, global assets, or vendor lock-in.
- Introduce inaccessible UI.
- Leak CSS/JS globally.
- Hardcode WooCommerce assumptions.
- Depend on unstable APIs without feature detection.
- Run destructive migrations without rollback.
- Ship without PCP for WordPress.org-bound plugins.
- Ship Woo changes without HPOS and checkout compatibility review.

**Rationale:** Determinism matters more than cleverness.

**Tradeoffs:** More review steps before code lands.

**Alternatives:** Fast prototype mode can skip some gates only in disposable branches.

**Risks:** Agents can produce plausible but unsafe code; gates must be mechanical.

**Production readiness:** 5  
**Implementation difficulty:** 4  
**Maintenance implications:** medium.

---

## 24. Autonomous Review Pipeline

**Review stages**

1. Diff scope review: unrelated changes, generated files, dependency changes.
2. Standards review: WPCS, i18n, readme, headers.
3. Security review: auth, nonce, sanitize, escape, SQL, REST, files, secrets.
4. Architecture review: APIs, hooks, extensibility, DI, migrations.
5. Performance review: assets, queries, caching, async, autoload.
6. Compatibility review: PHP/WP/WC/multisite/HPOS/checkout.
7. UI/a11y review: native patterns, keyboard, contrast, responsive.
8. Tests review: risk coverage, fixtures, matrix.
9. Release review: changelog, version bump, artifacts, rollback.

**Output format**

```markdown
## Findings
- [P0] ...
- [P1] ...
- [P2] ...

## Required Fixes
- ...

## Tests Run
- ...

## Release Risk
- Low/Medium/High, with reason.
```

**Rationale:** Review agents must lead with actionable risk.

**Tradeoffs:** Longer PR cycle.

**Alternatives:** Human-only review for small changes; still use mechanical gates.

**Risks:** False confidence if reviewers do not inspect generated code.

**Production readiness:** 5  
**Implementation difficulty:** 3  
**Maintenance implications:** medium.

---

## 25. Multi-Agent Architecture Blueprint

**Recommended roles**

- Planner: decomposes task and assigns surfaces.
- Repository cartographer: builds semantic map of hooks, services, REST, blocks, tests.
- Implementer: edits scoped files.
- Security reviewer: independent adversarial review.
- Woo reviewer: HPOS/checkout/order/payment review when applicable.
- Test engineer: writes/runs tests and triages failures.
- Release engineer: artifacts, changelog, compatibility matrix.

**Coordination rules**

- One owner per write area.
- Shared architecture brief before edits.
- No agent may revert another agent’s work without integrator approval.
- Final integrator resolves conflicts and runs full gates.
- High-risk domains require human approval before release: payments, migrations, deletes, customer/order data exports, AI write tools.

**Rationale:** Specialized review catches domain failures.

**Tradeoffs:** More orchestration cost.

**Alternatives:** Single agent for small scoped fixes.

**Risks:** Conflicting edits, duplicated abstractions, shallow reviews.

**Production readiness:** 4-5  
**Implementation difficulty:** 5  
**Maintenance implications:** medium-high.

---

## 26. Plugin Boilerplate Blueprint

**Main file responsibilities**

```php
<?php
/**
 * Plugin Name: Example Plugin
 * Description: Short factual description.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Vendor
 * License: GPL-2.0-or-later
 * Text Domain: example-plugin
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'EXAMPLE_PLUGIN_FILE', __FILE__ );
define( 'EXAMPLE_PLUGIN_VERSION', '1.0.0' );

require_once __DIR__ . '/vendor/autoload.php';

add_action(
	'plugins_loaded',
	static function () {
		( new ExamplePlugin\Plugin() )->boot();
	}
);
```

**Core service contract**

- `Plugin::boot()` registers services.
- Services expose `register(): void`.
- REST controllers expose `register_routes(): void`.
- Migrations are versioned and idempotent.
- Woo services load only after Woo detection.

**Rationale:** Thin bootstrap limits load-time side effects.

**Tradeoffs:** More files than a snippet.

**Alternatives:** Single-file plugin for tiny utilities.

**Risks:** Composer autoload path missing in distributed zip.

**Production readiness:** 5  
**Implementation difficulty:** 2  
**Maintenance implications:** low.

---

## 27. Recommended File Structures

**Small generic plugin**

```text
plugin.php
includes/
  admin.php
  hooks.php
  rest.php
assets/
tests/
```

**Professional generic plugin**

Use the structure in section 20.

**WooCommerce extension**

```text
src/
  Plugin.php
  WooCommerce/
    Bootstrap.php
    Compatibility.php
    HPOS/Compatibility.php
    Products/
    Orders/
    Checkout/
    StoreApi/
    Admin/
tests/
  woocommerce/
  e2e/
qit.json
```

**Block-first plugin**

```text
blocks/
  block-a/
    block.json
    edit.js
    index.js
    render.php
    style.scss
    editor.scss
    view.js
src/
  Blocks/
assets/build/
```

**Rationale:** Structure follows risk and scale.

**Tradeoffs:** Multiple templates required.

**Alternatives:** Monorepo packages for large vendors; align with `@wordpress/build` direction cautiously.

**Risks:** Build/distribution mismatch.

**Production readiness:** 5  
**Implementation difficulty:** 2  
**Maintenance implications:** low.

---

## 28. Checklists

**Pre-implementation**

- Task classified as WP, Woo, or mixed.
- Official API checked for current/unstable surfaces.
- Minimum WP/PHP/WC versions identified.
- Data model chosen.
- Security boundary identified.
- Tests planned.

**Pre-merge**

- WPCS clean or documented.
- Static analysis clean.
- Unit/integration/E2E relevant tests pass.
- Inputs sanitized/validated.
- Outputs escaped.
- Capabilities/nonces verified.
- Assets scoped.
- i18n complete.
- Backward compatibility assessed.

**Woo pre-merge**

- HPOS safe.
- CRUD APIs used.
- Checkout Blocks impact tested.
- Store API permissions/schema reviewed.
- Order/payment/customer data protected.
- QIT/local Woo tests run.

**Pre-release**

- Version bump.
- Changelog.
- Readme stable tag/tested up to.
- Build zip audited.
- PCP report.
- QIT report if Woo.
- Upgrade and rollback tested.

**Rationale:** Checklists make agent behavior repeatable.

**Tradeoffs:** Requires discipline to keep them current.

**Alternatives:** CI-only gates miss design intent.

**Risks:** Checklist fatigue; automate where possible.

**Production readiness:** 5  
**Implementation difficulty:** 1  
**Maintenance implications:** low.

---

## 29. Review Templates

**Security review**

```markdown
## Security Review
Scope:
Data touched:
Entry points:
Capabilities:
Nonces:
Input handling:
Output escaping:
SQL/file/network operations:
Secrets/logging:
Findings:
Decision: approve / changes required
```

**Woo compatibility review**

```markdown
## WooCommerce Compatibility Review
Woo surfaces:
HPOS status:
Checkout flow: Blocks / shortcode / both
Store API changes:
Order lifecycle impact:
Payment/shipping/tax/subscription impact:
QIT/local tests:
Findings:
Decision:
```

**Release review**

```markdown
## Release Review
Version:
Supported WP/PHP/WC:
Artifacts:
PCP:
QIT:
Upgrade path:
Rollback path:
Known risks:
Decision:
```

**Rationale:** Templates encode institutional memory.

**Tradeoffs:** Reviews become formal.

**Alternatives:** Free-form review for low-risk fixes.

**Risks:** Template completion without real inspection.

**Production readiness:** 5  
**Implementation difficulty:** 1  
**Maintenance implications:** low.

---

## 30. CI Templates

**Generic GitHub Actions skeleton**

```yaml
name: CI

on:
  pull_request:
  push:
    branches: [ main ]

jobs:
  php:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [ '7.4', '8.1', '8.2', '8.3' ]
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          tools: composer
      - run: composer install --no-interaction --prefer-dist
      - run: composer run phpcs
      - run: composer run phpstan
      - run: composer run test

  assets:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: 20
          cache: npm
      - run: npm ci
      - run: npm run lint
      - run: npm run build
      - run: npm run test:e2e --if-present

  plugin-check:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: WordPress/plugin-check-action@v1
        with:
          build-dir: .
```

**Woo overlay**

```yaml
  qit:
    runs-on: ubuntu-latest
    if: github.event_name == 'pull_request'
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          tools: composer
      - run: composer install --no-interaction --prefer-dist
      - run: ./vendor/bin/qit run:activation my-extension --wp=latest --woo=latest
      - run: ./vendor/bin/qit run:woo-api my-extension --extension_set=compatibility
```

**Rationale:** CI is the enforcement layer for autonomous systems.

**Tradeoffs:** Real projects need customized install scripts and secrets.

**Alternatives:** Reusable workflows per vendor.

**Risks:** Matrix drift, credential exposure, flaky browser tests.

**Production readiness:** 4 skeleton, 5 after project customization.  
**Implementation difficulty:** 3  
**Maintenance implications:** medium.

---

## 31. Release Templates

**Changelog entry**

```markdown
= 1.2.0 - 2026-05-17 =
* Added - Feature with user-facing value.
* Changed - Backward-compatible behavior change.
* Fixed - Bug with impact.
* Security - Security hardening, no exploit details before disclosure.
* Dev - Developer-facing hook/API changes.
```

**Release notes**

```markdown
## Release 1.2.0
Compatibility: WordPress 6.5-6.9, PHP 7.4-8.3, WooCommerce 8.2-10.x
Highlights:
Upgrade notes:
Migration behavior:
Rollback:
Known limitations:
Testing:
- PCP: pass
- QIT: pass
- E2E: pass
```

**Rationale:** Releases must explain operational impact.

**Tradeoffs:** More documentation work.

**Alternatives:** Auto-generated changelog requires human review.

**Risks:** Missing upgrade notes causes support incidents.

**Production readiness:** 5  
**Implementation difficulty:** 1  
**Maintenance implications:** low.

---

## 32. Migration & Rollback Strategy

**Migration rules**

- Store schema/data version separately from plugin code version.
- Run migrations idempotently.
- Use batches for large datasets.
- Provide WP-CLI migration commands.
- Do not run long migrations during normal page requests.
- Back up or shadow critical data before destructive changes.
- Add dry-run mode for risky migrations.
- Keep old reads compatible during transition.
- Log migration progress without secrets.
- Provide rollback where data shape allows.

**Woo-specific**

- Do not migrate order storage manually around HPOS; use Woo APIs.
- For order metadata changes, read/write through order CRUD.
- Test HPOS enabled, disabled, and compatibility mode if supported.

**Rationale:** Shared hosting cannot tolerate long blocking migrations.

**Tradeoffs:** More migration code and support commands.

**Alternatives:** Manual migration for tiny private sites only.

**Risks:** Timeouts, partial migrations, data loss, HPOS sync confusion.

**Production readiness:** 5  
**Implementation difficulty:** 4  
**Maintenance implications:** high for data-heavy plugins.

---

## 33. Failure Recovery Strategy

**Runtime recovery**

- Fail closed for permissions.
- Degrade gracefully when dependencies are inactive.
- Show admin notices only to capable users and only when actionable.
- Add Site Health/debug info for complex plugins.
- Use `WC_Logger` or plugin logger with opt-in diagnostics.
- Provide WP-CLI repair commands.
- Unschedule broken recurring tasks safely.
- Avoid fatal errors on older PHP/WP by checking requirements before loading incompatible code.

**Release recovery**

- Maintain rollback package.
- Keep previous DB readers during migration window.
- Feature-flag risky changes.
- For Woo checkout/payment changes, allow disabling new flow quickly.
- Document known conflicts.

**AI recovery**

- Audit every AI/MCP write.
- Provide undo records for reversible operations.
- Require human confirmation for destructive writes.
- Rate-limit and scope AI tools.

**Rationale:** Production WordPress failures often happen in wp-admin or checkout at the worst time.

**Tradeoffs:** Feature flags and diagnostics add code.

**Alternatives:** Rely on host backups; insufficient.

**Risks:** Recovery paths untested are imaginary.

**Production readiness:** 5  
**Implementation difficulty:** 4  
**Maintenance implications:** medium-high.

---

## 34. Future-Proofing Recommendations

**Adopt now**

- `block.json`, server-rendered dynamic blocks, WP packages.
- Script Modules and Interactivity API when WP 6.5+ is acceptable.
- Block Bindings with fallback.
- HPOS compatibility.
- Cart/Checkout Blocks compatibility.
- PCP in CI.
- QIT for Woo extensions.
- WP-CLI operational commands.

**Track carefully**

- `@wordpress/build` as a likely next-generation build tool.
- DataViews/DataForm for admin data interfaces.
- Abilities API and MCP adapter for AI-agent tool exposure.
- WordPress Playground for demos, support reproduction, and preview blueprints.
- SQLite support developments, but do not assume production availability.
- Gutenberg Phase 3 collaboration and Phase 4 multilingual roadmap.

**Avoid betting the product on**

- Undocumented Gutenberg internals.
- WooCommerce internal classes.
- AI/MCP write actions without audit/rollback.
- Complex JS app architectures inside wp-admin.
- Runtime remote dependencies.

**Rationale:** Future-proofing means progressive enhancement, not chasing novelty.

**Tradeoffs:** Some features wait until the installed base catches up.

**Alternatives:** Modern-only commercial plugins can move faster with clear requirements.

**Risks:** Emerging APIs change; agents must re-check official docs.

**Production readiness:** 4  
**Implementation difficulty:** 3  
**Maintenance implications:** medium.

---

## 35. Final Strategic Recommendations

1. Build two distinct but interoperable skill packs: `wordpress-plugin-engineer` and `woocommerce-extension-engineer`.
2. Make native WordPress/WooCommerce APIs the default and require ADRs for deviations.
3. Treat WooCommerce checkout, payments, orders, subscriptions, taxes, shipping, and inventory as high-risk domains requiring specialized review.
4. Make PCP and QIT first-class release gates.
5. Require HPOS-safe code for all WooCommerce order work.
6. Prefer `@wordpress/components` and Woo admin patterns over custom SaaS dashboards.
7. Use Interactivity API, Block Bindings, Script Modules, and DataViews as progressive enhancements with version gates.
8. Keep AI/MCP as an operational interface with permissions, audit, dry-run, and rollback, not as a shortcut around WordPress security.
9. Maintain compatibility matrices and update them before every release.
10. Optimize the agent for boring correctness: scoped edits, official APIs, deterministic tests, documented risks.

**Rationale:** The winning architecture is not the most novel; it is the one that survives real WordPress sites.

**Tradeoffs:** This approach may feel conservative compared with modern full-stack app generation.

**Alternatives:** Separate SaaS control plane plus lightweight plugin bridge can be valid for enterprise products, but WordPress.org and Woo Marketplace constraints still apply to the bridge.

**Risks:** Under-investing in QA and compatibility will overwhelm any productivity gained from AI generation.

**Production readiness:** 5  
**Implementation difficulty:** 5  
**Maintenance implications:** high discipline, strong long-term payoff.

---

## Prior Art: Existing AI-Agent and Plugin-Generation Systems

**Official/emerging**

- WooCommerce AI docs now provide `llms.txt`/`llms-full.txt` and describe MCP support for AI-assisted development.
- WooCommerce MCP exposes operations through WordPress Abilities and the WordPress MCP adapter with authentication and permissions.
- WordPress MCP work has moved from Automattic’s `wordpress-mcp` toward the WordPress `mcp-adapter` direction.
- QIT docs explicitly mention AI-agent-driven QIT workflows.

**Community/commercial examples observed**

- WP-Autoplugin: AI-assisted plugin generation and fixing. Strength: practical generation workflow. Weakness: generation alone does not prove PCP/QIT/security/compatibility maturity.
- ENTGENAI: WordPress plugin with built-in skills, AgentLoop, MCP endpoint, WordPress/WooCommerce actions. Strength: integrated agent runtime. Weakness: must be audited for least privilege, rollback, and marketplace constraints.
- ClawPress and similar agent plugins: position around autonomous site operation. Strength: aligns with Abilities/MCP trend. Weakness: many features are roadmap/research; direct DB read/write abilities are high risk.
- Community WooCommerce MCP servers: useful experimentation, often read-only. Strength: safer product/catalog access. Weakness: not official unless built on current Woo/WordPress MCP direction; write support needs strong controls.
- Cursor/Windsurf/Roo/Claude prompts and rules exist in scattered repos, but most are generic WordPress snippets rather than complete production engineering systems with PCP/QIT/HPOS/release gates.

**Gap analysis**

- Most existing systems optimize for generation or site operation, not marketplace-grade plugin engineering.
- Few clearly separate generic WordPress from WooCommerce transactional domains.
- Few encode HPOS, Checkout Blocks, Store API, QIT, PCP, rollback, migration, and accessibility as mandatory gates.
- Few offer deterministic multi-agent review pipelines.

**Strategic conclusion**

The opportunity is not another prompt pack. The useful artifact is a standards-driven engineering operating system: official-doc retrieval, repository semantic map, architecture decision rules, scoped code generation, test generation, PCP/QIT release gates, and adversarial review.

---

## Skill System Maintenance & Continuous Improvement

This blueprint must be treated as a living engineering system. WordPress, Gutenberg, WooCommerce, QIT, Plugin Check, MCP, and build tooling change often enough that stale rules become production risk.

### Update Cadence

| Cadence | Required action | Owner |
|---|---|---|
| Every task | Record new failures, confusing APIs, missing rules, and successful fixes. | Implementing agent |
| Weekly | Review unresolved error notes and convert repeated problems into rules, tests, snippets, or checklists. | Maintainer agent |
| Monthly | Check official WordPress, WooCommerce, Plugin Check, and QIT documentation for changes. | Research agent |
| Every WordPress release candidate | Review Make/Core field guide, dev notes, Gutenberg changes, deprecated APIs, block editor package changes. | WP specialist |
| Every WooCommerce release candidate | Review Woo dev blog/docs, HPOS, Blocks, Store API, payment, checkout, QIT changes. | Woo specialist |
| Before each plugin release | Refresh compatibility matrix, PCP/QIT versions, known conflicts, tested-up-to values. | Release agent |
| After production incident | Add incident note, root cause, detection rule, regression test, and rollback lesson. | Incident reviewer |

**Rationale:** Skill drift is a real failure mode for autonomous agents.

**Tradeoffs:** Maintenance costs time, but stale automation is more expensive.

**Alternatives:** Update only when failures occur. This is reactive and misses API deprecations before users are affected.

**Risks:** Agents may copy old patterns unless the skill pack has explicit expiry and review rules.

**Production readiness:** 5  
**Implementation difficulty:** 3  
**Maintenance implications:** medium, recurring.

### Official-Source Refresh Procedure

When updating this skill system, the agent must:

1. Check official sources first:
   - WordPress Plugin Handbook.
   - WordPress Block Editor Handbook.
   - WordPress Common APIs and REST API docs.
   - Make/Core field guides and dev notes.
   - Make/Performance and Make/Accessibility.
   - WordPress Coding Standards.
   - Plugin Check docs and GitHub repository.
   - WooCommerce Developer Docs.
   - WooCommerce HPOS, Blocks, Store API, MCP, extension best practices.
   - QIT docs and qit-cli repository.
2. Record the exact source URL, page title, and retrieval date.
3. Classify each change:
   - `stable`: safe default.
   - `stable_newer`: safe with version guard.
   - `emerging`: pilot only.
   - `experimental`: do not use in production defaults.
   - `deprecated`: remove from defaults and add migration guidance.
   - `forbidden`: block in reviews.
4. Update the relevant section and the technology register.
5. Add or update tests/checklists/CI gates if the change affects behavior.
6. Add a short changelog entry for the skill system itself.

**Skill changelog format**

```markdown
## Skill System Changelog

### 2026-05-17
- Updated: HPOS guidance based on WooCommerce docs retrieved 2026-05-17.
- Added: Error knowledge base template for recurring checkout block failures.
- Deprecated: Direct order postmeta examples removed from agent snippets.
- Action: Add HPOS enabled/disabled matrix to Woo release gate.
```

### Error Knowledge Base

Every repository using this skill system should maintain:

```text
docs/ai/error-knowledge-base.md
docs/ai/decision-log.md
docs/ai/recovery-playbook.md
docs/ai/compatibility-notes.md
docs/ai/test-failures.md
```

For small repositories, these may be combined into `docs/ai-notes.md`.

### Encountered Error Log Template

Agents must document errors that took meaningful time to diagnose, could recur, or reveal missing standards.

````markdown
## Error: Short searchable title

Date: YYYY-MM-DD
Repository:
Plugin version/branch:
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
What failed, including exact command, browser action, user flow, or CI job.

Exact error:
```text
Paste the smallest useful error output. Redact secrets.
```

Root cause:
Explain the real cause, not just the surface error.

Fix:
Describe the code/config/test change that resolved it.

Files changed:
- path/to/file.php

Prevention:
- New test:
- New lint/static rule:
- New checklist item:
- New agent rule:

Detection query:
```bash
rg "pattern that finds this issue"
```

Related sources:
- Official doc URL:
- Issue/PR URL:

Status:
resolved | mitigated | unresolved | accepted-risk

Follow-up owner:
````

### Error Taxonomy

Classify every error with one or more tags:

- `security`: nonce, capability, escaping, sanitization, SQL, file handling.
- `wordpress-core`: hook timing, lifecycle, REST, cron, multisite, i18n.
- `gutenberg`: block metadata, editor packages, SlotFill, Interactivity API, Block Bindings.
- `woocommerce-core`: CRUD, HPOS, product/order/customer APIs.
- `woocommerce-checkout`: shortcode checkout, Cart/Checkout Blocks, Store API.
- `woocommerce-payment`: gateway, webhook, tokenization, refund, idempotency.
- `performance`: queries, assets, autoload, cache, background jobs.
- `compatibility`: PHP/WP/WC version, theme/plugin conflict, multisite, multilingual.
- `ci`: dependency install, Docker, wp-env, Playwright, GitHub Actions.
- `pcp`: Plugin Check failure.
- `qit`: QIT failure.
- `release`: build zip, readme, changelog, versioning, marketplace packaging.
- `ai-agent`: hallucinated API, wrong file edit, missing context, unsafe plan.

### Resolution Pattern Library

When a fix recurs twice, promote it from an error note into a reusable pattern:

```markdown
## Pattern: HPOS-safe order metadata update

Use when:
- Updating order metadata in a WooCommerce extension.

Do:
- Use `wc_get_order()`.
- Use `$order->update_meta_data()`.
- Call `$order->save()`.
- Test HPOS enabled and disabled.

Do not:
- Write to `wp_postmeta`.
- Query `shop_order` posts directly.

Regression tests:
- tests/woocommerce/OrderMetaTest.php
- tests/e2e/hpos-order-flow.spec.ts

Review checklist:
- HPOS declaration audited.
- CRUD used for all order access.
```

### Agent Feedback Loop

After each meaningful task, the agent should add a short completion note:

```markdown
## Task Note: YYYY-MM-DD short title

What changed:
What was hard:
Unexpected errors:
Docs consulted:
Tests run:
Rules that should be updated:
Reusable snippet/pattern:
```

Promote notes into the main skill only when they meet at least one criterion:

- The issue can affect more than one repository.
- The issue caused a CI/release failure.
- The issue is security, checkout, order, payment, migration, or data-loss related.
- The same mistake happened twice.
- Official docs changed or clarified the behavior.

### Skill Versioning Rules

Use semantic versioning for the skill system:

- Patch: wording, examples, new error notes, non-breaking checklist additions.
- Minor: new recommended workflow, new stable API guidance, new CI gate.
- Major: changed defaults, removed supported pattern, new minimum platform requirement.

Each version must include:

- Summary of changes.
- Official sources checked.
- Compatibility impact.
- Migration steps for downstream prompt/rule packs.
- New or changed tests/checklists.

### Downstream Artifact Sync

Whenever this blueprint changes, update derived artifacts in this order:

1. Agent system prompts.
2. Codex/Claude/Cursor/Windsurf/Roo rules.
3. MCP tool permission manifests.
4. Repository templates and boilerplates.
5. CI workflow templates.
6. Review templates.
7. Documentation snippets.
8. Regression tests.

Each derived artifact should include:

```yaml
source_blueprint: wp-woocommerce-ai-skill-system-blueprint-2026.md
source_version: 1.0.0
last_synced: YYYY-MM-DD
```

### Efficiency Rules for Future Agents

- Search the error knowledge base before debugging from scratch.
- Search official docs before trusting old local notes.
- Prefer adding a regression test over adding prose-only guidance.
- Convert repeated manual review comments into automated checks.
- Convert repeated CI failures into preflight commands.
- Keep snippets small, official-API-based, and version-gated.
- Delete obsolete workarounds after upstream fixes are stable and tested.
- Mark risky guidance with an expiry date or review date.

### Required Preflight for Updating Skills

Before editing this blueprint or a derived skill pack:

```bash
rg "TODO|deprecated|experimental|review by|expires" docs .github src tests
rg "direct.*postmeta|wp_postmeta|shop_order|Internal" src tests
rg "wp_enqueue_script|wp_enqueue_style|register_rest_route|check_ajax_referer|wp_verify_nonce" src
```

For WooCommerce repositories, also run or inspect:

```bash
rg "custom_order_tables|FeaturesUtil::declare_compatibility|wc_get_order|WC_Order|StoreApi|Checkout" src tests
```

### Maintenance Definition of Done

A skill update is complete only when:

- The official source basis is recorded.
- Stable vs experimental status is updated.
- Any affected checklist is updated.
- Any affected CI/review template is updated.
- Any recurring error is linked to a prevention rule.
- Downstream artifacts are marked synced or intentionally pending.
- A human-readable changelog entry exists.

---

## Autonomous Skill Pack Skeleton

```yaml
name: wordpress-woocommerce-plugin-engineer-2026
version: 1.0.0
default_sources:
  - https://developer.wordpress.org/plugins/
  - https://developer.wordpress.org/apis/
  - https://developer.wordpress.org/block-editor/
  - https://developer.woocommerce.com/docs/
  - https://qit.woo.com/docs/
skills:
  - id: classify_task
    output: generic_wp | woocommerce | mixed
  - id: load_context
    checks: [versions, dependencies, hooks, rest, blocks, tests, ci]
  - id: plan_architecture
    requires: [official_api_match, compatibility_target, data_model]
  - id: implement_scoped_change
    constraints: [no_unrelated_edits, native_apis_first, tests_required]
  - id: security_review
    gates: [capabilities, nonces, sanitize, validate, escape, sql, files, secrets]
  - id: performance_review
    gates: [assets, queries, cache, autoload, async, checkout_cost]
  - id: woocommerce_review
    gates: [hpos, crud, checkout_blocks, store_api, qit]
  - id: qa
    commands: [phpcs, phpstan, phpunit, e2e, plugin_check, qit]
  - id: release
    artifacts: [zip, readme, changelog, reports, rollback]
forbidden:
  - nonce_without_capability
  - direct_order_postmeta_access
  - remote_runtime_code
  - global_assets_without_scope
  - destructive_migration_without_rollback
  - undocumented_internal_api_without_adr
```

---

## MCP/Abilities Design Rules

- Register abilities only for bounded, documented operations.
- Map every ability to a WordPress capability and runtime permission check.
- Separate read, write, destructive, and financial operations.
- Require dry-run for migrations, batch changes, product bulk edits, and order updates.
- Require confirmation for deletes, refunds, status transitions, payment capture/void/refund, customer data export, and settings changes that alter checkout/payment behavior.
- Log actor, ability, inputs summary, affected object IDs, result, and rollback token where possible.
- Do not expose raw SQL by default.
- Do not expose secrets through resources.
- Prefer read-only catalog/content abilities before write tools.

**Production readiness:** 3-4 in 2026; strategic, but must be guarded.

---

## Stable vs Experimental Technology Register

| Technology | Classification | Agent default |
|---|---|---|
| Hooks/actions/filters | stable | Use. |
| Settings/Options/Metadata APIs | stable | Use. |
| REST API | stable | Use with schema and permissions. |
| WP-CLI | stable | Use for ops/migrations. |
| WP-Cron | stable with host caveats | Use for light scheduling; document real cron option. |
| Action Scheduler | stable for Woo | Use for queues/retries. |
| WPCS/PHPCS | stable | Required. |
| Plugin Check | stable/evolving | Required release gate. |
| `@wordpress/scripts` | stable | Default build. |
| Script Modules API | stable newer | Use with version guards. |
| Interactivity API | stable newer | Use for WP 6.5+ interactive blocks. |
| Block Bindings | stable newer | Use with fallback. |
| DataViews/DataForm | emerging/stabilizing | Use selectively for WP 6.9+ targets. |
| `@wordpress/build` | experimental/emerging | Track, pilot, do not require broadly yet. |
| WordPress Abilities API/MCP | emerging | Use for agent tools with strict controls. |
| WordPress Playground | stable enough for demos/tests | Use for previews/repro where helpful. |
| SQLite support | experimental/developing | Do not assume production. |
| Woo HPOS | stable | Required compatibility for order extensions. |
| Cart/Checkout Blocks | stable/current | Required for checkout extensions unless explicitly legacy. |
| Woo Store API | stable for block/cart/checkout contexts | Use official extension points. |
| QIT | official/evolving | Use when available. |
