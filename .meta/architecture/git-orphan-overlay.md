# Git Architecture: Orphan Overlay

This project uses an **Orphan Overlay** strategy to manage configuration, metadata, and tool-specific directories (dot-prefixed directories) independently from the main application codebase.

## Core Concepts

1.  **Independent Histories:** The application code (in `main`, `feature/*`, etc.) and the configuration assets (in `config-assets`) live in separate branch histories.
2.  **Clean Main Branch:** The `main` branch ignores all dot-prefixed directories via `.gitignore`. This keeps the application history focused strictly on code.
3.  **Shared Source of Truth:** The `config-assets` branch is an **orphan branch** (no parent history) that tracks and versions all dot-directories.
4.  **Working Tree Overlay:** Files from `config-assets` are checked out into the local working tree. Because they are ignored by the active branch (`main`), they remain on disk as untracked files, usable by IDEs and tools without being committed to the app history.

## Branch Structure

-   `main`: Application logic, tests, and standard assets.
-   `config-assets`: (Orphan) `.agents/`, `.github/`, `.meta/`, `.history/`, `.kilo/`, `AGENTS.md`, `GEMINI.md`, `graphify-out/`.

## Developer Workflow

### Updating Shared Assets
If you need to change a GitHub Action or update documentation in `.meta/`:
1.  Switch to the asset branch: `git checkout config-assets`
2.  Make changes, stage, and commit: `git add .github/ && git commit -m "update workflow"`
3.  Switch back: `git checkout main`
4.  Sync your local disk: `./bin/sync-dots.sh`

### Synchronizing a New Branch
When creating a new feature branch or pulling updates:
```bash
./bin/sync-dots.sh
```

## Automation

The `bin/sync-dots.sh` utility automates the overlay process:
-   Checks out the latest files from `config-assets`.
-   Resets the Git index to keep them untracked in the current branch.
-   Restores the local `.gitignore` to maintain environment-specific ignores.

## Benefits
-   **Consistency:** IDE settings and CI workflows are identical across all branches.
-   **Security:** Prevents accidental leakage of local tool metadata into the public application history.
-   **Scalability:** Allows updating project-wide standards (like linting rules) across many repositories by merging/overlaying from a central source.
