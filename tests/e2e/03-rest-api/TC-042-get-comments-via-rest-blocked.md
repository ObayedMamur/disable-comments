---
id: TC-042
title: "Listing comments via REST API is blocked when globally disabled"
feature: rest-api
priority: high
tags: [rest-api, get, 403, remove-everywhere, rest_pre_dispatch]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-042 — Listing Comments via REST API Is Blocked When Globally Disabled

## Summary

Verifies that a GET request to `/wp-json/wp/v2/comments` returns HTTP 403 when the REST API restriction is enabled alongside "Remove Everywhere". The plugin intercepts the request at `rest_pre_dispatch` (priority 10) before WordPress processes or returns any comment data.

---

## Prerequisites

- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one approved comment exists in the database (so a non-blocked response would return data)
- [ ] "Remove Everywhere" and "Disable REST API Comments" are both enabled and saved

---

## Test Data

| Field | Value |
|-------|-------|
| REST endpoint | `/wp-json/wp/v2/comments` |
| HTTP method | `GET` |
| Expected HTTP status (blocked) | `403` |
| Expected HTTP status (unblocked control) | `200` |
| Auth method | None (unauthenticated) or Cookie/Basic Auth |

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads. |
| 2 | Enable "Remove Everywhere" toggle | Toggle is checked. |
| 3 | Enable "Disable REST API Comments" checkbox | Checkbox is checked. |
| 4 | Click Save Changes and wait for success notice | Settings are saved. Both options are confirmed active. |
| 5 | Send a GET request to `/wp-json/wp/v2/comments` (unauthenticated) | Request is dispatched. |
| 6 | Inspect the HTTP response status code | Status code is `403 Forbidden`. |
| 7 | Inspect the JSON response body | The body contains a JSON error object with a `code` and `message` field explaining comments are disabled. |
| 8 | Send an authenticated GET request to `/wp-json/wp/v2/comments` using admin credentials | Request is dispatched with Authorization header. |
| 9 | Inspect the HTTP response status code for the authenticated request | Status code is still `403 Forbidden` — even administrators cannot bypass this restriction. |
| 10 | As a control check: temporarily disable the "Disable REST API Comments" setting and resend the GET request | The response returns `200 OK` with a JSON array of comment objects. Re-enable the setting after this step. |

---

## Expected Results

- Unauthenticated GET requests to `/wp-json/wp/v2/comments` return `403 Forbidden`.
- Authenticated (admin) GET requests also return `403 Forbidden`.
- The response body is a JSON error object, not an array of comments.
- No comment data is leaked in the response.
- The `rest_pre_dispatch` hook fires and returns a `WP_Error` before any query is executed.
- When the restriction is disabled, the same endpoint returns `200 OK` with comment data (control case passes).

---

## Negative / Edge Cases

- Requests to unrelated REST endpoints (e.g. `/wp-json/wp/v2/posts`) must NOT be affected — only comment routes are blocked.
- The block applies to all sub-routes of the comments endpoint (e.g. `/wp-json/wp/v2/comments/<id>`).
- Filtering by `post` parameter (e.g. `/wp-json/wp/v2/comments?post=1`) must also return 403.
- If WordPress caching is active, ensure the 403 response is not stale-cached as a 200.

---

## Playwright Notes

**Page URL:** `API: /wp-json/wp/v2/comments`

**Key Selectors:**
- N/A — REST API test; UI interaction limited to settings setup.

**Implementation hints:**
- Use `const response = await page.request.get('/wp-json/wp/v2/comments');` for the unauthenticated call.
- Assert `expect(response.status()).toBe(403)`.
- For the authenticated call: `await page.request.get('/wp-json/wp/v2/comments', { headers: { 'Authorization': 'Basic ' + btoa('admin:app_password') } })`.
- For the control test (setting disabled), use WP-CLI in a `test.step` to toggle the option and re-enable it after: `wp option patch update disable_comments_options remove_rest_API_comments false`.
- Use `expect(response.headers()['content-type']).toContain('application/json')` to verify the error is a proper JSON error body.

---

## Related

- **WordPress Filters:** `rest_pre_dispatch` (priority 10), `rest_comment_query` (priority 10)
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **REST Endpoint:** `/wp/v2/comments`
- **Plugin Option Key:** `disable_comments_options.remove_rest_API_comments`, `disable_comments_options.remove_everywhere`
