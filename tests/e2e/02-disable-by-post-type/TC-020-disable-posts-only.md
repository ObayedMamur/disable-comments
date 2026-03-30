---
id: TC-020
title: "Disable comments for Posts post type only"
feature: disable-by-post-type
priority: smoke
tags: [disable-by-post-type, posts, settings, frontend, smoke]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-020 — Disable Comments for "Posts" Post Type Only

## Summary
Verifies that selecting "Disable by Post Type" with only the "Posts" checkbox enabled correctly disables comments on Post pages while leaving Pages unaffected. This is the foundational smoke test for selective post-type disabling.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one published Post exists with comments open (e.g. "Hello World")
- [ ] At least one published Page exists with comments open (e.g. "Sample Page")
- [ ] Plugin is NOT currently set to "Remove Everywhere" (or reset to default)

## Test Data

| Field | Value |
|-------|-------|
| Settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Sample Post URL | `/?p=1` or `/hello-world/` |
| Sample Page URL | `/?page_id=2` or `/sample-page/` |
| Radio option value | `2` (Disable by Post Type) |
| Post type checkbox | `post` |
| Plugin option key | `disable_comments_options.disabled_post_types` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads; the "Disable" tab is active |
| 2 | Click the "Disable by Post Type" radio button | The radio becomes selected; a section with post-type checkboxes appears (Posts, Pages, Media at minimum) |
| 3 | Ensure only the "Posts" checkbox is checked; uncheck "Pages" and "Media" if they are checked | Only the "Posts" checkbox is in a checked state; "Pages" and "Media" checkboxes are unchecked |
| 4 | Click the "Save Changes" button | An AJAX POST is sent to `admin-ajax.php` with action `disable_comments_save_settings`; a success notification appears |
| 5 | Dismiss the success notification (if modal) | Notification closes; settings page remains |
| 6 | Reload the settings page (`/wp-admin/admin.php?page=disable_comments_settings`) | Page reloads cleanly |
| 7 | Confirm "Disable by Post Type" radio is still selected and only "Posts" checkbox is checked | "Disable by Post Type" is active; "Posts" checkbox is checked; "Pages" and "Media" are unchecked |
| 8 | Open a new tab and navigate to a published Post frontend URL (e.g. `/hello-world/`) | Post page loads successfully |
| 9 | Scroll to the bottom of the post and inspect the comments section | The `#respond` div is NOT present in the DOM; no "Leave a Reply" heading, no comment text field, no submit button |
| 10 | Navigate to a published Page frontend URL (e.g. `/sample-page/`) | Page loads successfully |
| 11 | Scroll to the bottom of the page and inspect the comments section | The `#respond` div IS present in the DOM; the comment form renders normally with the reply textarea and submit button visible |
| 12 | (Optional) Verify via WP-CLI: `wp option get disable_comments_options --format=json` | The JSON shows `"remove_everywhere": false` and `"disabled_post_types": ["post"]` |

## Expected Results
- "Disable by Post Type" radio is selected and persists after page reload
- Only the "Posts" checkbox is checked in the settings UI after reload
- Post frontend pages have no comment form (`#respond` absent from DOM)
- Page frontend pages retain the fully functional comment form
- `disable_comments_options.remove_everywhere` is `false`
- `disable_comments_options.disabled_post_types` contains `["post"]`
- No JavaScript errors in the browser console during save

## Negative / Edge Cases
- The comment form on Pages must NOT be hidden via CSS — it must be fully present and functional in the DOM
- The Post comment form must NOT appear anywhere, even if the post has `comment_status = open` in the database
- Saving with no post types selected must NOT be allowed (validation should prevent it)

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name="disable_comments_options[remove_everywhere]"][value="2"]` — "Disable by Post Type" radio button
- `input[name="disable_comments_options[disabled_post_types][]"][value="post"]` — Posts checkbox
- `input[name="disable_comments_options[disabled_post_types][]"][value="page"]` — Pages checkbox
- `input[name="disable_comments_options[disabled_post_types][]"][value="attachment"]` — Media checkbox
- `#disable-comments-settings-form` — settings form container
- `button[type="submit"], input[type="submit"]` — Save Changes button
- `.swal2-popup` or `.notice-success` — success notification after save
- `#respond` — WordPress comment form wrapper (must be absent on Post frontend)
- `#comments` — WordPress comments section container

**Implementation hints:**
- After selecting the "Disable by Post Type" radio, wait for the checkbox section to become visible before interacting with checkboxes: `await page.waitForSelector('input[value="post"]', { state: 'visible' })`
- Use `await expect(page.locator('#respond')).not.toBeAttached()` on the Post page (not just hidden)
- Use `await expect(page.locator('#respond')).toBeVisible()` on the Page page
- For persistence check: `await page.reload()` and re-query both the radio and the Posts checkbox `checked` attribute
- Intercept the AJAX save to assert payload contains `disabled_post_types[]=post` and does NOT contain `remove_everywhere=1`
- Consider testing the Page as a logged-out visitor context to bypass any admin-only visibility quirks

## Related
- **WordPress Filters:** `comments_open`, `pings_open`, `get_comments_number`, `comments_array`
- **WordPress Actions:** `remove_post_type_support` (for `comments` and `trackbacks`)
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.disabled_post_types`, `disable_comments_options.remove_everywhere`
- **Plugin Methods:** `is_post_type_disabled('post')`, `get_disabled_post_types()`
