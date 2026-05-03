const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();

  for (const [name, vp] of [['mobile', { width: 390, height: 844 }], ['desktop', { width: 1440, height: 900 }]]) {
    const ctx = await browser.newContext({ viewport: vp, deviceScaleFactor: 2 });
    const page = await ctx.newPage();
    await page.goto('http://127.0.0.1:8765/', { waitUntil: 'networkidle' });
    const header = await page.$('header.main-header, header.ecom-header');
    await header.screenshot({ path: `/Users/user/Downloads/_Организовано_2026-04-21/04_Веб_проекты_код/Сайты_магазины/splitwise/.tmp/cmp56/header-${name}.png` });
    await ctx.close();
  }
  await browser.close();
  console.log('done');
})();
