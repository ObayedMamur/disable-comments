---
id: TC-040
title: "Enable \"Disable REST API Comments\" setting and save"
feature: rest-api
priority: high
tags: [rest-api, settings, toggle, admin-ui]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-040 — Enable "Disable REST API Comments" Setting and Save

## Summary

Verifies the REST API restriction toggle can be enabled and saved from the Disable Comments settings page. The setting should persist after a page reload and be reflected in the stored plugin options.

---

## Prerequisites

- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Disable REST API Comments" checkbox is currently unchecked (clean state)

---

## Test Data

| Field | Value |
|-------|-------|
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Option key | `disable_comments_options.remove_rest_API_comments` |
| Expected saved value | `true` (or `1`) |

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | The Disable Comments settings page loads without errors. The page title or heading confirms it is the plugin settings page. |
| 2 | Locate the "Disable REST API Comments" checkbox on the page | The checkbox is visible and currently unchecked. |
| 3 | Click the "Disable REST API Comments" checkbox to enable it | The checkbox becomes checked. No page reload occurs yet. |
| 4 | Click the "Save Changes" (or equivalent submit) button | The page submits the form via the AJAX action `wp_ajax_disable_comments_save_settings`. A success notice ("Settings saved." or equivalent) appears on the page. |
| 5 | Wait for the success notice to be visible | The notice confirms settings were saved successfully. |
| 6 | Reload the page (F5 or navigate back to the same URL) | The settings page reloads cleanly. |
| 7 | Locate the "Disable REST API Comments" checkbox again | The checkbox is still checked, confirming the setting persisted. |
| 8 | Using WP-CLI or a direct DB query, inspect `wp_options` for `disable_comments_options` | The serialized option contains `remove_rest_API_comments` set to `true` (or `1`). |

---

## Expected Results

- The "Disable REST API Comments" checkbox is checkable from the settings UI.
- After saving, a success notice is displayed without any PHP errors or warnings.
- After a page reload, the checkbox remains checked.
- The `disable_comments_options` record in `wp_options` contains `remove_rest_API_comments = true`.

---

## Negative / Edge Cases

- Saving the settings while not logged in as an administrator should result in a permissions error or redirect to the login page.
- The settings page should not expose raw PHP errors if the AJAX action encounters a nonce mismatch.
- Unchecking the option and saving should set `remove_rest_API_comments` to `false` and the REST API should once again function normally.

---

## Playwright Notes

**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `#disable_comments_remove_rest_API_comments` — "Disable REST API Comments" checkbox (confirm actual ID in DOM)
- `input[name="disable_comments_options[remove_rest_API_comments]"]` — alternative attribute selector
- `[role="alert"].notice-success` — Settings saved success notice
- `#submit` or `input[type="submit"]` — Save Changes button

**Implementation hints:**
- Use `page.waitForResponse()` to intercept the AJAX save call and assert a 200 status before checking for the notice.
- After reload, use `expect(page.locator('#disable_comments_remove_rest_API_comments')).toBeChecked()` to assert persistence.
- Optionally run `wp option get disable_comments_options --format=json` via WP-CLI to assert the DB value directly.
- Ensure the test resets the setting to unchecked in an `afterEach` teardown to avoid polluting subsequent tests.

---

## Related

- **WordPress Filters:** `rest_pre_dispatch`, `rest_pre_insert_comment`, `rest_comment_query`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **REST Endpoint:** `/wp/v2/comments`
- **Plugin Option Key:** `disable_comments_options.remove_rest_API_comments`
