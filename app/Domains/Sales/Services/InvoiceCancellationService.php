<?php

namespace App\Domains\Sales\Services;

use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sync\Handlers\InvoiceSync;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceCancellationService
{
    public function __construct(
        protected OutstandingLedgerService $outstandingLedgerService,
    ) {}

    public function cancel(Invoice $invoice, User $actor, ?string $reason = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $actor, $reason) {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'cancelled') {
                return $locked;
            }

            if (! in_array($locked->status, ['draft', 'issued', 'partial', 'paid'], true)) {
                throw new InvalidArgumentException("Invoice cannot be cancelled in status '{$locked->status}'.");
            }

            if ((float) $locked->paid_amount > 0.0001) {
                throw new InvalidArgumentException('Invoice has receipts allocated. Reverse or reallocate payments before cancelling.');
            }

            if (Payment::query()->where('invoice_id', $locked->id)->where('status', 'completed')->exists()) {
                throw new InvalidArgumentException('Invoice has completed payments. Cancel or reverse those receipts first.');
            }

            if ($locked->isEInvoiceGenerated() || $locked->isEWayBillGenerated()) {
                throw new InvalidArgumentException('Cancel IRN / E-Way Bill statutory documents before cancelling this tax invoice.');
            }

            $activeCreditNotes = $locked->creditNotes()
                ->whereIn('status', ['draft', 'approved', 'posted'])
                ->count();
            if ($activeCreditNotes > 0) {
                throw new InvalidArgumentException('Invoice has linked credit notes. Cancel or reverse those credit notes before cancelling this tax invoice.');
            }

            // Reverse stock movements (sale outs are typically negative qty).
            $movements = StockMovement::query()
                ->where('reference_type', $locked->getMorphClass())
                ->where('reference_id', $locked->id)
                ->get();

            foreach ($movements as $movement) {
                $qty = (float) $movement->quantity;
                if ($qty == 0.0) {
                    $movement->delete();
                    continue;
                }

                $stockLevel = StockLevel::query()
                    ->where('warehouse_id', $movement->warehouse_id)
                    ->where('product_id', $movement->product_id)
                    ->where('uom_id', $movement->uom_id)
                    ->first();

                if ($stockLevel) {
                    // Sale out reduced stock; restoring means adding abs(qty) when qty was negative,
                    // or subtracting when an erroneous positive movement was stored.
                    if ($qty < 0) {
                        $stockLevel->increment('quantity', abs($qty));
                    } else {
                        $stockLevel->decrement('quantity', $qty);
                    }
                }

                $movement->delete();
            }

            $outstanding = max(0.0, (float) $locked->grand_total - (float) $locked->paid_amount);
            if ($outstanding > 0) {
                $this->outstandingLedgerService->recordAdjustment(
                    customerId: (int) $locked->customer_id,
                    debit: 0,
                    credit: $outstanding,
                    notes: "Cancel invoice {$locked->invoice_no}".($reason ? " — {$reason}" : ''),
                );
            }

            $notes = trim((string) ($locked->notes ?? ''));
            $cancelNote = 'Cancelled by '.$actor->name.($reason ? ': '.$reason : '');
            $locked->update([
                'status' => 'cancelled',
                'notes' => $notes === '' ? $cancelNote : $notes."\n".$cancelNote,
            ]);

            try {
                app(InvoiceSync::class)->cancelBooksTwin($locked);
            } catch (\Throwable $e) {
                report($e);
            }

            return $locked->fresh(['customer', 'items']);
        });
    }
}
