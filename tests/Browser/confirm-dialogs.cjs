// Verifies confirmation dialogs: Cancel must block the action, Confirm must perform it.
const { chromium } = require('playwright');
const BASE = process.argv[2] || 'http://localhost:8000';
const PASS = process.env.KIRASOFT_DEMO_PASSWORD || 'Demo@12345';
const results = [];
const check = (name, ok) => { results.push({ name, ok }); console.log((ok ? 'PASS ' : 'FAIL ') + name); };
async function login(p, who) {
  await p.goto(BASE + '/login'); await p.fill('#email', who + '@kirasoft.test'); await p.fill('#password', PASS);
  await Promise.all([p.waitForNavigation(), p.click('button[type=submit]')]);
}
const dialog = (p) => p.locator('.ks-confirm.show');
async function answer(p, ok) { await dialog(p).waitFor(); const t = await dialog(p).locator('#ksConfirmTitle').innerText(); await dialog(p).locator(ok ? '[data-ks-ok]' : '[data-ks-cancel]').click(); return t; }
(async () => {
  const b = await chromium.launch(); const p = await (await b.newContext({ viewport: { width: 1366, height: 860 } })).newPage();
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  await login(p, 'owner');

  // Sign out: cancel keeps session, confirm logs out
  await p.click('button[aria-label="Account menu"]'); await p.click('text=Sign out');
  let t = await answer(p, false); await p.waitForTimeout(400);
  check(`sign-out dialog shown ("${t}") and cancel keeps user signed in`, t === 'Sign out?' && p.url().includes('/dashboard'));
  await p.click('button[aria-label="Account menu"]'); await p.click('text=Sign out'); await answer(p, true);
  await p.waitForURL(/login/); check('sign-out confirm logs out', p.url().includes('/login'));

  await login(p, 'owner');
  // Product update
  await p.goto(BASE + '/products/1/edit'); await p.fill('#price', '561'); await p.click('button:has-text("Save changes")');
  t = await answer(p, false); await p.waitForTimeout(400);
  check(`product update dialog ("${t}") cancel stays on form`, t === 'Update product?' && p.url().includes('/edit'));
  await p.click('button:has-text("Save changes")'); await answer(p, true); await p.waitForURL(/\/products\/1$/);
  check('product update confirm saves', (await p.content()).includes('561.00'));

  // Archive (DELETE)
  await p.click('button:has-text("Archive")'); t = await answer(p, false); await p.waitForTimeout(400);
  check(`archive dialog ("${t}") cancel keeps product`, t === 'Archive product?' && p.url().endsWith('/products/1'));
  await p.click('button:has-text("Archive")'); await answer(p, true); await p.waitForURL(/\/products$/);
  check('archive confirm archives', p.url().endsWith('/products'));
  await p.goto(BASE + '/products?status=archived'); await p.click('button:has-text("Restore")'); t = await answer(p, true); await p.waitForLoadState();
  check(`restore dialog ("${t}") and restore works`, t === 'Restore product?');

  // Category edit inside a modal
  await p.goto(BASE + '/categories'); await p.locator('button:has-text("Edit")').first().click();
  await p.locator('#cname').fill('Rice & Flour Items'); await p.click('.modal.show button:has-text("Save")');
  t = await answer(p, true); await p.waitForLoadState('networkidle');
  check(`category edit dialog ("${t}") confirm saves`, (await p.content()).includes('Rice &amp; Flour Items'));

  // Settings update
  await p.goto(BASE + '/settings'); await p.click('button:has-text("Save settings")'); t = await answer(p, false);
  check(`settings dialog ("${t}")`, t === 'Save shop settings?');

  // Employee status toggle (PATCH)
  await p.goto(BASE + '/employees'); await p.locator('button[aria-label^="More actions"]').first().click();
  await p.locator('.dropdown-menu.show button:has-text("activate")').first().click(); t = await answer(p, false);
  check(`employee status dialog ("${t}")`, t === 'Change status?');

  // POS: remove item and clear cart
  await p.goto(BASE + '/pos'); await p.waitForSelector('button.pos-product:not([disabled])');
  await p.locator('button.pos-product:not([disabled])').first().click();
  await p.click('button[aria-label^="Remove "]'); t = await answer(p, false);
  check(`POS remove-item dialog ("${t}") cancel keeps item`, t === 'Remove item?' && await p.locator('.cart-line').count() === 1);
  await p.click('button:has-text("Clear")'); t = await answer(p, true); await p.waitForTimeout(300);
  check(`POS clear dialog ("${t}") confirm empties cart`, t === 'Clear cart?' && await p.locator('.cart-line').count() === 0);

  check('no JavaScript errors', errs.length === 0);
  await b.close();
  const failed = results.filter(r => !r.ok).length; console.log(`${results.length} checks, ${failed} failed`); process.exit(failed ? 1 : 0);
})();
