const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch();
  // Desktop
  const ctxD = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const pageD = await ctxD.newPage();
  await pageD.goto('http://127.0.0.1:8765/', { waitUntil: 'networkidle', timeout: 30000 });
  await pageD.waitForTimeout(800);
  // Find categories section, scroll
  await pageD.evaluate(() => { const s = document.querySelector('.categories-section'); if (s) s.scrollIntoView({block:'start'}); });
  await pageD.waitForTimeout(400);
  await pageD.screenshot({ path: '/tmp/cmp35_categories_desktop.png', fullPage: false });
  // Scroll to IG section
  await pageD.evaluate(() => { const s = document.querySelector('.instagram-section, .instagram-grid, [class*="instagram"]'); if (s) s.scrollIntoView({block:'start'}); });
  await pageD.waitForTimeout(600);
  await pageD.screenshot({ path: '/tmp/cmp35_ig_desktop.png', fullPage: false });
  // Mobile
  const ctxM = await browser.newContext({ viewport: { width: 390, height: 844 } });
  const pageM = await ctxM.newPage();
  await pageM.goto('http://127.0.0.1:8765/', { waitUntil: 'networkidle', timeout: 30000 });
  await pageM.waitForTimeout(800);
  await pageM.evaluate(() => { const s = document.querySelector('.categories-section'); if (s) s.scrollIntoView({block:'start'}); });
  await pageM.waitForTimeout(400);
  await pageM.screenshot({ path: '/tmp/cmp35_categories_mobile.png', fullPage: false });
  await pageM.evaluate(() => { const s = document.querySelector('.instagram-section, .instagram-grid, [class*="instagram"]'); if (s) s.scrollIntoView({block:'start'}); });
  await pageM.waitForTimeout(600);
  await pageM.screenshot({ path: '/tmp/cmp35_ig_mobile.png', fullPage: false });
  // Detect horizontal scroll on 390
  const hScroll = await pageM.evaluate(() => ({ docW: document.documentElement.scrollWidth, vw: window.innerWidth }));
  console.log('mobile widths:', JSON.stringify(hScroll));
  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
