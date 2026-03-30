---
id: TC-103
title: "Subscriber role NOT excluded — cannot see comment form when globally disabled"
feature: role-exclusions
priority: high
tags: [role-exclusions, subscriber, negative, frontend, comment-form]
type: negative
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-103 — Subscriber Role NOT Excluded — Cannot See Comment Form When Globally Disabled

## Summary
Verifies that a Subscriber user who is NOT in the exclusion list does NOT see the comment form when "Remove Everywhere" is active. Being logged in does not grant a bypass — only users whose role appears in `exclude_by_role` are exempted.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator to configure settings
- [ ] "Remove Everywhere" disable mode is enabled and saved
- [ ] "Enable exclude by role" is enabled; only "Administrator" is checked (Subscriber is NOT excluded)
- [ ] A Subscriber user account exists (see Test Data)
- [ ] At least one published Post exists on the frontend

## Test Data

| Field | Value |
|-------|-------|
| Settings page | `/wp-admin/admin.php?page=disable_comments_settings` |
| Disable mode | Remove Everywhere |
| Excluded roles | `administrator` only |
| Subscriber username | `test_subscriber` |
| Subscriber password | `SubscriberPass123!` |
| Subscriber email | `test_subscriber@example.com` |
| Test post URL | Any published Post, e.g. `/?p=1` |
| Comment form element | `#respond` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | As Administrator, verify settings: Remove Everywhere ON, role exclusions ON, only Administrator checked; save | Settings confirmed |
| 2 | Open a new browser context and navigate to `/wp-login.php` | Login page loads |
| 3 | Log in as `test_subscriber` with Subscriber credentials | Login succeeds; user is redirected (Subscriber has no admin dashboard access, may redirect to home) |
| 4 | Navigate to a published Post URL (e.g. `/?p=1`) | Post page loads in the Subscriber's session |
| 5 | Verify that `#respond` is NOT present or NOT visible | Comment form is absent for the Subscriber user |
| 6 | Verify there is no comment form fallback message that reveals the form contents | No comment input fields are accessible to the Subscriber |
| 7 | Open a separate admin browser context and navigate to the same Post URL | Admin session loads the post |
| 8 | Verify that `#respond` IS visible for the Administrator | Comment form is visible only for the excluded Administrator |

## Expected Results
- The Subscriber user (not in exclusion list) does NOT see `#respond` despite being logged in.
- The Administrator (in exclusion list) DOES see `#respond` on the same post.
- Being authenticated/logged in alone does not bypass the comment restriction — the role must be in the exclusion list.

## Negative / Edge Cases
- If the Subscriber role is added to the exclusion list and settings are saved, the Subscriber should then see the form — this is the inverse positive case.
- A Subscriber who has been promoted to Editor should follow Editor exclusion rules after the role change.

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings` (setup), then any post frontend URL

**Key Selectors:**
- `#respond` — comment form wrapper (should be absent)
- `#user_login`, `#user_pass`, `#wp-submit` — login form on `/wp-login.php`

**Implementation hints:**
- Use `expect(subscriberPage.locator('#respond')).toHaveCount(0)` to assert the form is completely absent from the DOM.
- Alternatively use `expect(subscriberPage.locator('#respond')).not.toBeVisible()` if the element is hidden rather than removed.
- Use `browser.newContext()` to keep the Subscriber session isolated from the Admin session.
- In the Playwright `beforeAll`, create the Subscriber user via the WP REST API (`POST /wp-json/wp/v2/users`) to avoid manual setup.

## Related
- **WordPress Filters:** `comments_open`, `get_comments_number`
- **Plugin Method:** `is_exclude_by_role()`, `filter_comment_status()`
- **Plugin Option Key:** `disable_comments_options.enable_exclude_by_role`, `disable_comments_options.exclude_by_role`
- **Related TC:** TC-101, TC-104, TC-105
