<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Settlement\Models\Settlement;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\Invoice as BooksInvoice;

/** Trip settlement → pushes related sales invoices into Books. */
class SettlementSync
{
    public const KEY = 'settlement';

    public function __construct(private readonly InvoiceSync $invoices) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Settlement) {
            $this->push($model);
        }
    }

    public function push(Settlement $settlement): void
    {
        $settlement->loadMissing('lines.invoice');
        $last = null;
        foreach ($settlement->lines as $line) {
            if (! $line->invoice) {
                continue;
            }
            $this->invoices->push($line->invoice);
            $last = BooksInvoice::query()->find(SyncLinks::acctId(InvoiceSync::KEY, $line->invoice)) ?: $last;
        }
        if ($last) {
            SyncLinks::store(self::KEY, $settlement, $last);
        }
    }
}
