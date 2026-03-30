# E2E Test Suite — Disable Comments Plugin

End-to-end test cases for the **Disable Comments** WordPress plugin. These tests are written as human-readable Markdown today and will be gradually automated with Playwright.

---

## What This Is

This directory contains browser-level end-to-end test cases that verify the Disable Comments plugin from a real user's perspective. Each test case describes a complete scenario: what to set up, what to click, and what to observe.

**Today:** Manual testers follow the Markdown test cases step by step.
**Tomorrow:** Playwright `.spec.ts` automation files will live colocated with the `.md` files in the same feature folders, sharing the same base filename.

---

## Organizational Approach

This suite uses **Hybrid: Feature Folders + Metadata Frontmatter**.

- **Feature folders** organize tests by the area of the plugin they cover (e.g. `03-rest-api/`, `07-avatar/`). This makes it easy to find all tests for a given feature and to run a subset during development.
- **YAML frontmatter** inside each `.md` file carries structured metadata (ID, priority, tags, automation status) that enables cross-cutting views — for example, "show me all smoke tests" or "show me every test not yet automated."
- A master **INDEX.md** in this directory provides a flat, searchable overview of every test case across all folders.

---

## Folder Structure

```
tests/e2e/
├── README.md                   # This file
├── INDEX.md                    # Master list of all test cases
├── TC-TEMPLATE.md              # Blank template for new test cases
├── CONTRIBUTING.md             # Guide for writing and automating tests
│
├── 01-disable-everywhere/      # Global "Remove Everywhere" mode
├── 02-disable-by-post-type/    # Selective per-post-type disabling
├── 03-rest-api/                # REST API comment restrictions
├── 04-xml-rpc/                 # XML-RPC and pingback disabling
├── 05-delete-comments/         # Bulk comment deletion workflows
├── 06-role-exclusions/         # Role-based bypass configuration
├── 07-avatar/                  # Avatar display management
├── 08-frontend-behavior/       # Frontend rendering and display effects
├── 09-allowed-comment-types/   # Comment type allowlist management
├── 10-settings-ui/             # Admin settings page and UI
├── 11-gutenberg/               # Block editor integration
└── 12-multisite/               # WordPress multisite / network scenarios
```

### Feature Folder Descriptions

| Folder | Description |
|--------|-------------|
| `01-disable-everywhere` | Tests for the global "Remove Everywhere" toggle that disables comments across the entire site at once |
| `02-disable-by-post-type` | Tests for selectively disabling comments on specific post types (posts, pages, custom post types) |
| `03-rest-api` | Tests that verify comment endpoints in the WordPress REST API are blocked or restricted as expected |
| `04-xml-rpc` | Tests that XML-RPC comment methods and pingbacks are disabled when the option is enabled |
| `05-delete-comments` | Tests for the bulk comment deletion tool — confirmation flow, deletion scope, and undo safeguards |
| `06-role-exclusions` | Tests that certain user roles can be excluded from comment restrictions, retaining full comment access |
| `07-avatar` | Tests for the avatar display option that removes or replaces user avatars site-wide |
| `08-frontend-behavior` | Tests verifying frontend rendering: comment forms hidden, counts zeroed, discussion boxes absent |
| `09-allowed-comment-types` | Tests for the comment type allowlist — controlling which comment types (comments, pingbacks, trackbacks) remain active |
| `10-settings-ui` | Tests for the admin settings page itself: form elements, save feedback, reset, permission checks |
| `11-gutenberg` | Tests for block editor integration — Discussion panel visibility and behavior when comments are disabled |
| `12-multisite` | Tests for WordPress multisite / network scenarios: network-wide settings, per-site overrides, super admin controls |

---

## Test Case ID Scheme

Every test case has a unique ID in the format `TC-NNN` (zero-padded to three digits). IDs are assigned sequentially within each feature folder's reserved range.

| Folder | ID Range |
|--------|----------|
| `01-disable-everywhere` | TC-001 – TC-019 |
| `02-disable-by-post-type` | TC-020 – TC-039 |
| `03-rest-api` | TC-040 – TC-059 |
| `04-xml-rpc` | TC-060 – TC-079 |
| `05-delete-comments` | TC-080 – TC-099 |
| `06-role-exclusions` | TC-100 – TC-119 |
| `07-avatar` | TC-120 – TC-139 |
| `08-frontend-behavior` | TC-140 – TC-159 |
| `09-allowed-comment-types` | TC-160 – TC-179 |
| `10-settings-ui` | TC-180 – TC-199 |
| `11-gutenberg` | TC-200 – TC-219 |
| `12-multisite` | TC-220 – TC-259 |

IDs are never reused. If a test case is retired, its ID is marked deprecated in INDEX.md and the slot is left empty.

---

## Frontmatter Fields

Every test case `.md` file begins with a YAML frontmatter block. The fields are:

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique test case ID, e.g. `TC-001`. Never reused. |
| `title` | string | Short imperative title describing what is being verified. |
| `feature` | string | The feature folder name (without number prefix), e.g. `disable-everywhere`. |
| `priority` | enum | `smoke`, `high`, `medium`, or `low`. See Priority Definitions below. |
| `tags` | list | Free-form tags for cross-cutting filtering, e.g. `[rest-api, permissions]`. |
| `type` | enum | `functional`, `negative`, `edge-case`, or `integration`. |
| `automation_status` | enum | `manual` (not yet automated), `in-progress` (being written), or `automated` (spec file exists and passing). |
| `automation_file` | string | Relative path to the `.spec.ts` file, e.g. `tests/e2e/01-disable-everywhere/TC-001-global-disable-enable.spec.ts`. Empty string if not automated. |
| `created` | date | ISO 8601 date the test case was first written. |
| `updated` | date | ISO 8601 date the test case was last modified. |

---

## Priority Definitions

| Priority | Meaning | Typical count | When to run |
|----------|---------|--------------|-------------|
| `smoke` | Must pass for the plugin to be usable at all. A failure here means the plugin is broken for every user. | ~10 tests | Run first, before anything else |
| `high` | Core documented feature functionality. A failure here means a key feature is broken. | ~30–40 tests | Run on every release |
| `medium` | Less common or conditional paths. Important but not blocking for most users. | ~30–40 tests | Run on major releases |
| `low` | Edge cases, negative tests, boundary conditions, environment-specific scenarios. | Remaining tests | Run periodically or when relevant code changes |

---

## How to Run Manually

1. Spin up a WordPress environment (local or staging) with the Disable Comments plugin activated.
2. Open the test case `.md` file you want to execute.
3. Complete all **Prerequisites** listed at the top of the test case.
4. Prepare any items listed in **Test Data**.
5. Follow each row in the **Steps** table — perform the action and verify the expected result before moving to the next step.
6. Compare the final state against **Expected Results**.
7. Record pass/fail alongside the TC ID and the WordPress + plugin version tested.
8. For failures, file a GitHub issue and reference the TC ID in the issue title.

---

## How to Automate

1. Create a `.spec.ts` file **in the same folder** as the `.md` file, with the same base name.
   - Example: `TC-001-global-disable-enable.md` → `TC-001-global-disable-enable.spec.ts`
2. Add the TC annotation inside your test: `test.info().annotations.push({ type: 'TC', value: 'TC-001' })`
3. Name `describe` blocks to match the feature folder name; name `test` blocks to match the TC title.
4. Update the markdown frontmatter:
   - `automation_status: automated`
   - `automation_file: tests/e2e/01-disable-everywhere/TC-001-global-disable-enable.spec.ts`
5. Update the **automation_status** column in `INDEX.md` for that row.
6. See [`CONTRIBUTING.md`](CONTRIBUTING.md) for full guidance, naming conventions, and project setup details.
