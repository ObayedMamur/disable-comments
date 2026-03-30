---
id: TC-002
title: "Disable 'Remove Everywhere' mode restores comments"
feature: disable-everywhere
priority: high
tags: [disable-everywhere, settings, frontend, restore]
type: functional
automation_status: automated
automation_file: "[TC-002-disable-remove-everywhere.spec.ts](TC-002-disable-remove-everywhere.spec.ts)"
created: 2026-03-30
updated: 2026-03-30
---

# TC-002 — Disable "Remove Everywhere" Mode Restores Comments

## Summary
Verifies that switching from "Remove Everywhere" back to "Disable by Post Type" mode (with no post types selected) correctly re-enables comment functionality on the frontend. This confirms the plugin's disable action is reversible and does not permanently alter WordPress comment state.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] Plugin is in a clean/default state (Remove Everywhere is NOT currently active)
- [ ] Test creates its own Post with comments open via WP-CLI
- [ ] "Remove Everywhere" is enabled mid-test via UI before being reversed

## Test Data

| Field | Value |
|-------|-------|
| Settings URL | `/wp-admin/admin.php?page=disable_comments_settings` |
| Sample Post URL | `/?p=1` or `/hello-world/` |
| Starting state | `disable_comments_options.remove_everywhere = true` |
| Target state | `disable_comments_options.remove_everywhere = false` (or By Post Type with no types checked) |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | (Setup) Create a test Post with `comment_status=open` via WP-CLI | Post exists and its URL is known |
| 2 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads; "Remove Everywhere" radio is NOT selected (clean state confirmed) |
| 3 | Navigate to the test Post frontend URL | Post page loads; `#respond`, `h3#reply-title`, and `#comment` are visible (comment form present) |
| 4 | Return to settings; click "Remove Everywhere" and click "Save Changes" | SweetAlert success popup appears; setting saved |
| 5 | Navigate to the test Post frontend URL | `#respond` is completely absent from the DOM (Remove Everywhere now active — prerequisite state confirmed) |
| 6 | Return to settings; confirm "Remove Everywhere" radio is selected | Radio is checked |
| 7 | Click the "Disable by Post Type" radio button | Radio selection switches to "Disable by Post Type"; post-type checkboxes become visible |
| 8 | Ensure no post type checkboxes are checked (deselect all if any are pre-checked) | All post type checkboxes are unchecked |
| 9 | Click the "Save Changes" button | SweetAlert success popup appears; setting saved |
| 10 | Reload the settings page | "Disable by Post Type" radio is still selected; no post type checkboxes are checked |
| 11 | Navigate to the test Post frontend URL | `#respond` IS present; "Leave a Reply" heading, `#comment`, and `#submit` are visible |
| 12 | Inspect the comment form's `action` attribute | Form action contains `wp-comments-post.php` |
| 13 | (Verify) Run `wp option get disable_comments_options --format=json` | `remove_everywhere` is `false` or falsy in the stored options |

## Expected Results
- After switching to "Disable by Post Type" with no types checked and saving, the frontend Post shows the full comment form
- The `#respond` section is present and visible in the DOM
- The "Leave a Reply" heading is rendered on the post page
- The comment textarea (`#comment`) and submit button (`#submit`) are present and interactable
- The settings page reflects the new state ("Disable by Post Type" selected) after reload
- The `disable_comments_options` stored value no longer has `remove_everywhere = true`

## Negative / Edge Cases
- The comment form must NOT still be absent after switching modes (would indicate plugin failed to release the filter)
- The settings must NOT silently revert back to "Remove Everywhere" on reload
- If the post itself has comments disabled at the individual post level (WordPress native setting), the comment form will remain absent — this is expected WordPress behavior, not a plugin bug. Ensure the test post has comments open at the post level.

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `input[name="disable_comments_options[remove_everywhere]"][value="1"]` — Remove Everywhere radio
- `input[name="disable_comments_options[remove_everywhere]"][value="2"]` — Disable by Post Type radio
- `input[type="checkbox"][name*="disable_comments_options[post_types]"]` — post type checkboxes
- `#respond` — comment form wrapper (must be PRESENT after restoring)
- `#comment` — comment textarea
- `#submit` — comment submit button
- `h3#reply-title` — "Leave a Reply" heading

**Implementation hints:**
- Assert initial state: `await expect(page.locator('input[value="1"]')).toBeChecked()` before switching
- After switching: `await page.locator('input[value="2"]').click()`
- Uncheck all post type boxes: `await page.locator('input[type="checkbox"][name*="post_types"]').uncheck()` (use `{ force: true }` if needed for each)
- After save and frontend reload: `await expect(page.locator('#respond')).toBeVisible()`
- Use `page.evaluate()` to confirm the element is truly in the DOM: `document.getElementById('respond') !== null`

## Related
- **WordPress Filters:** `comments_open`, `pings_open`, `get_comments_number`, `comments_array`
- **WordPress Actions:** `wp_loaded` → `init_wploaded_filters()`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
