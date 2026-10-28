<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class WarrantyPeriod
{
    /**
     * Turn a label such as "3 Years" or "6 months" into a length in months.
     * Blank text and "No Warranty" have no period.
     */
    public static function months(?string $text): ?int
    {
        $text = trim((string) $text);
        if ($text === '' || preg_match('/\bno\s+warranty\b/i', $text)) {
            return null;
        }

        $months = 0;
        $matched = false;

        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:years?|yrs?)\b/i', $text, $match)) {
            $months += (int) round(((float) $match[1]) * 12);
            $matched = true;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:months?|mons?)\b/i', $text, $match)) {
            $months += (int) round((float) $match[1]);
            $matched = true;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*days?\b/i', $text, $match)) {
            $months += (int) max(1, round(((float) $match[1]) / 30));
            $matched = true;
        }

        return $matched && $months > 0 ? $months : null;
    }

    public static function expiresOn(CarbonInterface|string $start, ?int $months): ?Carbon
    {
        if ($months === null || $months < 1) {
            return null;
        }

        return Carbon::parse($start)->startOfDay()->addMonths($months);
    }

    /**
     * @return array<int, string>
     */
    public static function split(mixed $raw): array
    {
        if (is_array($raw)) {
            $parts = $raw;
        } elseif (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === '') {
                return [];
            }

            $decoded = str_starts_with($trimmed, '[') ? json_decode($trimmed, true) : null;
            $parts = is_array($decoded) ? $decoded : (preg_split('/[\s,;]+/', $trimmed) ?: []);
        } else {
            return [];
        }

        $clean = [];
        foreach ($parts as $part) {
            $serial = trim((string) $part);
            if ($serial !== '') {
                $clean[$serial] = $serial;
            }
        }

        return array_values($clean);
    }
}
