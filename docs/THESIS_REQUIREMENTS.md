# Thesis Requirements Traceability

> **Important limitation.** The thesis PDF (`KiraSoft(1).pdf`) was **not available** in the development
> environment (it was not attached to the repository or session). This matrix was therefore built from the
> requirement list in the project brief, which itself summarises the thesis chapters (scope, FR/NFR,
> use cases, schema, billing workflow, test cases KS-T01–KS-T15). **Before submission, compare each row
> against Chapters 3–6 of the thesis** and adjust IDs/wording. Test-case titles for KS-T01–T15 below are
> *inferred* from the described scope; rename them to match the thesis table.

Legend — **Type:** M = mandatory thesis requirement, S = supporting functionality needed to implement it, E = optional professional enhancement. **Status:** ✅ implemented & tested, 🟡 implemented, verified manually.

## Functional requirements

| # | Requirement | Type | Module / routes | Tables | Tests | Status |
|---|---|---|---|---|---|---|
| FR1 | Secure login / logout, hashed passwords | M | Auth · `/login`, `/logout` | users, sessions | AuthTest (valid, invalid, session regen) | ✅ |
| FR2 | Role-based access: Super Admin, Shop Admin, Branch Manager, Cashier | M | `App\Support\Permissions`, policies, `can:` middleware | users, branch_user | AuthorizationTest (6 tests) | ✅ |
| FR3 | Password reset | M | `/forgot-password`, `/reset-password/*` | password_reset_tokens | AuthTest::password_reset_flow | ✅ |
| FR4 | Account activation / deactivation | M | Employees toggle, shop suspend, `active` middleware | users.is_active, shops.status | AuthTest (inactive, deactivated mid-session), PlatformTest | ✅ |
| FR5 | Super Admin dashboard & shop management | M | `/admin`, `/admin/shops*` | shops | PlatformTest | ✅ |
| FR6 | Shop onboarding / approval | E | `/register-shop`, `/admin/signups*` | signup_requests | PlatformTest::signup_and_approval | ✅ |
| FR7 | Shop Admin dashboard with real metrics & charts | M | `/dashboard` (ReportService) | sales, sale_returns | PagesTest, ReturnAndReportTest | ✅ |
| FR8 | Branch management (CRUD, activate, manager, staff) | M | `/branches*` | branches, branch_user | ManagementTest::branch_crud | ✅ |
| FR9 | Employee management (CRUD, roles, branches, reset credentials) | M | `/employees*` | users, branch_user | ManagementTest::employee_management | ✅ |
| FR10 | Category management (add/edit/archive, no duplicates) | M | `/categories*` | categories | ManagementTest::category_crud | ✅ |
| FR11 | Product management (SKU, unit, price, cost, threshold, image, archive) | M | `/products*` | products | ManagementTest (3 tests) | ✅ |
| FR12 | Branch-wise inventory, opening stock, stock in/out, adjustments, history | M | `/inventory*` (InventoryService) | inventory, inventory_movements | InventoryTest (5 tests) | ✅ |
| FR13 | Point of Sale: search, cart, quantities, discount, tax, cash, change | M | `/pos`, `/pos/products`, `/pos/checkout` | sales, sale_items, payments | CheckoutTest (11), browser pos-flow | ✅ |
| FR14 | Atomic billing transaction with automatic stock deduction | M | CheckoutService | all sales tables | CheckoutTest::insufficient_stock_rolls_back, ConcurrencyTest | ✅ |
| FR15 | Invoice generation, unique numbers, print/reprint receipt | M | `/sales/{id}/receipt` | sales, invoice_sequences | CheckoutTest::invoice_numbers, receipt snapshot | ✅ |
| FR16 | Invoice/sales history, search & filters (date, cashier, branch) | M | `/sales` | sales | AuthorizationTest::cashier_sees_only_own_sales | ✅ |
| FR17 | Sales reports: daily/weekly/monthly/custom, branch, cashier, charts, CSV, print | M | `/reports`, `/reports/export` | sales, sale_returns | ReturnAndReportTest::report_totals_reconcile, csv export | ✅ |
| FR18 | Low-stock / out-of-stock alerts, configurable thresholds | M | `/alerts`, bell menu, threshold editor | inventory, products, shop_settings | InventoryTest::low_and_out_of_stock_alerts | ✅ |
| FR19 | Activity logging (logins, changes, sales, refunds, admin actions) | M | ActivityLogger, `/activity`, `/admin/activity` | activity_logs | asserted in Auth/Checkout/Inventory/Management tests | ✅ |
| FR20 | Settings: shop info, logo, currency PKR, receipt header/footer, invoice prefix, tax, thresholds | M | `/settings`, `/profile` | shop_settings | ManagementTest::settings_update | ✅ |
| FR21 | Sales returns with validated quantities & restock | E (supporting) | `/returns`, `/sales/{id}/return` (ReturnService) | sale_returns, sale_return_items | ReturnAndReportTest (4 tests) | ✅ |
| FR22 | Database backup & tested restore | S | `/admin/backups`, `php artisan kirasoft:backup --restore-test` | backups | PlatformTest::backup_and_restore | ✅ |
| FR23 | System health / diagnostics | S | `/admin/system`, `php artisan kirasoft:health`, `/up` | — | PlatformTest::admin_pages_and_health | ✅ |

## Non-functional requirements

| NFR | How it is met | Evidence |
|---|---|---|
| Security | bcrypt hashing, CSRF, Eloquent/bound queries, Blade escaping, server-side RBAC + record policies, login/checkout throttling, session regeneration, security headers, private backups, CSV formula-injection guard, no secrets in logs | AuthorizationTest, PagesTest (XSS, headers), manual CSRF check (419) |
| Data integrity | InnoDB FKs, unique keys, DECIMAL money/qty, integer-paisa arithmetic, transactions, row locks, idempotency keys, movement ledger | ConcurrencyTest, InventoryTest reconcile, MoneyTest |
| Usability | Keyboard POS (F2/F8/F9/Enter/↑↓), barcode/SKU entry, empty/loading/error states, Urdu-friendly PKR formatting | browser pos-flow |
| Responsiveness | Purpose-built mobile layouts (table→cards, bottom-sheet cart), tested at 320–1440px | `tests/Browser/smoke.cjs` — 306 checks, 0 failures |
| Performance | Indexed queries, aggregate SQL for reports, single catalog fetch per branch at POS | — |
| Maintainability | Laravel modular monolith, service classes, central permissions, design tokens | docs/ARCHITECTURE.md |

## Thesis test cases (inferred titles — align with Chapter 6)

| ID | Test case | Automated test |
|---|---|---|
| KS-T01 | Login with valid credentials | AuthTest::test_valid_login_redirects_by_role |
| KS-T02 | Login with invalid credentials | AuthTest::test_invalid_login_is_rejected |
| KS-T03 | Add / edit product | ManagementTest::test_product_crud_with_opening_stock_and_image |
| KS-T04 | Add category / duplicate rejected | ManagementTest::test_category_crud_duplicates_and_archive_rules |
| KS-T05 | Add products to cart & checkout | CheckoutTest::test_checkout_creates_complete_sale_atomically, browser pos-flow |
| KS-T06 | Bill calculation (subtotal, discount, tax, change) | CheckoutTest::test_percent_and_fixed_discounts_and_tax |
| KS-T07 | Checkout blocked on insufficient stock | CheckoutTest::test_insufficient_stock_rolls_back_everything |
| KS-T08 | Stock deducted after sale | CheckoutTest::test_checkout_creates_complete_sale_atomically |
| KS-T09 | Invoice generated & printed | CheckoutTest::test_invoice_numbers_are_sequential_per_branch, test_receipt_renders_with_snapshot_after_product_edit |
| KS-T10 | Stock adjustment recorded | InventoryTest::test_stock_in_out_and_count_adjustment_record_movements |
| KS-T11 | Low-stock alert shown | InventoryTest::test_low_and_out_of_stock_alerts_with_threshold_override |
| KS-T12 | Sales report matches transactions | ReturnAndReportTest::test_report_totals_reconcile_with_database |
| KS-T13 | Role restriction (cashier cannot access admin) | AuthorizationTest::test_cashier_cannot_reach_admin_modules |
| KS-T14 | Branch-specific data isolation | AuthorizationTest::test_cross_branch_access_is_denied, test_cross_shop_records_are_inaccessible |
| KS-T15 | Employee account management | ManagementTest::test_employee_management_and_password_reset |

## Scope notes
- Payment method is **cash only** (as described in the brief). Card/JazzCash/EasyPaisa would be future work.
- Alerts are **in-app only**; no SMS or e-mail alerts are claimed.
- Password-reset e-mails use Laravel's mailer; locally `MAIL_MAILER=log` writes the link to `storage/logs/laravel.log`.
- Products are catalogued per shop; stock is per branch.
