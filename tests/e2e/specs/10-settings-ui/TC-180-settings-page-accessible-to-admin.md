---
id: TC-180
title: "Settings page is accessible to Administrator at Tools > Disable Comments"
feature: settings-ui
priority: smoke
tags: [settings-ui, access-control, navigation, smoke]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-180 — Settings page is accessible to Administrator at Tools > Disable Comments

## Summary
Verifies that an Administrator can navigate to the plugin settings page via Tools > Disable Comments and that the page loads correctly without errors, displaying the expected heading and both tabs.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator (user with `manage_options` capability)
- [ ] WordPress admin dashboard is accessible

## Test Data

| Field | Value |
|-------|-------|
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Admin menu parent | Tools |
| Admin menu item | Disable Comments |
| Expected heading | Disable Comments |
| Expected tab 1 | Disable Comments |
| Expected tab 2 | Delete Comments |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Log in to WordPress as an Administrator | Successfully logged in; redirected to `/wp-admin/` dashboard |
| 2 | In the left admin sidebar, hover over or click the "Tools" menu item | Tools submenu expands and is visible |
| 3 | Click "Disable Comments" in the Tools submenu | Browser navigates to `/wp-admin/admin.php?page=disable_comments_settings` |
| 4 | Inspect the page title or main heading (h1 or h2) | Page displays a heading containing "Disable Comments" |
| 5 | Inspect the tab navigation area at the top of the settings form | Two tabs are visible: "Disable Comments" and "Delete Comments" |
| 6 | Verify the "Disable Comments" tab is active by default | The first tab is in an active/selected state; its content panel is visible |
| 7 | Check the browser console and page source for PHP errors or warnings | No PHP errors, warnings, or notices appear on the page; HTTP status is 200 |
| 8 | Verify the page URL in the browser address bar | URL matches `/wp-admin/admin.php?page=disable_comments_settings` (no redirect to error page) |

## Expected Results
- The settings page loads with HTTP 200 status
- Page heading contains "Disable Comments"
- Both tabs ("Disable Comments" and "Delete Comments") are visible in the tab navigation
- The "Disable Comments" tab is active by default and its content is displayed
- No PHP errors, fatal errors, or 404 responses occur
- The page is accessible via Tools > Disable Comments in the admin sidebar menu

## Negative / Edge Cases
- If the plugin is deactivated, the menu item should not appear and direct URL access should result in an error page
- If the user has been downgraded from Administrator mid-session, the page should deny access on the next request

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `#adminmenu a[href="tools.php"]` — Tools menu item in sidebar
- `#adminmenu a[href="admin.php?page=disable_comments_settings"]` — Disable Comments submenu link
- `.wrap h1, .wrap h2` — Main page heading
- `.dc-nav-tab-wrapper .nav-tab, .nav-tab-wrapper .nav-tab` — Tab navigation items
- `.nav-tab-active` — Currently active tab

**Implementation hints:**
- Use `await page.goto('/wp-admin/admin.php?page=disable_comments_settings')` for direct navigation
- Assert heading text with `await expect(page.locator('.wrap h1, .wrap h2').first()).toContainText('Disable Comments')`
- Count tabs: `await expect(page.locator('.nav-tab')).toHaveCount(2)`
- Verify active tab: `await expect(page.locator('.nav-tab-active').first()).toContainText('Disable Comments')`
- Check no PHP error output: `await expect(page.locator('body')).not.toContainText('Fatal error')`
- Check no 404: assert `response.status() === 200` when intercepting the page navigation response

## Related
- **WordPress Capability:** `manage_options`
- **Menu Registration:** `add_submenu_page('tools.php', ...)`
- **Plugin Option Key:** `disable_comments_options`
- **Related TC:** TC-181 (non-admin access blocked)
