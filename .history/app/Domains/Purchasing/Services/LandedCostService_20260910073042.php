<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Purchasing\Models\FreightBill;
use App\Domains\Purchasing\Models\LandedCost;
use App\Domains\Purchasing\Models\LandedCostItem;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Models\User;
use App\Support\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class LandedCostService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
    ) {}

    /**
     * Allocate additional costs across invoice lines without changing supplier invoice rates.
     *
     * @param  array{
     *   landed_date:string,
     *   purchase_invoice_id:int,
     *   freight_bill_id?:int|null,
     *   allocation_method:string,
     *   total_additional_cost?:float,
     *   notes?:string,
     *   manual_allocations?:array<int,float>
     * }  $data
     */
    public function allocate(array $data, User $actor): LandedCost
    {
        return DB::transaction(function () use ($data, $actor) {
            $invoice = PurchaseInvoice::query()
                ->with('items')
                ->findOrFail($data['purchase_invoice_id']);

            $method = $data['allocation_method'] ?? 'value';
            $additional = (float) ($data['total_additional_cost'] ?? 0);

            if (! empty($data['freight_bill_id'])) {
                $freight = FreightBill::findOrFail($data['freight_bill_id']);
                if ($additional <= 0) {
                    $additional = (float) $freight->total_amount;
                }
            }

            if ($additional <= 0 && $method !== 'manual') {
                throw ValidationException::withMessages([
                    'total_additional_cost' => 'Additional cost must be greater than zero.',
                ]);
            }

            $lines = $invoice->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'uom_id' => $item->uom_id,
                'quantity' => (float) $item->quantity,
                'base_value' => (float) $item->line_total,
                'weight' => 0.0,
                'volume' => 0.0,
                'unit_cost' => (float) $item->unit_cost,
                'invoice_item_id' => $item->id,
            ])->values()->all();

            if ($lines === []) {
                throw new InvalidArgumentException('Purchase invoice has no lines to allocate.');
            }

            $allocations = $this->computeAllocations($lines, $method, $additional, $data['manual_allocations'] ?? []);

            $landed = LandedCost::create([
                'landed_no' => $this->documentNumberService->next('LC'),
                'landed_date' => $data['landed_date'],
                'purchase_invoice_id' => $invoice->id,
                'freight_bill_id' => $data['freight_bill_id'] ?? null,
                'allocation_method' => $method,
                'total_additional_cost' => array_sum(array_column($allocations, 'allocated_cost')),
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($allocations as $row) {
                LandedCostItem::create([
                    'landed_cost_id' => $landed->id,
                    'product_id' => $row['product_id'],
                    'uom_id' => $row['uom_id'],
                    'quantity' => $row['quantity'],
                    'base_value' => $row['base_value'],
                    'weight' => $row['weight'],
                    'volume' => $row['volume'],
                    'allocated_cost' => $row['allocated_cost'],
                    'landed_unit_cost' => $row['landed_unit_cost'],
                ]);
            }

            if (! empty($data['freight_bill_id'])) {
                FreightBill::whereKey($data['freight_bill_id'])->update(['status' => 'allocated']);
            }

            return $landed->load(['items.product', 'items.uom', 'purchaseInvoice', 'freightBill']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<int, float>  $manual
     * @return array<int, array<string, mixed>>
     */
    public function computeAllocations(array $lines, string $method, float $additional, array $manual = []): array
    {
        $count = count($lines);

        if ($method === 'manual') {
            $result = [];
            foreach ($lines as $index => $line) {
                $allocated = (float) ($manual[$index] ?? $manual[$line['invoice_item_id'] ?? $index] ?? 0);
                $qty = max(0.0001, (float) $line['quantity']);
                $result[] = [
                    ...$line,
                    'allocated_cost' => round($allocated, 2),
                    'landed_unit_cost' => round(((float) $line['unit_cost']) + ($allocated / $qty), 4),
                ];
            }

            return $result;
        }

        $basisKey = match ($method) {
            'qty' => 'quantity',
            'weight' => 'weight',
            'volume' => 'volume',
            'equal' => null,
            default => 'base_value',
        };

        if ($method === 'equal') {
            $share = $additional / $count;
            return array_map(function (array $line) use ($share) {
                $qty = max(0.0001, (float) $line['quantity']);

                return [
                    ...$line,
                    'allocated_cost' => round($share, 2),
                    'landed_unit_cost' => round(((float) $line['unit_cost']) + ($share / $qty), 4),
                ];
            }, $lines);
        }

        $totalBasis = array_sum(array_map(fn ($line) => (float) ($line[$basisKey] ?? 0), $lines));
        if ($totalBasis <= 0) {
            // Fall back to equal if basis is empty (e.g. weight/volume not captured).
            return $this->computeAllocations($lines, 'equal', $additional, $manual);
        }

        $allocatedSum = 0;
        $result = [];
        foreach ($lines as $index => $line) {
            $basis = (float) ($line[$basisKey] ?? 0);
            $share = $index === $count - 1
                ? round($additional - $allocatedSum, 2)
                : round($additional * ($basis / $totalBasis), 2);
            $allocatedSum += $share;
            $qty = max(0.0001, (float) $line['quantity']);

            $result[] = [
                ...$line,
                'allocated_cost' => $share,
                'landed_unit_cost' => round(((float) $line['unit_cost']) + ($share / $qty), 4),
            ];
        }

        return $result;
    }
}
