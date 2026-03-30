---
id: TC-185
title: "Switching between Disable and Delete tabs shows correct content"
feature: settings-ui
priority: medium
tags: [settings-ui, tabs, navigation, ui]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-185 — Switching Between Disable and Delete Tabs Shows Correct Content

## Summary
Verifies that the two-tab interface works correctly — switching between "Disable Comments" and "Delete Comments" tabs shows the appropriate content for each and hides the other, without a page reload.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Plugin settings page is accessible at `/wp-admin/admin.php?page=disable_comments_settings`

## Test Data

| Field | Value |
|-------|-------|
| Default active tab | Disable Comments |
| Tab 1 label | Disable Comments |
| Tab 2 label | Delete Comments |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to the settings page — confirm "Disable Comments" tab is active by default | "Disable Comments" tab is highlighted as active; its content is visible |
| 2 | Verify disable tab content is visible (mode selector, post type checkboxes) | Disable Comments content panel is rendered and visible |
| 3 | Verify delete tab content is NOT visible | Delete Comments content panel is hidden or not rendered |
| 4 | Click "Delete Comments" tab | UI switches to the delete tab without a page reload |
| 5 | Verify delete tab content becomes visible | Delete Comments panel with its options is now visible |
| 6 | Verify disable tab content is now hidden | Disable Comments panel is no longer visible |
| 7 | Click "Disable Comments" tab again | UI switches back to the disable tab |
| 8 | Verify disable content is visible again, delete content hidden | Disable Comments panel visible; Delete panel hidden |
| 9 | Repeat switching 2–3 times to confirm no state corruption | Each switch shows the correct panel with no UI glitches or errors |

## Expected Results
- Tab switching is instantaneous (no page reload)
- Active tab is visually highlighted
- Correct content panel is shown for each tab
- Multiple tab switches do not cause errors or UI glitches

## Negative / Edge Cases
- Any unsaved changes in the Disable tab must not be lost when switching to Delete tab and back
- Tab switching must not trigger any AJAX or form submissions

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `.dc-tab-disable, [data-tab="disable"]` — Disable tab trigger
- `.dc-tab-delete, [data-tab="delete"]` — Delete tab trigger

**Implementation hints:**
- `await expect(page.locator('.disable-content')).toBeVisible()`
- `await page.click('[data-tab="delete"]')`
- `await expect(page.locator('.delete-content')).toBeVisible()`
- `await expect(page.locator('.disable-content')).toBeHidden()`

## Related
- **View File:** `views/settings.php`
- **JavaScript:** `assets/js/disable-comments-settings-scripts.js`
