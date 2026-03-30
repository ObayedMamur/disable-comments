---
id: TC-122
title: "Avatar setting works independently of comment disable setting"
feature: avatar
priority: medium
tags: [avatar, independence, show_avatars, settings]
type: edge-case
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-122 — Avatar setting works independently of comment disable setting

## Summary
Avatars can be disabled while comments are still enabled, and vice versa. These are two completely independent settings. This test verifies that neither setting affects the other.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] A post exists with at least 2 approved comments that have Gravatar-associated email addresses (or any avatar)
- [ ] WordPress Discussion Settings has "Show Avatars" enabled initially

## Test Data

| Field | Value |
|-------|-------|
| Post URL with comments | `/sample-post/` (or any post with approved comments) |
| Test comment email | one with a Gravatar (e.g. a WordPress.com account) |
| Comment disable state | varies per step |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Ensure both settings are default: comments enabled, avatars enabled | Plugin settings reflect: no post types disabled, avatars enabled |
| 2 | Visit a post with approved comments and verify: comment form visible AND comment avatars rendered (img.avatar elements present) | Comment form is visible; `img.avatar` elements are present in comment list |
| 3 | Navigate to plugin Settings > Disable tab | Disable tab is displayed with all settings visible |
| 4 | Disable Avatars (check "Disable Avatars"), but leave comment disabling OFF (no post types selected, not Remove Everywhere) | "Disable Avatars" checkbox is checked; no comment-disable options are selected |
| 5 | Save settings | Settings saved successfully; success notification shown |
| 6 | Navigate back to the post with comments | Post page loads normally |
| 7 | Verify: comment form is still visible (comments still enabled) | `#respond` container is present and visible |
| 8 | Verify: avatar images are NOT rendered (img.avatar absent from comment list) | No `img.avatar` elements appear in the comment list |
| 9 | Go back to settings: Re-enable Avatars AND enable Remove Everywhere | "Disable Avatars" unchecked; "Remove Everywhere" checked |
| 10 | Save settings | Settings saved successfully |
| 11 | Visit the post again | Post page loads normally |
| 12 | Verify: comment form is NOT visible (globally disabled) | `#respond` container is absent or hidden |
| 13 | Verify: avatars may still render in other contexts (e.g. author bio, sidebar) — avatar setting is re-enabled | Avatar images appear outside the comment list context |

## Expected Results
- Avatar disable works without affecting comment functionality
- Comment disable works without affecting avatar display
- The settings page shows both checkboxes independently
- WordPress `show_avatars` option reflects the avatar toggle state
- `disable_comments_options.remove_everywhere` reflects the comment toggle state

## Negative / Edge Cases
- Enabling avatars while comments are globally disabled should not cause errors
- Disabling avatars while comments are enabled should not show any comment-related notices

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `#disable_comments_options_disable_avatar` or `[name*="avatar"]` — avatar toggle checkbox
- `.avatar, img.avatar` — avatar images in comment list
- `#respond` — comment form container

**Implementation hints:**
- Use two separate `page.goto()` calls to the same post URL after each settings change
- `await expect(page.locator('img.avatar')).toHaveCount(0)` for no avatars
- `await expect(page.locator('#respond')).not.toBeAttached()` for no comment form

## Related
- **WordPress Option:** `show_avatars`
- **WordPress Function:** `get_avatar()`
- **Plugin Option Key:** `disable_comments_options.remove_everywhere`
