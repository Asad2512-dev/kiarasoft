<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Completed = 'completed';
    case PartiallyReturned = 'partially_returned';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Completed',
            self::PartiallyReturned => 'Partially returned',
            self::Returned => 'Returned',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::PartiallyReturned => 'warning',
            self::Returned => 'secondary',
        };
    }
}
