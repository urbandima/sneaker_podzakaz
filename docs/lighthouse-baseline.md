# Lighthouse Baseline — 2026-04-23

## Environment

- Tool: `@lhci/cli` v0.15.1
- Chrome: Puppeteer-managed (mac-140.0.7339.207, x64 via Rosetta on arm64)
- Server: PHP built-in (`php -S localhost:8080`), original codebase
- Mode: Desktop, 1 run per URL

---

## Baseline scores (before this wave of fixes)

| Page | Perf | A11y | Best Practices | SEO |
|------|------|------|---------------|-----|
| `/` (home) | 46 | 92 | 96 | 100 |
| `/catalog` | 44 | 83 | 96 | 98 |
| `/account/login` | 57 | 91 | 96 | 92 |

_Checkout and product pages could not be measured (require live product data / server sessions)._

---

## Fixes applied in this session (wave 1)

### Performance
- **Async font loading** — Google Fonts and Bootstrap Icons CDN moved from synchronous `<link>` to `<link rel="preload" as="style" onload="...">` in `frontend/views/layouts/main.php`. Eliminates 2 render-blocking resources (~600–900 ms FCP impact).
- **Removed duplicate Google Fonts** from `AppAsset::$css` (was loaded twice).
- **Apache gzip + cache headers** — `frontend/web/.htaccess`: `mod_deflate` for HTML/CSS/JS/SVG/fonts; `Cache-Control: max-age=31536000` for static assets; `max-age=86400` for HTML.

### Accessibility
- **Heading order** — Footer `<h4 class="footer-title">` → `<h3>` (no skipped levels). Catalog sidebar `<h3>` → `<p role="heading" aria-level="2">`.
- **WCAG color contrast** — `.chip-count` changed from `color: var(--color-text-muted); opacity: 0.7` (ratio ≈2.5:1, fail) to `color: #595959` (ratio ≈7.0:1, pass).
- **Button accessible names** — View-toggle buttons (grid/list) got `aria-label` + `aria-pressed` attributes.
- **Payment icon roles** — All 5 SVG payment icons wrapped in `<span role="img" aria-label="...">`.

---

## Expected score improvements (post-fix)

| Page | Perf (est.) | A11y (est.) |
|------|------------|------------|
| `/` | 70–80 | 95+ |
| `/catalog` | 65–75 | 92–95 |
| `/account/login` | 75–85 | 95+ |

_Perf gains driven by removing render-blocking fonts. A11y gains from heading order, contrast, and ARIA fixes._

---

## Known remaining issues

| Issue | Severity | Location |
|-------|----------|----------|
| `unsized-images` — brand logos missing `width`/`height` | Medium | `/catalog`, `/brands` |
| `color-contrast` — `payment-label` span (`#6B7280` ≈ 4.5:1, borderline) | Low | Footer |
| `color-contrast` — feedback/contact link `color:#e11d48` (≈3.85:1, fail) | Medium | Rendered HTML (source unknown — may be DB-driven) |
| `uses-optimized-images` — product images not WebP | High | All pages |
| `uses-text-compression` — gzip only works on Apache; PHP built-in dev server ignores `.htaccess` | Info | Dev only |

---

## Re-running Lighthouse

```bash
# Start server
php -S localhost:8080 -t frontend/web frontend/web/index.php &

# Run
npm run lhci
# or
bash scripts/lhci-run.sh
```

Reports are saved to `.lighthouseci/`.
