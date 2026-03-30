---
id: TC-080
title: "Delete all comments from all post types (Delete Everywhere flow)"
feature: delete-comments
priority: smoke
tags: [delete, everywhere, confirmation, smoke, irreversible]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-080 — Delete all comments from all post types (Delete Everywhere flow)

## Summary
Full end-to-end flow of deleting all comments site-wide using the "Delete Everywhere" mode, including the SweetAlert2 confirmation dialog, success message verification, and confirmed removal from both the admin Comments screen and the post frontend.

> WARNING: This operation is IRREVERSIBLE. Comments permanently removed via direct SQL (`DELETE FROM wp_comments`). Run only on a test/staging environment with disposable data.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least 3 comments exist across multiple posts and post types (e.g. 1 on a Post, 1 on a Page, 1 on a custom post type)
- [ ] At least one post has its comment count visible on the frontend (e.g. "3 Comments" link)
- [ ] Database is backed up or test data is disposable

## Test Data

| Field | Value |
|-------|-------|
| Comment on Post (ID) | Post ID: 1, Comment: "Great article, thanks!" |
| Comment on Page (ID) | Page ID: 2, Comment: "Very helpful page." |
| Comment on CPT | CPT: `product`, Post ID: 5, Comment: "Nice product." |
| Frontend post URL | `/sample-post/` (post with visible comment count) |
| Expected comment count before | 3 (or more) |
| Expected comment count after | 0 |
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/edit-comments.php` | The admin Comments screen loads and shows all existing comments (at least 3). Note the total count displayed. |
| 2 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | The Disable Comments settings page loads. |
| 3 | Click the **"Delete Comments"** tab | The Delete Comments tab becomes active. Delete mode options are displayed. |
| 4 | Verify the **"Delete Everywhere"** radio option is present and select it | The "Delete Everywhere" mode is selected. Post type checkboxes and comment type checkboxes are hidden or disabled. |
| 5 | Click the **"Delete Comments"** button | A SweetAlert2 confirmation modal appears. The modal contains a warning message about the irreversible nature of the action. The delete operation has NOT yet executed. |
| 6 | Verify the confirmation dialog content | The dialog shows a warning/danger heading and explanatory text. A "Yes, delete" (or equivalent confirm) button and a "Cancel" button are both visible. |
| 7 | Click the **"Yes, delete"** (confirm) button in the dialog | The dialog closes. An AJAX request is sent to `wp_ajax_disable_comments_delete_comments`. A loading indicator or spinner may appear briefly. |
| 8 | Wait for the AJAX response and verify the success message | A success notification appears in the UI indicating deletion was completed. The message should reference the number of deleted comments or confirm that all comments have been removed. |
| 9 | Navigate to `/wp-admin/edit-comments.php` | The Comments screen shows 0 comments (or "No comments found"). All previously listed comments are gone. |
| 10 | Navigate to the frontend URL of the test post (e.g. `/sample-post/`) | The post's comment count shows 0 or the comments section is empty. No individual comment entries are rendered. |

## Expected Results
- The SweetAlert2 confirmation dialog appears before any deletion takes place
- After confirmation, the AJAX delete action executes successfully
- A success message is displayed in the plugin UI with deletion confirmation
- All comments are removed from `wp_comments` across all post types
- The admin Comments screen (`/wp-admin/edit-comments.php`) shows 0 comments
- The post's frontend comment count is updated to 0 (via `wp_update_comment_count()`)
- No PHP errors or notices appear in the admin

## Negative / Edge Cases
- If no comments exist before the operation, the success message should still appear but indicate 0 deleted
- The operation must not partially fail — either all comments are deleted or the error is surfaced
- Running the delete twice should not cause a PHP error; the second run should succeed with 0 deleted

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `a[href*="delete"]` or tab selector — "Delete Comments" tab
- `input[type="radio"][value="delete_everywhere"]` — Delete Everywhere radio
- `button#delete-comments-submit` or `input[type="submit"].delete-btn` — Delete Comments button
- `.swal2-popup` — SweetAlert2 modal container
- `.swal2-confirm` — SweetAlert2 confirm button ("Yes, delete")
- `.swal2-cancel` — SweetAlert2 cancel button
- `.disable-comments-notice` or `.updated` — success message container
- `#the-comment-list` — comment table rows in admin Comments screen

**Implementation hints:**
- Use `page.waitForSelector('.swal2-popup')` after clicking Delete to assert the dialog appears
- Use `page.click('.swal2-confirm')` to confirm deletion
- Use `page.waitForResponse` or `page.waitForSelector` to detect the AJAX response
- After deletion, do a full page navigation to `/wp-admin/edit-comments.php` to avoid stale DOM
- Check `page.locator('#the-comment-list tr').count()` equals 0 for the admin screen assertion
- For frontend verification, navigate to the post URL and assert `page.locator('.comments-title, #comments h2')` contains "0" or is absent

## Related
- **WordPress Filters:** `disable_comments_remove_everywhere`
- **AJAX Action:** `wp_ajax_disable_comments_delete_comments`
- **Plugin Option Key:** `disable_comments_options`
- **SQL executed:** `DELETE FROM {$wpdb->comments} WHERE ...` + `DELETE FROM {$wpdb->commentmeta} WHERE ...`
- **Post count update:** `wp_update_comment_count()`
