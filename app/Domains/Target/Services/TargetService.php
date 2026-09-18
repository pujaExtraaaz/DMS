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

        if ($target->brand_id) {
            $query->whereHas('items.product', fn ($q) => $q->where('brand_id', $target->brand_id));
        }

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
        return TargetPeriod::create([
            'name' => $data['name'],
            'period_type' => $data['period_type'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'status' => 'open',
        ]);
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
