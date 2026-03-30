---
id: TC-086
title: "Post comment count is updated to 0 after comments are deleted"
feature: delete-comments
priority: high
tags: [delete, comment-count, frontend, wp_update_comment_count, post-meta]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-086 — Post comment count is updated to 0 after comments are deleted

## Summary
After a successful delete operation, the post's comment count must be recalculated and reflect 0 on the post frontend. The plugin calls `wp_update_comment_count()` for each affected post after deletion, ensuring the count stored in `wp_posts.comment_count` is accurate.

> WARNING: This test involves an IRREVERSIBLE delete operation. Run on a disposable test environment only.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one published post exists with 3 approved comments
- [ ] The post has comments enabled and the comments section is visible on the frontend
- [ ] The post's frontend URL is known and accessible

## Test Data

| Field | Value |
|-------|-------|
| Test post title | "Comment Count Test Post" |
| Test post ID | 10 |
| Test post frontend URL | `/comment-count-test-post/` |
| Comment 1 | Author: "Frank", Content: "First approved comment" |
| Comment 2 | Author: "Grace", Content: "Second approved comment" |
| Comment 3 | Author: "Heidi", Content: "Third approved comment" |
| Expected comment count before delete | 3 (displayed as "3 Comments" or "3 responses") |
| Expected comment count after delete | 0 (no comments label, empty section, or "0 Comments") |
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to the test post frontend URL (e.g. `/comment-count-test-post/`) | The post renders with a "3 Comments" heading or count indicator. All 3 comments (Frank, Grace, Heidi) are listed under the post. |
| 2 | Note the exact comment count text displayed (e.g. "3 Comments" or "3 responses") | Comment count text is recorded for comparison after deletion. |
| 3 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | The Disable Comments settings page loads. |
| 4 | Click the **"Delete Comments"** tab | The Delete Comments tab becomes active. |
| 5 | Select **"Delete Everywhere"** mode (or "Delete by Post Type" with Posts selected) | The appropriate delete mode is active. |
| 6 | Click the **"Delete Comments"** button | The SweetAlert2 confirmation dialog appears. |
| 7 | Click **"Yes, delete"** to confirm | The dialog closes. The AJAX delete request fires. The plugin executes `DELETE FROM wp_comments ...` followed by `wp_update_comment_count()` for Post ID 10. |
| 8 | Wait for the success message to appear in the plugin UI | A success notification is displayed confirming comments were deleted. |
| 9 | Navigate to the test post frontend URL (e.g. `/comment-count-test-post/`) | The post page loads. The comment count area now shows 0 comments, no comments, or the comment section displays an empty state (no individual comment entries). |
| 10 | Verify no individual comment entries are rendered on the post frontend | Frank, Grace, and Heidi comment entries are not present in the comments list on the frontend. |
| 11 | Navigate to `/wp-admin/edit.php` (Posts list) and check the comment count bubble for the test post | The comment count shown next to "Comment Count Test Post" in the Posts list is 0 or absent. |

## Expected Results
- After successful deletion, `wp_posts.comment_count` for the affected post is updated to 0
- The frontend displays 0 comments or an empty comments section
- The WordPress admin Posts list shows 0 for the comment count bubble on the affected post
- No stale comment counts remain on the frontend due to caching (if testing without a caching plugin)
- `wp_update_comment_count()` is verified to have been called (indirectly, via the visible count update)

## Negative / Edge Cases
- If a page caching plugin is active, the frontend count may appear stale until cache is cleared — note this as a known limitation
- If the post had comments from multiple authors on multiple posts and only some were deleted, the count should reflect the reduced (not zero) number
- The `comment_count` column in `wp_posts` should be directly verifiable via WP-CLI: `wp post get 10 --field=comment_count`

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings` (for delete), then post frontend URL

**Key Selectors:**
- `.comments-title` — comment count heading on frontend (e.g. "3 Comments")
- `#comments h2, #comments h3` — alternative heading selectors for comment count
- `#respond, #comments .comment-list` — comments section on frontend
- `.comment-count` or `a[href*="#comments"]` — comment count link in post header/meta area
- `.post-com-count` — comment count bubble in WP admin Posts list

**Implementation hints:**
- Before deletion: `const countText = await page.locator('.comments-title').textContent()` and assert it includes "3"
- After deletion: assert `page.locator('.comments-title').textContent()` includes "0" OR the `.comment-list` is empty or absent
- Use `page.locator('ol.comment-list li, ul.comment-list li').count()` to verify no comment list items are rendered
- If using WP-CLI in setup/teardown: `wp comment create --comment_post_ID=10 --comment_approved=1 --comment_author="Frank" --comment_content="First approved comment"`
- After deletion, verify via WP-CLI if needed: `wp post get 10 --field=comment_count` should return `0`

## Related
- **AJAX Action:** `wp_ajax_disable_comments_delete_comments`
- **WordPress Function:** `wp_update_comment_count()` — called after deletion for each affected post
- **Database column:** `wp_posts.comment_count`
- **Plugin Option Key:** `disable_comments_options`
