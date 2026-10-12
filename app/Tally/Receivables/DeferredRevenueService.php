<?php

namespace Tally\Receivables;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\DeferredRevenueSchedule;
use Tally\Models\FinancialYear;

class DeferredRevenueService
{
    public function __construct(private readonly VoucherEngine $vouchers) {}

    /**
     * @return list<DeferredRevenueSchedule>
     */
    public function recognizeDue(?string $asOn = null): array
    {
        $asOn ??= now()->toDateString();
        $done = [];

        $schedules = DeferredRevenueSchedule::query()
            ->with('company')
            ->where('status', 'open')
            ->whereNotNull('next_recognize_on')
            ->where('next_recognize_on', '<=', $asOn)
            ->get();

        foreach ($schedules as $schedule) {
            $remaining = Money::cents($schedule->remainingAmount());
            if ($remaining <= 0 || ! $schedule->revenue_ledger_id || ! $schedule->deferred_ledger_id) {
                $schedule->update(['status' => 'closed']);
                continue;
            }

            $months = max(1, (int) round((strtotime($schedule->end_date->toDateString()) - strtotime($schedule->start_date->toDateString())) / (30 * 86400)));
            $slice = (int) round(Money::cents((string) $schedule->total_amount) / $months);
            $slice = min($slice, $remaining);

            $year = FinancialYear::query()
                ->where('company_id', $schedule->company_id)
                ->where('is_active', true)
                ->where('start_date', '<=', $asOn)
                ->where('end_date', '>=', $asOn)
                ->first();

            $branch = $schedule->company->branches()->where('is_active', true)->orderBy('name')->first();
            $user = User::query()->first();

            if (! $year || ! $branch || ! $user) {
                continue;
            }

            $this->vouchers->save(
                $schedule->company,
                $branch,
                $year,
                $user,
                VoucherType::Journal,
                [
                    'voucher_date' => $asOn,
                    'narration' => 'Deferred revenue recognition',
                    'entries' => [
                        ['ledger_id' => $schedule->deferred_ledger_id, 'debit' => Money::format($slice), 'credit' => '0.00'],
                        ['ledger_id' => $schedule->revenue_ledger_id, 'debit' => '0.00', 'credit' => Money::format($slice)],
                    ],
                ],
                true
            );

            $recognized = Money::cents((string) $schedule->recognized_amount) + $slice;
            $schedule->update([
                'recognized_amount' => Money::format($recognized),
                'next_recognize_on' => date('Y-m-d', strtotime('+1 month', strtotime($asOn))),
                'status' => $recognized >= Money::cents((string) $schedule->total_amount) ? 'closed' : 'open',
            ]);

            $done[] = $schedule->fresh();
        }

        return $done;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DeferredRevenueSchedule
    {
        if (Money::cents((string) ($data['total_amount'] ?? '0')) <= 0) {
            throw ValidationException::withMessages(['total_amount' => 'Enter a positive amount.']);
        }

        return DeferredRevenueSchedule::query()->create($data + [
            'recognized_amount' => '0.00',
            'status' => 'open',
            'next_recognize_on' => $data['start_date'] ?? now()->toDateString(),
        ]);
    }
}
