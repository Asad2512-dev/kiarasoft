<?php

namespace App\Support;

use App\Enums\Role;

/**
 * Single source of truth for role -> permission mapping.
 * Record-level scoping (shop / branch) is enforced separately in policies
 * and in User::accessibleBranchIds().
 */
final class Permissions
{
    public const MAP = [
        'super_admin' => [
            'platform.dashboard', 'platform.shops.manage', 'platform.signups.manage',
            'platform.users.view', 'platform.logs.view', 'system.backups', 'system.health',
        ],
        'shop_admin' => [
            'dashboard.view', 'branches.manage', 'branches.view', 'employees.manage', 'employees.view',
            'categories.manage', 'products.manage', 'inventory.view', 'inventory.adjust',
            'pos.use', 'sales.view', 'sales.view_all', 'returns.create', 'reports.view', 'reports.export',
            'alerts.view', 'logs.view', 'settings.manage',
        ],
        'branch_manager' => [
            'dashboard.view', 'branches.view', 'employees.view',
            'categories.manage', 'products.manage', 'inventory.view', 'inventory.adjust',
            'pos.use', 'sales.view', 'sales.view_all', 'returns.create', 'reports.view', 'reports.export',
            'alerts.view', 'logs.view',
        ],
        'cashier' => [
            'pos.use', 'sales.view', 'inventory.view', 'alerts.view',
        ],
    ];

    public static function allows(Role $role, string $permission): bool
    {
        return in_array($permission, self::MAP[$role->value] ?? [], true);
    }
}
