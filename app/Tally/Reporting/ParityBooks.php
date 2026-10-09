<?php

namespace Tally\Reporting;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherStatus;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\AuditLog;
use Tally\Models\Company;
use Tally\Models\Invoice;
use Tally\Models\Ledger;
use Tally\Models\Product;
use Tally\Models\StockMovement;
use Tally\Models\Voucher;
use Tally\Models\VoucherEntry;
use Tally\Support\Queries\DateRange;
use Illuminate\Support\Facades\DB;

/**
 * Separate statutory and book reports. They do not replace Day Book, Ledger, Outstanding, Stock Ledger, or Stock Summary.
 */
class ParityBooks
{
    /**
     * @return array{title: string, sections: list<array{heading: string, columns: list<string>, rows: list<array<string, string>>}>}
     */
    public function gstr1(Company $company, string $from, string $to, ?int $branchId, ?int $registrationId = null): array
    {
        $invoices = $this->invoices($company, $from, $to, $branchId, $registrationId);
        $b2b = [];
        $b2c = [];
        $notes = [];
        $hsn = [];

        foreach ($invoices as $invoice) {
            if (! in_array($invoice->kind, [InvoiceKind::Sales, InvoiceKind::CreditNote, InvoiceKind::DebitNote], true)) {
                continue;
            }

            $tax = $this->taxRow($invoice);
            $gstin = trim((string) $invoice->party?->gstin);
            $row = [
                'Date' => $invoice->invoice_date?->format('d M Y') ?? '',
                'Number' => $invoice->invoice_number,
                'Party' => $invoice->party?->name ?? '',
                'GSTIN' => $gstin === '' ? '—' : $gstin,
                'Taxable' => $tax['taxable'],
                'CGST' => $tax['cgst'],
                'SGST' => $tax['sgst'],
                'IGST' => $tax['igst'],
            ];

            if ($invoice->kind === InvoiceKind::Sales && $gstin !== '') {
                $b2b[] = $row;
            } elseif ($invoice->kind === InvoiceKind::Sales) {
                $b2c[] = $row;
            } else {
                $row['Type'] = $invoice->kind->label();
                $notes[] = $row;
            }

            foreach ($invoice->lines as $line) {
                $code = $line->hsnSac?->code ?? '—';
                $hsn[$code] ??= ['HSN/SAC' => $code, 'Taxable' => 0, 'CGST' => 0, 'SGST' => 0, 'IGST' => 0];
                $sign = $invoice->kind === InvoiceKind::CreditNote ? -1 : 1;
                $hsn[$code]['Taxable'] += $sign * Money::cents((string) $line->taxable_amount);
                $hsn[$code]['CGST'] += $sign * Money::cents((string) $line->cgst_amount);
                $hsn[$code]['SGST'] += $sign * Money::cents((string) $line->sgst_amount);
                $hsn[$code]['IGST'] += $sign * Money::cents((string) $line->igst_amount);
            }
        }

        return [
            'title' => 'GSTR-1'.$this->gstinLabel($registrationId),
            'sections' => [
                ['heading' => 'B2B', 'columns' => ['Date', 'Number', 'Party', 'GSTIN', 'Taxable', 'CGST', 'SGST', 'IGST'], 'rows' => $b2b],
                ['heading' => 'B2C', 'columns' => ['Date', 'Number', 'Party', 'GSTIN', 'Taxable', 'CGST', 'SGST', 'IGST'], 'rows' => $b2c],
                ['heading' => 'Credit and debit notes', 'columns' => ['Date', 'Number', 'Type', 'Party', 'GSTIN', 'Taxable', 'CGST', 'SGST', 'IGST'], 'rows' => $notes],
                ['heading' => 'HSN/SAC summary', 'columns' => ['HSN/SAC', 'Taxable', 'CGST', 'SGST', 'IGST'], 'rows' => array_map(fn (array $row) => [
                    'HSN/SAC' => $row['HSN/SAC'],
                    'Taxable' => Money::format($row['Taxable']),
                    'CGST' => Money::format($row['CGST']),
                    'SGST' => Money::format($row['SGST']),
                    'IGST' => Money::format($row['IGST']),
                ], array_values($hsn))],
            ],
        ];
    }

    /**
     * @return array{title: string, sections: list<array{heading: string, columns: list<string>, rows: list<array<string, string>>}>}
     */
    public function gstr3b(Company $company, string $from, string $to, ?int $branchId, ?int $registrationId = null): array
    {
        $out = $this->blankTax();
        $in = $this->blankTax();
        $rcm = $this->blankTax();

        foreach ($this->invoices($company, $from, $to, $branchId, $registrationId) as $invoice) {
            if ($invoice->reverse_charge) {
                $bucket = $rcm;
            } else {
                $bucket = $invoice->kind->usesCostOfGoods() ? $out : $in;
            }
            $sign = $invoice->kind === InvoiceKind::CreditNote || $invoice->kind === InvoiceKind::DebitNote ? -1 : 1;

            if ($invoice->kind === InvoiceKind::DebitNote) {
                $sign = -1;
            }

            if ($invoice->kind === InvoiceKind::CreditNote) {
                $sign = -1;
            }

            foreach ($invoice->lines as $line) {
                $bucket['taxable'] += $sign * Money::cents((string) $line->taxable_amount);
                $bucket['cgst'] += $sign * Money::cents((string) $line->cgst_amount);
                $bucket['sgst'] += $sign * Money::cents((string) $line->sgst_amount);
                $bucket['igst'] += $sign * Money::cents((string) $line->igst_amount);
                $bucket['cess'] += $sign * Money::cents((string) $line->cess_amount);
            }

            if ($invoice->reverse_charge) {
                $rcm = $bucket;
            } elseif ($invoice->kind->usesCostOfGoods()) {
                $out = $bucket;
            } else {
                $in = $bucket;
            }
        }

        $liability = [
            'cgst' => $out['cgst'] - $in['cgst'] + $rcm['cgst'],
            'sgst' => $out['sgst'] - $in['sgst'] + $rcm['sgst'],
            'igst' => $out['igst'] - $in['igst'] + $rcm['igst'],
            'cess' => $out['cess'] - $in['cess'] + $rcm['cess'],
        ];

        $row = fn (string $name, array $tax) => [[
            'Particular' => $name,
            'Taxable' => Money::format($tax['taxable'] ?? 0),
            'CGST' => Money::format($tax['cgst']),
            'SGST' => Money::format($tax['sgst']),
            'IGST' => Money::format($tax['igst']),
            'Cess' => Money::format($tax['cess'] ?? 0),
        ]];

        return [
            'title' => 'GSTR-3B'.$this->gstinLabel($registrationId),
            'sections' => [
                ['heading' => 'Outward supplies', 'columns' => ['Particular', 'Taxable', 'CGST', 'SGST', 'IGST', 'Cess'], 'rows' => $row('Outward supplies', $out)],
                ['heading' => 'Input tax credit', 'columns' => ['Particular', 'Taxable', 'CGST', 'SGST', 'IGST', 'Cess'], 'rows' => $row('Inward supplies', $in)],
                ['heading' => 'Reverse charge', 'columns' => ['Particular', 'CGST', 'SGST', 'IGST'], 'rows' => [[
                    'Particular' => 'Inward supplies liable to reverse charge',
                    'CGST' => Money::format($rcm['cgst']),
                    'SGST' => Money::format($rcm['sgst']),
                    'IGST' => Money::format($rcm['igst']),
                ]]],
                ['heading' => 'Tax payable', 'columns' => ['Particular', 'CGST', 'SGST', 'IGST', 'Cess'], 'rows' => [[
                    'Particular' => 'Outward tax less input tax credit',
                    'CGST' => Money::format($liability['cgst']),
                    'SGST' => Money::format($liability['sgst']),
                    'IGST' => Money::format($liability['igst']),
                    'Cess' => Money::format($liability['cess']),
                ]]],
            ],
        ];
    }

    /**
     * @return array{title: string, sections: list<array{heading: string, columns: list<string>, rows: list<array<string, string>>}>}
     */
    public function cashFlow(Company $company, string $from, string $to, ?int $branchId): array
    {
        $cashIds = $this->ledgerIds($company, ['CASH', 'BANK']);
        $opening = $this->cashPosition($company, $cashIds, $branchId, null, date('Y-m-d', strtotime($from.' -1 day')));
        $buckets = ['Operating' => 0, 'Investing' => 0, 'Financing' => 0];
        $rows = [];

        $vouchers = Voucher::query()
            ->with(['entries.ledger.accountGroup.parent'])
            ->where('company_id', $company->id)
            ->where('status', VoucherStatus::Posted)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->tap(fn ($query) => DateRange::apply($query, 'voucher_date', $from, $to))
            ->orderBy('voucher_date')
            ->get();

        foreach ($vouchers as $voucher) {
            $cash = 0;
            $class = 'Operating';

            foreach ($voucher->entries as $entry) {
                $signed = Money::cents((string) $entry->debit) - Money::cents((string) $entry->credit);

                if (in_array($entry->ledger_id, $cashIds, true)) {
                    $cash += $signed;

                    continue;
                }

                $class = $this->flowClass($entry->ledger);
            }

            if ($cash === 0) {
                continue;
            }

            $buckets[$class] += $cash;
            $rows[] = [
                'Date' => $voucher->voucher_date?->format('d M Y') ?? '',
                'Voucher' => $voucher->voucher_number,
                'Class' => $class,
                'Cash in/(out)' => Money::format($cash),
                'url' => tally_route('vouchers.show', $voucher),
            ];
        }

        $movement = $buckets['Operating'] + $buckets['Investing'] + $buckets['Financing'];

        return [
            'title' => 'Cash Flow',
            'sections' => [
                ['heading' => 'Summary', 'columns' => ['Particular', 'Amount'], 'rows' => [
                    ['Particular' => 'Opening cash and bank', 'Amount' => Money::format($opening)],
                    ['Particular' => 'Operating inflow', 'Amount' => Money::format($this->flowSide($rows, 'Operating', true))],
                    ['Particular' => 'Operating outflow', 'Amount' => Money::format($this->flowSide($rows, 'Operating', false))],
                    ['Particular' => 'Operating', 'Amount' => Money::format($buckets['Operating'])],
                    ['Particular' => 'Investing inflow', 'Amount' => Money::format($this->flowSide($rows, 'Investing', true))],
                    ['Particular' => 'Investing outflow', 'Amount' => Money::format($this->flowSide($rows, 'Investing', false))],
                    ['Particular' => 'Investing', 'Amount' => Money::format($buckets['Investing'])],
                    ['Particular' => 'Financing inflow', 'Amount' => Money::format($this->flowSide($rows, 'Financing', true))],
                    ['Particular' => 'Financing outflow', 'Amount' => Money::format($this->flowSide($rows, 'Financing', false))],
                    ['Particular' => 'Financing', 'Amount' => Money::format($buckets['Financing'])],
                    ['Particular' => 'Net change', 'Amount' => Money::format($movement)],
                    ['Particular' => 'Closing cash and bank', 'Amount' => Money::format($opening + $movement)],
                ]],
                ['heading' => 'Movements', 'columns' => ['Date', 'Voucher', 'Class', 'Cash in/(out)'], 'rows' => $rows],
            ],
        ];
    }

    /**
     * @return array{title: string, sections: list<array{heading: string, columns: list<string>, rows: list<array<string, string>>}>}
     */
    public function fundsFlow(Company $company, string $from, string $to, ?int $branchId): array
    {
        $sources = [];
        $applications = [];
        $groups = $company->accountGroups()->get()->keyBy('id');

        $entries = VoucherEntry::query()
            ->join('acct_vouchers', 'acct_vouchers.id', '=', 'acct_voucher_entries.voucher_id')
            ->join('acct_ledgers', 'acct_ledgers.id', '=', 'acct_voucher_entries.ledger_id')
            ->where('acct_vouchers.company_id', $company->id)
            ->where('acct_vouchers.status', VoucherStatus::Posted)
            ->when($branchId, fn ($query) => $query->where('acct_vouchers.branch_id', $branchId))
            ->tap(fn ($query) => DateRange::apply($query, 'acct_vouchers.voucher_date', $from, $to))
            ->selectRaw('acct_ledgers.account_group_id as group_id, SUM(acct_voucher_entries.debit) as debit, SUM(acct_voucher_entries.credit) as credit')
            ->groupBy('acct_ledgers.account_group_id')
            ->get();

        foreach ($entries as $entry) {
            $group = $groups->get($entry->group_id);
            $code = $group?->code ?? '';

            if (in_array($code, ['CASH', 'BANK'], true)) {
                continue;
            }

            $netCredit = Money::cents((string) $entry->credit) - Money::cents((string) $entry->debit);
            $row = ['Group' => $group?->name ?? 'Ungrouped', 'Amount' => Money::format(abs($netCredit))];

            if ($netCredit > 0) {
                $sources[] = $row;
            } elseif ($netCredit < 0) {
                $applications[] = $row;
            }
        }

        $cashIds = $this->ledgerIds($company, ['CASH', 'BANK']);
        $opening = $this->cashPosition($company, $cashIds, $branchId, null, date('Y-m-d', strtotime($from.' -1 day')));
        $closing = $this->cashPosition($company, $cashIds, $branchId, null, $to);
        $moves = Voucher::query()
            ->where('company_id', $company->id)
            ->where('status', VoucherStatus::Posted)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->tap(fn ($query) => DateRange::apply($query, 'voucher_date', $from, $to))
            ->orderBy('voucher_date')
            ->limit(200)
            ->get()
            ->map(fn (Voucher $voucher) => [
                'Date' => $voucher->voucher_date?->format('d M Y') ?? '',
                'Voucher' => $voucher->voucher_number,
                'Amount' => (string) $voucher->total_debit,
                'url' => tally_route('vouchers.show', $voucher),
            ])
            ->all();

        return [
            'title' => 'Funds Flow',
            'sections' => [
                ['heading' => 'Fund position', 'columns' => ['Particular', 'Amount'], 'rows' => [
                    ['Particular' => 'Opening cash and bank', 'Amount' => Money::format($opening)],
                    ['Particular' => 'Closing cash and bank', 'Amount' => Money::format($closing)],
                ]],
                ['heading' => 'Sources of funds', 'columns' => ['Group', 'Amount'], 'rows' => $sources],
                ['heading' => 'Applications of funds', 'columns' => ['Group', 'Amount'], 'rows' => $applications],
                ['heading' => 'Transactions', 'columns' => ['Date', 'Voucher', 'Amount'], 'rows' => $moves],
            ],
        ];
    }

    /**
     * @return array{title: string, sections: list<array{heading: string, columns: list<string>, rows: list<array<string, string>>}>}
     */
    public function accountBooks(Company $company, string $from, string $to, ?int $branchId): array
    {
        return [
            'title' => 'Account Books',
            'sections' => [
                $this->book($company, $from, $to, $branchId, 'Cash book', ['CASH']),
                $this->book($company, $from, $to, $branchId, 'Bank book', ['BANK']),
            ],
        ];
    }

    /**
     * @return array{title: string, sections: list<array{heading: string, columns: list<string>, rows: list<array<string, string>>}>}
     */
    public function statementsOfAccounts(Company $company, string $from, string $to, ?int $branchId): array
    {
        $ids = $this->ledgerIds($company, ['DEBTORS', 'CREDITORS']);
        $rows = [];

        foreach (Ledger::query()->whereIn('id', $ids)->orderBy('name')->get() as $ledger) {
            $movement = $this->ledgerMovement($ledger->id, $from, $to, $branchId);
            $rows[] = [
                'Ledger' => $ledger->name,
                'Debit' => Money::format($movement['debit']),
                'Credit' => Money::format($movement['credit']),
                'url' => tally_route('reports.ledger', ['ledger_id' => $ledger->id, 'from' => $from, 'to' => $to]),
            ];
        }

        return [
            'title' => 'Statements of Accounts',
            'sections' => [[
                'heading' => 'Party statements',
                'columns' => ['Ledger', 'Debit', 'Credit'],
                'rows' => $rows,
            ]],
        ];
    }

    /**
     * @return array{title: string, sections: list<array{heading: string, columns: list<string>, rows: list<array<string, string>>}>}
     */
    public function inventoryBooks(Company $company, string $from, string $to, ?int $branchId): array
    {
        $rows = StockMovement::query()
            ->with('product')
            ->where('company_id', $company->id)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->tap(fn ($query) => DateRange::apply($query, 'movement_date', $from, $to))
            ->orderBy('movement_date')
            ->limit(500)
            ->get()
            ->map(fn (StockMovement $movement) => [
                'Date' => $movement->movement_date?->format('d M Y') ?? '',
                'Item' => $movement->product?->name ?? '',
                'Type' => (string) $movement->movement_type?->value,
                'Quantity' => (string) $movement->quantity,
                'Value' => (string) $movement->value,
            ])
            ->all();

        return [
            'title' => 'Inventory Books',
            'sections' => [[
                'heading' => 'Stock movement book',
                'columns' => ['Date', 'Item', 'Type', 'Quantity', 'Value'],
                'rows' => $rows,
            ]],
        ];
    }

    /**
     * @return array{title: string, sections: list<array{heading: string, columns: list<string>, rows: list<array<string, string>>}>}
     */
    public function statementsOfInventory(Company $company, string $from, string $to, ?int $branchId): array
    {
        $rows = StockMovement::query()
            ->join('acct_products', 'acct_products.id', '=', 'acct_stock_movements.product_id')
            ->leftJoin('acct_godowns', 'acct_godowns.id', '=', 'acct_stock_movements.godown_id')
            ->where('acct_stock_movements.company_id', $company->id)
            ->when($branchId, fn ($query) => $query->where('acct_stock_movements.branch_id', $branchId))
            ->tap(fn ($query) => DateRange::apply($query, 'acct_stock_movements.movement_date', $from, $to))
            ->selectRaw("acct_products.name as item, COALESCE(acct_godowns.name, 'No godown') as godown, SUM(acct_stock_movements.quantity) as quantity, SUM(acct_stock_movements.value) as value")
            ->groupBy('acct_products.name')
            ->groupByRaw("COALESCE(acct_godowns.name, 'No godown')")
            ->orderBy('acct_products.name')
            ->get()
            ->map(fn ($row) => [
                'Item' => $row->item,
                'Godown' => $row->godown,
                'Quantity' => (string) $row->quantity,
                'Value' => (string) $row->value,
            ])
            ->all();

        return [
            'title' => 'Statements of Inventory',
            'sections' => [[
                'heading' => 'Item and godown statement',
                'columns' => ['Item', 'Godown', 'Quantity', 'Value'],
                'rows' => $rows,
            ]],
        ];
    }

    /**
     * @return array{title: string, sections: list<array{heading: string, columns: list<string>, rows: list<array<string, string>>}>}
     */
    public function exceptions(Company $company, ?int $branchId): array
    {
        $rows = [];

        $unbalanced = Voucher::query()
            ->where('company_id', $company->id)
            ->where('status', VoucherStatus::Posted)
            ->whereColumn('total_debit', '!=', 'total_credit')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->limit(50)
            ->get();

        foreach ($unbalanced as $voucher) {
            $rows[] = ['Area' => 'Accounting', 'Issue' => 'Posted voucher is not balanced', 'Record' => $voucher->voucher_number, 'url' => tally_route('vouchers.show', $voucher)];
        }

        $drafts = Voucher::query()
            ->where('company_id', $company->id)
            ->where('status', VoucherStatus::Draft)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->count();

        if ($drafts > 0) {
            $rows[] = ['Area' => 'Accounting', 'Issue' => $drafts.' draft voucher'.($drafts === 1 ? '' : 's').' are not posted', 'Record' => 'Vouchers'];
        }

        $negative = DB::table('acct_stock_movements')
            ->join('acct_products', 'acct_products.id', '=', 'acct_stock_movements.product_id')
            ->where('acct_stock_movements.company_id', $company->id)
            ->when($branchId, fn ($query) => $query->where('acct_stock_movements.branch_id', $branchId))
            ->selectRaw('acct_products.id, acct_products.name, acct_products.opening_quantity, SUM(acct_stock_movements.quantity) as moved')
            ->groupBy('acct_products.id', 'acct_products.name', 'acct_products.opening_quantity')
            ->get();

        foreach ($negative as $product) {
            $onHand = (float) $product->opening_quantity + (float) $product->moved;

            if ($onHand < 0) {
                $rows[] = ['Area' => 'Inventory', 'Issue' => 'Negative stock '.number_format($onHand, 4), 'Record' => $product->name, 'url' => tally_route('products.show', $product->id)];
            }
        }

        $missingGstin = Invoice::query()
            ->with('party')
            ->where('company_id', $company->id)
            ->where('status', VoucherStatus::Posted)
            ->where('kind', InvoiceKind::Sales)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereHas('party', fn ($query) => $query->where(fn ($query) => $query->whereNull('gstin')->orWhere('gstin', '')))
            ->limit(50)
            ->get();

        foreach ($missingGstin as $invoice) {
            $rows[] = ['Area' => 'GST', 'Issue' => 'Posted sales invoice has no party GSTIN', 'Record' => $invoice->invoice_number, 'url' => tally_route('invoices.sales.show', $invoice)];
        }

        $ungrouped = $company->ledgers()->whereNull('account_group_id')->count();

        if ($ungrouped > 0) {
            $rows[] = ['Area' => 'Masters', 'Issue' => $ungrouped.' ledger'.($ungrouped === 1 ? '' : 's').' have no account group', 'Record' => 'Ledgers', 'url' => tally_route('ledgers.index')];
        }

        return [
            'title' => 'Exception Reports',
            'sections' => [[
                'heading' => 'Exceptions',
                'columns' => ['Area', 'Issue', 'Record'],
                'rows' => $rows,
            ]],
        ];
    }

    /**
     * @return array{title: string, sections: list<array{heading: string, columns: list<string>, rows: list<array<string, string>>}>}
     */
    public function analysis(Company $company, string $from, string $to, ?int $branchId): array
    {
        $rows = [];
        $totals = Voucher::query()
            ->where('company_id', $company->id)
            ->where('status', VoucherStatus::Posted)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->tap(fn ($query) => DateRange::apply($query, 'voucher_date', $from, $to))
            ->selectRaw('SUM(total_debit) as debit, SUM(total_credit) as credit')
            ->first();
        $difference = Money::cents((string) ($totals->debit ?? 0)) - Money::cents((string) ($totals->credit ?? 0));
        $rows[] = [
            'Check' => 'Posted vouchers balance',
            'Result' => $difference === 0 ? 'Balanced' : 'Difference '.Money::format($difference),
        ];

        $gst = $this->gstr3b($company, $from, $to, $branchId);
        $rows[] = [
            'Check' => 'GST outward and inward computed from posted invoices',
            'Result' => $gst['sections'][3]['rows'][0]['CGST'].' CGST payable',
        ];

        $stockValue = StockMovement::query()
            ->where('company_id', $company->id)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->sum('value');
        $openingValue = Product::query()->where('company_id', $company->id)->sum('opening_value');
        $rows[] = [
            'Check' => 'Inventory movement value plus opening value',
            'Result' => number_format((float) $openingValue + (float) $stockValue, 2, '.', ''),
        ];

        return [
            'title' => 'Analysis & Verification',
            'sections' => [[
                'heading' => 'Verification',
                'columns' => ['Check', 'Result'],
                'rows' => $rows,
            ]],
        ];
    }

    /**
     * @return array{title: string, sections: list<array{heading: string, columns: list<string>, rows: list<array<string, string>>}>}
     */
    public function editLog(Company $company, string $from, string $to): array
    {
        $rows = AuditLog::query()
            ->leftJoin('users', 'users.id', '=', 'acct_audit_logs.user_id')
            ->where('acct_audit_logs.company_id', $company->id)
            ->tap(fn ($query) => DateRange::apply($query, 'acct_audit_logs.created_at', $from, $to, true))
            ->selectRaw("DATE(acct_audit_logs.created_at) as day, COALESCE(users.name, 'System') as user_name, acct_audit_logs.module, COUNT(*) as changes")
            ->groupByRaw("DATE(acct_audit_logs.created_at), COALESCE(users.name, 'System'), acct_audit_logs.module")
            ->orderByDesc('day')
            ->get()
            ->map(fn ($row) => [
                'Date' => (string) $row->day,
                'User' => (string) $row->user_name,
                'Module' => (string) $row->module,
                'Changes' => (string) $row->changes,
            ])
            ->all();

        return [
            'title' => 'Edit Log Summary',
            'sections' => [[
                'heading' => 'Changes by user, date, and module',
                'columns' => ['Date', 'User', 'Module', 'Changes'],
                'rows' => $rows,
            ]],
        ];
    }

    /**
     * @return list<Invoice>
     */
    /**
     * @param  list<array<string, string>>  $rows
     */
    private function flowSide(array $rows, string $class, bool $inflow): int
    {
        $total = 0;

        foreach ($rows as $row) {
            if (($row['Class'] ?? '') !== $class) {
                continue;
            }

            $cents = Money::cents($row['Cash in/(out)'] ?? '0');

            if ($inflow && $cents > 0) {
                $total += $cents;
            }

            if (! $inflow && $cents < 0) {
                $total += abs($cents);
            }
        }

        return $total;
    }

    private function gstinLabel(?int $registrationId): string
    {
        if (! $registrationId) {
            return '';
        }

        $gstin = \Tally\Models\GstRegistration::query()->whereKey($registrationId)->value('gstin');

        return $gstin ? ' · '.$gstin : '';
    }

    private function invoices(Company $company, string $from, string $to, ?int $branchId, ?int $registrationId = null)
    {
        return Invoice::query()
            ->with(['party', 'lines.hsnSac', 'gstRegistration'])
            ->where('company_id', $company->id)
            ->where('status', VoucherStatus::Posted)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($registrationId, function ($query) use ($company, $registrationId) {
                $registration = \Tally\Models\GstRegistration::query()->where('company_id', $company->id)->find($registrationId);

                if (! $registration) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where(function ($query) use ($registration) {
                    $query->where('gst_registration_id', $registration->id);

                    if ($registration->branch_id) {
                        $query->orWhere(fn ($query) => $query->whereNull('gst_registration_id')->where('branch_id', $registration->branch_id));
                    }
                });
            })
            ->tap(fn ($query) => DateRange::apply($query, 'invoice_date', $from, $to))
            ->orderBy('invoice_date')
            ->get()
            ->all();
    }

    /**
     * @return array{taxable: string, cgst: string, sgst: string, igst: string}
     */
    private function taxRow(Invoice $invoice): array
    {
        $sign = $invoice->kind === InvoiceKind::CreditNote ? -1 : 1;
        $tax = $this->blankTax();

        foreach ($invoice->lines as $line) {
            $tax['taxable'] += $sign * Money::cents((string) $line->taxable_amount);
            $tax['cgst'] += $sign * Money::cents((string) $line->cgst_amount);
            $tax['sgst'] += $sign * Money::cents((string) $line->sgst_amount);
            $tax['igst'] += $sign * Money::cents((string) $line->igst_amount);
        }

        return [
            'taxable' => Money::format($tax['taxable']),
            'cgst' => Money::format($tax['cgst']),
            'sgst' => Money::format($tax['sgst']),
            'igst' => Money::format($tax['igst']),
        ];
    }

    /**
     * @param  list<string>  $codes
     * @return list<int>
     */
    private function ledgerIds(Company $company, array $codes): array
    {
        return $company->ledgers()->with('accountGroup.parent')->get()
            ->filter(fn (Ledger $ledger) => $ledger->belongsToGroup(...$codes))
            ->pluck('id')
            ->all();
    }

    /**
     * @param  list<int>  $cashIds
     */
    private function cashPosition(Company $company, array $cashIds, ?int $branchId, ?string $from, ?string $to): int
    {
        if ($cashIds === []) {
            return 0;
        }

        $query = VoucherEntry::query()
            ->join('acct_vouchers', 'acct_vouchers.id', '=', 'acct_voucher_entries.voucher_id')
            ->whereIn('acct_voucher_entries.ledger_id', $cashIds)
            ->where('acct_vouchers.company_id', $company->id)
            ->where('acct_vouchers.status', VoucherStatus::Posted)
            ->when($branchId, fn ($query) => $query->where('acct_vouchers.branch_id', $branchId));
        DateRange::apply($query, 'acct_vouchers.voucher_date', $from, $to);
        $row = $query->selectRaw('SUM(acct_voucher_entries.debit) as debit, SUM(acct_voucher_entries.credit) as credit')->first();

        return Money::cents((string) ($row->debit ?? 0)) - Money::cents((string) ($row->credit ?? 0));
    }

    private function flowClass(?Ledger $ledger): string
    {
        if (! $ledger) {
            return 'Operating';
        }

        if (in_array($ledger->cash_flow_class, ['Operating', 'Investing', 'Financing'], true)) {
            return $ledger->cash_flow_class;
        }

        if ($ledger->belongsToGroup('FIXED_ASSETS', 'INVESTMENTS')) {
            return 'Investing';
        }

        if ($ledger->belongsToGroup('CAPITAL', 'LOANS')) {
            return 'Financing';
        }

        return 'Operating';
    }

    /**
     * @param  list<string>  $codes
     * @return array{heading: string, columns: list<string>, rows: list<array<string, string>>}
     */
    private function book(Company $company, string $from, string $to, ?int $branchId, string $heading, array $codes): array
    {
        $ids = $this->ledgerIds($company, $codes);
        $rows = [];

        if ($ids !== []) {
            $rows = VoucherEntry::query()
                ->with(['voucher', 'ledger'])
                ->whereIn('ledger_id', $ids)
                ->whereHas('voucher', function ($query) use ($company, $from, $to, $branchId) {
                    $query->where('company_id', $company->id)->where('status', VoucherStatus::Posted)
                        ->when($branchId, fn ($query) => $query->where('branch_id', $branchId));
                    DateRange::apply($query, 'voucher_date', $from, $to);
                })
                ->limit(300)
                ->get()
                ->map(fn (VoucherEntry $entry) => [
                    'Date' => $entry->voucher?->voucher_date?->format('d M Y') ?? '',
                    'Voucher' => $entry->voucher?->voucher_number ?? '',
                    'Ledger' => $entry->ledger?->name ?? '',
                    'Debit' => (string) $entry->debit,
                    'Credit' => (string) $entry->credit,
                    'url' => $entry->voucher ? tally_route('vouchers.show', $entry->voucher) : '',
                ])
                ->all();
        }

        return ['heading' => $heading, 'columns' => ['Date', 'Voucher', 'Ledger', 'Debit', 'Credit'], 'rows' => $rows];
    }

    /**
     * @return array{debit: int, credit: int}
     */
    private function ledgerMovement(int $ledgerId, string $from, string $to, ?int $branchId): array
    {
        $query = VoucherEntry::query()
            ->join('acct_vouchers', 'acct_vouchers.id', '=', 'acct_voucher_entries.voucher_id')
            ->where('acct_voucher_entries.ledger_id', $ledgerId)
            ->where('acct_vouchers.status', VoucherStatus::Posted)
            ->when($branchId, fn ($query) => $query->where('acct_vouchers.branch_id', $branchId));
        DateRange::apply($query, 'acct_vouchers.voucher_date', $from, $to);
        $row = $query->selectRaw('SUM(acct_voucher_entries.debit) as debit, SUM(acct_voucher_entries.credit) as credit')->first();

        return [
            'debit' => Money::cents((string) ($row->debit ?? 0)),
            'credit' => Money::cents((string) ($row->credit ?? 0)),
        ];
    }

    /**
     * @return array{taxable: int, cgst: int, sgst: int, igst: int, cess: int}
     */
    private function blankTax(): array
    {
        return ['taxable' => 0, 'cgst' => 0, 'sgst' => 0, 'igst' => 0, 'cess' => 0];
    }
}
