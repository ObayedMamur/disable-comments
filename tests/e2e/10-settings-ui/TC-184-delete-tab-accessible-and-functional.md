---
id: TC-184
title: "Delete Comments tab is accessible and shows delete UI"
feature: settings-ui
priority: high
tags: [settings-ui, tabs, delete-tab, navigation]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-184 — Delete Comments Tab Is Accessible and Shows Delete UI

## Summary
Verifies the "Delete Comments" tab exists on the settings page, can be activated by clicking, and reveals the delete comments configuration UI including mode selection and Delete button.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Plugin settings page is accessible at `/wp-admin/admin.php?page=disable_comments_settings`

## Test Data

| Field | Value |
|-------|-------|
| Settings page URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Expected tab 1 | Disable Comments |
| Expected tab 2 | Delete Comments |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads without errors |
| 2 | Verify the page loads with two visible tabs: "Disable Comments" and "Delete Comments" | Both tabs are rendered and visible on the page |
| 3 | Click the "Delete Comments" tab | Tab click is registered and UI responds |
| 4 | Verify the tab becomes visually active (active state styling applied) | "Delete Comments" tab has active/selected styling; "Disable Comments" tab does not |
| 5 | Verify the "Disable Comments" tab content is hidden | Disable tab content panel is no longer visible |
| 6 | Verify delete mode selection options are visible (Delete Everywhere, By Post Type, etc.) | Delete mode radio buttons or selectors are rendered and visible |
| 7 | Verify the "Delete Comments" submit button is present and visible | A submit/delete button exists within the delete tab content area |
| 8 | Verify no PHP errors or JavaScript console errors appear | Browser console is free of errors; no PHP warnings in the page markup |

## Expected Results
- Both tabs are visible on the settings page
- Clicking "Delete Comments" tab switches the visible content
- Delete mode options are rendered correctly
- Delete submit button is present

## Negative / Edge Cases
- Refreshing the page after clicking Delete tab may return to default tab (Disable tab) — this is acceptable
- The Delete tab must NOT auto-submit when clicked

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `[data-tab="delete"], .dc-tab-delete, a[href*="delete"]` — Delete tab trigger
- `.dc-delete-settings, #dc-delete-tab` — Delete tab content container
- `button[name*="delete"], #dc-delete-btn` — Delete submit button

**Implementation hints:**
- `await page.click('[data-tab="delete"]')` or similar tab trigger
- `await expect(page.locator('.delete-tab-content')).toBeVisible()`
- Check console for errors: `page.on('console', msg => { if (msg.type() === 'error') ... })`

## Related
- **Plugin Method:** `settings_page()`
- **View File:** `views/settings.php`, `views/partials/_delete.php`
- **AJAX Action:** `wp_ajax_disable_comments_delete_comments`
