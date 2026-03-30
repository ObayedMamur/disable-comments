---
id: TC-223
title: "Sub-site admins cannot change settings when sitewide control is enabled"
feature: multisite
priority: high
tags: [sitewide-settings, permissions, sub-site-admin, network-enforcement, multisite]
type: negative
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-223 — Sub-site admins cannot change settings when sitewide control is enabled

## Summary
When `sitewide_settings = true`, sub-site admins must not be able to override the network-wide plugin settings. The settings page on their site should either show a read-only state, display a message that settings are managed at the network level, or be entirely inaccessible. This is a negative test to confirm the enforcement boundary.

## Prerequisites
- [ ] WordPress Multisite is configured and running
- [ ] Disable Comments plugin is network-activated
- [ ] Logged in as Super Administrator (Network Admin)
- [ ] At least two sub-sites exist in the network
- [ ] `sitewide_settings = true` is set at network level with "Remove Everywhere" active
- [ ] A sub-site administrator account exists (role: administrator on sub-site 1 only, NOT a super admin)

## Test Data

| Field | Value |
|-------|-------|
| Network Admin Settings URL | `/wp-admin/network/admin.php?page=disable_comments_settings` |
| Sub-site 1 Admin URL | `http://site1.example.com/wp-admin/` |
| Sub-site 2 Admin URL | `http://site2.example.com/wp-admin/` |
| Sub-site 1 Settings URL | `http://site1.example.com/wp-admin/admin.php?page=disable_comments_settings` |
| Sub-site Admin Username | `site1admin` |
| Sub-site Admin Password | `[test password]` |
| Sub-site 1 Test Post URL | `http://site1.example.com/sample-post/` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | As super admin, navigate to network admin settings, enable `sitewide_settings = true`, select "Remove Everywhere", and save | Settings saved; sitewide enforcement is active |
| 2 | Log out of the super admin account | Logged out successfully |
| 3 | Log in as the sub-site 1 administrator (site-level admin, not super admin) at `http://site1.example.com/wp-admin/` | Sub-site 1 admin dashboard loads |
| 4 | Navigate to Tools > Disable Comments (or directly to `http://site1.example.com/wp-admin/admin.php?page=disable_comments_settings`) | Page either loads in read-only mode OR redirects with an access notice — does NOT show editable settings |
| 5 | Observe the state of the settings page | Settings show the current network-enforced configuration ("Remove Everywhere" visible); inputs appear disabled, read-only, or a "Managed by Network Admin" notice is displayed |
| 6 | Attempt to change a setting, e.g. click or select "By Post Type" mode instead of "Remove Everywhere" | Input is either unclickable (disabled) or the change cannot be committed |
| 7 | Attempt to click Save Settings if the button is present | Button is disabled OR clicking it produces no change to the effective settings |
| 8 | Navigate to a published post on sub-site 1 frontend to verify enforcement is still active | Comment form (`#respond`) is absent — network settings still apply regardless of any attempted override |

## Expected Results
- Sub-site admin cannot change or save settings that override the network-enforced configuration.
- The settings page either shows inputs as disabled/read-only, displays a lock or "managed by network admin" notice, or is inaccessible to site admins.
- The frontend behavior on sub-site 1 still reflects the network-wide "Remove Everywhere" setting with no comment form present.

## Negative / Edge Cases
- The sub-site admin might not see the settings page at all — the plugin may redirect to the dashboard or show a capabilities error.
- If the plugin does show the page, a "Save Settings" button that is present but does nothing is acceptable — but misleading UX should be noted as a bug.
- The sub-site admin should not receive a generic WordPress error; the plugin should handle this gracefully with a user-friendly message.

## Playwright Notes
**Page URL:** `http://site1.example.com/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[type="submit"], #submit` — Save Settings button (assert disabled or absent)
- `input[type="radio"], input[type="checkbox"]` — settings inputs (assert disabled)
- `.notice, .dc-network-notice` — network admin notice/lock message
- `#respond` — comment form on frontend (must be absent after attempted override)

**Implementation hints:**
- Use `browser.newContext()` for the sub-site admin session to isolate from the super admin session
- Assert button is disabled: `await expect(page.locator('input[type="submit"]')).toBeDisabled()` OR
- Assert notice text: `await expect(page.locator('.notice')).toContainText('network')`
- For attempting to change settings: `await page.click('label[for="by-post-type"]')` then verify no change persists on reload
- Note: multisite tests often need multiple browser contexts for different user roles/sites

## Related
- **WordPress Functions:** `current_user_can('manage_network')`, `is_super_admin()`, `get_site_option()`
- **AJAX Action:** N/A
- **Plugin Option Key:** `disable_comments_options.sitewide_settings`
