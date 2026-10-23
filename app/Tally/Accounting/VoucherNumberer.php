<?php

namespace Tally\Accounting;

use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\VoucherSequence;
use Tally\Models\VoucherTypeMaster;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class VoucherNumberer
{
    public function method(Company $company, VoucherType $type): string
    {
        return $this->master($company, $type)?->numbering_method ?: 'automatic';
    }

    public function peek(Company $company, Branch $branch, FinancialYear $financialYear, VoucherType $type, ?DateTimeInterface $on = null): string
    {
        $master = $this->master($company, $type);

        if ($this->isTyped($master) || ($master?->numbering_method ?: 'automatic') === 'multi_user') {
            return '';
        }

        $sequence = $this->query($company, $branch, $financialYear, $type, $this->periodKey($master, $on))->first();

        return VoucherNumberFormat::make(
            $master?->prefix ?? $sequence?->prefix ?? $type->prefix(),
            $master?->suffix ?? $sequence?->suffix,
            (int) ($master?->padding ?? $sequence?->padding ?? 6),
            (int) ($sequence?->next_number ?? $master?->next_number ?? 1),
        );
    }

    public function allocate(Company $company, Branch $branch, FinancialYear $financialYear, VoucherType $type, ?DateTimeInterface $on = null): string
    {
        $master = $this->master($company, $type);
        $periodKey = $this->periodKey($master, $on);
        $sequence = $this->query($company, $branch, $financialYear, $type, $periodKey)->lockForUpdate()->first();

        if (! $sequence) {
            try {
                $sequence = VoucherSequence::query()->create([
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'financial_year_id' => $financialYear->id,
                    'voucher_type' => $type,
                    'period_key' => $periodKey,
                    'prefix' => $master?->prefix ?? $type->prefix(),
                    'suffix' => $master?->suffix,
                    'next_number' => $master?->next_number ?? 1,
                    'padding' => $master?->padding ?? 6,
                ]);
            } catch (UniqueConstraintViolationException) {
                $sequence = $this->query($company, $branch, $financialYear, $type, $periodKey)->lockForUpdate()->firstOrFail();
            }
        }

        if ($master) {
            $sequence->prefix = $master->prefix !== '' ? $master->prefix : ($sequence->prefix ?: $type->prefix());
            $sequence->suffix = $master->suffix;
            $sequence->padding = (int) $master->padding;
            if ((int) $master->next_number > (int) $sequence->next_number) {
                $sequence->next_number = (int) $master->next_number;
            }
            $sequence->save();
        }

        $number = $sequence->format((int) $sequence->next_number);
        $sequence->increment('next_number');

        return $number;
    }

    public function resolve(
        Company $company,
        Branch $branch,
        FinancialYear $financialYear,
        VoucherType $type,
        ?string $typed,
        ?DateTimeInterface $on = null,
        string $errorKey = 'voucher_number',
    ): string {
        $typed = trim((string) $typed);
        $master = $this->master($company, $type);

        if ($this->isTyped($master)) {
            if ($typed === '') {
                throw ValidationException::withMessages([
                    $errorKey => 'Enter the voucher number. This voucher type uses manual numbering.',
                ]);
            }

            return $typed;
        }

        if (($master?->numbering_method ?: 'automatic') === 'automatic_manual' && $typed !== '' && $typed !== $this->peek($company, $branch, $financialYear, $type, $on)) {
            return $typed;
        }

        return $this->allocate($company, $branch, $financialYear, $type, $on);
    }

    /**
     * @return Builder<VoucherSequence>
     */
    private function query(Company $company, Branch $branch, FinancialYear $financialYear, VoucherType $type, string $periodKey): Builder
    {
        $query = VoucherSequence::query()
            ->where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->where('voucher_type', $type->value)
            ->where('period_key', $periodKey);

        if ($periodKey !== 'never') {
            $query->where('financial_year_id', $financialYear->id);
        }

        return $query->orderBy('id');
    }

    private function master(Company $company, VoucherType $type): ?VoucherTypeMaster
    {
        return VoucherTypeMaster::query()
            ->where('company_id', $company->id)
            ->where('category', $type->value)
            ->first();
    }

    private function periodKey(?VoucherTypeMaster $master, ?DateTimeInterface $on): string
    {
        $restart = $master?->restart ?: 'yearly';

        return match ($restart) {
            'monthly' => ($on ?? now())->format('Y-m'),
            'daily' => ($on ?? now())->format('Y-m-d'),
            'weekly' => ($on ?? now())->format('o-\WW'),
            'never' => 'never',
            default => '',
        };
    }

    private function isTyped(?VoucherTypeMaster $master): bool
    {
        return in_array($master?->numbering_method, ['manual', 'none'], true);
    }
}