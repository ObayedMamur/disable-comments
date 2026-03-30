---
id: TC-043
title: "REST API comments work normally when restriction setting is not enabled"
feature: rest-api
priority: high
tags: [rest-api, negative, control, get, 200]
type: negative
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-043 — REST API Comments Work Normally When Restriction Setting Is Not Enabled

## Summary

Negative/control test verifying that when "Disable REST API Comments" is NOT checked, the REST API must function normally regardless of other settings being active. This ensures the plugin does not inadvertently block the REST API when the option is unchecked.

---

## Prerequisites

- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one published post exists
- [ ] At least one approved comment exists in the database
- [ ] "Disable REST API Comments" is currently unchecked (the setting under test must be OFF)

---

## Test Data

| Field | Value |
|-------|-------|
| REST endpoint (list) | `/wp-json/wp/v2/comments` |
| REST endpoint (single) | `/wp-json/wp/v2/comments/<id>` |
| HTTP method (read) | `GET` |
| HTTP method (create) | `POST` |
| Expected HTTP status (read) | `200` |
| Expected HTTP status (create) | `201` |
| Auth method | None (unauthenticated for GET) / Basic Auth for POST |
| Plugin option state | `remove_rest_API_comments = false` |

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads. |
| 2 | Confirm "Disable REST API Comments" checkbox is unchecked | Checkbox is unchecked. If checked, uncheck it and save. |
| 3 | Optionally enable "Remove Everywhere" to confirm REST is unaffected by that toggle alone | "Remove Everywhere" may be on or off; the REST API restriction is governed solely by its own checkbox. |
| 4 | Save settings if any change was made | Success notice is displayed. |
| 5 | Send a GET request to `/wp-json/wp/v2/comments` (unauthenticated) | HTTP response status is `200 OK`. |
| 6 | Inspect the JSON response body | The body is a JSON array. It may be empty or contain comment objects, but it is NOT a WP_Error object with a 403 code. |
| 7 | Send a GET request to `/wp-json/wp/v2/comments/<existing_comment_id>` | HTTP response status is `200 OK` and the body is a single comment object. |
| 8 | Send an authenticated POST request to `/wp-json/wp/v2/comments` with valid body fields (`post`, `content`, `author_name`, `author_email`) | HTTP response status is `201 Created`. The comment object is returned. |
| 9 | Verify the created comment exists via `/wp-json/wp/v2/comments?post=<post_id>` | The newly created comment appears in the list. |
| 10 | Clean up: delete the test comment created in step 8 | Comment is removed. Database is back to the prior state. |

---

## Expected Results

- GET `/wp-json/wp/v2/comments` returns `200 OK` with a JSON array.
- GET `/wp-json/wp/v2/comments/<id>` returns `200 OK` with the comment object.
- POST `/wp-json/wp/v2/comments` returns `201 Created` and the comment is persisted.
- No 403 error is returned from any comment REST endpoint.
- The plugin does not interfere with the REST API when `remove_rest_API_comments` is `false`.

---

## Negative / Edge Cases

- If "Remove Everywhere" is ON but REST disable is OFF, REST API must still work — the two settings are independent.
- If comments are closed on the target post, the API will return a WordPress-native error (not a plugin error). Use a post that has comments open.
- Ensure the test does not accidentally leave a dangling test comment that could pollute TC-041 or TC-042 runs.

---

## Playwright Notes

**Page URL:** `API: /wp-json/wp/v2/comments`

**Key Selectors:**
- `input[name="disable_comments_options[remove_rest_API_comments]"]` — "Disable REST API Comments" checkbox (must be unchecked).

**Implementation hints:**
- Use `const response = await page.request.get('/wp-json/wp/v2/comments');` and assert `expect(response.status()).toBe(200)`.
- Use `const body = await response.json(); expect(Array.isArray(body)).toBe(true);` to confirm the body is a comments array, not an error object.
- For the POST step, use `page.request.post` with Basic Auth headers and assert `expect(createResponse.status()).toBe(201)`.
- Teardown: `page.request.delete('/wp-json/wp/v2/comments/<new_id>?force=true', { headers: { 'Authorization': ... } })`.
- Use WP-CLI in `beforeAll` to ensure the option is false: `wp option patch update disable_comments_options remove_rest_API_comments false`.

---

## Related

- **WordPress Filters:** `rest_pre_dispatch` (priority 10)
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **REST Endpoint:** `/wp/v2/comments`
- **Plugin Option Key:** `disable_comments_options.remove_rest_API_comments`
