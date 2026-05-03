const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 });
  const page = await ctx.newPage();
  await page.goto('http://127.0.0.1:8765/catalog/product/nike-dunk-low-panda?_v=' + Date.now(), { waitUntil: 'networkidle' });
  await page.evaluate(() => window.scrollTo(0, 1200));
  await page.waitForTimeout(800);
  const sb = await page.evaluate(() => {
    const el = document.getElementById('stickyBar');
    const r = el.getBoundingClientRect();
    const details = el.querySelector('.sticky-details');
    const dr = details.getBoundingClientRect();
    const brand = el.querySelector('.sticky-brand');
    const br = brand ? brand.getBoundingClientRect() : null;
    const brCs = brand ? getComputedStyle(brand) : null;
    const name = el.querySelector('.sticky-name');
    const nr = name ? name.getBoundingClientRect() : null;
    const nCs = name ? getComputedStyle(name) : null;
    return {
      sb: { top: r.top, h: r.height },
      details: { top: dr.top, h: dr.height, w: dr.width, left: dr.left, display: getComputedStyle(details).display },
      brand: br ? { display: brCs.display, top: br.top, left: br.left, w: br.width, h: br.height, text: brand.textContent } : null,
      name: nr ? { display: nCs.display, top: nr.top, left: nr.left, w: nr.width, h: nr.height, text: name.textContent } : null,
    };
  });
  console.log(JSON.stringify(sb, null, 2));
  await page.screenshot({ path: '.tmp/cmp23-fix/after-nike-mobile-stickybar.png', clip: { x: 0, y: Math.max(0, sb.sb.top - 12), width: 390, height: sb.sb.h + 24 } });
  await browser.close();
})();
