<?php

namespace Tally\Accounting;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Ledger;
use Tally\Models\Voucher;

class IntercompanyService
{
    public function __construct(
        private readonly VoucherEngine $engine,
        private readonly IntercompanyMapper $mapper,
    ) {}

    /**
     * Post a journal in company A and the mirror journal in company B, linked by pair id.
     *
     * @param  list<array{ledger_id:int,debit?:mixed,credit?:mixed,narration?:?string}>  $entries
     * @return array{source:Voucher,mirror:Voucher}
     */
    public function postPairedJournal(
        Company $sourceCompany,
        Branch $sourceBranch,
        FinancialYear $sourceYear,
        Company $counterparty,
        Branch $counterBranch,
        FinancialYear $counterYear,
        User $user,
        string $date,
        array $entries,
        ?string $narration = null,
        ?int $sourceIcLedgerId = null,
        ?int $counterIcLedgerId = null,
    ): array {
        $sourceIc = $sourceIcLedgerId
            ? Ledger::query()->where('company_id', $sourceCompany->id)->whereKey($sourceIcLedgerId)->first()
            : $this->findIcLedger($sourceCompany, $counterparty);

        $counterIc = $counterIcLedgerId
            ? Ledger::query()->where('company_id', $counterparty->id)->whereKey($counterIcLedgerId)->first()
            : $this->findIcLedger($counterparty, $sourceCompany);

        if (! $sourceIc || ! $counterIc) {
            throw ValidationException::withMessages([
                'intercompany' => 'Mark intercompany ledgers on both companies before posting.',
            ]);
        }

        $pairId = (string) Str::uuid();
        $net = 0;

        foreach ($entries as $entry) {
            $net += Money::cents($entry['debit'] ?? 0) - Money::cents($entry['credit'] ?? 0);
        }

        return DB::transaction(function () use (
            $sourceCompany, $sourceBranch, $sourceYear,
            $counterparty, $counterBranch, $counterYear,
            $user, $date, $entries, $narration, $sourceIc, $counterIc, $pairId, $net
        ) {
            $sourceEntries = $entries;
            if ($net > 0) {
                $sourceEntries[] = ['ledger_id' => $sourceIc->id, 'debit' => 0, 'credit' => Money::format($net)];
            } elseif ($net < 0) {
                $sourceEntries[] = ['ledger_id' => $sourceIc->id, 'debit' => Money::format(abs($net)), 'credit' => 0];
            }

            $source = $this->engine->save(
                $sourceCompany,
                $sourceBranch,
                $sourceYear,
                $user,
                VoucherType::Journal,
                [
                    'voucher_date' => $date,
                    'narration' => $narration ?: 'Intercompany to '.$counterparty->name,
                    'entries' => $sourceEntries,
                    'intercompany_pair_id' => $pairId,
                    'counterparty_company_id' => $counterparty->id,
                ],
                true,
            );

            $mirrorEntries = $this->mapper->mirrorEntries($sourceCompany, $counterparty, $entries, $counterIc);

            if ($mirrorEntries === []) {
                throw ValidationException::withMessages([
                    'intercompany' => 'Could not map mirror ledgers. Add ledger mirrors or matching ledger names/codes in the counterparty company.',
                ]);
            }

            $mirrorNet = 0;
            foreach ($mirrorEntries as $entry) {
                $mirrorNet += Money::cents($entry['debit'] ?? 0) - Money::cents($entry['credit'] ?? 0);
            }

            if ($mirrorNet > 0) {
                $mirrorEntries[] = ['ledger_id' => $counterIc->id, 'debit' => 0, 'credit' => Money::format($mirrorNet)];
            } elseif ($mirrorNet < 0) {
                $mirrorEntries[] = ['ledger_id' => $counterIc->id, 'debit' => Money::format(abs($mirrorNet)), 'credit' => 0];
            }

            $mirror = $this->engine->save(
                $counterparty,
                $counterBranch,
                $counterYear,
                $user,
                VoucherType::Journal,
                [
                    'voucher_date' => $date,
                    'narration' => $narration ?: 'Intercompany from '.$sourceCompany->name,
                    'entries' => $mirrorEntries,
                    'intercompany_pair_id' => $pairId,
                    'counterparty_company_id' => $sourceCompany->id,
                ],
                true,
            );

            return ['source' => $source, 'mirror' => $mirror];
        });
    }

    /**
     * @return list<array{pair_id:string,source_company:string,counterparty:string,date:string,amount:string,voucher_count:int,source_voucher_id:?int,mirror_voucher_id:?int}>
     */
    public function eliminationRows(Company $company, string $from, string $to): array
    {
        $vouchers = Voucher::query()
            ->with('company')
            ->whereNotNull('intercompany_pair_id')
            ->where('status', VoucherStatus::Posted)
            ->where(function ($q) use ($company) {
                $q->where('company_id', $company->id)
                    ->orWhere('counterparty_company_id', $company->id);
            })
            ->whereDate('voucher_date', '>=', $from)
            ->whereDate('voucher_date', '<=', $to)
            ->orderBy('voucher_date')
            ->get()
            ->groupBy('intercompany_pair_id');

        $rows = [];

        foreach ($vouchers as $pairId => $group) {
            $source = $group->firstWhere('company_id', $company->id) ?? $group->first();
            $mirror = $group->first(fn ($v) => $v->id !== $source->id);

            $rows[] = [
                'pair_id' => (string) $pairId,
                'source_company' => $source->company?->name ?? '',
                'counterparty' => Company::query()->find($source->counterparty_company_id)?->name ?? '',
                'date' => $source->voucher_date?->toDateString() ?? '',
                'amount' => (string) $source->total_debit,
                'voucher_count' => $group->count(),
                'source_voucher_id' => $source->id,
                'mirror_voucher_id' => $mirror?->id,
                'eliminated' => Voucher::query()
                    ->where('company_id', $company->id)
                    ->where('reference_number', 'ELIM-'.$pairId)
                    ->where('status', VoucherStatus::Posted)
                    ->exists(),
            ];
        }

        return $rows;
    }

    /**
     * Consolidation worksheet: IC pairs with elimination status.
     *
     * @return array{rows: list<array<string, mixed>>, total: string, eliminated_total: string}
     */
    public function consolidationWorksheet(Company $company, string $from, string $to): array
    {
        $rows = $this->eliminationRows($company, $from, $to);
        $total = 0;
        $eliminated = 0;

        foreach ($rows as $row) {
            $cents = Money::cents($row['amount']);
            $total += $cents;
            if ($row['eliminated'] ?? false) {
                $eliminated += $cents;
            }
        }

        return [
            'rows' => $rows,
            'total' => Money::format($total),
            'eliminated_total' => Money::format($eliminated),
            'open_total' => Money::format($total - $eliminated),
        ];
    }

    /**
     * @return list<Voucher>
     */
    public function postEliminations(
        Company $company,
        Branch $branch,
        FinancialYear $year,
        User $user,
        string $from,
        string $to,
        int $eliminationLedgerId,
    ): array {
        $rows = $this->eliminationRows($company, $from, $to);
        $posted = [];

        foreach ($rows as $row) {
            if ($row['eliminated'] ?? false) {
                continue;
            }

            $pairId = $row['pair_id'];
            $amount = Money::cents($row['amount']);
            if ($amount <= 0) {
                continue;
            }

            $counter = Company::query()->where('name', $row['counterparty'])->first();
            $ic = ($counter ? $this->findIcLedger($company, $counter) : null)
                ?? Ledger::query()->whereKey($eliminationLedgerId)->where('company_id', $company->id)->first();

            if (! $ic || $ic->id === $eliminationLedgerId) {
                $ic = Ledger::query()
                    ->where('company_id', $company->id)
                    ->where('is_intercompany', true)
                    ->where('is_active', true)
                    ->whereKeyNot($eliminationLedgerId)
                    ->first() ?? $ic;
            }

            if (! $ic || $ic->id === $eliminationLedgerId) {
                continue;
            }

            $posted[] = $this->engine->save(
                $company,
                $branch,
                $year,
                $user,
                VoucherType::Journal,
                [
                    'voucher_date' => $to,
                    'reference_number' => 'ELIM-'.$pairId,
                    'narration' => 'Elimination for IC pair '.$pairId,
                    'entries' => [
                        ['ledger_id' => $ic->id, 'debit' => 0, 'credit' => Money::format($amount)],
                        ['ledger_id' => $eliminationLedgerId, 'debit' => Money::format($amount), 'credit' => 0],
                    ],
                    'intercompany_pair_id' => $pairId,
                ],
                true,
            );
        }

        return $posted;
    }

    private function findIcLedger(Company $company, Company $counterparty): ?Ledger
    {
        return Ledger::query()
            ->where('company_id', $company->id)
            ->where('is_intercompany', true)
            ->where(function ($q) use ($counterparty) {
                $q->where('intercompany_company_id', $counterparty->id)
                    ->orWhereNull('intercompany_company_id');
            })
            ->where('is_active', true)
            ->orderByRaw('case when intercompany_company_id is null then 1 else 0 end')
            ->first();
    }
}
