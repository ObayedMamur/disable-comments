# tests/

This directory contains all automated and manual tests for the **Disable Comments** WordPress plugin.

---

## Directory Structure

```
tests/
├── bootstrap.php       # PHPUnit bootstrap — sets up the WordPress test environment
├── test-plugin.php     # PHPUnit test cases for plugin PHP logic
└── e2e/                # End-to-end test suite (manual + Playwright automation)
```

---

## PHPUnit Tests (Unit / Integration)

| File | Purpose |
|------|---------|
| `bootstrap.php` | Loads WordPress test scaffolding via `wp-phpunit`. Required by PHPUnit before any test runs. |
| `test-plugin.php` | Unit and integration tests that assert plugin PHP behavior: option handling, filter hooks, REST API modifications, and similar logic that can be tested without a browser. |

**Run PHPUnit tests:**
```bash
composer test
# or directly:
./vendor/bin/phpunit --configuration phpunit.xml
```

---

## E2E Tests (`tests/e2e/`)

The `e2e/` subdirectory contains browser-level end-to-end test cases that verify the plugin from a real user's perspective — clicking through the WordPress admin, visiting frontend pages, and observing behavior in a live WordPress environment.

- Today these are **written as Markdown** and executed manually by testers.
- They are organized by feature folder with YAML frontmatter metadata.
- When automation begins, **Playwright `.spec.ts` files will be colocated** with their corresponding `.md` files inside `tests/e2e/`.

See [`tests/e2e/README.md`](e2e/README.md) for full documentation of the E2E suite.

---

## How the Two Layers Relate

| Layer | Tool | Scope | When to run |
|-------|------|-------|-------------|
| Unit / Integration | PHPUnit | PHP logic, hooks, option values | On every commit / CI push |
| End-to-End | Manual / Playwright | Full browser, admin UI, frontend rendering | Before releases, on feature branches |

The two layers are complementary. PHPUnit tests confirm the plugin's internal logic is correct; E2E tests confirm that logic produces the right observable behavior for real users.

---

## Where to Find What

- Plugin logic being tested: `disable-comments.php`, `includes/`
- PHPUnit config: `phpunit.xml` (project root)
- E2E test cases: `tests/e2e/` (organized by feature)
- E2E master index: `tests/e2e/INDEX.md`
- E2E contributing guide: `tests/e2e/CONTRIBUTING.md`
