---
id: TC-186
title: "Plugin setup notice is shown on admin pages when plugin is not yet configured"
feature: settings-ui
priority: medium
tags: [admin-notice, setup, onboarding, first-run]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-186 — Plugin Setup Notice Is Shown on Admin Pages When Plugin Is Not Yet Configured

## Summary
After fresh plugin activation (before any settings are saved), WordPress admin pages should display an admin notice prompting the user to configure the plugin. The notice contains a link to the settings page.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Plugin settings have never been saved (fresh install state) — simulate by deleting the `disable_comments_options` option from the database, or by deactivating and reactivating the plugin

## Test Data

| Field | Value |
|-------|-------|
| Initial state | `disable_comments_options` option does not exist or `settings_saved = false` |
| Notice type | `.notice-warning` or `.notice-info` (WordPress admin notice CSS class) |
| Admin page to check | `/wp-admin/index.php` (Dashboard) |
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Simulate fresh activation: deactivate and reactivate the plugin (or delete the option via WP-CLI: `wp option delete disable_comments_options`) | Plugin is in unconfigured state; `settings_saved` is false or the option is absent |
| 2 | Navigate to any WordPress admin page (e.g. Dashboard `/wp-admin/index.php`) | Dashboard loads normally |
| 3 | Verify an admin notice is displayed in the notices area (below the page title) | A `.notice` div is visible on the page |
| 4 | Verify the notice mentions "Disable Comments" and prompts configuration | Notice text references "Disable Comments" and instructs the user to configure the plugin |
| 5 | Verify the notice contains a link to the settings page (`/wp-admin/admin.php?page=disable_comments_settings`) | An anchor element pointing to the settings page URL is present inside the notice |
| 6 | Click the settings link — verify it navigates correctly to the settings page | Browser navigates to the settings page; page loads without errors |
| 7 | Complete a settings save — navigate back to Dashboard and verify the notice is gone | The admin notice no longer appears on the Dashboard or other admin pages |

## Expected Results
- Admin notice appears on admin pages when plugin is unconfigured
- Notice contains a link to the settings page
- After saving settings, the notice disappears

## Negative / Edge Cases
- Notice must not appear after settings are saved (`settings_saved = true`)
- Notice must appear on all admin pages, not just the settings page

## Playwright Notes
**Page URL:** `/wp-admin/index.php`

**Key Selectors:**
- `.notice, .notice-warning` — WordPress admin notice container
- `.notice a[href*="disable_comments_settings"]` — Settings link in notice

**Implementation hints:**
- `await expect(page.locator('.notice:has-text("Disable Comments")')).toBeVisible()`
- After save: `await expect(page.locator('.notice:has-text("Disable Comments")')).not.toBeVisible()`

## Related
- **WordPress Action:** `all_admin_notices`
- **Plugin Option Key:** `disable_comments_options.settings_saved`
- **Plugin Method:** `settings_notice()`
