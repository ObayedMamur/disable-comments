---
id: TC-006
title: "Comment feed URL returns 403 when globally disabled"
feature: disable-everywhere
priority: high
tags: [disable-everywhere, feed, rss, 403, comment-feed]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-006 — Comment Feed URL Returns 403 When Globally Disabled

## Summary
Verifies that visiting a WordPress comment RSS feed URL returns a 403 error or WordPress `wp_die()` response when Remove Everywhere is active. The plugin hooks `template_redirect` at priority 9 and calls `wp_die()` when `is_comment_feed()` returns true, blocking the feed entirely.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator (or test as logged-out)
- [ ] "Remove Everywhere" mode is currently ACTIVE
- [ ] WordPress permalinks are configured (Settings → Permalinks, at least "Plain" mode works for `?feed=` queries)

## Test Data

| Field | Value |
|-------|-------|
| Global comment feed URL | `/?feed=comments-rss2` |
| Per-post comment feed URL | `/?p=1&feed=rss` or `/hello-world/feed/` |
| Alternative global feed | `/?feed=comments-atom` |
| Expected HTTP status | 403 (Forbidden) |
| Expected response body | WordPress error page (from `wp_die()`) |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Confirm Remove Everywhere is active: navigate to `/wp-admin/admin.php?page=disable_comments_settings` | "Remove Everywhere" radio is selected |
| 2 | (Baseline check) Temporarily disable Remove Everywhere: switch to "Disable by Post Type" with nothing checked, save | Settings saved; Remove Everywhere is now OFF |
| 3 | Navigate to `/?feed=comments-rss2` | The comment RSS feed loads successfully and returns valid XML with HTTP 200 |
| 4 | Re-enable Remove Everywhere: return to settings, select "Remove Everywhere", save | Settings saved; success notification shown |
| 5 | Navigate to `/?feed=comments-rss2` | The browser does NOT display XML feed content; instead a WordPress error page (wp_die output) or 403 Forbidden response is shown |
| 6 | Open browser DevTools → Network tab; reload `/?feed=comments-rss2` | The HTTP response status code for this request is `403` |
| 7 | Verify the response body contains a WordPress error message (e.g. "Comments are closed." or similar `wp_die()` output) | An HTML error page is returned, not RSS/Atom XML content |
| 8 | Navigate to a per-post comment feed URL: `/?p=1&feed=rss` (replace `1` with the ID of a known post) | Same result: HTTP 403 or WordPress error page; no XML feed content is returned |
| 9 | Navigate to `/?feed=comments-atom` (Atom format) | Same result: HTTP 403 or WordPress error page |
| 10 | Navigate to the main site feed `/?feed=rss2` (NOT a comments feed) | The main posts RSS feed loads normally with HTTP 200 — only comment feeds are blocked |

## Expected Results
- Navigating to any comment feed URL (`?feed=comments-rss2`, `?feed=comments-atom`, `?p=X&feed=rss`) returns a non-200 response (typically 403) and a WordPress error page
- The response body is an HTML error page from `wp_die()`, not valid RSS or Atom XML
- The main site posts RSS feed (`/?feed=rss2`) is NOT affected and continues to work
- After disabling Remove Everywhere, the comment feeds return HTTP 200 with valid XML content

## Negative / Edge Cases
- Some server configurations or caching layers may serve a cached 200 response; clear any full-page caches before testing
- The main posts feed (`/?feed=rss2`) must NOT be blocked — only comment-specific feeds
- If the site uses a custom permalink structure, the feed URL pattern may differ (e.g. `/comments/feed/` vs `/?feed=comments-rss2`); test both formats if applicable
- Logged-in vs logged-out: the block should apply to ALL users including admins

## Playwright Notes
**Page URL:** `/?feed=comments-rss2`

**Key Selectors:**
- N/A (HTTP response validation, not DOM)

**Implementation hints:**
- Use Playwright's response object to check status code:
  ```js
  const response = await page.goto('/?feed=comments-rss2');
  expect(response.status()).toBe(403);
  ```
- Check response body for `wp_die` output:
  ```js
  const body = await response.text();
  expect(body).not.toContain('<?xml');
  expect(body).toContain('<!DOCTYPE html'); // wp_die renders HTML
  ```
- For per-post feed: `await page.goto('/?p=1&feed=rss')` and check the same status
- Confirm main feed is unaffected: `const r = await page.goto('/?feed=rss2'); expect(r.status()).toBe(200)`
- Use `page.route()` if you need to intercept and inspect headers alongside the response

## Related
- **WordPress Filters:** none (uses action hook)
- **WordPress Actions:** `template_redirect` (priority 9) → `filter_query()` → `wp_die()` when `is_comment_feed()`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
