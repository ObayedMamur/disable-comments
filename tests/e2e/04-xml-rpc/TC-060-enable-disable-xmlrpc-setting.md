---
id: TC-060
title: "Enable \"Disable XML-RPC Comments\" setting and save"
feature: xml-rpc
priority: high
tags: [xml-rpc, settings, toggle, admin-ui, persistence]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-060 — Enable "Disable XML-RPC Comments" Setting and Save

## Summary

Verifies that the XML-RPC restriction toggle can be enabled from the Disable Comments settings page and persists after save and page reload. The setting should be reflected in the stored plugin options and subsequent XML-RPC behavior.

---

## Prerequisites

- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] XML-RPC is enabled in WordPress (not blocked by server config or another plugin)
- [ ] "Disable XML-RPC Comments" checkbox is currently unchecked (clean state)

---

## Test Data

| Field | Value |
|-------|-------|
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Option key | `disable_comments_options.remove_xmlrpc_comments` |
| Expected saved value | `true` (or `1`) |
| XML-RPC endpoint | `/xmlrpc.php` |

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | The Disable Comments settings page loads without errors. |
| 2 | Locate the "Disable XML-RPC Comments" checkbox on the page | The checkbox is visible and currently unchecked. |
| 3 | Click the "Disable XML-RPC Comments" checkbox to enable it | The checkbox becomes checked. No page reload occurs yet. |
| 4 | Click the Save Changes button | The page submits the form. The AJAX action `wp_ajax_disable_comments_save_settings` fires. |
| 5 | Wait for and observe the success notice | A success notice ("Settings saved." or equivalent) appears, confirming the save was successful. |
| 6 | Reload the page by navigating back to `/wp-admin/admin.php?page=disable_comments_settings` | The settings page reloads cleanly. |
| 7 | Locate the "Disable XML-RPC Comments" checkbox again | The checkbox is still checked, confirming the setting persisted across a page reload. |
| 8 | Using WP-CLI, run `wp option get disable_comments_options --format=json` | The JSON output contains `"remove_xmlrpc_comments": true` (or `1`). |
| 9 | Uncheck the "Disable XML-RPC Comments" checkbox and save again | Success notice appears. |
| 10 | Reload the page and confirm the checkbox is now unchecked | The setting correctly reverts to the unchecked state, confirming round-trip persistence in both directions. |

---

## Expected Results

- The "Disable XML-RPC Comments" checkbox is checkable and its state persists after saving and reloading.
- A success notice is displayed after saving with no PHP errors or warnings.
- The `disable_comments_options` option in `wp_options` contains `remove_xmlrpc_comments = true` when checked.
- Unchecking and saving correctly stores `remove_xmlrpc_comments = false`.

---

## Negative / Edge Cases

- Attempting to access the settings page as a non-admin user (e.g. Editor) should result in a permissions error.
- Submitting the settings form with an invalid or missing nonce should fail gracefully — no settings should be changed.
- If XML-RPC is globally disabled at the server level, this setting is still storable and retrievable via the UI, even if its enforcement has no effect.

---

## Playwright Notes

**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `#disable_comments_remove_xmlrpc_comments` — "Disable XML-RPC Comments" checkbox (verify actual ID in DOM)
- `input[name="disable_comments_options[remove_xmlrpc_comments]"]` — alternative attribute selector
- `[role="alert"].notice-success` — Settings saved success notice
- `#submit` or `input[type="submit"]` — Save Changes button

**Implementation hints:**
- After save, use `expect(page.locator('input[name="disable_comments_options[remove_xmlrpc_comments]"]')).toBeChecked()` to assert the checkbox state.
- Use `page.waitForResponse(resp => resp.url().includes('admin-ajax.php') && resp.status() === 200)` to confirm the AJAX call succeeded before proceeding.
- Run `wp option get disable_comments_options --format=json` in a `test.step` to validate the DB value.
- Reset the setting to unchecked in `afterEach` to avoid polluting TC-061 through TC-063.

---

## Related

- **WordPress Filters:** `xmlrpc_methods`, `wp_headers`, `pre_option_default_pingback_flag`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **XML-RPC Endpoint:** `/xmlrpc.php`
- **Plugin Option Key:** `disable_comments_options.remove_xmlrpc_comments`
