<?php

namespace Tally\Accounting;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\Currency;
use Tally\Models\FinancialYear;
use Tally\Models\Ledger;
use Tally\Models\Voucher;
use Tally\Models\VoucherEntry;

class FxRevaluationService
{
    public function __construct(
        private readonly VoucherEngine $engine,
        private readonly FxRateBook $rates,
    ) {}

    /**
     * Revalue foreign-currency open balances to as-on rates; posts unrealized gain/loss journal.
     *
     * @return array{voucher:?Voucher, lines: list<array{ledger:string, amount:string, side:string}>}
     */
    public function revalue(
        Company $company,
        Branch $branch,
        FinancialYear $year,
        User $user,
        string $asOn,
        int $gainLossLedgerId,
        ?int $currencyId = null,
    ): array {
        $gainLoss = Ledger::query()
            ->where('company_id', $company->id)
            ->whereKey($gainLossLedgerId)
            ->first();

        if (! $gainLoss) {
            throw ValidationException::withMessages([
                'gain_loss_ledger_id' => 'Select a valid FX gain/loss ledger.',
            ]);
        }

        $currencies = Currency::query()
            ->where('company_id', $company->id)
            ->where('is_base', false)
            ->when($currencyId, fn ($q) => $q->whereKey($currencyId))
            ->get();

        $entries = [];
        $preview = [];

        foreach ($currencies as $currency) {
            $newRate = $this->rates->rateOn($company, $currency, $asOn);
            if ($newRate <= 0) {
                continue;
            }

            $lines = VoucherEntry::query()
                ->join('acct_vouchers', 'acct_vouchers.id', '=', 'acct_voucher_entries.voucher_id')
                ->where('acct_vouchers.company_id', $company->id)
                ->where('acct_vouchers.status', VoucherStatus::Posted)
                ->where('acct_vouchers.currency_id', $currency->id)
                ->whereDate('acct_vouchers.voucher_date', '<=', $asOn)
                ->when($branch->id, fn ($q) => $q->where('acct_vouchers.branch_id', $branch->id))
                ->select(
                    'acct_voucher_entries.ledger_id',
                    DB::raw('sum(acct_voucher_entries.debit) as debit_sum'),
                    DB::raw('sum(acct_voucher_entries.credit) as credit_sum'),
                    DB::raw('avg(acct_vouchers.exchange_rate) as avg_rate'),
                )
                ->groupBy('acct_voucher_entries.ledger_id')
                ->get();

            foreach ($lines as $line) {
                $foreign = Money::cents((string) $line->debit_sum) - Money::cents((string) $line->credit_sum);
                if ($foreign === 0) {
                    continue;
                }

                $bookRate = (float) ($line->avg_rate ?: $currency->exchange_rate ?: 1);
                if ($bookRate <= 0) {
                    continue;
                }

                $bookBase = (int) round($foreign * $bookRate);
                $revalued = (int) round($foreign * $newRate);
                $diff = $revalued - $bookBase;

                if ($diff === 0) {
                    continue;
                }

                $ledger = Ledger::query()->find($line->ledger_id);
                $preview[] = [
                    'ledger' => $ledger?->name ?? '#'.$line->ledger_id,
                    'amount' => Money::format(abs($diff)),
                    'side' => $diff > 0 ? 'debit' : 'credit',
                ];

                if ($diff > 0) {
                    $entries[] = ['ledger_id' => (int) $line->ledger_id, 'debit' => Money::format($diff), 'credit' => 0];
                    $entries[] = ['ledger_id' => $gainLoss->id, 'debit' => 0, 'credit' => Money::format($diff)];
                } else {
                    $entries[] = ['ledger_id' => (int) $line->ledger_id, 'debit' => 0, 'credit' => Money::format(abs($diff))];
                    $entries[] = ['ledger_id' => $gainLoss->id, 'debit' => Money::format(abs($diff)), 'credit' => 0];
                }
            }
        }

        if ($entries === []) {
            return ['voucher' => null, 'lines' => []];
        }

        $voucher = $this->engine->save(
            $company,
            $branch,
            $year,
            $user,
            VoucherType::Journal,
            [
                'voucher_date' => $asOn,
                'reference_number' => 'FX-REV-'.$asOn,
                'narration' => 'FX revaluation as on '.$asOn,
                'entries' => $entries,
            ],
            true,
        );

        return ['voucher' => $voucher, 'lines' => $preview];
    }
}
