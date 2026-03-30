---
id: TC-085
title: "Cancelling the confirmation dialog aborts the delete operation"
feature: delete-comments
priority: high
tags: [delete, cancel, confirmation, negative, dialog, abort]
type: negative
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-085 — Cancelling the confirmation dialog aborts the delete operation

## Summary
Verifies that when the user clicks "Cancel" (or dismisses the dialog via keyboard Escape) on the SweetAlert2 confirmation dialog, the delete operation is fully aborted — no AJAX request is sent and all comments remain in the database unchanged.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least 3 comments exist in the database
- [ ] SweetAlert2 library is loaded on the Delete Comments tab

## Test Data

| Field | Value |
|-------|-------|
| Test comment 1 | Post ID: 1, Author: "Carol", Content: "Cancel test comment 1" |
| Test comment 2 | Post ID: 1, Author: "David", Content: "Cancel test comment 2" |
| Test comment 3 | Post ID: 2, Author: "Eve", Content: "Cancel test comment 3" |
| Delete mode used | Delete Everywhere |
| Expected comment count before | 3 (unchanged after cancel) |
| Expected comment count after cancel | 3 (no change) |
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/edit-comments.php` | The Comments screen shows the 3 test comments. Note the exact total count. |
| 2 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | The Disable Comments settings page loads. |
| 3 | Click the **"Delete Comments"** tab | The Delete Comments tab becomes active. |
| 4 | Select **"Delete Everywhere"** mode | Delete Everywhere is the active mode. |
| 5 | Set up a network intercept to monitor requests to `admin-ajax.php` | Network monitoring is active to detect any AJAX delete calls. |
| 6 | Click the **"Delete Comments"** button | The SweetAlert2 confirmation modal appears. The modal is visible in the viewport. |
| 7 | Click the **"Cancel"** button (`.swal2-cancel`) in the dialog | The dialog closes (is removed from the DOM or hidden). No AJAX request to `disable_comments_delete_comments` has been sent. The page returns to its previous state. |
| 8 | Verify no AJAX delete request was fired | The network intercept confirms zero requests were made to the delete AJAX action. The page UI shows no success or error messages related to deletion. |
| 9 | Navigate to `/wp-admin/edit-comments.php` | The Comments screen still shows all 3 comments — Carol, David, and Eve. The count is unchanged. |
| 10 | Repeat steps 4–6 but this time press the **Escape key** instead of clicking Cancel | The dialog dismisses upon pressing Escape. The dialog is no longer visible. |
| 11 | Verify again that no deletion occurred after Escape dismissal | Network intercept shows no delete request. Navigating to the Comments screen still shows all 3 comments unchanged. |

## Expected Results
- Clicking Cancel in the SweetAlert2 dialog closes the dialog without firing any AJAX request
- Pressing Escape to dismiss the dialog also aborts the delete operation entirely
- No success or error messages appear in the plugin UI after cancellation
- All comments remain in the database after either cancel action
- The page returns to a state where the user can attempt the delete again if desired

## Negative / Edge Cases
- Clicking outside the SweetAlert2 modal (if allowed) should also abort — not delete
- After cancelling, the user should be able to re-click "Delete Comments" and get the dialog again (no broken state)
- The cancel action must not cause any JavaScript console errors
- If the backdrop click dismissal is disabled in SweetAlert2 config, this should be documented as expected behavior

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `button` or `input[type="submit"]` with delete label — "Delete Comments" button
- `.swal2-popup` — SweetAlert2 dialog
- `.swal2-cancel` — Cancel button
- `.swal2-backdrop` or `.swal2-container` — modal backdrop (for click-outside test)

**Implementation hints:**
- Use `page.route('**/admin-ajax.php', route => { requests.push(route); route.continue(); })` to capture all admin-ajax requests
- After clicking Cancel, assert `requests.filter(r => r.request().postData()?.includes('disable_comments_delete_comments')).length === 0`
- Use `page.keyboard.press('Escape')` to test keyboard dismiss
- After dialog closes via Cancel, assert `await page.locator('.swal2-popup').isHidden()` or `{ state: 'detached' }`
- After cancel, re-navigate to `/wp-admin/edit-comments.php` and count rows: `await page.locator('#the-comment-list tr.comment').count()` should equal 3
- To verify page is back in a usable state, assert the Delete Comments button is still visible/enabled after cancel

## Related
- **AJAX Action:** `wp_ajax_disable_comments_delete_comments` (must NOT be called on cancel)
- **UI Library:** SweetAlert2 (`.swal2-cancel`, `.swal2-popup`)
- **Plugin Option Key:** `disable_comments_options`
