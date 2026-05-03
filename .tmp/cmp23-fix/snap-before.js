const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 });
  const page = await ctx.newPage();
  page.on('pageerror', e => console.log('PAGEERROR', e.message));
  await page.goto('http://127.0.0.1:8765/catalog/product/adidas-yeezy-350-v2-zebra', { waitUntil: 'networkidle' });
  // Scroll past the SCROLL_THRESHOLD (200) to make sticky bar visible via the JS in product-page.js
  await page.evaluate(() => window.scrollTo(0, 1200));
  await page.waitForTimeout(700);
  await page.screenshot({ path: '.tmp/cmp23-fix/before-mobile-fold.png', fullPage: false });

  const diag = await page.evaluate(() => {
    const sb = document.getElementById('stickyBar');
    if (!sb) return { error: 'no stickyBar' };
    const cs = getComputedStyle(sb);
    const out = { sbTransform: cs.transform, sbOpacity: cs.opacity, hasVisible: sb.classList.contains('visible') };
    const price = sb.querySelector('.sticky-price');
    out.priceText = price ? price.textContent : null;
    out.priceDisplay = price ? getComputedStyle(price).display : null;
    const thumb = sb.querySelector('.sticky-thumb');
    if (thumb) {
      const r = thumb.getBoundingClientRect();
      out.thumb = { srcStart: thumb.getAttribute('src').slice(0, 40), display: getComputedStyle(thumb).display, w: r.width, h: r.height, top: r.top, left: r.left };
    }
    const sizeBtn = document.getElementById('stickySizeBtn');
    if (sizeBtn) {
      const r = sizeBtn.getBoundingClientRect();
      out.sizeBtnRect = { top: r.top, bottom: r.bottom, left: r.left, right: r.right };
    }
    // Walk all leaf nodes inside sticky bar; report any with BYN that is *visible*
    const hits = [];
    sb.querySelectorAll('*').forEach(el => {
      if (el.children.length === 0) {
        const t = (el.textContent || '').trim();
        if (t && /BYN/.test(t)) {
          const r = el.getBoundingClientRect();
          const cs2 = getComputedStyle(el);
          hits.push({ tag: el.tagName, cls: el.className, text: t, display: cs2.display, top: r.top, left: r.left, w: r.width, h: r.height });
        }
      }
    });
    out.bynHits = hits;
    return out;
  });
  console.log(JSON.stringify(diag, null, 2));
  await browser.close();
})();
