---
id: TC-010
title: "Dashboard 'Recent Comments' widget is hidden when globally disabled"
feature: disable-everywhere
priority: medium
tags: [disable-everywhere, dashboard, widgets, recent-comments, admin-ui]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-010 — Dashboard "Recent Comments" Widget Is Hidden When Globally Disabled

## Summary
Verifies that the WordPress dashboard "Recent Comments" widget is hidden when Remove Everywhere is active. The plugin uses `widgets_init` to call `disable_rc_widget()`, which unregisters or hides the Recent Comments dashboard widget. Additionally, `admin_print_styles-index.php` injects CSS via `admin_css()` to hide any remaining dashboard comment elements.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Remove Everywhere" mode is currently ACTIVE
- [ ] The "Recent Comments" widget was visible on the dashboard before enabling Remove Everywhere (confirm by checking baseline)

## Test Data

| Field | Value |
|-------|-------|
| Admin dashboard URL | `/wp-admin/index.php` |
| Widget element ID | `#dashboard_recent_comments` |
| Widget title | "Recent Comments" |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Confirm Remove Everywhere is active: navigate to `/wp-admin/admin.php?page=disable_comments_settings` | "Remove Everywhere" radio is selected |
| 2 | Navigate to the WordPress admin dashboard at `/wp-admin/index.php` | Dashboard page loads with the various dashboard widgets |
| 3 | Visually scan the dashboard for the "Recent Comments" widget | The "Recent Comments" widget is NOT visible anywhere on the dashboard |
| 4 | Open browser DevTools and search the DOM for `#dashboard_recent_comments` | The element `#dashboard_recent_comments` does NOT exist in the DOM |
| 5 | Search the DOM for any text "Recent Comments" within the dashboard widgets area (`#dashboard-widgets`) | No element containing the text "Recent Comments" as a widget title exists in the dashboard widget area |
| 6 | Check the "Screen Options" tab (click "Screen Options" at the top right of the dashboard) | The "Recent Comments" option is NOT listed in Screen Options (since the widget is unregistered, not just hidden) |
| 7 | (Baseline) Temporarily disable Remove Everywhere: switch to "Disable by Post Type", no types selected, save | Settings saved |
| 8 | Navigate to `/wp-admin/index.php` | Dashboard loads |
| 9 | Visually scan the dashboard for the "Recent Comments" widget | The "Recent Comments" widget IS visible on the dashboard (or available via Screen Options) |
| 10 | Confirm `#dashboard_recent_comments` is present in the DOM | Element exists in the DOM |
| 11 | Re-enable Remove Everywhere and save; navigate to `/wp-admin/index.php` | "Recent Comments" widget is absent again |

## Expected Results
- The "Recent Comments" widget (`#dashboard_recent_comments`) is absent from the WordPress admin dashboard DOM when Remove Everywhere is active
- The widget does not appear in the dashboard's "Screen Options" dropdown (it is unregistered, not merely hidden by preference)
- After disabling Remove Everywhere, the widget reappears on the dashboard
- Other dashboard widgets (At a Glance, Quick Draft, Activity, etc.) are not affected

## Negative / Edge Cases
- The widget may have been manually dismissed/hidden by the admin user via Screen Options; this would hide it regardless of plugin state — confirm the baseline shows it visible (step 8-10) before asserting its absence
- The plugin also injects CSS via `admin_print_styles-index.php` → `admin_css()` to hide comments in the activity feed area; check for `#dashboard_recent_comments` specifically as the primary assertion
- If the widget was never added to the dashboard (fresh WordPress install may not show it), the absence could be misleading — perform the baseline check first

## Playwright Notes
**Page URL:** `/wp-admin/index.php`

**Key Selectors:**
- `#dashboard_recent_comments` — the Recent Comments widget container (must NOT be attached)
- `#dashboard-widgets` — the dashboard widgets container
- `#dashboard_recent_comments .hndle span` — the widget title text "Recent Comments"
- `.metabox-holder` — the metabox holder that contains all dashboard widgets
- `#show-settings-link` or `#screen-options-link-wrap` — Screen Options toggle button

**Implementation hints:**
- `await expect(page.locator('#dashboard_recent_comments')).not.toBeAttached()` — primary assertion
- For a thorough check, verify in Screen Options:
  ```js
  await page.click('#show-settings-link');
  await expect(page.locator('#dashboard_recent_comments-hide')).not.toBeAttached();
  ```
- For the text-based check: `await expect(page.locator('#dashboard-widgets')).not.toContainText('Recent Comments')`
- After baseline restore: `await expect(page.locator('#dashboard_recent_comments')).toBeAttached()`
- Wait for the dashboard to fully load: `await page.waitForSelector('#dashboard-widgets')` before asserting widget presence

## Related
- **WordPress Filters:** N/A
- **WordPress Actions:** `widgets_init` → `disable_rc_widget()`, `admin_print_styles-index.php` → `admin_css()`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
