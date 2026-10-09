<?php

namespace App\Domains\Crm\Services;

use App\Domains\Crm\Models\Lead;

class LeadDuplicateChecker
{
    /**
     * Normalize email: trim leading/trailing whitespace and lowercase.
     */
    public static function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $trimmed = strtolower(trim($email));

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * Normalize mobile/contact number:
     * - Trims whitespace
     * - Strips non-digits (spaces, dashes, parens, plus, dots, slashes)
     * - Handles standard Indian mobile prefixes (+91/91 or leading 0 for 10-digit mobile)
     */
    public static function normalizeMobile(?string $mobile): ?string
    {
        if ($mobile === null) {
            return null;
        }

        $trimmed = trim($mobile);
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $trimmed);
        if ($digits === '' || $digits === null) {
            return null;
        }

        // 12-digit Indian mobile starting with 91
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }
        // 11-digit mobile starting with 0
        elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Find existing lead in database by email (case-insensitive, trimmed).
     */
    public static function findDuplicateEmail(string $email, ?int $companyId = null, ?int $excludeLeadId = null): ?Lead
    {
        $normalized = self::normalizeEmail($email);
        if ($normalized === null) {
            return null;
        }

        return Lead::query()
            ->when($companyId !== null, fn ($q) => $q->where('company_id', $companyId))
            ->when($excludeLeadId !== null, fn ($q) => $q->where('id', '!=', $excludeLeadId))
            ->where(function ($q) use ($normalized, $email) {
                $q->where('email', $normalized)
                  ->orWhere('email', trim($email))
                  ->orWhereRaw('LOWER(TRIM(email)) = ?', [$normalized]);
            })
            ->first(['id', 'name', 'email', 'mobile']);
    }

    /**
     * Find existing lead in database by mobile/contact number (normalized).
     */
    public static function findDuplicateMobile(string $mobile, ?int $companyId = null, ?int $excludeLeadId = null): ?Lead
    {
        $normalized = self::normalizeMobile($mobile);
        if ($normalized === null) {
            return null;
        }

        $variants = [
            $normalized,
            trim($mobile),
        ];

        if (strlen($normalized) === 10) {
            $variants[] = '0' . $normalized;
            $variants[] = '91' . $normalized;
            $variants[] = '+91' . $normalized;
            $variants[] = '+91 ' . $normalized;
            $variants[] = '+91-' . $normalized;
            $part1 = substr($normalized, 0, 5);
            $part2 = substr($normalized, 5);
            $variants[] = "{$part1} {$part2}";
            $variants[] = "{$part1}-{$part2}";
            $variants[] = "+91 {$part1} {$part2}";
            $variants[] = "+91-{$part1}-{$part2}";
        }

        $candidates = Lead::query()
            ->when($companyId !== null, fn ($q) => $q->where('company_id', $companyId))
            ->when($excludeLeadId !== null, fn ($q) => $q->where('id', '!=', $excludeLeadId))
            ->where(function ($q) use ($normalized, $variants) {
                $q->whereIn('mobile', $variants);
                if (strlen($normalized) >= 7) {
                    $lastDigits = substr($normalized, -7);
                    $q->orWhere('mobile', 'like', "%{$lastDigits}%");
                }
            })
            ->get(['id', 'name', 'email', 'mobile']);

        foreach ($candidates as $candidate) {
            if (self::normalizeMobile($candidate->mobile) === $normalized) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Validate manual lead creation or edit.
     * Returns an array of field => error message, e.g.:
     * [
     *   'email' => 'This email address is already registered with another lead.',
     *   'mobile' => 'This mobile/contact number is already registered with another lead.',
     * ]
     */
    public static function checkManualLead(?string $email, ?string $mobile, ?int $companyId = null, ?int $excludeLeadId = null): array
    {
        $errors = [];

        if (filled($email)) {
            $existing = self::findDuplicateEmail($email, $companyId, $excludeLeadId);
            if ($existing) {
                $errors['email'] = 'This email address is already registered with another lead.';
            }
        }

        if (filled($mobile)) {
            $existing = self::findDuplicateMobile($mobile, $companyId, $excludeLeadId);
            if ($existing) {
                $errors['mobile'] = 'This mobile/contact number is already registered with another lead.';
            }
        }

        return $errors;
    }
}
