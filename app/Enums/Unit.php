<?php

namespace App\Enums;

enum Unit: string
{
    case Piece = 'pcs';
    case Kilogram = 'kg';
    case Gram = 'g';
    case Liter = 'l';
    case Milliliter = 'ml';
    case Bottle = 'bottle';
    case Pack = 'pack';
    case Dozen = 'dozen';

    public function label(): string
    {
        return match ($this) {
            self::Piece => 'Piece (pcs)',
            self::Kilogram => 'Kilogram (kg)',
            self::Gram => 'Gram (g)',
            self::Liter => 'Liter (L)',
            self::Milliliter => 'Milliliter (ml)',
            self::Bottle => 'Bottle',
            self::Pack => 'Pack',
            self::Dozen => 'Dozen',
        };
    }

    public function short(): string
    {
        return $this === self::Liter ? 'L' : $this->value;
    }

    /** Whether fractional quantities (e.g. 1.250 kg) are allowed. */
    public function allowsDecimal(): bool
    {
        return in_array($this, [self::Kilogram, self::Gram, self::Liter, self::Milliliter], true);
    }
}
