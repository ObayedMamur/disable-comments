# Disable Comments Constitution

## Core Principles

### I. Test-First (NON-NEGOTIABLE)
TDD is mandatory for all work on this plugin. Tests are written and reviewed before any implementation begins. The Red-Green-Refactor cycle is strictly enforced. No feature, fix, or refactor is merged without corresponding tests that were written first. Tests live in `tests/` and run via `wp-env` — never via a bare `phpunit` on the host.

### II. wp-env for All Testing
All PHP tests (unit and integration) MUST run inside `wp-env`. Direct `phpunit` invocations on the host machine are prohibited. E2E tests also run inside `wp-env`. The test environment is defined in `.wp-env.json`. This ensures tests run against a real WordPress instance and avoids host/environment divergence.

### III. Full Coverage Before openclaw
Before any openclaw work begins, every existing behaviour of the plugin MUST have a corresponding test. This includes: settings persistence, AJAX handlers, multisite behaviour, post-type filtering, comment deletion, WP-CLI commands, and network admin operations. Coverage is the gate for openclaw readiness.

### IV. WordPress-Native Conventions
All PHP follows WordPress Coding Standards (enforced via `phpcs.ruleset.xml`). Hooks/filters for extensibility. `sanitize_*` for input, `esc_*` for output. `$wpdb->prepare()` for all queries. No raw SQL. The plugin MUST remain a singleton accessed via `Disable_Comments::get_instance()`.

### V. Simplicity & Minimalism
No premature abstractions. No feature flags or backwards-compatibility shims when the code can simply be changed. No docstrings or comments on unchanged code. Error handling only at system boundaries. Three similar lines are better than a premature helper.

## Technology Stack

- **Language**: PHP 7.4+ (currently targeting 5.2.4+ minimum — raise floor as needed for openclaw)
- **Testing**: PHPUnit via `wp-env`, WP_UnitTestCase for integration tests, Playwright for E2E
- **Test runner**: `wp-env run tests phpunit` (unit/integration), Playwright CLI (E2E)
- **Build**: Grunt + Babel, `npm run build`
- **Linting**: PHPCS with WordPress Coding Standards
- **Package manager**: Composer (PHP), npm (JS)

## Development Workflow

- All new work starts with a spec in `specs/` via `/speckit.specify`
- Tests written and approved before implementation (`/speckit.implement`)
- `wp-env` must be running before any test command
- Settings: `options['disable_comments_options']` — always read via `get_option()`, never direct DB
- AJAX nonce: `disable_comments_save_settings` — verified on every handler
- Multisite: always branch on `$this->networkactive` and `$this->sitewide_settings`

## Governance

This constitution supersedes all other development practices in this repository. Amendments require updating this file and propagating changes to `CLAUDE.md`. All specs and implementations must verify compliance with these principles before merging.

**Version**: 1.0.0 | **Ratified**: 2026-03-30 | **Last Amended**: 2026-03-30
