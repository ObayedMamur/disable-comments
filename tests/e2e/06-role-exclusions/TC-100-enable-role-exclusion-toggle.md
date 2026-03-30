---
id: TC-100
title: "Enable role exclusion toggle and save"
feature: role-exclusions
priority: high
tags: [role-exclusions, settings, toggle, ui]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-100 — Enable Role Exclusion Toggle and Save

## Summary
Verifies the "Enable exclude by role" toggle can be checked and saved, and that role checkboxes appear in the UI once the toggle is enabled. Confirms the setting persists after a page reload.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Remove Everywhere" (or any disable mode) is already active so exclusion is meaningful

## Test Data

| Field | Value |
|-------|-------|
| Settings page | `/wp-admin/admin.php?page=disable_comments_settings` |
| Expected roles visible | Administrator, Editor, Author, Contributor, Subscriber |
| Option key | `disable_comments_options.enable_exclude_by_role` |
| Sample role to select | `editor` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads without errors |
| 2 | Locate the "Enable exclude by role" checkbox/toggle | Checkbox is present and currently unchecked (default state) |
| 3 | Check the "Enable exclude by role" checkbox | Checkbox becomes checked; a list of role checkboxes appears (Administrator, Editor, Author, Contributor, Subscriber) |
| 4 | Verify all five role checkboxes are displayed and unchecked by default | Five role labels are visible; none are pre-selected |
| 5 | Check the "Editor" role checkbox | Editor checkbox becomes checked |
| 6 | Click the "Save Changes" button | Page reloads or shows a success notice (e.g. "Settings saved") |
| 7 | Reload the settings page (`F5` / `page.reload()`) | Page reloads without errors |
| 8 | Verify "Enable exclude by role" is still checked | Toggle remains in the enabled state |
| 9 | Verify "Editor" role checkbox is still checked | Editor role persists as selected after save and reload |

## Expected Results
- The "Enable exclude by role" toggle is visible on the settings page.
- Enabling the toggle reveals the list of WordPress role checkboxes.
- Saving with a role selected persists both `enable_exclude_by_role = true` and `exclude_by_role = ['editor']` in the database option.
- A page reload confirms the settings were saved correctly.

## Negative / Edge Cases
- If the toggle is enabled but no roles are selected, saving should still store an empty array without error.
- Disabling the toggle after roles were selected should hide the role list and not apply exclusions even if the array is stored.

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `#enable_exclude_by_role` — the "Enable exclude by role" checkbox
- `[name="disable_comments_options[exclude_by_role][]"]` — individual role checkboxes
- `[value="editor"]` — Editor role checkbox (within the role list)
- `[value="administrator"]` — Administrator role checkbox
- `input[type="submit"]` or `#submit` — Save Changes button
- `.notice-success, #setting-error-settings_updated` — success notice after save

**Implementation hints:**
- Use `page.waitForSelector` on the role list container after checking the toggle, as it may be shown/hidden via JS.
- After saving, call `page.reload()` and re-query the checkbox states to confirm persistence.
- Use `expect(locator).toBeChecked()` for each checkbox assertion.
- If the role list is conditionally rendered server-side (not JS toggle), it will appear only after a save with the toggle checked.

## Related
- **WordPress Filters:** `comment_status`, `comments_open`
- **Plugin Option Key:** `disable_comments_options.enable_exclude_by_role`, `disable_comments_options.exclude_by_role`
- **Related TC:** TC-101, TC-102, TC-103, TC-104, TC-105
