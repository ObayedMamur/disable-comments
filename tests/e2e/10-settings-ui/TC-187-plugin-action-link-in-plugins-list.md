---
id: TC-187
title: "Settings action link appears in Plugins list for quick navigation"
feature: settings-ui
priority: medium
tags: [plugins-page, action-link, navigation, admin-ui]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-187 — Settings Action Link Appears in Plugins List for Quick Navigation

## Summary
The Disable Comments plugin must add a "Settings" link in its row on the Plugins admin page, providing quick access to the settings page without navigating through the Tools menu.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Plugins page is accessible at `/wp-admin/plugins.php`

## Test Data

| Field | Value |
|-------|-------|
| Plugins page URL | `/wp-admin/plugins.php` |
| Plugin slug | `disable-comments` |
| Expected action link label | Settings |
| Expected settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/plugins.php` | Plugins page loads and lists all installed plugins |
| 2 | Find the "Disable Comments" plugin row in the list | Plugin row is visible with its name, description, and action links |
| 3 | Inspect the action links below the plugin name (usually: Deactivate \| Edit \| Settings) | Action links area is visible in the plugin row |
| 4 | Verify a "Settings" link is present in those action links | A link labeled "Settings" appears in the plugin row actions |
| 5 | Click the "Settings" link | Browser begins navigation |
| 6 | Verify it navigates to `/wp-admin/admin.php?page=disable_comments_settings` | URL matches the expected settings page path |
| 7 | Verify the settings page loads correctly | Settings page renders without PHP errors or missing content |

## Expected Results
- "Settings" link is visible in the Disable Comments plugin row on the Plugins page
- The link correctly navigates to the settings page
- No 404 or error on the settings page

## Negative / Edge Cases
- The link must also appear in the network admin plugins list when the plugin is network-activated

## Playwright Notes
**Page URL:** `/wp-admin/plugins.php`

**Key Selectors:**
- `tr[data-slug="disable-comments"] .row-actions` — Plugin row action links container
- `tr[data-slug="disable-comments"] a[href*="disable_comments_settings"]` — Settings link

**Implementation hints:**
- `await page.goto('/wp-admin/plugins.php')`
- `await expect(page.locator('tr[data-slug="disable-comments"] a[href*="disable_comments_settings"]')).toBeVisible()`
- `await page.click('tr[data-slug="disable-comments"] a[href*="disable_comments_settings"]')`
- `await expect(page).toHaveURL(/disable_comments_settings/)`

## Related
- **WordPress Filter:** `plugin_action_links`, `network_admin_plugin_action_links`
- **Plugin Method:** `plugin_action_links()`
