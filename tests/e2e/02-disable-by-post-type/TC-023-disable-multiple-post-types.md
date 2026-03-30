---
id: TC-023
title: "Disable comments for multiple post types simultaneously"
feature: disable-by-post-type
priority: high
tags: [disable-by-post-type, multiple-types, settings, frontend]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-023 — Disable Comments for Multiple Post Types Simultaneously

## Summary
Verifies that selecting more than one post type in "Disable by Post Type" mode correctly disables comments on all selected types while leaving unselected types unaffected. This test uses Posts and Pages as the disabled pair, with Media left enabled.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one published Post exists with comments open
- [ ] At least one published Page exists with comments open
- [ ] At least one accessible attachment page exists (or a custom post type with comments support, if available)
- [ ] Plugin is NOT currently set to "Remove Everywhere"

## Test Data

| Field | Value |
|-------|-------|
| Settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Sample Post URL | `/?p=1` or `/hello-world/` |
| Sample Page URL | `/?page_id=2` or `/sample-page/` |
| Sample Attachment URL | `/?attachment_id=5` (or any accessible attachment page) |
| Radio option value | `2` (Disable by Post Type) |
| Post type checkboxes | `post`, `page` |
| Plugin option key | `disable_comments_options.disabled_post_types` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads with the "Disable" tab active |
| 2 | Click the "Disable by Post Type" radio button | Radio becomes selected; post-type checkbox group is visible |
| 3 | Check the "Posts" checkbox | "Posts" is checked |
| 4 | Check the "Pages" checkbox | "Pages" is checked; both "Posts" and "Pages" are now checked simultaneously |
| 5 | Uncheck "Media" checkbox if it is checked | Only "Posts" and "Pages" are checked |
| 6 | Click the "Save Changes" button | AJAX save request fires; success notification appears |
| 7 | Dismiss the success notification | Notification closes |
| 8 | Reload the settings page | Page reloads cleanly |
| 9 | Confirm that both "Posts" and "Pages" checkboxes are still checked and "Media" is unchecked | Settings persisted with both types disabled correctly |
| 10 | Navigate to a published Post frontend URL (e.g. `/hello-world/`) | Post page loads |
| 11 | Inspect the comments section on the Post | The `#respond` div is NOT present in the DOM; comment form is absent |
| 12 | Navigate to a published Page frontend URL (e.g. `/sample-page/`) | Page loads |
| 13 | Inspect the comments section on the Page | The `#respond` div is NOT present in the DOM; comment form is absent |
| 14 | Navigate to an attachment page or another unselected post type (e.g. `/?attachment_id=5`) | That page loads (if accessible) |
| 15 | Inspect the comments section on the attachment/unselected type page | The `#respond` div IS present (or the type naturally supports comments); comment form is NOT disabled by the plugin |

## Expected Results
- "Disable by Post Type" radio persists after reload
- Both "Posts" and "Pages" checkboxes are checked after reload
- "Media" checkbox is unchecked after reload
- Post frontend pages have no comment form
- Page frontend pages have no comment form
- Unselected post types (e.g. Media/attachment) are not affected by the plugin
- `disable_comments_options.disabled_post_types` contains `["post", "page"]` (order may vary)
- `disable_comments_options.remove_everywhere` is `false`

## Negative / Edge Cases
- Selecting multiple types must NOT accidentally disable all types (i.e., must not behave as "Remove Everywhere")
- The saved array must contain exactly the selected post types — no extras, no missing entries
- If a custom post type is registered on the test site, verify it is NOT in the disabled list unless explicitly checked
- Unchecking a previously saved type and re-saving must remove that type from `disabled_post_types`

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name="disable_comments_options[remove_everywhere]"][value="2"]` — "Disable by Post Type" radio
- `input[name="disable_comments_options[disabled_post_types][]"][value="post"]` — Posts checkbox
- `input[name="disable_comments_options[disabled_post_types][]"][value="page"]` — Pages checkbox
- `input[name="disable_comments_options[disabled_post_types][]"][value="attachment"]` — Media checkbox
- `button[type="submit"], input[type="submit"]` — Save Changes button
- `.swal2-popup` or `.notice-success` — success notification
- `#respond` — comment form wrapper on frontend

**Implementation hints:**
- Check both checkboxes before saving: `await page.check('input[value="post"]')` and `await page.check('input[value="page"]')`
- After reload, assert both are checked: `expect(await page.locator('input[value="post"]').isChecked()).toBe(true)` and the same for `page`
- After reload, assert Media is unchecked: `expect(await page.locator('input[value="attachment"]').isChecked()).toBe(false)`
- On Post and Page frontend: `await expect(page.locator('#respond')).not.toBeAttached()`
- Capture the AJAX request payload to verify: `disabled_post_types[]=post&disabled_post_types[]=page` and absence of `remove_everywhere=1`
- If no accessible attachment page exists, document this step as not applicable (N/A) in the test run report

## Related
- **WordPress Filters:** `comments_open`, `pings_open`, `get_comments_number`, `comments_array`
- **WordPress Actions:** `remove_post_type_support` (applied to both `post` and `page`)
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.disabled_post_types`
- **Plugin Methods:** `is_post_type_disabled()`, `get_disabled_post_types()`, `get_all_post_types()`
