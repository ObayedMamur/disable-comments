---
id: TC-221
title: "Network admin settings page is accessible and loads correctly"
feature: multisite
priority: smoke
tags: [settings-page, network-admin, multisite, accessibility]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-221 — Network admin settings page is accessible and loads correctly

## Summary
Verifies the settings page exists at network admin level and loads without errors showing both tabs (Disable and Delete). Confirms multisite-specific UI elements such as the site selection widget are present on the network admin settings page.

## Prerequisites
- [ ] WordPress Multisite is configured and running
- [ ] Disable Comments plugin is network-activated
- [ ] Logged in as Super Administrator (Network Admin)
- [ ] At least two sub-sites exist in the network
- [ ] A non-super-admin site administrator account exists for negative case verification

## Test Data

| Field | Value |
|-------|-------|
| Network Admin Settings URL | `/wp-admin/network/admin.php?page=disable_comments_settings` |
| Network Admin Dashboard URL | `/wp-admin/network/` |
| Sub-site 1 Admin URL | `http://site1.example.com/wp-admin/` |
| Sub-site 2 Admin URL | `http://site2.example.com/wp-admin/` |
| Expected Page Heading | `Disable Comments` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Log in as Super Administrator | Successfully logged in; WordPress admin dashboard visible |
| 2 | Navigate to Network Admin via My Sites > Network Admin > Dashboard | Network Admin dashboard loads at `/wp-admin/network/` |
| 3 | Navigate directly to `/wp-admin/network/admin.php?page=disable_comments_settings` | Page loads with HTTP 200; no redirect to login or error page |
| 4 | Verify the page heading reads "Disable Comments" | `h1` or `.wrap h2` heading contains "Disable Comments" text |
| 5 | Verify both the "Disable Comments" tab and "Delete Comments" tab are visible | Two tab elements are present and clickable in the settings UI |
| 6 | Check for PHP errors, warnings, or 404/403 messages in the page content | No `<b>Fatal error</b>`, `<b>Warning</b>`, "Page not found", or "Access Denied" text is visible |
| 7 | Verify the site selection UI is present on the page (multisite-specific) | A site selector widget listing sub-sites is rendered on the settings page |

## Expected Results
- Settings page loads fully at `/wp-admin/network/admin.php?page=disable_comments_settings` with HTTP 200.
- Page heading "Disable Comments" is visible.
- Both the "Disable Comments" and "Delete Comments" tabs are present and functional.
- No PHP errors or access-denied messages appear.
- The multisite-specific site selector UI widget is present on the page.

## Negative / Edge Cases
- A regular site administrator (not super admin) who attempts to access the network admin URL directly should be redirected or shown an "Access Denied" / "You are not allowed to access this page" message.
- If the plugin is not network-activated, navigating to the network admin settings URL should result in a 404 or "page not found in settings" error.

## Playwright Notes
**Page URL:** `/wp-admin/network/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `h1, .wrap > h2` — page heading containing "Disable Comments"
- `.nav-tab-wrapper .nav-tab` — tab elements for Disable/Delete tabs
- `.dc-site-list, .dc-sites-list, #disable-comments-site-list` — site selector widget container

**Implementation hints:**
- Check `page.url()` matches a network admin URL pattern: `expect(page.url()).toContain('/wp-admin/network/')`
- Verify no error text: `await expect(page.locator('body')).not.toContainText('Fatal error')`
- For negative case: use `browser.newContext()` with site-admin credentials and assert redirect or access-denied notice
- Note: multisite tests often need multiple browser contexts for different user roles/sites

## Related
- **WordPress Functions:** `get_site_option()`, `is_network_admin()`, `current_user_can('manage_network')`
- **AJAX Action:** `get_sub_sites` (used by the site selection widget on page load)
- **Plugin Option Key:** `disable_comments_options.is_network_options`
