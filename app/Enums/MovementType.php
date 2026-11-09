<?php

namespace App\Enums;

enum MovementType: string
{
    case Opening = 'opening';
    case StockIn = 'stock_in';
    case StockOut = 'stock_out';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case SaleReturn = 'return';

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Opening stock',
            self::StockIn => 'Stock in',
            self::StockOut => 'Stock out',
            self::Adjustment => 'Adjustment',
            self::Sale => 'Sale',
            self::SaleReturn => 'Return',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Opening, self::StockIn, self::SaleReturn => 'success',
            self::StockOut, self::Sale => 'danger',
            self::Adjustment => 'warning',
        };
    }
}
