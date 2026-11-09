// Browser smoke + responsive test: logs in as each role, visits pages at several widths,
// fails on HTTP >= 500, JS console errors, or horizontal overflow. Usage: node tests/Browser/smoke.cjs [baseUrl] [shotsDir]
const { chromium } = require('playwright');
const BASE = process.argv[2] || 'http://localhost:8000';
const SHOTS = process.argv[3] || null;
const PASS = process.env.KIRASOFT_DEMO_PASSWORD || 'Demo@12345';
const roles = {
  admin: ['/admin', '/admin/shops', '/admin/shops/1', '/admin/shops/create', '/admin/signups', '/admin/users', '/admin/activity', '/admin/backups', '/admin/system', '/profile'],
  owner: ['/dashboard', '/pos', '/sales', '/sales/1', '/sales/1/return', '/returns', '/products', '/products/1', '/products/create', '/products/1/edit', '/categories', '/inventory', '/inventory/movements', '/inventory/adjust', '/inventory/reconcile', '/alerts', '/reports', '/reports?period=last7', '/branches', '/branches/1', '/branches/1/edit', '/branches/create', '/employees', '/employees/create', '/activity', '/settings', '/profile'],
  manager: ['/dashboard', '/pos', '/sales', '/products', '/inventory', '/reports', '/branches', '/employees', '/activity'],
  cashier: ['/pos', '/sales', '/alerts', '/inventory', '/profile'],
};
const widths = (process.env.WIDTHS || '1440,1024,768,390,375,320').split(',').map(Number);
(async () => {
  const browser = await chromium.launch();
  let failures = 0, checks = 0;
  for (const [role, pages] of Object.entries(roles)) {
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();
    const errors = [];
    page.on('console', m => { if (m.type() === 'error') errors.push(m.text()); });
    page.on('pageerror', e => errors.push(e.message));
    await page.goto(`${BASE}/login`);
    await page.fill('#email', `${role}@kirasoft.test`);
    await page.fill('#password', PASS);
    await Promise.all([page.waitForNavigation(), page.click('button[type=submit]')]);
    if (page.url().includes('/login')) { console.log(`FAIL login ${role}`); failures++; continue; }
    for (const path of pages) {
      for (const w of widths) {
        await page.setViewportSize({ width: w, height: w < 500 ? 780 : 900 });
        errors.length = 0;
        const res = await page.goto(BASE + path, { waitUntil: 'networkidle' });
        checks++;
        const status = res.status();
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        const problems = [];
        if (status >= 400) problems.push(`HTTP ${status}`);
        if (errors.length) problems.push('console: ' + errors.join(' | ').slice(0, 300));
        if (overflow > 1) problems.push(`horizontal overflow ${overflow}px`);
        if (problems.length) { failures++; console.log(`FAIL ${role} ${path} @${w}: ${problems.join('; ')}`); }
        if (SHOTS && (w === 1440 || w === 390)) await page.screenshot({ path: `${SHOTS}/${role}${path.replace(/[\/?=&]/g, '_')}_${w}.png`, fullPage: false });
      }
    }
    await ctx.close();
  }
  console.log(`${checks} page checks, ${failures} failures`);
  await browser.close();
  process.exit(failures ? 1 : 0);
})();
