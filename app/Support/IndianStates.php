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
            '28' => 'Andhra Pradesh (Old)',
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
        $states = self::all();
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
        foreach (self::all() as $code => $stateName) {
            if (strcasecmp($stateName, trim($name)) === 0) {
                return $code;
            }
        }
        return null;
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