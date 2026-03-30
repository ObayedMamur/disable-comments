---
id: TC-088
title: "Comment types in the allowlist are NOT deleted during bulk delete"
feature: delete-comments
priority: medium
tags: [delete, allowlist, comment-types, protection]
type: edge-case
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-088 — Comment types in the allowlist are NOT deleted during bulk delete

## Summary
When comment types are added to the `allowed_comment_types` setting (the allowlist), bulk delete operations must NOT remove those comments. Only non-allowed types should be deleted. This protects special comment types like WordPress Notes from being wiped during bulk cleanup.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least 2 regular 'comment' type comments exist
- [ ] At least 1 comment of an allowed type (e.g. type='note' or a custom type) exists
- [ ] The allowed type is configured in Settings > Disable tab > Allowed Comment Types section

## Test Data

| Field | Value |
|-------|-------|
| Allowed comment type | `note` (or any custom type in use) |
| Regular comment type | `comment` |
| Number of regular comments | 3 |
| Number of allowed-type comments | 1 |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Note current comment counts: note how many 'comment' type and how many 'note' type exist (via WP Admin > Comments, filter by type) | Comment counts for both types are recorded |
| 2 | Navigate to Settings > Disable tab, find Allowed Comment Types section | Allowed Comment Types section is visible |
| 3 | Add 'note' type to the allowlist, save settings | Settings saved successfully; 'note' checkbox is checked |
| 4 | Navigate to Settings > Delete Comments tab | Delete Comments tab is displayed |
| 5 | Select "Delete Everywhere" mode to delete all comment types | "Delete Everywhere" mode is selected |
| 6 | Click the Delete Comments button | SweetAlert2 confirmation dialog appears |
| 7 | Confirm the SweetAlert2 confirmation dialog | Deletion process begins |
| 8 | After deletion completes, navigate to WP Admin > Comments | Comments admin screen is displayed |
| 9 | Verify: regular 'comment' type comments are gone (count = 0) | Zero 'comment' type comments remain |
| 10 | Verify: 'note' type comments are still present (count unchanged from step 1) | 'note' type comments are intact and unmodified |
| 11 | Verify the success message reflects the number of deleted comments (only regular type count) | Success message shows count matching only non-allowed deleted comments |

## Expected Results
- Comments of type 'comment' (non-allowed) are permanently deleted
- Comments of the allowed type ('note') remain in the database untouched
- Admin Comments screen shows allowed-type comments still present
- Frontend comment counts reflect the deletion of regular comments

## Negative / Edge Cases
- If NO comment types are in the allowlist, ALL comments should be deleted
- Removing a type from the allowlist and re-running delete should then delete that type
- The protection applies to Delete by Post Type and Delete by Comment Type modes as well

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `.dc-delete-tab` or `[data-tab="delete"]` — Delete Comments tab
- `#swal2-confirm` — SweetAlert2 confirm button
- `.comments-count` — comment count indicator in UI

**Implementation hints:**
- Use `page.waitForResponse(resp => resp.url().includes('admin-ajax.php'))` to wait for delete AJAX to complete
- Verify allowed-type comments via `page.request.get('/wp-json/wp/v2/comments?type=note')` if REST is enabled
- Or verify by navigating to WP Admin > Comments and filtering by type

## Related
- **WordPress Function:** `is_allowed_comment_type()`
- **AJAX Action:** `wp_ajax_disable_comments_delete_comments`
- **Plugin Option Key:** `disable_comments_options.allowed_comment_types`
