<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Services\FinancialYearService;
use App\Domains\Purchasing\Models\DebitNote;
use App\Domains\Purchasing\Models\DebitNoteItem;
use App\Domains\Purchasing\Models\SupplierPayable;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DebitNoteService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
        protected AuditLogService $auditLogService,
        protected StockMovementService $stockMovementService,
        protected FinancialYearService $financialYearService,
    ) {}

    public function create(array $data, User $actor): DebitNote
    {
        return DB::transaction(function () use ($data, $actor) {
            $items = $data['items'] ?? [];
            $subtotal = 0.0;
            $tax = 0.0;

            foreach ($items as $item) {
                $line = round((float) $item['quantity'] * (float) $item['unit_cost'], 2);
                $lineTax = round((float) ($item['tax_amount'] ?? 0), 2);
                $subtotal += $line;
                $tax += $lineTax;
            }

            $note = DebitNote::create([
                'debit_note_no' => $this->documentNumberService->nextDocument('DN', now(), 4),
                'company_id' => $data['company_id'] ?? null,
                'supplier_id' => $data['supplier_id'],
                'purchase_invoice_id' => $data['purchase_invoice_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'debit_note_date' => $data['debit_note_date'],
                'reason' => $data['reason'] ?? 'return',
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'grand_total' => round($subtotal + $tax, 2),
                'affects_stock' => (bool) ($data['affects_stock'] ?? ($data['reason'] ?? '') === 'return'),
                'notes' => $data['notes'] ?? null,
                'created_by_name' => $this->auditLogService->actorName($actor->name),
            ]);

            foreach ($items as $item) {
                $line = round((float) $item['quantity'] * (float) $item['unit_cost'], 2);
                DebitNoteItem::create([
                    'debit_note_id' => $note->id,
                    'product_id' => $item['product_id'] ?? null,
                    'uom_id' => $item['uom_id'] ?? null,
                    'quantity' => $item['quantity'] ?? 0,
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'line_total' => $line + (float) ($item['tax_amount'] ?? 0),
                    'description' => $item['description'] ?? null,
                ]);
            }

            $this->auditLogService->record($note, 'created', $actor->name);

            return $note->load(['supplier', 'items.product', 'items.uom', 'purchaseInvoice']);
        });
    }

    public function approve(DebitNote $debitNote, User $actor): DebitNote
    {
        return DB::transaction(function () use ($debitNote, $actor) {
            $locked = DebitNote::query()->with('items')->whereKey($debitNote->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'draft') {
                throw new InvalidArgumentException('Only draft debit notes can be approved.');
            }

            $locked->update([
                'status' => 'approved',
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->auditLogService->record($locked, 'approved', $actor->name);

            return $locked->fresh(['supplier', 'items', 'purchaseInvoice']);
        });
    }

    public function post(DebitNote $debitNote, User $actor): DebitNote
    {
        return DB::transaction(function () use ($debitNote, $actor) {
            $locked = DebitNote::query()->with('items')->whereKey($debitNote->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, ['draft', 'approved'], true)) {
                throw new InvalidArgumentException('Debit note cannot be posted in its current status.');
            }

            $this->financialYearService->assertOpen(
                $locked->debit_note_date?->toDateString() ?: now()->toDateString()
            );

            if ($locked->status === 'draft') {
                $locked->update([
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                ]);
            }

            if ($locked->affects_stock) {
                foreach ($locked->items as $item) {
                    if (! $item->product_id || ! $item->uom_id || (float) $item->quantity <= 0) {
                        continue;
                    }

                    $product = Product::findOrFail($item->product_id);
                    $uom = Uom::findOrFail($item->uom_id);
                    $this->stockMovementService->recordOut(
                        $product,
                        $uom,
                        (float) $item->quantity,
                        'purchase_return',
                        $locked,
                        "Debit note {$locked->debit_note_no}",
                        $actor,
                        $locked->warehouse_id ? (int) $locked->warehouse_id : null,
                    );
                }
            }

            $this->recordPayableCredit($locked);

            $locked->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            $this->auditLogService->record($locked, 'posted', $actor->name);

            return $locked->fresh(['supplier', 'items', 'purchaseInvoice']);
        });
    }

    public function cancel(DebitNote $debitNote, User $actor): DebitNote
    {
        return DB::transaction(function () use ($debitNote, $actor) {
            $locked = DebitNote::query()->with('items')->whereKey($debitNote->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'cancelled') {
                return $locked;
            }

            if ($locked->status === 'posted') {
                // Reverse stock outs from purchase return.
                if ($locked->affects_stock) {
                    foreach ($locked->items as $item) {
                        if (! $item->product_id || ! $item->uom_id || (float) $item->quantity <= 0) {
                            continue;
                        }

                        $product = Product::findOrFail($item->product_id);
                        $uom = Uom::findOrFail($item->uom_id);
                        $this->stockMovementService->recordIn(
                            $product,
                            $uom,
                            (float) $item->quantity,
                            'return',
                            $locked,
                            "Cancel debit note {$locked->debit_note_no}",
                            $actor,
                            $locked->warehouse_id ? (int) $locked->warehouse_id : null,
                        );
                    }
                }

                $previous = (float) (SupplierPayable::query()
                    ->where('supplier_id', $locked->supplier_id)
                    ->orderByDesc('id')
                    ->value('balance') ?? 0);

                SupplierPayable::create([
                    'supplier_id' => $locked->supplier_id,
                    'type' => 'adjustment',
                    'reference_type' => $locked->getMorphClass(),
                    'reference_id' => $locked->id,
                    'debit' => $locked->grand_total,
                    'credit' => 0,
                    'balance' => $previous + (float) $locked->grand_total,
                    'notes' => "Cancel debit note {$locked->debit_note_no}",
                ]);
            } elseif (! in_array($locked->status, ['draft', 'approved'], true)) {
                throw new InvalidArgumentException('Debit note cannot be cancelled in its current status.');
            }

            $locked->update(['status' => 'cancelled']);
            $this->auditLogService->record($locked, 'cancelled', $actor->name);

            try {
                app(\App\Domains\Sync\Handlers\DebitNoteSync::class)->cancelBooksTwin($locked);
            } catch (\Throwable $e) {
                report($e);
            }

            return $locked->fresh(['supplier', 'items', 'purchaseInvoice']);
        });
    }

    protected function recordPayableCredit(DebitNote $note): void
    {
        $previous = (float) (SupplierPayable::query()
            ->where('supplier_id', $note->supplier_id)
            ->orderByDesc('id')
            ->value('balance') ?? 0);

        SupplierPayable::create([
            'supplier_id' => $note->supplier_id,
            'type' => 'debit_note',
            'reference_type' => $note->getMorphClass(),
            'reference_id' => $note->id,
            'debit' => 0,
            'credit' => $note->grand_total,
            'balance' => $previous - (float) $note->grand_total,
            'notes' => "Debit note {$note->debit_note_no}",
        ]);
    }
}
