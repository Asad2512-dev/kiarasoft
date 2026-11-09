<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_parses_and_formats_without_float_errors(): void
    {
        $this->assertSame(12345, Money::toMinor('123.45'));
        $this->assertSame(30, Money::toMinor('0.1') + Money::toMinor('0.2')); // 0.1+0.2 exactly 0.30
        $this->assertSame('0.30', Money::fromMinor(30));
        $this->assertSame(1250, Money::qtyToMilli('1.25'));
        $this->assertSame('1.25', Money::qty('1.250'));
        $this->assertSame('Rs 1,234.50', Money::format('1234.5'));
        $this->assertSame(-500, Money::toMinor('-5'));
    }

    public function test_line_totals_round_half_up(): void
    {
        // 1.255 kg x Rs 199.99 = 250.987... -> 250.99
        $this->assertSame(25099, Money::lineTotal(1255, 19999));
        $this->assertSame(5, Money::divRound(9, 2));
        $this->assertSame(-5, Money::divRound(-9, 2));
        $this->assertSame(1750, Money::percentOf(35000, 500)); // 5% of 350
    }

    public function test_rejects_invalid_input(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::toMinor('12abc');
    }
}
