---
id: TC-045
title: "REST API comment creation blocked for disabled post type"
feature: rest-api
priority: medium
tags: [rest-api, post-type, selective-disable, 403, by-post-type]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-045 — REST API Comment Creation Blocked for Disabled Post Type

## Summary

When using "Disable by Post Type" mode (not Remove Everywhere), the REST API restriction applies selectively — only to post types for which comments have been disabled. POST requests targeting a disabled post type return 403, while requests targeting an enabled post type succeed normally.

---

## Prerequisites

- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Remove Everywhere" is OFF
- [ ] "Disable by Post Type" is configured to disable comments on `post` (standard posts) only
- [ ] "Disable REST API Comments" is enabled
- [ ] At least one published `post` (standard post type) exists — call it Post A
- [ ] At least one published `page` (page post type) exists with comments open — call it Page B
- [ ] A valid user with comment-creation capability exists for authentication

---

## Test Data

| Field | Value |
|-------|-------|
| REST endpoint | `/wp-json/wp/v2/comments` |
| HTTP method | `POST` |
| Post type with comments disabled | `post` (standard post) |
| Post type with comments enabled | `page` |
| Expected status (disabled post type) | `403 Forbidden` |
| Expected status (enabled post type) | `201 Created` |
| Auth method | Basic Auth (Application Password) |

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads. |
| 2 | Confirm "Remove Everywhere" is unchecked | Toggle is off. The "Disable by Post Type" section is visible. |
| 3 | Check the `post` (Posts) post type checkbox in the "Disable by Post Type" section | Comments will be disabled for Posts only. |
| 4 | Enable "Disable REST API Comments" checkbox | Checkbox is checked. |
| 5 | Click Save Changes and wait for success notice | Settings are saved. Disable by Post Type for `post` and REST restriction are active. |
| 6 | Note the ID of the published standard `post` (Post A) | Post A's ID is available for use in the request body. |
| 7 | Send an authenticated POST request to `/wp-json/wp/v2/comments` with `post` set to Post A's ID | Request is dispatched targeting a disabled post type. |
| 8 | Inspect the HTTP response status for Post A | Status code is `403 Forbidden`. Comments for post type `post` are blocked via REST. |
| 9 | Note the ID of the published `page` (Page B) with comments open | Page B's ID is available. |
| 10 | Send an authenticated POST request to `/wp-json/wp/v2/comments` with `post` set to Page B's ID | Request is dispatched targeting an enabled post type. |
| 11 | Inspect the HTTP response status for Page B | Status code is `201 Created`. Comments for `page` post type are not blocked. |
| 12 | Confirm the comment for Page B appears via GET `/wp-json/wp/v2/comments?post=<Page B ID>` | The new comment is visible in the API response. |

---

## Expected Results

- POST to `/wp-json/wp/v2/comments` with `post` referencing a `post`-type entry returns `403 Forbidden`.
- POST to `/wp-json/wp/v2/comments` with `post` referencing a `page`-type entry returns `201 Created`.
- The selectivity confirms the plugin correctly inspects the post type of the referenced post during REST dispatch.
- GET requests to list comments for the disabled post type also return 403.

---

## Negative / Edge Cases

- If the `post` parameter is omitted from the request body, verify the plugin's behavior (may default to blocking or may allow the request through — document actual behavior).
- Switching from "Disable by Post Type" to "Remove Everywhere" mode should then block Pages as well.
- Custom post types registered by third-party plugins should follow the same selective logic if added to the disabled list.
- If a post is of a disabled type but its individual comment status is "open" (post-level override), the REST restriction should still apply based on the post type rule.

---

## Playwright Notes

**Page URL:** `API: /wp-json/wp/v2/comments`

**Key Selectors:**
- `input[name="disable_comments_options[post_types][]"][value="post"]` — Post type checkbox for standard posts
- `input[name="disable_comments_options[remove_rest_API_comments]"]` — REST API disable checkbox

**Implementation hints:**
- Use WP-CLI in `beforeAll` to configure the option: `wp option patch update disable_comments_options post_types '["post"]' --format=json`.
- For Post A (type `post`) request: `expect(response.status()).toBe(403)`.
- For Page B (type `page`) request: `expect(response.status()).toBe(201)`.
- Retrieve Post A and Page B IDs dynamically via `wp post list --post_type=post --format=ids` and `wp post list --post_type=page --format=ids` in test setup.
- Clean up Page B's test comment in `afterAll`.

---

## Related

- **WordPress Filters:** `rest_pre_dispatch` (priority 10), `rest_pre_insert_comment` (priority 10)
- **Plugin Method:** `is_allowed_comment_type_request()`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **REST Endpoint:** `/wp/v2/comments`
- **Plugin Option Key:** `disable_comments_options.remove_rest_API_comments`, `disable_comments_options.post_types`
