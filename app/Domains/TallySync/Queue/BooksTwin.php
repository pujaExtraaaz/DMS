<?php

namespace App\Domains\TallySync\Queue;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product as DmsProduct;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sync\Handlers\CreditNoteSync;
use App\Domains\Sync\Handlers\CustomerSync;
use App\Domains\Sync\Handlers\InvoiceSync;
use App\Domains\Sync\Handlers\PaymentSync;
use App\Domains\Sync\Handlers\ProductSync;
use App\Domains\Sync\Handlers\PurchaseInvoiceSync;
use App\Domains\Sync\Handlers\WarehouseSync;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\Invoice as BooksInvoice;
use Tally\Models\Ledger;
use Tally\Models\Party;
use Tally\Models\Product as BooksProduct;
use Tally\Models\Unit;
use Tally\Models\Godown;
use Tally\Models\Voucher;

/**
 * One Tally document per business event.
 * DMS rows that already have a Books voucher or master are not queued again.
 */
class BooksTwin
{
    public function target(Model $document): Model|string
    {
        return match ($document::class) {
            Invoice::class => $this->invoiceVoucher($document, InvoiceSync::KEY),
            PurchaseInvoice::class => $this->invoiceVoucher($document, PurchaseInvoiceSync::KEY),
            CreditNote::class => $this->invoiceVoucher($document, CreditNoteSync::KEY),
            Payment::class => $this->paymentVoucher($document),
            PurchaseOrder::class => 'dms',
            DmsProduct::class => $this->linked(ProductSync::KEY, $document, BooksProduct::class, (bool) $document->company_id),
            Customer::class => $this->customerLedger($document),
            Uom::class => $this->linkedUnit($document),
            Warehouse::class => $this->linked(WarehouseSync::KEY, $document, Godown::class, (bool) $document->company_id),
            default => 'dms',
        };
    }

    private function invoiceVoucher(Model $document, string $key): Model|string
    {
        $booksId = SyncLinks::acctId($key, $document);
        if (! $booksId) {
            return 'skip';
        }

        $invoice = BooksInvoice::query()->find($booksId);
        if (! $invoice?->voucher_id) {
            return 'skip';
        }

        return Voucher::query()->find($invoice->voucher_id) ?: 'skip';
    }

    private function paymentVoucher(Payment $payment): Model|string
    {
        $id = SyncLinks::acctId(PaymentSync::KEY, $payment);

        return $id ? (Voucher::query()->find($id) ?: 'skip') : 'skip';
    }

    private function customerLedger(Customer $customer): Model|string
    {
        $partyId = SyncLinks::acctId(CustomerSync::KEY, $customer);
        if (! $partyId) {
            return $customer->company_id ? 'skip' : 'dms';
        }

        $ledgerId = Party::query()->whereKey($partyId)->value('ledger_id');

        return $ledgerId ? (Ledger::query()->find($ledgerId) ?: 'skip') : 'skip';
    }

    private function linked(string $key, Model $document, string $class, bool $expectsBooks): Model|string
    {
        $id = SyncLinks::acctId($key, $document);
        if (! $id) {
            return $expectsBooks ? 'skip' : 'dms';
        }

        return $class::query()->find($id) ?: 'skip';
    }

    private function linkedUnit(Uom $uom): Model|string
    {
        $id = DB::table('sync_entity_links')
            ->where('dms_type', $uom::class)
            ->where('dms_id', $uom->getKey())
            ->where('entity_key', 'like', 'uom:%')
            ->value('acct_id');

        if (! $id) {
            return 'dms';
        }

        return Unit::query()->find($id) ?: 'skip';
    }
}