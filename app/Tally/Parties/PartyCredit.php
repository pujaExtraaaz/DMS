<?php

namespace Tally\Parties;

use Tally\Accounting\Money;
use Tally\Accounting\OpeningBalanceType;
use Tally\Accounting\VoucherStatus;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\Invoice;
use Tally\Models\Ledger;
use Tally\Models\VoucherEntry;
use Tally\Preferences\PreferenceStore;
use Illuminate\Validation\ValidationException;

class PartyCredit
{
    public function __construct(private readonly PreferenceStore $preferences) {}

    public function assertWithinLimit(Invoice $invoice, Ledger $party): void
    {
        if ($invoice->kind !== InvoiceKind::Sales || ! $this->preferences->enabled($invoice->company, 'sales.enforce_credit_limit')) {
            return;
        }

        $limit = Money::cents((string) ($party->credit_limit ?? '0'));

        if ($limit <= 0) {
            return;
        }

        $opening = Money::cents((string) $party->opening_balance);
        if ($party->opening_balance_type === OpeningBalanceType::Credit) {
            $opening = -$opening;
        }

        $movement = (int) VoucherEntry::query()
            ->where('ledger_id', $party->id)
            ->whereHas('voucher', function ($query) use ($invoice): void {
                $query->where('company_id', $invoice->company_id)
                    ->where('financial_year_id', $invoice->financial_year_id)
                    ->where('status', VoucherStatus::Posted);
            })
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
            ->value('balance');

        $next = $opening + $this->cents($movement) + Money::cents((string) $invoice->grand_total);

        if ($next > $limit) {
            throw ValidationException::withMessages([
                'party_ledger_id' => 'This invoice would take '.$party->name.' over the credit limit of '.Money::format($limit).'.',
            ]);
        }
    }

    private function cents(mixed $value): int
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return 0;
        }

        if (! str_contains($raw, '.')) {
            $raw .= '.00';
        }

        [$whole, $fraction] = explode('.', $raw, 2);
        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);

        return Money::cents(((int) $whole).'.'.$fraction);
    }
}
