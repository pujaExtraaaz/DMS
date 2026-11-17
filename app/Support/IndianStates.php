<?php

namespace App\Support;

class IndianStates
{
    /**
     * Complete list of Indian States and Union Territories with standard GST codes.
     */
    public static function all(): array
    {
        return [
            '01' => 'Jammu and Kashmir',
            '02' => 'Himachal Pradesh',
            '03' => 'Punjab',
            '04' => 'Chandigarh',
            '05' => 'Uttarakhand',
            '06' => 'Haryana',
            '07' => 'Delhi',
            '08' => 'Rajasthan',
            '09' => 'Uttar Pradesh',
            '10' => 'Bihar',
            '11' => 'Sikkim',
            '12' => 'Arunachal Pradesh',
            '13' => 'Nagaland',
            '14' => 'Manipur',
            '15' => 'Mizoram',
            '16' => 'Tripura',
            '17' => 'Meghalaya',
            '18' => 'Assam',
            '19' => 'West Bengal',
            '20' => 'Jharkhand',
            '21' => 'Odisha',
            '22' => 'Chhattisgarh',
            '23' => 'Madhya Pradesh',
            '24' => 'Gujarat',
            '26' => 'Dadra and Nagar Haveli and Daman and Diu',
            '27' => 'Maharashtra',
            // Legacy GST code 28 (undivided AP) maps to modern Andhra Pradesh (37).
            '28' => 'Andhra Pradesh',
            '29' => 'Karnataka',
            '30' => 'Goa',
            '31' => 'Lakshadweep',
            '32' => 'Kerala',
            '33' => 'Tamil Nadu',
            '34' => 'Puducherry',
            '35' => 'Andaman and Nicobar Islands',
            '36' => 'Telangana',
            '37' => 'Andhra Pradesh',
            '38' => 'Ladakh',
            '97' => 'Other Territory',
        ];
    }

    public static function names(): array
    {
        return array_values(self::all());
    }

    /**
     * Complete list of Indian States and Union Territories sorted alphabetically by name (A-Z).
     *
     * @return array<string, string> Key: GST code, Value: State/UT name
     */
    public static function allAlphabetical(): array
    {
        // Deduplicate by state name (e.g. legacy GST 28 + modern 37 both = Andhra Pradesh).
        $unique = [];
        foreach (self::all() as $code => $name) {
            $key = mb_strtolower(trim($name));
            if (! isset($unique[$key])) {
                $unique[$key] = [(string) $code, $name];
            }
        }

        $states = [];
        foreach ($unique as [$code, $name]) {
            $states[$code] = $name;
        }

        asort($states, SORT_NATURAL | SORT_FLAG_CASE);

        return $states;
    }

    /**
     * Names of all states sorted alphabetically.
     *
     * @return array<int, string>
     */
    public static function namesAlphabetical(): array
    {
        return array_values(self::allAlphabetical());
    }

    /**
     * State options formatted with name and code, sorted alphabetically (A-Z).
     *
     * @return array<int, array{code: string, name: string}>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::allAlphabetical() as $code => $name) {
            $options[] = [
                'code' => (string) str_pad((string) $code, 2, '0', STR_PAD_LEFT),
                'name' => $name,
            ];
        }
        return $options;
    }

    /**
     * Lookup state GST code by name.
     */
    public static function codeFromName(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        $normalized = self::normalizeName($name);

        // Prefer the modern Andhra Pradesh GST code when both 28 and 37 match.
        if (strcasecmp($normalized, 'Andhra Pradesh') === 0) {
            return '37';
        }

        foreach (self::all() as $code => $stateName) {
            if (strcasecmp($stateName, $normalized) === 0) {
                return (string) str_pad((string) $code, 2, '0', STR_PAD_LEFT);
            }
        }

        return null;
    }

    /**
     * Normalize legacy / alternate state labels to canonical names.
     */
    public static function normalizeName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        if (preg_match('/^andhra\s+pradesh(\s*\(old\))?$/i', $name)) {
            return 'Andhra Pradesh';
        }

        return $name;
    }

    public static function stateFromGstin(?string $gstin): ?string
    {
        if (! $gstin || strlen(trim($gstin)) < 2) {
            return null;
        }

        $code = substr(trim($gstin), 0, 2);
        return self::all()[$code] ?? null;
    }
}