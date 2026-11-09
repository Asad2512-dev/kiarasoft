# KiraSoft — Grocery Billing & Inventory Management System

A multi-shop, multi-branch POS and inventory system for Pakistani Kiryana stores, mini marts and small retailers.
BS Computer Science Final Year Project. **PHP 8.3 · Laravel 11 · MySQL 8 (InnoDB) · Blade · Bootstrap 5 · Alpine.js · Chart.js**

## Features
- Four roles (Super Admin, Shop Admin, Branch Manager, Cashier), server-side RBAC, shop and branch data isolation
- Keyboard-fast POS (search, SKU/barcode entry, decimal kg/L quantities, discounts, tax, cash and change, 80 mm receipts)
- Atomic, idempotent, row-locked billing, so stock can never be oversold
- Branch-wise inventory with a full movement ledger, adjustments, reconciliation and low/out-of-stock alerts
- Controlled partial/full returns with proportional refunds and optional restocking
- Real dashboards and reports (daily/weekly/monthly/custom, branch, cashier, top products) with CSV export and print
- Audit log, shop settings, onboarding and approval, backups with tested restore, and system health checks
- Responsive layouts designed for phones, tablets and desktops

## Quick start
```bash
composer install
```
```bash
cp .env.example .env
```
```bash
php artisan key:generate
```
Set the `DB_*` values and `KIRASOFT_DEMO_PASSWORD` in `.env`, then:
```bash
php artisan migrate --seed
```
```bash
php artisan storage:link
```
```bash
php artisan serve
```
Full MAMP/XAMPP instructions: [docs/SETUP.md](docs/SETUP.md)

## Documentation
| Doc | |
|---|---|
| [THESIS_REQUIREMENTS.md](docs/THESIS_REQUIREMENTS.md) | Requirement traceability matrix |
| [ARCHITECTURE.md](docs/ARCHITECTURE.md) | Design, billing transaction, report formulas, security |
| [DATABASE_SCHEMA.md](docs/DATABASE_SCHEMA.md) | Tables and ER diagram |
| [PERMISSIONS.md](docs/PERMISSIONS.md) | Role matrix and every route |
| [TEST_RESULTS.md](docs/TEST_RESULTS.md) | Executed test results |
| [DEMO_GUIDE.md](docs/DEMO_GUIDE.md) | Demo accounts and presentation walkthrough |
