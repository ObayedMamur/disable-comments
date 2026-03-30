---
id: TC-161
title: "Add a comment type to the allowlist and verify it persists after save"
feature: allowed-comment-types
priority: high
tags: [allowlist, comment-types, settings, persistence]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-161 — Add a comment type to the allowlist and verify it persists after save

## Summary
Verifies that selecting a comment type (e.g. 'pingback') from the allowlist section and saving persists the choice. After reload, the selected type must still be checked. The `allowed_comment_types` option should contain the selected type.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one comment type besides 'comment' exists in the DB (e.g. 'pingback')

## Test Data

| Field | Value |
|-------|-------|
| Comment type to allow | `pingback` |
| Option key | `disable_comments_options` |
| Array field | `allowed_comment_types` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to Settings > Disable Comments tab | Disable tab is displayed |
| 2 | Find the "Allowed Comment Types" section | Allowlist section is visible with checkboxes |
| 3 | Verify the 'pingback' checkbox is currently unchecked (or note its current state) | Initial state of 'pingback' checkbox is noted |
| 4 | Check the 'pingback' checkbox | 'pingback' checkbox becomes checked |
| 5 | Click "Save Settings" button | AJAX request is sent to save settings |
| 6 | Wait for the AJAX save response and success notification | Success notification appears confirming settings were saved |
| 7 | Reload the page (`F5` or `page.reload()`) | Page reloads |
| 8 | Navigate back to Disable Comments tab (if needed) | Disable tab is active |
| 9 | Verify the 'pingback' checkbox is still checked | 'pingback' checkbox remains checked after reload |
| 10 | Optionally verify via WP Options: `disable_comments_options.allowed_comment_types` contains 'pingback' | Option value in the database includes 'pingback' in the array |

## Expected Results
- After save + reload, the 'pingback' checkbox remains checked
- The `allowed_comment_types` array in the plugin options includes 'pingback'
- Other checkboxes are not affected by the save

## Negative / Edge Cases
- Unchecking all types and saving should result in an empty `allowed_comment_types` array
- Checking multiple types and saving should persist all of them

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[type="checkbox"][value="pingback"]` — pingback allowlist checkbox
- `#dc-save-settings` or `[type="submit"]` — Save button

**Implementation hints:**
- `await page.check('input[value="pingback"]')`
- `await page.click('[type="submit"]')`
- `await page.waitForResponse(r => r.url().includes('admin-ajax.php'))`
- `await page.reload()`
- `await expect(page.locator('input[value="pingback"]')).toBeChecked()`

## Related
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.allowed_comment_types`
- **Plugin Method:** `is_allowed_comment_type()`
