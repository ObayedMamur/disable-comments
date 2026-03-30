---
id: TC-222
title: "Remove Everywhere at network level disables comments on all sub-sites"
feature: multisite
priority: high
tags: [remove-everywhere, network-wide, sitewide-settings, multisite, frontend]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-222 — Remove Everywhere at network level disables comments on all sub-sites

## Summary
When Remove Everywhere is configured at the network admin level with `sitewide_settings = true`, all sub-sites must have comments disabled on their frontends regardless of per-site settings. This test confirms that the network-wide enforcement propagates to every sub-site's frontend post pages and that the admin bar Comment link is also removed.

## Prerequisites
- [ ] WordPress Multisite is configured and running
- [ ] Disable Comments plugin is network-activated
- [ ] Logged in as Super Administrator (Network Admin)
- [ ] At least two sub-sites exist in the network
- [ ] `sitewide_settings = true` is set (or will be set in step 1)
- [ ] At least one published post with comments open exists on sub-site 1
- [ ] At least one published post with comments open exists on sub-site 2

## Test Data

| Field | Value |
|-------|-------|
| Network Admin Settings URL | `/wp-admin/network/admin.php?page=disable_comments_settings` |
| Sub-site 1 Admin URL | `http://site1.example.com/wp-admin/` |
| Sub-site 2 Admin URL | `http://site2.example.com/wp-admin/` |
| Sub-site 1 Test Post URL | `http://site1.example.com/sample-post/` |
| Sub-site 2 Test Post URL | `http://site2.example.com/sample-post/` |
| Comment Form Selector | `#respond` |
| Admin Bar Comments Selector | `#wp-admin-bar-comments` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/network/admin.php?page=disable_comments_settings`, enable the "Sitewide Settings" toggle/checkbox | Sitewide Settings option is checked/enabled |
| 2 | Select the "Remove Everywhere" option on the network admin settings page | Remove Everywhere radio/option is selected |
| 3 | Click "Save Settings" | Settings saved successfully; success notice appears |
| 4 | Navigate to the test post URL on sub-site 1 frontend (e.g. `http://site1.example.com/sample-post/`) | Post page loads successfully |
| 5 | Verify the comment form (`#respond`) is absent on sub-site 1's post page | `#respond` element is not present in the DOM |
| 6 | Navigate to the test post URL on sub-site 2 frontend (e.g. `http://site2.example.com/sample-post/`) | Post page loads successfully |
| 7 | Verify the comment form (`#respond`) is absent on sub-site 2's post page | `#respond` element is not present in the DOM |
| 8 | While logged in as super admin, navigate to sub-site 1's frontend and inspect the admin bar | The "Comments" icon (`#wp-admin-bar-comments`) is absent from the admin bar on all sub-site frontends |

## Expected Results
- All sub-sites have comments disabled on their frontend post pages.
- No sub-site has an active or visible comment form (`#respond`).
- The admin bar "Comments" link is removed from both network admin and sub-site admin views.
- The settings are enforced network-wide regardless of any per-site configuration.

## Negative / Edge Cases
- If `sitewide_settings` is `false`, sub-sites may have their own settings and this test scenario does not apply — verify the option is `true` before running.
- Sub-sites that were individually configured with comments enabled before enabling sitewide_settings should still have comments disabled once sitewide enforcement is active.
- If a sub-site has no posts or its posts have comments closed at the post level, the test should be performed on posts that had comments explicitly open.

## Playwright Notes
**Page URL:** Multiple — network admin + sub-site frontends

**Key Selectors:**
- `#respond` — comment form container (must be absent)
- `#wp-admin-bar-comments` — admin bar comments link (must be absent)
- `[name="sitewide_settings"], #sitewide-settings` — sitewide settings checkbox/toggle
- `.notice-success` — settings saved confirmation

**Implementation hints:**
- Assert comment form absent: `await expect(page.locator('#respond')).not.toBeAttached()`
- Assert admin bar link absent: `await expect(page.locator('#wp-admin-bar-comments')).not.toBeAttached()`
- Sub-site URLs may be subdomains (e.g. `site1.example.com`) or subdirectories (e.g. `example.com/site1/`) depending on multisite configuration
- Use `page.goto()` with full absolute URLs when navigating between different sub-site origins
- Note: multisite tests often need multiple browser contexts for different user roles/sites

## Related
- **WordPress Functions:** `get_site_option()`, `update_site_option()`, `comments_open()`
- **AJAX Action:** N/A
- **Plugin Option Key:** `disable_comments_options.sitewide_settings`, `disable_comments_options.remove_everywhere`
