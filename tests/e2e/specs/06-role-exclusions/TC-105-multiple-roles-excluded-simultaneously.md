---
id: TC-105
title: "Multiple roles excluded simultaneously all bypass the restriction"
feature: role-exclusions
priority: medium
tags: [role-exclusions, multi-role, bypass, frontend, comment-form]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-105 — Multiple Roles Excluded Simultaneously All Bypass the Restriction

## Summary
Verifies that when multiple roles (Administrator, Editor, and Author) are all added to the exclusion list simultaneously, users belonging to any of those roles each individually see the comment form. Also confirms that a user in a non-excluded role (Subscriber) still cannot see the form.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator to configure settings
- [ ] "Remove Everywhere" disable mode is enabled and saved
- [ ] "Enable exclude by role" is enabled with Administrator, Editor, and Author all checked
- [ ] User accounts for Editor, Author, and Subscriber exist (see Test Data)
- [ ] At least one published Post exists on the frontend

## Test Data

| Field | Value |
|-------|-------|
| Settings page | `/wp-admin/admin.php?page=disable_comments_settings` |
| Disable mode | Remove Everywhere |
| Excluded roles | `administrator`, `editor`, `author` |
| Non-excluded role | `subscriber` |
| Editor username | `test_editor` |
| Editor password | `EditorPass123!` |
| Author username | `test_author` |
| Author password | `AuthorPass123!` |
| Subscriber username | `test_subscriber` |
| Subscriber password | `SubscriberPass123!` |
| Test post URL | Any published Post, e.g. `/?p=1` |
| Comment form element | `#respond` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | As Administrator, confirm settings: Remove Everywhere ON, exclude-by-role ON, Administrator + Editor + Author checked; save | All three roles are in the exclusion list; settings saved |
| 2 | Open a browser context logged in as Administrator and navigate to the test post | Post loads with admin session |
| 3 | Verify `#respond` is visible for Administrator | Admin (excluded) sees the comment form |
| 4 | Open a second browser context logged in as `test_editor` and navigate to the same post | Post loads with Editor session |
| 5 | Verify `#respond` is visible for the Editor user | Editor (excluded) sees the comment form |
| 6 | Open a third browser context logged in as `test_author` and navigate to the same post | Post loads with Author session |
| 7 | Verify `#respond` is visible for the Author user | Author (excluded) sees the comment form |
| 8 | Open a fourth browser context logged in as `test_subscriber` and navigate to the same post | Post loads with Subscriber session |
| 9 | Verify `#respond` is NOT visible or absent for the Subscriber user | Subscriber (not excluded) does NOT see the comment form |

## Expected Results
- All three excluded roles (Administrator, Editor, Author) independently and simultaneously bypass the comment restriction.
- The Subscriber role (not in the exclusion list) still cannot see the comment form.
- This confirms the `exclude_by_role` array is checked as a set membership test, not an ordered list, and multiple values work correctly.

## Negative / Edge Cases
- If only some of the roles are saved (e.g., a form submission error), any unsaved role should not receive the bypass — verify all three are truly saved before testing.
- A user who holds multiple roles (e.g., both Editor and Subscriber) should be excluded if ANY of their roles appear in the exclusion list.

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings` (setup), then any post frontend URL

**Key Selectors:**
- `#respond` — comment form wrapper
- `[name="disable_comments_options[exclude_by_role][]"][value="administrator"]` — Administrator role checkbox
- `[name="disable_comments_options[exclude_by_role][]"][value="editor"]` — Editor role checkbox
- `[name="disable_comments_options[exclude_by_role][]"][value="author"]` — Author role checkbox
- `[name="disable_comments_options[exclude_by_role][]"][value="subscriber"]` — Subscriber role checkbox

**Implementation hints:**
- Use `browser.newContext()` four times (or use `storageState` files) to create independent sessions for Admin, Editor, Author, and Subscriber.
- Run the four frontend checks in parallel using `Promise.all()` to speed up the test.
- Create test users (`test_editor`, `test_author`, `test_subscriber`) in a `beforeAll` hook via the WP REST API or WP-CLI.
- Use `expect(page.locator('#respond')).toBeVisible()` for excluded roles and `expect(page.locator('#respond')).toHaveCount(0)` for the Subscriber.
- After the test, clean up any created test users in an `afterAll` hook.

## Related
- **WordPress Filters:** `comments_open`, `get_comments_number`
- **Plugin Method:** `is_exclude_by_role()`, `filter_comment_status()`, `filter_existing_comments()`
- **Plugin Option Key:** `disable_comments_options.enable_exclude_by_role`, `disable_comments_options.exclude_by_role`
- **Related TC:** TC-100, TC-101, TC-102, TC-103, TC-104
