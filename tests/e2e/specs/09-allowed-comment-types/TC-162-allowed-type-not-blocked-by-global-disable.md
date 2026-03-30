---
id: TC-162
title: "REST API requests for allowed comment types pass through when globally disabled"
feature: allowed-comment-types
priority: high
tags: [allowlist, rest-api, comment-types, bypass, integration]
type: integration
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-162 — REST API requests for allowed comment types pass through when globally disabled

## Summary
When both "Remove Everywhere" and "Disable REST API Comments" are active, a REST API request for a comment type in the allowlist must succeed (HTTP 200), while a request for a non-allowed type must be blocked (HTTP 403). This verifies the allowlist bypass for REST API.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Remove Everywhere is enabled
- [ ] REST API comments are disabled (`remove_rest_API_comments = true`)
- [ ] 'pingback' is in the `allowed_comment_types` allowlist

## Test Data

| Field | Value |
|-------|-------|
| Allowed type | `pingback` |
| Blocked type | `comment` |
| REST endpoint | `/wp-json/wp/v2/comments` |
| Auth method | Basic Auth or Application Password (or cookie-based for authenticated requests) |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Configure: Enable Remove Everywhere + Enable "Disable REST API Comments" + Add 'pingback' to allowlist, save | All three settings are active and saved |
| 2 | Make GET request: `GET /wp-json/wp/v2/comments?type=pingback` | Request is sent to the REST API |
| 3 | Verify response status is 200 (not 403) — the allowed type passes through | HTTP 200 response is received |
| 4 | Verify response body is valid JSON with comment data (or empty array if no pingbacks exist) | Response body is parseable JSON, not an error object |
| 5 | Make GET request: `GET /wp-json/wp/v2/comments?type=comment` | Request is sent to the REST API |
| 6 | Verify response status is 403 — the non-allowed type is blocked | HTTP 403 response is received |
| 7 | Verify response body contains an error code (e.g. `rest_forbidden` or similar) | Response body contains a JSON error object with a code field |
| 8 | Make GET request: `GET /wp-json/wp/v2/comments` (no type param — defaults to 'comment' or mixed) | Request is sent without a type parameter |
| 9 | Verify this is also blocked (403) since the default type is not in the allowlist | HTTP 403 response is received for the untyped request |

## Expected Results
- Requests with `type=pingback` return 200 (allowed type bypasses restriction)
- Requests with `type=comment` return 403 (non-allowed type blocked)
- Requests with no type param return 403 (defaults to blocked)

## Negative / Edge Cases
- If the allowlist is empty, ALL comment REST requests are blocked
- POST requests to create a comment of an allowed type should also pass through

## Playwright Notes
**Page URL:** API test — no admin page needed

**Key Selectors:**
- N/A (API test)

**Implementation hints:**
- `const resp1 = await page.request.get('/wp-json/wp/v2/comments?type=pingback'); expect(resp1.status()).toBe(200);`
- `const resp2 = await page.request.get('/wp-json/wp/v2/comments?type=comment'); expect(resp2.status()).toBe(403);`
- Use `request` fixture in Playwright for stateless API calls
- May need authentication: `page.request.get(url, { headers: { 'Authorization': 'Basic ...' } })`

## Related
- **WordPress Filter:** `rest_pre_dispatch`
- **Plugin Method:** `is_allowed_comment_type_request()`, `filter_rest_comment_dispatch()`
- **REST Endpoint:** `/wp/v2/comments`
- **Plugin Option Key:** `disable_comments_options.allowed_comment_types`, `disable_comments_options.remove_rest_API_comments`
