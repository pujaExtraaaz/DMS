<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\Invoice as BooksInvoice;
use Tally\Models\Voucher;

/** Outstanding ledger rows are derived — re-push the source invoice/payment/CN. */
class OutstandingLedgerSync
{
    public const KEY = 'outstanding_ledger';

    public function __construct(
        private readonly InvoiceSync $invoices,
        private readonly PaymentSync $payments,
        private readonly CreditNoteSync $creditNotes,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof OutstandingLedger) {
            $this->push($model);
        }
    }

    public function push(OutstandingLedger $row): void
    {
        $ref = $row->reference_type;
        $id = $row->reference_id;
        if (! $ref || ! $id) {
            return;
        }

        if ($ref === Invoice::class || is_a($ref, Invoice::class, true)) {
            $invoice = Invoice::query()->find($id);
            if ($invoice) {
                $this->invoices->push($invoice);
                $books = BooksInvoice::query()->find(SyncLinks::acctId(InvoiceSync::KEY, $invoice));
                if ($books) {
                    SyncLinks::store(self::KEY, $row, $books);
                }
            }

            return;
        }

        if ($ref === Payment::class || is_a($ref, Payment::class, true)) {
            $payment = Payment::query()->find($id);
            if ($payment) {
                $this->payments->push($payment);
                $books = Voucher::query()->find(SyncLinks::acctId(PaymentSync::KEY, $payment));
                if ($books) {
                    SyncLinks::store(self::KEY, $row, $books);
                }
            }

            return;
        }

        if ($ref === CreditNote::class || is_a($ref, CreditNote::class, true)) {
            $cn = CreditNote::query()->find($id);
            if ($cn) {
                $this->creditNotes->push($cn);
                $books = BooksInvoice::query()->find(SyncLinks::acctId(CreditNoteSync::KEY, $cn));
                if ($books) {
                    SyncLinks::store(self::KEY, $row, $books);
                }
            }
        }
    }
}
