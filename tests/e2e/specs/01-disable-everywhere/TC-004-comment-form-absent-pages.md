---
id: TC-004
title: "Comment form is absent on Pages when globally disabled"
feature: disable-everywhere
priority: high
tags: [disable-everywhere, frontend, pages, comment-form]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-004 — Comment Form Is Absent on Pages When Globally Disabled

## Summary
Frontend verification that a WordPress Page post type does not render the comment form or "Leave a Reply" section when Remove Everywhere is active. This confirms that `remove_post_type_support()` and the `comments_open` filter apply to the `page` post type, not only `post`.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Remove Everywhere" mode is currently ACTIVE
- [ ] At least one published Page exists with its individual comment status set to "Open"
- [ ] The Page is accessible at a known URL
- [ ] The active WordPress theme supports comment display on pages (uses `comments_template()`)

## Test Data

| Field | Value |
|-------|-------|
| Test page URL | `/?page_id=2` or `/sample-page/` |
| Post type | `page` |
| Expected `#respond` presence | Absent from DOM |
| Expected comment form | Absent from DOM |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Confirm Remove Everywhere is active: navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page shows "Remove Everywhere" radio as selected |
| 2 | Navigate to `/wp-admin/edit.php?post_type=page` to view the Pages list | Pages list is displayed |
| 3 | Identify a published Page that has comments open at the individual post level; note its permalink (e.g. `/sample-page/` or `/?page_id=2`) | The page URL is confirmed (check Edit Page → Discussion box to confirm comments are set to "Open") |
| 4 | Navigate to the Page frontend URL in an incognito/private browser window or as a logged-out visitor | Page loads with full content; HTTP status 200 |
| 5 | Scroll to the bottom of the page content area | No comment form or comments section is visible below the page content |
| 6 | Open browser DevTools and search the DOM for an element with `id="respond"` | No element with `id="respond"` exists in the DOM |
| 7 | Search the DOM for `form.comment-form` or `#comment-form` | No comment form `<form>` element exists in the DOM |
| 8 | Search the DOM for any heading containing text "Leave a Reply" | No such heading is found in the DOM |
| 9 | Open the browser DevTools Console and run: `document.getElementById('respond')` | Returns `null` — the element does not exist in the DOM |
| 10 | View the page source and search for `id="respond"` or `class="comment-form"` | Neither string appears in the HTML source |

## Expected Results
- The `#respond` element is completely absent from the DOM on the Page frontend
- The `#comment-form` / `form.comment-form` does not exist in the DOM
- No "Leave a Reply" heading is present
- No comment textarea, name, email, or submit fields related to commenting are rendered
- The behavior matches TC-003 (Posts) — the plugin treats all post types equally when Remove Everywhere is on

## Negative / Edge Cases
- The comment form must NOT be present but hidden — it must not be in the DOM at all
- Themes that do not call `comments_template()` on pages will not show a comment form regardless of plugin state; confirm the theme does render comments on pages under normal conditions before running this test
- The absence of comments on a page that has "Discussion" disabled at the individual post level is normal WordPress behavior, not a plugin behavior — ensure the test page has comments enabled individually

## Playwright Notes
**Page URL:** `/sample-page/` or `/?page_id=2`

**Key Selectors:**
- `#respond` — WordPress standard comment form wrapper (must NOT be attached)
- `form.comment-form, #comment-form` — comment submission form
- `h3#reply-title, h2.comment-reply-title` — "Leave a Reply" heading variants
- `#comment` — comment textarea
- `#submit` — comment submit button

**Implementation hints:**
- `await expect(page.locator('#respond')).not.toBeAttached()` — confirms element is absent from DOM entirely
- Verify with page source: `const html = await page.content(); expect(html).not.toContain('id="respond"')`
- For a clean logged-out test context: `const context = await browser.newContext({ storageState: undefined })`
- If `#respond` is conditionally rendered by the theme only when comments are open, this test will pass by default; use TC-002 as a control to verify the form DOES appear when the plugin is set back to "Disable by Post Type" with no types selected

## Related
- **WordPress Filters:** `comments_open` (priority 20), `comments_array` (priority 20)
- **WordPress Actions:** `wp_loaded` → `init_wploaded_filters()`, `remove_post_type_support('page', 'comments')`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
