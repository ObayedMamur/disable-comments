---
id: TC-001
title: "Enable 'Remove Everywhere' mode and verify global comment disable"
feature: disable-everywhere
priority: smoke
tags: [disable-everywhere, settings, frontend, smoke]
type: functional
automation_status: automated
automation_file: "[TC-001-enable-remove-everywhere.spec.ts](TC-001-enable-remove-everywhere.spec.ts)"
created: 2026-03-30
updated: 2026-03-30
---

# TC-001 — Enable "Remove Everywhere" Mode and Verify Global Comment Disable

## Summary
Verifies that selecting the "Remove Everywhere" radio option and saving the settings correctly persists the configuration and disables comments on all post types across the frontend. This is the primary smoke test for the plugin's core feature.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Plugin is in a clean/default state (Remove Everywhere is NOT currently active)
- [ ] Test creates its own Post and Page with comments open via WP-CLI

## Test Data

| Field | Value |
|-------|-------|
| Settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Sample Post URL | `/?p=1` or `/hello-world/` |
| Sample Page URL | `/?page_id=2` or `/sample-page/` |
| Radio option value | `1` (Remove Everywhere) |
| Plugin option key | `disable_comments_options.remove_everywhere` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | (Setup) Create a test Post and a test Page with `comment_status=open` via WP-CLI | Post and Page exist and their URLs are known |
| 2 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads; the "Remove Everywhere" radio is NOT selected (clean state confirmed) |
| 3 | Navigate to the test Post's frontend URL | Post page loads; `#respond` and `#comment` are visible in the DOM (comment form present) |
| 4 | Return to settings; click the "Remove Everywhere" radio button | The radio becomes selected; the post-type checkboxes section collapses or becomes inactive |
| 5 | Click the "Save Changes" button | AJAX POST sent; SweetAlert success popup appears and auto-dismisses after 3 s |
| 6 | Reload the settings page | "Remove Everywhere" radio is still selected — setting persisted correctly |
| 7 | Navigate to the test Post frontend URL | `#respond` and `#comment-form` are completely absent from the DOM (not hidden — absent) |
| 8 | Navigate to the test Page frontend URL | `#respond` is completely absent from the DOM |
| 9 | (Verify) Run `wp option get disable_comments_options --format=json` | `remove_everywhere` is `true` (or truthy) in the stored options |

## Expected Results
- The "Remove Everywhere" radio button is selected and persists after page reload
- A success message is shown immediately after saving
- Frontend Post pages do not render the comment form (`#respond`, `#comments`, `comment-form`)
- Frontend Page pages do not render the comment form
- The WordPress options table entry `disable_comments_options` has `remove_everywhere = true`
- No JavaScript errors appear in the browser console during save or page load

## Negative / Edge Cases
- The comment form must NOT appear anywhere on the post/page (not hidden via CSS — it must be absent from the DOM entirely)
- The settings must NOT revert to a previous state upon page reload
- The AJAX save must NOT silently fail (no success notification = failed save)

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name="disable_comments_options[remove_everywhere]"][value="1"]` — Remove Everywhere radio button
- `input[name="disable_comments_options[remove_everywhere]"][value="2"]` — Disable by Post Type radio button
- `#disable-comments-settings-form` or `form.dc-settings-form` — settings form container
- `button[type="submit"], input[type="submit"]` — Save Changes button
- `.swal2-popup` or `.notice-success` — success notification after save
- `#respond` — WordPress standard comment form wrapper (must be absent on frontend)
- `#comments` — WordPress comments section container
- `#comment-form` — the actual `<form>` element for submitting comments

**Implementation hints:**
- Use `await expect(page.locator('#respond')).not.toBeAttached()` (not just hidden) to verify DOM absence
- After clicking Save, wait for the success notification: `await page.waitForSelector('.swal2-popup')` or equivalent
- For the reload persistence check: `await page.reload()` then re-query the radio button's `checked` property
- Use `page.evaluate(() => document.querySelectorAll('#respond').length === 0)` as an additional DOM check
- Check the frontend in a separate browser context or after clearing cookies if testing as a logged-out visitor
- Intercept the AJAX call with `page.route('**/admin-ajax.php', ...)` to assert the POST body contains `remove_everywhere=1`

## Related
- **WordPress Filters:** `comments_open`, `pings_open`, `get_comments_number`, `comments_array`
- **WordPress Actions:** `wp_loaded` → `init_wploaded_filters()`, `admin_init`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
