# Roles & Permissions

Defined once in `app/Support/Permissions.php`; each permission is a Gate ability used by `can:` route middleware and `@can` in views. Record-level policies additionally enforce shop and branch ownership.

| Permission | Super Admin | Shop Admin | Branch Manager | Cashier |
|---|:-:|:-:|:-:|:-:|
| platform.* (shops, signups, users, platform logs) | ✅ | | | |
| system.backups, system.health | ✅ | | | |
| dashboard.view | | ✅ | ✅ (own branches) | |
| branches.view | | ✅ all | ✅ assigned | |
| branches.manage | | ✅ | | |
| employees.view | | ✅ | ✅ (staff of own branches) | |
| employees.manage | | ✅ | | |
| categories.manage, products.manage | | ✅ | ✅ | |
| inventory.view | | ✅ | ✅ | ✅ (read-only) |
| inventory.adjust | | ✅ | ✅ | |
| pos.use | | ✅ | ✅ | ✅ |
| sales.view | | ✅ | ✅ | ✅ (own invoices only) |
| sales.view_all | | ✅ | ✅ | |
| returns.create | | ✅ | ✅ | |
| reports.view, reports.export | | ✅ | ✅ | |
| alerts.view | | ✅ | ✅ | ✅ |
| logs.view | | ✅ (shop) | ✅ (own branches) | |
| settings.manage | | ✅ | | |

Notes
- Shop Admins can create only **Branch Manager** and **Cashier** accounts (no privilege escalation).
- The Super Admin manages the platform and cannot open shop business records.
- Backups contain all shops' data, so only the Super Admin can create/download/test them.

## Routes and their permission
| Method | URI | Name | Middleware (permission) |
|---|---|---|---|
| GET | `/` |  |  |
| GET | `/activity` | activity.index | can:logs.view |
| GET | `/admin` | admin.dashboard | can:platform.dashboard |
| GET | `/admin/activity` | admin.activity | can:platform.logs.view |
| GET | `/admin/backups` | admin.backups.index | can:system.backups |
| POST | `/admin/backups` | admin.backups.store | can:system.backups |
| GET | `/admin/backups/{backup}/download` | admin.backups.download | can:system.backups |
| POST | `/admin/backups/{backup}/test-restore` | admin.backups.test | can:system.backups |
| GET | `/admin/shops` | admin.shops.index | can:platform.shops.manage |
| POST | `/admin/shops` | admin.shops.store | can:platform.shops.manage |
| GET | `/admin/shops/create` | admin.shops.create | can:platform.shops.manage |
| GET | `/admin/shops/{shop}` | admin.shops.show | can:platform.shops.manage |
| PUT | `/admin/shops/{shop}` | admin.shops.update | can:platform.shops.manage |
| GET | `/admin/shops/{shop}/edit` | admin.shops.edit | can:platform.shops.manage |
| PATCH | `/admin/shops/{shop}/status` | admin.shops.toggle | can:platform.shops.manage |
| GET | `/admin/signups` | admin.signups.index | can:platform.signups.manage |
| POST | `/admin/signups/{signupRequest}/approve` | admin.signups.approve | can:platform.signups.manage |
| POST | `/admin/signups/{signupRequest}/reject` | admin.signups.reject | can:platform.signups.manage |
| GET | `/admin/system` | admin.system | can:system.health |
| GET | `/admin/users` | admin.users.index | can:platform.users.view |
| GET | `/alerts` | alerts.index | can:alerts.view |
| GET | `/branches` | branches.index | can:branches.view |
| POST | `/branches` | branches.store | can:branches.view, can:branches.manage |
| GET | `/branches/create` | branches.create | can:branches.view, can:branches.manage |
| GET | `/branches/{branch}` | branches.show | can:branches.view |
| PUT | `/branches/{branch}` | branches.update | can:branches.view, can:branches.manage |
| GET | `/branches/{branch}/edit` | branches.edit | can:branches.view, can:branches.manage |
| PATCH | `/branches/{branch}/status` | branches.toggle | can:branches.view, can:branches.manage |
| GET | `/categories` | categories.index | can:categories.manage |
| POST | `/categories` | categories.store | can:categories.manage |
| PUT | `/categories/{category}` | categories.update | can:categories.manage |
| DELETE | `/categories/{category}` | categories.archive | can:categories.manage |
| PATCH | `/categories/{id}/restore` | categories.restore | can:categories.manage |
| GET | `/dashboard` | dashboard | can:dashboard.view |
| GET | `/employees` | employees.index | can:employees.view |
| POST | `/employees` | employees.store | can:employees.view, can:employees.manage |
| GET | `/employees/create` | employees.create | can:employees.view, can:employees.manage |
| PUT | `/employees/{employee}` | employees.update | can:employees.view, can:employees.manage |
| GET | `/employees/{employee}/edit` | employees.edit | can:employees.view, can:employees.manage |
| POST | `/employees/{employee}/password` | employees.password | can:employees.view, can:employees.manage |
| PATCH | `/employees/{employee}/status` | employees.toggle | can:employees.view, can:employees.manage |
| GET | `/forgot-password` | password.request |  |
| POST | `/forgot-password` | password.email |  |
| GET | `/inventory` | inventory.index | can:inventory.view |
| GET | `/inventory/adjust` | inventory.create | can:inventory.view, can:inventory.adjust |
| POST | `/inventory/adjust` | inventory.store | can:inventory.view, can:inventory.adjust |
| GET | `/inventory/movements` | inventory.movements | can:inventory.view |
| GET | `/inventory/reconcile` | inventory.reconcile | can:inventory.view, can:inventory.adjust |
| PUT | `/inventory/{inventory}/threshold` | inventory.threshold | can:inventory.view, can:inventory.adjust |
| GET | `/login` | login |  |
| POST | `/login` |  |  |
| POST | `/logout` | logout |  |
| GET | `/pos` | pos.index | can:pos.use |
| POST | `/pos/checkout` | pos.checkout | can:pos.use |
| GET | `/pos/products` | pos.products | can:pos.use |
| GET | `/products` | products.index | can:products.manage |
| POST | `/products` | products.store | can:products.manage |
| GET | `/products/create` | products.create | can:products.manage |
| GET | `/products/export` | products.export | can:products.manage |
| PATCH | `/products/{id}/restore` | products.restore | can:products.manage |
| GET | `/products/{product}` | products.show | can:products.manage |
| PUT|PATCH | `/products/{product}` | products.update | can:products.manage |
| DELETE | `/products/{product}` | products.archive | can:products.manage |
| GET | `/products/{product}/edit` | products.edit | can:products.manage |
| GET | `/profile` | profile.edit |  |
| PUT | `/profile` | profile.update |  |
| PUT | `/profile/password` | profile.password |  |
| GET | `/register-shop` | signup.create |  |
| POST | `/register-shop` | signup.store |  |
| GET | `/reports` | reports.index | can:reports.view |
| GET | `/reports/export` | reports.export | can:reports.view, can:reports.export |
| POST | `/reset-password` | password.store |  |
| GET | `/reset-password/{token}` | password.reset |  |
| GET | `/returns` | returns.index | can:returns.create |
| GET | `/returns/{saleReturn}` | returns.show | can:returns.create |
| GET | `/sales` | sales.index | can:sales.view |
| GET | `/sales/{sale}` | sales.show | can:sales.view |
| GET | `/sales/{sale}/receipt` | sales.receipt | can:sales.view |
| GET | `/sales/{sale}/return` | returns.create | can:returns.create |
| POST | `/sales/{sale}/return` | returns.store | can:returns.create |
| GET | `/settings` | settings.edit | can:settings.manage |
| PUT | `/settings` | settings.update | can:settings.manage |
