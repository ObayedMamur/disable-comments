---
id: TC-005
title: "Comment count returns 0 for all posts when globally disabled"
feature: disable-everywhere
priority: high
tags: [disable-everywhere, frontend, comment-count, filter]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-005 — Comment Count Returns 0 for All Posts When Globally Disabled

## Summary
Verifies that the `get_comments_number` filter returns 0 for all posts when Remove Everywhere is active. The plugin hooks `get_comments_number` at priority 20 and unconditionally returns 0, overriding the actual stored comment count. This affects any theme element that displays comment counts (e.g. "0 Comments" post meta, comment count badges, widgets).

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Remove Everywhere" mode is currently ACTIVE
- [ ] At least one published Post exists that has one or more APPROVED comments stored in the database (actual count > 0)
- [ ] The active theme displays comment count in post metadata (e.g. "3 Comments" link in post meta area)

## Test Data

| Field | Value |
|-------|-------|
| Test post URL | `/?p=1` or `/hello-world/` |
| Actual stored comment count | At least 1 approved comment in DB |
| Expected displayed count | 0 (or "No Comments" / "0 Comments") |
| Filter hook | `get_comments_number` at priority 20 |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Ensure the test post has at least one approved comment: navigate to `/wp-admin/edit-comments.php` and confirm at least one approved comment exists for the test post | Approved comment(s) are listed for the test post; the actual DB count is ≥ 1 |
| 2 | Note the actual comment count for the test post (e.g. "2 approved comments") | Comment count recorded for comparison |
| 3 | Confirm Remove Everywhere is active: navigate to `/wp-admin/admin.php?page=disable_comments_settings` | "Remove Everywhere" radio is selected |
| 4 | Navigate to the blog index / homepage (e.g. `/`) or post archive where post metadata with comment counts is displayed | Blog index or archive page loads showing a list of posts |
| 5 | Locate the test post in the listing and observe the comment count displayed in the post metadata | The comment count shown is `0` — it does NOT reflect the actual stored count (e.g. does not show "2 Comments") |
| 6 | Navigate directly to the test post's frontend URL (e.g. `/hello-world/`) | Post single page loads |
| 7 | Observe the comment count anywhere it is displayed on the single post page (post meta, comments section heading, etc.) | Any comment count display shows `0` or "No Comments"; not the actual database count |
| 8 | Open browser DevTools and inspect the DOM for any element rendering the comment count (e.g. `a.post-comments-link`, `.entry-meta .comments-link`) | The text content of comment count elements shows `0` or equivalent zero state |
| 9 | (Optional / Advanced) Via WP-CLI or phpMyAdmin, confirm the actual stored `comment_count` on the post is still the real number (not 0) | The database still holds the real comment count — the plugin only filters the output, not the DB value |
| 10 | Disable Remove Everywhere (switch to "Disable by Post Type" with nothing selected, save), then revisit the post | The real comment count (e.g. "2 Comments") is now displayed again — confirming the plugin was responsible for returning 0 |

## Expected Results
- Any theme-rendered comment count for posts displays `0` or "No Comments" when Remove Everywhere is active
- The `get_comments_number()` WordPress function returns `0` for any post regardless of the actual database value
- The database `comment_count` column on the `wp_posts` table is NOT modified — only the PHP filter return value is overridden
- After disabling Remove Everywhere, the real count reappears in the theme

## Negative / Edge Cases
- If the active theme does not display comment counts anywhere, this test cannot be verified visually — use WP-CLI (`wp post get 1 --field=comment_count`) to confirm the DB value is untouched, and use a `wp_die` / debugging hook to confirm the filter fires
- The plugin must NOT permanently set the `comment_count` column to 0 in the database — only the filter output should be 0
- A comment count display of "Comments Off" or "Commenting is closed" is a different condition from the count being 0; this test focuses on the numeric count being `0`

## Playwright Notes
**Page URL:** `/` (blog index) and `/?p=1` (single post)

**Key Selectors:**
- `.entry-meta a[href*="#comments"]` — comment count link in post metadata (theme-dependent)
- `.comments-link` — common class for comment count link
- `span.comments-count` — count span (theme-dependent)
- `h2.comments-title` — heading inside comments section on single post (e.g. "2 thoughts on...")
- `.comment-count` — generic comment count element

**Implementation hints:**
- Comment count selectors are highly theme-dependent; inspect the DOM of a post page with the plugin disabled first to find the exact selector used by the test theme
- `await expect(page.locator('.comments-link')).toContainText('0')` — or check for the equivalent "no comments" text
- For the "heading inside comments section" check: `await expect(page.locator('h2.comments-title')).not.toBeAttached()` when count is 0 (some themes hide the section entirely)
- The filter `get_comments_number` is a PHP-side filter; Playwright cannot directly assert it — assert the rendered output in the DOM
- Consider pairing with a WP-CLI assertion: `await exec('wp post get 1 --field=comment_count')` to confirm DB is unchanged

## Related
- **WordPress Filters:** `get_comments_number` (priority 20) → `filter_comments_number()` returns `0`
- **WordPress Actions:** `wp_loaded` → `init_wploaded_filters()`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
