# Project-Specific Overrides Guide

Use this guide when a repository already has local conventions, stack choices, or architecture notes. Project-specific instructions may narrow the skill, but must not weaken security, WordPress.org, WooCommerce, HPOS, PCP, or QIT rules.

## When To Use

Load this guide when:

- The repository has custom architecture docs.
- The repository has local agent instructions such as `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `.cursor/rules`, `.windsurfrules`, or `.roo/rules`.
- The project uses a non-default JS stack, package manager, namespace, folder layout, or CSS strategy.
- The user provides docs from another project to merge into the skill.
- You are creating a reusable rule pack for a specific plugin family.

## Override Precedence

Follow this order:

1. Official WordPress/WooCommerce/security requirements.
2. Marketplace rules: WordPress.org and WooCommerce Marketplace.
3. This skill's non-negotiable rules.
4. Repository architecture and style docs.
5. Existing local code patterns.
6. Agent preference.

Project overrides cannot permit unsafe patterns such as missing capability checks, direct Woo order postmeta access, unescaped output, or remote runtime code.

When multiple local instruction files exist, read the most general project file first, then platform-specific adapter files. Treat contradictions as a reason to prefer the safer and more official WordPress/WooCommerce path.

## Recommended Project Docs

For larger plugins, maintain:

```text
AGENTS.md
docs/ARCHITECTURE.md
docs/DEVELOPMENT.md
docs/RELEASE.md
docs/SECURITY.md
docs/ai/error-knowledge-base.md
```

If a project already uses `notes/ARCHITECTURE.md` or `notes/DEVELOPMENT.md`, keep using the existing convention. The agent should update whichever path the repository already uses.

If the project uses overlay-managed files or a generated repository map, follow the local verification rule exactly. For example, a repository may require checking a branch object after commits or refreshing a graph index after code edits.

## Style Overrides To Capture

Record these as project facts:

- PHP namespace and Composer PSR-4 root.
- Main plugin prefix for functions, hooks, options, transients, constants, and DB tables.
- Minimum PHP, WordPress, and WooCommerce versions.
- JS package manager: npm, pnpm, yarn, or Bun.
- Build tool: `@wordpress/scripts`, webpack, Vite, Tailwind CLI, etc.
- Frontend stack: React through `@wordpress/element`, TypeScript, state library, router, icons.
- CSS scope root, such as `#plugin-slug-root`, if using Tailwind or utility CSS.
- i18n text domain and POT/JSON generation commands.
- Release zip command.
- Test commands.
- Completion verification rules, especially for generated files, overlays, and remote pushes.
- Repository-intelligence commands such as `graphify`, if the project requires them.
- Required source-map files to read before raw grep or file exploration.

## Code Documentation Rules

Prefer WordPress-compatible documentation:

- PHPDoc for public classes, methods, hooks, filters, REST routes, and complex data structures.
- JSDoc/TSDoc for public JS/TS APIs and non-obvious components/hooks.
- Include `@since` for public APIs, hooks, filters, CLI commands, REST routes, and extension points.
- Add translator comments for strings with placeholders.
- Keep architecture docs updated when module boundaries change.

Do not require heavy docblocks for every private one-line helper if the code is self-explanatory.

## Example Project Profile

Use this shape for a project-specific rule file:

```yaml
project:
  text_domain: productbay
  php_namespace: WpabProductBay
  php_root: app/
  js_root: src/
  assets_root: assets/
  package_manager: bun
  php_min: "7.4"
  wordpress_min: "6.0"
  woocommerce_min: "6.1"
  css_scope: "#productbay-root"
  i18n:
    make_pot: "bun run i18n:make-pot"
    make_json: "bun run i18n:make-json"
  build:
    dev: "bun start"
    production: "bun build"
    release: "bun run release"
```

## Agent Rule For Project Overrides

```markdown
Before editing, read the project profile and architecture docs.
Use project stack choices when they do not conflict with official WordPress/WooCommerce requirements.
Keep generated UI scoped to the documented root selector.
Use the documented namespace, prefix, text domain, build commands, i18n commands, and release command.
If project docs are stale, update them as part of the change.
```
