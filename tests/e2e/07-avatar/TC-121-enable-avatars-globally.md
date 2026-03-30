---
id: TC-121
title: "Enable avatars via plugin settings (re-enable after disabling)"
feature: avatar
priority: medium
tags: [avatar, enable, re-enable, frontend, settings, show_avatars]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-121 — Enable Avatars via Plugin Settings (Re-enable After Disabling)

## Summary
Verifies that unchecking "Disable Avatars" in the plugin settings re-enables avatar display site-wide. Tests that the toggle works in both directions — from disabled back to enabled — and that avatar images reappear in the comments section after saving.

## Prerequisites
- [ ] WordPress site is running
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Disable Avatars" is currently checked/enabled (avatars are currently suppressed)
- [ ] At least one published Post with existing approved comments exists

## Test Data

| Field | Value |
|-------|-------|
| Settings page | `/wp-admin/admin.php?page=disable_comments_settings` |
| Settings tab | Disable |
| Avatar toggle label | "Disable Avatars" |
| WordPress option | `show_avatars` |
| Avatar CSS selector | `.avatar, img.avatar` |
| Test post URL | A post with at least one approved comment |
| Initial state | Avatars disabled (`show_avatars = false`) |
| Target state | Avatars enabled (`show_avatars = true`) |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads |
| 2 | Confirm "Disable Avatars" is checked (avatars currently disabled) | Checkbox is in the checked state |
| 3 | Navigate to the test post URL on the frontend | Post loads; verify `.avatar` images are NOT present (confirming current disabled state) |
| 4 | Return to settings and uncheck "Disable Avatars" | Checkbox becomes unchecked |
| 5 | Click Save Changes | Page reloads with a success notice |
| 6 | Navigate to the test post URL on the frontend | Post with comments loads |
| 7 | Verify that avatar `<img>` elements with class `.avatar` ARE rendered next to comments | At least one avatar image is visible in the comments section |
| 8 | Verify the avatar images have a valid `src` attribute (e.g., Gravatar URL) | Images have non-empty `src` pointing to an avatar service |
| 9 | Reload the settings page and verify "Disable Avatars" is unchecked | Setting persists; avatars remain enabled |

## Expected Results
- After unchecking "Disable Avatars" and saving, `show_avatars` is set to `true`.
- Avatar `<img>` elements (from `get_avatar()`) reappear in the comments section.
- The toggle genuinely works in both directions — disabling and re-enabling avatars.
- The setting persists across page reloads.

## Negative / Edge Cases
- If the commenters have not set a Gravatar, a default avatar image (e.g., Mystery Person or identicon) should still render when avatars are enabled — the presence of the `<img>` tag matters, not the specific avatar image.
- Browser or server-side caching may cause the old (avatar-absent) page to be served; a hard reload (`Ctrl+Shift+R`) or cache-bust query param may be needed.

## Playwright Notes
**Page URL:** `/wp-admin/admin.php?page=disable_comments_settings` (settings), then test post URL (frontend)

**Key Selectors:**
- `#disable_avatars` or `[name="disable_comments_options[disable_avatars]"]` — the "Disable Avatars" checkbox
- `.avatar, img.avatar` — avatar images in comments (should be present after re-enabling)
- `#submit, input[type="submit"]` — Save Changes button
- `.notice-success, #setting-error-settings_updated` — success notice

**Implementation hints:**
- Step 3 (confirming disabled state before the toggle) acts as a sanity check; `expect(page.locator('img.avatar')).toHaveCount(0)`.
- After re-enabling and saving, assert `expect(page.locator('img.avatar')).not.toHaveCount(0)` or use `toBeGreaterThan(0)` on the count.
- Check `src` attribute: `expect(page.locator('img.avatar').first()).toHaveAttribute('src', /gravatar|wp-includes\/images/)`.
- This test can be chained with TC-120 in a `describe` block — TC-120 disables, TC-121 re-enables — to test the full lifecycle.

## Related
- **WordPress Option:** `show_avatars` (standard WP option)
- **WordPress Function:** `get_avatar()`, `update_option('show_avatars', true)`
- **Related TC:** TC-120, TC-122
