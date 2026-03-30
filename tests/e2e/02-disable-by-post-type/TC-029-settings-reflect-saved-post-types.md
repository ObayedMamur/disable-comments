---
id: TC-029
title: "Settings page correctly reflects previously saved post type selections"
feature: disable-by-post-type
priority: high
tags: [disable-by-post-type, settings-persistence, ui-state, settings]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-029 — Settings Page Correctly Reflects Previously Saved Post Type Selections

## Summary
Verifies that after saving a "Disable by Post Type" configuration, returning to the settings page (fresh page load) correctly pre-checks the previously saved post type checkboxes and shows the "Disable by Post Type" radio as selected. This confirms proper UI state hydration from the stored `disable_comments_options` option.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Plugin is not locked to "Remove Everywhere" — it must allow switching to "Disable by Post Type"
- [ ] Standard post types (Posts, Pages, Media) are available in the settings UI

## Test Data

| Field | Value |
|-------|-------|
| Settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Radio option | "Disable by Post Type" (value `2`) |
| Checkbox selection (round 1) | `post` only |
| Checkbox selection (round 2) | `post`, `page` |
| Checkbox selection (round 3) | `page` only |
| Plugin option key | `disable_comments_options.disabled_post_types` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads |
| 2 | Select "Disable by Post Type" radio; check only "Posts"; uncheck all other types | Only "Posts" is checked |
| 3 | Click "Save Changes" | Success notification appears |
| 4 | Dismiss the notification | Notification closes |
| 5 | Navigate away from the settings page (e.g. to `WP Admin > Dashboard`) | Dashboard loads |
| 6 | Navigate back to `/wp-admin/admin.php?page=disable_comments_settings` via a fresh page load | Settings page loads fresh (not from browser cache) |
| 7 | Inspect the radio buttons: verify "Disable by Post Type" is selected | "Disable by Post Type" radio is checked; "Remove Everywhere" radio is not checked |
| 8 | Inspect the post-type checkboxes: verify "Posts" is checked and "Pages" and "Media" are unchecked | Only "Posts" checkbox is in the checked state; all others are unchecked |
| 9 | Now change the selection: check "Pages" as well (leave "Posts" checked) | Both "Posts" and "Pages" are checked; "Media" remains unchecked |
| 10 | Click "Save Changes" | Success notification appears |
| 11 | Reload the settings page via the browser address bar (hard reload to bypass cache) | Settings page loads fresh |
| 12 | Verify "Disable by Post Type" is still selected and both "Posts" and "Pages" are checked | Both checkboxes are checked; "Media" is unchecked; radio is correct |
| 13 | Change selection again: uncheck "Posts", leave only "Pages" checked | Only "Pages" is checked |
| 14 | Click "Save Changes" | Success notification appears |
| 15 | Reload the settings page | Settings page loads fresh |
| 16 | Verify only "Pages" is checked and "Posts" and "Media" are unchecked | Checkbox state matches the last saved selection exactly |

## Expected Results
- After each save, the settings page reloads showing exactly the checkboxes that were saved — no more, no less
- The "Disable by Post Type" radio is always pre-selected on reload (as long as that mode was saved)
- Checkbox state accurately reflects `disable_comments_options.disabled_post_types` from the database
- No phantom checkboxes appear (e.g. a previously saved type re-appearing after being unchecked and re-saved)
- No JavaScript errors during any save or page reload

## Negative / Edge Cases
- A previously checked type that is unchecked and re-saved must NOT re-appear as checked on the next page load
- If the database value is an empty array `[]` and "Disable by Post Type" is selected, the UI should either show no checkboxes checked or enforce at least one selection before allowing save
- Browser caching must not cause stale checkbox states — verify with a hard reload (Ctrl+Shift+R / Cmd+Shift+R) or by navigating away and back
- If the option key `disable_comments_options` is corrupted or missing in the database, the settings page must degrade gracefully (no PHP warnings, defaults shown)
- Saving with all checkboxes unchecked while "Disable by Post Type" is selected must be prevented by validation (this is a negative check complementary to this test's happy path)

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name="disable_comments_options[remove_everywhere]"][value="2"]` — "Disable by Post Type" radio
- `input[name="disable_comments_options[remove_everywhere]"][value="1"]` — "Remove Everywhere" radio
- `input[name="disable_comments_options[disabled_post_types][]"][value="post"]` — Posts checkbox
- `input[name="disable_comments_options[disabled_post_types][]"][value="page"]` — Pages checkbox
- `input[name="disable_comments_options[disabled_post_types][]"][value="attachment"]` — Media checkbox
- `button[type="submit"], input[type="submit"]` — Save Changes button
- `.swal2-popup` or `.notice-success` — success notification

**Implementation hints:**
- After each save, navigate away and back using `await page.goto('/wp-admin/')` then `await page.goto('/wp-admin/admin.php?page=disable_comments_settings')` to ensure a fresh page load
- Use `page.reload({ waitUntil: 'networkidle' })` with cache bypass: `await page.context().clearCookies()` is not needed; use `page.reload()` which triggers a fresh GET
- For hard reload: `await page.evaluate(() => location.reload(true))` or use `page.goto(url, { waitUntil: 'networkidle' })`
- Assert each checkbox state individually after reload:
  - `await expect(page.locator('input[value="post"]')).toBeChecked()` (round 1)
  - `await expect(page.locator('input[value="page"]')).not.toBeChecked()` (round 1)
  - `await expect(page.locator('input[value="attachment"]')).not.toBeChecked()` (round 1)
- Use WP-CLI to verify the DB value after each save: `wp option get disable_comments_options --format=json | jq '.disabled_post_types'`
- This test is well-suited for a data-driven parameterized Playwright test running through multiple selection combinations in sequence

## Related
- **WordPress Options API:** `get_option('disable_comments_options')`, `update_option('disable_comments_options', ...)`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.disabled_post_types`, `disable_comments_options.remove_everywhere`
- **Plugin Methods:** `get_disabled_post_types()`, `get_all_post_types()`
