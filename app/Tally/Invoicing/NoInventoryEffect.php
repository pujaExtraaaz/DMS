<?php

namespace Tally\Invoicing;

use Tally\Models\Invoice;

class NoInventoryEffect implements InventoryEffect
{
    public function apply(Invoice $invoice): void {}

    public function reverse(Invoice $invoice): void {}
}
