<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case ShopAdmin = 'shop_admin';
    case BranchManager = 'branch_manager';
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::ShopAdmin => 'Shop Admin',
            self::BranchManager => 'Branch Manager',
            self::Cashier => 'Cashier',
        };
    }

    /** Roles a shop admin may assign to employees. */
    public static function employeeRoles(): array
    {
        return [self::BranchManager, self::Cashier];
    }
}
