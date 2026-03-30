---
id: TC-022
title: "Disable comments for Media/Attachments post type only"
feature: disable-by-post-type
priority: medium
tags: [disable-by-post-type, attachments, media, settings, frontend]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-022 — Disable Comments for "Media/Attachments" Post Type Only

## Summary
Verifies that selecting "Disable by Post Type" with only the "Media" (attachment) checkbox disables comments on attachment pages while leaving Posts and Pages unaffected. Attachment pages must be enabled in WordPress media settings for this test to produce a visible frontend result.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one published Post exists with comments open
- [ ] At least one published Page exists with comments open
- [ ] Attachment pages are accessible: go to `Settings > Media` and confirm a media item has a dedicated permalink (e.g. `/wp-content/uploads/...` or `/?attachment_id=N`)
- [ ] At least one media attachment exists with a known URL (e.g. `/?attachment_id=5`)
- [ ] Plugin is NOT currently set to "Remove Everywhere"

## Test Data

| Field | Value |
|-------|-------|
| Settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Sample Post URL | `/?p=1` or `/hello-world/` |
| Sample Page URL | `/?page_id=2` or `/sample-page/` |
| Sample Attachment URL | `/?attachment_id=5` or `/media/sample-image/` |
| Radio option value | `2` (Disable by Post Type) |
| Post type checkbox | `attachment` |
| Plugin option key | `disable_comments_options.disabled_post_types` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads with the "Disable" tab active |
| 2 | Click the "Disable by Post Type" radio button | The radio becomes selected; the post-type checkbox group becomes visible |
| 3 | Uncheck "Posts" and "Pages" if checked; check only the "Media" (attachment) checkbox | Only the "Media" checkbox is in a checked state |
| 4 | Click the "Save Changes" button | An AJAX request is sent and a success notification appears |
| 5 | Dismiss the success notification | Notification closes |
| 6 | Reload the settings page | Page reloads cleanly |
| 7 | Confirm "Disable by Post Type" radio is still selected and only "Media" checkbox is checked | Settings persisted correctly |
| 8 | Navigate to the attachment page URL (e.g. `/?attachment_id=5`) | Attachment page loads (may show a single image or file) |
| 9 | Inspect the comments section on the attachment page | The `#respond` div is NOT present in the DOM; no comment form elements are visible |
| 10 | Navigate to a published Post frontend URL (e.g. `/hello-world/`) | Post page loads successfully |
| 11 | Inspect the comments section on the Post page | The `#respond` div IS present; the comment form renders normally |
| 12 | Navigate to a published Page frontend URL (e.g. `/sample-page/`) | Page loads successfully |
| 13 | Inspect the comments section on the Page | The `#respond` div IS present; the comment form renders normally |

## Expected Results
- "Disable by Post Type" radio persists after reload
- Only "Media" checkbox is checked after reload
- Attachment pages have no comment form (`#respond` absent from DOM)
- Post pages retain the fully functional comment form
- Page pages retain the fully functional comment form
- `disable_comments_options.remove_everywhere` is `false`
- `disable_comments_options.disabled_post_types` contains `["attachment"]`

## Negative / Edge Cases
- If the WordPress theme does not render a comment form on attachment pages by default, the test must still verify absence of `#respond` to confirm the plugin is not accidentally removing it from other post types
- Some WordPress configurations redirect attachment URLs or disable attachment pages entirely; document this condition and skip the attachment frontend check if the page is not accessible (404 or redirect)
- The theme's attachment template may not include `comments_template()`; in that case, the test is inconclusive for the attachment page but can still confirm Posts and Pages are unaffected
- Note: The "Organize my uploads into month- and year-based folders" setting in `Settings > Media` does not affect attachment page availability; attachment pages are always registered as post type `attachment` regardless

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name="disable_comments_options[remove_everywhere]"][value="2"]` — "Disable by Post Type" radio
- `input[name="disable_comments_options[disabled_post_types][]"][value="attachment"]` — Media checkbox
- `input[name="disable_comments_options[disabled_post_types][]"][value="post"]` — Posts checkbox
- `input[name="disable_comments_options[disabled_post_types][]"][value="page"]` — Pages checkbox
- `button[type="submit"], input[type="submit"]` — Save Changes button
- `.swal2-popup` or `.notice-success` — success notification
- `#respond` — comment form wrapper on frontend

**Implementation hints:**
- Find a valid attachment URL via WP-CLI: `wp post list --post_type=attachment --post_status=inherit --fields=ID,guid` or via the Media Library admin
- If attachment pages return 404, mark the attachment frontend step as a known limitation and assert only that Posts and Pages remain unaffected
- Use `await expect(page.locator('#respond')).not.toBeAttached()` on the attachment page
- Use `await expect(page.locator('#respond')).toBeVisible()` on both Post and Page
- Consider querying the attachment page as a logged-out visitor to avoid any admin-session side effects

## Related
- **WordPress Filters:** `comments_open`, `pings_open`, `get_comments_number`, `comments_array`
- **WordPress Actions:** `remove_post_type_support` (for `comments` and `trackbacks` on `attachment`)
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.disabled_post_types`
- **Plugin Methods:** `is_post_type_disabled('attachment')`, `get_disabled_post_types()`
