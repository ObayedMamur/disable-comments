---
id: TC-082
title: "Delete spam comments only"
feature: delete-comments
priority: high
tags: [delete, spam, selective, comment-status]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-082 — Delete spam comments only

## Summary
Verifies that the "Delete Spam" operation removes only comments with `comment_approved = 'spam'` from the database, while approved comments, pending comments, and trash comments remain untouched.

> WARNING: Spam deletion is IRREVERSIBLE. Run only on a test/staging environment.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least 2 spam comments exist (`comment_approved = 'spam'`) on any post
- [ ] At least 2 approved comments exist (`comment_approved = '1'`) on any post
- [ ] Database is backed up or test data is disposable

## Test Data

| Field | Value |
|-------|-------|
| Spam comment 1 | Post ID: 1, Author: "SpamBot One", Content: "Buy cheap meds now!" |
| Spam comment 2 | Post ID: 1, Author: "SpamBot Two", Content: "Click here for deals!" |
| Approved comment 1 | Post ID: 1, Author: "Alice", Content: "Really helpful post, thank you." |
| Approved comment 2 | Post ID: 2, Author: "Bob", Content: "Great read, will share." |
| comment_approved value for spam | `spam` |
| comment_approved value for approved | `1` |
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/edit-comments.php?comment_status=spam` | The admin Comments screen filtered to Spam shows at least 2 spam comments (SpamBot One, SpamBot Two). Note the spam count. |
| 2 | Navigate to `/wp-admin/edit-comments.php?comment_status=approved` | The admin Comments screen filtered to Approved shows at least 2 approved comments (Alice, Bob). Note the approved count. |
| 3 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | The Disable Comments settings page loads. |
| 4 | Click the **"Delete Comments"** tab | The Delete Comments tab becomes active. The "Delete Spam" option or button is visible. |
| 5 | Select or click the **"Delete Spam"** option (radio, checkbox, or dedicated button depending on plugin UI) | The Delete Spam mode is active or the Delete Spam button is ready to be clicked. |
| 6 | Click the **"Delete Comments"** or **"Delete Spam"** submit button | The SweetAlert2 confirmation modal appears, warning that spam comments will be permanently deleted. |
| 7 | Verify the confirmation modal content | The dialog references spam deletion. A confirm button and a cancel button are visible. |
| 8 | Click the **"Yes, delete"** confirm button | The dialog closes. An AJAX request is sent to `wp_ajax_disable_comments_delete_comments` with the spam filter. |
| 9 | Wait for AJAX response and verify the success message in the plugin UI | A success notification appears confirming spam deletion. The message may indicate how many spam comments were deleted (e.g. "2 comments deleted"). |
| 10 | Navigate to `/wp-admin/edit-comments.php?comment_status=spam` | The Spam queue is now empty. "SpamBot One" and "SpamBot Two" comments are no longer listed. The spam count shows 0. |
| 11 | Navigate to `/wp-admin/edit-comments.php?comment_status=approved` | Approved comments by Alice and Bob are still present and unaffected. |

## Expected Results
- Only comments with `comment_approved = 'spam'` are removed from `wp_comments`
- Approved comments (`comment_approved = '1'`) are not touched
- Pending comments (`comment_approved = '0'`) are not touched
- Trashed comments are not touched
- Success message is displayed after spam deletion
- Spam queue in admin shows 0 after the operation
- No PHP errors or notices appear

## Negative / Edge Cases
- If there are no spam comments, the operation should complete successfully with a 0 deleted message — it must not throw an error
- Running spam delete when the spam queue is already empty should be idempotent
- Spam comments on draft or private posts should also be deleted (spam status takes precedence over post status)

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[type="radio"][value="delete_spam"]` or `button.delete-spam-btn` — Delete Spam option/button
- `.swal2-popup` — SweetAlert2 confirmation modal
- `.swal2-confirm` — Confirm deletion button
- `.disable-comments-notice, .updated, .notice-success` — success message area
- `#the-comment-list tr` — comment rows in admin Comments screen

**Implementation hints:**
- Use `page.goto('/wp-admin/edit-comments.php?comment_status=spam')` to pre-validate spam exists before testing
- After deletion, re-navigate to the spam queue URL and assert `page.locator('#the-comment-list .no-items')` or empty state is visible
- For approved comments check, navigate to `/wp-admin/edit-comments.php?comment_status=approved` and assert Alice/Bob comments are still visible
- Consider seeding spam comments via WP-CLI: `wp comment create --comment_post_ID=1 --comment_approved=spam --comment_content="Spam content"`

## Related
- **AJAX Action:** `wp_ajax_disable_comments_delete_comments`
- **Plugin Option Key:** `disable_comments_options`
- **SQL filter:** `WHERE comment_approved = 'spam'`
- **WordPress spam admin URL:** `/wp-admin/edit-comments.php?comment_status=spam`
