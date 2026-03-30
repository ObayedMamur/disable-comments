---
id: TC-220
title: "Plugin can be network-activated from Network Admin Plugins page"
feature: multisite
priority: smoke
tags: [network-activation, multisite, plugins]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-220 — Plugin can be network-activated from Network Admin Plugins page

## Summary
Verifies the plugin can be activated at the network level, making it available site-wide without individual per-site activation. Once network-activated, the plugin appears as active on all sub-sites and cannot be deactivated by individual site administrators.

## Prerequisites
- [ ] WordPress Multisite is configured and running
- [ ] Disable Comments plugin is installed but NOT yet network-activated
- [ ] Logged in as Super Administrator (Network Admin)
- [ ] At least two sub-sites exist in the network
- [ ] A sub-site administrator account is available for verification in step 7

## Test Data

| Field | Value |
|-------|-------|
| Network Admin Settings URL | `/wp-admin/network/admin.php?page=disable_comments_settings` |
| Network Admin Plugins URL | `/wp-admin/network/plugins.php` |
| Plugin Slug | `disable-comments` |
| Sub-site 1 Admin URL | `http://site1.example.com/wp-admin/` |
| Sub-site 2 Admin URL | `http://site2.example.com/wp-admin/` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Log in as Super Administrator via `/wp-admin/` | Successfully logged in; Dashboard visible |
| 2 | Navigate to Network Admin via My Sites > Network Admin > Dashboard | Network Admin dashboard loads at `/wp-admin/network/` |
| 3 | Click "Plugins" in the network admin sidebar | Network Plugins page loads at `/wp-admin/network/plugins.php` |
| 4 | Find "Disable Comments" in the plugin list (`tr[data-slug="disable-comments"]`) | Plugin entry is visible in the list with name, description, and action links |
| 5 | Click the "Network Activate" link under the plugin name | Page reloads; a success notice appears confirming network activation |
| 6 | Verify the plugin row no longer shows "Network Activate" — instead it shows "Network Deactivate" | Plugin status is now "Network Active"; the "Network Deactivate" link is present |
| 7 | Log in as a sub-site administrator and navigate to that sub-site's Plugins page (e.g. `http://site1.example.com/wp-admin/plugins.php`) | "Disable Comments" shows as "Network Active" with no "Deactivate" link available to the site admin |

## Expected Results
- Plugin shows as network-activated across all sub-sites.
- The "Network Active" indicator appears in the plugin row on the network plugins page.
- Sub-site admins can view the plugin in their Plugins list but cannot deactivate it.
- No error notices or warnings are displayed during or after activation.

## Negative / Edge Cases
- If already network-activated, this test should verify the current activated state rather than activating again — look for the "Network Deactivate" link presence.
- Sub-site admins must not be able to deactivate a network-activated plugin; the deactivation link must be absent from their Plugins page.
- If the plugin files are missing or corrupted, the network activate link should either be absent or show an activation error.

## Playwright Notes
**Page URL:** `/wp-admin/network/plugins.php`

**Key Selectors:**
- `tr[data-slug="disable-comments"]` — plugin row in the network plugins table
- `tr[data-slug="disable-comments"] .network-activate a` — Network Activate link (pre-activation)
- `tr[data-slug="disable-comments"] .network-deactivate a` — Network Deactivate link (post-activation)
- `.notice-success` — success message after activation

**Implementation hints:**
- `await page.click('tr[data-slug="disable-comments"] .network-activate a')` to trigger network activation
- After activation, assert: `await expect(page.locator('tr[data-slug="disable-comments"] .network-deactivate')).toBeVisible()`
- For step 7, use `browser.newContext()` with sub-site admin credentials to open an isolated session
- On the sub-site plugins page, assert: `await expect(page.locator('tr[data-slug="disable-comments"] .deactivate')).not.toBeAttached()`
- Note: multisite tests often need multiple browser contexts for different user roles/sites

## Related
- **WordPress Functions:** `is_plugin_active_for_network()`, `activate_plugin()`, `get_site_option()`
- **AJAX Action:** N/A
- **Plugin Option Key:** N/A (network activation is a WordPress core concept stored in `active_sitewide_plugins` site option)
