<?php

namespace Tally\Accounting;

/**
 * Tally-style voucher number: prefix, zero-padded series, then suffix.
 * Example: REC-000001/26.
 */
class VoucherNumberFormat
{
    public static function make(string $prefix, ?string $suffix, int $padding, int $number): string
    {
        $padding = max(1, min(8, $padding));
        $body = sprintf('%0'.$padding.'d', max(0, $number));
        $prefix = strtoupper(trim($prefix));
        $suffix = strtoupper(trim((string) $suffix));
        $formatted = $prefix === '' ? $body : $prefix.'-'.$body;

        return $suffix === '' ? $formatted : $formatted.$suffix;
    }
}