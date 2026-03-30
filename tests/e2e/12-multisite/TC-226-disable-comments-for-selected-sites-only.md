---
id: TC-226
title: "Disable comments for specific selected sub-sites only"
feature: multisite
priority: high
tags: [site-selection, targeted-disable, partial-network, multisite, frontend]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-226 — Disable comments for specific selected sub-sites only

## Summary
Using the site selection UI, the network admin selects only sub-site 1 (not sub-site 2) and enables Remove Everywhere. This test verifies that only the selected site is affected and that unselected sites retain their comment functionality unchanged.

## Prerequisites
- [ ] WordPress Multisite is configured and running
- [ ] Disable Comments plugin is network-activated
- [ ] Logged in as Super Administrator (Network Admin)
- [ ] At least two sub-sites exist in the network
- [ ] `sitewide_settings = false` is set (per-site targeting mode enabled)
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
| Target Site ID | `2` (sub-site 1 — verify actual ID in WP network) |
| Plugin Option | `disable_comments_options.disabled_sites` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/network/admin.php?page=disable_comments_settings`, go to the "Disable Comments" tab | Settings page loads; site selector UI is visible |
| 2 | In the site selector, check/select only sub-site 1; ensure sub-site 2 is NOT selected | Sub-site 1 is marked/selected; sub-site 2 is unchecked |
| 3 | Select the "Remove Everywhere" mode | Remove Everywhere option is active |
| 4 | Click "Save Settings" | Settings saved successfully; success notice appears |
| 5 | Navigate to the test post on sub-site 1 frontend (`http://site1.example.com/sample-post/`) | Post page loads |
| 6 | Verify the comment form is absent on sub-site 1 | `#respond` is not present in the DOM — comments are disabled |
| 7 | Navigate to the test post on sub-site 2 frontend (`http://site2.example.com/sample-post/`) | Post page loads |
| 8 | Verify the comment form IS present on sub-site 2 | `#respond` is visible and functional — sub-site 2 is unaffected |
| 9 | Verify (via WP-CLI or DB inspection) that `disable_comments_options.disabled_sites` contains sub-site 1's ID but not sub-site 2's ID | Array contains `[2]` (or relevant sub-site 1 ID) and does not contain sub-site 2's ID |

## Expected Results
- Only sub-site 1 has comments disabled on its frontend; `#respond` is absent.
- Sub-site 2 retains its comment form; `#respond` is present.
- The `disabled_sites` option accurately reflects the selected site IDs.
- No other sub-sites in the network are impacted by this configuration.

## Negative / Edge Cases
- Deselecting sub-site 1 from the site selector and saving should restore the comment form on sub-site 1.
- Selecting a site ID that no longer exists (deleted site) should not cause errors — the plugin should handle stale IDs gracefully.
- If `sitewide_settings = true` is accidentally active, all sites would be affected — verify the setting is `false` before running this test.

## Playwright Notes
**Page URL:** `/wp-admin/network/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `.dc-site-list li input[type="checkbox"]` — site selection checkboxes
- `.dc-site-list li[data-site-id="2"] input` — checkbox for sub-site 1 (adjust data attribute to match actual site IDs)
- `#respond` — comment form on frontend
- `.notice-success` — save confirmation

**Implementation hints:**
- Select sub-site 1 checkbox: `await page.check('.dc-site-list li[data-site-id="2"] input[type="checkbox"]')`
- Uncheck sub-site 2: `await page.uncheck('.dc-site-list li[data-site-id="3"] input[type="checkbox"]')` (adjust IDs)
- The site selector may use a multi-select `<select>` element — use `page.selectOption()` in that case
- Assert comment form present: `await expect(page.locator('#respond')).toBeVisible()`
- Assert comment form absent: `await expect(page.locator('#respond')).not.toBeAttached()`
- Note: multisite tests often need multiple browser contexts for different user roles/sites

## Related
- **WordPress Functions:** `get_site_option()`, `update_site_option()`, `get_sites()`
- **AJAX Action:** `get_sub_sites`
- **Plugin Option Key:** `disable_comments_options.disabled_sites` (array of site IDs)
