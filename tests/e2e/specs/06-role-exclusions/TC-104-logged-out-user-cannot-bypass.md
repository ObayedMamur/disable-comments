---
id: TC-104
title: "Logged-out users cannot bypass comment restriction via role exclusion"
feature: role-exclusions
priority: high
tags: [role-exclusions, anonymous, logged-out, negative, security, frontend]
type: negative
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-104 — Logged-Out Users Cannot Bypass Comment Restriction via Role Exclusion

## Summary
Verifies that anonymous (logged-out) visitors cannot see the comment form even when all WordPress roles are added to the exclusion list. Since logged-out users have no role, `is_exclude_by_role()` finds no intersection and the restriction applies. This is a security-focused negative test.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] "Remove Everywhere" is enabled and saved
- [ ] "Enable exclude by role" is enabled
- [ ] ALL roles are checked in the exclusion list (Administrator, Editor, Author, Contributor, Subscriber)
- [ ] At least one published Post exists on the frontend

## Test Data

| Field | Value |
|-------|-------|
| Settings page | `/wp-admin/admin.php?page=disable_comments_settings` |
| Disable mode | Remove Everywhere |
| Excluded roles | All: `administrator`, `editor`, `author`, `contributor`, `subscriber` |
| Test post URL | Any published Post, e.g. `/?p=1` |
| Comment form element | `#respond` |
| Session type | Anonymous (no cookies, not logged in) |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | As Administrator, navigate to settings and enable Remove Everywhere; save | Comments disabled site-wide |
| 2 | Enable "Enable exclude by role" and check ALL five roles (Administrator, Editor, Author, Contributor, Subscriber); save | All roles are in the exclusion list; settings saved |
| 3 | Open a new browser context with no cookies (anonymous/incognito) | Fresh context with no WordPress session |
| 4 | Navigate to a published Post URL (e.g. `/?p=1`) | Post page loads without any authenticated session |
| 5 | Verify that `#respond` is NOT present or NOT visible | Comment form is absent for the anonymous visitor |
| 6 | Verify no comment input fields (`#comment`, `#author`, `#email`) are accessible | No form inputs are rendered |
| 7 | Check the page source for any comment-form-related markup | `#respond` block is not rendered in the HTML response |
| 8 | As a control, log in as Administrator in a separate context and visit the same post | Admin (excluded role) sees `#respond` — confirming the settings are active and exclusion works for authenticated users |

## Expected Results
- Logged-out users with no role do NOT see the comment form, even when all roles are in the exclusion list.
- `wp_get_current_user()->roles` returns an empty array for anonymous users, so the intersection with `exclude_by_role` is always empty.
- The Administrator in a separate logged-in context DOES see the form, confirming exclusions work for authenticated users.
- This test confirms there is no way for a logged-out user to exploit the role exclusion feature to bypass restrictions.

## Negative / Edge Cases
- Even with `exclude_by_role = ['administrator','editor','author','contributor','subscriber']`, the logged-out case always returns false from `is_exclude_by_role()`.
- Cookies from a previous logged-in session that have expired or been cleared should be treated as logged-out.

## Playwright Notes
**Page URL:** Any published post frontend URL, e.g. `/?p=1`

**Key Selectors:**
- `#respond` — comment form wrapper (must be absent for anonymous users)
- `#commentform` — inner form (must be absent)
- `#comment` — comment textarea (must be absent)

**Implementation hints:**
- Use `browser.newContext()` with no `storageState` to guarantee a clean, unauthenticated session.
- Use `expect(anonPage.locator('#respond')).toHaveCount(0)` to assert the form is fully absent from the DOM.
- Optionally use `page.content()` to inspect the raw HTML and assert it does not contain `id="respond"`.
- The admin control check should use a pre-authenticated context (e.g. set via `storageState` from a prior login).

## Related
- **WordPress Filters:** `comments_open`, `get_comments_number`
- **Plugin Method:** `is_exclude_by_role()` — returns false when `wp_get_current_user()->roles` is empty
- **Plugin Option Key:** `disable_comments_options.enable_exclude_by_role`, `disable_comments_options.exclude_by_role`
- **Related TC:** TC-101, TC-103, TC-105
