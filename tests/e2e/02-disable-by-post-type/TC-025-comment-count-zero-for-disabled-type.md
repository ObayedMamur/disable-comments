---
id: TC-025
title: "Comment count shows 0 for disabled post type, unchanged for enabled"
feature: disable-by-post-type
priority: high
tags: [disable-by-post-type, comment-count, filters, frontend]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-025 — Comment Count Shows 0 for Disabled Post Type, Unchanged for Enabled

## Summary
Verifies that the `get_comments_number` filter is applied selectively — posts of the disabled type return a comment count of 0 even when they have approved comments, while posts of the enabled type return their actual comment count. This ensures no bleed-over between types.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one published Post exists with at least 1 approved comment (note the actual count)
- [ ] At least one published Page exists with at least 1 approved comment (note the actual count)
- [ ] The active theme renders a comment count link/number (e.g. via `get_comments_number()` or `comments_number()` in the template)
- [ ] Plugin is configured to disable "Posts" type only (configure in steps below)

## Test Data

| Field | Value |
|-------|-------|
| Settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Sample Post URL | `/?p=1` or `/hello-world/` |
| Sample Page URL | `/?page_id=2` or `/sample-page/` |
| Known Post comment count | e.g. `3` (note actual count before test) |
| Known Page comment count | e.g. `2` (note actual count before test) |
| Disabled post type | `post` |
| Enabled post type | `page` |
| Plugin option key | `disable_comments_options.disabled_post_types` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Note the existing approved comment counts for the test Post and test Page (via WP admin or WP-CLI: `wp comment list --post_id=1 --status=approve --format=count`) | Actual counts are recorded as baseline |
| 2 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads |
| 3 | Select "Disable by Post Type" radio button | Radio is selected; post-type checkboxes appear |
| 4 | Check "Posts" only; uncheck "Pages" and "Media" | Only "Posts" is checked |
| 5 | Click "Save Changes" | Success notification appears; settings are saved |
| 6 | Dismiss the notification | Notification closes |
| 7 | Navigate to the Post frontend URL (e.g. `/hello-world/`) as a logged-out visitor | Post page loads |
| 8 | Inspect the comment count displayed on the post (in the meta area, or within the page body) | The displayed comment count is `0` (or the count element is entirely absent), not the actual baseline count |
| 9 | If the theme displays a comment count link (e.g. "3 Comments"), verify it now shows "0 Comments" or no count at all | Count reflects `0` for the disabled post type |
| 10 | Navigate to the Page frontend URL (e.g. `/sample-page/`) as a logged-out visitor | Page loads |
| 11 | Inspect the comment count displayed on the Page | The displayed comment count matches the actual baseline count (e.g. `2 Comments`) — NOT reduced to 0 |
| 12 | (Optional) Use browser DevTools or WP-CLI to call `get_comments_number($post_id)` for both post types and compare results | Post returns `0`; Page returns the actual count |

## Expected Results
- Disabled post type (Posts) shows a comment count of `0` on the frontend even though approved comments exist in the database
- Enabled post type (Pages) shows the actual comment count unchanged
- The `get_comments_number` WordPress filter is applied only to posts whose type is in `disabled_post_types`
- No errors in the browser console or PHP error log

## Negative / Edge Cases
- A Post with 0 comments would trivially show 0 — use a Post that already has at least 1 approved comment to make the test meaningful
- If the theme does not render a visible comment count (uses no `comments_number()` template tag), the count filter may still work but not be visible; in that case, verify via WP-CLI or a custom endpoint
- The `get_comments_number` filter must NOT return 0 for the Page — verify the Page count is the actual database value
- Test must be run as a logged-out visitor; admin users may see different counts or bypass filters in some configurations

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name="disable_comments_options[remove_everywhere]"][value="2"]` — "Disable by Post Type" radio
- `input[name="disable_comments_options[disabled_post_types][]"][value="post"]` — Posts checkbox
- `.comments-link` or `.post-comments` — theme comment count link (varies by theme)
- `a[href*="#comments"]` — typical comment count anchor link in post meta

**Implementation hints:**
- Use WP-CLI to seed test data: `wp comment create --post_id=1 --comment_status=approve --comment_content="Test comment"`
- Record the comment count before the test: `const beforeCount = await page.locator('.comments-link').textContent()`
- After configuring the plugin, navigate as a guest context and assert count changed
- Theme-specific: Twenty Twenty-One uses `.entry-meta .comments-link`; Twenty Twenty-Three may use different markup — inspect the theme before writing the selector
- For a reliable count check: `await expect(page.locator('.comments-link')).toContainText('0')`
- For the Page: `await expect(page.locator('.comments-link')).not.toContainText('0')` (and match the actual baseline count)

## Related
- **WordPress Filters:** `get_comments_number` (returns `0` for disabled types), `comments_open`, `comments_array`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.disabled_post_types`
- **Plugin Methods:** `is_post_type_disabled()`, `get_disabled_post_types()`
