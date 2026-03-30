---
id: TC-021
title: "Disable comments for Pages post type only"
feature: disable-by-post-type
priority: high
tags: [disable-by-post-type, pages, settings, frontend]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-021 — Disable Comments for "Pages" Post Type Only

## Summary
Verifies that selecting "Disable by Post Type" with only the "Pages" checkbox disables comments exclusively on Page post type pages, while Post post type pages continue to show a fully functional comment form.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one published Post exists with comments open (e.g. "Hello World")
- [ ] At least one published Page exists with comments open (e.g. "Sample Page")
- [ ] Plugin is NOT currently set to "Remove Everywhere"

## Test Data

| Field | Value |
|-------|-------|
| Settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Sample Post URL | `/?p=1` or `/hello-world/` |
| Sample Page URL | `/?page_id=2` or `/sample-page/` |
| Radio option value | `2` (Disable by Post Type) |
| Post type checkbox | `page` |
| Plugin option key | `disable_comments_options.disabled_post_types` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads with the "Disable" tab active |
| 2 | Click the "Disable by Post Type" radio button | The radio becomes selected; the post-type checkbox group becomes visible |
| 3 | Uncheck "Posts" and "Media" checkboxes if checked; check only the "Pages" checkbox | Only "Pages" checkbox is in a checked state |
| 4 | Click the "Save Changes" button | An AJAX request is sent and a success notification appears |
| 5 | Dismiss the success notification | Notification closes |
| 6 | Reload the settings page | Page reloads cleanly |
| 7 | Confirm "Disable by Post Type" radio is still selected and only "Pages" checkbox is checked | Settings persisted correctly: "Pages" is checked, "Posts" and "Media" are unchecked |
| 8 | Navigate to a published Page frontend URL (e.g. `/sample-page/`) | Page loads successfully |
| 9 | Inspect the comments section at the bottom of the page | The `#respond` div is NOT present in the DOM; no "Leave a Reply" heading or comment input is visible |
| 10 | Navigate to a published Post frontend URL (e.g. `/hello-world/`) | Post page loads successfully |
| 11 | Inspect the comments section at the bottom of the post | The `#respond` div IS present in the DOM; the comment form renders with the text area, name/email fields, and submit button |
| 12 | Submit a test comment on the Post to verify it is fully functional (optional) | Comment submission proceeds normally without errors (subject to moderation settings) |

## Expected Results
- "Disable by Post Type" radio persists after reload
- Only the "Pages" checkbox is checked after reload
- Page frontend has no comment form (`#respond` absent from DOM)
- Post frontend retains the fully functional comment form
- `disable_comments_options.remove_everywhere` is `false`
- `disable_comments_options.disabled_post_types` contains `["page"]`
- No JavaScript errors in the browser console

## Negative / Edge Cases
- A Post with `comment_status = open` in the database must still render the form — the plugin must NOT disable Posts when only Pages is selected
- A Page with `comment_status = closed` before this test would skew results; ensure the test Page has comments open at the WordPress level
- The comment form absence on Pages must be a DOM removal, not a CSS `display:none`

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name="disable_comments_options[remove_everywhere]"][value="2"]` — "Disable by Post Type" radio button
- `input[name="disable_comments_options[disabled_post_types][]"][value="page"]` — Pages checkbox
- `input[name="disable_comments_options[disabled_post_types][]"][value="post"]` — Posts checkbox
- `input[name="disable_comments_options[disabled_post_types][]"][value="attachment"]` — Media checkbox
- `button[type="submit"], input[type="submit"]` — Save Changes button
- `.swal2-popup` or `.notice-success` — success notification
- `#respond` — comment form wrapper on frontend
- `#comment` — the comment textarea within `#respond`

**Implementation hints:**
- After selecting the radio, use `await page.waitForSelector('.post-type-checkboxes', { state: 'visible' })` (adjust selector to match actual wrapper class)
- Uncheck the Posts checkbox explicitly: `await page.uncheck('input[value="post"]')` before checking Pages
- After reload, assert `await page.locator('input[value="page"]').isChecked()` is `true` and `await page.locator('input[value="post"]').isChecked()` is `false`
- On the Page frontend: `await expect(page.locator('#respond')).not.toBeAttached()`
- On the Post frontend: `await expect(page.locator('#respond')).toBeVisible()`
- Use a separate browser context or an incognito page to view the frontend as a logged-out visitor

## Related
- **WordPress Filters:** `comments_open`, `pings_open`, `get_comments_number`, `comments_array`
- **WordPress Actions:** `remove_post_type_support` (for `comments` and `trackbacks` on `page`)
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.disabled_post_types`, `disable_comments_options.remove_everywhere`
- **Plugin Methods:** `is_post_type_disabled('page')`, `get_disabled_post_types()`
