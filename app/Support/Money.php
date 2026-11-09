<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Decimal-safe money & quantity arithmetic.
 *
 * Money is handled as integer paisa (1 PKR = 100 paisa) and quantities as
 * integer milli-units (1 kg = 1000), so no floating point is ever used for
 * business totals. Values are parsed from strings (as returned by MySQL DECIMAL).
 */
final class Money
{
    /** "123.45" -> 12345 */
    public static function toMinor(string|int|float|null $amount): int
    {
        return self::parseScaled($amount, 2);
    }

    /** 12345 -> "123.45" */
    public static function fromMinor(int $minor): string
    {
        return self::formatScaled($minor, 2);
    }

    /** "1.250" -> 1250 */
    public static function qtyToMilli(string|int|float|null $qty): int
    {
        return self::parseScaled($qty, 3);
    }

    /** 1250 -> "1.250" */
    public static function qtyFromMilli(int $milli): string
    {
        return self::formatScaled($milli, 3);
    }

    /** Line total in paisa for (milli-qty x paisa-price), rounded half-up. */
    public static function lineTotal(int $qtyMilli, int $pricePaisa): int
    {
        return self::divRound($qtyMilli * $pricePaisa, 1000);
    }

    /** Percentage of an amount (percent given in hundredths, e.g. 1750 = 17.50%). */
    public static function percentOf(int $minor, int $percentHundredths): int
    {
        return self::divRound($minor * $percentHundredths, 10000);
    }

    public static function divRound(int $num, int $den): int
    {
        if ($den === 0) {
            throw new InvalidArgumentException('Division by zero');
        }
        $q = intdiv($num, $den);
        $r = $num % $den;
        if (abs($r) * 2 >= abs($den)) {
            $q += ($num < 0) !== ($den < 0) ? -1 : 1;
        }

        return $q;
    }

    /** Human display: "Rs 1,234.50" */
    public static function format(string|int|float|null $amount, string $currency = 'Rs'): string
    {
        $minor = self::toMinor($amount);
        $neg = $minor < 0;
        $abs = abs($minor);
        $str = number_format(intdiv($abs, 100)).'.'.str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);

        return ($neg ? '-' : '').$currency.' '.$str;
    }

    /** Trim trailing zeros for quantities: "2.000" -> "2", "1.250" -> "1.25" */
    public static function qty(string|int|float|null $qty): string
    {
        $s = self::qtyFromMilli(self::qtyToMilli($qty));

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    }

    private static function parseScaled(string|int|float|null $value, int $scale): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_float($value)) {
            $value = number_format($value, $scale, '.', '');
        }
        $value = trim((string) $value);
        if (! preg_match('/^(-)?(\d*)(?:\.(\d*))?$/', $value, $m) || ($m[2] === '' && ($m[3] ?? '') === '')) {
            throw new InvalidArgumentException("Invalid decimal value: {$value}");
        }
        $int = $m[2] === '' ? '0' : $m[2];
        $frac = $m[3] ?? '';
        $extra = substr($frac, $scale);
        $frac = str_pad(substr($frac, 0, $scale), $scale, '0');
        $result = (int) $int * (10 ** $scale) + (int) $frac;
        if ($extra !== '' && (int) $extra[0] >= 5) {
            $result++;
        }

        return ($m[1] ?? '') === '-' ? -$result : $result;
    }

    private static function formatScaled(int $value, int $scale): string
    {
        $neg = $value < 0;
        $abs = abs($value);
        $div = 10 ** $scale;

        return ($neg ? '-' : '').intdiv($abs, $div).'.'.str_pad((string) ($abs % $div), $scale, '0', STR_PAD_LEFT);
    }
}
