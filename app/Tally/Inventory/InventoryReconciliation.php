<?php

namespace Tally\Inventory;

use Tally\Accounting\Money;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Reporting\LedgerBalances;

/**
 * Stock valuation and the stock, raw material, and finished goods ledgers
 * are two readings of the same movements. They should agree.
 */
class InventoryReconciliation
{
    public function __construct(
        private readonly AdvancedInventoryReport $valuation,
        private readonly LedgerBalances $balances,
        private readonly InventoryAccounts $accounts,
    ) {}

    /**
     * @return array{stock_value: string, ledger_value: string, difference: string, agrees: bool}
     */
    public function compare(Company $company, FinancialYear $year): array
    {
        $stock = 0;

        foreach ($this->valuation->valuation($company, $year)['rows'] as $row) {
            $stock += Money::cents($row['value']);
        }

        $ledger = 0;
        $codes = $this->accounts->assetCodes();

        foreach ($this->balances->asOn($company, null, $year, $year->end_date->toDateString()) as $row) {
            if (in_array($row['ledger']->code, $codes, true)) {
                $ledger += $row['debit'] - $row['credit'];
            }
        }

        $difference = $stock - $ledger;

        return [
            'stock_value' => Money::format($stock),
            'ledger_value' => Money::format($ledger),
            'difference' => Money::format($difference),
            'agrees' => $difference === 0,
        ];
    }
}
