---
id: TC-044
title: "Allowed comment types bypass REST API restriction"
feature: rest-api
priority: medium
tags: [rest-api, allowed-comment-types, allowlist, is_allowed_comment_type_request]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-044 — Allowed Comment Types Bypass REST API Restriction

## Summary

Verifies that comment types added to the allowlist (e.g. a custom type such as `review`) are not blocked by the REST API restriction while regular `comment` type requests remain blocked. The plugin's `is_allowed_comment_type_request()` method inspects the `type` parameter in the REST request to determine whether to apply the block.

---

## Prerequisites

- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Remove Everywhere" and "Disable REST API Comments" are enabled
- [ ] A custom comment type (e.g. `review`) is registered and available in the WordPress instance
- [ ] The allowlist feature ("Allowed Comment Types") is accessible in the settings UI
- [ ] At least one published post exists

---

## Test Data

| Field | Value |
|-------|-------|
| REST endpoint | `/wp-json/wp/v2/comments` |
| Allowed comment type | `review` (or site-specific custom type) |
| Blocked comment type | `comment` (default WordPress type) |
| HTTP method | `POST` |
| Expected status (allowed type) | `201 Created` |
| Expected status (blocked type) | `403 Forbidden` |
| Auth method | Basic Auth (Application Password) |
| Plugin option — allowed types | `disable_comments_options.allowed_comment_types` |

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads. |
| 2 | Enable "Remove Everywhere" and "Disable REST API Comments" | Both checkboxes are checked. |
| 3 | Locate the "Allowed Comment Types" input field and enter `review` | The value `review` is entered in the allowed types field. |
| 4 | Click Save Changes and wait for success notice | Settings are saved. `remove_rest_API_comments = true` and `allowed_comment_types` includes `review`. |
| 5 | Send a POST request to `/wp-json/wp/v2/comments` with `type: "comment"` in the body and valid `post`, `content`, `author_name`, `author_email` fields | Request is dispatched. |
| 6 | Inspect the HTTP response status for the default `comment` type request | Status code is `403 Forbidden`. The blocked type is not exempt. |
| 7 | Send a POST request to `/wp-json/wp/v2/comments` with `type: "review"` in the body (same other fields) | Request is dispatched. |
| 8 | Inspect the HTTP response status for the `review` type request | Status code is `201 Created`. The allowed type bypasses the restriction. |
| 9 | Confirm the `review` comment was created via GET `/wp-json/wp/v2/comments?type=review&post=<post_id>` | The new `review` comment appears in the response. |
| 10 | Remove `review` from the allowlist, save, and re-send the `review` type POST request | Status code is now `403 Forbidden`, confirming the allowlist controls the exemption. |

---

## Expected Results

- POST with `type: "comment"` returns `403 Forbidden` when REST restriction is active.
- POST with `type: "review"` (allowlisted) returns `201 Created`.
- The allowlisted comment is persisted in the database.
- Removing the type from the allowlist and retrying results in `403`.
- The `is_allowed_comment_type_request()` method correctly inspects the `type` field in request body params and JSON body.

---

## Negative / Edge Cases

- Passing `type` in the URL query string (e.g. `?type=review`) rather than the POST body — verify the method also checks query params.
- Attempting to bypass the check by sending `type` as an array or with mixed casing (e.g. `Review`, `REVIEW`) — the check should be case-sensitive and not bypassable.
- If `allowed_comment_types` is empty, all comment types (including `review`) should be blocked.
- For UPDATE (PATCH/PUT) requests: the plugin checks the existing comment's type. Test that updating a non-allowlisted comment is blocked even if you pass an allowed `type` in the payload.

---

## Playwright Notes

**Page URL:** `API: /wp-json/wp/v2/comments`

**Key Selectors:**
- `input[name="disable_comments_options[allowed_comment_types][]"]` or equivalent — Allowed Comment Types field (confirm selector in DOM)
- `input[name="disable_comments_options[remove_rest_API_comments]"]` — REST API disable checkbox

**Implementation hints:**
- Use `page.request.post('/wp-json/wp/v2/comments', { data: { post: postId, content: 'review test', author_name: 'Reviewer', author_email: 'r@example.com', type: 'comment' }, headers: { Authorization: ... } })` and assert 403.
- Repeat with `type: 'review'` and assert 201.
- Use WP-CLI to set up the allowlist programmatically in `beforeAll`: `wp option patch update disable_comments_options allowed_comment_types '["review"]' --format=json`.
- Teardown: delete the created `review` comment and restore settings.

---

## Related

- **WordPress Filters:** `rest_pre_dispatch` (priority 10), `rest_pre_insert_comment` (priority 10)
- **Plugin Method:** `is_allowed_comment_type_request()`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **REST Endpoint:** `/wp/v2/comments`
- **Plugin Option Key:** `disable_comments_options.remove_rest_API_comments`, `disable_comments_options.allowed_comment_types`
