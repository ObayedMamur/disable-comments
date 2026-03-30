---
id: TC-141
title: "Post comment RSS feed URL returns 403 when globally disabled"
feature: frontend-behavior
priority: high
tags: [frontend-behavior, global, rss-feed, 403, comment-feed, http-status]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-141 — Post Comment RSS Feed URL Returns 403 When Globally Disabled

## Summary
When Remove Everywhere is active, WordPress comment feed endpoints must be blocked. The plugin hooks into `template_redirect` at priority 9 and calls `wp_die()` when `is_comment_feed()` returns true, resulting in a 403 error page rather than serving XML feed content.

---

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Remove Everywhere" mode is currently ACTIVE (saved)
- [ ] At least one published Post exists (e.g. ID 1)
- [ ] WordPress permalinks are configured (Settings > Permalinks flushed)

---

## Test Data

| Field | Value |
|-------|-------|
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Global comment feed URL | `/?feed=comments-rss2` |
| Post-specific feed URL | `/?p=1&feed=rss` |
| Pretty permalink feed URL | `/feed/comments/` |
| Expected HTTP status | 403 |
| Expected response body | WordPress die/error page — must NOT contain `<?xml` |

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Disable Comments settings page loads |
| 2 | Confirm "Remove Everywhere" radio is selected; if not, select it and save | Settings page shows "Remove Everywhere" as active; success notice shown |
| 3 | Open a new browser tab and navigate to `/?feed=comments-rss2` | The browser receives an HTTP 403 response. A WordPress error/die page is displayed (e.g. "Comments are closed." or a generic error), NOT an XML feed |
| 4 | Inspect the response body to confirm it does NOT start with `<?xml` or contain `<rss` | The page content is a plain WordPress error page or HTML die message, not RSS/XML content |
| 5 | Navigate to `/?p=1&feed=rss` (post-specific comment feed for post ID 1) | HTTP 403 is returned; same WordPress error page is shown |
| 6 | If pretty permalinks are enabled, navigate to `/feed/comments/` | HTTP 403 is returned; WordPress error/die page is shown |
| 7 | In browser DevTools, check the Network tab for the feed request and note the status code | Status code is 403 for all comment feed URLs tested |
| 8 | Navigate to the main content feed `/?feed=rss2` (NOT a comment feed) | The main RSS feed loads normally with HTTP 200 and valid XML — this confirms only comment feeds are blocked |
| 9 | Disable "Remove Everywhere" (switch to "Do not disable" mode) and save settings | Settings saved successfully |
| 10 | Navigate back to `/?feed=comments-rss2` | HTTP 200 is returned and the comment RSS feed XML is served normally |

---

## Expected Results
- `/?feed=comments-rss2` returns HTTP 403 when Remove Everywhere is active
- `/?p=1&feed=rss` returns HTTP 403 when Remove Everywhere is active
- `/feed/comments/` returns HTTP 403 when Remove Everywhere is active
- The response body for all blocked feeds does NOT contain `<?xml` or `<rss`
- The main site RSS feed (`/?feed=rss2`) is NOT affected and continues to return 200
- After disabling Remove Everywhere, comment feeds return to 200 with valid XML

---

## Negative / Edge Cases
- The main site post feed (`/?feed=rss2`) must remain accessible — only comment feed endpoints should be blocked
- The block must apply whether the user is logged in or not
- If WordPress sends a 403 status but the response body still contains XML, the test should FAIL — the feed content must not be exposed
- The `wp_die()` message content may vary by theme/WordPress version; the test should assert on HTTP status rather than a specific die message string

---

## Playwright Notes
**Page URL:** `/?feed=comments-rss2`

**Key Selectors:**
- (No DOM selectors needed — this test operates at HTTP response level)

**Implementation hints:**
- Assert on HTTP status: `const response = await page.goto('/?feed=comments-rss2'); expect(response.status()).toBe(403);`
- Also assert content is not XML: `const body = await response.text(); expect(body).not.toContain('<?xml');`
- Parameterise over multiple feed URLs: `['/?feed=comments-rss2', '/?p=1&feed=rss', '/feed/comments/']`
- For the non-comment feed smoke check: `const mainFeed = await page.goto('/?feed=rss2'); expect(mainFeed.status()).toBe(200);`
- Use `page.goto(url, { waitUntil: 'commit' })` to capture the initial response before the page renders any error content

---

## Related
- **WordPress Filters/Actions:** `template_redirect` (plugin hooks at priority 9), `is_comment_feed()`
- **WordPress Function:** `wp_die()` — called by the plugin to terminate feed requests
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
