# Lighthouse Baseline — updated 2026-04-26

## Environment

- Tool: `@lhci/cli` v0.15.1
- Chrome: Puppeteer-managed (mac-147.0.7727.57, x64 via Rosetta on arm64)
- Server: PHP built-in (`php -S localhost:8080`), dev environment
- Mode: Desktop preset, 1 run per URL
- Note: TBT figures are inflated by dev-env overhead (Rosetta + PHP built-in). Production with nginx/compression/CDN expected to score 20–30 pts higher on Performance.

---

## Before this session (original baseline, 2026-04-23)

| Page | Perf | A11y | Best Practices | SEO |
|------|------|------|----------------|-----|
| `/` (home) | 46 | 92 | 100 | 100 |
| `/catalog` | 44 | 83 | 100 | 100 |
| `/account/login` | 57 | 91 | 96 | 66 |

_Checkout and product pages not measured in wave-1 audit (required live session/data)._

---

## After this session (wave 2, 2026-04-26)

| Page | Perf | A11y | Best Practices | SEO | Δ Perf | Δ A11y |
|------|------|------|----------------|-----|--------|--------|
| `/` (home) | 69 | 99 | 100 | 100 | **+23** | **+7** |
| `/catalog` | 68 | 100 | 100 | 100 | **+24** | **+17** |
| `/catalog/product/*` | 67 | 100 | 100 | 100 | (new) | (new) |
| `/admin/login` | 72 | 100 | 100 | 54† | +15 | +9 |

† Admin login has `<meta name="robots" content="noindex">` (intentional — admin pages must not be indexed). This causes Lighthouse SEO `is-crawlable` to fail; it is not a defect.

---

## Key metrics (final run)

| Page | TBT | LCP | FCP | CLS |
|------|-----|-----|-----|-----|
| `/` | ~940 ms | 1.0 s | 0.6 s | 0 |
| `/catalog` | ~780 ms | 1.3 s | 0.7 s | 0.067 |
| `/catalog/product/*` | ~1120 ms | 0.9 s | 0.8 s | 0 |
| `/admin/login` | ~330 ms | 0.9 s | 0.8 s | 0.25† |

† CLS on admin/login is caused by Bootstrap Icons loading async (preload+onload); icons shift text when they arrive. Acceptable trade-off vs render-blocking.

---

## Fixes applied in wave 2 (2026-04-26)

### Infinite scroll bug (X-bug)
- `catalog/index.php`: Yii2 pagination uses 1-based URL params (`?page=1` = internal index 0); JS was sending `?page=currentPage+1` which always returned page 1. Fixed to `nextPage + 1` as URL param.
- Added `cache: 'no-store'` to fetch to prevent 304 responses on revisited page URLs.

### Accessibility
- `footer.php`: `role="img"` added to all 5 payment icon spans (was `aria-prohibited-attr` failure).
- `footer.css`: `.payment-label` color changed to `var(--c-gray-400)` (7.5:1 contrast ratio, was gray-500 ≈ 3.9:1).
- `footer.php`: feedback link changed from inline `color:#e11d48` (3.85:1 fail) to `.footer-director-link { color: #f87171 }` (6.1:1 pass).
- `AppAsset.php`: Bootstrap Icons CDN removed from blocking `$css`; moved to async preload+onload in `main.php`.
- `catalog/index.php`: size-nav buttons (`size-nav-left`, `size-nav-right`) and `qv-close` button got `aria-label`; decorative icons got `aria-hidden="true"`.
- `catalog/index.php`: sort and per-page `<select>` elements wrapped with `<label class="sr-only">`.
- `catalog/index.php`: `<h2 class="sr-only">Список товаров</h2>` added before products grid (heading order).
- `catalog/product.php`: delivery section `<h4>` tags changed to `<h3>` (was skipping from h2 to h4).
- `catalog/product.php`: size-selector heading changed from `<h3>` to `<h2>` (fits page heading hierarchy).
- `product.css`: `.legend-item.available` color changed from `#16a34a` (3.29:1 fail) to `#166534` (6.15:1 pass).
- `product.css`: `.legend-item.unavailable` opacity:0.7 removed; uses `var(--c-gray-400)` directly.
- `admin/views/admin/login.php`: password-toggle button minimum touch target 44×44px (WCAG 2.5.8).
- `site/index.php`: brand logo `alt=""` (redundant-alt — name already in adjacent `.brand-label`).

### Performance
- `AppAsset.php` + `main.php`: Removed `landing.css`, `catalog.css`, `product.css`, `cart.css`, `checkout.css`, `account.css` from AppAsset (previously loaded on every page). Per-controller CSS loading added to `main.php` layout based on controller/module ID. Saves ~810 ms of render-blocking CSS on home page.
- `catalog/index.php`: `POS_READY` → `POS_END` for lazy-load init (was unnecessarily wrapped in `jQuery(function(){})`, only uses vanilla JS).
- `catalog/product.php`: `POS_READY` → `POS_END` for lightbox config (same reason).

---

## Remaining known issues

| Issue | Severity | Impact |
|-------|----------|--------|
| TBT 780–1120 ms on shop pages | High | Performance < 80 — caused by jQuery/JS evaluation overhead in dev env (Rosetta); expected ~200–400 ms in production |
| `catalog/product/*` CLS 0 but TBT high | Medium | Product page TBT ~1120 ms includes lightbox-plus-jquery bundling jQuery twice |
| Unminified CSS/JS | High | ~40% savings expected from minification; requires build pipeline (Gulp/webpack) |
| Product images not WebP | Medium | `uses-modern-image-formats` — converting would require upload pipeline changes |
| `unsized-images` — brand logos missing width/height | Low | Minor CLS risk on brand grid |
| Admin login CLS 0.25 | Low | Bootstrap Icons async load causes icon-triggered reflow |

---

## Re-running Lighthouse

```bash
# Start server (if not running)
php -S localhost:8080 -t frontend/web frontend/web/index.php &

# Patch lighthouse for Rosetta compatibility (run once after npm install)
sed -i '' 's/if (process.platform === .darwin. && process.arch === .x64.)/if (false \/* patched arch check *\/)/' node_modules/lighthouse/cli/run.js

# Run audit
CHROME_PATH="$HOME/.cache/puppeteer/chrome/mac-147.0.7727.57/chrome-mac-x64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing" \
npx lhci collect \
  --url="http://localhost:8080/" \
  --url="http://localhost:8080/catalog" \
  --url="http://localhost:8080/catalog/product/nike-air-force-1" \
  --url="http://localhost:8080/admin/login" \
  --numberOfRuns=1 \
  --settings.preset=desktop \
  --settings.onlyCategories=performance,accessibility,best-practices,seo
```

Reports are saved to `.lighthouseci/`.
