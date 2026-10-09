<?php

namespace Tally\Invoicing;

use Tally\Models\Invoice;

/**
 * Stock posting can implement this later. The invoice transaction already calls it.
 */
interface InventoryEffect
{
    public function apply(Invoice $invoice): void;

    public function reverse(Invoice $invoice): void;
}
