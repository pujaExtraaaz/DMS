<?php

namespace Tally\Inventory;

use Tally\Accounting\Money;
use InvalidArgumentException;

final class Quantity
{
    public static function accepts(string $value, int $places): bool
    {
        $places = max(0, min(4, $places));
        $pattern = $places === 0 ? '/^\d+$/' : '/^\d+(\.\d{1,'.$places.'})?$/';

        return preg_match($pattern, $value) === 1;
    }

    public static function scale(string $value, int $places): int
    {
        if (! preg_match('/^\d+(\.\d{1,4})?$/', $value)) {
            throw new InvalidArgumentException('Enter a quantity with up to 4 decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        $fraction = str_pad(substr($fraction, 0, 4), 4, '0');

        if ($places < 4 && (int) substr($fraction, $places) !== 0) {
            throw new InvalidArgumentException('Enter a quantity with up to '.$places.' decimal places.');
        }

        return ((int) $whole * 10000) + (int) $fraction;
    }

    public static function openingValue(string $quantity, string $rate): string
    {
        $scaled = self::scale($quantity, 4);
        $cents = Money::cents($rate);

        if ($cents < 0) {
            throw new InvalidArgumentException('Enter a rate that is zero or more.');
        }

        if ($scaled === 0 || $cents === 0) {
            return '0.00';
        }

        if ($cents > intdiv(PHP_INT_MAX - 5000, $scaled)) {
            throw new InvalidArgumentException('Opening value is too large.');
        }

        return Money::format(intdiv(($scaled * $cents) + 5000, 10000));
    }

    public static function signedScale(string $value, int $places): int
    {
        $negative = str_starts_with($value, '-');
        $scaled = self::scale(ltrim($value, '-'), $places);

        return $negative ? -$scaled : $scaled;
    }

    public static function format(int $scaled): string
    {
        $sign = $scaled < 0 ? '-' : '';
        $scaled = abs($scaled);

        return $sign.intdiv($scaled, 10000).'.'.str_pad((string) ($scaled % 10000), 4, '0', STR_PAD_LEFT);
    }

    public static function valueFromScaled(int $scaled, string $rate): string
    {
        $value = self::openingValue(self::format(abs($scaled)), $rate);

        return $scaled < 0 && $value !== '0.00' ? '-'.$value : $value;
    }
}
