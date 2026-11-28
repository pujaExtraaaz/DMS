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
            $dateString = $date->toDateString();
            $hasCompanyScope = Schema::hasColumn('document_sequences', 'company_id');

            // Prefer company-scoped sequence rows to avoid cross-company number collisions.
            $sequenceQuery = DB::table('document_sequences')
                ->where('prefix', $prefix)
                ->whereDate('sequence_date', $dateString);

            if ($hasCompanyScope && $companyId) {
                $sequence = (clone $sequenceQuery)
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->first();

                if (! $sequence) {
                    $sequence = (clone $sequenceQuery)
                        ->whereNull('company_id')
                        ->lockForUpdate()
                        ->first();
                }
            } else {
                $sequence = $sequenceQuery->lockForUpdate()->first();
            }

            if (! $sequence) {
                $row = [
                    'prefix' => $prefix,
                    'sequence_date' => $dateString,
                    'last_number' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($hasCompanyScope) {
                    $row['company_id'] = $companyId;
                    if (Schema::hasColumn('document_sequences', 'branch_id')) {
                        $row['branch_id'] = $branchId;
                    }
                    if (Schema::hasColumn('document_sequences', 'financial_year_id')) {
                        $row['financial_year_id'] = $financialYearId;
                    }
                }

                DB::table('document_sequences')->insertOrIgnore($row);

                $reload = DB::table('document_sequences')
                    ->where('prefix', $prefix)
                    ->whereDate('sequence_date', $dateString);
                if ($hasCompanyScope && $companyId) {
                    $sequence = (clone $reload)->where('company_id', $companyId)->lockForUpdate()->first()
                        ?: (clone $reload)->whereNull('company_id')->lockForUpdate()->first();
                } else {
                    $sequence = $reload->lockForUpdate()->first();
                }
            }

            if (! $sequence) {
                throw new RuntimeException("Unable to initialize document sequence for {$prefix}.");
            }

            // Backfill company/branch when an older null-scoped row is reused.
            if ($hasCompanyScope) {
                $updates = [];
                if ($companyId && empty($sequence->company_id)) {
                    $updates['company_id'] = $companyId;
                }
                if ($branchId && Schema::hasColumn('document_sequences', 'branch_id') && empty($sequence->branch_id)) {
                    $updates['branch_id'] = $branchId;
                }
                if ($financialYearId && Schema::hasColumn('document_sequences', 'financial_year_id') && empty($sequence->financial_year_id)) {
                    $updates['financial_year_id'] = $financialYearId;
                }
                if ($updates !== []) {
                    $updates['updated_at'] = now();
                    DB::table('document_sequences')->where('id', $sequence->id)->update($updates);
                }
            }

            $nextNumber = ((int) $sequence->last_number) + 1;

            DB::table('document_sequences')
                ->where('id', $sequence->id)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            // Compact legacy format to stay within GST 16-char voucher limit.
            $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $prefix) ?: 'DOC');
            $fy = $date->format('y').$date->copy()->addYear()->format('y'); // e.g. 2627
            $maxLen = \App\Domains\Organization\Models\DocumentNumberSeries::MAX_VOUCHER_NUMBER_LENGTH;
            $pad = max(1, min(8, $padding));

            for ($tryPad = $pad; $tryPad >= 1; $tryPad--) {
                $candidate = sprintf('%s-%s-%0*d', $prefix, $fy, $tryPad, $nextNumber);
                if (strlen($candidate) <= $maxLen) {
                    return $candidate;
                }
            }

            $shortPrefix = \App\Domains\Organization\Models\DocumentNumberSeries::shortenPrefixForLimit(
                $prefix,
                $prefix,
                max(1, $maxLen - (1 + strlen($fy) + 1 + strlen((string) $nextNumber)))
            );

            $candidate = sprintf('%s-%s-%d', $shortPrefix, $fy, $nextNumber);
            if (strlen($candidate) > $maxLen) {
                throw new RuntimeException(
                    "Generated voucher number \"{$candidate}\" exceeds {$maxLen} characters. Shorten the document prefix."
                );
            }

            return $candidate;
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

    /**
     * Prefer configured voucher numbering series; fall back to legacy daily prefix sequences.
     */
    public function nextDocument(
        string $documentType,
        ?Carbon $date = null,
        int $padding = 4,
        ?int $companyId = null,
        ?int $branchId = null,
        ?callable $collisionCheck = null,
    ): string {
        $series = $this->resolveSeries($documentType, $companyId);
        if ($series && ($series->isAutomatic() || $series->isManualOverride() || $series->isMultiUserAuto())) {
            return $this->nextFromSeries($series, $date, $companyId, $collisionCheck);
        }

        return $this->next($documentType, $date, $padding, $companyId, $branchId);
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

    public function nextFromSeries(
        DocumentNumberSeries|string $seriesOrType,
        ?Carbon $date = null,
        ?int $companyId = null,
        ?callable $collisionCheck = null
    ): string {
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

        $lockKey = 'doc_series_lock_'.$series->id;
        $execute = function () use ($series, $date, $companyId, $collisionCheck) {
            return DB::transaction(function () use ($series, $date, $companyId, $collisionCheck) {
                $fy = $series->resolveFinancialYear($date);
                $attempts = 0;
                $maxAttempts = 100;

                do {
                    $next = $this->bumpSeriesCounter($series, $fy, $date, $companyId);
                    $formatted = $series->formatNumber($next, $fy, $date);
                    $attempts++;
                } while ($collisionCheck && $collisionCheck($formatted) && $attempts < $maxAttempts);

                return $formatted;
            });
        };

        try {
            return \Illuminate\Support\Facades\Cache::lock($lockKey, 10)->block(10, $execute);
        } catch (\Throwable) {
            return $execute();
        }
    }

    protected function seriesKey(DocumentNumberSeries $series, ?FinancialYear $fy, Carbon $date): string
    {
        $restartRule = $series->resolveRestartRule($date);
        if ($restartRule) {
            $periodicity = strtolower((string) ($restartRule['periodicity'] ?? 'yearly'));
            $periodKey = match ($periodicity) {
                'daily' => 'd:'.$date->format('Ymd'),
                'weekly' => 'w:'.$date->format('oW'),
                'monthly' => 'm:'.$date->format('Ym'),
                'yearly' => 'y:'.($fy?->id ? 'fy'.$fy->id : ($restartRule['applicable_from'] ?? $date->format('Y'))),
                'never' => 'never',
                default => 'p:'.$periodicity.':'.($restartRule['applicable_from'] ?? 'all'),
            };

            return strtoupper($series->document_type).'|'.$series->id.'|r:'.($restartRule['applicable_from'] ?? '0').'|'.$periodKey;
        }

        $fyPart = $series->restart_each_fy
            ? ($fy?->id ? 'fy'.$fy->id : $series->financialYearCode($fy, $date))
            : 'all';

        return strtoupper($series->document_type).'|'.$series->id.'|'.$fyPart;
    }

    protected function sequenceAnchorDate(DocumentNumberSeries $series, ?FinancialYear $fy, Carbon $date): string
    {
        $restartRule = $series->resolveRestartRule($date);
        if ($restartRule) {
            $periodicity = strtolower((string) ($restartRule['periodicity'] ?? 'yearly'));

            return match ($periodicity) {
                'daily' => $date->toDateString(),
                'weekly' => $date->copy()->startOfWeek()->toDateString(),
                'monthly' => $date->copy()->startOfMonth()->toDateString(),
                'yearly' => ! empty($restartRule['applicable_from']) ? Carbon::parse($restartRule['applicable_from'])->toDateString() : ($fy?->starts_on ? $fy->starts_on->toDateString() : '2000-01-01'),
                'never' => ! empty($restartRule['applicable_from']) ? Carbon::parse($restartRule['applicable_from'])->toDateString() : '2000-01-01',
                default => $date->toDateString(),
            };
        }

        if ($series->restart_each_fy && $fy?->starts_on) {
            return $fy->starts_on->toDateString();
        }

        return '2000-01-01';
    }

    protected function peekSeriesCounter(DocumentNumberSeries $series, ?FinancialYear $fy, Carbon $date): int
    {
        $key = $this->seriesKey($series, $fy, $date);
        $anchor = $this->sequenceAnchorDate($series, $fy, $date);
        $activePrefix = $series->resolvePrefix($date);
        $prefix = strtoupper(trim((string) $activePrefix) ?: (trim((string) $series->prefix) ?: $series->document_type));

        $query = DB::table('document_sequences')->where('prefix', $prefix);

        if (Schema::hasColumn('document_sequences', 'series_key')) {
            $query->where('series_key', $key);
        }

        if (Schema::hasColumn('document_sequences', 'financial_year_id') && $series->restart_each_fy && $fy && ! $series->resolveRestartRule($date)) {
            $query->where('financial_year_id', $fy->id);
        }

        $sequence = $query->whereDate('sequence_date', $anchor)->first();

        if (! $sequence) {
            $restartRule = $series->resolveRestartRule($date);

            return max(1, (int) ($restartRule['starting_number'] ?? $series->starting_number ?? 1));
        }

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
        $activePrefix = $series->resolvePrefix($date);
        $prefix = strtoupper(trim((string) $activePrefix) ?: (trim((string) $series->prefix) ?: $series->document_type));
        $restartRule = $series->resolveRestartRule($date);
        $fyId = ($series->restart_each_fy && $fy && ! $restartRule) ? $fy->id : null;

        $startingNumber = max(1, (int) ($restartRule['starting_number'] ?? $series->starting_number ?? 1));
        $initialLastNumber = $startingNumber - 1;

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
                'last_number' => $initialLastNumber,
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

            try {
                DB::table('document_sequences')->insert($row);
            } catch (\Throwable) {
                // Ignore race condition if another concurrent thread already inserted the sequence row
            }

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
