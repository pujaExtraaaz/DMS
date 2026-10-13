<?php

namespace Tally\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\Godown;
use Tally\Models\Product;
use Tally\Models\StockCount;

class StockCountService
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly InventoryValuation $valuation,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, ?Branch $branch, array $input, ?StockCount $count = null): StockCount
    {
        return DB::transaction(function () use ($company, $branch, $input, $count) {
            if ($count && ! $count->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft stock counts can be changed.',
                ]);
            }

            $godownId = isset($input['godown_id']) && $input['godown_id'] !== '' ? (int) $input['godown_id'] : null;
            $godown = null;

            if ($godownId !== null) {
                $godown = Godown::query()
                    ->where('company_id', $company->id)
                    ->whereKey($godownId)
                    ->firstOrFail();
            }

            $payload = [
                'count_date' => (string) ($input['count_date'] ?? now()->toDateString()),
                'godown_id' => $godown?->id,
                'branch_id' => $branch?->id ?? ($input['branch_id'] ?? null),
                'narration' => ($input['narration'] ?? null) ?: null,
                'status' => ($input['status'] ?? null) ?: ($count?->status ?? 'draft'),
            ];

            if ($count) {
                $count->update($payload);
                $count->lines()->delete();
            } else {
                $seq = StockCount::query()->where('company_id', $company->id)->count() + 1;
                $count = StockCount::query()->create($payload + [
                    'company_id' => $company->id,
                    'number' => ($input['number'] ?? null) ?: 'SC-'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT),
                ]);
            }

            $lines = $input['lines'] ?? [];
            if ($lines === []) {
                throw ValidationException::withMessages([
                    'lines' => 'Add at least one product line.',
                ]);
            }

            foreach ($lines as $index => $line) {
                $productId = (int) ($line['product_id'] ?? 0);
                $product = Product::query()
                    ->where('company_id', $company->id)
                    ->whereKey($productId)
                    ->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        "lines.$index.product_id" => 'Select a valid product.',
                    ]);
                }

                $systemQty = $this->movements->quantity($product, $godown);
                $countedQty = number_format((float) ($line['counted_qty'] ?? 0), 4, '.', '');
                $unitCost = isset($line['unit_cost']) && $line['unit_cost'] !== ''
                    ? number_format((float) $line['unit_cost'], 4, '.', '')
                    : $this->valuation->averageRate($product);

                $count->lines()->create([
                    'product_id' => $product->id,
                    'system_qty' => $systemQty,
                    'counted_qty' => $countedQty,
                    'variance_qty' => $this->variance($countedQty, $systemQty),
                    'unit_cost' => $unitCost,
                ]);
            }

            return $count->fresh(['lines.product', 'godown']);
        });
    }

    public function recomputeVariances(StockCount $count): StockCount
    {
        $count->loadMissing(['lines.product', 'godown', 'company']);
        $godown = $count->godown;

        foreach ($count->lines as $line) {
            $systemQty = $this->movements->quantity($line->product, $godown);
            $countedQty = number_format((float) $line->counted_qty, 4, '.', '');
            $line->update([
                'system_qty' => $systemQty,
                'variance_qty' => $this->variance($countedQty, $systemQty),
            ]);
        }

        return $count->fresh(['lines.product', 'godown']);
    }

    private function variance(string $countedQty, string $systemQty): string
    {
        return number_format((float) $countedQty - (float) $systemQty, 4, '.', '');
    }
}
