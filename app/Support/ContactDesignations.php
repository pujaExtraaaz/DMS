<?php

namespace App\Support;

use App\Domains\Master\Models\Designation;
use Illuminate\Support\Facades\Schema;

class ContactDesignations
{
    /**
     * Predefined standard contact person designations.
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
     * Return standard predefined designation options.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return self::OPTIONS;
    }

    /**
     * Return active designation records from database for dynamic dropdowns.
     *
     * @return array<int, array{id: int, name: string, code: ?string, is_active: bool}>
     */
    public static function items(): array
    {
        try {
            if (Schema::hasTable('designations')) {
                return Designation::where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'code', 'is_active'])
                    ->map(fn ($d) => [
                        'id' => (int) $d->id,
                        'name' => (string) $d->name,
                        'code' => $d->code,
                        'is_active' => (bool) $d->is_active,
                    ])
                    ->all();
            }
        } catch (\Throwable $e) {}

        return [];
    }

    /**
     * Return dynamic list of active designation names from the database,
     * ensuring 'Other' is always at the end.
     *
     * @return array<int, string>
     */
    public static function dynamicNames(): array
    {
        try {
            if (Schema::hasTable('designations')) {
                $names = Designation::where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name')
                    ->all();

                if (! empty($names)) {
                    $hasOther = false;
                    $filtered = [];
                    foreach ($names as $name) {
                        if (strcasecmp($name, 'Other') === 0) {
                            $hasOther = true;
                        } else {
                            $filtered[] = $name;
                        }
                    }

                    if ($hasOther) {
                        $filtered[] = 'Other';
                    }

                    return $filtered;
                }
            }
        } catch (\Throwable $e) {}

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

        if (in_array($designation, self::standardOptions(), true)) {
            return true;
        }

        // Also check if matches standard options case-insensitively or alias
        foreach (self::standardOptions() as $opt) {
            if (strcasecmp($opt, $designation) === 0) {
                return true;
            }
        }

        if (strcasecmp($designation, 'General Manager') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Resolve the dropdown selection and custom other value for a stored role or designation_id.
     *
     * @return array{designation_id: ?int, designation: string, other_designation: string}
     */
    public static function resolve(?string $role, ?int $designationId = null): array
    {
        $role = trim((string) $role);

        // 1. If designation_id is provided, look up the master record
        if ($designationId) {
            try {
                $master = Designation::find($designationId);
                if ($master) {
                    if (strcasecmp($master->name, 'Other') === 0) {
                        return [
                            'designation_id' => $master->id,
                            'designation' => 'Other',
                            'other_designation' => $role !== 'Other' ? $role : '',
                        ];
                    }

                    return [
                        'designation_id' => $master->id,
                        'designation' => $master->name,
                        'other_designation' => '',
                    ];
                }
            } catch (\Throwable $e) {}
        }

        if ($role === '') {
            return [
                'designation_id' => null,
                'designation' => '',
                'other_designation' => '',
            ];
        }

        // 2. Check if role matches standard options
        if (self::isStandard($role)) {
            $matchedOpt = $role;
            foreach (self::standardOptions() as $opt) {
                if (strcasecmp($opt, $role) === 0) {
                    $matchedOpt = $opt;
                    break;
                }
            }

            $id = null;
            try {
                $id = Designation::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($matchedOpt)])->value('id');
            } catch (\Throwable $e) {}

            return [
                'designation_id' => $id,
                'designation' => $matchedOpt,
                'other_designation' => '',
            ];
        }

        if ($role === 'Other' || strcasecmp($role, 'Other') === 0) {
            $otherId = null;
            try {
                $otherId = Designation::whereRaw('LOWER(TRIM(name)) = ?', ['other'])->value('id');
            } catch (\Throwable $e) {}

            return [
                'designation_id' => $otherId,
                'designation' => 'Other',
                'other_designation' => '',
            ];
        }

        // 3. Check if matches any designation in DB
        try {
            $master = Designation::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($role)])->first();
            if ($master) {
                return [
                    'designation_id' => $master->id,
                    'designation' => $master->name,
                    'other_designation' => '',
                ];
            }
        } catch (\Throwable $e) {}

        // Custom designation saved previously
        $otherId = null;
        try {
            $otherId = Designation::whereRaw('LOWER(TRIM(name)) = ?', ['other'])->value('id');
        } catch (\Throwable $e) {}

        return [
            'designation_id' => $otherId,
            'designation' => 'Other',
            'other_designation' => $role,
        ];
    }
}
