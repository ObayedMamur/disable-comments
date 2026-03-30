---
id: TC-011
title: "\"Show existing comments\" option displays old comments while blocking new ones"
feature: disable-everywhere
priority: high
tags: [disable-everywhere, show-existing-comments, frontend, comment-visibility]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-011 — "Show Existing Comments" Option Displays Old Comments While Blocking New Ones

## Summary
When "Remove Everywhere" is ON and "Show existing comments" is also enabled, previously submitted approved comments must still be visible on post pages while the comment submission form remains absent. When `show_existing_comments` is true, the `comments_array` filter does NOT return an empty array, allowing existing comments to render. The `comments_open` filter still returns false, so no new comments can be submitted.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Remove Everywhere" mode is currently ACTIVE
- [ ] At least one published Post exists with at least one APPROVED comment already in the database
- [ ] The approved comment's text is known (for visual verification)
- [ ] The active theme renders comments via `comments_template()` and displays existing comments in a `<ol class="comment-list">` or similar

## Test Data

| Field | Value |
|-------|-------|
| Test post URL | `/?p=1` or `/hello-world/` |
| Known approved comment text | e.g. "This is a test comment." |
| Plugin option | `disable_comments_options.show_existing_comments = true` |
| Settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/edit-comments.php` and confirm at least one approved comment exists for the test post | At least one approved comment is listed; note its text (e.g. "This is a test comment.") and the parent post |
| 2 | Navigate to the settings page `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads on the "Disable" tab |
| 3 | Ensure "Remove Everywhere" radio is selected | "Remove Everywhere" is the active selection |
| 4 | Locate the "Show existing comments" checkbox on the Disable tab and ensure it is CHECKED (enable it if not already) | The "Show existing comments" checkbox is checked |
| 5 | Click "Save Changes" | AJAX POST is sent; success notification appears |
| 6 | Dismiss the notification; reload the settings page | Settings page reloads; "Remove Everywhere" is selected and "Show existing comments" is still checked |
| 7 | Navigate to the test post's frontend URL (e.g. `/hello-world/`) in a new tab or incognito window | Post page loads |
| 8 | Scroll to the comments section of the post | The existing approved comment IS visible on the page (e.g. the text "This is a test comment." is rendered in the comments list) |
| 9 | Inspect the comments list for the known comment content | The comment's author name, comment text, and date are rendered in the `<ol class="comment-list">` or theme-equivalent element |
| 10 | Check for the comment form in the same area | The `#respond` div and `#comment-form` are NOT present in the DOM; no "Leave a Reply" heading is shown |
| 11 | Inspect the DOM for `#respond` | The element `#respond` does NOT exist in the DOM — new comment submission is blocked |
| 12 | Now disable "Show existing comments": return to settings, uncheck the checkbox, save | Settings saved with "Show existing comments" unchecked |
| 13 | Revisit the test post frontend URL | Post page loads |
| 14 | Scroll to the comments section | The previously visible existing comment is now HIDDEN — the comments list is empty or the entire comments section is absent |

## Expected Results
- With both "Remove Everywhere" ON and "Show existing comments" ON:
  - Existing approved comments ARE rendered in the comments list on the post page
  - The comment form (`#respond`, "Leave a Reply") is NOT in the DOM (new submissions blocked)
- With "Remove Everywhere" ON and "Show existing comments" OFF:
  - Existing comments are NOT shown (controlled by TC-012)
- The transition between states (steps 12-14) confirms the checkbox setting is being respected

## Negative / Edge Cases
- Comments with "pending" or "spam" status must NOT appear even with "Show existing comments" enabled — only approved comments should be visible
- The comment form must NOT appear even when existing comments are visible; the `comments_open` filter still returns false regardless of `show_existing_comments`
- If the theme's `comments_template()` is not called for posts, or if the theme does not render comments, this test may need to be run on a theme that does support comment display (e.g. Twenty Twenty-Three)
- Nested/threaded replies of approved comments should also be visible when the parent comment is visible

## Playwright Notes
**Page URL:** `/?p=1` or `/hello-world/`

**Key Selectors:**
- `#respond` — comment form wrapper (must NOT be attached)
- `ol.comment-list` — the comments list (must be present and contain items)
- `ol.comment-list li.comment` — individual comment items
- `.comment-body p` — the paragraph containing comment text
- `input[name="disable_comments_options[show_existing]"]` or similar — the checkbox on the settings page (inspect to find the exact name attribute)
- `#comments` — the outer comments section container

**Implementation hints:**
- Settings checkbox selector — inspect the actual HTML to find it:
  ```js
  await page.locator('input[type="checkbox"][name*="show_existing"]').check();
  ```
- Assert existing comments visible:
  ```js
  await expect(page.locator('ol.comment-list li.comment')).toBeVisible();
  await expect(page.locator('.comment-body')).toContainText('This is a test comment.');
  ```
- Assert form absent: `await expect(page.locator('#respond')).not.toBeAttached()`
- After unchecking and saving, verify hidden: `await expect(page.locator('ol.comment-list')).not.toBeAttached()` (or `.not.toBeVisible()` depending on theme rendering)
- Use a new browser context for the logged-out frontend check to avoid admin bar interference

## Related
- **WordPress Filters:** `comments_array` (priority 20) → `filter_existing_comments()` — returns `[]` when `show_existing_comments` is false, passes through when true; `comments_open` (priority 20) → `filter_comment_status()` → always returns false
- **WordPress Actions:** `wp_loaded` → `init_wploaded_filters()`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.show_existing_comments`
