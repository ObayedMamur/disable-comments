---
id: TC-027
title: "Switch from Disable by Post Type to Remove Everywhere mode"
feature: disable-by-post-type
priority: medium
tags: [disable-by-post-type, mode-switch, remove-everywhere, settings, frontend]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-027 — Switch from "Disable by Post Type" to "Remove Everywhere" Mode

## Summary
Verifies that switching from selective "Disable by Post Type" mode (only Posts disabled) to "Remove Everywhere" mode is a clean transition — after saving the new mode, all post types including previously enabled ones (Pages) now have comments disabled on the frontend.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least one published Post exists with comments open
- [ ] At least one published Page exists with comments open
- [ ] Plugin is currently set to "Disable by Post Type" with only "Posts" disabled (configure first if needed)

## Test Data

| Field | Value |
|-------|-------|
| Settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Sample Post URL | `/?p=1` or `/hello-world/` |
| Sample Page URL | `/?page_id=2` or `/sample-page/` |
| Starting state | "Disable by Post Type" — only `post` selected |
| Target state | "Remove Everywhere" |
| Plugin option key | `disable_comments_options.remove_everywhere` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads |
| 2 | Confirm current state: "Disable by Post Type" is selected and only "Posts" is checked | Starting state is confirmed |
| 3 | As a logged-out visitor, navigate to the Page frontend URL (e.g. `/sample-page/`) and confirm `#respond` is present | Baseline: Page still has the comment form before the mode switch |
| 4 | Return to the admin settings page | Settings page is visible |
| 5 | Click the "Remove Everywhere" radio button | The "Remove Everywhere" radio becomes selected; the post-type checkbox section collapses or becomes inactive (checkboxes may be hidden or grayed out) |
| 6 | Click "Save Changes" | AJAX save request fires; success notification appears |
| 7 | Dismiss the success notification | Notification closes |
| 8 | Reload the settings page | Page reloads cleanly |
| 9 | Confirm "Remove Everywhere" radio is selected after reload | Mode switch has persisted; "Disable by Post Type" is no longer the active selection |
| 10 | As a logged-out visitor, navigate to the Post frontend URL (e.g. `/hello-world/`) | Post page loads |
| 11 | Inspect the comments section on the Post | `#respond` is NOT present in the DOM (was already disabled before the switch; still disabled) |
| 12 | Navigate to the Page frontend URL (e.g. `/sample-page/`) | Page loads |
| 13 | Inspect the comments section on the Page | `#respond` is NOT present in the DOM (newly disabled as a result of the mode switch to Remove Everywhere) |
| 14 | (Optional) Verify via WP-CLI: `wp option get disable_comments_options --format=json` | `"remove_everywhere": true` (or `1`) in the stored options |

## Expected Results
- "Remove Everywhere" radio persists after reload
- Both Post and Page frontend pages have no comment form after the switch
- The switch from selective to global mode does not leave any post type enabled
- `disable_comments_options.remove_everywhere` is `true`
- No JavaScript errors in the browser console

## Negative / Edge Cases
- After switching to "Remove Everywhere", the `disabled_post_types` array in the database may retain its previous value; this is acceptable as long as `remove_everywhere = true` takes precedence in plugin logic
- The Page comment form must transition from present (before switch) to absent (after switch) — verifying this before-and-after sequence is the core of this test
- If the UI does not visually collapse or disable the post-type checkboxes when "Remove Everywhere" is selected, document as a UI issue but still verify the save behavior
- Switching modes must not require a page refresh beyond the explicit reload in step 8; no further manual action should be needed

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name="disable_comments_options[remove_everywhere]"][value="1"]` — "Remove Everywhere" radio
- `input[name="disable_comments_options[remove_everywhere]"][value="2"]` — "Disable by Post Type" radio
- `.post-type-checkboxes` or the checkbox wrapper div — may collapse/hide when Remove Everywhere is selected
- `button[type="submit"], input[type="submit"]` — Save Changes button
- `.swal2-popup` or `.notice-success` — success notification
- `#respond` — comment form wrapper on frontend (must be absent on both Post and Page after switch)

**Implementation hints:**
- Capture the before state on the Page: `await expect(guestPage.locator('#respond')).toBeVisible()` before switching modes
- After the switch and reload, assert on the Page: `await expect(guestPage.locator('#respond')).not.toBeAttached()`
- Verify radio persistence: `await expect(page.locator('input[value="1"]')).toBeChecked()` after reload
- Check AJAX payload: `remove_everywhere=1` should be present, `disabled_post_types` may or may not be in the payload depending on implementation
- Optionally check that the post-type checkbox section is hidden/disabled in the UI when "Remove Everywhere" is selected: `await expect(page.locator('.post-type-section')).not.toBeVisible()`

## Related
- **WordPress Filters:** `comments_open`, `get_comments_number`, `comments_array`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`, `disable_comments_options.disabled_post_types`
- **Plugin Methods:** `is_post_type_disabled()`, `get_disabled_post_types()`
