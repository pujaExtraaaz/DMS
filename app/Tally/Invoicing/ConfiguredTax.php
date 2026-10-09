<?php

namespace Tally\Invoicing;

use Tally\Accounting\Money;
use Tally\Models\Invoice;
use Tally\Models\TaxAccount;
use Tally\Tax\TaxComponent;
use Illuminate\Validation\ValidationException;

/**
 * Posts CGST, SGST, IGST, and cess through the voucher engine using the
 * ledgers configured for the company. Manual tax without a tax rate stays
 * inside the sales or purchase amount.
 */
class ConfiguredTax implements TaxCalculator
{
    public function ledgerLines(Invoice $invoice): array
    {
        $invoice->loadMissing('lines');
        $totals = [
            TaxComponent::Cgst->value => 0,
            TaxComponent::Sgst->value => 0,
            TaxComponent::Igst->value => 0,
            TaxComponent::Cess->value => 0,
        ];

        foreach ($invoice->lines as $line) {
            $totals[TaxComponent::Cgst->value] += Money::cents((string) $line->cgst_amount);
            $totals[TaxComponent::Sgst->value] += Money::cents((string) $line->sgst_amount);
            $totals[TaxComponent::Igst->value] += Money::cents((string) $line->igst_amount);
            $totals[TaxComponent::Cess->value] += Money::cents((string) $line->cess_amount);
        }

        $sales = $invoice->kind->debitsParty();
        $lines = [];

        foreach (TaxComponent::cases() as $component) {
            $cents = $totals[$component->value];

            if ($cents === 0) {
                continue;
            }

            $account = TaxAccount::query()
                ->where('company_id', $invoice->company_id)
                ->where('component', $component)
                ->first();

            if (! $account) {
                throw ValidationException::withMessages([
                    'lines' => 'Map a '.$component->label().' ledger before posting this tax.',
                ]);
            }

            $amount = Money::format($cents);
            $lines[] = [
                'ledger_id' => $account->ledger_id,
                'debit' => $sales ? '0.00' : $amount,
                'credit' => $sales ? $amount : '0.00',
            ];
        }

        return $lines;
    }
}
