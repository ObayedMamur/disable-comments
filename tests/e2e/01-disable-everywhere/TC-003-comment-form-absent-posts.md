---
id: TC-003
title: "Comment form is absent on Posts when globally disabled"
feature: disable-everywhere
priority: smoke
tags: [disable-everywhere, frontend, posts, comment-form, smoke]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-003 — Comment Form Is Absent on Posts When Globally Disabled

## Summary
Frontend verification that a single Post does not render the comment form or "Leave a Reply" section when Remove Everywhere is active. This confirms the `comments_open` filter returning false and `remove_post_type_support('post', 'comments')` are working correctly for the Post post type.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Remove Everywhere" mode is currently ACTIVE
- [ ] At least one published Post exists with its individual comment status set to "Open"
- [ ] The Post's WordPress theme supports comment display (uses `comments_template()`)

## Test Data

| Field | Value |
|-------|-------|
| Test post URL | `/?p=1` or `/hello-world/` |
| Post type | `post` |
| Expected `#respond` presence | Absent from DOM |
| Expected `comment-reply.js` | Not enqueued |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Confirm Remove Everywhere is active: navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page shows "Remove Everywhere" radio as selected |
| 2 | Open the WordPress admin post list at `/wp-admin/edit.php` | Post list is displayed with at least one published post |
| 3 | Note the ID or permalink of a published test post (e.g. "Hello World", ID=1) | Post URL is identified (e.g. `/?p=1` or `/hello-world/`) |
| 4 | Navigate to the test post's frontend URL in a new browser tab (visit as logged-out visitor or use a private/incognito window) | Post page loads with full content displayed; HTTP status 200 |
| 5 | Scroll to the bottom of the post content area | The area below the post content shows no comment form and no "Leave a Reply" heading |
| 6 | Use browser DevTools (Inspect) to search the DOM for `#respond` | The `#respond` element does NOT exist in the DOM (not present, not hidden) |
| 7 | Use browser DevTools to search the DOM for `#comment-form` or `form.comment-form` | No comment form element exists in the DOM |
| 8 | Use browser DevTools to search the DOM for `h3#reply-title` or any heading containing "Leave a Reply" | No such heading exists in the DOM |
| 9 | Open browser DevTools Network tab, reload the page, and filter scripts for `comment-reply` | The `comment-reply.js` WordPress script is NOT loaded in the page's network requests |
| 10 | Check the page source (Ctrl+U or View Source) for the string `id="respond"` | The string `id="respond"` does not appear anywhere in the page HTML source |

## Expected Results
- The `#respond` div is completely absent from the DOM (not merely hidden with CSS)
- The `#comment-form` element does not exist in the DOM
- The "Leave a Reply" heading (`h3#reply-title` or similar) is not present
- The `comment-reply.js` script is not enqueued or loaded
- No comment-related HTML (textarea, submit button, name/email fields for comments) is present in the page source

## Negative / Edge Cases
- The comment form must NOT be present but hidden via `display:none` or `visibility:hidden` — it must be entirely absent from the DOM
- If the theme renders a custom comment template that does not use `#respond`, verify the absence of `<form class="comment-form">` or any equivalent
- Existing comments (if any) being visible does not constitute a failure for this test case (that is covered by TC-011/TC-012); only the form absence matters here

## Playwright Notes
**Page URL:** `/?p=1` (or the slug of your test post)

**Key Selectors:**
- `#respond` — WordPress standard comment form wrapper (must NOT be attached)
- `#comment-form, form.comment-form` — the comment `<form>` element
- `h3#reply-title` — "Leave a Reply" heading
- `#comment` — comment textarea
- `#submit` — submit button for comment form
- `script[src*="comment-reply"]` — the comment-reply.js script tag

**Implementation hints:**
- Use `await expect(page.locator('#respond')).not.toBeAttached()` — this checks the element is not in the DOM at all (preferred over `not.toBeVisible()` which allows hidden elements)
- Use `await expect(page.locator('#comment-form')).not.toBeAttached()`
- Check script absence: `const scripts = await page.locator('script[src*="comment-reply"]').count(); expect(scripts).toBe(0)`
- For a logged-out check, use a new browser context: `const context = await browser.newContext(); const page = await context.newPage()`
- View source check in Playwright: `const content = await page.content(); expect(content).not.toContain('id="respond"')`

## Related
- **WordPress Filters:** `comments_open` (priority 20), `comments_array` (priority 20)
- **WordPress Actions:** `wp_loaded` → `init_wploaded_filters()`, `remove_post_type_support('post', 'comments')`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
