<?php

namespace App\Domains\Sales\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Services\SalePricingService;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Sales\Models\QuotationItem;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class QuotationService
{
    public function __construct(
        protected SalePricingService $salePricingService,
        protected DocumentNumberService $documentNumberService,
        protected AuditLogService $auditLogService,
        protected RegionBrandPolicyService $regionBrandPolicyService,
    ) {}

    public function create(array $data, User $actor): Quotation
    {
        return DB::transaction(function () use ($data, $actor) {
            $customer = Customer::query()
                ->with(['customerType', 'area'])
                ->whereKey($data['customer_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $items = $this->normalizeItems($data['items']);
            $this->regionBrandPolicyService->assertItemsAllowed($customer, $items);

            $pricing = $this->salePricingService->price(
                $customer,
                $items,
                (float) ($data['discount_amount'] ?? 0)
            );

            $profitLines = $this->attachProfit($pricing['lines']);
            $estimatedProfit = collect($profitLines)->sum('estimated_profit');

            $actorName = $this->auditLogService->actorName(
                $data['created_by_name'] ?? $actor->name
            );

            $quotation = Quotation::create([
                'quotation_no' => $this->documentNumberService->next('QTN', now(), 4),
                'customer_id' => $customer->id,
                'salesperson_id' => $actor->id,
                'quotation_date' => $data['quotation_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'status' => 'draft',
                'subtotal' => $pricing['subtotal'],
                'discount_amount' => $pricing['discount'],
                'tax_amount' => $pricing['tax'],
                'grand_total' => $pricing['total'],
                'estimated_profit' => $estimatedProfit,
                'notes' => $data['notes'] ?? null,
                'created_by_name' => $actorName,
            ]);

            foreach ($profitLines as $line) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $line['product']->id,
                    'uom_id' => $line['uom']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unitPrice'],
                    'discount_amount' => $line['discount'] ?? 0,
                    'line_total' => $line['lineTotal'],
                    'estimated_landed_cost' => $line['estimated_landed_cost'],
                    'estimated_profit' => $line['estimated_profit'],
                ]);
            }

            $this->auditLogService->record($quotation, 'created', $actorName);

            return $quotation->load(['customer', 'items.product', 'items.uom']);
        });
    }

    public function update(Quotation $quotation, array $data, User $actor): Quotation
    {
        return DB::transaction(function () use ($quotation, $data, $actor) {
            $locked = Quotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, ['draft', 'sent'], true)) {
                throw new InvalidArgumentException('Only draft or sent quotations can be edited.');
            }

            $customer = Customer::query()
                ->with(['customerType', 'area'])
                ->whereKey($data['customer_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $items = $this->normalizeItems($data['items']);
            $this->regionBrandPolicyService->assertItemsAllowed($customer, $items);

            $pricing = $this->salePricingService->price(
                $customer,
                $items,
                (float) ($data['discount_amount'] ?? 0)
            );

            $profitLines = $this->attachProfit($pricing['lines']);
            $estimatedProfit = collect($profitLines)->sum('estimated_profit');
            $actorName = $this->auditLogService->actorName($data['updated_by_name'] ?? $actor->name);

            $locked->update([
                'customer_id' => $customer->id,
                'quotation_date' => $data['quotation_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'subtotal' => $pricing['subtotal'],
                'discount_amount' => $pricing['discount'],
                'tax_amount' => $pricing['tax'],
                'grand_total' => $pricing['total'],
                'estimated_profit' => $estimatedProfit,
                'notes' => $data['notes'] ?? null,
                'updated_by_name' => $actorName,
            ]);

            $locked->items()->delete();

            foreach ($profitLines as $line) {
                QuotationItem::create([
                    'quotation_id' => $locked->id,
                    'product_id' => $line['product']->id,
                    'uom_id' => $line['uom']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unitPrice'],
                    'discount_amount' => $line['discount'] ?? 0,
                    'line_total' => $line['lineTotal'],
                    'estimated_landed_cost' => $line['estimated_landed_cost'],
                    'estimated_profit' => $line['estimated_profit'],
                ]);
            }

            $this->auditLogService->record($locked, 'updated', $actorName);

            return $locked->fresh(['customer', 'items.product', 'items.uom']);
        });
    }

    public function markSent(Quotation $quotation, User $actor): Quotation
    {
        if ($quotation->status !== 'draft') {
            throw new InvalidArgumentException('Only draft quotations can be marked sent.');
        }

        $quotation->update(['status' => 'sent']);
        $this->auditLogService->record($quotation, 'sent', $actor->name);

        return $quotation->fresh();
    }

    public function accept(Quotation $quotation, User $actor): Quotation
    {
        if (! in_array($quotation->status, ['draft', 'sent'], true)) {
            throw new InvalidArgumentException('Quotation cannot be accepted in its current status.');
        }

        $quotation->update(['status' => 'accepted']);
        $this->auditLogService->record($quotation, 'accepted', $actor->name);

        return $quotation->fresh();
    }

    /**
     * Admin profit = sale line total − (landed/purchase unit cost × qty).
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    protected function attachProfit(array $lines): array
    {
        return array_map(function (array $line) {
            /** @var Product $product */
            $product = $line['product'];
            $qty = (float) $line['quantity'];
            $unitLanded = (float) ($product->purchase_price ?? 0);
            $landed = round($unitLanded * $qty, 2);
            $lineTotal = (float) $line['lineTotal'];

            $line['estimated_landed_cost'] = $unitLanded;
            $line['estimated_profit'] = round($lineTotal - $landed, 2);

            return $line;
        }, $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{product_id: int, uom_id: int, quantity: float, unit_price: ?float}>
     */
    protected function normalizeItems(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'At least one quotation line is required.',
            ]);
        }

        $productIds = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->unique();
        $uomIds = collect($items)->pluck('uom_id')->map(fn ($id) => (int) $id)->unique();

        $products = Product::query()->whereIn('id', $productIds)->where('is_active', true)->get()->keyBy('id');
        $uoms = Uom::query()->whereIn('id', $uomIds)->where('is_active', true)->get()->keyBy('id');

        foreach ($items as $index => $item) {
            if (! isset($products[(int) $item['product_id']])) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Invalid product.',
                ]);
            }
            if (! isset($uoms[(int) $item['uom_id']])) {
                throw ValidationException::withMessages([
                    "items.{$index}.uom_id" => 'Invalid UOM.',
                ]);
            }
        }

        return array_map(static fn (array $item) => [
            'product_id' => (int) $item['product_id'],
            'uom_id' => (int) $item['uom_id'],
            'quantity' => round((float) $item['quantity'], 4),
            'unit_price' => isset($item['unit_price']) ? (float) $item['unit_price'] : null,
        ], $items);
    }
}
