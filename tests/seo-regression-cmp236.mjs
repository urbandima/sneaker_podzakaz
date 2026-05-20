/**
 * SEO Regression Smoke — CMP-236
 * Covers CMP-223–233: meta description, OG tags, JSON-LD Product/BreadcrumbList, title dedup
 * Viewport: 390×844 (mobile-first)
 */

import { chromium } from '../node_modules/playwright/index.mjs';
import * as fs from 'fs';
import * as path from 'path';

const BASE_URL = 'http://127.0.0.1:8765';
const PRODUCT_PATH = '/catalog/product/adidas-forum-low-white-blue';
const CATALOG_PATH = '/catalog';
const HOME_PATH = '/';
const VIEWPORT = { width: 390, height: 844 };
const SCREENSHOT_DIR = '/Users/user/Downloads/_Организовано_2026-04-21/04_Веб_проекты_код/Сайты_магазины/splitwise/qa-screenshots-cmp236';

fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });

let passed = 0;
let failed = 0;
const findings = [];

function assert(label, condition, detail = '') {
  if (condition) {
    passed++;
    console.log(`  ✅ PASS  ${label}`);
  } else {
    failed++;
    console.error(`  ❌ FAIL  ${label}${detail ? ' — ' + detail : ''}`);
    findings.push({ label, detail });
  }
}

function assertEq(label, actual, expected) {
  const ok = actual === expected;
  assert(label, ok, ok ? '' : `actual="${actual}" expected="${expected}"`);
}

async function screenshot(page, name) {
  const filePath = path.join(SCREENSHOT_DIR, `${name}.png`);
  await page.screenshot({ path: filePath, fullPage: false });
  console.log(`  📸 ${filePath}`);
  return filePath;
}

/** Extract all <meta>, <title>, <link rel=canonical>, JSON-LD scripts from <head> */
async function extractHead(page) {
  return await page.evaluate(() => {
    const head = document.head;
    const title = head.querySelector('title')?.textContent ?? '';
    const allTitles = Array.from(head.querySelectorAll('title')).map(t => t.textContent);

    const metas = Array.from(head.querySelectorAll('meta')).map(m => ({
      name: m.getAttribute('name') ?? '',
      property: m.getAttribute('property') ?? '',
      content: m.getAttribute('content') ?? '',
    }));

    const canonical = head.querySelector('link[rel="canonical"]')?.getAttribute('href') ?? '';

    const jsonLDs = Array.from(head.querySelectorAll('script[type="application/ld+json"]')).map(s => {
      try { return JSON.parse(s.textContent); } catch { return null; }
    }).filter(Boolean);

    return { title, allTitles, metas, canonical, jsonLDs };
  });
}

async function runProductTests(page) {
  console.log('\n=== Product card: ' + PRODUCT_PATH + ' ===');
  await page.goto(BASE_URL + PRODUCT_PATH, { waitUntil: 'domcontentloaded', timeout: 15000 });
  await screenshot(page, '01-product-card');

  const { title, allTitles, metas, jsonLDs } = await extractHead(page);

  // --- CMP-227: title format "Brand Product купить — СНИКЕРХЭД" (per spec in CMP-227) ---
  console.log('\n-- Title (CMP-227/233) --');
  console.log(`  title: "${title}"`);
  assert('No duplicate <title> tags', allTitles.length === 1,
    `count=${allTitles.length}`);
  assert('Title contains "купить"', title.includes('купить'),
    `title="${title}"`);
  assert('Title contains "СНИКЕРХЭД"', title.includes('СНИКЕРХЭД'),
    `title="${title}"`);
  assert('Title ≤60 chars', title.length <= 60,
    `length=${title.length}, title="${title}"`);
  assert('Title contains brand (non-empty text before "купить")', title.indexOf('купить') > 0,
    `title="${title}"`);

  // --- CMP-223: meta description ---
  console.log('\n-- Meta description (CMP-223) --');
  const metaDescriptions = metas.filter(m => m.name === 'description');
  assert('Exactly one meta description', metaDescriptions.length === 1,
    `count=${metaDescriptions.length}, values=${JSON.stringify(metaDescriptions.map(m => m.content))}`);
  if (metaDescriptions.length > 0) {
    assert('Meta description is non-empty', metaDescriptions[0].content.length > 0,
      `content="${metaDescriptions[0].content}"`);
    console.log(`  meta description: "${metaDescriptions[0].content}"`);
  }

  // --- CMP-224: OG tags ---
  console.log('\n-- Open Graph tags (CMP-224/233) --');
  const ogMetas = {};
  for (const m of metas) {
    if (m.property.startsWith('og:')) {
      if (!ogMetas[m.property]) ogMetas[m.property] = [];
      ogMetas[m.property].push(m.content);
    }
  }

  const requiredOG = ['og:title', 'og:description', 'og:image', 'og:type'];
  for (const prop of requiredOG) {
    const vals = ogMetas[prop] ?? [];
    assert(`${prop} present`, vals.length >= 1, `values=${JSON.stringify(vals)}`);
    assert(`${prop} no duplicate`, vals.length <= 1,
      `count=${vals.length}, values=${JSON.stringify(vals)}`);
    if (vals.length > 0) console.log(`  ${prop}: "${vals[0]}"`);
  }
  assert('og:type = product', (ogMetas['og:type'] ?? [])[0] === 'product',
    `actual="${(ogMetas['og:type'] ?? [])[0]}"`);

  // --- CMP-225: JSON-LD Product schema ---
  console.log('\n-- JSON-LD Product schema (CMP-225) --');
  const productSchemas = jsonLDs.filter(s => s['@type'] === 'Product');
  assert('Exactly one JSON-LD Product schema', productSchemas.length === 1,
    `count=${productSchemas.length}`);

  if (productSchemas.length > 0) {
    const ps = productSchemas[0];
    assert('Product schema has @context = schema.org', ps['@context']?.includes('schema.org') ?? false,
      `@context="${ps['@context']}"`);
    assert('Product schema has name', !!ps.name, `name="${ps.name}"`);
    assert('Product schema has image', !!ps.image, `image=${JSON.stringify(ps.image)}`);
    assert('Product schema has description', !!ps.description, `description="${ps.description}"`);
    assert('Product schema has brand', !!ps.brand, `brand=${JSON.stringify(ps.brand)}`);
    console.log(`  Product name: "${ps.name}"`);
    console.log(`  Product brand: ${JSON.stringify(ps.brand)}`);
    if (ps.offers) {
      assert('Product offers has price', ps.offers.price !== undefined, `offers=${JSON.stringify(ps.offers)}`);
      console.log(`  Product price: ${ps.offers.price} ${ps.offers.priceCurrency}`);
    }
  }

  // --- CMP-226: BreadcrumbList schema ---
  console.log('\n-- BreadcrumbList schema (CMP-226) --');
  const breadcrumbSchemas = jsonLDs.filter(s => s['@type'] === 'BreadcrumbList');
  assert('Exactly one BreadcrumbList schema', breadcrumbSchemas.length === 1,
    `count=${breadcrumbSchemas.length}`);

  if (breadcrumbSchemas.length > 0) {
    const bs = breadcrumbSchemas[0];
    assert('BreadcrumbList has @context = schema.org', bs['@context']?.includes('schema.org') ?? false);
    const items = bs.itemListElement ?? [];
    assert('BreadcrumbList has ≥2 items', items.length >= 2, `count=${items.length}`);
    if (items.length > 0) {
      const last = items[items.length - 1];
      assert('Last breadcrumb has name', !!(last.name || last.item?.name), `last=${JSON.stringify(last)}`);
      console.log(`  Breadcrumbs: ${items.map(i => i.name ?? i.item?.name ?? JSON.stringify(i)).join(' > ')}`);
    }
  }

  // --- CMP-233: No duplicate tags overall check ---
  console.log('\n-- Duplication audit (CMP-233) --');
  const allMetaDescCount = metas.filter(m => m.name === 'description').length;
  assert('No duplicate meta description', allMetaDescCount <= 1, `count=${allMetaDescCount}`);

  const totalProductSchemas = jsonLDs.filter(s => s['@type'] === 'Product').length;
  assert('No duplicate Product JSON-LD', totalProductSchemas <= 1, `count=${totalProductSchemas}`);

  const totalBCSchemas = jsonLDs.filter(s => s['@type'] === 'BreadcrumbList').length;
  assert('No duplicate BreadcrumbList JSON-LD', totalBCSchemas <= 1, `count=${totalBCSchemas}`);

  await screenshot(page, '02-product-head-inspect');
  return { title, metaDescriptions, ogMetas, productSchemas, breadcrumbSchemas };
}

async function runCatalogTests(page) {
  console.log('\n=== Catalog: ' + CATALOG_PATH + ' ===');
  await page.goto(BASE_URL + CATALOG_PATH, { waitUntil: 'domcontentloaded', timeout: 15000 });
  await screenshot(page, '03-catalog');

  const { title, allTitles, metas, jsonLDs } = await extractHead(page);
  console.log(`  title: "${title}"`);

  assert('Catalog: has <title>', title.length > 0);
  assert('Catalog: no duplicate <title>', allTitles.length === 1, `count=${allTitles.length}`);

  const metaDescs = metas.filter(m => m.name === 'description');
  assert('Catalog: ≤1 meta description', metaDescs.length <= 1, `count=${metaDescs.length}`);
  if (metaDescs.length > 0) console.log(`  meta description: "${metaDescs[0].content}"`);

  const ogDupes = {};
  for (const m of metas) {
    if (m.property.startsWith('og:')) {
      ogDupes[m.property] = (ogDupes[m.property] ?? 0) + 1;
    }
  }
  for (const [prop, count] of Object.entries(ogDupes)) {
    assert(`Catalog: no duplicate ${prop}`, count <= 1, `count=${count}`);
  }

  const productSchemasCount = jsonLDs.filter(s => s['@type'] === 'Product').length;
  assert('Catalog: no Product JSON-LD (listing page, not product)', productSchemasCount === 0,
    `count=${productSchemasCount} (Product schema should only be on product pages)`);
}

async function runHomepageTests(page) {
  console.log('\n=== Homepage: ' + HOME_PATH + ' ===');
  await page.goto(BASE_URL + HOME_PATH, { waitUntil: 'domcontentloaded', timeout: 15000 });
  await screenshot(page, '04-homepage');

  const { title, allTitles, metas, jsonLDs } = await extractHead(page);
  console.log(`  title: "${title}"`);

  assert('Homepage: has <title>', title.length > 0);
  assert('Homepage: no duplicate <title>', allTitles.length === 1, `count=${allTitles.length}`);

  const metaDescs = metas.filter(m => m.name === 'description');
  assert('Homepage: ≤1 meta description', metaDescs.length <= 1, `count=${metaDescs.length}`);
  if (metaDescs.length > 0) console.log(`  meta description: "${metaDescs[0].content}"`);

  const ogDupes = {};
  for (const m of metas) {
    if (m.property.startsWith('og:')) {
      ogDupes[m.property] = (ogDupes[m.property] ?? 0) + 1;
    }
  }
  for (const [prop, count] of Object.entries(ogDupes)) {
    assert(`Homepage: no duplicate ${prop}`, count <= 1, `count=${count}`);
  }
}

async function main() {
  console.log('CMP-236 SEO Regression Smoke');
  console.log('============================');

  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext({ viewport: VIEWPORT });
  const page = await ctx.newPage();

  try {
    const productData = await runProductTests(page);
    await runCatalogTests(page);
    await runHomepageTests(page);
  } finally {
    await browser.close();
  }

  console.log('\n============================');
  console.log(`РЕЗУЛЬТАТ: ${passed} pass, ${failed} fail`);
  if (findings.length > 0) {
    console.log('\nПроваленные проверки:');
    for (const f of findings) {
      console.log(`  • ${f.label}${f.detail ? ' — ' + f.detail : ''}`);
    }
  }

  // Write JSON report
  const report = {
    timestamp: new Date().toISOString(),
    issue: 'CMP-236',
    viewport: VIEWPORT,
    pages: [BASE_URL + '/catalog/product/adidas-forum-low-white-blue', BASE_URL + '/catalog', BASE_URL + '/'],
    passed,
    failed,
    verdict: failed === 0 ? 'PASS' : 'FAIL',
    findings,
    screenshots: SCREENSHOT_DIR,
  };
  const reportPath = '/Users/user/Downloads/_Организовано_2026-04-21/04_Веб_проекты_код/Сайты_магазины/splitwise/qa-report-cmp236.json';
  fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));
  console.log(`\nОтчёт: ${reportPath}`);

  process.exit(failed === 0 ? 0 : 1);
}

main().catch(err => {
  console.error('ОШИБКА:', err);
  process.exit(1);
});
