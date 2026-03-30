---
id: TC-083
title: "Delete comments by comment type (e.g. pingbacks only)"
feature: delete-comments
priority: medium
tags: [delete, comment-type, pingback, trackback, selective]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-083 — Delete comments by comment type (e.g. pingbacks only)

## Summary
Verifies that the "Delete by Comment Type" mode removes only the selected comment type (e.g. `pingback`) while all other comment types — particularly regular `comment` type — remain intact in the database.

> WARNING: This operation is IRREVERSIBLE for the targeted comment type. Run only on a test/staging environment.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least 2 comments of type `pingback` exist (`comment_type = 'pingback'`)
- [ ] At least 2 comments of type `comment` (regular) exist (`comment_type = 'comment'` or empty string)
- [ ] The "Delete by Comment Type" option is available in the plugin Delete tab
- [ ] Database is backed up or test data is disposable

## Test Data

| Field | Value |
|-------|-------|
| Pingback 1 | Post ID: 1, comment_type: `pingback`, comment_author: "External Site A", comment_content: "Pingback from site-a.com" |
| Pingback 2 | Post ID: 2, comment_type: `pingback`, comment_author: "External Site B", comment_content: "Pingback from site-b.com" |
| Regular comment 1 | Post ID: 1, comment_type: `comment`, comment_author: "Alice", comment_content: "Great article!" |
| Regular comment 2 | Post ID: 2, comment_type: `comment`, comment_author: "Bob", comment_content: "Very useful, thanks." |
| Comment type to delete | `pingback` |
| Comment type to preserve | `comment` |
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/edit-comments.php` | The Comments screen loads. Verify pingback and regular comment entries exist. Optionally filter by type to confirm counts. |
| 2 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | The Disable Comments settings page loads. |
| 3 | Click the **"Delete Comments"** tab | The Delete Comments tab becomes active. Comment type deletion controls are displayed. |
| 4 | Select the **"Delete by Comment Type"** radio option | The "Delete by Comment Type" mode is selected. A list of comment type checkboxes appears (e.g. comment, pingback, trackback). |
| 5 | Check **only the "pingback" checkbox** and ensure "comment", "trackback", and all other type checkboxes are unchecked | Only "pingback" is checked. The "comment" type box is explicitly unchecked. |
| 6 | Click the **"Delete Comments"** button | The SweetAlert2 confirmation modal appears, warning that pingback-type comments will be permanently deleted. |
| 7 | Review the confirmation dialog and verify the warning message references pingbacks or the selected type | The dialog text accurately reflects the scope of deletion (pingbacks only). Both a confirm and cancel button are visible. |
| 8 | Click the **"Yes, delete"** confirm button | The dialog closes. An AJAX request is sent to `wp_ajax_disable_comments_delete_comments` with the `pingback` comment type parameter. |
| 9 | Wait for the AJAX response and verify the success message | A success notification appears confirming deletion. The message indicates completion (ideally mentioning count or type deleted). |
| 10 | Navigate to `/wp-admin/edit-comments.php` and search or filter for pingback-type comments | "External Site A" and "External Site B" pingback entries are gone. No pingback type comments remain. |
| 11 | Verify regular comments still exist in the admin Comments screen | "Alice" ("Great article!") and "Bob" ("Very useful, thanks.") regular comments are still present and unaffected. |

## Expected Results
- Only comments with `comment_type = 'pingback'` are deleted from `wp_comments`
- Comments with `comment_type = 'comment'` are not deleted
- Comments with `comment_type = 'trackback'` are not deleted (since "trackback" was not selected)
- The affected posts' comment counts are updated via `wp_update_comment_count()`
- Success message is displayed after deletion
- No PHP errors or notices appear

## Negative / Edge Cases
- If no checkboxes are selected under "Delete by Comment Type", clicking Delete should either show a validation message or treat it as a no-op
- If all comment types are selected under this mode, the result should be equivalent to Delete Everywhere
- Custom comment types registered by third-party plugins should appear in the list and be deletable if selected
- Comment types listed in `allowed_comment_types` must NOT be deleted even if their checkbox is selected (see TC-088)

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[type="radio"][value="delete_by_comment_type"]` — Delete by Comment Type radio
- `input[type="checkbox"][name*="comment_types[]"][value="pingback"]` — pingback checkbox
- `input[type="checkbox"][name*="comment_types[]"][value="comment"]` — regular comment checkbox
- `input[type="checkbox"][name*="comment_types[]"][value="trackback"]` — trackback checkbox
- `.swal2-popup` — SweetAlert2 modal
- `.swal2-confirm` — Confirm button
- `.disable-comments-notice, .notice-success` — success notification

**Implementation hints:**
- After selecting "Delete by Comment Type", wait for the comment type checkbox list to appear before interacting
- Use `page.uncheck()` on all checkboxes first, then `page.check()` only the pingback one to avoid race conditions
- After deletion, navigate to the admin comments screen and use a filter if available, or search for known pingback author text
- Seed pingback comments via WP-CLI: `wp comment create --comment_post_ID=1 --comment_type=pingback --comment_author="External Site A" --comment_content="Pingback from site-a.com" --comment_approved=1`

## Related
- **AJAX Action:** `wp_ajax_disable_comments_delete_comments`
- **Plugin Option Key:** `disable_comments_options`
- **SQL filter:** `WHERE comment_type = 'pingback'`
- **Protected types:** `allowed_comment_types` setting (see TC-088)
- **Post count update:** `wp_update_comment_count()`
