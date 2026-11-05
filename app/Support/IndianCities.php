<?php

namespace App\Support;

use App\Domains\Master\Models\CustomCity;
use Illuminate\Support\Facades\Schema;

class IndianCities
{
    /**
     * Cities for every state and union territory.
     * Source: Countries States Cities Database (ODbL), merged with towns already used on party addresses.
     * https://github.com/dr5hn/countries-states-cities-database
     *
     * @var array<string, array<int, string>>|null
     */
    protected static ?array $stateCities = null;

    /**
     * Get all cities mapped by state, sorted alphabetically (A-Z).
     *
     * @return array<string, array<int, string>>
     */
    public static function all(): array
    {
        $result = [];
        foreach (self::citiesByState() as $state => $cities) {
            $sorted = $cities;
            natcasesort($sorted);
            $result[$state] = array_values(array_unique($sorted));
        }

        return $result;
    }

    /**
     * Get cities list for a given state name, sorted alphabetically (A-Z).
     *
     * @return array<int, string>
     */
    public static function getCitiesForState(?string $state): array
    {
        if (! $state) {
            return [];
        }

        $normalizedState = self::normalizeStateName($state);
        $cities = self::citiesByState()[$normalizedState] ?? [];
        natcasesort($cities);

        return array_values(array_unique($cities));
    }

    /**
     * Verify whether a city belongs to the given state.
     */
    public static function isValidCityForState(?string $city, ?string $state): bool
    {
        $city = trim((string) $city);
        $state = trim((string) $state);

        if ($city === '' || $state === '') {
            return true;
        }

        $citiesByState = self::citiesByState();
        $normalizedState = self::normalizeStateName($state);
        $citiesInTargetState = array_map('mb_strtolower', $citiesByState[$normalizedState] ?? []);

        if (in_array(mb_strtolower($city), $citiesInTargetState, true)) {
            return true;
        }

        foreach ($citiesByState as $otherState => $otherCities) {
            if ($otherState === $normalizedState) {
                continue;
            }

            $lowerOtherCities = array_map('mb_strtolower', $otherCities);
            if (in_array(mb_strtolower($city), $lowerOtherCities, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected static function baseCities(): array
    {
        if (self::$stateCities === null) {
            $path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'indian-cities.json';
            $decoded = json_decode((string) file_get_contents($path), true);
            self::$stateCities = is_array($decoded) ? $decoded : [];
        }

        return self::$stateCities;
    }

    protected static function normalizeStateName(string $state): string
    {
        $state = trim($state);
        foreach (array_keys(self::baseCities()) as $knownState) {
            if (strcasecmp($state, $knownState) === 0) {
                return $knownState;
            }
        }

        return $state;
    }

    /**
     * Built-in cities plus cities saved from the party form.
     *
     * @return array<string, array<int, string>>
     */
    protected static function citiesByState(): array
    {
        $merged = self::baseCities();

        if (! Schema::hasTable('custom_cities')) {
            return $merged;
        }

        $saved = CustomCity::query()->orderBy('name')->get(['state', 'name']);
        foreach ($saved as $city) {
            $state = self::normalizeStateName($city->state);
            $existing = array_map('mb_strtolower', $merged[$state] ?? []);
            if (! in_array(mb_strtolower($city->name), $existing, true)) {
                $merged[$state][] = $city->name;
            }
        }

        return $merged;
    }
}
