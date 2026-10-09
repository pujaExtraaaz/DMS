<?php

namespace Tally\Accounting;

use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\VoucherSequence;
use Tally\Models\VoucherTypeMaster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;

class VoucherNumberer
{
    public function peek(Company $company, Branch $branch, FinancialYear $financialYear, VoucherType $type): string
    {
        $sequence = $this->query($company, $branch, $financialYear, $type)->first();
        $master = $this->master($company, $type);

        return $this->format(
            $master?->prefix ?? $sequence?->prefix ?? $type->prefix(),
            $master?->padding ?? $sequence?->padding ?? 6,
            $sequence?->next_number ?? $master?->next_number ?? 1,
        );
    }

    public function allocate(Company $company, Branch $branch, FinancialYear $financialYear, VoucherType $type): string
    {
        $sequence = $this->query($company, $branch, $financialYear, $type)->lockForUpdate()->first();

        if (! $sequence) {
            $master = $this->master($company, $type);

            try {
                $sequence = VoucherSequence::query()->create([
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'financial_year_id' => $financialYear->id,
                    'voucher_type' => $type,
                    'prefix' => $master?->prefix ?? $type->prefix(),
                    'next_number' => $master?->next_number ?? 1,
                    'padding' => $master?->padding ?? 6,
                ]);
            } catch (UniqueConstraintViolationException) {
                $sequence = $this->query($company, $branch, $financialYear, $type)->lockForUpdate()->firstOrFail();
            }
        }

        $master = $this->master($company, $type);

        if ($master && ($sequence->prefix !== $master->prefix || (int) $sequence->padding !== (int) $master->padding)) {
            $sequence->prefix = $master->prefix;
            $sequence->padding = (int) $master->padding;
            $sequence->save();
        }

        $number = $sequence->format($sequence->next_number);
        $sequence->increment('next_number');

        return $number;
    }

    /**
     * @return Builder<VoucherSequence>
     */
    private function query(Company $company, Branch $branch, FinancialYear $financialYear, VoucherType $type): Builder
    {
        return VoucherSequence::query()
            ->where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->where('financial_year_id', $financialYear->id)
            ->where('voucher_type', $type->value);
    }

    private function master(Company $company, VoucherType $type): ?VoucherTypeMaster
    {
        return VoucherTypeMaster::query()
            ->where('company_id', $company->id)
            ->where('category', $type->value)
            ->first();
    }

    private function format(string $prefix, int $padding, int $number): string
    {
        return sprintf('%s-%0'.$padding.'d', $prefix, $number);
    }
}
