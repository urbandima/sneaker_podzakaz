# QA Performance Audit — Done Report
**Session**: 2026-04-26  
**Tool**: Lighthouse CI (`@lhci/cli` v0.15.1), desktop preset, PHP built-in dev server

---

## Score summary

| Page | Perf before | Perf after | A11y before | A11y after | BP before | BP after | SEO before | SEO after |
|------|-------------|------------|-------------|------------|-----------|----------|------------|-----------|
| `/` (home) | 46 | **69** | 92 | **99** | 100 | **100** | 100 | **100** |
| `/catalog` | 44 | **68** | 83 | **100** | 100 | **100** | 100 | **100** |
| `/catalog/product/*` | — | **67** | — | **100** | — | **100** | — | **100** |
| `/admin/login` | 57† | **72** | 91 | **100** | 96 | **100** | 66† | 54†† |

† Original baseline measured `/account/login`; admin login is a different URL.  
†† Admin/login SEO 54 is intentional: `<meta name="robots" content="noindex">` — admin pages must not be indexed.

---

## Fixes applied

### Bug fix: Infinite scroll never loaded page 2+
**File**: `frontend/views/catalog/index.php`

Yii2 Pagination uses 1-based URL params (`?page=1` = internal index 0). The JS infinite scroll was using `currentPage + 1` as the URL param directly, which always returned the first page. Fixed to `nextPage + 1` as the URL param. Also added `cache: 'no-store'` to the fetch to prevent 304 responses on re-visited page URLs.

---

### Accessibility (A11y)

| # | File | Fix |
|---|------|-----|
| 1 | `footer.php` | `role="img"` + `aria-label` on all 5 payment icon `<span>`s (`aria-prohibited-attr`) |
| 2 | `footer.css` | `.payment-label` → `var(--c-gray-400)` (7.5:1 ratio; was gray-500 ≈ 3.9:1 — fail) |
| 3 | `footer.php` | Director feedback link: inline `color:#e11d48` (3.85:1 fail) → `.footer-director-link { color: #f87171 }` (6.1:1) |
| 4 | `AppAsset.php` + `main.php` | Bootstrap Icons CDN moved from blocking `$css` to async `<link rel="preload" as="style" onload>` |
| 5 | `catalog/index.php` | `aria-label` on size-nav-btn left/right + `qv-close`; `aria-hidden="true"` on decorative icons |
| 6 | `catalog/index.php` | `<label class="sr-only">` for sort and per-page `<select>` elements |
| 7 | `catalog/index.php` | `<h2 class="sr-only">Список товаров</h2>` before products grid (heading order) |
| 8 | `catalog/product.php` | Delivery section `<h4>` → `<h3>` (was skipping h2→h4) |
| 9 | `catalog/product.php` | Size-selector heading `<h3>` → `<h2>` (fits page hierarchy: h1 title → h2 size/sections) |
| 10 | `product.css` | `.legend-item.available` color `#16a34a` (3.29:1 fail) → `#166534` (6.15:1 pass) |
| 11 | `product.css` | `.legend-item.unavailable` `opacity:0.7` removed; uses `var(--c-gray-400)` directly |
| 12 | `admin/login.php` CSS | Password-toggle min size 44×44 px (WCAG 2.5.8 touch target) |
| 13 | `site/index.php` | Brand logo `alt=""` (was redundant — brand name already in adjacent `.brand-label`) |

---

### Best Practices

- Fixed `aria-prohibited-attr` on payment icon spans (item #1 above).
- No console errors in final audit.

---

### Performance

| Fix | Impact |
|-----|--------|
| Per-controller CSS split: removed `catalog.css`, `product.css`, `cart.css`, `checkout.css`, `account.css` from AppAsset; load per controller in layout | ~810 ms less render-blocking CSS on home page; LCP improved from 1.5 s → 0.9–1.0 s |
| `POS_READY` → `POS_END` for inline scripts in catalog/index.php and product.php | Removes unnecessary `jQuery(function(){})` DOM-ready wrapper for vanilla-JS blocks |
| Bootstrap Icons async preload (item #4 in A11y) | Removes 1 render-blocking external resource |

---

### SEO

- Home, catalog, product pages: all SEO metrics pass (100/100).
- Admin/login: intentional `noindex` → SEO 54 is expected and correct.
- The original `/account/login` baseline score of 66 was for a different URL; it is not regressed.

---

## Admin codebase — backlog findings

### Dead code removed
- `backend/modules/admin/views/poizon/poizon/` — 5 view files (errors, index, run, view-log, view) that were exact duplicates of `views/poizon/` differing only in CSS class names (`btn` vs `admin-btn`). Never referenced by PoizonController. **Deleted.**

### Real TODO/FIXME items (not false positives)

| Location | Item | Priority |
|----------|------|----------|
| `SettingsController.php:176` | CSRF token disabled for settings AJAX — needs `X-CSRF-Token` header in JS fetch | Medium |
| `CustomerController.php:46` | CSRF token bypass for customer AJAX — same fix needed | Medium |
| `views/product/edit.php:1600` | `deleteCharacteristicInline()` stub — `/* TODO */` inside confirm callback; characteristics inline delete not implemented | Low |
| `admin-settings.js:1573–1615` | 5 integration endpoints stubs: amoCRM test, amoCRM save, МойСклад test, МойСклад save, Telegram bot save — no backend endpoints exist | Low |

### GridView audit
- 11 GridView usages across admin views — no excessive duplication, each is in its own dedicated view.
- `BaseAdminController` exists; all 36+ admin controllers already extend it.

---

## What remains below target (Performance < 80)

Performance scores are 67–72 on shop pages (target 80). The gap is almost entirely **Total Blocking Time (TBT)**:

- Shop pages: TBT 780–1120 ms
- Admin login: TBT 330 ms (closer to target)

**Root cause in dev environment**: x64 Chrome running via Rosetta on arm64 Mac + PHP built-in server inflates JS evaluation times significantly. The jQuery.min.js evaluation alone accounts for one 394 ms long task — abnormally high for 87 KB of JS on modern hardware.

**Expected production improvement**: with nginx + gzip + CDN + minified assets, TBT should drop to 150–350 ms, placing Performance in the 80–90 range.

**To close the remaining gap in dev**, the following production-level changes would be needed:
1. **Minify CSS/JS** (Gulp/webpack pipeline) — saves ~40% parse/eval time
2. **Image CDN / WebP conversion** — eliminates `uses-modern-image-formats` and `uses-responsive-images` warnings
3. **Inline critical CSS** — extract above-fold CSS into `<style>` block, load rest async (complex)
4. **jQuery defer** — can be implemented by: (a) updating web.php JqueryAsset config to include defer, and (b) adding a tiny `jQuery(fn)` ready-queue shim inline in `<head>` so Yii2 ActiveForm's inline callbacks still work when jQuery loads deferred

---

## Commits in this session

| Hash | Message |
|------|---------|
| `c9c98e2` | fix(catalog): infinite scroll page offset — Yii2 1-based URL pagination |
| `30b9b17` | a11y: fix aria-prohibited-attr, contrast, and target-size |
| `6d83636` | a11y: aria-labels catalog buttons/selects, heading order, legend contrast |
| `2ff1f72` | perf: split page-specific CSS per-controller — removes up to 810ms render-blocking |
| `8cf4acb` | perf: replace POS_READY with POS_END in catalog/product views |
| (pending) | docs + admin dead-code cleanup |
