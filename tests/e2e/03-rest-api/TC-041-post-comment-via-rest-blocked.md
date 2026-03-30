---
id: TC-041
title: "Creating a comment via REST API is blocked when globally disabled"
feature: rest-api
priority: smoke
tags: [rest-api, post, 403, remove-everywhere, auth]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-041 — Creating a Comment via REST API Is Blocked When Globally Disabled

## Summary

Verifies that a POST request to `/wp-json/wp/v2/comments` returns HTTP 403 when both "Remove Everywhere" is active and "Disable REST API Comments" is enabled. This confirms the `rest_pre_dispatch` hook intercepts the request before WordPress processes it.

---

## Prerequisites

- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one published post exists (to use as `post` parameter)
- [ ] A valid WordPress user with comment-creation capability exists (for authentication)
- [ ] "Remove Everywhere" and "Disable REST API Comments" are both enabled and saved

---

## Test Data

| Field | Value |
|-------|-------|
| REST endpoint | `/wp-json/wp/v2/comments` |
| HTTP method | `POST` |
| Request body — `post` | ID of an existing published post |
| Request body — `content` | `"TC-041 test comment"` |
| Request body — `author_name` | `"TC041 Tester"` |
| Request body — `author_email` | `"tc041@example.com"` |
| Auth method | Basic Auth (Application Password) or Cookie Auth |
| Expected HTTP status | `403` |
| Expected error code | `rest_comment_disabled` (or equivalent plugin error code) |

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads. |
| 2 | Enable "Remove Everywhere" toggle | Toggle is checked. |
| 3 | Enable "Disable REST API Comments" checkbox | Checkbox is checked. |
| 4 | Click Save Changes and wait for success notice | Settings are saved. Both options are active. |
| 5 | Obtain an Application Password for an Administrator user (or ensure cookie session is active) | Credentials are ready for authenticating the REST request. |
| 6 | Note the ID of an existing published post | Post ID is available for use in the request body. |
| 7 | Send a POST request to `/wp-json/wp/v2/comments` with valid JSON body (`post`, `content`, `author_name`, `author_email`) and the Authorization header | Request is dispatched to the WordPress REST API. |
| 8 | Inspect the HTTP response status code | Status code is `403 Forbidden`. |
| 9 | Inspect the JSON response body | The `code` field contains a plugin-defined error code (e.g. `rest_comment_disabled`) and the `message` field contains an explanatory string. |
| 10 | Verify no new comment was created by checking `/wp-json/wp/v2/comments?post=<post_id>` | The comment does not appear in the comment list, confirming it was not persisted. |

---

## Expected Results

- The POST request to `/wp-json/wp/v2/comments` receives a `403 Forbidden` response.
- The JSON response body contains a descriptive error code and message.
- No comment record is created in the database.
- The `rest_pre_dispatch` hook fires before WP processes the request, so no partial write occurs.

---

## Negative / Edge Cases

- If only "Disable REST API Comments" is enabled but "Remove Everywhere" is off (and the target post type has comments open), the request may succeed — confirm that BOTH settings must be active for the block to apply globally.
- Unauthenticated POST requests should also receive 403 (or 401 for missing auth, then 403 for the plugin restriction — verify ordering).
- A malformed JSON body (missing required fields) should still return 403 from the plugin hook before WP's own validation fires.

---

## Playwright Notes

**Page URL:** `API: /wp-json/wp/v2/comments`

**Key Selectors:**
- N/A — this is a REST API test, no browser UI interaction after settings setup.

**Implementation hints:**
- Use `page.request.post('/wp-json/wp/v2/comments', { data: { post: postId, content: 'TC-041 test comment', author_name: 'TC041 Tester', author_email: 'tc041@example.com' }, headers: { 'Authorization': 'Basic ' + btoa('username:app_password') } })` to send the request.
- Assert `expect(response.status()).toBe(403)`.
- Parse the response JSON with `await response.json()` and assert `responseBody.code` matches the expected error code.
- Store the Application Password in a Playwright environment variable (`.env`) — never hard-code credentials.
- In `beforeAll`, set up both settings via the admin UI or direct WP-CLI option injection; in `afterAll`, restore defaults.

---

## Related

- **WordPress Filters:** `rest_pre_dispatch` (priority 10)
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **REST Endpoint:** `/wp/v2/comments`
- **Plugin Option Key:** `disable_comments_options.remove_rest_API_comments`, `disable_comments_options.remove_everywhere`
