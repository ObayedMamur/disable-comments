---
id: TC-001
title: "Enable 'Remove Everywhere' mode and verify global comment disable"
feature: disable-everywhere
priority: smoke
tags: [disable-everywhere, settings, frontend, smoke]
type: functional
automation_status: manual
automation_file: ""
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
- [ ] At least one published Post exists (e.g. "Hello World" or a known test post)
- [ ] At least one published Page exists
- [ ] Plugin is in a clean/default state (Remove Everywhere is NOT currently active)

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
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads; the "Disable" tab is active by default |
| 2 | Confirm the current state: note which radio is selected (should NOT be "Remove Everywhere" for a clean state) | "Disable by Post Type" or no selection is active; the "Remove Everywhere" radio is not selected |
| 3 | Click the "Remove Everywhere" radio button | The radio button becomes selected; "Disable by Post Type" section (post type checkboxes) collapses or becomes inactive |
| 4 | Click the "Save Changes" button | An AJAX POST is sent to `admin-ajax.php` with action `disable_comments_save_settings`; a success notification appears (SweetAlert dialog or inline message confirming save) |
| 5 | Dismiss the success notification (if modal) | Notification closes; settings page remains displayed |
| 6 | Reload the settings page (`/wp-admin/admin.php?page=disable_comments_settings`) | Page reloads cleanly |
| 7 | Verify the "Remove Everywhere" radio is still selected after reload | The "Remove Everywhere" radio option is checked; setting has persisted correctly |
| 8 | In a new tab or after logging out, navigate to a published Post frontend URL (e.g. `/hello-world/`) | Post page loads successfully |
| 9 | Scroll to the bottom of the post page and inspect the comments section | The `#respond` div and `#comment-form` are NOT present in the DOM; no "Leave a Reply" heading or comment text field is visible |
| 10 | Navigate to a published Page frontend URL (e.g. `/sample-page/`) | Page loads successfully |
| 11 | Scroll to the bottom of the page and inspect the comments section | The `#respond` div is NOT present in the DOM; no comment form elements are visible on the page |
| 12 | Return to the settings page and confirm `remove_everywhere` value in the DB (optional: via WP-CLI `wp option get disable_comments_options`) | `remove_everywhere` is `true` (or `1`) in the stored options array |

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
