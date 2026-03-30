---
id: TC-102
title: "Editor role excluded — comment form visible when comments globally disabled"
feature: role-exclusions
priority: medium
tags: [role-exclusions, editor, bypass, frontend, comment-form, multi-user]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-102 — Editor Role Excluded — Comment Form Visible When Comments Globally Disabled

## Summary
When the "Editor" role is added to the excluded roles list and "Remove Everywhere" is active, an Editor user must still see the comment form on frontend posts. This mirrors TC-101 but validates the exclusion works for a non-Administrator role, confirming that `is_exclude_by_role()` handles arbitrary role slugs correctly.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator to configure settings
- [ ] "Remove Everywhere" disable mode is enabled and saved
- [ ] "Enable exclude by role" is enabled and "Editor" is checked in the exclusion list
- [ ] An Editor user account exists (see Test Data)
- [ ] At least one published Post exists on the frontend

## Test Data

| Field | Value |
|-------|-------|
| Settings page | `/wp-admin/admin.php?page=disable_comments_settings` |
| Disable mode | Remove Everywhere (all post types) |
| Excluded role | `editor` |
| Editor username | `test_editor` |
| Editor password | `EditorPass123!` |
| Editor email | `test_editor@example.com` |
| Test post URL | Any published Post, e.g. `/?p=1` |
| Comment form element | `#respond` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | As Administrator, navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads |
| 2 | Confirm "Remove Everywhere" is enabled; enable "Enable exclude by role" and check "Editor"; save | Settings saved; Editor is in the exclusion list |
| 3 | Ensure the Editor user account (`test_editor`) exists — create via WP Admin > Users if needed | Editor user is present in the system with role `editor` |
| 4 | Open a new browser context (`browser.newContext()`) and navigate to `/wp-login.php` | Login page loads in an isolated context |
| 5 | Log in as `test_editor` with the Editor credentials | Login succeeds; redirected to WP Admin dashboard or frontend |
| 6 | Navigate to a published Post URL (e.g. `/?p=1`) | Post page loads in the Editor's session |
| 7 | Verify that `#respond` (comment form) is visible on the page | Comment form is present and visible for the Editor user |
| 8 | Switch to the admin browser context and verify the same post's `#respond` is also visible | Admin (also excluded) still sees the form |
| 9 | Open a third anonymous browser context, navigate to the same post, and verify `#respond` is absent | Anonymous users cannot see the comment form |

## Expected Results
- The Editor user (excluded role) can see the `#respond` comment form even when "Remove Everywhere" is active.
- An anonymous (logged-out) user visiting the same post does NOT see the comment form.
- The exclusion works consistently for roles other than Administrator.

## Negative / Edge Cases
- If the Editor role is removed from the exclusion list and settings are saved, the Editor should no longer see the form on a subsequent page load.
- A user with only the `subscriber` role should not be affected by the Editor exclusion.

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings` (setup), then any post frontend URL

**Key Selectors:**
- `#respond` — comment form wrapper
- `#commentform` — form element inside `#respond`
- `#user_login`, `#user_pass`, `#wp-submit` — login form fields on `/wp-login.php`

**Implementation hints:**
- Use `browser.newContext()` to create an isolated browser context for the Editor session so cookies do not bleed into the admin or anonymous sessions.
- Log in within the new context using `context.newPage()` followed by filling the login form.
- Use `expect(editorPage.locator('#respond')).toBeVisible()` for the Editor assertion.
- Use `expect(anonPage.locator('#respond')).toHaveCount(0)` or `.not.toBeVisible()` for the anonymous check.
- Clean up: close the extra browser contexts after the test.
- If creating the Editor user via the test, prefer using the WP REST API or WP-CLI in a `beforeAll` hook to avoid manual UI steps in every test run.

## Related
- **WordPress Filters:** `comments_open`, `get_comments_number`
- **Plugin Method:** `is_exclude_by_role()`, `filter_comment_status()`, `filter_existing_comments()`
- **Plugin Option Key:** `disable_comments_options.enable_exclude_by_role`, `disable_comments_options.exclude_by_role`
- **Related TC:** TC-100, TC-101, TC-103, TC-105
