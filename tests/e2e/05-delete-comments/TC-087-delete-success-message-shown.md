---
id: TC-087
title: "Success message shows count of deleted comments after deletion"
feature: delete-comments
priority: medium
tags: [delete, success-message, ui-feedback, count, notification]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-087 — Success message shows count of deleted comments after deletion

## Summary
After a delete operation completes, the plugin UI must display a success notification confirming the operation finished. The message should ideally indicate the number of comments deleted or explicitly confirm that all comments have been removed.

> WARNING: This test involves an IRREVERSIBLE delete operation. Run on a disposable test environment only.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Exactly 5 approved comments exist in the database (for predictable count verification)
- [ ] The comments are spread across at least 2 posts

## Test Data

| Field | Value |
|-------|-------|
| Comment 1 | Post ID: 1, Author: "User One", Content: "Success message test #1" |
| Comment 2 | Post ID: 1, Author: "User Two", Content: "Success message test #2" |
| Comment 3 | Post ID: 1, Author: "User Three", Content: "Success message test #3" |
| Comment 4 | Post ID: 2, Author: "User Four", Content: "Success message test #4" |
| Comment 5 | Post ID: 2, Author: "User Five", Content: "Success message test #5" |
| Delete mode | Delete Everywhere |
| Expected deleted count | 5 |
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/edit-comments.php` | The Comments screen shows exactly 5 comments. Confirm the count. |
| 2 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | The Disable Comments settings page loads. |
| 3 | Click the **"Delete Comments"** tab | The Delete Comments tab becomes active. No success or error messages are visible at this point. |
| 4 | Select **"Delete Everywhere"** mode | Delete Everywhere is the active mode. |
| 5 | Click the **"Delete Comments"** button | The SweetAlert2 confirmation dialog appears. |
| 6 | Click the **"Yes, delete"** confirm button | The dialog closes. The AJAX request is sent to `wp_ajax_disable_comments_delete_comments`. A loading/processing state may appear briefly. |
| 7 | Wait for the AJAX response to complete (no more than 10 seconds) | The plugin UI transitions from a loading/pending state to a completed state. |
| 8 | Verify a success notification element appears in the UI | A success message container (e.g. `.notice-success`, `.updated`, or a custom plugin response area) is visible in the page. |
| 9 | Read the content of the success message and verify it indicates successful deletion | The message text confirms deletion was successful. Ideally it includes the number "5" (comments deleted) or language such as "All comments have been deleted" or "5 comments deleted successfully". |
| 10 | Verify no error notification or failure message is displayed alongside the success message | Only the success notification is shown. No `.notice-error` or `.error` elements are present. |

## Expected Results
- A success notification element is rendered in the plugin UI after the AJAX delete response
- The notification text confirms the deletion completed successfully
- Ideally, the notification mentions the count of deleted comments (e.g. "5 comments deleted")
- No error messages appear after a successful deletion
- The notification is clearly visible without scrolling (or the page scrolls to it automatically)

## Negative / Edge Cases
- If 0 comments exist when delete is run, a success message should still appear — it should say "0 comments deleted" or equivalent, not show an error
- If the AJAX request fails (e.g. network timeout, server error), an error message should appear instead of a success message
- The success message should not disappear immediately — it must remain visible long enough for the user (or test automation) to read it
- Running delete twice in a row: the second run should show "0 comments deleted" success (not an error)

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `.notice-success` — WordPress admin success notice
- `.updated` — alternative WP admin success class
- `.disable-comments-delete-response` or `.delete-result` — plugin-specific response area (inspect actual DOM)
- `.notice-error, .error` — error notice (should NOT appear on success)
- `.swal2-confirm` — confirm button in dialog

**Implementation hints:**
- After clicking confirm, use `page.waitForSelector('.notice-success, .updated', { timeout: 10000 })` to wait for the success message
- Read the message text: `const msg = await page.locator('.notice-success').textContent()`
- Assert the message contains a digit (e.g. `/\d+/`) or known success keywords
- If the plugin uses a custom response container (not WP native notices), inspect the actual DOM structure first and update selectors accordingly
- Use `page.waitForResponse(resp => resp.url().includes('admin-ajax.php') && resp.status() === 200)` to detect when the AJAX call completes before asserting the message

## Related
- **AJAX Action:** `wp_ajax_disable_comments_delete_comments`
- **Plugin Option Key:** `disable_comments_options`
- **Expected UI state:** success notice visible, no error notice present
