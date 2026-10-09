<?php

namespace Tally\DataExchange\Export;

use Tally\Context\WorkspaceContext;
use Tally\Inventory\InventoryReports;
use Tally\Models\Ledger;
use Tally\Models\Product;
use Tally\Models\Voucher;
use Tally\Reporting\FinancialReports;
use Tally\Reporting\OutstandingReport;
use InvalidArgumentException;

class ExportCatalog
{
    public function __construct(
        private readonly WorkspaceContext $context,
        private readonly FinancialReports $financial,
        private readonly OutstandingReport $outstanding,
        private readonly InventoryReports $stock,
    ) {}

    /**
     * @return list<array{key: string, label: string, description: string, needs_year: bool}>
     */
    public function options(): array
    {
        return [
            ['key' => 'ledgers', 'label' => 'Ledgers', 'description' => 'All ledgers in the current company.', 'needs_year' => false],
            ['key' => 'products', 'label' => 'Products', 'description' => 'Product master with rates and opening stock.', 'needs_year' => false],
            ['key' => 'parties', 'label' => 'Customers / Suppliers', 'description' => 'Ledgers under Sundry Debtors and Sundry Creditors.', 'needs_year' => false],
            ['key' => 'vouchers', 'label' => 'Vouchers', 'description' => 'Voucher lines for the current financial year.', 'needs_year' => true],
            ['key' => 'outstanding', 'label' => 'Outstanding', 'description' => 'Receivables and payables as of the year end or today.', 'needs_year' => true],
            ['key' => 'stock', 'label' => 'Stock', 'description' => 'Stock summary for the current financial year.', 'needs_year' => true],
            ['key' => 'trial-balance', 'label' => 'Trial balance', 'description' => 'Trial balance for the current financial year.', 'needs_year' => true],
            ['key' => 'profit-and-loss', 'label' => 'Profit and loss', 'description' => 'Profit and loss for the current financial year.', 'needs_year' => true],
            ['key' => 'balance-sheet', 'label' => 'Balance sheet', 'description' => 'Balance sheet for the current financial year.', 'needs_year' => true],
        ];
    }

    public function needsYear(string $key): bool
    {
        foreach ($this->options() as $option) {
            if ($option['key'] === $key) {
                return $option['needs_year'];
            }
        }

        throw new InvalidArgumentException('Unknown export.');
    }

    /**
     * @return array{filename: string, headers: list<string>, rows: list<array<string, scalar|null>>}
     */
    public function build(string $key): array
    {
        $company = $this->context->company();

        if (! $company) {
            throw new InvalidArgumentException('Select a company before exporting.');
        }

        $year = $this->context->financialYear();

        if ($this->needsYear($key) && ! $year) {
            throw new InvalidArgumentException('Select a financial year before exporting this report.');
        }

        $asOf = $year
            ? (now()->toDateString() < $year->start_date->toDateString()
                ? $year->start_date->toDateString()
                : min(now()->toDateString(), $year->end_date->toDateString()))
            : now()->toDateString();
        $from = $year?->start_date->toDateString() ?? $asOf;
        $branchId = $this->context->branchId();

        $table = match ($key) {
            'ledgers' => $this->ledgers(),
            'products' => $this->products(),
            'parties' => $this->parties(),
            'vouchers' => $this->vouchers(),
            'outstanding' => $this->outstandingRows($asOf, $branchId),
            'stock' => $this->stockRows($from, $asOf, $branchId),
            'trial-balance' => $this->trialBalance($asOf, $branchId),
            'profit-and-loss' => $this->profitAndLoss($asOf, $branchId),
            'balance-sheet' => $this->balanceSheet($asOf, $branchId),
            default => throw new InvalidArgumentException('Unknown export.'),
        };

        return [
            'filename' => $key.'-'.$company->id.'-'.now()->format('Ymd'),
            'headers' => $table['headers'],
            'rows' => $table['rows'],
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, scalar|null>>}
     */
    private function ledgers(): array
    {
        $headers = ['name', 'code', 'group_code', 'group_name', 'opening_balance', 'opening_balance_type', 'address', 'state', 'phone', 'email', 'gstin', 'gst_registration_type', 'pan', 'credit_limit', 'credit_days', 'is_active'];
        $rows = $this->context->company()->ledgers()->with('accountGroup')->orderBy('name')->get()->map(function (Ledger $ledger) {
            return [
                'name' => $ledger->name,
                'code' => $ledger->code,
                'group_code' => $ledger->accountGroup?->code,
                'group_name' => $ledger->accountGroup?->name,
                'opening_balance' => $ledger->opening_balance,
                'opening_balance_type' => $ledger->opening_balance_type->value,
                'address' => $ledger->address,
                'state' => $ledger->state,
                'phone' => $ledger->phone,
                'email' => $ledger->email,
                'gstin' => $ledger->gstin,
                'gst_registration_type' => $ledger->gst_registration_type?->value,
                'pan' => $ledger->pan,
                'credit_limit' => $ledger->credit_limit,
                'credit_days' => $ledger->credit_days,
                'is_active' => $ledger->is_active ? 'yes' : 'no',
            ];
        })->all();

        return compact('headers', 'rows');
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, scalar|null>>}
     */
    private function products(): array
    {
        $headers = ['name', 'code', 'group_code', 'group_name', 'unit_symbol', 'alternate_unit', 'conversion_factor', 'barcode', 'purchase_rate', 'sales_rate', 'opening_quantity', 'opening_rate', 'opening_value', 'minimum_stock', 'reorder_level', 'is_active'];
        $rows = $this->context->company()->products()->with(['productGroup', 'primaryUnit', 'alternateUnit'])->orderBy('name')->get()->map(function (Product $product) {
            return [
                'name' => $product->name,
                'code' => $product->code,
                'group_code' => $product->productGroup?->code,
                'group_name' => $product->productGroup?->name,
                'unit_symbol' => $product->primaryUnit?->symbol,
                'alternate_unit' => $product->alternateUnit?->symbol,
                'conversion_factor' => $product->conversion_factor,
                'barcode' => $product->barcode,
                'purchase_rate' => $product->purchase_rate,
                'sales_rate' => $product->sales_rate,
                'opening_quantity' => $product->opening_quantity,
                'opening_rate' => $product->opening_rate,
                'opening_value' => $product->opening_value,
                'minimum_stock' => $product->minimum_stock,
                'reorder_level' => $product->reorder_level,
                'is_active' => $product->is_active ? 'yes' : 'no',
            ];
        })->all();

        return compact('headers', 'rows');
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, scalar|null>>}
     */
    private function parties(): array
    {
        $headers = ['party_type', 'name', 'code', 'opening_balance', 'opening_balance_type', 'state', 'phone', 'email', 'gstin', 'is_active'];
        $rows = [];

        $this->context->company()->ledgers()->with('accountGroup.parent')->orderBy('name')->get()->each(function (Ledger $ledger) use (&$rows) {
            $type = $ledger->isCustomer() ? 'customer' : ($ledger->isSupplier() ? 'supplier' : null);

            if (! $type) {
                return;
            }

            $rows[] = [
                'party_type' => $type,
                'name' => $ledger->name,
                'code' => $ledger->code,
                'opening_balance' => $ledger->opening_balance,
                'opening_balance_type' => $ledger->opening_balance_type->value,
                'state' => $ledger->state,
                'phone' => $ledger->phone,
                'email' => $ledger->email,
                'gstin' => $ledger->gstin,
                'is_active' => $ledger->is_active ? 'yes' : 'no',
            ];
        });

        return compact('headers', 'rows');
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, scalar|null>>}
     */
    private function vouchers(): array
    {
        $headers = ['date', 'number', 'type', 'status', 'branch', 'ledger', 'debit', 'credit', 'narration'];
        $rows = [];
        $year = $this->context->financialYear();

        Voucher::query()
            ->with(['entries.ledger', 'branch'])
            ->where('company_id', $this->context->companyId())
            ->where('financial_year_id', $year->id)
            ->orderBy('voucher_date')
            ->orderBy('id')
            ->get()
            ->each(function (Voucher $voucher) use (&$rows) {
                foreach ($voucher->entries as $entry) {
                    $rows[] = [
                        'date' => $voucher->voucher_date->toDateString(),
                        'number' => $voucher->voucher_number,
                        'type' => $voucher->voucher_type->value,
                        'status' => $voucher->status->value,
                        'branch' => $voucher->branch?->code,
                        'ledger' => $entry->ledger?->name,
                        'debit' => $entry->debit,
                        'credit' => $entry->credit,
                        'narration' => $voucher->narration,
                    ];
                }
            });

        return compact('headers', 'rows');
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, scalar|null>>}
     */
    private function outstandingRows(string $asOf, ?int $branchId): array
    {
        $headers = ['kind', 'party', 'bill_number', 'bill_date', 'due_date', 'original', 'paid', 'outstanding'];
        $rows = [];
        $company = $this->context->company();

        foreach (['receivable' => $this->outstanding->receivables($company, $asOf, $branchId), 'payable' => $this->outstanding->payables($company, $asOf, $branchId)] as $kind => $report) {
            foreach ($report['rows'] as $row) {
                $rows[] = [
                    'kind' => $kind,
                    'party' => $row['party'],
                    'bill_number' => $row['bill_number'],
                    'bill_date' => $row['bill_date'],
                    'due_date' => $row['due_date'],
                    'original' => $row['original'],
                    'paid' => $row['paid'],
                    'outstanding' => $row['outstanding'],
                ];
            }
        }

        return compact('headers', 'rows');
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, scalar|null>>}
     */
    private function stockRows(string $from, string $to, ?int $branchId): array
    {
        $report = $this->stock->summary($this->context->company(), $this->context->financialYear(), $branchId, $from, $to, null, null);
        $headers = ['product', 'code', 'opening_quantity', 'in_quantity', 'out_quantity', 'closing_quantity', 'closing_value'];
        $rows = [];

        foreach ($report['rows'] as $row) {
            $rows[] = [
                'product' => $row['product'] ?? '',
                'code' => $row['code'] ?? '',
                'opening_quantity' => $row['opening_quantity'] ?? '',
                'in_quantity' => $row['in_quantity'] ?? '',
                'out_quantity' => $row['out_quantity'] ?? '',
                'closing_quantity' => $row['closing_quantity'] ?? '',
                'closing_value' => $row['closing_value'] ?? '',
            ];
        }

        return compact('headers', 'rows');
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, scalar|null>>}
     */
    private function trialBalance(string $asOf, ?int $branchId): array
    {
        $report = $this->financial->trialBalance($this->context->company(), $branchId, $this->context->financialYear(), $asOf);
        $headers = ['ledger', 'group', 'debit', 'credit'];

        return ['headers' => $headers, 'rows' => $report['rows']];
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, scalar|null>>}
     */
    private function profitAndLoss(string $asOf, ?int $branchId): array
    {
        $report = $this->financial->profitAndLoss($this->context->company(), $branchId, $this->context->financialYear(), $asOf);
        $headers = ['section', 'name', 'amount'];
        $rows = [];

        foreach (['direct_income' => 'Direct income', 'direct_expense' => 'Direct expense', 'indirect_income' => 'Indirect income', 'indirect_expense' => 'Indirect expense'] as $key => $section) {
            foreach ($report[$key] as $line) {
                $rows[] = ['section' => $section, 'name' => $line['name'], 'amount' => $line['amount']];
            }
        }

        $rows[] = ['section' => 'Gross', 'name' => $report['gross_side'], 'amount' => $report['gross_profit']];
        $rows[] = ['section' => 'Net', 'name' => $report['net_side'], 'amount' => $report['net_profit']];

        return compact('headers', 'rows');
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, scalar|null>>}
     */
    private function balanceSheet(string $asOf, ?int $branchId): array
    {
        $report = $this->financial->balanceSheet($this->context->company(), $branchId, $this->context->financialYear(), $asOf);
        $headers = ['section', 'name', 'amount'];
        $rows = [];

        foreach ($report['assets'] as $line) {
            $rows[] = ['section' => 'Asset', 'name' => $line['name'], 'amount' => $line['amount']];
        }

        foreach ($report['liabilities'] as $line) {
            $rows[] = ['section' => 'Liability', 'name' => $line['name'], 'amount' => $line['amount']];
        }

        $rows[] = ['section' => 'Profit', 'name' => $report['profit_side'], 'amount' => $report['profit']];
        $rows[] = ['section' => 'Total assets', 'name' => '', 'amount' => $report['asset_total']];
        $rows[] = ['section' => 'Total liabilities', 'name' => '', 'amount' => $report['liability_total']];

        return compact('headers', 'rows');
    }
}
