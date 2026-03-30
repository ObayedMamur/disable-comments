---
id: TC-026
title: "Existing comments are hidden for disabled post type"
feature: disable-by-post-type
priority: high
tags: [disable-by-post-type, existing-comments, frontend, comments-array]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-026 — Existing Comments Are Hidden for Disabled Post Type

## Summary
Verifies that posts of the disabled type with existing approved comments do not display those comments in the frontend. The `comments_array` filter must return an empty array for disabled types, preventing the comment list from rendering even if comments exist in the database.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one published Post exists with at least 2 approved comments (verify via `WP Admin > Comments`)
- [ ] At least one published Page exists with at least 1 approved comment
- [ ] The active theme renders the comment list when comments exist (uses `wp_list_comments()` or similar)
- [ ] Plugin is NOT currently set to "Remove Everywhere"

## Test Data

| Field | Value |
|-------|-------|
| Settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Sample Post URL | `/?p=1` or `/hello-world/` |
| Sample Page URL | `/?page_id=2` or `/sample-page/` |
| Test Post approved comment content | e.g. "Great post! This is a test comment." |
| Test Page approved comment content | e.g. "Nice page content, test comment here." |
| Disabled post type | `post` |
| Enabled post type | `page` |
| Plugin option key | `disable_comments_options.disabled_post_types` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Confirm test data: navigate to `WP Admin > Comments` and verify the test Post has at least 2 approved comments; note their content | Approved comments are confirmed to exist for both the Post and Page |
| 2 | As a logged-out visitor, navigate to the Post frontend URL BEFORE configuring the plugin | The Post shows its existing comments in the `#comments` list (`.comment-list li` elements are present) |
| 3 | Navigate to the Page frontend URL BEFORE configuring the plugin | The Page shows its existing comments in the `#comments` list |
| 4 | Return to the admin and navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads |
| 5 | Select "Disable by Post Type" radio button | Radio is selected; post-type checkboxes are visible |
| 6 | Check only the "Posts" checkbox; ensure "Pages" is unchecked | Only "Posts" is checked |
| 7 | Click "Save Changes" | Success notification appears; settings are saved |
| 8 | Dismiss the notification | Notification closes |
| 9 | As a logged-out visitor, navigate to the Post frontend URL | Post page loads |
| 10 | Inspect the `#comments` section on the Post | The `.comment-list` is NOT present in the DOM OR contains no `<li>` elements; the known comment content (e.g. "Great post!") is not visible anywhere on the page |
| 11 | Verify the `#respond` form is also absent on the Post | `#respond` is not in the DOM (double-check alongside comment list absence) |
| 12 | Navigate to the Page frontend URL as a logged-out visitor | Page loads |
| 13 | Inspect the `#comments` section on the Page | The `.comment-list` IS present; the known Page comment content is visible in the comment list |

## Expected Results
- Post pages do not render existing comments (`.comment-list` is empty or absent)
- Existing comment text content is not visible anywhere on disabled-type pages
- `#respond` form is also absent on the Post (complementary to TC-020)
- Page pages continue to show their existing approved comments normally
- No PHP errors or JavaScript errors in the browser console
- The database is NOT modified — comments still exist in the DB; only the display is filtered

## Negative / Edge Cases
- The plugin must NOT delete the existing comments from the database — only suppress display; verify comments still exist in `WP Admin > Comments` after the test
- If the theme uses a custom comment template that does not call `wp_list_comments()`, the `comments_array` filter may not apply; document as a theme limitation
- Comments hidden via the `comments_array` filter are still physically present in the database — a DB check via WP-CLI should still return them
- Test must run as a logged-out visitor; admin sessions may bypass display filters
- Pingbacks and trackbacks should also be absent for the disabled type (verify `pings_open` filter applies similarly)

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name="disable_comments_options[remove_everywhere]"][value="2"]` — "Disable by Post Type" radio
- `input[name="disable_comments_options[disabled_post_types][]"][value="post"]` — Posts checkbox
- `#comments` — WordPress comments section container
- `.comment-list` or `ol.comment-list` — the ordered list of comments
- `.comment-list li` — individual comment items
- `#respond` — comment form wrapper

**Implementation hints:**
- Use WP-CLI to seed test comments before the test: `wp comment create --post_id=1 --comment_status=approve --comment_content="Existing test comment A" --comment_author="Tester"`
- Pre-test baseline check: `await expect(guestPage.locator('.comment-list li')).toHaveCount(greaterThan(0))` before enabling the plugin setting
- Post-test check: `await expect(guestPage.locator('.comment-list li')).toHaveCount(0)` OR `await expect(guestPage.locator('.comment-list')).not.toBeAttached()`
- Check comment content is not in the DOM: `await expect(guestPage.locator('body')).not.toContainText('Existing test comment A')`
- Page comment list should still be present: `await expect(guestPage.locator('.comment-list li')).toHaveCount(greaterThan(0))`
- After the test, verify the DB still has the comments: run `wp comment list --post_id=1 --status=approve` and confirm comments still exist

## Related
- **WordPress Filters:** `comments_array` (returns `[]` for disabled types), `comments_open`, `get_comments_number`
- **WordPress Functions:** `wp_list_comments()`, `comments_template()`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.disabled_post_types`
- **Plugin Methods:** `is_post_type_disabled()`, `get_disabled_post_types()`
