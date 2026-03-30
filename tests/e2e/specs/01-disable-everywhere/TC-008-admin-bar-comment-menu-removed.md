---
id: TC-008
title: "Admin bar 'Comments' shortcut is removed when globally disabled"
feature: disable-everywhere
priority: medium
tags: [disable-everywhere, admin-bar, comments-menu, admin-ui]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-008 — Admin Bar "Comments" Shortcut Is Removed When Globally Disabled

## Summary
Verifies that the WordPress admin bar (top toolbar) does not show the "Comments" icon and menu when Remove Everywhere is active. The plugin removes `wp_admin_bar_comments_menu` from the `admin_bar_menu` hook. This applies to both the frontend (when logged in as admin) and the WP admin area.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Remove Everywhere" mode is currently ACTIVE
- [ ] The WordPress admin bar is visible (not hidden for the administrator account — check Users → Your Profile → "Show Toolbar when viewing site")

## Test Data

| Field | Value |
|-------|-------|
| Admin bar comment menu ID | `#wp-admin-bar-comments` |
| Test URL (frontend) | `/` (homepage, while logged in) |
| Test URL (admin) | `/wp-admin/` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Confirm Remove Everywhere is active: navigate to `/wp-admin/admin.php?page=disable_comments_settings` | "Remove Everywhere" radio is selected |
| 2 | Navigate to the frontend homepage `/` (while still logged in as Administrator) | Homepage loads; the WordPress admin bar is visible at the top of the page |
| 3 | Look at the admin bar for a speech-bubble / comment icon (the "Comments" item) | The Comments icon and its associated menu item are NOT visible in the admin bar |
| 4 | Open browser DevTools and inspect the admin bar DOM | The element `#wp-admin-bar-comments` does NOT exist in the DOM |
| 5 | Navigate to the WP admin dashboard (`/wp-admin/`) | Admin dashboard loads with the admin bar visible at the top |
| 6 | Look at the admin bar at the top of the admin area for the Comments item | The Comments icon/menu is NOT visible in the admin bar within the admin area |
| 7 | Open browser DevTools and inspect the admin bar DOM within the admin area | The element `#wp-admin-bar-comments` does NOT exist in the DOM |
| 8 | (Baseline) Temporarily disable Remove Everywhere (switch to "Disable by Post Type", no types selected, save) | Settings saved |
| 9 | Navigate to the frontend homepage `/` while logged in | Homepage loads with admin bar |
| 10 | Look at the admin bar | The "Comments" icon/item IS now visible in the admin bar |
| 11 | Confirm the element exists in the DOM: use DevTools to inspect for `#wp-admin-bar-comments` | The element `#wp-admin-bar-comments` IS present in the DOM |
| 12 | Re-enable Remove Everywhere and save; revisit the frontend | Comments item is absent from admin bar again |

## Expected Results
- The `#wp-admin-bar-comments` element is absent from the DOM on both the frontend and admin areas when Remove Everywhere is active
- The Comments bubble icon is not visible in the admin toolbar
- After disabling Remove Everywhere, the Comments item reappears in the admin bar
- No JavaScript errors related to the admin bar appear in the console

## Negative / Edge Cases
- The item must be absent from the DOM, not merely hidden with `display:none` — use DevTools DOM inspector to confirm, not just visual inspection
- If the admin bar is hidden for the current admin user (profile setting), this test cannot be performed visually — ensure "Show Toolbar when viewing site" is enabled in the user profile
- Non-administrator users (editors, authors) may have different admin bar states; this test is scoped to the Administrator role only

## Playwright Notes
**Page URL:** `/` (frontend, logged in) and `/wp-admin/`

**Key Selectors:**
- `#wp-admin-bar-comments` — Comments admin bar node (must NOT be attached)
- `#wpadminbar` — the admin bar container
- `#wp-admin-bar-comments a` — the clickable link inside the comments bar node
- `#wp-admin-bar-comments .ab-label` — the label/count inside the comments node

**Implementation hints:**
- `await expect(page.locator('#wp-admin-bar-comments')).not.toBeAttached()` — confirms absent from DOM
- Check on both frontend and admin:
  ```js
  for (const url of ['/', '/wp-admin/']) {
    await page.goto(url);
    await expect(page.locator('#wp-admin-bar-comments')).not.toBeAttached();
  }
  ```
- For the baseline check: `await expect(page.locator('#wp-admin-bar-comments')).toBeAttached()`
- Admin bar only renders for logged-in users; ensure your test session is authenticated before navigating
- Use `page.waitForSelector('#wpadminbar')` to confirm the admin bar itself is loaded before asserting the comments item

## Related
- **WordPress Filters:** `admin_bar_menu` — plugin removes `wp_admin_bar_comments_menu` callback
- **WordPress Actions:** `template_redirect` (priority 10) → `filter_admin_bar()`, `admin_init` → `filter_admin_bar()`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
