<?php

namespace Tally\Invoicing;

use Tally\Models\Company;
use Tally\Models\Ledger;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TradingAccounts
{
    /**
     * @return Collection<int, Ledger>
     */
    public function options(Company $company, InvoiceKind $kind): Collection
    {
        return $company->ledgers()
            ->with('accountGroup.parent')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(fn (Ledger $ledger) => $ledger->belongsToGroup($kind->accountGroupCode()))
            ->values();
    }

    public function find(Company $company, InvoiceKind $kind, int $ledgerId): Ledger
    {
        $ledger = $this->options($company, $kind)->first(fn (Ledger $ledger) => $ledger->id === $ledgerId);

        if (! $ledger) {
            throw ValidationException::withMessages([
                'account_ledger_id' => $kind->accountError(),
            ]);
        }

        return $ledger;
    }
}
