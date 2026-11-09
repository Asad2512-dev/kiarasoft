# Architecture

KiraSoft is a **Laravel 11 modular monolith** (PHP 8.2+/8.3, MySQL 8 InnoDB, Blade + Bootstrap 5 + Alpine.js + Chart.js, built with Vite).

```
Browser ── Blade pages (server-rendered) ──┐
   └── POS (Alpine.js) ── JSON ──────────── │ routes/web.php, routes/auth.php
                                            ▼
              Middleware: auth · auth.session · active · can:<permission> · throttle · CSRF · SecurityHeaders
                                            ▼
              Thin controllers (validation, authorization via Policies)
                                            ▼
              Services (business logic)                     Support
              ├─ CheckoutService   (billing transaction)    ├─ Permissions (role → permission map)
              ├─ InventoryService  (all stock changes)      ├─ Money (integer paisa / milli-qty math)
              ├─ ReturnService     (refunds)                ├─ SystemHealth, Csv
              ├─ InvoiceNumberGenerator                     
              ├─ ReportService · AlertService               
              ├─ ActivityLogger · BackupService · ShopOnboardingService
                                            ▼
              Eloquent models ── MySQL (InnoDB, FKs, DECIMAL)
```

## Code layout
| Path | Contents |
|---|---|
| `app/Http/Controllers` | One controller per module; `Admin/` for platform screens, `Auth/` for authentication |
| `app/Services` | All business rules (no business logic in Blade or controllers) |
| `app/Policies` | Record-level checks (shop + branch ownership) |
| `app/Support/Permissions.php` | **Single source of truth** for role permissions; each becomes a Gate ability |
| `app/Enums` | Role, Unit, MovementType, SaleStatus |
| `resources/scss/_tokens.scss` | Design tokens (colours, type, radius, shadows, layout) |
| `resources/js/pos.js` | POS component (display-only maths; server recalculates) |
| `tests/Feature`, `tests/Unit` | PHPUnit (MySQL); `tests/Browser` Playwright smoke + POS flow |

## Tenancy & isolation
- Every business row carries `shop_id`; branch-scoped rows also carry `branch_id`.
- `User::accessibleBranchIds()` — Shop Admin: every branch of their shop; Manager/Cashier: assigned branches (`branch_user`).
- Every list query filters by `shop_id` **and** the accessible branch IDs; every `?branch=` filter is validated (`Controller::branchScope()` aborts 403).
- Policies re-check ownership on every record route. Hiding buttons is cosmetic only.

## Billing transaction (CheckoutService::checkout)
1. Validate the user is active, may use POS, belongs to the shop, may access the active branch.
2. Idempotency: an existing sale with the same `idempotency_key` is returned instead of creating another (also enforced by a UNIQUE index, catching simultaneous duplicates).
3. Normalise the cart (merge duplicate lines, quantities > 0, whole units for pcs/pack/bottle/dozen).
4. `DB::transaction(..., 3)` (retries deadlocks).
5. `SELECT … FOR UPDATE` inventory rows **in ascending product_id order** (consistent lock order ⇒ no deadlock cycles).
6. Re-check stock against locked rows.
7. Read prices from the database and compute subtotal/discount/tax/total in integer paisa.
8. Lock the branch's daily `invoice_sequences` row and increment → `INV-{BRANCH}-{YYYYMMDD}-{NNNN}`.
9–13. Insert `sales`, `sale_items` (name/SKU/unit/price/cost snapshots), `payments`; deduct stock; write one `inventory_movements` row per line.
14. Write `activity_logs`. 15. Commit. Any exception rolls back everything.

`ConcurrencyTest` launches 6 separate PHP processes buying 3 of 10 units at the same instant: exactly 3 succeed, stock ends at 1, never negative.

## Money & quantities
DECIMAL(12,2) money and DECIMAL(12,3) quantities in MySQL. In PHP, `App\Support\Money` converts to integers (paisa / milli-units), computes, and rounds half-up — no floats touch totals.

## Report metric definitions (ReportService)
| Metric | Definition |
|---|---|
| Gross sales | Σ `sales.subtotal` for invoices whose `sold_at` is in range |
| Discounts | Σ `sales.discount_amount` |
| Tax | Σ `sales.tax_amount` (tax = (subtotal − discount) × rate) |
| Invoiced | Σ `sales.total` = gross − discounts + tax |
| Returns | Σ `sale_returns.refund_amount` for returns **processed** in range (cash basis) |
| Net sales | Invoiced − Returns |
| Invoices | COUNT(sales) |
| Avg. invoice | Invoiced ÷ Invoices |

Branch, cashier and daily breakdowns use the same definitions, so they sum to the summary (verified in `ReturnAndReportTest::test_report_totals_reconcile_with_database`).

## Returns
Refund for a line = returned qty × unit price × (invoice total ÷ invoice subtotal), so discount/tax are shared proportionally. The final return of an invoice refunds exactly the remaining paid amount; refunds can never exceed it. Restocking is optional (damaged/expired goods) and produces `return` movements.

## Stock thresholds
Effective low-stock threshold = branch override (`inventory.low_stock_threshold`) → product threshold → shop default.

## Security summary
bcrypt; CSRF on all forms; Blade auto-escaping; Eloquent bindings; rate limits (login 5/min per email+IP, checkout 60/min, public forms 10/h); session regenerated on login and invalidated on logout; `auth.session` logs out other devices after password change; deactivated users/suspended shops are logged out on their next request; uploads restricted to images ≤ 2 MB on the public disk; backups on the private disk with SHA-256 checksums and credentials passed via a temporary 0600 defaults file; activity logs strip password/token fields; security headers (X-Frame-Options, nosniff, Referrer-Policy, HSTS on HTTPS).
