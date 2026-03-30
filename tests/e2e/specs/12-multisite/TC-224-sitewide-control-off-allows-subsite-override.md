---
id: TC-224
title: "Sub-site admins can configure their own settings when sitewide control is disabled"
feature: multisite
priority: high
tags: [sitewide-settings, sub-site-admin, per-site-config, independence, multisite]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-224 — Sub-site admins can configure their own settings when sitewide control is disabled

## Summary
When `sitewide_settings = false`, each sub-site admin can set independent comment disable settings for their own site without affecting other sites. This test verifies true per-site isolation: disabling comments on sub-site 1 must leave sub-site 2 completely unaffected, and vice versa.

## Prerequisites
- [ ] WordPress Multisite is configured and running
- [ ] Disable Comments plugin is network-activated
- [ ] Logged in as Super Administrator (Network Admin)
- [ ] At least two sub-sites exist in the network
- [ ] `sitewide_settings = false` is set at network level (or will be set in step 1)
- [ ] Administrator accounts exist for both sub-site 1 and sub-site 2 (separate accounts)
- [ ] At least one published post with comments open exists on each sub-site

## Test Data

| Field | Value |
|-------|-------|
| Network Admin Settings URL | `/wp-admin/network/admin.php?page=disable_comments_settings` |
| Sub-site 1 Admin URL | `http://site1.example.com/wp-admin/` |
| Sub-site 2 Admin URL | `http://site2.example.com/wp-admin/` |
| Sub-site 1 Settings URL | `http://site1.example.com/wp-admin/admin.php?page=disable_comments_settings` |
| Sub-site 2 Settings URL | `http://site2.example.com/wp-admin/admin.php?page=disable_comments_settings` |
| Sub-site 1 Test Post URL | `http://site1.example.com/sample-post/` |
| Sub-site 2 Test Post URL | `http://site2.example.com/sample-post/` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | As super admin, navigate to network admin settings, set `sitewide_settings = false` (disable sitewide control), and save | Sitewide control is OFF; sub-sites may manage their own settings |
| 2 | Log out of super admin; log in as sub-site 1 administrator | Sub-site 1 admin dashboard is accessible |
| 3 | Navigate to Tools > Disable Comments on sub-site 1 | Settings page loads in editable/interactive state (not read-only) |
| 4 | Select "Remove Everywhere" and click Save Settings | Settings saved for sub-site 1; success notice appears |
| 5 | Navigate to the test post on sub-site 1 frontend | Comment form (`#respond`) is absent — comments disabled on sub-site 1 |
| 6 | Log out of sub-site 1 admin; log in as sub-site 2 administrator | Sub-site 2 admin dashboard is accessible |
| 7 | Navigate to Tools > Disable Comments on sub-site 2 | Settings page loads; settings reflect sub-site 2's own (default/unconfigured) state, NOT sub-site 1's settings |
| 8 | Leave sub-site 2 settings at default (comments NOT disabled) and click Save Settings (or simply do not change) | Sub-site 2 settings saved with no disable configuration active |
| 9 | Navigate to the test post on sub-site 2 frontend | Comment form (`#respond`) IS present — comments remain enabled on sub-site 2 |
| 10 | Navigate back to the test post on sub-site 1 frontend (verify persistence) | Comment form is still absent on sub-site 1 — its settings were not affected by sub-site 2's configuration |

## Expected Results
- Sub-site 1 has comments disabled on its frontend post pages.
- Sub-site 2 has comments enabled (comment form is present) on its frontend post pages.
- Settings between sub-site 1 and sub-site 2 are completely independent.
- Changes made on one sub-site have zero effect on the other sub-site's frontend behavior.

## Negative / Edge Cases
- Changing sub-site 1's settings must not alter sub-site 2's `wp_options` table and vice versa.
- If sitewide_settings is accidentally set to `true` during the test, the results will be invalid — verify the network option is `false` before proceeding.
- Re-enabling sitewide_settings=true after this test should override both sub-sites' individual settings.

## Playwright Notes
**Page URL:** `http://site1.example.com/wp-admin/admin.php?page=disable_comments_settings` and `http://site2.example.com/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `#respond` — comment form (present = comments enabled, absent = disabled)
- `input[type="radio"][value="1"], #remove-everywhere` — Remove Everywhere option
- `input[type="submit"], #submit` — Save Settings button
- `.notice-success` — settings saved confirmation

**Implementation hints:**
- Use separate `browser.newContext()` for each sub-site admin session to maintain independent cookies
- This test requires 3 sequential sessions: super admin, site1 admin, site2 admin — or use `page.goto()` with login/logout flows
- Assert comment form present: `await expect(page.locator('#respond')).toBeAttached()`
- Assert comment form absent: `await expect(page.locator('#respond')).not.toBeAttached()`
- Note: multisite tests often need multiple browser contexts for different user roles/sites

## Related
- **WordPress Functions:** `get_option()`, `update_option()`, `get_site_option()`, `update_site_option()`
- **AJAX Action:** N/A
- **Plugin Option Key:** `disable_comments_options.sitewide_settings` (network), `disable_comments_options` (per-site in `wp_X_options`)
