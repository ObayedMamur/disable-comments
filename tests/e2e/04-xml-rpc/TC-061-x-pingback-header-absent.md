---
id: TC-061
title: "X-Pingback HTTP header is absent when XML-RPC comments are disabled"
feature: xml-rpc
priority: medium
tags: [xml-rpc, pingback, http-header, wp_headers, security]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-061 — X-Pingback HTTP Header Is Absent When XML-RPC Comments Are Disabled

## Summary

When XML-RPC comments are disabled, the `X-Pingback` HTTP response header must not be present in any frontend page responses. The plugin removes it via the `wp_headers` filter. Its absence prevents discovery of the XML-RPC endpoint by external crawlers and comment spammers.

---

## Prerequisites

- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Disable XML-RPC Comments" is enabled and saved
- [ ] The WordPress site has at least one published post accessible on the frontend

---

## Test Data

| Field | Value |
|-------|-------|
| Test URL (homepage) | `/` |
| Test URL (single post) | `/` + slug of any published post |
| Expected header (blocked) | `X-Pingback` must be absent |
| Expected header (unblocked control) | `X-Pingback: <site-url>/xmlrpc.php` |
| Plugin filter | `wp_headers` |

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads. |
| 2 | Enable "Disable XML-RPC Comments" checkbox | Checkbox is checked. |
| 3 | Click Save Changes and wait for success notice | Settings are saved successfully. |
| 4 | Make a GET request to the site homepage (`/`) | The homepage loads and returns `200 OK`. |
| 5 | Inspect the HTTP response headers for the homepage response | The `X-Pingback` header is absent from the response headers. |
| 6 | Make a GET request to the URL of a single published post | The post page loads and returns `200 OK`. |
| 7 | Inspect the HTTP response headers for the single post response | The `X-Pingback` header is absent. |
| 8 | Make a GET request to a static page (if available) | The page loads and returns `200 OK`. |
| 9 | Inspect the HTTP response headers for the static page | The `X-Pingback` header is absent. |
| 10 | As a control: disable the "Disable XML-RPC Comments" setting, save, and repeat the homepage GET | The `X-Pingback: <site-url>/xmlrpc.php` header IS now present in the response. Re-enable after this step. |

---

## Expected Results

- The `X-Pingback` header is absent from all frontend page HTTP responses when the XML-RPC restriction is active.
- The absence applies to the homepage, single posts, and static pages.
- When the restriction is disabled (control case), the `X-Pingback` header is present and points to `/xmlrpc.php`.
- No other HTTP headers are inadvertently removed by the plugin's `wp_headers` filter.

---

## Negative / Edge Cases

- The `X-Pingback` header should also be absent from REST API responses and admin page responses — verify this is consistent.
- If a caching plugin (e.g. WP Super Cache, W3 Total Cache) has cached page responses, the cached version may still serve the old header. Test with a cache-busting query parameter or after flushing the cache.
- If the server (Nginx/Apache) independently adds the `X-Pingback` header outside of WordPress, the plugin cannot remove it — document this limitation.
- The `X-Pingback` header in `<link>` tags within the `<head>` HTML (not the HTTP header) is separate; verify both are handled if applicable.

---

## Playwright Notes

**Page URL:** `/` (homepage) and a single post URL

**Key Selectors:**
- N/A — this test operates on HTTP response headers, not DOM elements.

**Implementation hints:**
- Use `const response = await page.goto('/');` then `const headers = response.headers();` to access all response headers.
- Assert `expect(headers['x-pingback']).toBeUndefined()` — note header names are lowercase in Playwright's `headers()` map.
- For the control case, temporarily disable the setting via WP-CLI: `wp option patch update disable_comments_options remove_xmlrpc_comments false` then verify the header is present.
- Check both homepage and a post URL: `await page.goto('/sample-page/')` (or use a dynamically retrieved post URL via WP-CLI).
- Use `test.step` blocks to clearly separate the "blocked" and "control" assertions in the test output.

---

## Related

- **WordPress Filters:** `wp_headers`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **XML-RPC Endpoint:** `/xmlrpc.php`
- **Plugin Option Key:** `disable_comments_options.remove_xmlrpc_comments`
