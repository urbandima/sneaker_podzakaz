#!/usr/bin/env node
/**
 * Admin Panel Exhaustive UI Audit — single-context approach.
 * Uses one persistent browser context so PHP session stays alive.
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = 'http://localhost:8080';
const USER = 'testadmin';
const PASS = 'Admin!2345';
const OUT  = path.join(__dirname, '../audit-results');
const SS   = path.join(OUT, 'screenshots');
[OUT, SS].forEach(d => fs.mkdirSync(d, { recursive: true }));

const PAGES = [
  { url: '/admin',                      label: 'Dashboard' },
  { url: '/admin/product',              label: 'Products list' },
  { url: '/admin/product/create',       label: 'Product create' },
  { url: '/admin/catalog',              label: 'Catalog' },
  { url: '/admin/characteristic',       label: 'Characteristics' },
  { url: '/admin/characteristic/create', label: 'Characteristic create' },
  { url: '/admin/product-tag',          label: 'Product tags' },
  { url: '/admin/order',                label: 'Orders list' },
  { url: '/admin/order/create',         label: 'Order create' },
  { url: '/admin/customer',             label: 'Customers list' },
  { url: '/admin/shipping',             label: 'Shipping orders' },
  { url: '/admin/shipping/dispatch',    label: 'Shipping dispatch' },
  { url: '/admin/return',               label: 'Returns' },
  { url: '/admin/coupon',               label: 'Coupons list' },
  { url: '/admin/coupon/create',        label: 'Coupon create' },
  { url: '/admin/coupon/statistics',    label: 'Coupon statistics' },
  { url: '/admin/tariff',               label: 'Tariffs' },
  { url: '/admin/analytics',            label: 'Analytics' },
  { url: '/admin/analytics/rfm',        label: 'Analytics RFM' },
  { url: '/admin/marketing',            label: 'Marketing' },
  { url: '/admin/poizon',               label: 'Poizon import' },
  { url: '/admin/poizon/run',           label: 'Poizon run' },
  { url: '/admin/import',               label: 'Import dashboard' },
  { url: '/admin/import/upload',        label: 'Import upload' },
  { url: '/admin/plugin',               label: 'Plugins' },
  { url: '/admin/plugin/amocrm',        label: 'Plugin AmoCRM' },
  { url: '/admin/plugin/lamoda',        label: 'Plugin Lamoda' },
  { url: '/admin/plugin/moysklad',      label: 'Plugin MoySklad' },
  { url: '/admin/plugin/telegram',      label: 'Plugin Telegram' },
  { url: '/admin/settings',             label: 'Settings' },
  { url: '/admin/settings/shipping',    label: 'Settings shipping' },
  { url: '/admin/settings/payment',     label: 'Settings payment' },
  { url: '/admin/settings/statuses',    label: 'Settings statuses' },
  { url: '/admin/user',                 label: 'Users list' },
  { url: '/admin/user/create',          label: 'User create' },
  { url: '/admin/activity-log',         label: 'Activity log' },
  { url: '/admin/review',               label: 'Reviews' },
  { url: '/admin/finance/payments',     label: 'Finance payments' },
  { url: '/admin/finance/expenses',     label: 'Finance expenses' },
  { url: '/admin/finance/pnl',          label: 'Finance P&L' },
  { url: '/admin/finance/margin',       label: 'Finance margin' },
  { url: '/admin/procurement',          label: 'Procurement' },
  { url: '/admin/dev-tools',            label: 'Dev tools' },
  { url: '/admin/moysklad',             label: 'MoySklad' },
];

const XSS = '<script>window.__XSS__=1</script>';

const findings = [];
let bugId = 1;
const pageStats = {};

function bug(sev, cat, page, el, exp, act, detail = '') {
  const id = `ADM-${String(bugId++).padStart(3, '0')}`;
  const icons = { critical: '🔴', high: '🟠', medium: '🟡', low: '🔵' };
  findings.push({ id, sev, cat, page, el: el.slice(0,80), exp: exp.slice(0,60), act: act.slice(0,100), detail, fixed: false });
  console.log(`  ${icons[sev] || '⚪'} [${id}] ${sev.toUpperCase()} | ${cat} | ${el.slice(0,50)} → ${act.slice(0,80)}`);
  return id;
}

async function shot(pg, name) {
  const p = path.join(SS, `${name.replace(/[^a-z0-9_-]/gi,'_')}.png`);
  await pg.screenshot({ path: p }).catch(() => {});
  return p;
}

// ─── LOGIN ────────────────────────────────────────────────────────────────────
async function login(pg) {
  await pg.goto(`${BASE}/admin/login`, { waitUntil: 'networkidle', timeout: 20000 });
  await pg.fill('input[name="LoginForm[username]"]', USER);
  await pg.fill('input[name="LoginForm[password]"]', PASS);
  await pg.click('button[type="submit"]');
  await pg.waitForURL(/\/admin(?!\/login)/, { timeout: 15000 });
  // Verify we're actually in admin
  const url = pg.url();
  if (url.includes('/login')) throw new Error('Login failed — still on login page');
  console.log(`✅ Logged in → ${url}`);
}

// ─── AUDIT ONE PAGE ───────────────────────────────────────────────────────────
async function auditPage(pg, info) {
  const consoleErrors = [];
  const networkErrors = [];

  const onConsole = msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); };
  const onResponse = r => { if (r.status() >= 500) networkErrors.push(`${r.status()} ${r.url().split('?')[0]}`); };
  pg.on('console', onConsole);
  pg.on('response', onResponse);

  const fullUrl = `${BASE}${info.url}`;
  let counts = { buttons: 0, links: 0, forms: 0, inputs: 0, modals: 0, tables: 0 };

  try {
    const resp = await pg.goto(fullUrl, { waitUntil: 'domcontentloaded', timeout: 25000 });
    await pg.waitForTimeout(800);

    const httpStatus = resp?.status() ?? 0;
    if (httpStatus >= 400) {
      const sp = await shot(pg, `http${httpStatus}_${info.url}`);
      bug('critical','functional', info.url, 'Page load', '2xx', `HTTP ${httpStatus}`, sp);
      pg.off('console', onConsole); pg.off('response', onResponse);
      return counts;
    }

    // Check for PHP exception on page
    const title = await pg.title();
    if (/exception|error/i.test(title)) {
      const sp = await shot(pg, `exception_${info.url}`);
      bug('critical','functional', info.url, 'Page render', 'Clean', `Title: "${title}"`, sp);
    }

    // Check we didn't land on login page (session lost)
    if (pg.url().includes('/login')) {
      bug('critical','functional', info.url, 'Auth', 'Authenticated page', 'Redirected to login');
      pg.off('console', onConsole); pg.off('response', onResponse);
      return counts;
    }

    // Count elements
    counts = await pg.evaluate(() => ({
      buttons: document.querySelectorAll('button:not([disabled]), input[type="submit"]').length,
      links:   document.querySelectorAll('a[href]:not([href="#"]):not([href^="javascript"])').length,
      forms:   document.querySelectorAll('form').length,
      inputs:  document.querySelectorAll('input:not([type="hidden"]), select, textarea').length,
      modals:  document.querySelectorAll('.modal').length,
      tables:  document.querySelectorAll('table').length,
    }));

    // ── CSRF check ────────────────────────────────────────────────────────────
    const csrfIssues = await pg.evaluate(() => {
      const issues = [];
      document.querySelectorAll('form').forEach(f => {
        const method = (f.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'post') return;
        const csrf = f.querySelector('input[name="_csrf"]');
        if (!csrf) issues.push(f.action || f.id || '(unnamed form)');
      });
      return issues;
    });
    csrfIssues.forEach(a => {
      if (!/logout/i.test(a))
        bug('high','security', info.url, `Form(${a.slice(-30)})`, 'CSRF token', 'Missing _csrf input');
    });

    // ── Mixed button systems ──────────────────────────────────────────────────
    const btnMix = await pg.evaluate(() => {
      const bs = document.querySelectorAll('.btn-primary, .btn-danger, .btn-success, .btn-warning, .btn-secondary').length;
      const adm = document.querySelectorAll('.admin-btn-primary, .admin-btn-danger, .admin-btn-success').length;
      return { bs, adm };
    });
    if (btnMix.bs > 0 && btnMix.adm > 0) {
      bug('low','consistency', info.url, 'Button classes', 'Single design system',
          `Mixed: ${btnMix.bs} Bootstrap + ${btnMix.adm} admin-btn`);
    }

    // ── Delete without confirm ────────────────────────────────────────────────
    const noConfirm = await pg.evaluate(() => {
      const found = [];
      document.querySelectorAll('a[href], button').forEach(el => {
        const txt = (el.innerText || el.title || '').toLowerCase();
        const href = (el.getAttribute('href') || '').toLowerCase();
        if (/удал|delete|remove/.test(txt + href)) {
          const confirm = el.getAttribute('data-confirm') ||
            (el.getAttribute('onclick') || '').includes('confirm');
          if (!confirm) found.push((el.innerText || el.title || href).trim().slice(0,30));
        }
      });
      return [...new Set(found)].slice(0,5);
    });
    noConfirm.forEach(t => bug('medium','ux', info.url, `Delete "${t}"`, 'JS confirm', 'No data-confirm/onclick confirm'));

    // ── Broken images ─────────────────────────────────────────────────────────
    await pg.waitForTimeout(300);
    const brokenImgs = await pg.evaluate(() => {
      const broken = [];
      document.querySelectorAll('img[src]').forEach(img => {
        if (!img.naturalWidth && img.complete && img.src && !img.src.startsWith('data:'))
          broken.push(img.src.split('/').pop().slice(0,40));
      });
      return broken;
    });
    if (brokenImgs.length > 0) {
      bug('low','style', info.url, 'Images', 'All load', `${brokenImgs.length} broken: ${brokenImgs.slice(0,3).join(', ')}`);
    }

    // ── Console errors ────────────────────────────────────────────────────────
    const filteredConsole = [...new Set(consoleErrors)]
      .filter(e => !/favicon|net::ERR_|manifest|404.*\.map/.test(e));
    filteredConsole.slice(0,3).forEach(e =>
      bug('medium','functional', info.url, 'JS console error', 'No errors', e.slice(0,120))
    );

    // ── Network 5xx ───────────────────────────────────────────────────────────
    const filteredNet = [...new Set(networkErrors)];
    filteredNet.slice(0,3).forEach(e =>
      bug('high','functional', info.url, 'Network 5xx', 'No 5xx', e.slice(0,120))
    );

    // ── Modal triggers ────────────────────────────────────────────────────────
    const modalTriggers = await pg.locator('[data-bs-toggle="modal"], [data-toggle="modal"]').all();
    for (const trigger of modalTriggers.slice(0, 2)) {
      const trigText = await trigger.innerText().catch(() => '?');
      // Skip triggers that navigate away or logout
      const targetId = await trigger.getAttribute('data-bs-target') || await trigger.getAttribute('data-target') || '';
      try {
        await trigger.click({ timeout: 3000 });
        await pg.waitForTimeout(500);
        const isOpen = await pg.locator('.modal.show, .modal[aria-modal="true"]').count() > 0;
        if (!isOpen) {
          bug('medium','functional', info.url, `Modal trigger "${trigText.slice(0,30)}"`, 'Modal opens', 'Modal not visible after click');
        } else {
          // Test ESC
          await pg.keyboard.press('Escape');
          await pg.waitForTimeout(400);
          const stillOpen = await pg.locator('.modal.show').count() > 0;
          if (stillOpen) {
            bug('medium','ux', info.url, `Modal "${trigText.slice(0,30)}"`, 'Closes on ESC', 'Still open after ESC');
            // Force close by clicking backdrop or close btn
            await pg.locator('.modal.show .btn-close, .modal.show [data-bs-dismiss], .modal.show .close').first().click({ timeout: 1500 }).catch(() => {});
            await pg.waitForTimeout(300);
          }
        }
      } catch {}
    }

    // ── Inline style count ────────────────────────────────────────────────────
    const inlineStyles = await pg.evaluate(() => document.querySelectorAll('[style]').length);
    if (inlineStyles > 30) {
      bug('low','style', info.url, 'Inline styles', '<30 inline', `${inlineStyles} elements with inline style`);
    }

    // ── Form validation — empty submit ────────────────────────────────────────
    // Only on create/edit pages
    if (/\/(create|update|edit)/.test(info.url)) {
      const submitBtn = pg.locator('button[type="submit"], input[type="submit"]').first();
      if (await submitBtn.count() > 0) {
        // Don't actually submit — just check for required attributes
        const hasHtmlRequired = await pg.evaluate(() =>
          document.querySelectorAll('input[required], select[required], textarea[required]').length
        );
        const hasYiiRequired = await pg.evaluate(() =>
          document.querySelectorAll('.required, [class*="required"]').length
        );
        if (hasHtmlRequired === 0 && hasYiiRequired === 0) {
          bug('low','ux', info.url, 'Required fields', 'required attr or .required class', 'No required indicators found');
        }
      }
    }

    await shot(pg, `page_${info.url}`);

  } catch (err) {
    const sp = await shot(pg, `crash_${info.url}`);
    bug('critical','functional', info.url, 'Page audit', 'Completes', err.message.slice(0,100), sp);
  }

  pg.off('console', onConsole);
  pg.off('response', onResponse);
  return counts;
}

// ─── XSS FORM TEST ────────────────────────────────────────────────────────────
async function testXss(pg, url, fieldName) {
  await pg.goto(`${BASE}${url}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
  const inp = pg.locator(`[name="${fieldName}"]`);
  if (await inp.count() === 0) return;

  await inp.fill(XSS);
  const btn = pg.locator('button[type="submit"]').first();
  if (await btn.count() === 0) return;

  await btn.click();
  await pg.waitForLoadState('domcontentloaded', { timeout: 10000 }).catch(() => {});
  await pg.waitForTimeout(500);

  const executed = await pg.evaluate(() => typeof window.__XSS__ !== 'undefined').catch(() => false);
  if (executed) {
    const sp = await shot(pg, `XSS_EXECUTED_${url}`);
    bug('critical','security', url, `XSS in ${fieldName}`, 'Escaped', 'XSS EXECUTED!', sp);
  } else {
    console.log(`  ✅ XSS escaped in ${url}[${fieldName}]`);
  }
}

// ─── RESPONSIVE ───────────────────────────────────────────────────────────────
async function checkResponsive(browser, cookiesFile) {
  const vps = [
    { w: 1440, h: 900 }, { w: 1280, h: 800 },
    { w: 1024, h: 768 }, { w: 768,  h: 1024 },
  ];
  const urls = ['/admin', '/admin/product', '/admin/order', '/admin/settings'];

  for (const vp of vps) {
    const ctx = await browser.newContext({ storageState: cookiesFile, viewport: { width: vp.w, height: vp.h }, ignoreHTTPSErrors: true });
    const pg = await ctx.newPage();
    for (const url of urls) {
      try {
        await pg.goto(`${BASE}${url}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
        await pg.waitForTimeout(400);
        const overflow = await pg.evaluate(() => document.body.scrollWidth > window.innerWidth + 20);
        if (overflow) {
          const sp = await shot(pg, `resp_${vp.w}_${url}`);
          bug('medium','style', url, `@${vp.w}px`, 'No overflow', 'Horizontal scroll', sp);
        } else {
          console.log(`  ✅ ${vp.w}px ${url}`);
        }
      } catch {}
    }
    await ctx.close();
  }
}

// ─── MAIN ─────────────────────────────────────────────────────────────────────
(async () => {
  console.log('🚀 Admin Panel Exhaustive UI Audit\n');
  const t0 = Date.now();

  const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-dev-shm-usage'] });
  const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1440, height: 900 } });
  const pg = await ctx.newPage();

  // Login
  await login(pg);

  // Verify session works
  const testResp = await pg.goto(`${BASE}/admin`, { waitUntil: 'domcontentloaded', timeout: 15000 });
  if (testResp.url().includes('/login')) {
    console.error('❌ Session not working after login!');
    process.exit(1);
  }

  // Count elements on dashboard to verify we're really in
  const dashCount = await pg.evaluate(() => ({
    buttons: document.querySelectorAll('button').length,
    links:   document.querySelectorAll('a').length,
  }));
  console.log(`✅ Dashboard OK — ${dashCount.buttons} buttons, ${dashCount.links} links (confirms auth working)`);

  console.log('\n═══ PAGE AUDITS ═══');
  const pageSummary = [];

  for (const info of PAGES) {
    console.log(`\n📄 ${info.label} → ${info.url}`);
    const counts = await auditPage(pg, info);
    pageSummary.push({ ...info, ...counts, bugs: findings.filter(f => f.page === info.url).length });
    console.log(`  Btns:${counts.buttons} Links:${counts.links} Forms:${counts.forms} Inputs:${counts.inputs} Modals:${counts.modals} Tables:${counts.tables}`);
  }

  // ── XSS tests ──────────────────────────────────────────────────────────────
  console.log('\n═══ XSS TESTS ═══');
  await testXss(pg, '/admin/coupon/create', 'Coupon[code]');
  await testXss(pg, '/admin/user/create', 'User[username]');
  await testXss(pg, '/admin/characteristic/create', 'Characteristic[name]');

  // ── Save cookies for responsive check ─────────────────────────────────────
  const cookiesFile = path.join(OUT, 'cookies.json');
  await ctx.storageState({ path: cookiesFile });
  await ctx.close();

  // ── Responsive check (separate contexts with saved cookies) ───────────────
  console.log('\n═══ RESPONSIVE CHECK ═══');
  await checkResponsive(browser, cookiesFile);

  await browser.close();

  // ── Report ─────────────────────────────────────────────────────────────────
  const duration = ((Date.now() - t0) / 1000).toFixed(1);
  const bySev = { critical: [], high: [], medium: [], low: [] };
  const byCat = {};
  findings.forEach(f => {
    bySev[f.sev]?.push(f);
    (byCat[f.cat] = byCat[f.cat] || []).push(f);
  });

  fs.writeFileSync(path.join(OUT, 'findings.json'), JSON.stringify(findings, null, 2));

  // Markdown report
  let md = `# Admin Panel UI Audit — Exhaustive Click-Through Report\n\n`;
  md += `**Generated:** ${new Date().toISOString().slice(0,16).replace('T',' ')}\n`;
  md += `**Duration:** ${duration}s | **Pages:** ${PAGES.length} | **Total findings:** ${findings.length}\n\n`;

  md += `## Summary\n\n| Severity | Count |\n|---|---|\n`;
  ['critical','high','medium','low'].forEach(s => {
    const icon = { critical:'🔴', high:'🟠', medium:'🟡', low:'🔵' }[s];
    md += `| ${icon} ${s} | ${bySev[s].length} |\n`;
  });
  md += '\n';

  md += `## Admin Panel Route Map\n\n| URL | Label | Elements | Bugs |\n|---|---|---|---|\n`;
  pageSummary.forEach(p => {
    const icon = p.bugs === 0 ? '✅' :
      findings.filter(f => f.page === p.url && f.sev === 'critical').length ? '🔴' :
      findings.filter(f => f.page === p.url && f.sev === 'high').length ? '🟠' :
      findings.filter(f => f.page === p.url && f.sev === 'medium').length ? '🟡' : '🔵';
    md += `| ${p.url} | ${p.label} | btn:${p.buttons} lnk:${p.links} form:${p.forms} inp:${p.inputs} modal:${p.modals} tbl:${p.tables} | ${icon} ${p.bugs} |\n`;
  });
  md += '\n';

  md += `## Findings\n\n`;
  Object.entries(byCat).forEach(([cat, arr]) => {
    md += `### ${cat.toUpperCase()} (${arr.length} issues)\n\n`;
    md += `| ID | Severity | Page | Element | Expected | Actual |\n|---|---|---|---|---|---|\n`;
    arr.forEach(f => {
      md += `| ${f.id} | ${f.sev} | ${f.page} | ${f.el} | ${f.exp} | ${f.act} |\n`;
    });
    md += '\n';
  });

  fs.writeFileSync(path.join(OUT, 'audit-report.md'), md);

  console.log('\n\n═══════════════════════════════════════════');
  console.log(`        AUDIT COMPLETE (${duration}s)`);
  console.log('═══════════════════════════════════════════');
  console.log(`Pages: ${PAGES.length} | Findings: ${findings.length}`);
  console.log(`🔴 Critical: ${bySev.critical.length}`);
  console.log(`🟠 High:     ${bySev.high.length}`);
  console.log(`🟡 Medium:   ${bySev.medium.length}`);
  console.log(`🔵 Low:      ${bySev.low.length}`);
  console.log(`\nReport → ${OUT}/audit-report.md`);
})();
