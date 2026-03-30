---
id: TC-081
title: "Delete comments for a specific post type only"
feature: delete-comments
priority: high
tags: [delete, post-type, selective, partial-delete]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-081 — Delete comments for a specific post type only

## Summary
Verifies that the "Delete by Post Type" mode selectively removes comments only from the chosen post type (Posts), while comments attached to other post types (Pages) remain untouched after the operation.

> WARNING: This operation is IRREVERSIBLE for the targeted post type. Run only on a test/staging environment.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least 2 approved comments exist on Posts (post_type = `post`)
- [ ] At least 2 approved comments exist on Pages (post_type = `page`)
- [ ] The "Delete by Post Type" option is available in the plugin Delete tab
- [ ] Database is backed up or test data is disposable

## Test Data

| Field | Value |
|-------|-------|
| Post 1 (type: post) | ID: 10, Title: "Test Post Alpha", Comment 1: "Alpha comment one", Comment 2: "Alpha comment two" |
| Page 1 (type: page) | ID: 20, Title: "Test Page Beta", Comment 1: "Beta comment one", Comment 2: "Beta comment two" |
| Post type to delete | `post` |
| Post type to preserve | `page` |
| Expected comments deleted | 2 (on Posts) |
| Expected comments remaining | 2 (on Pages) |
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/edit-comments.php` | The Comments screen loads. Confirm at least 2 comments on Posts and at least 2 comments on Pages are listed. Note exact counts. |
| 2 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | The Disable Comments settings page loads successfully. |
| 3 | Click the **"Delete Comments"** tab | The Delete Comments tab becomes active and deletion controls are visible. |
| 4 | Select the **"Delete by Post Type"** radio option | The "Delete by Post Type" mode is selected. A list of registered post type checkboxes appears (e.g. Posts, Pages, Products). |
| 5 | Check **only the "Posts" checkbox** and ensure "Pages" and all other post type checkboxes are unchecked | Only "Posts" checkbox is checked. All other post type boxes remain unchecked. |
| 6 | Click the **"Delete Comments"** button | The SweetAlert2 confirmation modal appears with a warning about irreversible deletion for the selected post type. |
| 7 | Click the **"Yes, delete"** confirm button in the SweetAlert2 dialog | The dialog closes. An AJAX request fires to `wp_ajax_disable_comments_delete_comments` with the `post` post type parameter. |
| 8 | Wait for the AJAX response and verify the success message | A success notification appears confirming deletion. The message references comments being deleted (ideally mentioning the count or post type). |
| 9 | Navigate to `/wp-admin/edit-comments.php` and filter by post type or search for Post comments | Comments previously attached to Posts (ID: 10) are no longer listed. The "Alpha comment one" and "Alpha comment two" entries are gone. |
| 10 | Verify Page comments still exist in the admin Comments screen | Comments attached to Pages (ID: 20) — "Beta comment one" and "Beta comment two" — are still present and unaffected. |

## Expected Results
- Only comments belonging to the `post` post type are deleted from `wp_comments`
- Comments on Pages and any other post types remain intact in the database
- The post comment count for Post ID 10 is updated to 0 via `wp_update_comment_count()`
- The page comment count for Page ID 20 is unchanged
- Success message is displayed after the deletion
- No PHP errors or notices appear

## Negative / Edge Cases
- If no post type is selected before clicking Delete, the form should either prevent submission or show a validation message
- Deleting by post type with 0 comments on that type should succeed with a 0 deleted message
- If a post type is registered but has no posts, the operation should handle gracefully

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[type="radio"][value="delete_by_post_type"]` — Delete by Post Type radio
- `input[type="checkbox"][name*="post_types[]"][value="post"]` — Posts checkbox
- `input[type="checkbox"][name*="post_types[]"][value="page"]` — Pages checkbox
- `button#delete-comments-submit` or `.delete-comments-btn` — Delete button
- `.swal2-popup` — SweetAlert2 confirmation modal
- `.swal2-confirm` — Confirm button in dialog
- `.disable-comments-notice, .updated, .notice-success` — success message

**Implementation hints:**
- After selecting "Delete by Post Type", assert the post type checkbox list becomes visible before interacting
- Use `page.uncheck()` to ensure all other post type checkboxes are unchecked before proceeding
- After deletion, use `page.goto('/wp-admin/edit-comments.php')` and filter or search to validate per-post-type
- To check Page comments still exist, look for `page.locator('td.comment').filter({ hasText: "Beta comment one" })` and assert it is visible
- Consider using WP-CLI or a REST API setup step to seed test comments programmatically before the test

## Related
- **AJAX Action:** `wp_ajax_disable_comments_delete_comments`
- **Plugin Option Key:** `disable_comments_options`
- **SQL executed:** `DELETE FROM {$wpdb->comments} WHERE comment_post_ID IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'post')`
- **Post count update:** `wp_update_comment_count()`
