---
id: TC-230
title: "Sub-site admin can view (but not change) network-configured settings when sitewide control is ON"
feature: multisite
priority: medium
tags: [multisite, sitewide-settings, permissions, read-only]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-230 — Sub-site admin can view (but not change) network-configured settings when sitewide control is ON

## Summary
When `sitewide_settings = true`, a sub-site administrator visiting their own Tools > Disable Comments settings page should see the network-configured settings displayed (so they understand why comments are disabled), but must not be able to modify or save different settings. This tests the locked/read-only state presented to sub-site admins under network control.

## Prerequisites
- [ ] WordPress Multisite is configured and running
- [ ] Disable Comments plugin is network-activated
- [ ] Logged in as Super Administrator initially (to configure network settings)
- [ ] `sitewide_settings = true` is set at network admin level
- [ ] "Remove Everywhere" is configured at network level
- [ ] A sub-site administrator account exists (site-level admin, NOT a super admin)

## Test Data

| Field | Value |
|-------|-------|
| Network Admin Settings URL | `/wp-admin/network/admin.php?page=disable_comments_settings` |
| Sub-site Admin Settings URL | `http://site1.example.com/wp-admin/admin.php?page=disable_comments_settings` |
| Sub-site admin username | `site1admin` (example) |
| Sub-site admin role | Administrator (on site1 only, not super admin) |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | As super admin: navigate to Network Admin settings, enable "Sitewide Settings" control and "Remove Everywhere", save | Settings saved — network now enforces Remove Everywhere on all sites |
| 2 | Log out of the super admin account | Logged out successfully |
| 3 | Log in as the sub-site 1 administrator (site-level admin, not super admin) | Logged in as site1admin |
| 4 | Navigate to Tools > Disable Comments on sub-site 1 | Page at `http://site1.example.com/wp-admin/admin.php?page=disable_comments_settings` loads (no 404 or access error) |
| 5 | Observe the settings page content | Page shows the current configuration: Remove Everywhere is indicated as active |
| 6 | Check whether form inputs (radio buttons, checkboxes) are disabled or the page shows a "managed by network" notice | Inputs are either disabled/read-only OR a notice is shown explaining settings are network-controlled |
| 7 | Attempt to change a setting (e.g. click "Disable by Post Type" radio if enabled) | Either the input does not respond, OR any change does not persist |
| 8 | Attempt to click "Save Settings" if the button is present | Either button is absent/disabled, OR clicking it has no effect on network settings |
| 9 | Navigate to a published post on sub-site 1 as a logged-out visitor | Post frontend loads |
| 10 | Verify the comment form is still absent (network settings still enforced) | `#respond` is not in the DOM — Remove Everywhere is still in effect |

## Expected Results
- Sub-site admin can access the settings page without a permissions error
- The page clearly communicates that settings are controlled at the network level
- Sub-site admin cannot override or save different settings
- The network-configured Remove Everywhere remains in effect on the frontend

## Negative / Edge Cases
- If the plugin completely hides the settings page from sub-site admins under sitewide control, that is also acceptable behavior — verify a graceful redirect or notice rather than a broken 404
- The sub-site admin must not accidentally be granted super admin powers by accessing this page

## Playwright Notes
**Page URL:** `http://site1.example.com/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[type="radio"], input[type="checkbox"]` — form inputs (should be disabled)
- `.notice, .dc-network-notice` — network control notice message
- `[type="submit"]` — Save button (should be absent or disabled)

**Implementation hints:**
- Use a separate `browser.newContext()` for the sub-site admin session
- `await expect(page.locator('input[type="radio"]').first()).toBeDisabled()` — if inputs are rendered as disabled
- `await expect(page.locator('.dc-network-notice, .notice:has-text("network")')).toBeVisible()` — if a notice is shown
- After attempting any change, verify the frontend still has no comment form: `await expect(page.locator('#respond')).not.toBeAttached()`
- Note: Sub-site URLs in subdomain multisite require separate cookie domains in Playwright

## Related
- **WordPress Functions:** `get_site_option()`, `is_super_admin()`
- **Plugin Option Key:** `disable_comments_options.sitewide_settings`
- **Plugin Method:** `is_network_admin()`, settings page rendering logic
