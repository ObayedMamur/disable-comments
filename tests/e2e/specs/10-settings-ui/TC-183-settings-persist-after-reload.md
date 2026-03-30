---
id: TC-183
title: "Settings persist correctly after browser page reload"
feature: settings-ui
priority: high
tags: [settings, persistence, reload, options]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-183 — Settings Persist Correctly After Browser Page Reload

## Summary
After saving settings, reloading the settings page must show the same selected options — verifying the WordPress options are actually written to and read back from the database correctly.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Plugin settings page is accessible at `/wp-admin/admin.php?page=disable_comments_settings`

## Test Data

| Field | Value |
|-------|-------|
| Mode (first save) | Remove Everywhere |
| XML-RPC checkbox | Checked |
| REST API checkbox | Checked |
| Mode (second save) | Disable by Post Type |
| Post types (second save) | Posts, Pages |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to Settings > Disable Comments tab at `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads with current saved options visible |
| 2 | Select "Remove Everywhere" radio option | Radio button becomes selected |
| 3 | Check "Disable XML-RPC Comments" checkbox | Checkbox becomes checked |
| 4 | Check "Disable REST API Comments" checkbox | Checkbox becomes checked |
| 5 | Click Save Settings and wait for success notification | Success message appears confirming settings were saved |
| 6 | Reload the page (press F5 or call `page.reload()`) | Page reloads and re-renders the settings form |
| 7 | Verify "Remove Everywhere" radio is still selected | Radio button reflects saved value from the database |
| 8 | Verify "Disable XML-RPC Comments" is still checked | Checkbox reflects saved value from the database |
| 9 | Verify "Disable REST API Comments" is still checked | Checkbox reflects saved value from the database |
| 10 | Switch to "Disable by Post Type", check "Posts" and "Pages", click Save Settings | Success message appears |
| 11 | Reload the page and verify "Posts" and "Pages" checkboxes are still checked | Both checkboxes reflect the last saved state |

## Expected Results
- All saved settings are correctly loaded from the database on page reload
- Radio group and checkbox states match the last saved values
- No settings revert to defaults after reload

## Negative / Edge Cases
- Settings must persist across browser sessions (not just within the same session)
- If two admin tabs save conflicting settings simultaneously, the last-write wins

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name*="remove_everywhere"][value="1"]` — Remove Everywhere radio
- `input[name*="remove_xmlrpc_comments"]` — XML-RPC checkbox
- `input[name*="remove_rest_API_comments"]` — REST API checkbox

**Implementation hints:**
- `await page.check('input[value="1"][name*="remove_everywhere"]')`
- `await page.click('[type="submit"]')`
- `await page.reload()`
- `await expect(page.locator('input[value="1"][name*="remove_everywhere"]')).toBeChecked()`

## Related
- **WordPress Function:** `update_option()`, `get_option()`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options`
