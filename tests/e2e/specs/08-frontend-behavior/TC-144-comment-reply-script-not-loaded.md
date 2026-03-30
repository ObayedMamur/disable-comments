---
id: TC-144
title: "comment-reply.js is not enqueued when comments are globally disabled"
feature: frontend-behavior
priority: low
tags: [frontend, javascript, comment-reply, performance, script]
type: edge-case
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-144 — comment-reply.js is not enqueued when comments are globally disabled

## Summary
WordPress enqueues `comment-reply.js` only when `comments_open()` returns true. When Disable Comments globally disables comments, this script must not be loaded on post pages, reducing unnecessary page weight.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Remove Everywhere mode is active
- [ ] The test site uses a standard WordPress theme that loads comment-reply.js (Twenty Twenty-One, Twenty Twenty-Four, etc.)

## Test Data

| Field | Value |
|-------|-------|
| Post URL | `/sample-post/` (any published post) |
| Script handle | `comment-reply` |
| WordPress script URL pattern | `/wp-includes/js/comment-reply.min.js` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Enable "Remove Everywhere" in plugin settings, save | Settings saved; Remove Everywhere is active |
| 2 | Navigate to a published post URL (e.g. `/sample-post/`) as a logged-out user | Post page loads without errors |
| 3 | Open browser DevTools > Network tab, filter by "JS" or search "comment-reply" | Network tab is open and filtered |
| 4 | Reload the page with DevTools Network tab open | Page reloads; network requests are captured |
| 5 | Verify `comment-reply.min.js` does NOT appear in the network requests list | No request for `comment-reply.min.js` is recorded |
| 6 | Also check page source (Ctrl+U): search for "comment-reply" — it should not appear in any `<script>` tag | No `<script>` tag references `comment-reply` in the HTML source |
| 7 | For comparison: disable the plugin temporarily and reload — verify comment-reply.js IS loaded now (to confirm the test setup works) | `comment-reply.min.js` appears in network requests when plugin is deactivated |

## Expected Results
- `comment-reply.min.js` is absent from the network requests
- No `<script>` tag references `comment-reply` in the page HTML
- Page loads normally without JavaScript errors

## Negative / Edge Cases
- When comments ARE enabled, comment-reply.js SHOULD be present (use as control check)
- On a page post type (not disabled), the script should load normally if pages have comments enabled

## Playwright Notes
**Page URL:** `/sample-post/` (any post page)

**Key Selectors:**
- N/A (network-level check)

**Implementation hints:**
- `const scriptRequests = []; page.on('request', r => { if (r.url().includes('comment-reply')) scriptRequests.push(r.url()) }); await page.goto('/sample-post/'); await page.waitForLoadState('networkidle'); expect(scriptRequests).toHaveLength(0);`
- Alternative: `const html = await page.content(); expect(html).not.toContain('comment-reply');`
- More robust: use `page.waitForLoadState('networkidle')` before asserting

## Related
- **WordPress Function:** `wp_deregister_script('comment-reply')`
- **Plugin Method:** `check_comment_template()`
- **WordPress Action:** `wp_enqueue_scripts`
