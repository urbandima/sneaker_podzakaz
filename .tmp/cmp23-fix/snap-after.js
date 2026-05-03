const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 });
  const page = await ctx.newPage();
  page.on('pageerror', e => console.log('PAGEERROR', e.message));
  // bypass any cache
  await page.route('**/*', route => route.continue());

  const url = 'http://127.0.0.1:8765/catalog/product/nike-dunk-low-panda?_v=' + Date.now();
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.evaluate(() => window.scrollTo(0, 1200));
  await page.waitForTimeout(800);

  // Mobile fold: just the sticky-bar area (last 200px of viewport)
  await page.screenshot({ path: '.tmp/cmp23-fix/after-mobile-fold.png', clip: { x: 0, y: 644, width: 390, height: 200 } });

  // Also full mobile page (above-the-fold) for completeness
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForTimeout(300);
  await page.screenshot({ path: '.tmp/cmp23-fix/after-mobile-fold-full.png' });

  // Trigger sticky again then capture
  await page.evaluate(() => window.scrollTo(0, 1200));
  await page.waitForTimeout(500);
  await page.screenshot({ path: '.tmp/cmp23-fix/after-mobile-stickybar.png', clip: { x: 0, y: 700, width: 390, height: 144 } });

  const diag = await page.evaluate(() => {
    const sb = document.getElementById('stickyBar');
    const out = {};
    const thumb = sb.querySelector('.sticky-thumb');
    if (thumb) {
      out.thumbTag = thumb.tagName;
      out.thumbClass = thumb.className;
      const r = thumb.getBoundingClientRect();
      out.thumbRect = { w: r.width, h: r.height };
      out.thumbDisplay = getComputedStyle(thumb).display;
    }
    const price = sb.querySelector('.sticky-price');
    out.priceText = price ? price.textContent : null;
    out.priceDisplay = price ? getComputedStyle(price).display : null;
    // Hunt for any visible BYN inside sticky-bar
    const visibleByn = [];
    sb.querySelectorAll('*').forEach(el => {
      if (el.children.length === 0) {
        const t = (el.textContent || '').trim();
        if (t && /BYN/.test(t)) {
          const r = el.getBoundingClientRect();
          if (r.width > 0 && r.height > 0) {
            visibleByn.push({ cls: el.className, text: t, w: r.width, h: r.height });
          }
        }
      }
    });
    out.visibleByn = visibleByn;
    return out;
  });
  console.log(JSON.stringify(diag, null, 2));

  // Test desktop too — make sure desktop layout still works
  const dctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const dpage = await dctx.newPage();
  await dpage.goto('http://127.0.0.1:8765/catalog/product/nike-dunk-low-panda?_v=' + Date.now(), { waitUntil: 'networkidle' });
  await dpage.evaluate(() => window.scrollTo(0, 1200));
  await dpage.waitForTimeout(700);
  await dpage.screenshot({ path: '.tmp/cmp23-fix/after-desktop-stickybar.png', clip: { x: 0, y: 800, width: 1440, height: 100 } });
  await dpage.screenshot({ path: '.tmp/cmp23-fix/after-desktop-fold.png', clip: { x: 0, y: 0, width: 1440, height: 900 } });
  const dDiag = await dpage.evaluate(() => {
    const price = document.querySelector('#stickyBar .sticky-price');
    return {
      priceText: price ? price.textContent : null,
      priceDisplay: price ? getComputedStyle(price).display : null
    };
  });
  console.log('DESKTOP:', JSON.stringify(dDiag, null, 2));

  await browser.close();
})();
