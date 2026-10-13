<?php

namespace Tally\Accounting;

use App\Models\User;
use Tally\Models\Company;
use Tally\Models\ConsolidationMap;
use Tally\Models\FinancialYear;
use Tally\Models\Ledger;
use Tally\Models\Voucher;
use Tally\Reporting\FinancialReports;

class ConsolidationService
{
    public function __construct(
        private readonly FinancialReports $reports,
        private readonly IntercompanyService $intercompany,
    ) {}

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ConsolidationMap>
     */
    public function mapsFor(Company $parent)
    {
        return ConsolidationMap::query()
            ->with('subsidiaryCompany')
            ->where('parent_company_id', $parent->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    /**
     * Consolidated totals = sum of trial balance nets for parent + subsidiaries,
     * with ownership_percent applied as group_share on subsidiary net.
     *
     * @return array{maps: mixed, totals: array{debit: string, credit: string, companies: list<array{company_id: int, name: string, debit: string, credit: string, net: string, ownership_percent: string, group_share: string}>}}
     */
    public function consolidatedTotals(Company $parent, string $asOf): array
    {
        $maps = $this->mapsFor($parent);
        $ownershipByCompany = $maps->mapWithKeys(
            fn (ConsolidationMap $map) => [(int) $map->subsidiary_company_id => (float) $map->ownership_percent]
        );

        $companies = collect([$parent])->merge($maps->pluck('subsidiaryCompany')->filter());
        $companyRows = [];
        $debitTotal = 0;
        $creditTotal = 0;

        foreach ($companies as $company) {
            /** @var Company $company */
            $year = FinancialYear::query()
                ->where('company_id', $company->id)
                ->whereDate('start_date', '<=', $asOf)
                ->whereDate('end_date', '>=', $asOf)
                ->first()
                ?? $company->financialYears()->orderByDesc('start_date')->first();

            $debit = 0;
            $credit = 0;

            if ($year) {
                $tb = $this->reports->trialBalance($company, null, $year, $asOf);
                foreach ($tb['rows'] ?? [] as $row) {
                    $debit += Money::cents((string) ($row['debit'] ?? 0));
                    $credit += Money::cents((string) ($row['credit'] ?? 0));
                }
            }

            $debitTotal += $debit;
            $creditTotal += $credit;
            $net = $debit - $credit;
            $isParent = $company->id === $parent->id;
            $ownership = $isParent ? 100.0 : (float) ($ownershipByCompany[$company->id] ?? 100);
            $groupShare = (int) round($net * ($ownership / 100));

            $companyRows[] = [
                'company_id' => $company->id,
                'name' => $company->name,
                'debit' => Money::format($debit),
                'credit' => Money::format($credit),
                'net' => Money::format($net),
                'ownership_percent' => number_format($ownership, 2, '.', ''),
                'group_share' => Money::format($groupShare),
            ];
        }

        return [
            'maps' => $maps,
            'totals' => [
                'debit' => Money::format($debitTotal),
                'credit' => Money::format($creditTotal),
                'companies' => $companyRows,
            ],
        ];
    }

    /**
     * Build consolidation worksheet rows per mapped subsidiary (IC worksheet or TB + IC pairs).
     *
     * @return array{from: string, to: string, subsidiaries: list<array<string, mixed>>, parent_ic: array<string, mixed>|null}
     */
    public function worksheet(Company $parent, ?string $from = null, ?string $to = null): array
    {
        $year = $parent->financialYears()->where('is_active', true)->orderByDesc('start_date')->first()
            ?? $parent->financialYears()->orderByDesc('start_date')->first();

        $from ??= $year?->start_date?->toDateString() ?? now()->startOfYear()->toDateString();
        $to ??= $year?->end_date?->toDateString() ?? now()->toDateString();

        $maps = $this->mapsFor($parent);
        $subsidiaries = [];

        foreach ($maps as $map) {
            $sub = $map->subsidiaryCompany;
            if (! $sub) {
                continue;
            }

            $icWorksheet = null;
            if (method_exists($this->intercompany, 'consolidationWorksheet')) {
                $icWorksheet = $this->intercompany->consolidationWorksheet($sub, $from, $to);
            }

            $subYear = FinancialYear::query()
                ->where('company_id', $sub->id)
                ->whereDate('start_date', '<=', $to)
                ->whereDate('end_date', '>=', $from)
                ->first()
                ?? $sub->financialYears()->orderByDesc('start_date')->first();

            $tb = $subYear
                ? $this->reports->trialBalance($sub, null, $subYear, $to)
                : ['rows' => []];

            $icPairs = $icWorksheet['rows'] ?? $this->listIcPairs($sub, $from, $to);

            $subsidiaries[] = [
                'map_id' => $map->id,
                'company_id' => $sub->id,
                'name' => $sub->name,
                'ownership_percent' => (string) $map->ownership_percent,
                'trial_balance' => $tb,
                'ic_pairs' => $icPairs,
                'ic_worksheet' => $icWorksheet,
            ];
        }

        $parentIc = method_exists($this->intercompany, 'consolidationWorksheet')
            ? $this->intercompany->consolidationWorksheet($parent, $from, $to)
            : ['rows' => $this->listIcPairs($parent, $from, $to), 'total' => '0.00', 'eliminated_total' => '0.00', 'open_total' => '0.00'];

        return [
            'from' => $from,
            'to' => $to,
            'subsidiaries' => $subsidiaries,
            'parent_ic' => $parentIc,
        ];
    }

    /**
     * Post IC eliminations for each mapped subsidiary pair (parent + each sub).
     *
     * @return list<Voucher>
     */
    public function postEliminationsForGroup(
        Company $parent,
        User $user,
        ?string $from = null,
        ?string $to = null,
        ?int $eliminationLedgerId = null,
    ): array {
        if (! method_exists($this->intercompany, 'postEliminations')) {
            return [];
        }

        $year = $parent->financialYears()->where('is_active', true)->orderByDesc('start_date')->first()
            ?? $parent->financialYears()->orderByDesc('start_date')->first();
        $from ??= $year?->start_date?->toDateString() ?? now()->startOfYear()->toDateString();
        $to ??= $year?->end_date?->toDateString() ?? now()->toDateString();

        $posted = [];
        $targets = collect([$parent])->merge(
            $this->mapsFor($parent)->pluck('subsidiaryCompany')->filter()
        )->unique('id');

        foreach ($targets as $company) {
            /** @var Company $company */
            $branch = $company->branches()->where('is_active', true)->orderBy('id')->first();
            $fy = FinancialYear::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->whereDate('start_date', '<=', $to)
                ->whereDate('end_date', '>=', $from)
                ->first()
                ?? $company->financialYears()->where('is_active', true)->orderByDesc('start_date')->first();

            if (! $branch || ! $fy) {
                continue;
            }

            $elimLedgerId = $eliminationLedgerId
                ?? $this->resolveEliminationLedgerId($company);

            if (! $elimLedgerId) {
                continue;
            }

            $result = $this->intercompany->postEliminations(
                $company,
                $branch,
                $fy,
                $user,
                $from,
                $to,
                $elimLedgerId,
            );

            foreach ($result as $voucher) {
                $posted[] = $voucher;
            }
        }

        return $posted;
    }

    /**
     * @param  array{subsidiary_company_id: int, ownership_percent?: mixed, reporting_currency?: string, is_active?: bool}  $data
     */
    public function upsertMap(Company $parent, array $data): ConsolidationMap
    {
        return ConsolidationMap::query()->updateOrCreate(
            [
                'parent_company_id' => $parent->id,
                'subsidiary_company_id' => (int) $data['subsidiary_company_id'],
            ],
            [
                'ownership_percent' => $data['ownership_percent'] ?? 100,
                'reporting_currency' => $data['reporting_currency'] ?? 'INR',
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]
        );
    }

    /**
     * @return list<array{pair_id: string, amount: string, eliminated: bool}>
     */
    private function listIcPairs(Company $company, string $from, string $to): array
    {
        if (method_exists($this->intercompany, 'eliminationRows')) {
            return $this->intercompany->eliminationRows($company, $from, $to);
        }

        return [];
    }

    private function resolveEliminationLedgerId(Company $company): ?int
    {
        $named = Ledger::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', '%Elimination%')
                    ->orWhere('code', 'ELIM');
            })
            ->orderBy('id')
            ->first();
        if ($named) {
            return $named->id;
        }

        $ic = Ledger::query()
            ->where('company_id', $company->id)
            ->where('is_intercompany', true)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        $offset = Ledger::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->when($ic, fn ($q) => $q->whereKeyNot($ic->id))
            ->whereHas('accountGroup', fn ($q) => $q->whereIn('code', ['CURRENT_LIABILITIES', 'RESERVES']))
            ->orderBy('id')
            ->first();

        return $offset?->id ?? $ic?->id;
    }
}
