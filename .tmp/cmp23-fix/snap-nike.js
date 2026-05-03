const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 });
  const page = await ctx.newPage();
  page.on('pageerror', e => console.log('PAGEERROR', e.message));
  await page.goto('http://127.0.0.1:8765/catalog/product/nike-dunk-low-panda', { waitUntil: 'networkidle' });
  await page.evaluate(() => window.scrollTo(0, 1200));
  await page.waitForTimeout(700);
  await page.screenshot({ path: '.tmp/cmp23-fix/before-nike-stickybar.png', fullPage: false });

  const diag = await page.evaluate(() => {
    const sb = document.getElementById('stickyBar');
    const out = {};
    const thumb = sb.querySelector('.sticky-thumb');
    if (thumb) {
      const r = thumb.getBoundingClientRect();
      out.thumbSrcStart = thumb.getAttribute('src').slice(0, 80);
      out.thumbDisplay = getComputedStyle(thumb).display;
      out.thumbVisible = r.width > 0 && r.height > 0;
      out.thumbRect = { top: r.top, left: r.left, w: r.width, h: r.height };
    }
    const price = sb.querySelector('.sticky-price');
    out.priceText = price ? price.textContent : null;
    out.priceDisplay = price ? getComputedStyle(price).display : null;
    return out;
  });
  console.log(JSON.stringify(diag, null, 2));
  await browser.close();
})();
