---
id: TC-001
title: "Remove Everywhere lifecycle: enable, verify disabled, restore"
feature: disable-everywhere
priority: smoke
tags: [disable-everywhere, settings, frontend, smoke, restore]
type: functional
automation_status: automated
automation_file: "[TC-001-remove-everywhere-lifecycle.spec.ts](TC-001-remove-everywhere-lifecycle.spec.ts)"
created: 2026-03-30
updated: 2026-03-30
---

# TC-001 — Remove Everywhere Lifecycle: Enable, Verify Disabled, Restore

## Summary

Full end-to-end lifecycle test for the "Remove Everywhere" mode. Verifies that:

1. Enabling "Remove Everywhere" persists the setting and removes the comment form from all post types (Posts and Pages) for logged-out visitors — including DOM absence, script absence, and page-source absence.
2. Switching back to "Disable by Post Type" with no types selected correctly re-enables comment functionality, confirming the disable is fully reversible.

This is the primary smoke test for the plugin's core feature.

> **Note:** This test consolidates the scope formerly covered by TC-001 through TC-004. Those IDs are retired; do not reuse them.

## Prerequisites

- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Plugin is in a clean/default state ("Remove Everywhere" is NOT currently active)
- [ ] Test creates its own Post and Page with `comment_status=open` via `requestUtils`

## Test Data

| Field | Value |
|-------|-------|
| Settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Test Post | Created via `requestUtils.createPost()` with `comment_status=open` |
| Test Page | Created via `requestUtils.rest('/wp/v2/pages')` with `comment_status=open` |
| Radio — Remove Everywhere | `input[value="1"]` |
| Radio — Disable by Post Type | `input[value="2"]` |
| Plugin option key | `disable_comments_options.remove_everywhere` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | (Setup) Create a test Post and a test Page with `comment_status=open` via `requestUtils` | Post and Page exist; their URLs are known |
| 2 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads; "Remove Everywhere" radio is NOT selected |
| 3 | Open the test Post URL in a fresh logged-out browser context | `#respond` and `#comment` are visible — comment form IS present |
| 4 | Open the test Page URL in the same logged-out context | `#respond` is visible on the Page too |
| 5 | Return to settings; click the "Remove Everywhere" radio button | Radio becomes selected |
| 6 | Click "Save Changes" | SweetAlert success popup appears and auto-dismisses |
| 7 | Reload the settings page | "Remove Everywhere" radio remains selected — setting persisted |
| 8 | Open the test Post URL in a fresh logged-out context | `#respond` is completely absent from the DOM (not hidden — absent) |
| 9 | Confirm `#comment-form` / `form.comment-form` is absent | No comment form `<form>` element exists in the DOM |
| 10 | Confirm `h3#reply-title` is absent | No "Leave a Reply" heading exists in the DOM |
| 11 | Confirm `comment-reply.js` is not loaded | Zero `<script src="*comment-reply*">` elements in the DOM |
| 12 | Check the page source for `id="respond"` | The string `id="respond"` does not appear anywhere in the HTML source |
| 13 | Open the test Page URL in the same logged-out context | `#respond` and `form.comment-form` are completely absent from the DOM |
| 14 | Verify DB via WP-CLI: `wp option get disable_comments_options --format=json` | `remove_everywhere` is `true` (or truthy) |
| 15 | Return to settings; click the "Disable by Post Type" radio button | Radio selection switches; post-type checkboxes become visible |
| 16 | Ensure no post type checkboxes are checked | All checkboxes unchecked |
| 17 | Click "Save Changes" | SweetAlert success popup appears |
| 18 | Reload the settings page | "Disable by Post Type" radio is selected; no checkboxes are checked |
| 19 | Navigate to the test Post URL (admin context is fine here) | `#respond` IS present; "Leave a Reply" heading, `#comment`, and `#submit` are visible |
| 20 | Inspect the comment form's `action` attribute | Form action contains `wp-comments-post.php` |
| 21 | Verify DB: `wp option get disable_comments_options --format=json` | `remove_everywhere` is `false` or falsy |

## Expected Results

- The "Remove Everywhere" radio button persists after page reload
- Frontend Post and Page pages do not render any comment form elements when active (DOM-absent, not hidden)
- `comment-reply.js` is not loaded when Remove Everywhere is active
- Switching to "Disable by Post Type" with no types selected fully restores the comment form on Posts
- The DB option correctly reflects both transitions
- No JavaScript errors appear in the browser console during save or page load

## Negative / Edge Cases

- Comment form must be absent from the DOM (not hidden via `display:none` or `visibility:hidden`)
- Settings must not silently revert on reload
- After restoring, the comment form must not still be absent (would indicate the plugin failed to release its filters)
- If the test post/page has comments disabled at the individual post level, the form will be absent regardless of plugin state — ensure test content is created with `comment_status=open`

## Playwright Notes

**Settings URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name="disable_comments_options[remove_everywhere]"][value="1"]` — Remove Everywhere radio
- `input[name="disable_comments_options[remove_everywhere]"][value="2"]` — Disable by Post Type radio
- `input[type="checkbox"][name*="disable_comments_options[post_types]"]` — post type checkboxes
- `#respond` — WordPress comment form wrapper
- `#comment-form, form.comment-form` — the comment `<form>` element
- `h3#reply-title` — "Leave a Reply" heading
- `#comment` — comment textarea
- `#submit` — comment submit button
- `#commentform` — comment form (has `action` attribute)
- `script[src*="comment-reply"]` — comment-reply.js script tag

**Implementation hints:**
- Use `requestUtils.createPost()` for the test post and `requestUtils.rest({ path: '/wp/v2/pages', ... })` for the test page — do not use `wpCli()` for content creation
- Use a fresh `browser.newContext()` for logged-out visitor checks so admin session cookies do not interfere
- `await expect(page.locator('#respond')).not.toBeAttached()` — checks element is absent from DOM (preferred over `not.toBeVisible()`)
- `const html = await page.content(); expect(html).not.toContain('id="respond"')` — page source check
- `const count = await page.locator('script[src*="comment-reply"]').count(); expect(count).toBe(0)` — script absence

## Related

- **WordPress Filters:** `comments_open`, `pings_open`, `get_comments_number`, `comments_array`
- **WordPress Actions:** `wp_loaded` → `init_wploaded_filters()`, `admin_init`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
