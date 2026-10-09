<?php

namespace Tally\Invoicing;

use Tally\Models\Invoice;

class PlaceholderTax implements TaxCalculator
{
    public function ledgerLines(Invoice $invoice): array
    {
        return [];
    }
}
