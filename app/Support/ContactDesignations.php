<?php

namespace App\Support;

class ContactDesignations
{
    /**
     * Predefined contact person designations.
     *
     * @var array<int, string>
     */
    public const OPTIONS = [
        'Accounts',
        'Architect',
        'ASM',
        'Branch Manager',
        'CEO',
        'Customer',
        'Director',
        'Gen. Manger',
        'Logistics',
        'Marketing',
        'MD',
        'Partner',
        'Purchase Dept.',
        'Salesman',
        'Store Manager',
        'Other',
    ];

    /**
     * Return all available designation options.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return self::OPTIONS;
    }

    /**
     * Return only predefined standard options (excluding 'Other').
     *
     * @return array<int, string>
     */
    public static function standardOptions(): array
    {
        return array_values(array_filter(self::OPTIONS, fn ($opt) => $opt !== 'Other'));
    }

    /**
     * Check if a designation is one of the predefined standard options.
     */
    public static function isStandard(?string $designation): bool
    {
        if ($designation === null || $designation === '') {
            return false;
        }

        return in_array($designation, self::standardOptions(), true);
    }

    /**
     * Resolve the dropdown selection and custom other value for a stored role.
     *
     * @return array{designation: string, other_designation: string}
     */
    public static function resolve(?string $role): array
    {
        $role = trim((string) $role);

        if ($role === '') {
            return [
                'designation' => '',
                'other_designation' => '',
            ];
        }

        if (self::isStandard($role)) {
            return [
                'designation' => $role,
                'other_designation' => '',
            ];
        }

        if ($role === 'Other') {
            return [
                'designation' => 'Other',
                'other_designation' => '',
            ];
        }

        // Custom designation saved previously
        return [
            'designation' => 'Other',
            'other_designation' => $role,
        ];
    }
}

