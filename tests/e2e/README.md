# Disable Comments — E2E Test Suite

Playwright end-to-end tests for the **Disable Comments** WordPress plugin. Tests run against a fully isolated WordPress instance managed by Docker Compose.

---

## Prerequisites

Install these tools before doing anything else:

| Tool | Minimum version | Install |
|------|----------------|---------|
| Node.js | 18 | [nodejs.org](https://nodejs.org) |
| Docker Desktop | 24 | [docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop) |
| Docker Compose | v2 (bundled with Docker Desktop) | included above |

---

## Quick Start

All commands run from `tests/e2e/`.

```bash
# 1. Install Node dependencies and Playwright browsers
cd tests/e2e
npm install
npx playwright install chromium

# 2. Start the Docker environment and wait for WordPress to finish setup
npm run env:up

# 3. Run the full test suite
npm test
```

That is all. Step 2 starts three containers, installs WordPress, activates the plugin, and blocks until everything is ready.

---

## NPM Scripts

| Script | What it does |
|--------|-------------|
| `npm run env:up` | Start Docker containers and wait for WordPress setup to complete |
| `npm run env:down` | Stop containers (preserves volumes / DB data) |
| `npm run env:reset` | Destroy all volumes and start from scratch — use this when the environment is broken |
| `npm run env:logs` | Stream container logs |
| `npm test` | Run all tests headlessly |
| `npm run test:headed` | Run tests with a visible browser window |
| `npm run test:ui` | Open the interactive Playwright UI |
| `npm run test:debug` | Run tests in debug / step-through mode |

---

## How the Environment Works

### Docker Compose services

Three containers are defined in `docker-compose.yml`:

| Container | Image | Purpose |
|-----------|-------|---------|
| `db` | `mariadb:10.6` | MySQL-compatible database |
| `wordpress` | `wordpress:latest` | PHP + Apache serving the WordPress site at `http://localhost:8080` |
| `wpcli` | `wordpress:cli` | WP-CLI runner; also executes the setup script on startup |

The plugin source tree (the repository root) is bind-mounted directly into the `wordpress` and `wpcli` containers at `wp-content/plugins/disable-comments`. No ZIP file is built; changes to plugin PHP files are reflected immediately without rebuilding.

### WordPress setup — `scripts/setup-wp.sh`

When the `wpcli` container starts it runs `setup-wp.sh`, which:

1. Waits for the database to accept connections
2. Installs WordPress (`wp core install`) if it is not already installed
3. Configures timezone, pretty permalinks
4. Removes default plugins (Hello Dolly, Akismet)
5. Activates the Disable Comments plugin from the bind mount
6. Writes a sentinel file `/var/www/html/.e2e-setup-complete`

### Readiness wait — `scripts/wait-for-setup.sh`

`npm run env:up` calls `docker compose up -d` and then `scripts/wait-for-setup.sh`. The wait script polls for the `.e2e-setup-complete` sentinel every 3 seconds (up to 180 s) and exits once it appears. This means the npm script blocks until WordPress is genuinely ready; Playwright never starts against a half-initialised site.

---

## How Tests Work

### Global setup — `global-setup.ts`

Runs once before the first test:

1. Authenticates as `admin` using `@wordpress/e2e-test-utils-playwright`'s `RequestUtils.setup()` + `setupRest()`. This logs in via the WordPress login form and writes session cookies to `artifacts/storage-states/admin.json`.
2. Takes a full database snapshot: `wp db export /var/www/html/e2e-backup.sql`. The snapshot is taken _after_ authentication so that the valid session tokens are included.

### Per-test DB restore — `utils/fixtures.ts`

The custom `test` export extends `@wordpress/e2e-test-utils-playwright` with an **auto-running `freshDB` fixture** that runs before every single test:

```
wp db import /var/www/html/e2e-backup.sql
```

This restores the database to the exact state captured in global setup — before any test has touched anything. Every test therefore starts from a guaranteed clean, deterministic baseline. No test can pollute the state seen by a later test.

### Global teardown — `global-teardown.ts`

Runs once after the last test:

```
wp db import /var/www/html/e2e-backup.sql
```

This restores the environment to its clean baseline after the test run completes. On the next `npm test` invocation, global setup will find a clean database and produce a valid snapshot again. Without this teardown, a second consecutive run would capture a dirty snapshot and tests would fail.

### The full lifecycle

```
npm test
  │
  ├─ global-setup
  │     ├─ authenticate admin  →  artifacts/storage-states/admin.json
  │     └─ wp db export        →  /var/www/html/e2e-backup.sql  (baseline snapshot)
  │
  ├─ [for each test]
  │     ├─ freshDB fixture:  wp db import e2e-backup.sql  (clean slate)
  │     ├─ storageState:     load admin.json              (already logged in)
  │     └─ test body runs
  │
  └─ global-teardown
        └─ wp db import e2e-backup.sql  (restore env for next run)
```

---

## Running WP-CLI Inside Tests

`utils/wp-cli.ts` exposes a `wpCli()` helper that runs any `wp` command inside the `wpcli` container synchronously:

```typescript
import { wpCli } from '../../utils/wp-cli';

// Create test data
const postId = wpCli("post create --post_title='My Post' --post_status=publish --comment_status=open --porcelain").trim();
const postUrl = wpCli(`post get ${ postId } --field=url`).trim();

// Verify DB state
const raw = wpCli('option get disable_comments_options --format=json');
const opts = JSON.parse(raw) as Record<string, unknown>;
expect(opts.remove_everywhere).toBeTruthy();
```

Use `wpCli()` for:
- **Test data setup** — creating posts, users, or options in a known state
- **DB-level verification** — asserting that the correct value was persisted

Do **not** use `wpCli()` to change plugin settings during a test. Settings changes must go through the UI so the test exercises real user behaviour (see the testing pattern below).

---

## Page Objects

Reusable UI abstractions live in `page-objects/`.

| Class | File | Description |
|-------|------|-------------|
| `SettingsPage` | `page-objects/SettingsPage.ts` | Disable Comments admin settings page |

The settings page uses custom CSS-styled radio buttons and checkboxes where the actual `<input>` elements are hidden. `SettingsPage` always clicks the associated `<label>` element instead of the input — which is why the methods are named `selectRemoveEverywhere()` rather than calling `.check()` directly.

---

## Project Structure

```
tests/e2e/
├── README.md                        # This file
├── package.json                     # npm scripts and dependencies
├── playwright.config.ts             # Playwright configuration
├── tsconfig.json                    # TypeScript configuration
├── docker-compose.yml               # Docker services definition
├── global-setup.ts                  # Auth + DB snapshot (runs once before tests)
├── global-teardown.ts               # DB restore (runs once after tests)
│
├── scripts/
│   ├── setup-wp.sh                  # WordPress installation script (runs in wpcli container)
│   ├── wait-for-setup.sh            # Polls for .e2e-setup-complete sentinel
│   ├── auto-login.php               # MU-plugin for bypassing login in test environments
│   └── tinyfilemanager.php          # Optional file manager for debugging
│
├── utils/
│   ├── fixtures.ts                  # Extended test with auto freshDB restore
│   └── wp-cli.ts                    # wpCli() helper — runs WP-CLI in Docker
│
├── page-objects/
│   └── SettingsPage.ts              # POM for the plugin settings page
│
├── artifacts/
│   └── storage-states/
│       └── admin.json               # Saved browser session (created by global-setup)
│
└── specs/                           # All test cases (see specs/README.md)
    ├── smoke.spec.ts
    ├── 01-disable-everywhere/
    │   ├── TC-001-enable-remove-everywhere.md
    │   ├── TC-001-enable-remove-everywhere.spec.ts
    │   └── ...
    └── ...
```

---

## Writing New Tests

### Required import

Always import `test` and `expect` from the local fixtures file, not directly from Playwright or the WP utils package:

```typescript
// correct
import { test, expect } from '../../utils/fixtures';

// wrong — bypasses the freshDB auto-restore
import { test, expect } from '@playwright/test';
```

### The three-phase testing pattern

Every test that modifies plugin settings **must** follow this pattern:

**Phase 1 — Verify the initial state**

Before touching any settings, confirm that the baseline is what you expect. This catches issues where a previous test leaked state or the environment is misconfigured.

```typescript
// Verify settings show the expected starting state
await settings.navigate();
await expect(settings.removeEverywhereRadio).not.toBeChecked();

// Verify the frontend behaves as expected in the starting state
await page.goto(postUrl);
await expect(page.locator('#respond')).toBeVisible(); // comment form IS present
```

**Phase 2 — Perform the action through the UI**

Change plugin settings the same way a real user would — by navigating to the settings page and clicking UI elements. Do not use `wpCli()` to change plugin settings; reserve it for data setup and DB verification.

```typescript
await settings.navigate();
await settings.selectRemoveEverywhere();
await settings.saveAndWaitForSuccess();
```

**Phase 3 — Verify the final state**

After the UI action, confirm both the settings persisted and the frontend reflects the change.

```typescript
// Settings persisted
await page.reload();
await expect(settings.removeEverywhereRadio).toBeChecked();

// Frontend changed
await page.goto(postUrl);
await expect(page.locator('#respond')).not.toBeAttached();

// DB-level verification (optional but recommended)
const raw = wpCli('option get disable_comments_options --format=json');
expect(JSON.parse(raw).remove_everywhere).toBeTruthy();
```

### Test data

Create all required posts, pages, or users via `wpCli()` at the top of each test. Do not rely on content that may or may not exist in the database:

```typescript
const postId = wpCli(
  "post create --post_title='Test Post' --post_status=publish --comment_status=open --porcelain"
).trim();
const postUrl = wpCli(`post get ${ postId } --field=url`).trim();
```

### Minimal spec skeleton

```typescript
import { test, expect } from '../../utils/fixtures';
import { wpCli } from '../../utils/wp-cli';
import { SettingsPage } from '../../page-objects/SettingsPage';

test.describe('disable-everywhere', () => {
  test('TC-XXX — descriptive title matching the .md file', async ({ page, admin }) => {
    test.info().annotations.push({ type: 'TC', value: 'TC-XXX' });

    // Setup: create test data
    const postId = wpCli("post create --post_title='TC-XXX Post' --post_status=publish --comment_status=open --porcelain").trim();
    const postUrl = wpCli(`post get ${ postId } --field=url`).trim();

    const settings = new SettingsPage(page, admin);

    // Phase 1: verify initial state
    await settings.navigate();
    await expect(settings.removeEverywhereRadio).not.toBeChecked();
    await page.goto(postUrl);
    await expect(page.locator('#respond')).toBeVisible();

    // Phase 2: perform action through UI
    await settings.navigate();
    await settings.selectRemoveEverywhere();
    await settings.saveAndWaitForSuccess();

    // Phase 3: verify final state
    await page.reload();
    await expect(settings.removeEverywhereRadio).toBeChecked();
    await page.goto(postUrl);
    await expect(page.locator('#respond')).not.toBeAttached();
  });
});
```

---

## Environment Variables

| Variable | Default | Purpose |
|----------|---------|---------|
| `WP_BASE_URL` | `http://localhost:8080` | WordPress site URL. Set in `playwright.config.ts` before any WP utils import. |
| `PW_E2E_DIR` | Resolved from `playwright.config.ts` location | Absolute path to `tests/e2e/`. Used by `wp-cli.ts` to locate `docker-compose.yml` regardless of working directory. |

---

## Resetting the Environment

| Situation | Command |
|-----------|---------|
| Tests pass but the Docker containers are stopped | `npm run env:up` |
| The DB is in an unknown state but containers are running | `npm test` (teardown will restore at the end; or `npm run env:reset` for a full clean) |
| Everything is broken / containers won't start | `npm run env:reset` |
| You want to inspect the running WordPress site | Visit `http://localhost:8080` — log in with `admin` / `password` |
| You want to inspect container logs | `npm run env:logs` |

---

## Credentials

These are only used locally inside Docker. Do not use them for anything else.

| Account | Username | Password |
|---------|----------|---------|
| WordPress admin | `admin` | `password` |
