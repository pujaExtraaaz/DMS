<?php

namespace Tally\Accounting;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\RecurringVoucher;
use Tally\Models\Voucher;

class RecurringVoucherService
{
    public function __construct(private readonly VoucherEngine $engine) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  list<array<string, mixed>>  $lines
     */
    public function save(Company $company, User $user, array $input, array $lines, ?RecurringVoucher $existing = null): RecurringVoucher
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['entries' => 'Add at least one ledger line.']);
        }

        return DB::transaction(function () use ($company, $user, $input, $lines, $existing) {
            $attrs = [
                'company_id' => $company->id,
                'branch_id' => (int) $input['branch_id'],
                'name' => trim((string) $input['name']),
                'voucher_type' => $input['voucher_type'] ?? VoucherType::Journal->value,
                'frequency' => $input['frequency'] ?? 'monthly',
                'day_of_month' => (int) ($input['day_of_month'] ?? 1),
                'start_date' => $input['start_date'],
                'end_date' => $input['end_date'] ?? null,
                'next_run_on' => $input['next_run_on'] ?? $input['start_date'],
                'narration' => $input['narration'] ?? null,
                'auto_post' => filter_var($input['auto_post'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_active' => filter_var($input['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ];

            if ($existing) {
                $existing->update($attrs);
                $existing->lines()->delete();
                $recurring = $existing;
            } else {
                $attrs['created_by'] = $user->id;
                $recurring = RecurringVoucher::query()->create($attrs);
            }

            foreach (array_values($lines) as $index => $line) {
                $recurring->lines()->create([
                    'ledger_id' => (int) $line['ledger_id'],
                    'line_number' => $index + 1,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'cost_centre_id' => $line['cost_centre_id'] ?? null,
                    'business_unit_id' => $line['business_unit_id'] ?? null,
                    'narration' => $line['narration'] ?? null,
                ]);
            }

            return $recurring->fresh('lines');
        });
    }

    /**
     * @return list<Voucher>
     */
    public function runDue(?Carbon $on = null): array
    {
        $on = ($on ?? now())->startOfDay();
        $created = [];

        $due = RecurringVoucher::query()
            ->with(['lines', 'company', 'branch'])
            ->where('is_active', true)
            ->whereDate('next_run_on', '<=', $on->toDateString())
            ->where(function ($q) use ($on) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $on->toDateString());
            })
            ->get();

        foreach ($due as $template) {
            try {
                $created[] = $this->generate($template, $on);
            } catch (\Throwable) {
                // Keep processing other templates; failures are visible via next_run_on not advancing.
            }
        }

        return $created;
    }

    public function generate(RecurringVoucher $template, ?Carbon $on = null): Voucher
    {
        $on = $on ?? now()->startOfDay();
        $company = $template->company;
        $branch = $template->branch;
        $year = FinancialYear::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('is_closed', false)
            ->whereDate('start_date', '<=', $on->toDateString())
            ->whereDate('end_date', '>=', $on->toDateString())
            ->first()
            ?? throw ValidationException::withMessages(['next_run_on' => 'No open financial year covers '.$on->toDateString()]);

        $user = $template->creator ?? User::query()->find($template->created_by)
            ?? User::query()->firstOrFail();

        $voucher = $this->engine->save(
            $company,
            $branch,
            $year,
            $user,
            $template->voucher_type instanceof VoucherType ? $template->voucher_type : VoucherType::Journal,
            [
                'voucher_date' => $on->toDateString(),
                'reference_number' => 'REC-'.$template->id,
                'narration' => $template->narration ?: $template->name,
                'entries' => $template->lines->map(fn ($line) => [
                    'ledger_id' => $line->ledger_id,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'cost_centre_id' => $line->cost_centre_id,
                    'business_unit_id' => $line->business_unit_id,
                    'narration' => $line->narration,
                ])->all(),
            ],
            (bool) $template->auto_post,
        );

        $template->update(['next_run_on' => $this->nextDate($template, $on)->toDateString()]);

        return $voucher;
    }

    private function nextDate(RecurringVoucher $template, Carbon $from): Carbon
    {
        $next = $from->copy();

        return match ($template->frequency) {
            'weekly' => $next->addWeek(),
            'quarterly' => $next->addMonths(3)->day(min($template->day_of_month, $next->copy()->addMonths(3)->daysInMonth)),
            'yearly' => $next->addYear()->day(min($template->day_of_month, $next->copy()->addYear()->daysInMonth)),
            default => $next->addMonth()->day(min($template->day_of_month, $next->copy()->addMonth()->daysInMonth)),
        };
    }
}
