---
id: TC-143
title: "\"Leave a comment\" / comment count link is absent from post metadata"
feature: frontend-behavior
priority: medium
tags: [frontend-behavior, global, leave-a-comment, comment-link, post-meta]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-143 — "Leave a Comment" / Comment Count Link Is Absent from Post Metadata

## Summary
WordPress themes display a "Leave a comment" or "X Comments" link in post metadata. When comments are disabled, this link must either be completely absent from the DOM or, if present, must show a count of 0. This test verifies that the frontend post meta area does not invite users to leave comments when commenting is disabled.

---

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Remove Everywhere" mode is currently ACTIVE (saved)
- [ ] At least one published Post exists
- [ ] The active WordPress theme renders a "Leave a comment" or comment count link in post metadata (e.g. Twenty Twenty-Four, Twenty Twenty-Three, or similar)

---

## Test Data

| Field | Value |
|-------|-------|
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Test post URL | `/?p=1` or `/sample-post/` |
| Common link selector | `.comments-link`, `a[href*="#respond"]` |
| Expected link text (if present) | "0 comments" or "No comments" — NOT "Leave a comment" |
| Expected link href anchor (if present) | Must NOT link to `#respond` (which no longer exists) |

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Disable Comments settings page loads |
| 2 | Confirm "Remove Everywhere" is selected; if not, select it and save | Settings confirms Remove Everywhere is active |
| 3 | Navigate to a published post URL (e.g. `/?p=1`) as an anonymous visitor (incognito window) | Post page loads with status 200 |
| 4 | Examine the post meta area (typically below the post title or at the bottom of the post header) for a comment count link or "Leave a comment" text | Either: (a) no comment link is present in the post meta area at all, OR (b) a link is present but shows "0 comments" — the text "Leave a comment" must NOT appear |
| 5 | Use browser DevTools to search the DOM for elements matching `.comments-link` | Either absent from the DOM, OR present with inner text matching "0" or "No comments" |
| 6 | Use browser DevTools to search the DOM for `a[href*="#respond"]` | No anchor element in the page links to `#respond` (since `#respond` does not exist) |
| 7 | Use browser DevTools to search the page text for the exact string "Leave a comment" | The string "Leave a comment" does not appear anywhere in the rendered page text |
| 8 | Navigate to the blog archive page (e.g. `/`) | Archive page loads |
| 9 | Examine the comment link in post listing items for the test post | Same result as step 4: absent or showing 0 |
| 10 | Disable "Remove Everywhere" and save, then revisit the test post | "Leave a comment" link appears in post meta (confirming the theme does render it when comments are open) |

---

## Expected Results
- The "Leave a comment" text is not present in the post meta on single post pages when Remove Everywhere is active
- Any comment count link that remains in the DOM shows 0 (not a positive integer) and does not link to the absent `#respond` element
- The same behavior is consistent on archive/loop pages
- After re-enabling comments, the "Leave a comment" link re-appears, confirming the theme supports it

---

## Negative / Edge Cases
- Some minimal or block themes may not render a "Leave a comment" link at all even when comments are enabled — run step 10 first to confirm the theme renders the link before treating its absence as a test pass
- If the theme renders a `comments-link` element but links to `#respond` while comments are disabled, this may cause a broken anchor (scroll to non-existent element); the test should flag this as a UX issue even if not a strict plugin failure
- The "Leave a comment" text may appear as aria-label or title attribute on a link — check attributes as well as visible text

---

## Playwright Notes
**Page URL:** `/?p=1`

**Key Selectors:**
- `.comments-link` — WordPress standard comment count link class
- `a[href*="#respond"]` — links pointing to the comment form anchor
- `.entry-meta` — post meta wrapper in Twenty* themes
- `.post-meta` — post meta wrapper in other themes

**Implementation hints:**
- Check for absence of "Leave a comment" text: `await expect(page.getByText('Leave a comment', { exact: false })).not.toBeVisible()`
- Check that no link points to `#respond`: `const count = await page.locator('a[href*="#respond"]').count(); expect(count).toBe(0);`
- If `.comments-link` exists, verify it shows 0: `const linkText = await page.locator('.comments-link').textContent(); expect(linkText).toMatch(/0/);`
- Combine both scenarios in one test using conditional logic: if the element exists, assert the count is 0; if it doesn't exist, assert it's absent
- To confirm theme renders the link when comments are ON, wrap the re-enable check in a test fixture teardown or a separate describe block

---

## Related
- **WordPress Filters:** `get_comments_number` (plugin returns 0), `comments_open` (plugin returns false)
- **WordPress Template Tag:** `comments_number()`, `get_comments_number()`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
