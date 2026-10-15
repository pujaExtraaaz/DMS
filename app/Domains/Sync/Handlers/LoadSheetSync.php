<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Logistics\Models\LoadSheet;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\Invoice as BooksInvoice;

/** Trip/load sheet → pushes linked delivery invoices into Books. */
class LoadSheetSync
{
    public const KEY = 'load_sheet';

    public function __construct(private readonly InvoiceSync $invoices) {}

    public function sync(Model $model): void
    {
        if ($model instanceof LoadSheet) {
            $this->push($model);
        }
    }

    public function push(LoadSheet $sheet): void
    {
        $sheet->loadMissing(['items.invoice', 'deliveries.invoice']);
        $last = null;
        $invoices = collect();
        if (method_exists($sheet, 'items')) {
            foreach ($sheet->items ?? [] as $item) {
                if ($item->invoice ?? null) {
                    $invoices->push($item->invoice);
                }
            }
        }
        if (method_exists($sheet, 'deliveries')) {
            foreach ($sheet->deliveries ?? [] as $delivery) {
                if ($delivery->invoice) {
                    $invoices->push($delivery->invoice);
                }
            }
        }
        foreach ($invoices->unique('id') as $invoice) {
            $this->invoices->push($invoice);
            $last = BooksInvoice::query()->find(SyncLinks::acctId(InvoiceSync::KEY, $invoice)) ?: $last;
        }
        if ($last) {
            SyncLinks::store(self::KEY, $sheet, $last);
        }
    }
}
