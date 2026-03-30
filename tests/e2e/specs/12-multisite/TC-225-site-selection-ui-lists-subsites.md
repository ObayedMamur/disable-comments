---
id: TC-225
title: "Site selection UI lists all sub-sites in the network"
feature: multisite
priority: medium
tags: [site-selection, ajax, get-sub-sites, ui, multisite]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-225 — Site selection UI lists all sub-sites in the network

## Summary
The settings page includes a site selection widget that loads sub-sites via AJAX from the `get_sub_sites` endpoint. This test verifies the UI loads and displays the expected sub-sites with their names and URLs, and that search filtering and pagination work correctly for networks with many sites.

## Prerequisites
- [ ] WordPress Multisite is configured and running
- [ ] Disable Comments plugin is network-activated
- [ ] Logged in as Super Administrator (Network Admin)
- [ ] At least two sub-sites exist in the network
- [ ] For pagination testing: ideally 20+ sub-sites (otherwise verify pagination does not appear for small networks)

## Test Data

| Field | Value |
|-------|-------|
| Network Admin Settings URL | `/wp-admin/network/admin.php?page=disable_comments_settings` |
| Sub-site 1 Admin URL | `http://site1.example.com/wp-admin/` |
| Sub-site 2 Admin URL | `http://site2.example.com/wp-admin/` |
| AJAX Endpoint | `/wp-admin/admin-ajax.php` |
| AJAX Action | `get_sub_sites` |
| AJAX Parameters | `type` (disabled/delete), `search`, `page`, `size` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/network/admin.php?page=disable_comments_settings` | Settings page loads successfully |
| 2 | Locate the site selection UI section on the page | A site list widget or selector is visible, possibly with a loading indicator initially |
| 3 | Wait for the AJAX request to `admin-ajax.php` with `action=get_sub_sites` to complete | Network request completes with HTTP 200 and a JSON response containing a site list |
| 4 | Verify the site list renders at least one entry per network sub-site | Site items are visible with names and/or URLs (e.g. `site1.example.com`) |
| 5 | Confirm all known sub-sites in the network are represented in the list | Site count in the UI matches the number of sub-sites in the network |
| 6 | Locate the search/filter input within the site selector and type a partial site name (e.g. "site1") | The list filters in real time or after a brief debounce, showing only sites matching the search term |
| 7 | Clear the search input and verify the full list is restored | All sub-sites are listed again after clearing the search |
| 8 | If 20+ sub-sites exist in the network, scroll to the bottom of the list | Pagination controls (next page button, page numbers, or load-more) are visible |

## Expected Results
- All network sub-sites are listed in the site selector widget after the AJAX request completes.
- Each site entry displays the site name and/or URL for identification.
- Search/filter input narrows the list to matching sites.
- Pagination controls appear when the site count exceeds the page size threshold (typically 10–20 sites).
- An empty network (only root site) still shows the root site in the selector.

## Negative / Edge Cases
- An empty search that returns no results should show a "no sites found" message rather than a blank list.
- If the AJAX request fails (e.g. nonce mismatch, server error), a visible error message must appear rather than a silent blank state.
- For a single-site result after filtering, pagination controls should be hidden.

## Playwright Notes
**Page URL:** `/wp-admin/network/admin.php?page=disable_comments_settings`

**Key Selectors:**
- `.dc-site-list li, .dc-sites-list .site-item, #disable-comments-site-list li` — individual site entries in the list
- `.dc-site-search input, input[name="site_search"]` — search/filter input
- `.dc-pagination, .dc-sites-pagination` — pagination controls
- `.dc-no-sites, .no-results` — empty state message

**Implementation hints:**
- Wait for AJAX: `await page.waitForResponse(resp => resp.url().includes('admin-ajax.php') && resp.url().includes('get_sub_sites') || (await resp.text()).includes('get_sub_sites'))`
- Alternatively: `await page.waitForResponse(r => r.url().includes('admin-ajax.php'))`
- Assert list populated: `await expect(page.locator('.dc-site-list li')).toHaveCount(greaterThanOrEqualTo(1))`
- For search test: `await page.fill('.dc-site-search input', 'site1')` then wait for list to update
- Note: multisite tests often need multiple browser contexts for different user roles/sites

## Related
- **WordPress Functions:** `get_sites()`, `get_site_option()`, `wp_nonce_field()`
- **AJAX Action:** `get_sub_sites` — accepts `type` (disabled/delete), `search`, `page`, `size` parameters; returns JSON site list with pagination metadata
- **Plugin Option Key:** `disable_comments_options.disabled_sites`
