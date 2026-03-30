---
id: TC-181
title: "Settings page is not accessible to non-admin users"
feature: settings-ui
priority: high
tags: [settings-ui, access-control, security, negative]
type: negative
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-181 — Settings page is not accessible to non-admin users

## Summary
A non-administrator user (e.g. Editor, Subscriber) must not be able to access the plugin settings page. Attempting to navigate to the settings URL should result in an access denied message or redirect, never in the actual settings being displayed.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] An Editor or Subscriber user account exists (e.g. username: `editor_user`, role: Editor)
- [ ] Logged in as the non-admin user (NOT as Administrator)

## Test Data

| Field | Value |
|-------|-------|
| Test user role | Editor (or Subscriber) |
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Expected outcome | Access denied message or redirect away from settings page |
| Denied message (typical) | "You don't have permission to access this page." or "You need a higher level of permission." |
| Required capability | `manage_options` (not held by Editor/Subscriber) |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Log out of any current admin session | Successfully logged out |
| 2 | Log in to WordPress as an Editor or Subscriber user | Successfully logged in as non-admin user; redirected to dashboard |
| 3 | Verify the left admin sidebar does NOT show a "Disable Comments" entry under Tools | The "Disable Comments" menu item is absent from the Tools submenu |
| 4 | Manually navigate to `/wp-admin/admin.php?page=disable_comments_settings` in the browser address bar | Browser loads the page at that URL |
| 5 | Inspect the page content | Page does NOT display the Disable Comments settings form, tabs, or any plugin settings |
| 6 | Verify an access-denied message or redirect occurred | Either: (a) page shows "You don't have permission to access this page" or similar, or (b) user is redirected to the dashboard or another admin page |
| 7 | Confirm the page does not display the "Disable Comments" settings heading or any plugin controls | No plugin heading, tabs, or save button are visible |
| 8 | Confirm no PHP errors or sensitive data is exposed | Page does not show PHP errors or any plugin internal data |

## Expected Results
- The "Disable Comments" submenu item is not visible in the Tools menu for non-admin users
- Direct URL access to the settings page is blocked
- User sees an access-denied/permission error message or is redirected
- The actual settings UI (tabs, form fields, Save button) is never rendered for non-admin users
- No plugin configuration data or PHP errors are exposed

## Negative / Edge Cases
- A user with a custom role that has `manage_options` granted explicitly should be able to access the page (this is a positive scenario out of scope here)
- Multisite: super admins may have access regardless of sub-site role
- If WordPress is in debug mode with `WP_DEBUG_DISPLAY=true`, ensure no PHP debug output leaks configuration data to the non-admin user

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `#adminmenu #menu-tools .wp-submenu a` — Tools submenu links (verify absence of settings link)
- `.wrap h1, .wrap h2` — Page heading (must NOT contain "Disable Comments")
- `#wpbody-content` — Main page body content area

**Implementation hints:**
- Log in as non-admin using a separate browser context or `storageState` for the editor user
- After login, assert `page.locator('#adminmenu a[href="admin.php?page=disable_comments_settings"]')` has count 0
- Navigate directly: `await page.goto('/wp-admin/admin.php?page=disable_comments_settings')`
- Assert the settings form is absent: `await expect(page.locator('.dc-nav-tab-wrapper, .nav-tab-wrapper')).toHaveCount(0)`
- Assert permission message or redirect: check for text like "permission" or "not allowed", or verify the final URL is not the settings page
- Example assertion: `await expect(page.locator('#wpbody-content')).not.toContainText('Disable Comments')`

## Related
- **WordPress Capability:** `manage_options`
- **Menu Registration:** `add_submenu_page('tools.php', ...)` with capability check
- **Plugin Option Key:** `disable_comments_options`
- **Related TC:** TC-180 (admin access granted)
