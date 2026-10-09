<?php

namespace Tally\Invoicing;

use Tally\Models\Company;
use Tally\Models\Godown;
use Tally\Models\HsnSac;
use Tally\Models\Product;
use Tally\Models\TaxRate;
use Illuminate\Validation\ValidationException;

/**
 * Resolves invoice lines to products, godowns, and tax configuration in the current company.
 */
class InvoiceLinePrep
{
    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    public function normalize(Company $company, array $lines): array
    {
        $prepared = [];

        foreach (array_values($lines) as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $productId = $this->id($line['product_id'] ?? null);
            $godownId = $this->id($line['godown_id'] ?? null);
            $taxRateId = $this->id($line['tax_rate_id'] ?? null);
            $hsnId = $this->id($line['hsn_sac_id'] ?? null);
            $product = null;

            if ($productId) {
                $product = Product::query()->where('company_id', $company->id)->whereKey($productId)->first();

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        "lines.$index.product_id" => 'Select an active product from the current company.',
                    ]);
                }

                if (trim((string) ($line['item_name'] ?? '')) === '') {
                    $line['item_name'] = $product->name;
                }

                $taxRateId ??= $product->tax_rate_id;
                $hsnId ??= $product->hsn_sac_id;
            }

            if ($godownId) {
                $godown = Godown::query()->where('company_id', $company->id)->whereKey($godownId)->first();

                if (! $godown || ! $godown->is_active) {
                    throw ValidationException::withMessages([
                        "lines.$index.godown_id" => 'Select an active godown from the current company.',
                    ]);
                }
            }

            if ($product && ! $godownId) {
                throw ValidationException::withMessages([
                    "lines.$index.godown_id" => 'Select a godown for '.$product->name.'.',
                ]);
            }

            if (! $product && $godownId) {
                throw ValidationException::withMessages([
                    "lines.$index.product_id" => 'Select a product when a godown is set.',
                ]);
            }

            if ($taxRateId && ! TaxRate::query()->where('company_id', $company->id)->whereKey($taxRateId)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    "lines.$index.tax_rate_id" => 'Select an active tax rate from the current company.',
                ]);
            }

            if ($hsnId && ! HsnSac::query()->where('company_id', $company->id)->whereKey($hsnId)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    "lines.$index.hsn_sac_id" => 'Select an active HSN or SAC from the current company.',
                ]);
            }

            $line['product_id'] = $productId;
            $line['godown_id'] = $godownId;
            $line['tax_rate_id'] = $taxRateId;
            $line['hsn_sac_id'] = $hsnId;
            $prepared[] = $line;
        }

        return $prepared;
    }

    private function id(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
