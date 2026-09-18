<?php

namespace App\Domains\Payment\Services;

use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\CreditNoteItem;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreditNoteService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
        protected OutstandingLedgerService $outstandingLedgerService,
        protected AuditLogService $auditLogService,
        protected StockMovementService $stockMovementService,
    ) {}

    public function create(array $data, User $actor): CreditNote
    {
        return DB::transaction(function () use ($data, $actor) {
            $items = $data['items'] ?? [];
            $subtotal = 0.0;
            $tax = 0.0;

            foreach ($items as $item) {
                $line = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $lineTax = round((float) ($item['tax_amount'] ?? 0), 2);
                $subtotal += $line;
                $tax += $lineTax;
            }

            if ($items === [] && isset($data['grand_total'])) {
                $subtotal = (float) $data['grand_total'];
                $tax = (float) ($data['tax_amount'] ?? 0);
            }

            $creditNote = CreditNote::create([
                'credit_note_no' => $this->documentNumberService->next('CN', now(), 4),
                'customer_id' => $data['customer_id'],
                'invoice_id' => $data['invoice_id'] ?? null,
                'credit_note_date' => $data['credit_note_date'],
                'reason' => $data['reason'] ?? 'other',
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'grand_total' => round($subtotal + $tax, 2),
                'affects_stock' => (bool) ($data['affects_stock'] ?? ($data['reason'] ?? '') === 'return'),
                'notes' => $data['notes'] ?? null,
                'created_by_name' => $this->auditLogService->actorName($actor->name),
            ]);

            foreach ($items as $item) {
                $line = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                CreditNoteItem::create([
                    'credit_note_id' => $creditNote->id,
                    'product_id' => $item['product_id'] ?? null,
                    'uom_id' => $item['uom_id'] ?? null,
                    'quantity' => $item['quantity'] ?? 0,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'line_total' => $line + (float) ($item['tax_amount'] ?? 0),
                    'description' => $item['description'] ?? null,
                ]);
            }

            $this->auditLogService->record($creditNote, 'created', $actor->name);

            return $creditNote->load(['customer', 'items.product', 'items.uom', 'invoice']);
        });
    }

    public function approve(CreditNote $creditNote, User $actor): CreditNote
    {
        return DB::transaction(function () use ($creditNote, $actor) {
            $locked = CreditNote::query()->with('items')->whereKey($creditNote->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'draft') {
                throw new InvalidArgumentException('Only draft credit notes can be approved.');
            }

            $locked->update([
                'status' => 'approved',
                'approved_by' => $actor->id,
                'approved_by_name' => $this->auditLogService->actorName($actor->name),
                'approved_at' => now(),
            ]);

            $this->auditLogService->approval($locked, 'credit_note_approve', 'approved');
            $this->auditLogService->record($locked, 'approved', $actor->name);

            return $locked->fresh(['customer', 'items', 'invoice']);
        });
    }

    public function post(CreditNote $creditNote, User $actor): CreditNote
    {
        return DB::transaction(function () use ($creditNote, $actor) {
            $locked = CreditNote::query()->with('items')->whereKey($creditNote->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, ['draft', 'approved'], true)) {
                throw new InvalidArgumentException('Credit note cannot be posted in its current status.');
            }

            if ($locked->status === 'draft') {
                $locked->update([
                    'approved_by' => $actor->id,
                    'approved_by_name' => $this->auditLogService->actorName($actor->name),
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
                    $this->stockMovementService->recordIn(
                        $product,
                        $uom,
                        (float) $item->quantity,
                        'return',
                        $locked,
                        "Credit note {$locked->credit_note_no}",
                        $actor
                    );
                }
            }

            $this->outstandingLedgerService->recordCreditNote($locked);

            $locked->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            $this->auditLogService->record($locked, 'posted', $actor->name);

            return $locked->fresh(['customer', 'items', 'invoice']);
        });
    }
}
