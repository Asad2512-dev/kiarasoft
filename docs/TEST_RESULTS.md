# Test Results

All results below come from commands actually executed on **2026-11-09** in the development container
(Ubuntu 24.04, PHP 8.3.6, MySQL 8.0.46, Laravel 11.57, Chromium via Playwright). Nothing here is assumed.

## Summary

| Suite | Command | Result |
|---|---|---|
| PHPUnit unit + feature (MySQL `kirasoft_test`) | `php artisan test` | **55 passed, 0 failed (329 assertions)** |
| Real multi-process concurrency | included above (`ConcurrencyTest`) | 6 parallel PHP processes, 3 succeeded, 3 rejected, stock 10→1, never negative |
| Backup + restore into separate DB | included above (`PlatformTest`) | restored row counts match source |
| Browser responsive smoke (4 roles × all screens × 320/375/390/768/1024/1440 px) | `node tests/Browser/smoke.cjs` | **306 page checks, 0 failures** (no HTTP errors, no JS errors, no horizontal overflow) |
| Browser end-to-end POS checkout | `node tests/Browser/pos-flow.cjs` | passed: SKU entry, decimal qty 1.5 kg, F9 pay, change, invoice `INV-G11-…` listed in sales |
| Browser confirmation dialogs (cancel blocks / confirm performs) | `node tests/Browser/confirm-dialogs.cjs` | **13 checks, 0 failures** |
| CSRF enforcement (manual, against running server) | `curl -X POST -d "email=a@b.c&password=x" localhost:8000/login` | HTTP **419** (rejected without token) |
| Seed data integrity (manual, tinker) | `InventoryService::reconcile()` after seeding | 0 mismatches, 0 negative stock rows, Σ sale subtotals = Σ line totals |

## Defects found by testing and fixed
| Defect | Found by | Fix |
|---|---|---|
| Model defaults not loaded (`is_active` null) → all seeded checkouts rejected | seeder produced 0 sales | `$attributes` defaults on models |
| Reserved word `lines` in inventory value SQL → 500 | browser smoke | alias renamed `line_count` |
| `withCount` mixed with aggregate under `ONLY_FULL_GROUP_BY` → 500 on Sales | browser smoke | totals built from base query |
| Blade failed to parse inline `@json([...])` chart configs → 500 | browser smoke | `{{ json_encode(...) }}` |
| Bootstrap `!important` display utilities overrode Alpine `x-show` → POS modal visible on load | screenshot review | removed utility classes from x-show elements |
| `callback: undefined` hid chart axis labels | screenshot review | conditional option spread |
| POS horizontal overflow 1077px at 390px | browser smoke | `min-width:0` on grid children |
| Apostrophes broke error page templates → 500 on 403 | AuthorizationTest | text fixed |
| Seeder used mocked clock for "today" → dates drifted a year | dashboard showed Rs 0 | real date captured before mocking |

## Known gaps (not claimed)
- KS-T01…T15 titles are inferred because the thesis PDF was unavailable (see THESIS_REQUIREMENTS.md).
- CSRF is disabled by Laravel inside PHPUnit, so it was verified manually (above), not by an automated test.
- Tested on Linux with MySQL 8 and `php artisan serve`; MAMP/XAMPP steps are documented but were not run here.
- Printing was verified as rendered HTML/print CSS; not on a physical thermal printer.

## Full PHPUnit output (`php artisan test`)
```
   PASS  Tests\Unit\MoneyTest
  ✓ parses and formats without float errors
  ✓ line totals round half up
  ✓ rejects invalid input
   PASS  Tests\Feature\AuthTest
  ✓ valid login redirects by role
  ✓ invalid login is rejected
  ✓ inactive user and suspended shop cannot login
  ✓ deactivated user is logged out on next request
  ✓ login is rate limited
  ✓ session regenerates on login and guests redirected
  ✓ password reset flow
  ✓ unknown email reset gives same response
   PASS  Tests\Feature\AuthorizationTest
  ✓ cashier cannot reach admin modules
  ✓ manager cannot manage branches employees or settings
  ✓ shop admin cannot access platform admin
  ✓ cross shop records are inaccessible
  ✓ cross branch access is denied
  ✓ cashier sees only own sales
   PASS  Tests\Feature\CheckoutTest
  ✓ checkout creates complete sale atomically
  ✓ client prices and totals are ignored
  ✓ insufficient stock rolls back everything
  ✓ decimal quantities and whole unit rule
  ✓ percent and fixed discounts and tax
  ✓ excessive discount and short cash are rejected
  ✓ duplicate checkout is idempotent
  ✓ invoice numbers are sequential per branch
  ✓ archived or inactive products cannot be sold
  ✓ receipt renders with snapshot after product edit
  ✓ pos catalog endpoint returns branch stock
   PASS  Tests\Feature\ConcurrencyTest
  ✓ concurrent checkouts cannot oversell
   PASS  Tests\Feature\InventoryTest
  ✓ stock in out and count adjustment record movements
  ✓ negative stock is not allowed
  ✓ reason required for stock out and opening only once
  ✓ sales and movements always reconcile
  ✓ low and out of stock alerts with threshold override
   PASS  Tests\Feature\ManagementTest
  ✓ product crud with opening stock and image
  ✓ product validation and duplicate sku
  ✓ sold product keeps history when archived
  ✓ category crud duplicates and archive rules
  ✓ branch crud and manager assignment
  ✓ employee management and password reset
  ✓ settings update
   PASS  Tests\Feature\PagesTest
  ✓ all shop pages render for each role
  ✓ guest pages
  ✓ output is xss escaped
   PASS  Tests\Feature\PlatformTest
  ✓ signup and approval onboards shop
  ✓ suspending shop blocks its users
  ✓ admin pages and health
  ✓ backup and restore into separate database
  ✓ backups are not publicly reachable
   PASS  Tests\Feature\ReturnAndReportTest
  ✓ partial return restocks and updates sale
  ✓ excess and duplicate refunds are rejected
  ✓ refund respects discount and never exceeds paid
  ✓ cashier cannot process returns and not restocked option
  ✓ report totals reconcile with database
  ✓ report pages and csv export
  Tests:    55 passed (329 assertions)
  Duration:
```
