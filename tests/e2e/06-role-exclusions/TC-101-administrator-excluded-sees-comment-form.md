---
id: TC-101
title: "Administrator role excluded — comment form visible when comments globally disabled"
feature: role-exclusions
priority: high
tags: [role-exclusions, administrator, bypass, frontend, comment-form]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-101 — Administrator Role Excluded — Comment Form Visible When Comments Globally Disabled

## Summary
When "Administrator" is added to the excluded roles list and "Remove Everywhere" is active, an Administrator user must still see the comment form on frontend posts. This validates the core bypass functionality of the role exclusion feature.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator to configure settings
- [ ] "Remove Everywhere" disable mode is enabled and saved
- [ ] "Enable exclude by role" is enabled and "Administrator" is checked in the exclusion list
- [ ] At least one published Post exists with comments open (before the disable was applied)

## Test Data

| Field | Value |
|-------|-------|
| Settings page | `/wp-admin/admin.php?page=disable_comments_settings` |
| Disable mode | Remove Everywhere (all post types) |
| Excluded role | `administrator` |
| Test URL | Any published Post URL, e.g. `/?p=1` or `/sample-page/` |
| Comment form element | `#respond` |
| Admin username | `admin` (or site-specific) |
| Admin password | (site-specific) |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads |
| 2 | Enable "Remove Everywhere" and save | All comment forms are disabled site-wide for non-excluded users |
| 3 | Enable "Enable exclude by role", check "Administrator", and save | `exclude_by_role` includes `administrator`; settings saved successfully |
| 4 | Open a new browser context logged in as the Administrator user | Admin session is established (cookies set) |
| 5 | Navigate to a published Post on the frontend (e.g. `/?p=1`) | Post page loads |
| 6 | Verify that the element `#respond` (comment form) is visible on the page | `#respond` is present in the DOM and visible |
| 7 | Log out of the admin session | User is now logged out (anonymous) |
| 8 | Navigate to the same Post URL | Post page loads |
| 9 | Verify that `#respond` is NOT visible or present for the logged-out user | Comment form is hidden/absent for anonymous visitors |

## Expected Results
- The Administrator user (excluded role) can see the `#respond` comment form even though "Remove Everywhere" is active.
- A logged-out user visiting the same post does NOT see the comment form.
- This confirms `is_exclude_by_role()` correctly detects the Administrator's role and bypasses the `filter_comment_status()` and `filter_existing_comments()` hooks.

## Negative / Edge Cases
- If the Administrator is NOT in the exclusion list, they should also not see the form (same as any other user).
- Super admin on multisite may always bypass restrictions regardless of this setting.

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings` (setup), then any post frontend URL

**Key Selectors:**
- `#respond` — the comment form wrapper
- `#commentform` — the actual `<form>` inside `#respond`
- `#loginform` — WordPress login form on `/wp-login.php`
- `#user_login`, `#user_pass`, `#wp-submit` — login form fields

**Implementation hints:**
- Use `page.goto('/wp-login.php')` and fill credentials to establish the admin session before checking the frontend.
- Use `expect(page.locator('#respond')).toBeVisible()` for the admin check.
- Use `expect(page.locator('#respond')).toHaveCount(0)` or `not.toBeVisible()` for the logged-out check.
- Consider using `browser.newContext()` to isolate admin session from anonymous session within the same test.
- Store the post URL in a variable after confirming the post exists in setup.

## Related
- **WordPress Filters:** `comments_open`, `get_comments_number`
- **Plugin Method:** `is_exclude_by_role()`, `filter_comment_status()`, `filter_existing_comments()`
- **Plugin Option Key:** `disable_comments_options.enable_exclude_by_role`, `disable_comments_options.exclude_by_role`
- **Related TC:** TC-100, TC-103, TC-104
