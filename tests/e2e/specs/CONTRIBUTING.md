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

### Step 6 — Update INDEX.md

Add a row for the new test case in `INDEX.md`. Fill in the ID, title, feature, priority, type, and automation_status columns.

---

## 2. Test Case Quality Checklist

Before submitting a PR with a new or modified test case, verify:

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

### Step 2 — Add the TC annotation

Inside every `test()` block that corresponds to a TC, add:

```typescript
test('TC-001 — Global disable hides comment form on single post', async ({ page }) => {
  test.info().annotations.push({ type: 'TC', value: 'TC-001' });
  // ...
});
```

### Step 3 — Match naming conventions

- Wrap tests in a `describe` block named after the **feature folder** (without number prefix):

  ```typescript
  test.describe('disable-everywhere', () => { ... });
  ```

- Name individual `test` blocks to **match the TC title** exactly.

### Step 4 — Update the markdown frontmatter

Once the spec file exists and is passing, open the `.md` file and update:

```yaml
automation_status: automated
automation_file: "tests/e2e/specs/01-disable-everywhere/TC-001-global-disable-enable.spec.ts"
```

Set `updated` to today's date.

### Step 5 — Update INDEX.md

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

Full Playwright configuration lives at `tests/e2e/playwright.config.ts`. Key decisions when that is set up:

- **WordPress environment:** Will use `@wordpress/env` (or a compatible local Docker setup) to spin up a clean WordPress instance for each test run.
- **Page objects:** Will live in `tests/e2e/page-objects/` (e.g. `SettingsPage.ts`, `DeletePage.ts`). Page objects encapsulate selectors and common interactions so spec files stay readable.
- **Fixtures:** Will live in `tests/e2e/fixtures/`. Fixtures handle shared setup like logging in as admin, creating test posts, and resetting plugin settings between tests.
- **Spec files:** Colocated with `.md` files inside `tests/e2e/specs/` feature folders.
- **CI:** Playwright tests will run in GitHub Actions on pull requests targeting `master`, after PHPUnit tests pass.

Until the Playwright config is in place, write spec files so they can be picked up by a standard `playwright.config.ts` that sets `testDir: 'tests/e2e/specs'` and `testMatch: '**/*.spec.ts'`.

---

## 6. File Naming Conventions

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

## 7. Pull Request Checklist

When submitting a PR that adds or changes test cases:

- [ ] New `.md` files follow the naming convention and are in the correct folder
- [ ] All frontmatter fields are populated with real values (no template placeholders)
- [ ] `INDEX.md` is updated
- [ ] If a spec file was added: frontmatter `automation_status` and `automation_file` are updated
- [ ] If a spec file was added: the spec passes locally against a clean WordPress environment
- [ ] No existing TC IDs were reused or renumbered
