---
id: TC-028
title: "Switch from Remove Everywhere to Disable by Post Type mode"
feature: disable-by-post-type
priority: medium
tags: [disable-by-post-type, mode-switch, remove-everywhere, settings, frontend]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-028 — Switch from "Remove Everywhere" to "Disable by Post Type" Mode

## Summary
Verifies that after switching from global "Remove Everywhere" mode to selective "Disable by Post Type" mode, only the newly selected post types have comments disabled and previously globally-disabled types that were not re-selected now have comments restored on the frontend.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one published Post exists with comments open at the WordPress level
- [ ] At least one published Page exists with comments open at the WordPress level
- [ ] Plugin is currently set to "Remove Everywhere" (configure first if needed)

## Test Data

| Field | Value |
|-------|-------|
| Settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Sample Post URL | `/?p=1` or `/hello-world/` |
| Sample Page URL | `/?page_id=2` or `/sample-page/` |
| Starting state | "Remove Everywhere" (all types disabled) |
| Target state | "Disable by Post Type" — only `post` selected |
| Newly disabled type | `post` |
| Newly enabled (restored) type | `page` |
| Plugin option key | `disable_comments_options.remove_everywhere`, `disable_comments_options.disabled_post_types` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads |
| 2 | Confirm current state: "Remove Everywhere" is selected | Starting state confirmed; both Post and Page comment forms are globally disabled |
| 3 | As a logged-out visitor, verify that both the Post and Page frontend have no `#respond` | Baseline: both types are currently disabled (Remove Everywhere is active) |
| 4 | Return to the admin settings page | Settings page is visible |
| 5 | Click the "Disable by Post Type" radio button | The "Disable by Post Type" radio becomes selected; the post-type checkbox section becomes visible |
| 6 | Check only the "Posts" checkbox; ensure "Pages" and "Media" are unchecked | Only "Posts" is checked |
| 7 | Click "Save Changes" | AJAX save request fires; success notification appears |
| 8 | Dismiss the success notification | Notification closes |
| 9 | Reload the settings page | Page reloads cleanly |
| 10 | Confirm "Disable by Post Type" radio is selected and only "Posts" is checked after reload | Mode switch and post-type selection have persisted correctly |
| 11 | As a logged-out visitor, navigate to the Post frontend URL (e.g. `/hello-world/`) | Post page loads |
| 12 | Inspect the comments section on the Post | `#respond` is NOT present in the DOM (Posts remain disabled in the new mode) |
| 13 | Navigate to the Page frontend URL (e.g. `/sample-page/`) | Page loads |
| 14 | Inspect the comments section on the Page | `#respond` IS present in the DOM and the comment form is fully functional (Pages were restored by switching out of Remove Everywhere) |
| 15 | Interact with the comment form on the Page: click in the textarea and verify it is editable | The `#comment` textarea accepts input without errors |

## Expected Results
- "Disable by Post Type" radio persists after reload
- Only "Posts" checkbox is checked after reload
- Post frontend remains without comment form (selectively disabled)
- Page frontend has the comment form restored and fully functional
- `disable_comments_options.remove_everywhere` is `false`
- `disable_comments_options.disabled_post_types` contains `["post"]`
- No JavaScript errors in the browser console

## Negative / Edge Cases
- The Page comment form must transition from absent (Remove Everywhere was active) to present (selective mode with Pages not selected) — the before-and-after sequence is the core of this test
- If the plugin requires at least one post type to be selected when in "Disable by Post Type" mode, attempting to save with no types checked should show a validation error; this test verifies the happy path (at least Posts selected)
- If the old `disabled_post_types` value was `["post", "page"]` from a previous test run, ensure the checkboxes are correctly initialized when switching modes — pre-existing values should not bleed in unless re-selected
- Switching modes should not require a full page refresh beyond the explicit reload; verify the UI reflects the correct state immediately after save

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name="disable_comments_options[remove_everywhere]"][value="1"]` — "Remove Everywhere" radio
- `input[name="disable_comments_options[remove_everywhere]"][value="2"]` — "Disable by Post Type" radio
- `input[name="disable_comments_options[disabled_post_types][]"][value="post"]` — Posts checkbox
- `input[name="disable_comments_options[disabled_post_types][]"][value="page"]` — Pages checkbox
- `button[type="submit"], input[type="submit"]` — Save Changes button
- `.swal2-popup` or `.notice-success` — success notification
- `#respond` — comment form wrapper on frontend
- `#comment` — comment textarea within `#respond`

**Implementation hints:**
- Confirm the "Remove Everywhere" starting state: `await expect(page.locator('input[value="1"]')).toBeChecked()`
- Pre-test baseline on frontend: verify both Post and Page have `#respond` absent
- After mode switch, wait for checkboxes to appear: `await page.waitForSelector('input[value="post"]', { state: 'visible' })`
- After reload, assert: `await expect(page.locator('input[value="2"]')).toBeChecked()` and `await expect(page.locator('input[value="post"]')).toBeChecked()`
- On Page frontend: `await expect(guestPage.locator('#respond')).toBeVisible()` and `await expect(guestPage.locator('#comment')).toBeEditable()`
- On Post frontend: `await expect(guestPage.locator('#respond')).not.toBeAttached()`
- Use separate guest browser context for all frontend checks

## Related
- **WordPress Filters:** `comments_open`, `get_comments_number`, `comments_array`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`, `disable_comments_options.disabled_post_types`
- **Plugin Methods:** `is_post_type_disabled()`, `get_disabled_post_types()`
