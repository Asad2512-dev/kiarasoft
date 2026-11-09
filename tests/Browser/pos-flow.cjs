// End-to-end POS flow in a real browser: search, add, adjust qty, pay, verify invoice + receipt.
const { chromium } = require('playwright');
const BASE = process.argv[2] || 'http://localhost:8000';
(async () => {
  const b = await chromium.launch(); const p = await (await b.newContext({ viewport: { width: 1366, height: 860 } })).newPage();
  const errors = []; p.on('pageerror', e => errors.push(e.message));
  await p.goto(BASE + '/login'); await p.fill('#email', 'cashier@kirasoft.test'); await p.fill('#password', process.env.KIRASOFT_DEMO_PASSWORD || 'Demo@12345');
  await Promise.all([p.waitForNavigation(), p.click('button[type=submit]')]);
  await p.waitForSelector('button.pos-product:not([disabled])');
  await p.fill('input[aria-label="Search products"]', 'MAIDA-L'); await p.keyboard.press('Enter');          // barcode/SKU entry
  await p.fill('input[aria-label="Search products"]', 'Glucose'); await p.keyboard.press('Enter');
  await p.locator('.cart-line', { hasText: 'Glucose' }).locator('button[aria-label="Increase quantity"]').click();
  const q = p.locator('input[aria-label="Quantity of Maida (loose)"]'); await q.fill('1.5'); await q.press('Enter');
  const total = await p.locator('.grand span').nth(1).innerText();
  await p.keyboard.press('F9'); await p.waitForSelector('#tendered');
  await p.fill('#tendered', '2000'); if (process.env.SHOT_DIR) await p.screenshot({ path: process.env.SHOT_DIR + '/pos-pay.png' });
  await p.keyboard.press('Enter');
  await p.waitForSelector('text=Sale completed', { timeout: 10000 });
  const inv = await p.locator('.success-check ~ .mono').innerText();
  if (process.env.SHOT_DIR) await p.screenshot({ path: process.env.SHOT_DIR + '/pos-done.png' });
  const r = await p.goto(BASE + '/sales'); const listed = (await p.content()).includes(inv);
  console.log(JSON.stringify({ total, invoice: inv, listedInSales: listed, errors }));
  await b.close(); process.exit(listed && !errors.length && /^INV-G11-\d{8}-\d{4}$/.test(inv) ? 0 : 1);
})();
