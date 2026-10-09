<?php

namespace Tally\Reporting;

use Tally\Accounting\AccountNature;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherStatus;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Ledger;
use Tally\Models\Voucher;
use Tally\Models\VoucherEntry;
use Tally\Support\Queries\DateRange;

class FinancialReports
{
    public function __construct(private readonly LedgerBalances $balances) {}

    /**
     * @return array{rows: list<array<string, mixed>>, debit: string, credit: string}
     */
    public function dayBook(Company $company, ?int $branchId, FinancialYear $year, string $from, string $to, string $search = ''): array
    {
        $like = '%'.addcslashes($search, '%_\\').'%';
        $vouchers = Voucher::query()
            ->with('entries.ledger')
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->where('status', VoucherStatus::Posted)
            ->tap(fn ($query) => DateRange::apply($query, 'voucher_date', $from, $to))
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($search !== '', function ($query) use ($like) {
                $query->where(function ($query) use ($like) {
                    $query->where('voucher_number', 'like', $like)
                        ->orWhere('narration', 'like', $like)
                        ->orWhere('reference_number', 'like', $like);
                });
            })
            ->orderBy('voucher_date')
            ->orderBy('id');
        $debit = $this->reportCents((clone $vouchers)->sum('total_debit'));
        $credit = $this->reportCents((clone $vouchers)->sum('total_credit'));
        $page = $vouchers->with(['entries.ledger', 'invoice'])->paginate(100)->withQueryString();
        $rows = [];

        foreach ($page as $voucher) {
            $rows[] = [
                'date' => $voucher->voucher_date->format('d M Y'),
                'number' => $voucher->voucher_number,
                'type' => $voucher->voucher_type->label(),
                'particulars' => $voucher->ledgerSummary(),
                'narration' => $voucher->narration,
                'debit' => $voucher->total_debit,
                'credit' => $voucher->total_credit,
                'url' => $this->dayBookUrl($voucher),
            ];
        }

        return [
            'rows' => $rows,
            'paginator' => $page,
            'debit' => Money::format($debit),
            'credit' => Money::format($credit),
        ];
    }

    /**
     * @return array{ledger: ?Ledger, opening: string, opening_side: string, rows: list<array<string, mixed>>, closing: string, closing_side: string, debit: string, credit: string}
     */
    public function ledger(Company $company, ?int $branchId, FinancialYear $year, ?int $ledgerId, string $from, string $to): array
    {
        $ledger = $ledgerId
            ? $company->ledgers()->whereKey($ledgerId)->first()
            : null;
        $empty = [
            'ledger' => $ledger,
            'opening' => '0.00',
            'opening_side' => 'Dr',
            'rows' => [],
            'closing' => '0.00',
            'closing_side' => 'Dr',
            'debit' => '0.00',
            'credit' => '0.00',
        ];

        if (! $ledger) {
            return $empty;
        }

        $opening = $this->signedOpening($ledger, $company, $branchId, $year, $from);
        $lines = VoucherEntry::query()
            ->with('voucher')
            ->where('ledger_id', $ledger->id)
            ->whereHas('voucher', function ($query) use ($company, $branchId, $year, $from, $to) {
                $query->where('company_id', $company->id)
                    ->where('financial_year_id', $year->id)
                    ->where('status', VoucherStatus::Posted)
                    ->tap(fn ($query) => DateRange::apply($query, 'voucher_date', $from, $to))
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId));
            })
            ->get()
            ->sortBy(fn (VoucherEntry $entry) => $entry->voucher->voucher_date->toDateString().'-'.str_pad((string) $entry->voucher_id, 8, '0', STR_PAD_LEFT));

        $running = $opening;
        $debit = 0;
        $credit = 0;
        $rows = [];

        foreach ($lines as $entry) {
            $lineDebit = Money::cents((string) $entry->debit);
            $lineCredit = Money::cents((string) $entry->credit);
            $running += $lineDebit - $lineCredit;
            $debit += $lineDebit;
            $credit += $lineCredit;
            $rows[] = [
                'date' => $entry->voucher->voucher_date->format('d M Y'),
                'number' => $entry->voucher->voucher_number,
                'type' => $entry->voucher->voucher_type->label(),
                'narration' => $entry->narration ?: $entry->voucher->narration,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'balance' => Money::format(abs($running)),
                'side' => $running >= 0 ? 'Dr' : 'Cr',
                'url' => tally_route('vouchers.show', $entry->voucher),
            ];
        }

        return [
            'ledger' => $ledger,
            'opening' => Money::format(abs($opening)),
            'opening_side' => $opening >= 0 ? 'Dr' : 'Cr',
            'rows' => $rows,
            'closing' => Money::format(abs($running)),
            'closing_side' => $running >= 0 ? 'Dr' : 'Cr',
            'debit' => Money::format($debit),
            'credit' => Money::format($credit),
        ];
    }

    /**
     * @return array{rows: list<array<string, string>>, debit: string, credit: string}
     */
    public function trialBalance(Company $company, ?int $branchId, FinancialYear $year, string $to): array
    {
        $debit = 0;
        $credit = 0;
        $rows = [];

        foreach ($this->balances->asOn($company, $branchId, $year, $to) as $row) {
            if ($row['debit'] === 0 && $row['credit'] === 0) {
                continue;
            }

            $debit += $row['debit'];
            $credit += $row['credit'];
            $rows[] = [
                'ledger' => $row['ledger']->name,
                'group' => $row['ledger']->accountGroup?->name ?? '—',
                'debit' => $row['debit'] > 0 ? Money::format($row['debit']) : '',
                'credit' => $row['credit'] > 0 ? Money::format($row['credit']) : '',
            ];
        }

        $foreign = $this->foreignTrial($company, $branchId, $to);

        foreach ($rows as &$row) {
            $extra = $foreign[$row['ledger']] ?? ['debit' => 0, 'credit' => 0];
            $row['foreign_debit'] = $extra['debit'] > 0 ? Money::format($extra['debit']) : '';
            $row['foreign_credit'] = $extra['credit'] > 0 ? Money::format($extra['credit']) : '';
        }
        unset($row);

        return [
            'rows' => $rows,
            'debit' => Money::format($debit),
            'credit' => Money::format($credit),
            'foreign' => $foreign !== [],
        ];
    }

    /**
     * Base posted amounts converted back by the voucher exchange rate.
     *
     * @return array<string, array{debit: int, credit: int}>
     */
    private function foreignTrial(Company $company, ?int $branchId, string $to): array
    {
        $lines = \Tally\Models\VoucherEntry::query()
            ->join('acct_vouchers', 'acct_vouchers.id', '=', 'acct_voucher_entries.voucher_id')
            ->join('acct_ledgers', 'acct_ledgers.id', '=', 'acct_voucher_entries.ledger_id')
            ->where('acct_vouchers.company_id', $company->id)
            ->where('acct_vouchers.status', VoucherStatus::Posted)
            ->whereNotNull('acct_vouchers.currency_id')
            ->where('acct_vouchers.exchange_rate', '>', 0)
            ->whereDate('acct_vouchers.voucher_date', '<=', $to)
            ->when($branchId, fn ($query) => $query->where('acct_vouchers.branch_id', $branchId))
            ->get(['acct_ledgers.name as ledger_name', 'acct_voucher_entries.debit', 'acct_voucher_entries.credit', 'acct_vouchers.exchange_rate']);
        $rows = [];

        foreach ($lines as $line) {
            $rate = (float) $line->exchange_rate;

            if ($rate <= 0) {
                continue;
            }

            $name = (string) $line->ledger_name;
            $rows[$name] ??= ['debit' => 0, 'credit' => 0];
            $rows[$name]['debit'] += (int) round(Money::cents((string) $line->debit) / $rate);
            $rows[$name]['credit'] += (int) round(Money::cents((string) $line->credit) / $rate);
        }

        return array_filter($rows, fn (array $row) => $row['debit'] !== 0 || $row['credit'] !== 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function profitAndLoss(Company $company, ?int $branchId, FinancialYear $year, string $to): array
    {
        $directIncome = [];
        $directExpense = [];
        $cogs = [];
        $indirectIncome = [];
        $indirectExpense = [];
        $closingInventory = 0;

        foreach ($this->balances->asOn($company, $branchId, $year, $to) as $row) {
            if (in_array($row['ledger']->code, ['STOCK', 'RAW', 'FG'], true)) {
                $closingInventory += $row['debit'] - $row['credit'];
            }

            if (! in_array($row['nature'], [AccountNature::Income, AccountNature::Expense], true)) {
                continue;
            }

            $direct = array_intersect($row['codes'], ['SALES', 'DIRECT_INCOMES', 'PURCHASE', 'DIRECT_EXPENSES']) !== [];
            $income = $row['nature'] === AccountNature::Income;
            $amount = $income ? $row['credit'] - $row['debit'] : $row['debit'] - $row['credit'];

            if ($amount === 0) {
                continue;
            }

            $line = ['name' => $row['ledger']->name, 'amount' => Money::format($amount)];

            if ($row['ledger']->code === 'COGS') {
                $cogs[] = $line;
            } elseif ($income && $direct) {
                $directIncome[] = $line;
            } elseif ($income) {
                $indirectIncome[] = $line;
            } elseif ($direct) {
                $directExpense[] = $line;
            } else {
                $indirectExpense[] = $line;
            }
        }

        $directIncomeTotal = $this->sum($directIncome);
        $directExpenseTotal = $this->sum($directExpense) + $this->sum($cogs);
        $gross = $directIncomeTotal - $directExpenseTotal;
        $net = $gross + $this->sum($indirectIncome) - $this->sum($indirectExpense);

        return [
            'direct_income' => $directIncome,
            'cogs' => $cogs,
            'direct_expense' => $directExpense,
            'indirect_income' => $indirectIncome,
            'indirect_expense' => $indirectExpense,
            'closing_inventory' => Money::format($closingInventory),
            'gross_profit' => Money::format(abs($gross)),
            'gross_side' => $gross >= 0 ? 'profit' : 'loss',
            'net_profit' => Money::format(abs($net)),
            'net_side' => $net >= 0 ? 'profit' : 'loss',
            'net_cents' => $net,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function balanceSheet(Company $company, ?int $branchId, FinancialYear $year, string $to): array
    {
        $assets = [];
        $liabilities = [];
        $assetCents = 0;
        $liabilityCents = 0;

        foreach ($this->balances->asOn($company, $branchId, $year, $to) as $row) {
            if ($row['nature'] === AccountNature::Asset) {
                $amount = $row['debit'] - $row['credit'];
            } elseif ($row['nature'] === AccountNature::Liability) {
                $amount = $row['credit'] - $row['debit'];
            } else {
                continue;
            }

            if ($amount === 0) {
                continue;
            }

            $line = [
                'name' => $row['ledger']->name,
                'amount' => Money::format($amount),
                'group' => $this->sheetGroup($row['codes']),
                'ledger_id' => $row['ledger']->id,
                'url' => tally_route('reports.ledger', ['ledger_id' => $row['ledger']->id, 'to' => $to]),
            ];

            if ($row['nature'] === AccountNature::Asset) {
                $assets[] = $line;
                $assetCents += $amount;
            } else {
                $liabilities[] = $line;
                $liabilityCents += $amount;
            }
        }

        $profit = $this->profitAndLoss($company, $branchId, $year, $to)['net_cents'];
        $opening = Money::cents((string) ($year->opening_profit ?? '0'));

        if ($profit >= 0) {
            $liabilityCents += $profit;
        } else {
            $assetCents += -$profit;
        }

        if ($opening > 0) {
            $liabilityCents += $opening;
        } elseif ($opening < 0) {
            $assetCents += -$opening;
        }

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'profit' => Money::format(abs($profit)),
            'profit_side' => $profit >= 0 ? 'liability' : 'asset',
            'opening_profit' => Money::format(abs($opening)),
            'opening_side' => $opening > 0 ? 'liability' : ($opening < 0 ? 'asset' : null),
            'asset_total' => Money::format($assetCents),
            'liability_total' => Money::format($liabilityCents),
            'difference' => Money::format(abs($assetCents - $liabilityCents)),
            'balanced' => $assetCents === $liabilityCents,
        ];
    }

    private function dayBookUrl(Voucher $voucher): string
    {
        $invoice = $voucher->invoice;

        if ($invoice) {
            return tally_route($invoice->kind->routeName($invoice->isDraft() ? 'edit' : 'show'), $invoice);
        }

        return tally_route('vouchers.entry', ['voucher' => $voucher, 'return' => 'day-book']);
    }

    /**
     * @param  list<string>  $codes
     */
    private function sheetGroup(array $codes): string
    {
        foreach ([
            'FIXED_ASSETS' => 'Fixed Assets',
            'INVESTMENTS' => 'Investments',
            'CAPITAL' => 'Capital Account',
            'LOANS' => 'Loans (Liability)',
            'CURRENT_LIABILITIES' => 'Current Liabilities',
            'CURRENT_ASSETS' => 'Current Assets',
        ] as $code => $label) {
            if (in_array($code, $codes, true)) {
                return $label;
            }
        }

        return 'Other';
    }

    private function signedOpening(Ledger $ledger, Company $company, ?int $branchId, FinancialYear $year, string $from): int
    {
        $signed = app(\Tally\Accounting\LedgerOpeningBook::class)->signedCents($ledger, $year, $branchId);

        if ($from <= $year->start_date->toDateString()) {
            return $signed;
        }

        $prior = VoucherEntry::query()
            ->select(['acct_voucher_entries.debit', 'acct_voucher_entries.credit'])
            ->join('acct_vouchers', 'acct_vouchers.id', '=', 'acct_voucher_entries.voucher_id')
            ->where('acct_voucher_entries.ledger_id', $ledger->id)
            ->where('acct_vouchers.company_id', $company->id)
            ->where('acct_vouchers.financial_year_id', $year->id)
            ->where('acct_vouchers.status', VoucherStatus::Posted->value)
            ->where('acct_vouchers.voucher_date', '>=', $year->start_date->toDateString())
            ->where('acct_vouchers.voucher_date', '<', $from)
            ->when($branchId, fn ($query) => $query->where('acct_vouchers.branch_id', $branchId))
            ->orderBy('acct_voucher_entries.id')
            ->cursor();

        foreach ($prior as $entry) {
            $signed += Money::cents((string) $entry->debit) - Money::cents((string) $entry->credit);
        }

        return $signed;
    }

    /**
     * @param  list<array{amount: string}>  $rows
     */
    private function sum(array $rows): int
    {
        $total = 0;

        foreach ($rows as $row) {
            $total += Money::cents($row['amount']);
        }

        return $total;
    }

    private function reportCents(mixed $value): int
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return 0;
        }

        if (! str_contains($raw, '.')) {
            $raw .= '.00';
        }

        [$whole, $fraction] = explode('.', ltrim($raw, '-'), 2);
        $negative = str_starts_with($raw, '-');

        return Money::cents(($negative ? '-' : '').((int) $whole).'.'.substr(str_pad($fraction, 2, '0'), 0, 2));
    }
}
