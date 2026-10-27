<?php

namespace Tally\Parties;

use Tally\Accounting\BillAllocationService;
use Tally\Accounting\BillService;
use Tally\Accounting\Money;
use Tally\Accounting\OpeningBalanceType;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\Bill;
use Tally\Models\Invoice;
use Tally\Models\Ledger;
use Tally\Preferences\PreferenceStore;
use Tally\Support\Queries\DateRange;
use Illuminate\Validation\ValidationException;

class PartyCredit
{
    public function __construct(
        private readonly PreferenceStore $preferences,
        private readonly BillService $bills,
        private readonly BillAllocationService $allocations,
    ) {}

    public function assertWithinLimit(Invoice $invoice, Ledger $party): void
    {
        if ($invoice->kind !== InvoiceKind::Sales || ! $this->preferences->enabled($invoice->company, 'sales.enforce_credit_limit')) {
            return;
        }

        $limit = Money::cents((string) ($party->credit_limit ?? '0'));

        if ($limit <= 0) {
            return;
        }

        $exposure = $this->billWiseExposureCents($party) + Money::cents((string) $invoice->grand_total);

        if ($exposure > $limit) {
            $message = 'This invoice would take '.$party->name.' over the credit limit of '.Money::format($limit)
                .' (bill-wise exposure '.Money::format($exposure - Money::cents((string) $invoice->grand_total)).').';
            $action = (string) $this->preferences->get($invoice->company, null, 'sales.credit_limit_action');

            if ($action === 'warn') {
                session()->flash('warning', $message);

                return;
            }

            throw ValidationException::withMessages([
                'party_ledger_id' => $message,
            ]);
        }
    }

    /**
     * Open bills outstanding minus unallocated advances for the party.
     */
    public function billWiseExposureCents(Ledger $party, ?string $asOf = null): int
    {
        $asOf ??= now()->toDateString();
        $this->bills->ensureOpening($party);

        $open = 0;
        $bills = Bill::query()
            ->where('company_id', $party->company_id)
            ->where('ledger_id', $party->id)
            ->where('side', OpeningBalanceType::Debit)
            ->tap(fn ($query) => DateRange::apply($query, 'bill_date', null, $asOf))
            ->get();

        foreach ($bills as $bill) {
            if (! $bill->isActive()) {
                continue;
            }
            $open += max(0, $this->allocations->outstandingCents($bill, $asOf));
        }

        return max(0, $open);
    }

    public function availableCredit(Ledger $party): ?string
    {
        $limit = Money::cents((string) ($party->credit_limit ?? '0'));

        if ($limit <= 0) {
            return null;
        }

        return Money::format(max(0, $limit - $this->billWiseExposureCents($party)));
    }
}
