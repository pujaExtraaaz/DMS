<?php

namespace App\Domains\Master\Services;

use App\Domains\Master\Models\Uom;
use App\Domains\Master\Models\UomMapping;
use Illuminate\Support\Collection;

class UomLookupService
{
    /**
     * Standard dictionary of known aliases and abbreviations to possible canonical codes and names.
     */
    public const ALIAS_MAP = [
        // Pieces / Numbers / Units
        'PCS' => ['PCS', 'PIECE', 'PIECES'],
        'PC' => ['PCS', 'PIECE', 'PIECES'],
        'PIECE' => ['PCS', 'PIECE', 'PIECES'],
        'PIECES' => ['PCS', 'PIECE', 'PIECES'],
        'PCS.' => ['PCS', 'PIECE', 'PIECES'],
        'NOS' => ['NOS', 'NUMBER', 'NUMBERS', 'PCS', 'PIECE', 'PIECES'],
        'NO' => ['NOS', 'NUMBER', 'NUMBERS', 'PCS', 'PIECE', 'PIECES'],
        'NO.' => ['NOS', 'NUMBER', 'NUMBERS', 'PCS', 'PIECE', 'PIECES'],
        'NUMBER' => ['NOS', 'NUMBER', 'NUMBERS', 'PCS', 'PIECE', 'PIECES'],
        'NUMBERS' => ['NOS', 'NUMBER', 'NUMBERS', 'PCS', 'PIECE', 'PIECES'],
        'UNT' => ['UNT', 'UNIT', 'UNITS', 'PCS', 'PIECE'],
        'UNIT' => ['UNT', 'UNIT', 'UNITS', 'PCS', 'PIECE'],
        'UNITS' => ['UNT', 'UNIT', 'UNITS', 'PCS', 'PIECE'],
        'EA' => ['EA', 'EACH', 'PCS', 'PIECE'],
        'EACH' => ['EA', 'EACH', 'PCS', 'PIECE'],

        // Boxes & Cartons
        'BOX' => ['BOX', 'BOXES'],
        'BOXES' => ['BOX', 'BOXES'],
        'BX' => ['BOX', 'BOXES'],
        'CTN' => ['CTN', 'CARTON', 'CARTONS', 'BOX'],
        'CARTON' => ['CTN', 'CARTON', 'CARTONS', 'BOX'],
        'CARTONS' => ['CTN', 'CARTON', 'CARTONS', 'BOX'],

        // Cases
        'CASE' => ['CASE', 'CASES'],
        'CASES' => ['CASE', 'CASES'],
        'CS' => ['CASE', 'CASES'],

        // Weight
        'KG' => ['KG', 'KILOGRAM', 'KILOGRAMS'],
        'KGS' => ['KG', 'KILOGRAM', 'KILOGRAMS'],
        'KILOGRAM' => ['KG', 'KILOGRAM', 'KILOGRAMS'],
        'KILOGRAMS' => ['KG', 'KILOGRAM', 'KILOGRAMS'],
        'GM' => ['GMS', 'GM', 'GRAM', 'GRAMS'],
        'GMS' => ['GMS', 'GM', 'GRAM', 'GRAMS'],
        'GRAM' => ['GMS', 'GM', 'GRAM', 'GRAMS'],
        'GRAMS' => ['GMS', 'GM', 'GRAM', 'GRAMS'],
        'TON' => ['TON', 'TONS', 'TONNE', 'TONNES', 'MT'],
        'TONS' => ['TON', 'TONS', 'TONNE', 'TONNES', 'MT'],
        'TONNE' => ['TON', 'TONS', 'TONNE', 'TONNES', 'MT'],
        'TONNES' => ['TON', 'TONS', 'TONNE', 'TONNES', 'MT'],
        'MT' => ['TON', 'TONS', 'TONNE', 'TONNES', 'MT'],
        'QTL' => ['QTL', 'QUINTAL', 'QUINTALS'],
        'QUINTAL' => ['QTL', 'QUINTAL', 'QUINTALS'],

        // Volume
        'LTR' => ['LTR', 'LITRE', 'LITRES', 'LITER', 'LITERS'],
        'LITRE' => ['LTR', 'LITRE', 'LITRES', 'LITER', 'LITERS'],
        'LITRES' => ['LTR', 'LITRE', 'LITRES', 'LITER', 'LITERS'],
        'LITER' => ['LTR', 'LITRE', 'LITRES', 'LITER', 'LITERS'],
        'LITERS' => ['LTR', 'LITRE', 'LITRES', 'LITER', 'LITERS'],
        'ML' => ['ML', 'MILLILITRE', 'MILLILITRES', 'MILLILITER'],
        'MLS' => ['ML', 'MILLILITRE', 'MILLILITRES', 'MILLILITER'],
        'BTL' => ['BTL', 'BOTTLE', 'BOTTLES'],
        'BOTTLE' => ['BTL', 'BOTTLE', 'BOTTLES'],
        'BOTTLES' => ['BTL', 'BOTTLE', 'BOTTLES'],
        'CAN' => ['CAN', 'CANS'],
        'CANS' => ['CAN', 'CANS'],
        'DRM' => ['DRM', 'DRUM', 'DRUMS'],
        'DRUM' => ['DRM', 'DRUM', 'DRUMS'],
        'DRUMS' => ['DRM', 'DRUM', 'DRUMS'],

        // Length & Area
        'MTR' => ['MTR', 'METRE', 'METRES', 'METER', 'METERS'],
        'METER' => ['MTR', 'METRE', 'METRES', 'METER', 'METERS'],
        'METERS' => ['MTR', 'METRE', 'METRES', 'METER', 'METERS'],
        'METRE' => ['MTR', 'METRE', 'METRES', 'METER', 'METERS'],
        'METRES' => ['MTR', 'METRE', 'METRES', 'METER', 'METERS'],
        'CMS' => ['CMS', 'CENTIMETER', 'CENTIMETRES'],
        'SQF' => ['SQF', 'SQFT', 'SQUARE FEET'],
        'SQFT' => ['SQF', 'SQFT', 'SQUARE FEET'],
        'SQM' => ['SQM', 'SQUARE METRE', 'SQUARE METER'],
        'YDS' => ['YDS', 'YARD', 'YARDS'],

        // Job & Services
        'JOB' => ['JOB', 'JOBS'],
        'JOBS' => ['JOB', 'JOBS'],
        'HR' => ['HRS', 'HR', 'HOUR', 'HOURS'],
        'HRS' => ['HRS', 'HR', 'HOUR', 'HOURS'],
        'HOUR' => ['HRS', 'HR', 'HOUR', 'HOURS'],
        'HOURS' => ['HRS', 'HR', 'HOUR', 'HOURS'],
        'DAY' => ['DAY', 'DAYS'],
        'DAYS' => ['DAY', 'DAYS'],

        // Sets / Packs / Bundles
        'SET' => ['SET', 'SETS'],
        'SETS' => ['SET', 'SETS'],
        'PAC' => ['PAC', 'PACK', 'PACKS', 'PKT', 'PACKET', 'PACKETS', 'PKG'],
        'PACK' => ['PAC', 'PACK', 'PACKS', 'PKT', 'PACKET', 'PACKETS', 'PKG'],
        'PACKS' => ['PAC', 'PACK', 'PACKS', 'PKT', 'PACKET', 'PACKETS', 'PKG'],
        'PKT' => ['PAC', 'PACK', 'PACKS', 'PKT', 'PACKET', 'PACKETS', 'PKG'],
        'PACKET' => ['PAC', 'PACK', 'PACKS', 'PKT', 'PACKET', 'PACKETS', 'PKG'],
        'PACKETS' => ['PAC', 'PACK', 'PACKS', 'PKT', 'PACKET', 'PACKETS', 'PKG'],
        'PKG' => ['PKG', 'PACKAGE', 'PACKAGES', 'PAC', 'PACK'],
        'PACKAGE' => ['PKG', 'PACKAGE', 'PACKAGES', 'PAC', 'PACK'],
        'PACKAGES' => ['PKG', 'PACKAGE', 'PACKAGES', 'PAC', 'PACK'],
        'PRS' => ['PRS', 'PAIR', 'PAIRS'],
        'PAIR' => ['PRS', 'PAIR', 'PAIRS'],
        'PAIRS' => ['PRS', 'PAIR', 'PAIRS'],
        'DOZ' => ['DOZ', 'DOZEN', 'DOZENS'],
        'DOZEN' => ['DOZ', 'DOZEN', 'DOZENS'],
        'DOZENS' => ['DOZ', 'DOZEN', 'DOZENS'],
        'ROL' => ['ROL', 'ROLL', 'ROLLS'],
        'ROLL' => ['ROL', 'ROLL', 'ROLLS'],
        'ROLLS' => ['ROL', 'ROLL', 'ROLLS'],
        'BDL' => ['BDL', 'BUNDLE', 'BUNDLES'],
        'BUNDLE' => ['BDL', 'BUNDLE', 'BUNDLES'],
        'BUNDLES' => ['BDL', 'BUNDLE', 'BUNDLES'],
        'BAG' => ['BAG', 'BAGS'],
        'BAGS' => ['BAG', 'BAGS'],
    ];

    /**
     * Resolve any imported UOM string to a Uom model.
     *
     * @param string|null $rawInput The raw imported value (e.g. 'PCS (PCS-PIECES)', 'PIECES', 'JOB')
     * @param Collection|null $uoms Optional preloaded collection of Uom models
     * @param Collection|null $mappings Optional preloaded collection of UomMapping models
     * @return Uom|null The matching UOM model or null if not found
     */
    public static function resolve(?string $rawInput, ?Collection $uoms = null, ?Collection $mappings = null): ?Uom
    {
        $input = trim((string) $rawInput);
        if ($input === '') {
            return null;
        }

        // 1. Check custom database mappings first
        if ($mappings === null) {
            $mapping = UomMapping::with('uom')
                ->whereRaw('LOWER(TRIM(imported_uom)) = ?', [mb_strtolower($input)])
                ->first();
            if ($mapping && $mapping->uom) {
                return $mapping->uom;
            }
        } else {
            $mapped = $mappings->first(function ($m) use ($input) {
                return strcasecmp(trim((string) $m->imported_uom), $input) === 0;
            });
            if ($mapped && $mapped->uom) {
                return $mapped->uom;
            }
        }

        // 2. Load all available UOMs if not supplied
        $uoms = $uoms ?? Uom::all();
        if ($uoms->isEmpty()) {
            return null;
        }

        // 3. Direct match by exact Code (case-insensitive) or Name (case-insensitive)
        $directMatch = self::findInCollection($uoms, $input);
        if ($directMatch) {
            return $directMatch;
        }

        // 4. Handle composite patterns like:
        //    - "PCS (PCS-PIECES)"
        //    - "PIECES (PCS)"
        //    - "BOX (BOXES)"
        //    - "PCS-PIECES"
        $tokens = self::extractTokens($input);
        foreach ($tokens as $token) {
            $match = self::findInCollection($uoms, $token);
            if ($match) {
                return $match;
            }
        }

        // 5. Check standard alias dictionary for the full input and extracted tokens
        $candidateInputs = array_merge([$input], $tokens);
        foreach ($candidateInputs as $cand) {
            $key = strtoupper(trim($cand));
            if (isset(self::ALIAS_MAP[$key])) {
                foreach (self::ALIAS_MAP[$key] as $target) {
                    $match = self::findInCollection($uoms, $target);
                    if ($match) {
                        return $match;
                    }
                }
            }
        }

        // 6. Plural / Singular stemming fallback
        // If input ends with 'S' (e.g. 'PIECES', 'BOXES', 'JOBS')
        $singular = self::stemSingular($input);
        if ($singular !== $input) {
            $match = self::findInCollection($uoms, $singular);
            if ($match) {
                return $match;
            }
            $singularUpper = strtoupper($singular);
            if (isset(self::ALIAS_MAP[$singularUpper])) {
                foreach (self::ALIAS_MAP[$singularUpper] as $target) {
                    $match = self::findInCollection($uoms, $target);
                    if ($match) {
                        return $match;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Find a UOM in the collection matching code or name.
     */
    protected static function findInCollection(Collection $uoms, string $term): ?Uom
    {
        $term = trim($term);
        if ($term === '') {
            return null;
        }

        $termLower = mb_strtolower($term);
        $termUpper = mb_strtoupper($term);

        return $uoms->first(function (Uom $uom) use ($termLower, $termUpper) {
            $codeUpper = mb_strtoupper(trim((string) $uom->code));
            $nameLower = mb_strtolower(trim((string) $uom->name));

            return $codeUpper === $termUpper || $nameLower === $termLower;
        });
    }

    /**
     * Extract constituent tokens from strings like "PCS (PCS-PIECES)" or "PCS-PIECES".
     *
     * @return string[]
     */
    public static function extractTokens(string $input): array
    {
        $tokens = [];

        // Check for "CODE (DESCRIPTION)" or "NAME (CODE)" pattern
        if (preg_match('/^([^\(\)]+)\s*\((.+)\)$/', $input, $matches)) {
            $outer = trim($matches[1]);
            $inner = trim($matches[2]);

            if ($outer !== '') {
                $tokens[] = $outer;
            }

            // Inside parentheses, e.g. "PCS-PIECES" or "BOXES"
            $innerParts = preg_split('/[\s\-\/,]+/', $inner);
            foreach ($innerParts as $part) {
                $part = trim($part);
                if ($part !== '' && ! in_array($part, $tokens, true)) {
                    $tokens[] = $part;
                }
            }
        }

        // Check for hyphen or slash separated e.g. "PCS-PIECES"
        $parts = preg_split('/[\-\/,]+/', $input);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && ! in_array($part, $tokens, true)) {
                $tokens[] = $part;
            }
        }

        return $tokens;
    }

    /**
     * Simple singular stemming.
     */
    protected static function stemSingular(string $word): string
    {
        $upper = strtoupper(trim($word));
        if (str_ends_with($upper, 'IES') && strlen($upper) > 4) {
            return substr($word, 0, -3).'y';
        }
        if (str_ends_with($upper, 'ES') && strlen($upper) > 3) {
            return substr($word, 0, -2);
        }
        if (str_ends_with($upper, 'S') && strlen($upper) > 2) {
            return substr($word, 0, -1);
        }

        return $word;
    }

    /**
     * Suggest an appropriate code for a missing UOM.
     */
    public static function suggestCode(string $rawInput): string
    {
        $tokens = self::extractTokens($rawInput);
        $candidate = ! empty($tokens) ? $tokens[0] : trim($rawInput);

        // Remove punctuation
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $candidate);
        $clean = strtoupper($clean);

        if ($clean !== '') {
            return mb_substr($clean, 0, 20);
        }

        return 'UOM';
    }

    /**
     * Suggest an appropriate descriptive name for a missing UOM.
     */
    public static function suggestName(string $rawInput): string
    {
        $tokens = self::extractTokens($rawInput);
        if (count($tokens) > 1) {
            // If has tokens like "PCS", "PIECES", prefer the longer more descriptive token as name
            usort($tokens, fn ($a, $b) => strlen($b) <=> strlen($a));
            return ucfirst(strtolower($tokens[0]));
        }

        $candidate = trim($rawInput);
        // Strip parentheses if any
        $candidate = preg_replace('/[()]/', ' ', $candidate);
        $candidate = preg_replace('/\s+/', ' ', trim($candidate));

        return ucfirst(strtolower($candidate));
    }
}

