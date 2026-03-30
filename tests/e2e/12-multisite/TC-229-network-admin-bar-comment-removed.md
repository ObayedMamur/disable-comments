---
id: TC-229
title: "Comments link is removed from network admin bar when globally disabled"
feature: multisite
priority: medium
tags: [admin-bar, multisite, network-admin, ui]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-229 — Comments link is removed from network admin bar when globally disabled

## Summary
The WordPress network admin bar shows a "Comments" icon linking to the comments screen. When Remove Everywhere is enabled at network level, this link must be removed from both the network admin bar and sub-site admin bars. The plugin hooks into `admin_bar_menu` at priority 500 to remove the network-level comment link.

## Prerequisites
- [ ] WordPress Multisite is configured and running
- [ ] Disable Comments plugin is network-activated
- [ ] Logged in as Super Administrator (Network Admin)
- [ ] "Remove Everywhere" is enabled in network admin settings

## Test Data

| Field | Value |
|-------|-------|
| Network Admin Settings URL | `/wp-admin/network/admin.php?page=disable_comments_settings` |
| Network Admin Dashboard URL | `/wp-admin/network/admin.php` |
| Admin bar Comments element ID | `#wp-admin-bar-comments` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to network admin settings and ensure "Remove Everywhere" is enabled and saved | Settings saved confirmation shown |
| 2 | Navigate to the Network Admin dashboard: `/wp-admin/network/admin.php` | Network admin dashboard loads |
| 3 | Look at the admin bar at the top of the page — scan for a "Comments" icon or link | Comments icon/link should be absent |
| 4 | Inspect the DOM: verify `#wp-admin-bar-comments` element does not exist | Element is not present in the DOM |
| 5 | Navigate to a sub-site admin dashboard (e.g. `http://site1.example.com/wp-admin/`) | Sub-site admin dashboard loads |
| 6 | Look at the admin bar on the sub-site admin — verify Comments icon is also absent | `#wp-admin-bar-comments` absent on sub-site admin bar too |
| 7 | Navigate to a sub-site frontend page as a logged-in super admin | Frontend loads with admin bar visible |
| 8 | Verify Comments link is absent from the frontend admin bar | Admin bar shows no Comments link |
| 9 | Disable "Remove Everywhere" in settings, save | Settings saved |
| 10 | Reload the network admin dashboard — verify Comments link reappears | `#wp-admin-bar-comments` is now present in admin bar |

## Expected Results
- `#wp-admin-bar-comments` is absent from the network admin bar when Remove Everywhere is active
- The same absence applies to sub-site admin bars and frontend admin bars
- When Remove Everywhere is turned off, the Comments link reappears

## Negative / Edge Cases
- The admin bar Comments link removal must not cause any JavaScript errors
- Other admin bar items must remain unaffected (only Comments is removed)

## Playwright Notes
**Page URL:** `/wp-admin/network/admin.php`

**Key Selectors:**
- `#wp-admin-bar-comments` — the Comments admin bar item (should be absent)
- `#wpadminbar` — the admin bar container (should still exist)

**Implementation hints:**
- `await expect(page.locator('#wp-admin-bar-comments')).not.toBeAttached()`
- Verify the admin bar itself is still present: `await expect(page.locator('#wpadminbar')).toBeVisible()`
- Test on multiple URLs (network admin, sub-site admin, frontend) with the same assertion

## Related
- **WordPress Action:** `admin_bar_menu` (priority 500)
- **WordPress Function:** `$wp_admin_bar->remove_node('comments')`
- **Plugin Method:** `filter_admin_bar()`
