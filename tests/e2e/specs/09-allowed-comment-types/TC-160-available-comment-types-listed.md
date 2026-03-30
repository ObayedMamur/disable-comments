---
id: TC-160
title: "Available comment types from database are listed in the settings UI"
feature: allowed-comment-types
priority: medium
tags: [settings-ui, comment-types, allowlist, database]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-160 — Available comment types from database are listed in the settings UI

## Summary
The settings Disable tab must display a list of comment types currently stored in the WordPress database, queried via `SELECT DISTINCT comment_type FROM wp_comments`. This lets admins see what types are in use and choose which to allow/protect.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] At least two different comment types exist in the database (e.g. 'comment' and 'pingback')

## Test Data

| Field | Value |
|-------|-------|
| Comment type 1 | `comment` (regular comment) |
| Comment type 2 | `pingback` |
| Comment type 3 (optional) | `trackback` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Ensure at least 2 different comment types exist in the database (create a pingback or check WP Admin > Comments for type variety) | At least 'comment' and 'pingback' types are present in the DB |
| 2 | Navigate to plugin Settings > Disable Comments tab | Disable tab is displayed |
| 3 | Scroll to the "Allowed Comment Types" or comment type allowlist section | Allowlist section is visible on the page |
| 4 | Verify the section displays a list of checkboxes, one per comment type found in the DB | Multiple checkboxes are rendered, each corresponding to a comment type |
| 5 | Verify 'comment' type is listed (if regular comments exist) | A checkbox labeled 'comment' is present and visible |
| 6 | Verify 'pingback' type is listed (if pingbacks exist) | A checkbox labeled 'pingback' is present and visible |
| 7 | Verify all listed types match types actually present in `wp_comments` table | No extra or missing types appear in the UI compared to database contents |

## Expected Results
- Comment types section is visible on the Disable tab
- Each distinct comment_type from the database appears as a labeled checkbox
- Types from the `disable_comments_known_comment_types` filter also appear (if any plugins register them)
- The list updates dynamically when new comment types appear in the DB

## Negative / Edge Cases
- If only 'comment' type exists, only 'comment' should be listed
- An empty database (no comments) should show an empty list or a "no types found" message

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `.dc-comment-types-list` or `[name*="allowed_comment_types"]` — allowlist checkboxes
- `input[type="checkbox"][value="comment"]` — 'comment' type checkbox
- `input[type="checkbox"][value="pingback"]` — 'pingback' type checkbox

**Implementation hints:**
- `await expect(page.locator('input[value="comment"]')).toBeVisible()`
- Count the checkboxes: `await expect(page.locator('[name*="allowed_comment_types"]')).toHaveCount(greaterThan(1))`

## Related
- **Plugin Method:** `get_all_comment_types()`, `get_available_comment_type_options()`
- **WordPress Filter:** `disable_comments_known_comment_types`
- **Database:** `SELECT DISTINCT comment_type FROM wp_comments`
