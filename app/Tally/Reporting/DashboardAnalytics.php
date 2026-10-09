<?php

namespace Tally\Reporting;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherStatus;
use Tally\Inventory\StockMovementService;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Invoice;
use Tally\Models\InvoiceLine;
use Tally\Models\ManufacturingOrder;
use Tally\Models\Product;
use Tally\Models\StockBatch;
use Tally\Models\StockMovement;
use Tally\Models\VoucherEntry;
use Tally\Support\Queries\DateRange;
use Tally\Tax\GstReport;

class DashboardAnalytics
{
    public function __construct(
        private readonly FinancialReports $reports,
        private readonly OutstandingReport $outstanding,
        private readonly LedgerBalances $balances,
        private readonly StockMovementService $stock,
        private readonly GstReport $gst,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Company $company, ?int $branchId, FinancialYear $year, string $asOn): array
    {
        $asOn = $asOn < $year->start_date->toDateString() ? $year->start_date->toDateString() : $asOn;
        $asOn = $asOn > $year->end_date->toDateString() ? $year->end_date->toDateString() : $asOn;
        $from = $year->start_date->toDateString();
        $pnl = $this->reports->profitAndLoss($company, $branchId, $year, $asOn);
        $sheet = $this->reports->balanceSheet($company, $branchId, $year, $asOn);
        $cash = 0;
        $bank = 0;
        $expenses = 0;
        $accounts = [];
        $flowIds = [];

        foreach ($this->balances->asOn($company, $branchId, $year, $asOn) as $row) {
            $amount = $row['debit'] - $row['credit'];

            if (in_array('CASH', $row['codes'], true)) {
                $cash += $amount;
                $flowIds[] = $row['ledger']->id;
            }

            if (in_array('BANK', $row['codes'], true)) {
                $bank += $amount;
                $flowIds[] = $row['ledger']->id;
            }

            if ((in_array('CASH', $row['codes'], true) || in_array('BANK', $row['codes'], true)) && $amount !== 0) {
                $accounts[] = [
                    'name' => $row['ledger']->name,
                    'amount' => Money::format(abs($amount)).($amount > 0 ? ' Dr' : ' Cr'),
                    'url' => tally_route('banking.activities', ['ledger_id' => $row['ledger']->id]),
                ];
            }

            if ($row['nature'] === \Tally\Accounting\AccountNature::Expense && $row['ledger']->code !== 'COGS') {
                $expenses += $amount;
            }
        }

        $receivables = $this->outstanding->receivables($company, $asOn, $branchId);
        $payables = $this->outstanding->payables($company, $asOn, $branchId);
        $gst = $this->gst->build($company, $from, $asOn, $branchId);
        $stock = $this->stockPosition($company, $branchId, $year);
        $purchaseTrend = $this->trend($company, $branchId, $year, InvoiceKind::Purchase, $asOn);

        return [
            'as_on' => $asOn,
            'revenue' => $this->sumLines($pnl['direct_income']),
            'purchases' => $this->sumTotals($purchaseTrend),
            'expenses' => Money::format($expenses),
            'cogs' => $this->sumLines($pnl['cogs']),
            'gross_profit' => $pnl['gross_profit'],
            'gross_side' => $pnl['gross_side'],
            'net_profit' => $pnl['net_profit'],
            'net_side' => $pnl['net_side'],
            'receivables' => $receivables['net'],
            'payables' => $payables['net'],
            'receivables_overdue' => $receivables['overdue'],
            'payables_overdue' => $payables['overdue'],
            'cash' => Money::format($cash),
            'bank' => Money::format($bank),
            'accounts' => $accounts,
            'assets' => $sheet['asset_total'],
            'liabilities' => $sheet['liability_total'],
            'cash_flow' => $this->cashFlow($company, $branchId, $year, $asOn, $flowIds),
            'outstanding_bills' => $receivables['total'],
            'gst_output' => $gst['output']['tax'],
            'gst_input' => $gst['input']['tax'],
            'sales_trend' => $this->trend($company, $branchId, $year, InvoiceKind::Sales, $asOn),
            'purchase_trend' => $purchaseTrend,
            'top_customers' => $this->topParties($company, $branchId, $year, InvoiceKind::Sales, $asOn),
            'top_suppliers' => $this->topParties($company, $branchId, $year, InvoiceKind::Purchase, $asOn),
            'top_products' => $this->topProducts($company, $branchId, $year, $asOn),
            'sales_by_branch' => $this->byBranch($company, $year, InvoiceKind::Sales, $asOn),
            'stock_value' => $stock['value'],
            'stock_quantity' => $stock['quantity'],
            'low_stock' => $stock['low'],
            'by_godown' => $stock['godowns'],
            'fast_moving' => $this->movers($company, $branchId, $year, $asOn, 'desc'),
            'slow_moving' => $this->movers($company, $branchId, $year, $asOn, 'asc'),
            'expiring' => $this->expiring($company),
            'manufacturing' => $this->manufacturing($company, $branchId, $year, $asOn),
        ];
    }

    /**
     * @param  list<int>  $ledgerIds
     * @return array{inflow: string, outflow: string, net: string}
     */
    private function cashFlow(Company $company, ?int $branchId, FinancialYear $year, string $asOn, array $ledgerIds): array
    {
        if ($ledgerIds === []) {
            return ['inflow' => '0.00', 'outflow' => '0.00', 'net' => '0.00'];
        }

        $movement = VoucherEntry::query()
            ->join('acct_vouchers', 'acct_vouchers.id', '=', 'acct_voucher_entries.voucher_id')
            ->where('acct_vouchers.company_id', $company->id)
            ->where('acct_vouchers.financial_year_id', $year->id)
            ->where('acct_vouchers.status', VoucherStatus::Posted->value)
            ->whereIn('acct_voucher_entries.ledger_id', $ledgerIds)
            ->tap(fn ($query) => DateRange::apply($query, 'acct_vouchers.voucher_date', $year->start_date->toDateString(), $asOn))
            ->when($branchId, fn ($query) => $query->where('acct_vouchers.branch_id', $branchId))
            ->selectRaw('round(sum(acct_voucher_entries.debit), 2) as inflow')
            ->selectRaw('round(sum(acct_voucher_entries.credit), 2) as outflow')
            ->first();
        $inflow = Money::cents((string) ($movement->inflow ?? 0));
        $outflow = Money::cents((string) ($movement->outflow ?? 0));

        return [
            'inflow' => Money::format($inflow),
            'outflow' => Money::format($outflow),
            'net' => Money::format(abs($inflow - $outflow)).($inflow >= $outflow ? ' Dr' : ' Cr'),
        ];
    }

    /**
     * @param  list<array{name: string, amount: string}>  $lines
     */
    private function sumLines(array $lines): string
    {
        $cents = 0;

        foreach ($lines as $line) {
            $cents += Money::cents($line['amount']);
        }

        return Money::format($cents);
    }

    /**
     * @param  list<array{total: string}>  $rows
     */
    private function sumTotals(array $rows): string
    {
        $cents = 0;

        foreach ($rows as $row) {
            $cents += Money::cents($row['total']);
        }

        return Money::format($cents);
    }

    /**
     * @return list<array{period: string, total: string}>
     */
    private function trend(Company $company, ?int $branchId, FinancialYear $year, InvoiceKind $kind, string $asOn): array
    {
        $rows = Invoice::query()
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->where('kind', $kind)
            ->where('status', VoucherStatus::Posted)
            ->whereDate('invoice_date', '<=', $asOn)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->selectRaw('substr(invoice_date, 1, 7) as period, SUM(grand_total) as total')
            ->groupByRaw('substr(invoice_date, 1, 7)')
            ->orderBy('period')
            ->get();

        return $rows->map(fn ($row) => [
            'period' => (string) $row->period,
            'total' => $this->money($row->total),
        ])->all();
    }

    /**
     * @return list<array{name: string, total: string}>
     */
    private function topParties(Company $company, ?int $branchId, FinancialYear $year, InvoiceKind $kind, string $asOn): array
    {
        return Invoice::query()
            ->join('acct_ledgers', 'acct_ledgers.id', '=', 'acct_invoices.party_ledger_id')
            ->where('acct_invoices.company_id', $company->id)
            ->where('acct_invoices.financial_year_id', $year->id)
            ->where('acct_invoices.kind', $kind)
            ->where('acct_invoices.status', VoucherStatus::Posted)
            ->whereDate('acct_invoices.invoice_date', '<=', $asOn)
            ->when($branchId, fn ($query) => $query->where('acct_invoices.branch_id', $branchId))
            ->selectRaw('acct_ledgers.name as name, SUM(acct_invoices.grand_total) as total')
            ->groupBy('acct_ledgers.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => (string) $row->name,
                'total' => $this->money($row->total),
            ])
            ->all();
    }

    /**
     * @return list<array{name: string, total: string}>
     */
    private function topProducts(Company $company, ?int $branchId, FinancialYear $year, string $asOn): array
    {
        return InvoiceLine::query()
            ->join('acct_invoices', 'acct_invoices.id', '=', 'acct_invoice_lines.invoice_id')
            ->where('acct_invoices.company_id', $company->id)
            ->where('acct_invoices.financial_year_id', $year->id)
            ->where('acct_invoices.kind', InvoiceKind::Sales)
            ->where('acct_invoices.status', VoucherStatus::Posted)
            ->whereDate('acct_invoices.invoice_date', '<=', $asOn)
            ->when($branchId, fn ($query) => $query->where('acct_invoices.branch_id', $branchId))
            ->whereNotNull('acct_invoice_lines.product_id')
            ->selectRaw('acct_invoice_lines.item_name as name, SUM(acct_invoice_lines.line_total) as total')
            ->groupBy('acct_invoice_lines.item_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['name' => (string) $row->name, 'total' => $this->money($row->total)])
            ->all();
    }

    /**
     * @return list<array{name: string, total: string}>
     */
    private function byBranch(Company $company, FinancialYear $year, InvoiceKind $kind, string $asOn): array
    {
        return Invoice::query()
            ->join('acct_branches', 'acct_branches.id', '=', 'acct_invoices.branch_id')
            ->where('acct_invoices.company_id', $company->id)
            ->where('acct_invoices.financial_year_id', $year->id)
            ->where('acct_invoices.kind', $kind)
            ->where('acct_invoices.status', VoucherStatus::Posted)
            ->whereDate('acct_invoices.invoice_date', '<=', $asOn)
            ->selectRaw('acct_branches.name as name, SUM(acct_invoices.grand_total) as total')
            ->groupBy('acct_branches.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['name' => (string) $row->name, 'total' => $this->money($row->total)])
            ->all();
    }

    /**
     * @return array{value: string, quantity: string, low: list<array{name: string, quantity: string}>, godowns: list<array{name: string, value: string}>}
     */
    private function stockPosition(Company $company, ?int $branchId, FinancialYear $year): array
    {
        $movements = $this->movementTotals($company->id, $branchId, $year->id);
        $products = Product::query()->where('company_id', $company->id)->get(['id', 'name', 'opening_quantity', 'opening_value', 'reorder_level', 'is_active']);
        $qty = 0;
        $value = 0;
        $low = [];

        foreach ($products as $product) {
            $scaled = ($movements['quantity'][$product->id] ?? 0);

            if ($branchId === null) {
                $scaled += \Tally\Inventory\Quantity::scale((string) $product->opening_quantity, 4);
                $value += Money::cents((string) $product->opening_value);
            }

            $value += $movements['value'][$product->id] ?? 0;
            $qty += $scaled;
            $reorder = \Tally\Inventory\Quantity::scale((string) $product->reorder_level, 4);

            if ($product->is_active && $reorder > 0 && $scaled <= $reorder) {
                $low[] = ['name' => $product->name, 'quantity' => \Tally\Inventory\Quantity::format($scaled)];
            }
        }

        $godowns = StockMovement::query()
            ->join('acct_godowns', 'acct_godowns.id', '=', 'acct_stock_movements.godown_id')
            ->where('acct_stock_movements.company_id', $company->id)
            ->when($branchId, fn ($query) => $query->where('acct_stock_movements.branch_id', $branchId))
            ->selectRaw('acct_godowns.name as name, SUM(acct_stock_movements.value) as value')
            ->groupBy('acct_godowns.name')
            ->orderBy('acct_godowns.name')
            ->get()
            ->map(fn ($row) => ['name' => (string) $row->name, 'value' => $this->money($row->value)])
            ->all();

        return [
            'value' => Money::format($value),
            'quantity' => \Tally\Inventory\Quantity::format($qty),
            'low' => array_slice($low, 0, 8),
            'godowns' => $godowns,
        ];
    }

    /**
     * @return list<array{name: string, quantity: string}>
     */
    private function movers(Company $company, ?int $branchId, FinancialYear $year, string $asOn, string $direction): array
    {
        $query = InvoiceLine::query()
            ->join('acct_invoices', 'acct_invoices.id', '=', 'acct_invoice_lines.invoice_id')
            ->where('acct_invoices.company_id', $company->id)
            ->where('acct_invoices.financial_year_id', $year->id)
            ->where('acct_invoices.kind', InvoiceKind::Sales)
            ->where('acct_invoices.status', VoucherStatus::Posted)
            ->whereDate('acct_invoices.invoice_date', '<=', $asOn)
            ->when($branchId, fn ($query) => $query->where('acct_invoices.branch_id', $branchId))
            ->whereNotNull('acct_invoice_lines.product_id')
            ->selectRaw('acct_invoice_lines.item_name as name, SUM(acct_invoice_lines.quantity) as quantity')
            ->groupBy('acct_invoice_lines.item_name');

        if ($direction === 'asc') {
            $query->orderBy('quantity');
        } else {
            $query->orderByDesc('quantity');
        }

        return $query->limit(5)->get()->map(fn ($row) => [
            'name' => (string) $row->name,
            'quantity' => rtrim(rtrim((string) $row->quantity, '0'), '.'),
        ])->all();
    }

    /**
     * @return list<array{product: string, batch: string, expires: string, quantity: string}>
     */
    private function expiring(Company $company): array
    {
        $batches = StockBatch::query()
            ->with('product:id,name')
            ->where('company_id', $company->id)
            ->whereNotNull('expires_on')
            ->whereDate('expires_on', '<=', now()->addDays(90)->toDateString())
            ->orderBy('expires_on')
            ->limit(8)
            ->get();
        $quantities = StockMovement::query()
            ->whereIn('batch_id', $batches->pluck('id'))
            ->selectRaw('batch_id, SUM(quantity) as quantity')
            ->groupBy('batch_id')
            ->pluck('quantity', 'batch_id');

        return $batches->map(fn (StockBatch $batch) => [
            'product' => $batch->product?->name ?? 'Batch',
            'batch' => $batch->batch_number,
            'expires' => $batch->expires_on?->format('d M Y') ?? '',
            'quantity' => rtrim(rtrim((string) ($quantities[$batch->id] ?? '0'), '0'), '.') ?: '0',
        ])->all();
    }

    /**
     * @return array{quantity: string, cost: string, orders: int}
     */
    private function manufacturing(Company $company, ?int $branchId, FinancialYear $year, string $asOn): array
    {
        $row = ManufacturingOrder::query()
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->where('status', VoucherStatus::Posted)
            ->whereDate('manufactured_on', '<=', $asOn)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(quantity), 0) as quantity, COALESCE(SUM(material_cost), 0) as cost')
            ->first();

        $finished = StockMovement::query()
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->where('reference_type', (new ManufacturingOrder)->getMorphClass())
            ->where('quantity', '>', 0)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereDate('movement_date', '<=', $asOn)
            ->sum('value');
        $consumed = StockMovement::query()
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->where('reference_type', (new ManufacturingOrder)->getMorphClass())
            ->where('quantity', '<', 0)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereDate('movement_date', '<=', $asOn)
            ->sum('value');

        return [
            'orders' => (int) ($row->orders ?? 0),
            'quantity' => rtrim(rtrim((string) ($row->quantity ?? '0'), '0'), '.') ?: '0',
            'finished_goods' => $this->money($finished),
            'consumption' => $this->money(abs((float) $consumed)),
            'cost' => $this->money($row->cost ?? 0),
            'scrap' => $this->scrapQuantity($company, $branchId, $year, $asOn),
        ];
    }

    private function scrapQuantity(Company $company, ?int $branchId, FinancialYear $year, string $asOn): string
    {
        $quantity = ManufacturingOrder::query()
            ->join('acct_bom_byproducts', 'acct_bom_byproducts.bill_of_material_id', '=', 'acct_manufacturing_orders.bill_of_material_id')
            ->where('acct_manufacturing_orders.company_id', $company->id)
            ->where('acct_manufacturing_orders.financial_year_id', $year->id)
            ->where('acct_manufacturing_orders.status', VoucherStatus::Posted)
            ->whereDate('acct_manufacturing_orders.manufactured_on', '<=', $asOn)
            ->when($branchId, fn ($query) => $query->where('acct_manufacturing_orders.branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(acct_bom_byproducts.quantity * acct_manufacturing_orders.quantity), 0) as quantity')
            ->value('quantity');

        return rtrim(rtrim((string) $quantity, '0'), '.') ?: '0';
    }

    /**
     * @return array{quantity: array<int, int>, value: array<int, int>}
     */
    private function movementTotals(int $companyId, ?int $branchId, int $yearId): array
    {
        if ($branchId === null) {
            return $this->stock->totalsForCompany($companyId, $yearId);
        }

        $quantity = [];
        $value = [];
        StockMovement::query()
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->select(['id', 'product_id', 'financial_year_id', 'quantity', 'value'])
            ->chunkById(500, function ($rows) use (&$quantity, &$value, $yearId): void {
                foreach ($rows as $row) {
                    $quantity[$row->product_id] = ($quantity[$row->product_id] ?? 0) + \Tally\Inventory\Quantity::signedScale((string) $row->quantity, 4);

                    if ((int) $row->financial_year_id === $yearId) {
                        $value[$row->product_id] = ($value[$row->product_id] ?? 0) + Money::cents((string) $row->value);
                    }
                }
            });

        return ['quantity' => $quantity, 'value' => $value];
    }

    private function money(mixed $value): string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return '0.00';
        }

        if (! str_contains($raw, '.')) {
            $raw .= '.00';
        }

        [$whole, $fraction] = explode('.', ltrim($raw, '-'), 2);
        $negative = str_starts_with($raw, '-');
        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);

        return Money::format(Money::cents(($negative ? '-' : '').$whole.'.'.$fraction));
    }
}
