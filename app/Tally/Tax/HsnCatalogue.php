<?php

namespace Tally\Tax;

use Tally\Models\Company;
use Tally\Models\HsnSac;
use Tally\Models\TaxCategory;
use Tally\Models\TaxRate;
use Illuminate\Support\Facades\DB;

/**
 * Common HSN and SAC codes with the GST rate used for that code.
 * A code that is not in this list is left for the company to rate itself.
 */
final class HsnCatalogue
{
    /**
     * @var array<string, array{description: string, rate: float, kind: string}>
     */
    private const CODES = [
        '0401' => ['description' => 'Milk and cream', 'rate' => 0, 'kind' => 'hsn'],
        '1006' => ['description' => 'Rice', 'rate' => 0, 'kind' => 'hsn'],
        '4901' => ['description' => 'Printed books', 'rate' => 0, 'kind' => 'hsn'],
        '0901' => ['description' => 'Coffee', 'rate' => 5, 'kind' => 'hsn'],
        '1701' => ['description' => 'Cane or beet sugar', 'rate' => 5, 'kind' => 'hsn'],
        '3004' => ['description' => 'Medicaments', 'rate' => 5, 'kind' => 'hsn'],
        '6109' => ['description' => 'T-shirts and singlets', 'rate' => 5, 'kind' => 'hsn'],
        '6203' => ['description' => 'Men\'s suits and trousers', 'rate' => 5, 'kind' => 'hsn'],
        '7113' => ['description' => 'Articles of jewellery', 'rate' => 3, 'kind' => 'hsn'],
        '2106' => ['description' => 'Food preparations', 'rate' => 18, 'kind' => 'hsn'],
        '2523' => ['description' => 'Cement', 'rate' => 18, 'kind' => 'hsn'],
        '3304' => ['description' => 'Beauty preparations', 'rate' => 18, 'kind' => 'hsn'],
        '3923' => ['description' => 'Plastic packing goods', 'rate' => 18, 'kind' => 'hsn'],
        '3926' => ['description' => 'Other articles of plastic', 'rate' => 18, 'kind' => 'hsn'],
        '4819' => ['description' => 'Cartons and boxes', 'rate' => 18, 'kind' => 'hsn'],
        '7214' => ['description' => 'Bars and rods of iron or steel', 'rate' => 18, 'kind' => 'hsn'],
        '7326' => ['description' => 'Articles of iron or steel', 'rate' => 18, 'kind' => 'hsn'],
        '8471' => ['description' => 'Automatic data processing machines', 'rate' => 18, 'kind' => 'hsn'],
        '8517' => ['description' => 'Telephones and mobiles', 'rate' => 18, 'kind' => 'hsn'],
        '8528' => ['description' => 'Monitors and projectors', 'rate' => 18, 'kind' => 'hsn'],
        '8708' => ['description' => 'Parts of motor vehicles', 'rate' => 18, 'kind' => 'hsn'],
        '9403' => ['description' => 'Furniture', 'rate' => 18, 'kind' => 'hsn'],
        '9405' => ['description' => 'Lamps and lighting', 'rate' => 18, 'kind' => 'hsn'],
        '9954' => ['description' => 'Construction services', 'rate' => 18, 'kind' => 'sac'],
        '9963' => ['description' => 'Accommodation services', 'rate' => 5, 'kind' => 'sac'],
        '996331' => ['description' => 'Restaurant services', 'rate' => 5, 'kind' => 'sac'],
        '9965' => ['description' => 'Goods transport', 'rate' => 5, 'kind' => 'sac'],
        '9971' => ['description' => 'Financial services', 'rate' => 18, 'kind' => 'sac'],
        '9972' => ['description' => 'Real estate services', 'rate' => 18, 'kind' => 'sac'],
        '997212' => ['description' => 'Rental of commercial property', 'rate' => 18, 'kind' => 'sac'],
        '9983' => ['description' => 'Professional and consulting services', 'rate' => 18, 'kind' => 'sac'],
        '998314' => ['description' => 'Information technology consulting', 'rate' => 18, 'kind' => 'sac'],
        '9984' => ['description' => 'Telecommunications', 'rate' => 18, 'kind' => 'sac'],
        '9985' => ['description' => 'Support services', 'rate' => 18, 'kind' => 'sac'],
        '9992' => ['description' => 'Education services', 'rate' => 0, 'kind' => 'sac'],
    ];

    /**
     * @return list<array{code: string, description: string, rate: float, kind: string}>
     */
    public static function search(string $digits): array
    {
        $digits = preg_replace('/\D/', '', $digits) ?? '';

        if (strlen($digits) < 2) {
            return [];
        }

        $rows = [];

        foreach (self::CODES as $code => $row) {
            if (! str_starts_with($code, $digits) && ! str_contains($code, $digits)) {
                continue;
            }

            $rows[] = ['code' => $code] + $row;

            if (count($rows) === 12) {
                break;
            }
        }

        return $rows;
    }

    public static function adopt(Company $company, string $code): ?HsnSac
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        $row = self::CODES[$code] ?? null;

        if ($row === null) {
            return null;
        }

        return DB::transaction(function () use ($company, $code, $row) {
            $existing = HsnSac::query()->where('company_id', $company->id)->where('code', $code)->first();
            $rate = self::rate($company, (float) $row['rate']);

            if ($existing) {
                if ($existing->tax_rate_id === null) {
                    $existing->update(['tax_rate_id' => $rate->id]);
                }

                return $existing->fresh();
            }

            return HsnSac::query()->create([
                'company_id' => $company->id,
                'tax_rate_id' => $rate->id,
                'code' => $code,
                'kind' => $row['kind'] === 'sac' ? HsnKind::Sac : HsnKind::Hsn,
                'description' => $row['description'],
                'is_active' => true,
            ]);
        });
    }

    private static function rate(Company $company, float $percent): TaxRate
    {
        $igst = number_format($percent, 4, '.', '');
        $half = number_format($percent / 2, 4, '.', '');
        $found = TaxRate::query()
            ->where('company_id', $company->id)
            ->where('igst_rate', $igst)
            ->where('cess_rate', '0.0000')
            ->first();

        if ($found) {
            return $found;
        }

        $category = TaxCategory::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => 'GST'],
            ['code' => 'GST', 'is_active' => true],
        );
        $label = 'GST '.rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.');

        return TaxRate::query()->create([
            'company_id' => $company->id,
            'tax_category_id' => $category->id,
            'name' => $label,
            'code' => 'GST'.(int) $percent,
            'cgst_rate' => $half,
            'sgst_rate' => $half,
            'igst_rate' => $igst,
            'cess_rate' => '0.0000',
            'is_active' => true,
        ]);
    }
}
