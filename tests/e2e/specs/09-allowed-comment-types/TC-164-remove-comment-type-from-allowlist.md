---
id: TC-164
title: "Removing a comment type from the allowlist re-enables blocking for that type"
feature: allowed-comment-types
priority: medium
tags: [allowlist, comment-types, settings, toggle]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-164 — Removing a comment type from the allowlist re-enables blocking for that type

## Summary
Verifies that unchecking a previously allowed comment type removes it from the `allowed_comment_types` option and that type becomes blocked again by the REST API restriction (or deleted in bulk delete). This tests the allowlist toggle works in both directions.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Remove Everywhere ON, REST API disable ON
- [ ] 'pingback' is currently in the allowlist (from a previous save)
- [ ] REST GET for type=pingback currently returns 200

## Test Data

| Field | Value |
|-------|-------|
| Type to remove from allowlist | `pingback` |
| Verification | REST request for 'pingback' goes from 200 → 403 after removal |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Confirm 'pingback' is in the allowlist: Settings > Disable tab, verify 'pingback' checkbox is checked | 'pingback' checkbox is checked |
| 2 | Confirm current state: REST `GET /wp-json/wp/v2/comments?type=pingback` returns 200 | HTTP 200 response confirms 'pingback' is currently allowed |
| 3 | Navigate to Settings > Disable tab | Disable tab is displayed |
| 4 | Uncheck 'pingback' from the allowlist | 'pingback' checkbox becomes unchecked |
| 5 | Click Save Settings and wait for success notification | Settings saved; success notification is shown |
| 6 | Reload the settings page | Page reloads |
| 7 | Verify 'pingback' checkbox is now unchecked (removal persisted) | 'pingback' checkbox remains unchecked after reload |
| 8 | Attempt REST `GET /wp-json/wp/v2/comments?type=pingback` | Request is sent to the REST API |
| 9 | Verify response is now 403 (type is no longer allowed, now blocked) | HTTP 403 response is received |
| 10 | Verify `disable_comments_options.allowed_comment_types` no longer contains 'pingback' | The option array in the database does not include 'pingback' |

## Expected Results
- After unchecking and saving, 'pingback' is removed from the allowlist
- REST requests for 'pingback' now return 403 instead of 200
- Settings page reload shows 'pingback' unchecked
- No error or PHP notice triggered during save

## Negative / Edge Cases
- Removing the last type from the allowlist should result in an empty array (not null)
- If Remove Everywhere is OFF, removing from allowlist has no visible effect until comments are disabled

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[type="checkbox"][value="pingback"]` — allowlist checkbox

**Implementation hints:**
- `await page.uncheck('input[value="pingback"]')`
- After save and reload: `await expect(page.locator('input[value="pingback"]')).not.toBeChecked()`
- Then verify API: `const resp = await page.request.get('/wp-json/wp/v2/comments?type=pingback'); expect(resp.status()).toBe(403);`

## Related
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.allowed_comment_types`
- **Plugin Method:** `is_allowed_comment_type()`, `is_allowed_comment_type_request()`
