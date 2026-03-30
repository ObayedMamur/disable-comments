---
id: TC-227
title: "Bulk delete comments across all sites in the network"
feature: multisite
priority: medium
tags: [delete-comments, bulk-delete, network-wide, sweetalert2, multisite]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-227 — Bulk delete comments across all sites in the network

## Summary
On the Delete tab in network admin, selecting all sites and triggering a bulk delete removes comments from all sub-sites' posts in a single operation. This test confirms that the deletion spans the entire network and leaves zero comments across all sub-sites.

## Prerequisites
- [ ] WordPress Multisite is configured and running
- [ ] Disable Comments plugin is network-activated
- [ ] Logged in as Super Administrator (Network Admin)
- [ ] At least two sub-sites exist in the network
- [ ] At least 2 comments exist on posts on sub-site 1 (pre-verified and noted)
- [ ] At least 2 comments exist on posts on sub-site 2 (pre-verified and noted)
- [ ] **WARNING: This test permanently deletes comment data — ensure a database backup or snapshot exists before running**

## Test Data

| Field | Value |
|-------|-------|
| Network Admin Settings URL | `/wp-admin/network/admin.php?page=disable_comments_settings` |
| Sub-site 1 Admin URL | `http://site1.example.com/wp-admin/` |
| Sub-site 2 Admin URL | `http://site2.example.com/wp-admin/` |
| Sub-site 1 Comments Admin URL | `http://site1.example.com/wp-admin/edit-comments.php` |
| Sub-site 2 Comments Admin URL | `http://site2.example.com/wp-admin/edit-comments.php` |
| Pre-test Sub-site 1 Comment Count | `3` (example — record actual count) |
| Pre-test Sub-site 2 Comment Count | `2` (example — record actual count) |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to sub-site 1 admin Comments screen and note the current comment count | Comment count recorded (e.g. 3 comments) |
| 2 | Navigate to sub-site 2 admin Comments screen and note the current comment count | Comment count recorded (e.g. 2 comments) |
| 3 | Navigate to `/wp-admin/network/admin.php?page=disable_comments_settings` and click the "Delete Comments" tab | Delete Comments tab is active and its form is visible |
| 4 | Select "Delete Everywhere" mode in the Delete tab | Delete Everywhere option is selected |
| 5 | In the site selector, ensure ALL sites are selected (or verify the default selects all) | All sub-sites are checked/selected in the site selector |
| 6 | Click the "Delete Comments" button | A SweetAlert2 confirmation dialog appears asking to confirm the irreversible deletion |
| 7 | Click "Confirm" (or "Yes, delete") in the SweetAlert2 dialog | Dialog closes; AJAX request is sent to `admin-ajax.php` |
| 8 | Wait for the deletion operation to complete and observe the success message | A success notice appears confirming comments have been deleted |
| 9 | Navigate to `http://site1.example.com/wp-admin/edit-comments.php` — check comment count | Comment count is 0; "No comments found" message is shown |
| 10 | Navigate to `http://site2.example.com/wp-admin/edit-comments.php` — check comment count | Comment count is 0; "No comments found" message is shown |

## Expected Results
- All comments are deleted from all sub-sites in the network.
- Both sub-site 1 and sub-site 2 admin comment screens show 0 comments after the operation.
- A success message is displayed on the network admin settings page after deletion completes.
- No error messages or partial failure notices appear.

## Negative / Edge Cases
- This operation is irreversible — restore from backup/snapshot after test completion.
- If the AJAX request times out on a large network with many comments, a timeout error should be displayed rather than a silent failure.
- If all sites already have 0 comments when the delete is triggered, the operation should still succeed gracefully with no errors.

## Playwright Notes
**Page URL:** `/wp-admin/network/admin.php?page=disable_comments_settings` (Delete tab)

**Key Selectors:**
- `.nav-tab[href*="delete"], #delete-tab` — Delete Comments tab
- `#swal2-confirm, .swal2-confirm` — SweetAlert2 confirm button
- `.swal2-popup` — SweetAlert2 dialog container
- `.notice-success, .dc-success-notice` — post-deletion success message
- `#the-comment-list tr` — comment rows on comments admin page (should be empty after deletion)

**Implementation hints:**
- Click confirm button: `await page.click('#swal2-confirm')`
- Wait for AJAX to complete: `await page.waitForResponse(r => r.url().includes('admin-ajax.php'))`
- Assert no comments: `await expect(page.locator('#the-comment-list tr.no-items')).toBeVisible()`
- Or: `await expect(page.locator('#the-comment-list tr[id^="comment"]')).toHaveCount(0)`
- Note: multisite tests often need multiple browser contexts for different user roles/sites

## Related
- **WordPress Functions:** `get_comments()`, `wp_delete_comment()`, `get_sites()`, `switch_to_blog()`
- **AJAX Action:** (delete comments AJAX action in plugin)
- **Plugin Option Key:** `disable_comments_options.disabled_sites`
