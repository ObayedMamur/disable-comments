# Contributing to the E2E Test Suite

This guide covers everything you need to write a new test case, automate an existing one, or maintain the suite over time.

---

## 1. Writing a New Test Case

Follow these steps every time you add a test case:

### Step 1 — Choose the right feature folder

Identify which feature folder your test belongs to. See the folder table in [`README.md`](README.md). If the test spans multiple features, pick the folder that owns the primary behavior being tested.

### Step 2 — Assign the next available ID

Each folder has a reserved ID range (e.g. `01-disable-everywhere` owns TC-001 to TC-019). Open `INDEX.md` and find the highest ID currently used in that range. Increment by one. Never skip numbers or reuse retired IDs.

### Step 3 — Copy and rename the template

```bash
cp tests/e2e/specs/TC-TEMPLATE.md tests/e2e/specs/NN-feature-folder/TC-XXX-kebab-case-title.md
```

File name rules:
- Prefix with the TC ID: `TC-001-`
- Use lowercase kebab-case for the rest of the title
- Keep it short but descriptive: `TC-001-global-disable-enable.md`
- Match the title to the `title` frontmatter field as closely as possible

### Step 4 — Fill in all frontmatter fields

Open the new file and replace every placeholder. Rules:
- `id`: The exact TC ID you assigned (e.g. `TC-042`)
- `title`: Short imperative phrase — what is being verified
- `feature`: Folder name without number prefix (e.g. `rest-api`)
- `priority`: See Priority Guidelines below — when in doubt, ask
- `tags`: At least one tag; use existing tags from INDEX.md for consistency
- `type`: `functional` unless it's explicitly testing failure states (`negative`), edge inputs (`edge-case`), or cross-system behavior (`integration`)
- `automation_status`: Always `manual` for a new hand-written test case
- `automation_file`: Leave as `""` until a spec file exists
- `created` / `updated`: Today's date in `YYYY-MM-DD` format

**Never leave a field blank** unless the field's comment in the template explicitly says it may be empty (currently only `automation_file`).

### Step 5 — Write clear, self-contained steps

Each test case must be runnable by a tester who has never seen the feature before. Write as if the tester starts from a clean state every time.

- **Prerequisites** must be complete. If the test requires an existing post, say so explicitly: title, post type, status.
- **Test Data** should list every piece of data the tester must create or use, with exact values.
- **Steps** must be atomic — one action per row, one expected result per row. Never bundle "click X, then Y, then check Z" into a single row.
- **Expected Results** are the final acceptance criteria — what must be true after all steps complete.

#### Required: verify the initial state

Every test that modifies plugin settings **must begin by verifying the initial state**. Before the tester (or Playwright) touches any setting, the steps must confirm:

1. The plugin settings are in the expected starting state (e.g. "Remove Everywhere" is NOT checked)
2. The frontend behaves as expected before the change (e.g. the comment form IS visible)

This makes it immediately obvious when a test runs in a dirty environment and prevents a false positive where a setting was already active before the test began.

Example steps table structure:

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | (Setup) Create a test Post with comments open | Post exists and URL is known |
| 2 | Verify initial settings state: navigate to settings page | "Remove Everywhere" radio is NOT selected |
| 3 | Verify initial frontend state: navigate to the test post | `#respond` comment form IS visible on the page |
| 4 | Select "Remove Everywhere" and click Save | Success notification appears |
| 5 | Reload the settings page | "Remove Everywhere" radio remains selected |
| 6 | Navigate to the test post | `#respond` is absent from the DOM |

### Step 6 — Update INDEX.md

Add a row for the new test case in `INDEX.md`. Fill in the ID, title, feature, priority, type, and automation_status columns.

---

## 2. Test Case Quality Checklist

Before submitting a PR with a new or modified test case, verify:

- [ ] Steps begin by **verifying the initial state** — settings are confirmed before any change is made, and the frontend is checked to behave as expected before the plugin action is taken
- [ ] Steps are **atomic** — each row has exactly one action and one observable expected result
- [ ] Prerequisites are **complete** — the test can be run in isolation without referring to other test cases
- [ ] Test data is **explicit** — no vague references like "some post" or "a user"; all data has exact values
- [ ] Expected results are **observable** — described in terms of what a tester can see, read, or measure in a browser or via an API response
- [ ] The `Playwright Notes` section is **present for any test that can be automated** — even if automation hasn't started yet
- [ ] Frontmatter is **fully populated** — no leftover placeholder values from the template
- [ ] File is **named correctly** — `TC-XXX-kebab-case-title.md` in the right folder
- [ ] `INDEX.md` has been **updated** with the new row

---

## 3. Automating a Test Case

When you are ready to write a Playwright spec for an existing manual test case:

### Step 1 — Create the spec file

Create the `.spec.ts` file **in the same folder** as the `.md` file, with the same base name:

```
tests/e2e/specs/01-disable-everywhere/
├── TC-001-global-disable-enable.md
└── TC-001-global-disable-enable.spec.ts   ← new file
```

### Step 2 — Use the correct imports

Always import `test` and `expect` from the fixtures utility, not from Playwright or the WP utils package directly. This activates the automatic per-test DB restore.

```typescript
import { test, expect } from '../../utils/fixtures';
import { wpCli } from '../../utils/wp-cli';
import { SettingsPage } from '../../page-objects/SettingsPage';
```

The `test` fixture from `@wordpress/e2e-test-utils-playwright` automatically provides `admin`, `editor`, `requestUtils`, `page`, and `browser` as fixture arguments — use them directly in your `test()` callback. See **Section 6** for when to use each utility for test data setup.

### Step 3 — Add the TC annotation

Inside every `test()` block that corresponds to a TC, add:

```typescript
test('TC-001 — Global disable hides comment form on single post', async ({ page, admin }) => {
  test.info().annotations.push({ type: 'TC', value: 'TC-001' });
  // ...
});
```

### Step 4 — Match naming conventions

- Wrap tests in a `describe` block named after the **feature folder** (without number prefix):

  ```typescript
  test.describe('disable-everywhere', () => { ... });
  ```

- Name individual `test` blocks to **match the TC title** exactly.

### Step 5 — Follow the three-phase pattern

Every spec must verify the initial state, perform the action through the UI, then verify the final state. See **Section 5** for the full pattern with code examples. Tests that skip the initial-state phase are considered incomplete and will not be merged.

### Step 6 — Update the markdown frontmatter

Once the spec file exists and is passing, open the `.md` file and update:

```yaml
automation_status: automated
automation_file: "[TC-001-global-disable-enable.spec.ts](TC-001-global-disable-enable.spec.ts)"
```

Use a Markdown link with just the filename as both the label and the relative path. Because the `.md` and `.spec.ts` files sit in the same folder, the relative link resolves correctly in any Markdown previewer, making the spec file directly clickable.

Set `updated` to today's date.

### Step 7 — Update INDEX.md

Change the `automation_status` column for that row from `manual` to `automated`.

---

## 4. Priority Guidelines

Use these questions to assign the correct priority:

| Question | If yes → |
|----------|---------|
| Would a site owner immediately notice the plugin is completely broken? | `smoke` |
| Is this a core documented feature that most users rely on? | `high` |
| Is this a conditional path, less-used option, or secondary feature? | `medium` |
| Is this a negative test, boundary condition, or environment-specific scenario? | `low` |

**Smoke tests** should number roughly 10 or fewer across the entire suite. They are the absolute minimum set that confirms the plugin is functional. If in doubt, prefer `high` over `smoke`.

**When to escalate:** If a `medium` or `low` test starts failing consistently in production for many users, escalate its priority in a follow-up PR.

---

## 5. Playwright Project Setup

Full infrastructure documentation is in [`tests/e2e/README.md`](../README.md). Quick reference for test authors:

- **WordPress environment:** Docker Compose with MariaDB + WordPress + WP-CLI containers. The plugin repo root is bind-mounted directly — no ZIP build required.
- **Running tests:** `npm run env:up` then `npm test` from `tests/e2e/`.
- **Page objects:** `tests/e2e/page-objects/` — e.g. `SettingsPage.ts`. Add page objects here for any admin page you interact with in more than one spec.
- **Fixtures:** `tests/e2e/utils/fixtures.ts` — exports a `test` that automatically restores the database before every test. Always import from here.
- **WP-CLI in tests:** `tests/e2e/utils/wp-cli.ts` — use `wpCli()` for DB-level verification and any operation not available via REST. Do not use it to change plugin settings or to create test content when `requestUtils` can do the same. See **Section 6** for the full decision hierarchy.
- **Spec files:** Colocated with `.md` files inside `tests/e2e/specs/` feature folders.

### The three-phase pattern for Playwright specs

Every spec that changes plugin settings **must** follow these three phases:

**Phase 1 — Verify the initial state**

Confirm that settings and the frontend are in the expected state _before_ any change is made. Never assume the environment is clean just because the DB was restored; assert it.

```typescript
// Settings are in the expected starting state
await settings.navigate();
await expect(settings.removeEverywhereRadio).not.toBeChecked();

// Frontend behaves as expected before the change
await page.goto(postUrl);
await expect(page.locator('#respond')).toBeVisible(); // comment form IS present
```

**Phase 2 — Perform the action through the UI**

Change plugin settings exactly as a real user would. Use the settings page, click buttons, and save. **Do not use `wpCli()` to change plugin settings** — reserve it for data setup and verification.

```typescript
await settings.navigate();
await settings.selectRemoveEverywhere();
await settings.saveAndWaitForSuccess();
```

**Phase 3 — Verify the final state**

After saving, confirm both that the setting persisted and that the frontend reflects the change.

```typescript
// Settings persisted after reload
await page.reload();
await expect(settings.removeEverywhereRadio).toBeChecked();

// Frontend reflects the change
await page.goto(postUrl);
await expect(page.locator('#respond')).not.toBeAttached();

// (Optional) Confirm at DB level
const raw = wpCli('option get disable_comments_options --format=json');
expect(JSON.parse(raw).remove_everywhere).toBeTruthy();
```

Tests that skip Phase 1 are incomplete — they cannot distinguish between "the feature worked correctly" and "the feature was already in that state before the test ran".

---

## 6. WordPress Test Utilities

The `@wordpress/e2e-test-utils-playwright` package is already a dev dependency and its fixtures (`requestUtils`, `admin`, `editor`) are available in every test. **Prefer these utilities over raw WP-CLI commands** for creating and managing test content — they are faster (REST, no Docker exec), return typed objects with direct URL access, and cleanly express intent.

### Decision hierarchy for test data setup

| Need | Use |
|------|-----|
| Create a post | `requestUtils.createPost()` |
| Create a page | `requestUtils.createPage()` |
| Create a comment | `requestUtils.createComment()` |
| Delete all posts | `requestUtils.deleteAllPosts( postType? )` |
| Delete all pages | `requestUtils.deleteAllPages()` |
| Delete all comments | `requestUtils.deleteAllComments( type? )` |
| Open a new post in the block editor | `admin.createNewPost( options )` |
| Open an existing post/page in the block editor | `admin.editPost( postId )` |
| DB-level read/verify, or anything not exposed by REST | `wpCli()` |

**Never use `wpCli()` to change plugin settings** — always drive settings changes through the UI (Phase 2 of the three-phase pattern) so that the test exercises the real save flow.

### Common patterns

**Creating a post:**

```typescript
const post = await requestUtils.createPost( {
    title: 'TC-001 Test Post',
    status: 'publish',
    date_gmt: new Date().toISOString(),
} );
const postUrl = post.link;
```

`CreatePostPayload` fields: `title?`, `content?`, `status` (required), `date?`, `date_gmt` (required).

**Creating a page:**

```typescript
const wpPage = await requestUtils.createPage( {
    title: 'TC-001 Test Page',
    status: 'publish',
} );
const pageUrl = wpPage.link;
```

`CreatePagePayload` fields: `title?`, `content?`, `status` (required), `date?`, `date_gmt?`.

**Creating a comment:**

```typescript
await requestUtils.createComment( {
    post: post.id,
    content: 'TC-001 test comment',
} );
```

`CreateCommentPayload` fields: `content` (required), `post` (required — post ID as number). The author is resolved automatically to the current authenticated user. Do not pass a `status` field — it is not part of the payload type.

**Cleaning up between tests:**

```typescript
await requestUtils.deleteAllPosts();              // deletes default 'posts'
await requestUtils.deleteAllPosts( 'pages' );    // or pass a custom post type slug
await requestUtils.deleteAllPages();
await requestUtils.deleteAllComments();          // deletes all 'comment' type
await requestUtils.deleteAllComments( 'ping' );  // optional: filter by comment type
```

**Opening the block editor for a new post:**

```typescript
// Only when the test needs to interact with the Gutenberg editor itself
await admin.createNewPost( { postType: 'post', title: 'Editor Test Post' } );
// Supported options: postType, title, content, excerpt, showWelcomeGuide, fullscreenMode
```

**Opening an existing post or page in the block editor:**

```typescript
// postId comes from a prior requestUtils.createPost() / createPage() call
await admin.editPost( post.id );
```

**DB-level verification** (still appropriate; use `wpCli()` here):

```typescript
const raw = wpCli( 'option get disable_comments_options --format=json' );
expect( ( JSON.parse( raw ) as Record<string, unknown> ).remove_everywhere ).toBeTruthy();
```

### Why not always use WP-CLI?

- `wpCli()` is synchronous and blocks the Node process while waiting for Docker exec
- It returns raw strings that require manual parsing (post ID, URL, etc.)
- `requestUtils.*` methods are async, return typed objects, and are idiomatic for Playwright-based WordPress testing

---

## 7. File Naming Conventions

Consistent naming makes it possible to automatically pair `.md` files with their `.spec.ts` files.

| Artifact | Convention | Example |
|----------|-----------|---------|
| Test case markdown | `TC-NNN-kebab-case-title.md` | `TC-001-global-disable-enable.md` |
| Playwright spec | `TC-NNN-kebab-case-title.spec.ts` | `TC-001-global-disable-enable.spec.ts` |
| Page object class | `PascalCasePage.ts` | `SettingsPage.ts` |
| Delete page object | `PascalCasePage.ts` | `DeletePage.ts` |
| Fixture file | `camelCase.fixture.ts` | `adminLogin.fixture.ts` |

Rules:
- The base name of `.md` and `.spec.ts` **must match exactly** (same ID, same slug).
- Never use spaces or uppercase letters in `.md` or `.spec.ts` filenames.
- Page object filenames use PascalCase to match the exported class name.

---

## 8. Pull Request Checklist

When submitting a PR that adds or changes test cases:

- [ ] New `.md` files follow the naming convention and are in the correct folder
- [ ] All frontmatter fields are populated with real values (no template placeholders)
- [ ] `INDEX.md` is updated
- [ ] If a spec file was added: it imports `test` and `expect` from `../../utils/fixtures`
- [ ] If a spec file was added: it follows the three-phase pattern (verify initial state → UI action → verify final state)
- [ ] If a spec file was added: frontmatter `automation_status` and `automation_file` are updated
- [ ] If a spec file was added: the spec passes locally against a clean WordPress environment (`npm run env:reset && npm test`)
- [ ] No existing TC IDs were reused or renumbered
