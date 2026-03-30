---
id: TC-142
title: "Comment count in post metadata shows 0 when globally disabled"
feature: frontend-behavior
priority: high
tags: [frontend-behavior, global, comment-count, metadata, template-tag]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-142 — Comment Count in Post Metadata Shows 0 When Globally Disabled

## Summary
The WordPress comment count displayed in post headers, post meta areas, and archive pages must show 0 when Remove Everywhere is active, regardless of the actual approved comment count stored in the database. The plugin filters `get_comments_number` to return 0 for disabled post types.

---

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Remove Everywhere" mode is currently ACTIVE (saved)
- [ ] A published Post exists with at least 2 approved comments in the database
- [ ] The active WordPress theme renders comment count in post meta (e.g. "2 Comments" link in post header or footer)

---

## Test Data

| Field | Value |
|-------|-------|
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Test post | A published post with at least 2 approved comments (e.g. "Hello World", `/?p=1`) |
| Actual comment count in DB | ≥ 2 |
| Expected displayed count (disabled) | 0 |
| Expected displayed count (enabled) | Actual count (≥ 2) |

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Disable Comments settings page loads |
| 2 | Confirm "Remove Everywhere" is selected; if not, select it and save | Settings page shows Remove Everywhere as active |
| 3 | Navigate to the WordPress admin, open the test post in the editor, and note the actual number of approved comments (visible in the "Comments" meta box or the admin comment list) | Confirmed: the post has ≥ 2 approved comments in the database |
| 4 | Open a new browser tab and navigate to the published test post URL (e.g. `/?p=1`) | Post page loads with status 200 |
| 5 | Locate the comment count display in the post header or post meta area (commonly a link such as "2 Comments" or "Leave a comment") | The comment count reads "0 comments", "No comments", or "0" — NOT the actual DB count of ≥ 2 |
| 6 | Navigate to the blog archive page (e.g. `/` or `/blog/`) | Archive page loads showing the list of posts |
| 7 | Locate the comment count indicator for the test post in the archive listing | The comment count for the test post shows 0 in the archive listing as well |
| 8 | View the page source of the single post (Ctrl+U) and search for any comment count template output | The count value in the source is 0, not the actual DB count |
| 9 | Disable "Remove Everywhere" (switch to "Do not disable" mode) and save settings | Settings saved successfully |
| 10 | Navigate back to the same post URL | The comment count now reflects the actual database count (≥ 2), confirming the plugin was suppressing it |

---

## Expected Results
- The comment count on the single post page shows 0 while Remove Everywhere is active, regardless of actual DB count
- The comment count in archive/loop pages also shows 0 for the affected post type
- After disabling Remove Everywhere, the actual comment count is restored in the display
- The post page renders without errors despite the filtered count

---

## Negative / Edge Cases
- If the active theme does not output a comment count in the post meta, this test may need to be adapted to call `get_comments_number()` via a custom template or WP-CLI: `wp eval "echo get_comments_number(1);"`
- The database comment count must NOT be altered — the plugin must only filter the display value; verifying the admin comment list still shows actual count after re-enabling confirms DB integrity
- Posts with 0 actual comments are not suitable for this test — the DB count must be ≥ 2 to prove the filter is suppressing a non-zero value

---

## Playwright Notes
**Page URL:** `/?p=1` (or the slug of your test post with existing comments)

**Key Selectors:**
- `.comments-link` — common theme class for the comment count link in post meta
- `a[href*="#comments"]` — link anchoring to the comments section
- `.entry-meta .comments-link` — Twenty* themes comment count in post entry meta
- `span.comment-count` — some themes wrap the count in a span

**Implementation hints:**
- Read the comment count text: `const countText = await page.locator('.comments-link').textContent(); expect(countText).toMatch(/0/);`
- If the selector varies by theme, use a broader search: `await expect(page.getByText(/0 comments/i)).toBeVisible()`
- To set up the prerequisite (post with 2 comments) in a test fixture, use the WordPress REST API: `POST /wp/v2/comments` with `post: 1` twice (requires authentication)
- WP-CLI alternative for fixture setup: `wp comment generate --count=2 --post_id=1 --comment_status=approve`

---

## Related
- **WordPress Filters:** `get_comments_number` (plugin returns 0 for disabled post types)
- **WordPress Template Tag:** `comments_number()`, `get_comments_number()`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
