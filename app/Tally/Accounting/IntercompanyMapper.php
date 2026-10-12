<?php

namespace Tally\Accounting;

use Tally\Models\Company;
use Tally\Models\Ledger;
use Tally\Models\LedgerMirror;

/**
 * Resolves counterparty ledger for intercompany mirror posting.
 */
class IntercompanyMapper
{
    public function mirrorLedger(Company $sourceCompany, Company $counterparty, Ledger $sourceLedger, ?Ledger $fallbackIc = null): ?Ledger
    {
        $mapped = LedgerMirror::query()
            ->where('source_company_id', $sourceCompany->id)
            ->where('source_ledger_id', $sourceLedger->id)
            ->where('counterparty_company_id', $counterparty->id)
            ->value('counterparty_ledger_id');

        if ($mapped) {
            return Ledger::query()
                ->where('company_id', $counterparty->id)
                ->whereKey($mapped)
                ->where('is_active', true)
                ->first();
        }

        $byName = Ledger::query()
            ->where('company_id', $counterparty->id)
            ->where('is_active', true)
            ->whereRaw('lower(name) = ?', [mb_strtolower($sourceLedger->name)])
            ->first();

        if ($byName) {
            return $byName;
        }

        if ($sourceLedger->code) {
            $byCode = Ledger::query()
                ->where('company_id', $counterparty->id)
                ->where('is_active', true)
                ->where('code', $sourceLedger->code)
                ->first();

            if ($byCode) {
                return $byCode;
            }
        }

        return $fallbackIc;
    }

    /**
     * Build balanced mirror entries (swapped sides) for every source line.
     *
     * @param  list<array{ledger_id:int,debit?:mixed,credit?:mixed,narration?:?string}>  $entries
     * @return list<array{ledger_id:int,debit:mixed,credit:mixed,narration:?string}>
     */
    public function mirrorEntries(
        Company $sourceCompany,
        Company $counterparty,
        array $entries,
        Ledger $counterIc,
    ): array {
        $mirror = [];

        foreach ($entries as $entry) {
            $sourceLedger = Ledger::query()->find($entry['ledger_id']);
            if (! $sourceLedger) {
                continue;
            }

            $target = $this->mirrorLedger($sourceCompany, $counterparty, $sourceLedger, $counterIc);
            if (! $target) {
                continue;
            }

            $mirror[] = [
                'ledger_id' => $target->id,
                'debit' => $entry['credit'] ?? 0,
                'credit' => $entry['debit'] ?? 0,
                'narration' => $entry['narration'] ?? null,
            ];
        }

        return $mirror;
    }
}
