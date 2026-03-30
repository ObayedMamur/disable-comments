---
id: TC-084
title: "Confirmation dialog is shown before deletion proceeds"
feature: delete-comments
priority: high
tags: [delete, confirmation, sweetalert2, dialog, ux]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-084 — Confirmation dialog is shown before deletion proceeds

## Summary
Verifies that the SweetAlert2 confirmation dialog always appears when clicking the "Delete Comments" button, that the dialog contains an appropriate warning, and that the actual delete operation does NOT execute until the user explicitly confirms through the dialog.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least 2 comments exist in the database
- [ ] SweetAlert2 library is loaded on the Delete Comments tab

## Test Data

| Field | Value |
|-------|-------|
| Test comment 1 | Post ID: 1, Author: "Alice", Content: "Confirmation test comment 1" |
| Test comment 2 | Post ID: 1, Author: "Bob", Content: "Confirmation test comment 2" |
| Delete mode used | Delete Everywhere |
| Expected comment count before dialog | 2 (unchanged) |
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/edit-comments.php` | The Comments screen shows at least 2 existing comments. Note the comment count. |
| 2 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | The Disable Comments settings page loads. |
| 3 | Click the **"Delete Comments"** tab | The Delete Comments tab becomes active. The delete mode controls and "Delete Comments" button are visible. |
| 4 | Select the **"Delete Everywhere"** radio mode (or confirm it is pre-selected) | Delete Everywhere is the active mode. |
| 5 | Set up a network intercept (Playwright) to monitor AJAX calls to `admin-ajax.php` | Network monitoring is active. Any request to `wp_ajax_disable_comments_delete_comments` will be captured. |
| 6 | Click the **"Delete Comments"** button | A SweetAlert2 modal dialog appears in the viewport. The page background is dimmed. The delete AJAX request has NOT been fired at this point. |
| 7 | Verify the SweetAlert2 modal is visible and contains expected elements | The modal (`.swal2-popup`) is present in the DOM and visible. The dialog contains: a warning icon or heading, text describing the irreversible nature of the operation, a "Yes, delete" (or similar) confirm button, and a "Cancel" button. |
| 8 | Verify no AJAX delete request was made before dialog confirmation | The network intercept captures 0 requests to `disable_comments_delete_comments` action at this point. Comments still exist in the database. |
| 9 | Navigate to `/wp-admin/edit-comments.php` in a **new tab** (or via a separate request) while the dialog is still open | The admin Comments screen still shows the original comment count (2 or more). Comments have NOT been deleted. |
| 10 | Return to the settings page tab and click **"Yes, delete"** in the dialog | The dialog closes and the AJAX delete request fires. The operation proceeds to completion. |

## Expected Results
- The SweetAlert2 dialog is displayed immediately upon clicking "Delete Comments", before any server-side action occurs
- The dialog contains a clear warning that the action is irreversible
- The dialog presents both a confirm ("Yes, delete" or equivalent) and a cancel button
- Zero AJAX requests to the delete action are made before the dialog is confirmed
- Comment data is unchanged in the database at the time the dialog is displayed
- After confirming, the delete operation proceeds normally

## Negative / Edge Cases
- The dialog must appear regardless of which delete mode is selected (Everywhere, By Post Type, By Comment Type, Spam)
- If the SweetAlert2 library fails to load, clicking Delete should not silently delete — there should be a graceful fallback or error
- The dialog must be accessible (keyboard navigable): Tab to confirm/cancel buttons must work, Enter should activate the focused button

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `button` or `input[type="submit"]` with delete label — "Delete Comments" button
- `.swal2-popup` — SweetAlert2 dialog container
- `.swal2-title` — dialog title/heading
- `.swal2-html-container` or `.swal2-content` — dialog body text
- `.swal2-confirm` — confirm button (Yes, delete)
- `.swal2-cancel` — cancel button
- `.swal2-icon.swal2-warning` — warning icon inside dialog

**Implementation hints:**
- Use `page.route('**/admin-ajax.php', ...)` to intercept and track AJAX requests before clicking confirm
- After clicking the Delete button, assert `await page.locator('.swal2-popup').isVisible()` before taking any further action
- Assert the AJAX route was NOT called by checking the intercepted requests array is empty at dialog-shown time
- Use `page.waitForSelector('.swal2-popup', { state: 'visible' })` with a reasonable timeout (5s)
- The `.swal2-confirm` button click should be followed by `page.waitForSelector('.swal2-popup', { state: 'detached' })` to confirm the dialog closed

## Related
- **AJAX Action:** `wp_ajax_disable_comments_delete_comments`
- **UI Library:** SweetAlert2 (`.swal2-popup`, `.swal2-confirm`, `.swal2-cancel`)
- **Plugin Option Key:** `disable_comments_options`
