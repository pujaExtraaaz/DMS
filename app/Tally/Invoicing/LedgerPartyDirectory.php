<?php

namespace Tally\Invoicing;

use Tally\Models\Company;
use Tally\Models\Ledger;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LedgerPartyDirectory implements PartyDirectory
{
    public function options(Company $company, InvoiceKind $kind): Collection
    {
        return $this->active($company)
            ->filter(fn (Ledger $ledger) => $ledger->belongsToGroup($kind->partyGroupCode()))
            ->values();
    }

    public function find(Company $company, InvoiceKind $kind, int $ledgerId): Ledger
    {
        $ledger = $this->active($company)->first(fn (Ledger $ledger) => $ledger->id === $ledgerId && $ledger->belongsToGroup($kind->partyGroupCode()));

        if (! $ledger) {
            throw ValidationException::withMessages([
                'party_ledger_id' => $kind->partyError(),
            ]);
        }

        return $ledger;
    }

    /**
     * @return Collection<int, Ledger>
     */
    private function active(Company $company): Collection
    {
        return $company->ledgers()
            ->with('accountGroup.parent')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
