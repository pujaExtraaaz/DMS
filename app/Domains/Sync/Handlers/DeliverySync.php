<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Delivery\Models\Delivery;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\Invoice as BooksInvoice;

/** Ensures related sales invoice is pushed when a delivery run is saved. */
class DeliverySync
{
    public const KEY = 'delivery';

    public function __construct(private readonly InvoiceSync $invoices) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Delivery) {
            $this->push($model);
        }
    }

    public function push(Delivery $delivery): void
    {
        $delivery->loadMissing('invoice');
        if (! $delivery->invoice) {
            return;
        }
        $this->invoices->push($delivery->invoice);
        $books = BooksInvoice::query()->find(SyncLinks::acctId(InvoiceSync::KEY, $delivery->invoice));
        if ($books) {
            SyncLinks::store(self::KEY, $delivery, $books);
        }
    }
}
