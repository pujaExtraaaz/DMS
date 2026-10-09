<?php

namespace Tally\Invoicing;

use Tally\Models\Invoice;

/**
 * GST can replace this later. Until then, entered tax stays inside the sales or purchase amount.
 */
interface TaxCalculator
{
    /**
     * @return list<array{ledger_id: int, debit: string, credit: string}>
     */
    public function ledgerLines(Invoice $invoice): array;
}
