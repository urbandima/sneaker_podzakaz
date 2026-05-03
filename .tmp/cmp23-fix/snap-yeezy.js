const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 });
  const page = await ctx.newPage();
  await page.goto('http://127.0.0.1:8765/catalog/product/adidas-yeezy-350-v2-zebra?_v=' + Date.now(), { waitUntil: 'networkidle' });
  await page.evaluate(() => window.scrollTo(0, 1200));
  await page.waitForTimeout(700);
  const sb = await page.evaluate(() => {
    const el = document.getElementById('stickyBar');
    const r = el.getBoundingClientRect();
    return { top: r.top, height: r.height };
  });
  console.log('sb geom:', sb);
  await page.screenshot({ path: '.tmp/cmp23-fix/after-yeezy-stickybar.png', clip: { x: 0, y: Math.max(0, sb.top - 8), width: 390, height: sb.height + 16 } });
  await browser.close();
})();
