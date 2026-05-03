// CMP-22 UX review — capture mobile + desktop catalog at real viewports
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const URL = 'http://127.0.0.1:8765/catalog';
const OUT = path.join(__dirname, 'cmp22');
fs.mkdirSync(OUT, { recursive: true });

(async () => {
  const browser = await chromium.launch({ headless: true });

  for (const v of [
    { name: 'mobile-390x844', width: 390, height: 844, isMobile: true, deviceScaleFactor: 2 },
    { name: 'desktop-1440x900', width: 1440, height: 900, isMobile: false, deviceScaleFactor: 1 },
  ]) {
    const ctx = await browser.newContext({
      viewport: { width: v.width, height: v.height },
      deviceScaleFactor: v.deviceScaleFactor,
      isMobile: v.isMobile,
      hasTouch: v.isMobile,
      userAgent: v.isMobile
        ? 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
        : undefined,
    });
    const page = await ctx.newPage();
    await page.goto(URL, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1200); // let skeleton/lazy + JS settle

    // Snapshot full page
    await page.screenshot({ path: path.join(OUT, `${v.name}-full.png`), fullPage: true });
    // Above-the-fold for hero comparison
    await page.screenshot({ path: path.join(OUT, `${v.name}-fold.png`), fullPage: false });

    const measurements = await page.evaluate(() => {
      const q = (sel) => document.querySelector(sel);
      const productGrid = q('.product-grid');
      const cards = document.querySelectorAll('.product-grid > .product-card, .products-grid > .product-card');
      const cardRects = Array.from(cards).slice(0, 4).map((c) => {
        const r = c.getBoundingClientRect();
        return { left: Math.round(r.left), right: Math.round(r.right), top: Math.round(r.top), w: Math.round(r.width), h: Math.round(r.height) };
      });
      const h1All = Array.from(document.querySelectorAll('h1')).map((h) => h.textContent.trim());
      const catalogPage = q('.catalog-page');
      const brandRail = q('.quick-filters-bar.scroll-rail');
      const sizeRail = q('.sizes-scroll-container.scroll-rail');
      const sizeNavBtns = document.querySelectorAll('.size-nav-btn');
      const filterBarEmptyBtns = Array.from(document.querySelectorAll('.catalog-toolbar button, .quick-filters-bar button, .quick-filters-sizes button')).filter((b) => {
        const txt = (b.textContent || '').trim();
        const hasIcon = b.querySelector('i.bi, svg, img');
        return !txt && !hasIcon;
      }).length;

      const styles = (el) => el ? getComputedStyle(el) : null;
      const rail1 = styles(brandRail);
      const rail2 = styles(sizeRail);

      return {
        viewport: { w: window.innerWidth, h: window.innerHeight },
        documentScrollWidth: document.documentElement.scrollWidth,
        bodyScrollWidth: document.body.scrollWidth,
        catalogPageScrollWidth: catalogPage ? catalogPage.scrollWidth : null,
        productGridGridTemplateColumns: styles(productGrid)?.gridTemplateColumns,
        productGridGap: styles(productGrid)?.gap,
        cardCount: cards.length,
        cardRects,
        h1Count: h1All.length,
        h1Texts: h1All,
        sizeNavBtnCount: sizeNavBtns.length,
        emptyButtonsInFilterBar: filterBarEmptyBtns,
        brandRailOverflowX: rail1?.overflowX,
        brandRailMaskImage: rail1?.maskImage || rail1?.webkitMaskImage,
        brandRailScrollWidth: brandRail?.scrollWidth,
        brandRailClientWidth: brandRail?.clientWidth,
        sizeRailOverflowX: rail2?.overflowX,
        sizeRailMaskImage: rail2?.maskImage || rail2?.webkitMaskImage,
        sizeRailScrollWidth: sizeRail?.scrollWidth,
        sizeRailClientWidth: sizeRail?.clientWidth,
      };
    });

    fs.writeFileSync(path.join(OUT, `${v.name}.json`), JSON.stringify(measurements, null, 2));
    console.log(`\n=== ${v.name} ===`);
    console.log(JSON.stringify(measurements, null, 2));

    await ctx.close();
  }

  await browser.close();
})();
