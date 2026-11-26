<?php

namespace App\Domains\Sales\Services;

use App\Domains\Organization\Services\FinancialYearService;
use App\Domains\Sales\Models\DeliveryChallan;
use App\Domains\Sales\Models\DeliveryChallanItem;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeliveryChallanService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
        protected AuditLogService $auditLogService,
        protected FinancialYearService $financialYearService,
    ) {}

    public function create(array $data, User $actor): DeliveryChallan
    {
        return DB::transaction(function () use ($data, $actor) {
            $this->financialYearService->assertOpen($data['challan_date']);

            $challan = DeliveryChallan::create([
                'challan_no' => $this->documentNumberService->nextDocument('DC', now(), 4),
                'company_id' => $data['company_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'customer_id' => $data['customer_id'],
                'order_id' => $data['order_id'] ?? null,
                'invoice_id' => $data['invoice_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'challan_date' => $data['challan_date'],
                'status' => 'draft',
                'vehicle_no' => $data['vehicle_no'] ?? null,
                'transporter_name' => $data['transporter_name'] ?? null,
                'lr_no' => $data['lr_no'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($data['items'] ?? [] as $item) {
                if ((float) ($item['quantity'] ?? 0) <= 0) {
                    continue;
                }
                DeliveryChallanItem::create([
                    'delivery_challan_id' => $challan->id,
                    'product_id' => $item['product_id'] ?? null,
                    'uom_id' => $item['uom_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'description' => $item['description'] ?? null,
                ]);
            }

            $this->auditLogService->record($challan, 'created', $actor->name);

            return $challan->load(['customer', 'order', 'invoice', 'items.product', 'items.uom']);
        });
    }

    public function dispatch(DeliveryChallan $challan, User $actor): DeliveryChallan
    {
        return DB::transaction(function () use ($challan, $actor) {
            $locked = DeliveryChallan::query()->whereKey($challan->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'draft') {
                throw new InvalidArgumentException('Only draft challans can be dispatched.');
            }

            $locked->update([
                'status' => 'dispatched',
                'dispatched_at' => now(),
            ]);

            $this->auditLogService->record($locked, 'dispatched', $actor->name);

            return $locked->fresh(['customer', 'items', 'order', 'invoice']);
        });
    }

    public function markDelivered(DeliveryChallan $challan, User $actor): DeliveryChallan
    {
        return DB::transaction(function () use ($challan, $actor) {
            $locked = DeliveryChallan::query()->whereKey($challan->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, ['draft', 'dispatched'], true)) {
                throw new InvalidArgumentException('Challan cannot be marked delivered in its current status.');
            }

            $locked->update([
                'status' => 'delivered',
                'dispatched_at' => $locked->dispatched_at ?? now(),
            ]);

            $this->auditLogService->record($locked, 'delivered', $actor->name);

            return $locked->fresh(['customer', 'items', 'order', 'invoice']);
        });
    }

    public function cancel(DeliveryChallan $challan, User $actor): DeliveryChallan
    {
        return DB::transaction(function () use ($challan, $actor) {
            $locked = DeliveryChallan::query()->whereKey($challan->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'cancelled') {
                return $locked;
            }

            if ($locked->status === 'delivered') {
                throw new InvalidArgumentException('Delivered challans cannot be cancelled.');
            }

            $locked->update(['status' => 'cancelled']);
            $this->auditLogService->record($locked, 'cancelled', $actor->name);

            return $locked->fresh(['customer', 'items']);
        });
    }
}
