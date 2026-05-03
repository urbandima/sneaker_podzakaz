// CMP-80 visual-truth re-review for UXDesigner.
// Captures landing page at desktop (1440x900) + mobile (390x844),
// in two states: live IG embed and blocked-iframe simulation.
// Pulls computed values for .instagram-embed-wrap and .category-placeholder.
const { chromium } = require('/Users/user/Downloads/_Организовано_2026-04-21/04_Веб_проекты_код/Сайты_магазины/splitwise/node_modules/playwright');
const fs = require('fs');
const path = require('path');

const OUT = '/Users/user/Downloads/_Организовано_2026-04-21/04_Веб_проекты_код/Сайты_магазины/splitwise/outputs/cmp-80';
const URL = 'http://127.0.0.1:8765/';

const VIEWPORTS = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'mobile',  width: 390,  height: 844 },
];

async function blockIgFrames(context) {
  await context.route('**/*', (route) => {
    const u = route.request().url();
    if (u.includes('instagram.com') || u.includes('cdninstagram.com')) {
      return route.abort();
    }
    return route.continue();
  });
}

async function pullComputed(page) {
  return await page.evaluate(() => {
    const wrap = document.querySelector('.instagram-embed-wrap');
    const cats = Array.from(document.querySelectorAll('.category-placeholder'));
    const wrapBg = wrap ? getComputedStyle(wrap) : null;
    const catBg  = cats[0] ? getComputedStyle(cats[0]) : null;
    return {
      wrap: wrap ? {
        rect: wrap.getBoundingClientRect().toJSON(),
        backgroundSize: wrapBg.backgroundSize,
        backgroundPosition: wrapBg.backgroundPosition,
        backgroundImage: wrapBg.backgroundImage,
      } : null,
      categoryCount: cats.length,
      category: catBg ? {
        rect: cats[0].getBoundingClientRect().toJSON(),
        backgroundSize: catBg.backgroundSize,
        backgroundPosition: catBg.backgroundPosition,
        backgroundImage: catBg.backgroundImage,
      } : null,
    };
  });
}

async function shootCrop(page, sel, file) {
  const el = await page.$(sel);
  if (!el) return null;
  await el.scrollIntoViewIfNeeded();
  await page.waitForTimeout(250);
  await el.screenshot({ path: path.join(OUT, file) });
  return file;
}

async function run(viewport, mode) {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    viewport: { width: viewport.width, height: viewport.height },
    deviceScaleFactor: 2,
    userAgent: viewport.name === 'mobile'
      ? 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
      : undefined,
  });
  if (mode === 'blocked') await blockIgFrames(context);
  const page = await context.newPage();
  await page.goto(URL, { waitUntil: 'networkidle', timeout: 20000 });
  await page.waitForTimeout(800);
  const computed = await pullComputed(page);

  const tag = `${viewport.name}-${mode}`;
  const full = `${tag}-full.png`;
  await page.screenshot({ path: path.join(OUT, full), fullPage: true });

  const crops = {};
  crops.wrap     = await shootCrop(page, '.instagram-embed-wrap', `${tag}-wrap.png`);
  crops.category = await shootCrop(page, '.category-placeholder', `${tag}-category.png`);

  await browser.close();
  return { computed, full, crops };
}

(async () => {
  const out = {};
  for (const v of VIEWPORTS) {
    for (const mode of ['blocked', 'live']) {
      const key = `${v.name}_${mode}`;
      try {
        out[key] = await run(v, mode);
        console.log(`[ok] ${key}`);
      } catch (e) {
        out[key] = { error: String(e) };
        console.log(`[fail] ${key}: ${e.message}`);
      }
    }
  }
  fs.writeFileSync(path.join(OUT, 'computed.json'), JSON.stringify(out, null, 2));
  console.log('\n=== summary ===');
  for (const [k, v] of Object.entries(out)) {
    if (v.error) { console.log(`${k}: ERROR ${v.error}`); continue; }
    const w = v.computed.wrap;
    const c = v.computed.category;
    console.log(
      `${k}: wrap.bg-size=${w?.backgroundSize}  wrap.w=${Math.round(w?.rect.width)}  ` +
      `cat.bg-pos=${c?.backgroundPosition}  cat.bg-size=${c?.backgroundSize}  ` +
      `cat.count=${v.computed.categoryCount}`
    );
  }
})();
