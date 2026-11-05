<?php

namespace App\Support;

use App\Domains\Organization\Models\DocumentNumberSeries;
use App\Domains\Organization\Models\FinancialYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

            if (Schema::hasColumn('document_sequences', 'company_id')) {
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

    public function resolveSeries(string $documentType, ?int $companyId = null): ?DocumentNumberSeries
    {
        if (! Schema::hasTable('document_number_series')) {
            return null;
        }

        $companyId ??= auth()->user()?->company_id;

        $query = DocumentNumberSeries::query()
            ->where('document_type', $documentType)
            ->where('is_active', true);

        $series = (clone $query)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->first();

        if ($series) {
            return $series;
        }

        return $query->whereNull('company_id')->first();
    }

    public function peekFromSeries(DocumentNumberSeries|string $seriesOrType, ?Carbon $date = null, ?int $companyId = null): ?string
    {
        $series = $seriesOrType instanceof DocumentNumberSeries
            ? $seriesOrType
            : $this->resolveSeries($seriesOrType, $companyId);

        if (! $series || $series->isManual()) {
            return null;
        }

        $date ??= now();
        $fy = $series->resolveFinancialYear($date);
        $next = $this->peekSeriesCounter($series, $fy, $date);

        return $series->formatNumber($next, $fy, $date);
    }

    public function nextFromSeries(DocumentNumberSeries|string $seriesOrType, ?Carbon $date = null, ?int $companyId = null): string
    {
        $series = $seriesOrType instanceof DocumentNumberSeries
            ? $seriesOrType
            : $this->resolveSeries($seriesOrType, $companyId);

        if (! $series) {
            $type = is_string($seriesOrType) ? $seriesOrType : 'DOC';

            return $this->next($type, $date, 4, $companyId);
        }

        if ($series->isManual()) {
            throw new RuntimeException('Document series is configured for manual numbering.');
        }

        $date ??= now();
        $companyId ??= $series->company_id ?? auth()->user()?->company_id;

        return DB::transaction(function () use ($series, $date, $companyId) {
            $fy = $series->resolveFinancialYear($date);
            $next = $this->bumpSeriesCounter($series, $fy, $date, $companyId);

            return $series->formatNumber($next, $fy, $date);
        });
    }

    protected function seriesKey(DocumentNumberSeries $series, ?FinancialYear $fy, Carbon $date): string
    {
        $fyPart = $series->restart_each_fy
            ? ($fy?->id ? 'fy'.$fy->id : $series->financialYearCode($fy, $date))
            : 'all';

        return strtoupper($series->document_type).'|'.$series->id.'|'.$fyPart;
    }

    protected function sequenceAnchorDate(DocumentNumberSeries $series, ?FinancialYear $fy, Carbon $date): string
    {
        if ($series->restart_each_fy && $fy?->starts_on) {
            return $fy->starts_on->toDateString();
        }

        return '2000-01-01';
    }

    protected function peekSeriesCounter(DocumentNumberSeries $series, ?FinancialYear $fy, Carbon $date): int
    {
        $key = $this->seriesKey($series, $fy, $date);
        $anchor = $this->sequenceAnchorDate($series, $fy, $date);
        $prefix = strtoupper(trim((string) $series->prefix) ?: $series->document_type);

        $query = DB::table('document_sequences')->where('prefix', $prefix);

        if (Schema::hasColumn('document_sequences', 'series_key')) {
            $query->where('series_key', $key);
        }

        if (Schema::hasColumn('document_sequences', 'financial_year_id') && $series->restart_each_fy && $fy) {
            $query->where('financial_year_id', $fy->id);
        }

        $sequence = $query->whereDate('sequence_date', $anchor)->first();

        return ((int) ($sequence->last_number ?? 0)) + 1;
    }

    protected function bumpSeriesCounter(
        DocumentNumberSeries $series,
        ?FinancialYear $fy,
        Carbon $date,
        ?int $companyId
    ): int {
        $key = $this->seriesKey($series, $fy, $date);
        $anchor = $this->sequenceAnchorDate($series, $fy, $date);
        $prefix = strtoupper(trim((string) $series->prefix) ?: $series->document_type);
        $fyId = ($series->restart_each_fy && $fy) ? $fy->id : null;

        $query = DB::table('document_sequences')->where('prefix', $prefix);

        if (Schema::hasColumn('document_sequences', 'series_key')) {
            $query->where('series_key', $key);
        } else {
            $query->whereDate('sequence_date', $anchor);
            if (Schema::hasColumn('document_sequences', 'financial_year_id')) {
                $query->when($fyId, fn ($q) => $q->where('financial_year_id', $fyId), fn ($q) => $q->whereNull('financial_year_id'));
            }
        }

        $existing = (clone $query)->lockForUpdate()->first();

        if (! $existing) {
            $row = [
                'prefix' => $prefix,
                'sequence_date' => $anchor,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('document_sequences', 'company_id')) {
                $row['company_id'] = $companyId;
            }
            if (Schema::hasColumn('document_sequences', 'branch_id')) {
                $row['branch_id'] = null;
            }
            if (Schema::hasColumn('document_sequences', 'financial_year_id')) {
                $row['financial_year_id'] = $fyId;
            }
            if (Schema::hasColumn('document_sequences', 'series_key')) {
                $row['series_key'] = $key;
            }

            DB::table('document_sequences')->insert($row);
            $existing = (clone $query)->lockForUpdate()->first();
        }

        if (! $existing) {
            throw new RuntimeException("Unable to initialize series sequence for {$series->document_type}.");
        }

        $nextNumber = ((int) $existing->last_number) + 1;

        DB::table('document_sequences')
            ->where('id', $existing->id)
            ->update([
                'last_number' => $nextNumber,
                'updated_at' => now(),
            ]);

        return $nextNumber;
    }
}
