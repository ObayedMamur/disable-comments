---
id: TC-024
title: "Enabled post type retains comment form when others are disabled"
feature: disable-by-post-type
priority: smoke
tags: [disable-by-post-type, pages, frontend, smoke, negative-check]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-024 — Enabled Post Type Retains Comment Form When Others Are Disabled

## Summary
This is a critical negative check verifying that the plugin's selective disabling does not produce collateral damage. When only "Posts" are disabled, Pages must continue to show a fully functional comment form — the `comments_open` filter must return `true` for enabled types, and `#respond` must be fully present and interactive in the DOM.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one published Page exists with `comment_status = open` at the WordPress level
- [ ] The Page has discussion enabled: confirm in the Page edit screen under "Discussion" that "Allow comments" is checked
- [ ] The active WordPress theme renders the comment form on Pages (i.e., the theme calls `comments_template()`)
- [ ] Plugin is currently set to "Disable by Post Type" with only "Posts" disabled (or configure it in Step 1–5)

## Test Data

| Field | Value |
|-------|-------|
| Settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Sample Page URL | `/?page_id=2` or `/sample-page/` |
| Sample Post URL | `/?p=1` or `/hello-world/` |
| Radio option value | `2` (Disable by Post Type) |
| Disabled post type | `post` |
| Enabled post type under test | `page` |
| Plugin option key | `disable_comments_options.disabled_post_types` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads |
| 2 | Select "Disable by Post Type" radio button | Radio is selected; post-type checkboxes appear |
| 3 | Check only the "Posts" checkbox; ensure "Pages" and "Media" are unchecked | Only "Posts" is checked |
| 4 | Click "Save Changes" | Success notification appears; settings are saved |
| 5 | Dismiss the notification and reload the settings page | Settings page shows "Disable by Post Type" selected with only "Posts" checked |
| 6 | Navigate to the sample Page frontend URL (e.g. `/sample-page/`) | Page loads in the browser |
| 7 | Scroll to the bottom of the page and locate the comments section | The `#respond` div is present in the DOM |
| 8 | Verify the "Leave a Reply" heading is visible within `#respond` | `#respond h3` or `#reply-title` element is visible with the expected heading text |
| 9 | Verify the comment textarea is present and interactable | `#comment` textarea is visible and editable; click inside it and type a few characters |
| 10 | Verify the "Post Comment" submit button is present and enabled | `#submit` or `input[type="submit"]` within `#respond` is visible and not disabled |
| 11 | Navigate to the sample Post frontend URL (e.g. `/hello-world/`) as a cross-check | Post page loads |
| 12 | Inspect the comments section on the Post | `#respond` is NOT present in the DOM; confirm the plugin is correctly targeting only Posts |

## Expected Results
- The Page comment form (`#respond`) is fully present in the DOM and all interactive elements are accessible
- The "Leave a Reply" heading is visible
- The comment textarea (`#comment`) is editable
- The submit button is enabled
- The Post comment form is absent (confirming the plugin is active and selectively working)
- `disable_comments_options.disabled_post_types` contains `["post"]` and does NOT contain `"page"`
- No JavaScript errors in the browser console on either the Page or Post frontend

## Negative / Edge Cases
- If `#respond` is present but the submit button is disabled or the form is hidden via CSS, the test must FAIL — partial presence is not acceptable
- A logged-in administrator may see the comment form even when disabled due to admin capabilities; run this verification as a logged-out user or in an incognito context
- If the WordPress theme does not call `comments_template()` on Pages, the form will be absent regardless of plugin state; document this as a theme limitation, not a plugin bug
- If the Page's "Allow comments" is unchecked at the WordPress level, the form will naturally be absent; ensure the prerequisite Page has comments enabled at the post level

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name="disable_comments_options[remove_everywhere]"][value="2"]` — "Disable by Post Type" radio
- `input[name="disable_comments_options[disabled_post_types][]"][value="post"]` — Posts checkbox
- `input[name="disable_comments_options[disabled_post_types][]"][value="page"]` — Pages checkbox
- `#respond` — comment form wrapper (must be present on Page)
- `#reply-title` or `#respond h3` — "Leave a Reply" heading
- `#comment` — comment textarea
- `#submit` or `input[name="submit"]` — Post Comment button

**Implementation hints:**
- Use a separate browser context for the logged-out frontend check: `const context = await browser.newContext(); const guestPage = await context.newPage();`
- Assert `#respond` is attached and visible: `await expect(guestPage.locator('#respond')).toBeVisible()`
- Assert the textarea is editable: `await expect(guestPage.locator('#comment')).toBeEditable()`
- Assert the submit button is enabled: `await expect(guestPage.locator('#submit')).toBeEnabled()`
- Cross-check the Post page: `await expect(guestPage.locator('#respond')).not.toBeAttached()`
- Avoid running the Page check as an admin — admin users may bypass filters; use `browser.newContext()` with no stored auth state

## Related
- **WordPress Filters:** `comments_open` (must return `true` for enabled types), `get_comments_number`, `comments_array`
- **WordPress Functions:** `comments_template()` (called by the theme)
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.disabled_post_types`
- **Plugin Methods:** `is_post_type_disabled('page')` — must return `false`
