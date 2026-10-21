<?php

namespace App\Http\Controllers\Reporting;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\FreightBill;
use App\Domains\Purchasing\Models\SupplierPayable;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sync\Support\BooksCompany;
use App\Http\Controllers\Controller;
use App\Support\ReportExporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Models\Voucher;

/**
 * Financial reports derived from the existing document tables (invoices, payments,
 * credit notes, supplier payables) — no dedicated general-ledger table is used.
 * The date-range filter defaults to the current financial year.
 */
class FinancialReportController extends Controller
{
    public function dayBook(Request $request): View|Response
    {
        [$from, $to] = $this->range($request);

        $rows = $this->buildDayBook($from, $to);

        if ($search = trim($request->input('search', ''))) {
            $lower = mb_strtolower($search);
            $rows = $rows->filter(function ($r) use ($lower) {
                return str_contains(mb_strtolower($r['voucher'] ?? ''), $lower)
                    || str_contains(mb_strtolower($r['party'] ?? ''), $lower)
                    || str_contains(mb_strtolower($r['type'] ?? ''), $lower)
                    || str_contains(mb_strtolower($r['narration'] ?? ''), $lower);
            })->values();
        }

        $sort = $request->input('sort', 'date');
        $dir = $request->input('direction', 'asc');
        $rows = $dir === 'desc'
            ? $rows->sortByDesc($sort)->values()
            : $rows->sortBy($sort)->values();

        if ($request->filled('export')) {
            return $this->export($request, 'day-book', 'Day Book',
                ['Date', 'Voucher Type', 'Voucher No', 'Party', 'Narration', 'Debit', 'Credit'],
                $rows->map(fn ($r) => [
                    $r['date']->format('d M Y'), $r['type'], $r['voucher'], $r['party'], $r['narration'],
                    number_format((float) $r['debit'], 2), number_format((float) $r['credit'], 2),
                ]),
                ['From' => $from, 'To' => $to]
            );
        }

        return view('reporting.day-book', [
            'rows' => $rows,
            'dateFrom' => $from,
            'dateTo' => $to,
            'sort' => $sort,
            'direction' => $dir,
            'totals' => [
                'debit' => $rows->sum('debit'),
                'credit' => $rows->sum('credit'),
            ],
        ]);
    }

    public function profitLoss(Request $request): View|Response
    {
        [$from, $to] = $this->range($request);
        $data = $this->buildProfitLoss($from, $to);

        if ($request->filled('export')) {
            return $this->export($request, 'profit-loss', 'Profit &amp; Loss',
                ['Section', 'Line Item', 'Amount'],
                $data['rows'],
                ['From' => $from, 'To' => $to]
            );
        }

        return view('reporting.profit-loss', array_merge($data, ['dateFrom' => $from, 'dateTo' => $to]));
    }

    public function balanceSheet(Request $request): View|Response
    {
        $asOf = $request->input('as_of', now()->toDateString());
        $data = $this->buildBalanceSheet($asOf);

        if ($request->filled('export')) {
            return $this->export($request, 'balance-sheet', 'Balance Sheet',
                ['Side', 'Line Item', 'Amount'],
                $data['rows'],
                ['As of' => $asOf]
            );
        }

        return view('reporting.balance-sheet', array_merge($data, ['asOf' => $asOf]));
    }

    public function trialBalance(Request $request): View|Response
    {
        [$from, $to] = $this->range($request);
        $rows = $this->buildTrialBalance($from, $to);

        if ($request->filled('export')) {
            return $this->export($request, 'trial-balance', 'Trial Balance',
                ['Ledger', 'Debit', 'Credit', 'Balance'],
                $rows->map(fn ($r) => [$r['ledger'], number_format((float) $r['debit'], 2), number_format((float) $r['credit'], 2), number_format((float) $r['balance'], 2)]),
                ['From' => $from, 'To' => $to]
            );
        }

        return view('reporting.trial-balance', [
            'rows' => $rows,
            'dateFrom' => $from,
            'dateTo' => $to,
            'totals' => [
                'debit' => $rows->sum('debit'),
                'credit' => $rows->sum('credit'),
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // Data builders — kept protected so they can be reused by scheduled jobs.
    // ---------------------------------------------------------------------

    protected function buildDayBook(string $from, string $to)
    {
        $entries = collect();

        Invoice::query()
            ->with('customer')
            ->whereBetween('invoice_date', [$from, $to])
            ->orderBy('invoice_date')
            ->get()
            ->each(function (Invoice $inv) use ($entries) {
                $entries->push([
                    'date' => $inv->invoice_date, 'type' => 'Sales',
                    'voucher' => $inv->invoice_no, 'party' => $inv->customer?->name ?? '—',
                    'narration' => 'Being sales invoice',
                    'debit' => (float) $inv->grand_total, 'credit' => 0,
                ]);
            });

        Payment::query()
            ->with('customer')
            ->whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->where('status', 'completed')
            ->orderBy('paid_at')
            ->get()
            ->each(function (Payment $p) use ($entries) {
                $entries->push([
                    'date' => $p->paid_at ? Carbon::parse($p->paid_at) : now(),
                    'type' => 'Receipt', 'voucher' => $p->payment_no ?? ('PAY-'.$p->id),
                    'party' => $p->customer?->name ?? '—',
                    'narration' => 'Payment received via '.$p->method,
                    'debit' => 0, 'credit' => (float) $p->amount,
                ]);
            });

        CreditNote::query()
            ->with('customer')
            ->whereBetween('credit_note_date', [$from, $to])
            ->orderBy('credit_note_date')
            ->get()
            ->each(function (CreditNote $c) use ($entries) {
                $entries->push([
                    'date' => $c->credit_note_date, 'type' => 'Credit Note',
                    'voucher' => $c->credit_note_no, 'party' => $c->customer?->name ?? '—',
                    'narration' => 'Credit note ('.$c->reason.')',
                    'debit' => 0, 'credit' => (float) $c->grand_total,
                ]);
            });

        PurchaseInvoice::query()
            ->with('supplier')
            ->whereBetween('invoice_date', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->orderBy('invoice_date')
            ->get()
            ->each(function (PurchaseInvoice $pi) use ($entries) {
                $entries->push([
                    'date' => $pi->invoice_date, 'type' => 'Purchase',
                    'voucher' => $pi->invoice_no, 'party' => $pi->supplier?->name ?? '—',
                    'narration' => 'Purchase invoice',
                    'debit' => 0, 'credit' => (float) $pi->grand_total,
                ]);
            });

        FreightBill::query()
            ->whereBetween('bill_date', [$from, $to])
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '!=', 'cancelled');
            })
            ->orderBy('bill_date')
            ->get()
            ->each(function (FreightBill $bill) use ($entries) {
                $entries->push([
                    'date' => $bill->bill_date, 'type' => 'Freight',
                    'voucher' => $bill->freight_no, 'party' => $bill->transporter_name ?: '—',
                    'narration' => 'Freight bill',
                    'debit' => (float) $bill->total_amount, 'credit' => 0,
                ]);
            });

        $seen = $entries->pluck('voucher')->filter()->map(fn ($number) => (string) $number);
        foreach ($this->booksVouchers($from, $to, false, $seen) as $voucher) {
            $entries->push([
                'date' => $voucher->voucher_date,
                'type' => $voucher->voucher_type->label().($voucher->isDraft() ? ' (Draft)' : ''),
                'voucher' => $voucher->voucher_number,
                'party' => $voucher->ledgerSummary(),
                'narration' => $voucher->narration ?: 'Accounting voucher',
                'debit' => (float) $voucher->total_debit,
                'credit' => (float) $voucher->total_credit,
            ]);
        }

        return $entries->sortBy(fn ($e) => Carbon::parse($e['date'])->timestamp.'-'.$e['voucher'])->values();
    }

    protected function buildProfitLoss(string $from, string $to): array
    {
        $sales = (float) Invoice::whereBetween('invoice_date', [$from, $to])->where('status', '!=', 'cancelled')->sum('subtotal');
        $creditNotes = (float) CreditNote::whereBetween('credit_note_date', [$from, $to])->sum('grand_total');
        $purchases = (float) PurchaseInvoice::whereBetween('invoice_date', [$from, $to])->where('status', '!=', 'cancelled')->sum('subtotal');
        $books = $this->booksAmounts($from, $to, true);
        $sales += $books['sales'];
        $creditNotes += $books['credit_notes'];
        $purchases += $books['purchases'];
        $purchases = max(0, $purchases - $books['debit_notes']);
        $tax = (float) Invoice::whereBetween('invoice_date', [$from, $to])->sum('tax_amount');
        $purchaseTax = (float) PurchaseInvoice::whereBetween('invoice_date', [$from, $to])->sum('tax_amount');

        $revenue = $sales - $creditNotes;
        $cogs = $purchases;
        $grossProfit = $revenue - $cogs;

        // Direct expenses proxy — freight bills + landed cost totals
        $freight = (float) \DB::table('freight_bills')->whereBetween('bill_date', [$from, $to])->sum('total_amount');
        $operatingProfit = $grossProfit - $freight;

        $rows = collect([
            ['Section' => 'Income', 'Line Item' => 'Sales', 'Amount' => number_format($sales, 2)],
            ['Section' => 'Income', 'Line Item' => '(-) Credit Notes / Returns', 'Amount' => '−'.number_format($creditNotes, 2)],
            ['Section' => 'Income', 'Line Item' => 'Net Revenue', 'Amount' => number_format($revenue, 2), '_total' => true],
            ['Section' => 'Cost of Goods Sold', 'Line Item' => 'Purchases', 'Amount' => number_format($cogs, 2)],
            ['Section' => 'Gross Profit', 'Line Item' => 'Revenue − COGS', 'Amount' => number_format($grossProfit, 2), '_total' => true],
            ['Section' => 'Direct Expenses', 'Line Item' => 'Freight / Transport', 'Amount' => number_format($freight, 2)],
            ['Section' => 'Operating Profit', 'Line Item' => 'Gross Profit − Direct Expenses', 'Amount' => number_format($operatingProfit, 2), '_total' => true],
            ['Section' => 'GST', 'Line Item' => 'Output GST', 'Amount' => number_format($tax, 2)],
            ['Section' => 'GST', 'Line Item' => 'Input GST', 'Amount' => number_format($purchaseTax, 2)],
        ]);

        return [
            'rows' => $rows,
            'summary' => compact('revenue', 'cogs', 'grossProfit', 'freight', 'operatingProfit', 'tax', 'purchaseTax'),
        ];
    }

    protected function buildBalanceSheet(string $asOf): array
    {
        // Assets: receivables (customer outstanding) + stock value
        $receivables = (float) OutstandingLedger::query()
            ->whereDate('created_at', '<=', $asOf)
            ->groupBy('customer_id')
            ->selectRaw('MAX(id) as id')
            ->pluck('id')
            ->pipe(fn ($ids) => OutstandingLedger::whereIn('id', $ids)->sum('balance'));

        $stockValue = (float) \DB::table('stock_cost_layers')
            ->whereDate('received_on', '<=', $asOf)
            ->selectRaw('COALESCE(SUM(quantity_remaining * unit_cost), 0) as total')
            ->value('total');

        // Liabilities: supplier payables
        $payables = (float) SupplierPayable::query()
            ->whereDate('created_at', '<=', $asOf)
            ->groupBy('supplier_id')
            ->selectRaw('MAX(id) as id')
            ->pluck('id')
            ->pipe(fn ($ids) => SupplierPayable::whereIn('id', $ids)->sum('balance'));

        $assetsTotal = $receivables + $stockValue;
        $liabilitiesTotal = $payables;
        $equity = $assetsTotal - $liabilitiesTotal; // proxy — retained earnings

        $rows = collect([
            ['Side' => 'Assets', 'Line Item' => 'Trade Receivables', 'Amount' => number_format($receivables, 2)],
            ['Side' => 'Assets', 'Line Item' => 'Inventory (FIFO cost)', 'Amount' => number_format($stockValue, 2)],
            ['Side' => 'Assets', 'Line Item' => 'Total Assets', 'Amount' => number_format($assetsTotal, 2), '_total' => true],
            ['Side' => 'Liabilities', 'Line Item' => 'Trade Payables', 'Amount' => number_format($payables, 2)],
            ['Side' => 'Liabilities', 'Line Item' => 'Total Liabilities', 'Amount' => number_format($liabilitiesTotal, 2), '_total' => true],
            ['Side' => 'Equity', 'Line Item' => 'Retained Earnings (Assets − Liab.)', 'Amount' => number_format($equity, 2), '_total' => true],
        ]);

        return [
            'rows' => $rows,
            'summary' => compact('receivables', 'stockValue', 'payables', 'assetsTotal', 'liabilitiesTotal', 'equity'),
        ];
    }

    protected function buildTrialBalance(string $from, string $to)
    {
        $sales = (float) Invoice::whereBetween('invoice_date', [$from, $to])->where('status', '!=', 'cancelled')->sum('grand_total');
        $purchases = (float) PurchaseInvoice::whereBetween('invoice_date', [$from, $to])->where('status', '!=', 'cancelled')->sum('grand_total');
        $receipts = (float) Payment::whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59'])->where('status', 'completed')->sum('amount');
        $creditNotes = (float) CreditNote::whereBetween('credit_note_date', [$from, $to])->sum('grand_total');
        $outputGst = (float) Invoice::whereBetween('invoice_date', [$from, $to])->where('status', '!=', 'cancelled')->sum('tax_amount');
        $inputGst = (float) PurchaseInvoice::whereBetween('invoice_date', [$from, $to])->where('status', '!=', 'cancelled')->sum('tax_amount');
        $books = $this->booksAmounts($from, $to, true);
        $sales += $books['sales'];
        $purchases = max(0, $purchases + $books['purchases'] - $books['debit_notes']);
        $receipts += $books['receipts'];
        $creditNotes += $books['credit_notes'];
        $paymentsOut = $books['payments'];
        $freight = (float) \DB::table('freight_bills')->whereBetween('bill_date', [$from, $to])->sum('total_amount');

        $rows = collect([
            ['ledger' => 'Trade Receivables', 'debit' => $sales, 'credit' => 0, 'balance' => $sales],
            ['ledger' => 'Sales Revenue', 'debit' => 0, 'credit' => $sales - $outputGst, 'balance' => -($sales - $outputGst)],
            ['ledger' => 'Output GST Payable', 'debit' => 0, 'credit' => $outputGst, 'balance' => -$outputGst],
            ['ledger' => 'Bank / Cash', 'debit' => $receipts, 'credit' => $paymentsOut, 'balance' => $receipts - $paymentsOut],
            ['ledger' => 'Trade Payables', 'debit' => 0, 'credit' => $purchases, 'balance' => -$purchases],
            ['ledger' => 'Purchases', 'debit' => $purchases - $inputGst, 'credit' => 0, 'balance' => $purchases - $inputGst],
            ['ledger' => 'Input GST Receivable', 'debit' => $inputGst, 'credit' => 0, 'balance' => $inputGst],
            ['ledger' => 'Freight / Transport Expense', 'debit' => $freight, 'credit' => 0, 'balance' => $freight],
            ['ledger' => 'Sales Returns (Credit Notes)', 'debit' => $creditNotes, 'credit' => 0, 'balance' => $creditNotes],
        ]);

        return $rows;
    }

    /**
     * Accounting vouchers that are not already represented by a DMS document.
     *
     * @param  Collection<int, string>  $skipNumbers
     * @return Collection<int, Voucher>
     */
    protected function booksVouchers(string $from, string $to, bool $postedOnly, Collection $skipNumbers): Collection
    {
        $books = app(\Tally\Context\WorkspaceContext::class)->company();
        if (! $books) {
            $organizationId = (int) (auth()->user()?->company_id ?: 0);
            $books = $organizationId ? BooksCompany::forOrganization($organizationId) : null;
        }
        if (! $books && \Tally\Models\Company::query()->count() === 1) {
            $books = \Tally\Models\Company::query()->first();
        }
        if (! $books) {
            return collect();
        }

        $linkedInvoiceIds = Schema::hasTable('sync_entity_links')
            ? DB::table('sync_entity_links')
                ->whereIn('entity_key', ['sales_invoice', 'purchase_invoice', 'credit_note'])
                ->pluck('acct_id')
            : collect();

        return Voucher::query()
            ->with(['entries.ledger', 'invoice'])
            ->where('company_id', $books->id)
            ->where('status', '!=', VoucherStatus::Cancelled)
            ->when($postedOnly, fn ($query) => $query->where('status', VoucherStatus::Posted))
            ->whereBetween('voucher_date', [$from, $to])
            ->orderBy('voucher_date')
            ->orderBy('id')
            ->get()
            ->reject(function (Voucher $voucher) use ($skipNumbers, $linkedInvoiceIds) {
                if ($skipNumbers->contains((string) $voucher->voucher_number)) {
                    return true;
                }

                return $voucher->invoice && $linkedInvoiceIds->contains($voucher->invoice->id);
            })
            ->values();
    }

    /**
     * @return array{sales: float, purchases: float, receipts: float, payments: float, credit_notes: float, debit_notes: float}
     */
    protected function booksAmounts(string $from, string $to, bool $postedOnly): array
    {
        $amounts = [
            'sales' => 0.0,
            'purchases' => 0.0,
            'receipts' => 0.0,
            'payments' => 0.0,
            'credit_notes' => 0.0,
            'debit_notes' => 0.0,
        ];

        $numbers = Invoice::query()->whereBetween('invoice_date', [$from, $to])->pluck('invoice_no')
            ->merge(PurchaseInvoice::query()->whereBetween('invoice_date', [$from, $to])->pluck('invoice_no'))
            ->merge(CreditNote::query()->whereBetween('credit_note_date', [$from, $to])->pluck('credit_note_no'))
            ->merge(Payment::query()->whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59'])->pluck('payment_no'))
            ->filter()
            ->map(fn ($number) => (string) $number);

        foreach ($this->booksVouchers($from, $to, $postedOnly, $numbers) as $voucher) {
            $amount = (float) ($voucher->total_debit ?: $voucher->total_credit);
            $key = match ($voucher->voucher_type) {
                VoucherType::Sales => 'sales',
                VoucherType::Purchase => 'purchases',
                VoucherType::Receipt => 'receipts',
                VoucherType::Payment => 'payments',
                VoucherType::CreditNote => 'credit_notes',
                VoucherType::DebitNote => 'debit_notes',
                default => null,
            };
            if ($key) {
                $amounts[$key] += $amount;
            }
        }

        return $amounts;
    }

    /**
     * @return array{0:string,1:string}
     */
    protected function range(Request $request): array
    {
        $fy = FinancialYear::query()
            ->when(auth()->user()?->company_id, fn ($q) => $q->where('company_id', auth()->user()->company_id))
            ->where('is_current', true)->first();

        $from = $request->input('date_from', $fy?->starts_on?->toDateString() ?? now()->startOfMonth()->toDateString());
        $to = $request->input('date_to', $fy?->ends_on?->toDateString() ?? now()->toDateString());

        return [$from, $to];
    }

    protected function export(Request $request, string $slug, string $title, array $headers, $rows, array $meta = []): Response
    {
        $format = $request->input('export');
        $filename = $slug.'-'.now()->format('Ymd-His').'.'.($format === 'pdf' ? 'pdf' : 'csv');
        return $format === 'pdf'
            ? ReportExporter::pdf($filename, $title, $headers, is_array($rows) ? $rows : $rows->all(), $meta)
            : ReportExporter::csv($filename, $headers, is_array($rows) ? $rows : $rows->all());
    }
}
