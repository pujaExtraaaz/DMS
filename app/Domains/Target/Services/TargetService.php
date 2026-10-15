<?php

namespace App\Domains\Target\Services;

use App\Domains\Payment\Models\CreditNote;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Target\Models\PartyTarget;
use App\Domains\Target\Models\TargetAchievement;
use App\Domains\Target\Models\TargetPeriod;
use Illuminate\Support\Facades\DB;

class TargetService
{
    public function calculatePeriod(TargetPeriod $period, bool $finalize = false): void
    {
        $period->load('targets');

        foreach ($period->targets as $target) {
            $this->calculateTarget($target, $finalize);
        }

        if ($finalize) {
            $period->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
        }
    }

    public function calculateTarget(PartyTarget $target, bool $finalize = false): TargetAchievement
    {
        $existing = TargetAchievement::query()
            ->where('party_target_id', $target->id)
            ->first();

        if ($existing?->is_final) {
            return $existing;
        }

        $period = $target->period ?? TargetPeriod::findOrFail($target->target_period_id);

        $query = Invoice::query()
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->whereBetween('invoice_date', [$period->starts_on->toDateString(), $period->ends_on->toDateString()]);

        if ($target->customer_id) {
            $query->where('customer_id', $target->customer_id);
        }

        if ($target->salesperson_id) {
            $query->where('salesperson_id', $target->salesperson_id);
        }

        // Apply brand scope: party target brand takes precedence; fallback to period brand
        $effectiveBrandId = $target->brand_id ?: ($period->target_category === 'brand' ? $period->brand_id : null);
        if ($effectiveBrandId) {
            $query->whereHas('items.product', fn ($q) => $q->where('brand_id', $effectiveBrandId));
        }

        // Apply product scope if period category is product
        if ($period->target_category === 'product' && $period->product_id) {
            $query->whereHas('items', fn ($q) => $q->where('product_id', $period->product_id));
        }

        // Note: For suppliers, achievement calculations are not artificially invented
        // to preserve existing sales calculations. Supplier selection and tracking are persisted.

        $achieved = (float) $query->sum('grand_total');

        // Optional CN netting against customer targets.
        if ($target->customer_id) {
            $cn = (float) CreditNote::query()
                ->where('customer_id', $target->customer_id)
                ->where('status', 'posted')
                ->whereBetween('credit_note_date', [$period->starts_on->toDateString(), $period->ends_on->toDateString()])
                ->sum('grand_total');
            $achieved = max(0, $achieved - $cn);
        }

        $percent = (float) $target->amount > 0
            ? round(($achieved / (float) $target->amount) * 100, 2)
            : 0;

        return TargetAchievement::updateOrCreate(
            ['party_target_id' => $target->id],
            [
                'target_period_id' => $period->id,
                'achieved_amount' => round($achieved, 2),
                'achievement_percent' => $percent,
                'is_final' => $finalize,
                'calculated_at' => now(),
            ]
        );
    }

    public function createPeriod(array $data): TargetPeriod
    {
        $rawCategory = $data['target_category'] ?? null;
        $category = match ($rawCategory) {
            'brand', 'brands' => 'brand',
            'product', 'products' => 'product',
            'supplier', 'suppliers' => 'supplier',
            default => null,
        };

        return TargetPeriod::create([
            'name' => $data['name'],
            'period_type' => $data['period_type'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'status' => 'open',
            'target_category' => $category,
            'brand_id' => $category === 'brand' ? ($data['brand_id'] ?? null) : null,
            'product_id' => $category === 'product' ? ($data['product_id'] ?? null) : null,
            'supplier_id' => $category === 'supplier' ? ($data['supplier_id'] ?? null) : null,
        ]);
    }

    public function updatePeriod(TargetPeriod $period, array $data): TargetPeriod
    {
        $rawCategory = $data['target_category'] ?? null;
        $category = match ($rawCategory) {
            'brand', 'brands' => 'brand',
            'product', 'products' => 'product',
            'supplier', 'suppliers' => 'supplier',
            default => null,
        };

        $period->update([
            'name' => $data['name'],
            'period_type' => $data['period_type'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'target_category' => $category,
            'brand_id' => $category === 'brand' ? ($data['brand_id'] ?? null) : null,
            'product_id' => $category === 'product' ? ($data['product_id'] ?? null) : null,
            'supplier_id' => $category === 'supplier' ? ($data['supplier_id'] ?? null) : null,
        ]);

        return $period;
    }

    public function createTarget(array $data): PartyTarget
    {
        return DB::transaction(function () use ($data) {
            $target = PartyTarget::create([
                'target_period_id' => $data['target_period_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'salesperson_id' => $data['salesperson_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'amount' => $data['amount'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->calculateTarget($target);

            return $target->load(['period', 'customer', 'salesperson', 'brand', 'achievement']);
        });
    }
}
