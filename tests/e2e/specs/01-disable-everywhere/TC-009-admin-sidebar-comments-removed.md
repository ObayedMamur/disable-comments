---
id: TC-009
title: "Admin sidebar 'Comments' menu item is removed when globally disabled"
feature: disable-everywhere
priority: medium
tags: [disable-everywhere, admin-menu, sidebar, admin-ui]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-009 — Admin Sidebar "Comments" Menu Item Is Removed When Globally Disabled

## Summary
Verifies that the "Comments" entry is removed from the WordPress admin left sidebar navigation when Remove Everywhere is active. The plugin removes it via the `admin_menu` filter at priority 9999 (`filter_admin_menu()` method). This prevents admins from navigating to the comments management screen.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Remove Everywhere" mode is currently ACTIVE

## Test Data

| Field | Value |
|-------|-------|
| Admin URL | `/wp-admin/` |
| Comments menu selector | `#menu-comments` |
| Comments menu link | `/wp-admin/edit-comments.php` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Confirm Remove Everywhere is active: navigate to `/wp-admin/admin.php?page=disable_comments_settings` | "Remove Everywhere" radio is selected |
| 2 | Navigate to the WordPress admin dashboard `/wp-admin/` | Admin dashboard loads with the left sidebar navigation visible |
| 3 | Scan the left sidebar navigation for a "Comments" menu item (typically displayed with a speech bubble icon between "Appearance" and "Plugins" area, or between "Posts" and "Pages") | The "Comments" menu item is NOT visible in the sidebar |
| 4 | Open browser DevTools and inspect the sidebar DOM for `#menu-comments` | The `#menu-comments` list item does NOT exist in the DOM |
| 5 | Search the DOM for any anchor tag with `href` containing `edit-comments.php` | No anchor element linking to `edit-comments.php` exists in the admin sidebar DOM |
| 6 | Attempt to navigate directly to `/wp-admin/edit-comments.php` | The page either redirects to the dashboard, shows a permissions error, or an appropriate blocked state (direct URL access behavior may vary) |
| 7 | (Baseline) Temporarily disable Remove Everywhere: switch to "Disable by Post Type", no types selected, save | Settings saved; navigated back to dashboard |
| 8 | Navigate to the WordPress admin dashboard `/wp-admin/` | Admin dashboard loads |
| 9 | Scan the left sidebar navigation for the "Comments" menu item | The "Comments" menu item IS present in the sidebar |
| 10 | Open browser DevTools and inspect for `#menu-comments` | The `#menu-comments` element IS present in the DOM |
| 11 | Re-enable Remove Everywhere and save; navigate to `/wp-admin/` | The "Comments" menu item is absent from the sidebar again |

## Expected Results
- The `#menu-comments` element is absent from the admin sidebar DOM when Remove Everywhere is active
- No link to `edit-comments.php` appears in the left navigation sidebar
- After disabling Remove Everywhere, the "Comments" item reappears in the sidebar
- The rest of the admin sidebar navigation items (Posts, Pages, Media, etc.) are not affected

## Negative / Edge Cases
- The menu item must be absent from the DOM, not just visually hidden — use browser DevTools to confirm
- Direct navigation to `/wp-admin/edit-comments.php` while the menu is removed: the plugin may or may not block direct access (the test in step 6 documents behavior, but direct URL blocking is not the primary assertion of this test)
- Other roles (Editor, Author) may still see or not see Comments based on their capabilities; this test is scoped to Administrator

## Playwright Notes
**Page URL:** `/wp-admin/`

**Key Selectors:**
- `#menu-comments` — the Comments menu item `<li>` in the admin sidebar (must NOT be attached)
- `#adminmenu` — the admin sidebar `<ul>` container
- `#adminmenu a[href*="edit-comments.php"]` — any sidebar link to comments (must NOT be attached)
- `#menu-comments .wp-menu-name` — the text label inside the menu item

**Implementation hints:**
- `await expect(page.locator('#menu-comments')).not.toBeAttached()` — confirms element absent from DOM
- Check for any link: `await expect(page.locator('#adminmenu a[href*="edit-comments.php"]')).not.toBeAttached()`
- After re-enabling: `await expect(page.locator('#menu-comments')).toBeAttached()`
- Wait for the admin menu to load: `await page.waitForSelector('#adminmenu')` before asserting
- If testing direct URL access behavior: `const response = await page.goto('/wp-admin/edit-comments.php'); expect(response.url()).not.toContain('edit-comments.php')` (if it redirects)

## Related
- **WordPress Filters:** `admin_menu` (priority 9999) → `filter_admin_menu()` removes comments menu page
- **WordPress Actions:** `admin_init` → `filter_admin_bar()`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
