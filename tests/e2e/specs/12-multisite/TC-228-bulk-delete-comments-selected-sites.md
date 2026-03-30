---
id: TC-228
title: "Bulk delete comments for selected sub-sites only"
feature: multisite
priority: medium
tags: [delete-comments, site-selection, targeted-delete, sweetalert2, multisite]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-228 — Bulk delete comments for selected sub-sites only

## Summary
Using the site selector on the Delete tab, the admin selects only sub-site 1 and triggers the comment deletion. Sub-site 2 comments must remain completely intact. This test verifies that the site selection accurately scopes the DELETE SQL query to only the chosen sub-site's database tables.

## Prerequisites
- [ ] WordPress Multisite is configured and running
- [ ] Disable Comments plugin is network-activated
- [ ] Logged in as Super Administrator (Network Admin)
- [ ] At least two sub-sites exist in the network
- [ ] At least 2 comments exist on posts on sub-site 1 (pre-verified and noted)
- [ ] At least 2 comments exist on posts on sub-site 2 (pre-verified and noted)
- [ ] **WARNING: This test permanently deletes comment data on sub-site 1 — ensure a database backup or snapshot exists before running**

## Test Data

| Field | Value |
|-------|-------|
| Network Admin Settings URL | `/wp-admin/network/admin.php?page=disable_comments_settings` |
| Sub-site 1 Admin URL | `http://site1.example.com/wp-admin/` |
| Sub-site 2 Admin URL | `http://site2.example.com/wp-admin/` |
| Sub-site 1 Comments Admin URL | `http://site1.example.com/wp-admin/edit-comments.php` |
| Sub-site 2 Comments Admin URL | `http://site2.example.com/wp-admin/edit-comments.php` |
| Pre-test Sub-site 1 Comment Count | `3` (example — record actual count) |
| Pre-test Sub-site 2 Comment Count | `2` (example — must remain unchanged after test) |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to sub-site 1 admin Comments screen and note the comment count; do the same for sub-site 2 | Both counts recorded for post-test comparison |
| 2 | Navigate to `/wp-admin/network/admin.php?page=disable_comments_settings` and click the "Delete Comments" tab | Delete Comments tab is active |
| 3 | In the site selector for the Delete tab, check/select only sub-site 1; uncheck/deselect sub-site 2 | Sub-site 1 is selected; sub-site 2 is explicitly deselected |
| 4 | Select "Delete Everywhere" mode | Delete Everywhere option is active |
| 5 | Click "Delete Comments" button | SweetAlert2 confirmation dialog appears |
| 6 | Click "Confirm" (or "Yes, delete") in the dialog | Dialog closes; AJAX deletion request is sent |
| 7 | Wait for deletion to complete and a success message to appear | Success notice visible; operation completed without errors |
| 8 | Navigate to `http://site1.example.com/wp-admin/edit-comments.php` | Comment count is 0; "No comments found" message is shown |
| 9 | Navigate to `http://site2.example.com/wp-admin/edit-comments.php` | Comment count matches the pre-test recorded count; no comments were deleted from sub-site 2 |

## Expected Results
- All comments on sub-site 1 are deleted; its comment screen shows 0 comments.
- Sub-site 2 comments are preserved; its comment screen shows the original pre-test count.
- No error messages or partial failure notices appear.
- The deletion operation is scoped exclusively to the selected sub-site's data.

## Negative / Edge Cases
- The site selection must accurately control which `wp_X_comments` table is targeted in the DELETE query — a bug here could wipe all sites.
- Verifying that sub-site 2's comment count matches exactly (not just "greater than 0") confirms no partial deletion occurred.
- If the site selector allows selecting no sites at all, clicking Delete should either be disabled or show a validation error rather than silently doing nothing.

## Playwright Notes
**Page URL:** `/wp-admin/network/admin.php?page=disable_comments_settings` (Delete tab)

**Key Selectors:**
- `.nav-tab[href*="delete"], #delete-tab` — Delete Comments tab
- `.dc-site-list li[data-site-id="2"] input[type="checkbox"]` — checkbox for sub-site 1 in Delete tab selector (adjust ID)
- `.dc-site-list li[data-site-id="3"] input[type="checkbox"]` — checkbox for sub-site 2 (adjust ID; uncheck this)
- `#swal2-confirm, .swal2-confirm` — SweetAlert2 confirm button
- `.notice-success` — success message after deletion

**Implementation hints:**
- Select only sub-site 1: `await page.check('.dc-site-list li[data-site-id="2"] input')`
- Deselect sub-site 2: `await page.uncheck('.dc-site-list li[data-site-id="3"] input')`
- Confirm dialog: `await page.click('#swal2-confirm')`
- Wait for AJAX: `await page.waitForResponse(r => r.url().includes('admin-ajax.php'))`
- Assert sub-site 2 count unchanged: retrieve count before test, assert same value after
- Note: multisite tests often need multiple browser contexts for different user roles/sites

## Related
- **WordPress Functions:** `wp_delete_comment()`, `get_sites()`, `switch_to_blog()`, `restore_current_blog()`
- **AJAX Action:** (delete comments AJAX action in plugin)
- **Plugin Option Key:** `disable_comments_options.disabled_sites`
