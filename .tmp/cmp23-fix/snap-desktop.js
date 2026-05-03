const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  await page.goto('http://127.0.0.1:8765/catalog/product/nike-dunk-low-panda?_v=' + Date.now(), { waitUntil: 'networkidle' });
  await page.evaluate(() => {
    window.scrollTo(0, 1200);
    // Force-show sticky-bar to be safe
    const sb = document.getElementById('stickyBar');
    if (sb) {
      sb.classList.add('visible');
      sb.style.transform = 'translateY(0)';
      sb.style.opacity = '1';
    }
  });
  await page.waitForTimeout(900);
  const info = await page.evaluate(() => {
    const sb = document.getElementById('stickyBar');
    const r = sb.getBoundingClientRect();
    const price = sb.querySelector('.sticky-price');
    const pr = price ? price.getBoundingClientRect() : null;
    return {
      sb: { top: r.top, left: r.left, w: r.width, h: r.height, opacity: getComputedStyle(sb).opacity, transform: getComputedStyle(sb).transform },
      price: price ? { display: getComputedStyle(price).display, top: pr.top, w: pr.width, h: pr.height, text: price.textContent } : null,
    };
  });
  console.log(JSON.stringify(info, null, 2));
  await page.screenshot({ path: '.tmp/cmp23-fix/after-desktop-stickybar-wide.png', clip: { x: 0, y: Math.max(0, info.sb.top - 10), width: 1440, height: Math.min(150, info.sb.h + 20) } });
  await browser.close();
})();
