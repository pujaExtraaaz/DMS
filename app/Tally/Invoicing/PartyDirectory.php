<?php

namespace Tally\Invoicing;

use Tally\Models\Company;
use Tally\Models\Ledger;
use Illuminate\Support\Collection;

/**
 * Customers and suppliers are ledgers today.
 * A later Party Master can implement this and still return the ledger each party posts to.
 */
interface PartyDirectory
{
    /**
     * @return Collection<int, Ledger>
     */
    public function options(Company $company, InvoiceKind $kind): Collection;

    public function find(Company $company, InvoiceKind $kind, int $ledgerId): Ledger;
}
