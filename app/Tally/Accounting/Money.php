<?php

namespace Tally\Accounting;

final class Money
{
    public static function cents(mixed $value): int
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return 0;
        }

        if (! preg_match('/^-?\d+(\.\d{1,2})?$/', $raw)) {
            throw new \InvalidArgumentException('Amount must be a number with up to 2 decimal places.');
        }

        $negative = str_starts_with($raw, '-');
        $raw = ltrim($raw, '-');
        [$whole, $fraction] = array_pad(explode('.', $raw, 2), 2, '0');
        $fraction = str_pad(substr($fraction, 0, 2), 2, '0');
        $cents = ((int) $whole * 100) + (int) $fraction;

        return $negative ? -$cents : $cents;
    }

    public static function format(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function add(int $left, int $right): int
    {
        return $left + $right;
    }
}
