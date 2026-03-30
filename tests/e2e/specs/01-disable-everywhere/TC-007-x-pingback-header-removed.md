---
id: TC-007
title: "X-Pingback HTTP header is removed when globally disabled"
feature: disable-everywhere
priority: medium
tags: [disable-everywhere, http-headers, pingback, security]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-007 — X-Pingback HTTP Header Is Removed When Globally Disabled

## Summary
Verifies that the `X-Pingback` HTTP response header is absent from site responses when Remove Everywhere is active. WordPress normally includes this header pointing to `xmlrpc.php`. The plugin removes it via the `wp_headers` filter (`filter_wp_headers()` method) when comments are globally disabled.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] "Remove Everywhere" mode is currently ACTIVE
- [ ] WordPress is configured to normally emit the `X-Pingback` header (default WordPress behavior — no other plugin/configuration suppressing it)

## Test Data

| Field | Value |
|-------|-------|
| Test URL | `/` (homepage) |
| Header name | `X-Pingback` (case-insensitive in HTTP) |
| Expected value when plugin ON | Header is absent / not present |
| Expected value when plugin OFF | `https://your-site.com/xmlrpc.php` (or similar) |
| WordPress filter | `wp_headers` → `filter_wp_headers()` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Confirm Remove Everywhere is active: navigate to `/wp-admin/admin.php?page=disable_comments_settings` | "Remove Everywhere" radio is selected |
| 2 | Open browser DevTools → Network tab | Network tab is open and ready to capture requests |
| 3 | Navigate to the site homepage (`/`) | Homepage loads with HTTP 200 |
| 4 | In the Network tab, click the main document request (the `/` URL) | Request details are shown |
| 5 | Click on the "Response Headers" section of the request | Response headers list is displayed |
| 6 | Search for `X-Pingback` in the response headers list | The `X-Pingback` header is NOT present in the list |
| 7 | Repeat for a published Post URL (e.g. `/hello-world/`) | Navigate to post, inspect response headers — `X-Pingback` is absent |
| 8 | Repeat for a published Page URL (e.g. `/sample-page/`) | Navigate to page, inspect response headers — `X-Pingback` is absent |
| 9 | (Baseline) Temporarily disable Remove Everywhere (switch to "Disable by Post Type", no types selected, save) | Settings saved |
| 10 | Navigate to the homepage `/` and inspect response headers | The `X-Pingback` header IS present with a value like `https://your-site.com/xmlrpc.php` |
| 11 | Re-enable Remove Everywhere and save | Settings saved |
| 12 | Navigate to `/` and confirm the `X-Pingback` header is absent again | Header is absent — confirming the plugin controls this behavior |

## Expected Results
- The `X-Pingback` response header is absent from HTTP responses on all pages when Remove Everywhere is active
- This applies to the homepage, single posts, single pages, and archives
- After disabling Remove Everywhere, the `X-Pingback` header reappears in responses (default WordPress behavior)
- No other headers are affected or removed

## Negative / Edge Cases
- If another plugin or server configuration (`.htaccess`, Nginx config) also removes the `X-Pingback` header, the baseline check (step 10) will also show the header absent — in this case, use a staging environment without other interfering plugins
- HTTP/2 protocol normalizes all headers to lowercase; look for `x-pingback` (lowercase) in browser DevTools when using HTTP/2
- Caching plugins may serve cached responses without re-running PHP filters; clear all caches before testing

## Playwright Notes
**Page URL:** `/` (homepage)

**Key Selectors:**
- N/A (HTTP header validation)

**Implementation hints:**
- Use Playwright's response object to inspect headers:
  ```js
  const response = await page.goto('/');
  const headers = response.headers();
  expect(headers['x-pingback']).toBeUndefined();
  ```
- Note: Playwright's `response.headers()` returns lowercase keys (HTTP/2 normalized); use `headers['x-pingback']` not `headers['X-Pingback']`
- For the baseline check (plugin off), assert: `expect(headers['x-pingback']).toBeDefined()`
- Test across multiple URLs: `['/hello-world/', '/sample-page/', '/']` in a loop
- Use `page.route()` to intercept if needed, but `response.headers()` from `page.goto()` is sufficient

## Related
- **WordPress Filters:** `wp_headers` → `filter_wp_headers()` (removes `X-Pingback` key)
- **WordPress Actions:** N/A
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
