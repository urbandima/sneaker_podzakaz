const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const out = {};

  for (const [name, vp] of [['mobile', { width: 390, height: 844 }], ['desktop', { width: 1440, height: 900 }]]) {
    const ctx = await browser.newContext({ viewport: vp, deviceScaleFactor: 2 });
    const page = await ctx.newPage();
    await page.goto('http://127.0.0.1:8765/', { waitUntil: 'networkidle' });
    const data = await page.evaluate(() => {
      const h = document.querySelector('header.main-header, header.ecom-header');
      const navMenu = document.querySelector('.main-nav .nav-menu, .ecom-header .nav-menu');
      const navMenuVisible = navMenu ? getComputedStyle(navMenu).display !== 'none' : null;
      const navMenuChildren = navMenu ? navMenu.children.length : null;
      const actions = document.querySelector('.header-actions');
      const headerRect = h ? h.getBoundingClientRect() : null;
      const actionsRect = actions ? actions.getBoundingClientRect() : null;
      const cartBtn = document.querySelector('.btn-cart, [aria-label="Корзина"]');
      const cartRect = cartBtn ? cartBtn.getBoundingClientRect() : null;
      const searchBtn = document.querySelector('.btn-search, .header-search');
      const searchRect = searchBtn ? searchBtn.getBoundingClientRect() : null;
      return {
        innerWidth: window.innerWidth,
        docScrollWidth: document.documentElement.scrollWidth,
        bodyScrollWidth: document.body.scrollWidth,
        headerCls: h ? h.className : null,
        headerRect,
        navMenuVisible,
        navMenuChildren,
        actionsRect,
        cartRectRight: cartRect ? cartRect.right : null,
        cartVisible: cartRect ? (cartRect.right > 0 && cartRect.right <= window.innerWidth) : null,
        searchRectRight: searchRect ? searchRect.right : null,
      };
    });
    await page.screenshot({ path: `/Users/user/Downloads/_Организовано_2026-04-21/04_Веб_проекты_код/Сайты_магазины/splitwise/.tmp/cmp56/before-${name}.png`, fullPage: false });
    out[name] = data;
    await ctx.close();
  }
  await browser.close();
  console.log(JSON.stringify(out, null, 2));
})();
