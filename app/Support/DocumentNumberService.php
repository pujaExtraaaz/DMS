<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentNumberService
{
    public function next(
        string $prefix,
        ?Carbon $date = null,
        int $padding = 4,
        ?int $companyId = null,
        ?int $branchId = null,
        ?int $financialYearId = null,
    ): string {
        $date ??= now();
        $companyId ??= auth()->user()?->company_id;
        $branchId ??= auth()->user()?->branch_id;

        return DB::transaction(function () use ($prefix, $date, $padding, $companyId, $branchId, $financialYearId) {
            $query = DB::table('document_sequences')->where('prefix', $prefix);

            if (\Illuminate\Support\Facades\Schema::hasColumn('document_sequences', 'company_id')) {
                $existing = (clone $query)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId), fn ($q) => $q->whereNull('company_id'))
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId), fn ($q) => $q->whereNull('branch_id'))
                    ->when($financialYearId, fn ($q) => $q->where('financial_year_id', $financialYearId), fn ($q) => $q->whereNull('financial_year_id'))
                    ->whereDate('sequence_date', $date->toDateString())
                    ->lockForUpdate()
                    ->first();

                if (! $existing) {
                    DB::table('document_sequences')->insert([
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                        'financial_year_id' => $financialYearId,
                        'prefix' => $prefix,
                        'sequence_date' => $date->toDateString(),
                        'last_number' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $sequence = DB::table('document_sequences')
                    ->where('prefix', $prefix)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId), fn ($q) => $q->whereNull('company_id'))
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId), fn ($q) => $q->whereNull('branch_id'))
                    ->when($financialYearId, fn ($q) => $q->where('financial_year_id', $financialYearId), fn ($q) => $q->whereNull('financial_year_id'))
                    ->whereDate('sequence_date', $date->toDateString())
                    ->lockForUpdate()
                    ->first();
            } else {
                DB::table('document_sequences')->insertOrIgnore([
                    'prefix' => $prefix,
                    'sequence_date' => $date->toDateString(),
                    'last_number' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sequence = DB::table('document_sequences')
                    ->where('prefix', $prefix)
                    ->whereDate('sequence_date', $date->toDateString())
                    ->lockForUpdate()
                    ->first();
            }

            if (! $sequence) {
                throw new RuntimeException("Unable to initialize document sequence for {$prefix}.");
            }

            $nextNumber = ((int) $sequence->last_number) + 1;

            DB::table('document_sequences')
                ->where('id', $sequence->id)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            return sprintf(
                '%s-%s-%0*d',
                $prefix,
                $date->format('Ymd'),
                $padding,
                $nextNumber
            );
        });
    }
}
