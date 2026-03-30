---
id: TC-140
title: "Comment section is absent from post frontend when globally disabled"
feature: frontend-behavior
priority: smoke
tags: [frontend-behavior, global, comment-form, smoke, anonymous, dom]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-140 — Comment Section Is Absent from Post Frontend When Globally Disabled

## Summary
When "Remove Everywhere" is active, the entire comment section — including the comment form (`#respond`, `.comment-form`) and the "Leave a Reply" heading — must not be rendered in the DOM on any post page. This is the primary frontend verification test for the plugin's core functionality.

---

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one published Post exists with its individual comment status set to "Open"
- [ ] The active WordPress theme supports comment display via `comments_template()`

---

## Test Data

| Field | Value |
|-------|-------|
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Test post URL | `/?p=1` or `/sample-post/` |
| Post type | `post` |
| Test user | Anonymous (logged-out visitor) |
| Expected `#respond` in DOM | Absent |
| Expected `.comment-form` in DOM | Absent |
| Expected `comment-reply.js` loaded | No |

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | The Disable Comments settings page loads without errors |
| 2 | Select the "Remove Everywhere" option (first radio button) | The "Remove Everywhere" radio is selected |
| 3 | Click "Save Changes" | A success notice confirms settings have been saved |
| 4 | Open a new private/incognito browser window (simulating an anonymous visitor) | A new browser session with no admin cookies is open |
| 5 | Navigate to a published post URL, e.g. `/?p=1` | The post page loads with status 200 and post content is displayed |
| 6 | Scroll to the bottom of the post content area | No comment form, no "Leave a Reply" heading, and no comment list wrapper is visible in the rendered page |
| 7 | Open browser DevTools and inspect the DOM for an element with `id="respond"` | The `#respond` element is completely absent from the DOM (not hidden with CSS — not present at all) |
| 8 | In DevTools, search the DOM for `form.comment-form` or `form#commentform` | No comment form element exists anywhere in the DOM |
| 9 | In DevTools, search for any heading element containing the text "Leave a Reply" | No such heading exists (e.g. `h3#reply-title`, `h2.comment-reply-title`) |
| 10 | View the full page source (Ctrl+U) and search for the string `id="respond"` | The string `id="respond"` does not appear anywhere in the page HTML source |
| 11 | View the page source and search for `comment-reply` | The `comment-reply.js` script tag is absent from the source |
| 12 | Repeat steps 5–11 for a published Page (e.g. `/sample-page/`) | Same result: no comment section, no `#respond`, no `.comment-form` |

---

## Expected Results
- The `#respond` div is completely absent from the DOM on all tested post URLs
- The `.comment-form` / `#commentform` element does not exist in the DOM
- The "Leave a Reply" heading (`h3#reply-title`, `h2.comment-reply-title`, or equivalent) is not present
- The `comment-reply.js` script is not enqueued or loaded in the page
- No comment-related form fields (textarea `#comment`, `#submit` button, name/email inputs) are present in the page source
- The behavior is consistent whether the visitor is logged out or logged in as a non-admin

---

## Negative / Edge Cases
- The comment form must NOT be present but hidden via `display:none` or `visibility:hidden` — it must be entirely absent from the DOM
- If the active theme uses a custom comment template that does not use the standard `#respond` wrapper, also verify absence of `<form class="comment-form">` and any `<textarea>` with `id="comment"`
- The absence of the comment section must not affect page rendering; the rest of the post content must display normally
- Existing comments in the database are not the subject of this test (covered by TC-142); only the form and section absence matters here

---

## Playwright Notes
**Page URL:** `/?p=1` (or the slug of your test post)

**Key Selectors:**
- `#respond` — WordPress standard comment form wrapper (must NOT be attached)
- `form.comment-form, form#commentform` — the comment `<form>` element
- `h3#reply-title, h2.comment-reply-title` — "Leave a Reply" heading
- `#comment` — comment textarea
- `#submit` — comment form submit button
- `script[src*="comment-reply"]` — the comment-reply.js script tag

**Implementation hints:**
- Use `await expect(page.locator('#respond')).not.toBeAttached()` — checks the element is not in the DOM at all (preferred over `.not.toBeVisible()` which still allows hidden elements)
- Use `await expect(page.locator('form.comment-form')).not.toBeAttached()`
- Verify script absence: `const count = await page.locator('script[src*="comment-reply"]').count(); expect(count).toBe(0)`
- To simulate a logged-out visitor: `const context = await browser.newContext(); const anonPage = await context.newPage();`
- Page source assertion: `const html = await page.content(); expect(html).not.toContain('id="respond"');`
- For multi-post-type coverage, parameterise the test over an array of post URLs: `[{ url: '/?p=1', label: 'post' }, { url: '/sample-page/', label: 'page' }]`

---

## Related
- **WordPress Filters:** `comments_open` (priority 20), `comments_array` (priority 20)
- **WordPress Actions:** `wp_loaded` → `init_wploaded_filters()`, `remove_post_type_support('post', 'comments')`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
