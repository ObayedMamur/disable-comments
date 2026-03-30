---
id: TC-003
title: "Comment form is absent on Posts when globally disabled"
feature: disable-everywhere
priority: smoke
tags: [disable-everywhere, frontend, posts, comment-form, smoke]
type: functional
automation_status: automated
automation_file: "[TC-003-comment-form-absent-posts.spec.ts](TC-003-comment-form-absent-posts.spec.ts)"
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
- [ ] Plugin is in a clean/default state (Remove Everywhere is NOT currently active)
- [ ] Test creates its own Post with comments open via WP-CLI
- [ ] "Remove Everywhere" is enabled mid-test via UI
- [ ] The WordPress theme supports comment display (uses `comments_template()`)

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
| 1 | (Setup) Create a test Post with `comment_status=open` via WP-CLI | Post exists and its URL is known |
| 2 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads; "Remove Everywhere" radio is NOT selected (clean state confirmed) |
| 3 | Open the test post URL in a fresh (logged-out) browser context | Post page loads; `#respond` and `#comment` are visible — comment form IS present |
| 4 | Return to settings; click the "Remove Everywhere" radio and click "Save Changes" | SweetAlert success popup appears; setting saved |
| 5 | Open the test post URL again in a fresh (logged-out) browser context | Post page loads |
| 6 | Inspect the DOM for `#respond` | `#respond` does NOT exist in the DOM (not present, not hidden) |
| 7 | Inspect the DOM for `#comment-form` or `form.comment-form` | No comment form element exists in the DOM |
| 8 | Inspect the DOM for `h3#reply-title` | No "Leave a Reply" heading exists in the DOM |
| 9 | Check the page for `script[src*="comment-reply"]` | `comment-reply.js` is NOT loaded |
| 10 | Read the full page source | The string `id="respond"` does not appear anywhere in the HTML source |

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
