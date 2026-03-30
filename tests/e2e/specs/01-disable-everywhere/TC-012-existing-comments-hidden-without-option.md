---
id: TC-012
title: "Existing comments are hidden when \"Show existing comments\" is disabled"
feature: disable-everywhere
priority: medium
tags: [disable-everywhere, show-existing-comments, frontend, comment-visibility, default-state]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-012 — Existing Comments Are Hidden When "Show Existing Comments" Is Disabled

## Summary
When Remove Everywhere is ON and "Show existing comments" is disabled (the default state), ALL comments including previously approved ones must be hidden from the frontend. The plugin's `comments_array` filter at priority 20 returns an empty array when `show_existing_comments` is false, causing the theme to render no comments even if approved comments exist in the database.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Remove Everywhere" mode is currently ACTIVE
- [ ] "Show existing comments" checkbox is UNCHECKED (disabled — default state)
- [ ] At least one published Post exists with at least one APPROVED comment in the database

## Test Data

| Field | Value |
|-------|-------|
| Test post URL | `/?p=1` or `/hello-world/` |
| Known approved comment text | e.g. "This is a test comment." |
| Plugin option | `disable_comments_options.show_existing_comments = false` (or absent) |
| Filter mechanism | `comments_array` at priority 20 returns `[]` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/edit-comments.php` and confirm at least one approved comment exists for the test post | At least one approved comment is listed with "Approved" status; note the comment text |
| 2 | Navigate to the settings page `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads |
| 3 | Confirm "Remove Everywhere" radio is selected | "Remove Everywhere" is active |
| 4 | Confirm the "Show existing comments" checkbox is UNCHECKED (disabled) | The checkbox is unchecked; if it was checked, uncheck it and click "Save Changes" |
| 5 | If a save was needed in step 4: dismiss the success notification and reload the settings page | Settings page reloads; "Remove Everywhere" is selected and "Show existing comments" is unchecked |
| 6 | Navigate to the test post's frontend URL (e.g. `/hello-world/`) in an incognito/private browser window | Post page loads with full content |
| 7 | Scroll to the area below the post content where comments would normally appear | No comments are visible; the comments section is empty or the entire comments area is absent |
| 8 | Look specifically for the known approved comment text (e.g. "This is a test comment.") | The known comment text is NOT present anywhere on the page |
| 9 | Open browser DevTools and inspect the DOM for `ol.comment-list` | Either the element does not exist in the DOM, or it exists but contains no `<li>` child elements |
| 10 | Search the DOM for any `li.comment` elements | No `li.comment` elements exist in the DOM |
| 11 | Inspect the DOM for `#respond` | The `#respond` element also does NOT exist in the DOM (comment form is also absent) |
| 12 | (Control) Navigate to `/wp-admin/edit-comments.php` and confirm the approved comment still exists in the database | The approved comment is still listed in WordPress admin — it has NOT been deleted; only filtered from display |
| 13 | (Contrast with TC-011) Navigate back to settings, check "Show existing comments", save | Settings saved with show_existing_comments = true |
| 14 | Revisit the test post frontend URL | The existing approved comment IS now visible on the page — confirming the `show_existing_comments` flag controls visibility |

## Expected Results
- Existing approved comments are NOT rendered on the post frontend when "Show existing comments" is disabled
- The `<ol class="comment-list">` either does not exist in the DOM or contains zero `<li.comment>` elements
- The known approved comment text is not found anywhere in the page HTML
- The `#respond` form is also absent (as expected with Remove Everywhere)
- The comments remain intact in the WordPress database — the plugin only filters display, not stored data
- After enabling "Show existing comments" (step 13-14), the existing comments become visible again

## Negative / Edge Cases
- The comments must NOT appear even if the browser cache has a stale version of the page — use an incognito window or clear cache before testing
- Pending or spam comments should not appear regardless of the `show_existing_comments` setting — their absence here is expected, not specific to the plugin
- If the theme does not render a comment section at all for posts (some themes omit `comments_template()` on certain templates), the absence of comments is not caused by the plugin — verify with the baseline check (TC-011, steps 13-14)
- The `comments_array` filter returning `[]` does not delete data from `wp_comments` table — this must be confirmed in step 12

## Playwright Notes
**Page URL:** `/hello-world/` or `/?p=1`

**Key Selectors:**
- `ol.comment-list` — comment list (must be absent or empty)
- `li.comment` — individual comment item (count must be 0)
- `#respond` — comment form (must NOT be attached)
- `.comment-body p` — comment text paragraphs (must not contain known comment text)
- `#comments` — outer comments section wrapper

**Implementation hints:**
- Check no comment items exist:
  ```js
  const commentItems = await page.locator('li.comment').count();
  expect(commentItems).toBe(0);
  ```
- Alternatively assert the list is absent: `await expect(page.locator('ol.comment-list')).not.toBeAttached()`
- Verify known comment text is absent from page source:
  ```js
  const html = await page.content();
  expect(html).not.toContain('This is a test comment.');
  ```
- Assert form also absent: `await expect(page.locator('#respond')).not.toBeAttached()`
- For the control check (step 12), can use WP-CLI in a test fixture: `wp comment list --status=approve --post_id=1` to confirm comment still in DB
- Use `browser.newContext()` for incognito-equivalent testing to avoid cached responses

## Related
- **WordPress Filters:** `comments_array` (priority 20) → `filter_existing_comments()` returns `[]` when `show_existing_comments` is false
- **WordPress Actions:** `wp_loaded` → `init_wploaded_filters()`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.show_existing_comments`
