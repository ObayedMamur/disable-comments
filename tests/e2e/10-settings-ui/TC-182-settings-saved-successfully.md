---
id: TC-182
title: "Settings are saved successfully with AJAX success response"
feature: settings-ui
priority: smoke
tags: [settings-ui, save, ajax, smoke]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-182 — Settings are saved successfully with AJAX success response

## Summary
Verifies the complete save flow — the user changes a setting and clicks Save, the AJAX request to `admin-ajax.php` succeeds with a `{"success": true}` response, and a success notification is displayed in the UI.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Browser developer tools or Playwright network interception is available to inspect AJAX responses

## Test Data

| Field | Value |
|-------|-------|
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| AJAX endpoint | `/wp-admin/admin-ajax.php` |
| AJAX action | `disable_comments_save_settings` |
| Nonce field name | `disable_comments_save_settings` |
| Expected AJAX response | `{"success": true, "data": "Saved"}` |
| Setting to change | Mode: "Remove Everywhere" (radio button) |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads successfully |
| 2 | On the "Disable Comments" tab, locate the mode selection radio buttons | Radio buttons for mode selection are visible (e.g. "Remove Everywhere", "Disable by Post Type") |
| 3 | Select "Remove Everywhere" radio button (or change from current selection) | Radio button is selected/checked |
| 4 | Set up network interception to capture the POST request to `admin-ajax.php` | Interception is active (in Playwright: `page.waitForResponse(...)`) |
| 5 | Click the "Save" button | Button click is registered; spinner or loading state may appear briefly |
| 6 | Wait for the AJAX response from `admin-ajax.php` | Response is received with HTTP status 200 |
| 7 | Inspect the AJAX response body | Response body is valid JSON: `{"success": true, "data": "Saved"}` (or equivalent success payload) |
| 8 | Inspect the settings page UI after save completes | A success notification/message appears (e.g. a banner, toast, or inline alert) |
| 9 | Verify the success message text | Message contains "Saved", "Settings saved", or similar confirmation text |
| 10 | Verify the form is still usable after save (no page unload required) | Settings form remains on the page and interactive; no full page reload occurred |

## Expected Results
- AJAX POST to `admin-ajax.php` with action `disable_comments_save_settings` returns HTTP 200
- Response JSON contains `"success": true`
- A visible success notification appears in the page UI
- The notification contains a confirmation message (e.g. "Saved" or "Settings saved successfully")
- The page does not perform a full reload (save is handled asynchronously via AJAX)
- No JavaScript errors occur during the save operation

## Negative / Edge Cases
- If the nonce is invalid or expired, the AJAX call should return an error; test that the UI handles this gracefully (error message instead of success)
- If the network request fails (simulate offline), verify an error state is shown rather than a false success
- Saving with no changes made should still succeed and return a success response

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name="disabled_post_types"], input[type="radio"][name*="mode"]` — Mode radio buttons (exact name may vary; inspect DOM)
- `#disable_comments_save_btn, button[type="submit"], input[type="submit"]` — Save button
- `.notice-success, .updated, .dc-notice, [class*="success"]` — Success notification element
- `form#disable_comments_settings_form` — Settings form (if applicable)

**Implementation hints:**
- Intercept the AJAX response before clicking Save:
  ```ts
  const responsePromise = page.waitForResponse(
    resp => resp.url().includes('admin-ajax.php') && resp.request().method() === 'POST'
  );
  await page.locator('/* save button selector */').click();
  const response = await responsePromise;
  expect(response.status()).toBe(200);
  const body = await response.json();
  expect(body.success).toBe(true);
  ```
- Then assert success UI: `await expect(page.locator('.notice-success, .updated')).toBeVisible()`
- Verify nonce is present in the POST body by inspecting `response.request().postData()`

## Related
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Nonce:** `disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options`
- **Related TC:** TC-183 (settings persist after reload)
