<?php

namespace App\Support;

/**
 * Money helpers that work in integer sen (1/100 MYR) so balance maths never
 * suffers float rounding (e.g. 0.1 + 0.2 != 0.3).
 */
final class Money
{
    public static function toSen(string|int|float|null $amount): int
    {
        return (int) round(((float) ($amount ?? 0)) * 100);
    }

    public static function fromSen(int $sen): string
    {
        return number_format($sen / 100, 2, '.', '');
    }

    /** True when two amounts are equal to the sen. */
    public static function equals(string|int|float|null $a, string|int|float|null $b): bool
    {
        return self::toSen($a) === self::toSen($b);
    }
}
