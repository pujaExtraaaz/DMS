<?php

namespace Tally\Invoicing;

use Tally\Accounting\Money;
use Tally\Models\Company;
use Tally\Models\Ledger;
use Tally\Models\TaxRate;
use Tally\Tax\SupplyType;
use Tally\Tax\TaxCalculationService;
use Tally\Tax\TaxPricing;
use Tally\Tax\TaxRounding;
use Illuminate\Validation\ValidationException;

class InvoiceTax
{
    public function __construct(private readonly TaxCalculationService $calculator) {}

    /**
     * @param  array<string, mixed>  $compiled
     * @return array<string, mixed>
     */
    public function apply(Company $company, Ledger $party, array $compiled): array
    {
        $intra = null;
        $used = false;
        $taxTotal = 0;
        $addedTax = 0;
        $pricing = $company->tax_pricing ?? TaxPricing::Exclusive;
        $rounding = $company->tax_rounding ?? TaxRounding::Paisa;
        $inclusive = $pricing === TaxPricing::Inclusive;
        $lines = [];

        foreach ($compiled['lines'] as $index => $line) {
            $rateId = $line['tax_rate_id'] ?? null;

            if (! $rateId) {
                $line['cgst_amount'] = '0.00';
                $line['sgst_amount'] = '0.00';
                $line['igst_amount'] = '0.00';
                $line['cess_amount'] = '0.00';
                $manual = Money::cents((string) $line['tax_amount']);
                $taxTotal += $manual;
                $addedTax += $manual;
                $lines[] = $line;

                continue;
            }

            $used = true;

            if ($intra === null) {
                $intra = $this->calculator->intraState($company->state, $party->state);

                if ($intra === null) {
                    throw ValidationException::withMessages([
                        'lines' => 'Set the company state and the party ledger state before using a tax rate. Matching states use CGST and SGST. Different states use IGST.',
                    ]);
                }
            }

            $rate = TaxRate::query()
                ->where('company_id', $company->id)
                ->whereKey($rateId)
                ->where('is_active', true)
                ->first();

            if (! $rate) {
                throw ValidationException::withMessages([
                    "lines.$index.tax_rate_id" => 'Select an active tax rate from the current company.',
                ]);
            }

            $gross = (string) $line['taxable_amount'];
            $breakdown = $inclusive
                ? $this->calculator->fromInclusive($gross, $rate, $intra, $rounding)
                : $this->calculator->calculate($gross, $rate, $intra, $rounding);
            $line['taxable_amount'] = $breakdown->taxable;
            $line['cgst_amount'] = $breakdown->cgst;
            $line['sgst_amount'] = $breakdown->sgst;
            $line['igst_amount'] = $breakdown->igst;
            $line['cess_amount'] = $breakdown->cess;
            $line['tax_amount'] = $breakdown->tax;
            $line['line_total'] = $inclusive ? $gross : $breakdown->total;
            $taxCents = Money::cents($breakdown->tax);
            $taxTotal += $taxCents;

            if (! $inclusive) {
                $addedTax += $taxCents;
            }

            $lines[] = $line;
        }

        $grand = Money::cents((string) $compiled['subtotal'])
            - Money::cents((string) $compiled['discount_total'])
            + $addedTax;

        return [
            'lines' => $lines,
            'subtotal' => $compiled['subtotal'],
            'discount_total' => $compiled['discount_total'],
            'tax_total' => Money::format($taxTotal),
            'grand_total' => Money::format($grand),
            'supply_type' => ! $used || $intra === null ? null : ($intra ? SupplyType::Intra : SupplyType::Inter),
            'place_of_supply' => $used ? $party->state : null,
        ];
    }
}
