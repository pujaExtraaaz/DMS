<?php

namespace Tally\Support;

/**
 * Indian grouping for printed documents. Money::format stays unchanged.
 */
final class IndianCurrency
{
    public static function format(mixed $amount): string
    {
        $raw = trim((string) $amount);

        if ($raw === '') {
            $raw = '0.00';
        }

        $negative = str_starts_with($raw, '-');
        $raw = ltrim($raw, '-');
        [$whole, $fraction] = array_pad(explode('.', $raw, 2), 2, '00');
        $fraction = str_pad(substr($fraction, 0, 2), 2, '0');
        $whole = ltrim($whole, '0') ?: '0';

        if (strlen($whole) > 3) {
            $last = substr($whole, -3);
            $rest = substr($whole, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) ?? $rest;
            $whole = $rest.','.$last;
        }

        return ($negative ? '-' : '').'₹'.$whole.'.'.$fraction;
    }

    public static function words(mixed $amount): string
    {
        $raw = str_replace([',', '₹', ' '], '', trim((string) $amount));
        $negative = str_starts_with($raw, '-');
        $raw = ltrim($raw, '-');

        if ($raw === '') {
            $raw = '0.00';
        }

        if (! str_contains($raw, '.')) {
            $raw .= '.00';
        }

        [$whole, $fraction] = explode('.', $raw, 2);
        $rupees = (int) $whole;
        $paise = (int) substr(str_pad($fraction, 2, '0'), 0, 2);
        $text = self::indianWords($rupees).' rupees';

        if ($paise > 0) {
            $text .= ' and '.self::indianWords($paise).' paise';
        }

        return ($negative ? 'Minus ' : '').ucfirst($text).' only';
    }

    private static function indianWords(int $number): string
    {
        if ($number === 0) {
            return 'zero';
        }

        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        $parts = [];
        $crore = intdiv($number, 10000000);
        $number %= 10000000;
        $lakh = intdiv($number, 100000);
        $number %= 100000;
        $thousand = intdiv($number, 1000);
        $number %= 1000;
        $hundred = intdiv($number, 100);
        $number %= 100;

        $chunk = function (int $value) use ($ones, $tens): string {
            if ($value < 20) {
                return $ones[$value];
            }

            $ten = intdiv($value, 10);
            $one = $value % 10;

            return trim($tens[$ten].' '.$ones[$one]);
        };

        if ($crore) {
            $parts[] = $chunk($crore).' crore';
        }

        if ($lakh) {
            $parts[] = $chunk($lakh).' lakh';
        }

        if ($thousand) {
            $parts[] = $chunk($thousand).' thousand';
        }

        if ($hundred) {
            $parts[] = $ones[$hundred].' hundred';
        }

        if ($number) {
            $parts[] = $chunk($number);
        }

        return implode(' ', $parts);
    }
}
