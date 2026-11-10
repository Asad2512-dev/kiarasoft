<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/** Employee administration within a shop. */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('employees.view');
    }

    public function view(User $user, User $employee): bool
    {
        if (! $user->hasPermission('employees.view') || $employee->shop_id !== $user->shop_id || $employee->isSuperAdmin()) {
            return false;
        }
        if ($user->isShopAdmin()) {
            return true;
        }

        // managers: only staff sharing one of their branches
        return $employee->id === $user->id || (bool) array_intersect($employee->accessibleBranchIds(), $user->accessibleBranchIds());
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('employees.manage');
    }

    public function update(User $user, User $employee): bool
    {
        return $user->hasPermission('employees.manage') && $employee->shop_id === $user->shop_id
            && in_array($employee->role, Role::employeeRoles(), true);
    }
}
