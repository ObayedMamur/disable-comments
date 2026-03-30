---
id: TC-163
title: "Non-allowed comment types are blocked via REST API when globally disabled"
feature: allowed-comment-types
priority: medium
tags: [allowlist, rest-api, blocked, negative]
type: negative
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-163 — Non-allowed comment types are blocked via REST API when globally disabled

## Summary
Explicitly verifies the negative path: comment types NOT present in the allowlist are blocked by the REST API restriction when globally disabled. The allowlist exempts only specific types; everything else remains blocked.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Remove Everywhere is enabled
- [ ] REST API comments are disabled
- [ ] The allowlist contains only 'pingback' (NOT 'comment' or 'trackback')

## Test Data

| Field | Value |
|-------|-------|
| Allowed type (in allowlist) | `pingback` |
| Blocked type 1 | `comment` |
| Blocked type 2 | `trackback` |
| Expected HTTP status for blocked | 403 |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Confirm configuration: Remove Everywhere ON, REST disable ON, only 'pingback' in allowlist | All three settings confirmed active |
| 2 | Attempt: `GET /wp-json/wp/v2/comments?type=comment` — expect 403 | HTTP 403 response is received |
| 3 | Attempt: `GET /wp-json/wp/v2/comments?type=trackback` — expect 403 | HTTP 403 response is received |
| 4 | Attempt: `POST /wp-json/wp/v2/comments` with body `{"post": 1, "content": "test", "type": "comment"}` — expect 403 | HTTP 403 response is received for the POST request |
| 5 | Verify error response body contains a meaningful error message (not empty, not HTML) | Response body is valid JSON with an error code field |
| 6 | Contrast: `GET /wp-json/wp/v2/comments?type=pingback` — expect 200 (allowed) | HTTP 200 response confirms the allowed type still passes through |
| 7 | Verify: the 403 responses do not leak any comment data | 403 response bodies contain only error information, no comment objects |

## Expected Results
- 'comment' type requests blocked with 403
- 'trackback' type requests blocked with 403
- Error response body is JSON with error code, not HTML or empty
- Only 'pingback' (allowed type) returns 200

## Negative / Edge Cases
- Empty `type` param may default to 'comment' behavior — should be blocked
- Types that don't exist in DB but are not in allowlist should still return 403 (not 404)

## Playwright Notes
**Page URL:** API test

**Key Selectors:**
- N/A

**Implementation hints:**
- Use `page.request.get()` and `page.request.post()` for API calls
- Assert `response.status() === 403`
- Parse response body: `const body = await response.json(); expect(body.code).toBeDefined()`

## Related
- **WordPress Filter:** `rest_pre_dispatch`
- **Plugin Method:** `filter_rest_comment_dispatch()`, `is_allowed_comment_type_request()`
- **Plugin Option Key:** `disable_comments_options.allowed_comment_types`
