<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Documents\DocumentPdf;
use Tally\Documents\TradingDocument;
use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\PartyDirectory;
use Tally\Accounting\Money;
use Tally\Models\PurchaseOrder;
use Tally\Reporting\LedgerBalances;
use Tally\Purchasing\PurchaseOrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', [
                'title' => 'Purchase orders',
                'message' => 'Select a company and financial year before opening purchase orders.',
            ]);
        }

        return view('tally::purchase-orders.index', [
            'company' => $company,
            'orders' => PurchaseOrder::query()
                ->with('supplier')
                ->where('company_id', $company->id)
                ->where('financial_year_id', $year->id)
                ->orderByDesc('order_date')
                ->orderByDesc('id')
                ->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context, PartyDirectory $parties): View
    {
        $company = $context->company();

        if (! $company || ! $context->branch() || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', [
                'title' => 'Purchase order',
                'message' => 'Select a company, branch, and financial year before creating a purchase order.',
            ]);
        }

        $year = $context->financialYear();
        $count = PurchaseOrder::query()->where('company_id', $company->id)->where('financial_year_id', $year->id)->count() + 1;
        $balances = [];

        foreach (app(LedgerBalances::class)->asOn($company, $context->branch()->id, $year, $year->end_date->toDateString()) as $row) {
            $signed = $row['debit'] - $row['credit'];

            if ($signed !== 0) {
                $balances[$row['ledger']->id] = Money::format(abs($signed)).($signed > 0 ? ' Dr' : ' Cr');
            }
        }

        return view('tally::purchase-orders.form', [
            'company' => $company,
            'year' => $year,
            'nextNumber' => 'PO-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT),
            'suppliers' => $parties->options($company, InvoiceKind::Purchase),
            'purchaseLedgers' => $company->ledgers()->whereHas('accountGroup', fn ($query) => $query->where('code', 'PURCHASE'))->orderBy('name')->get(),
            'products' => $company->products()->where('is_active', true)->with('primaryUnit')->orderBy('name')->get(),
            'taxRates' => $company->taxRates()->where('is_active', true)->orderBy('name')->get(),
            'balances' => $balances,
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, PurchaseOrderService $orders): RedirectResponse
    {
        $order = $orders->save(
            $context->company(),
            $context->branch(),
            $context->financialYear(),
            $request->user(),
            $request->all(),
        );

        return redirect()->route('books.tally.purchase-orders.show', $order)->with('status', 'Purchase order saved. It does not post stock or accounts.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'lines.product', 'lines.hsnSac', 'branch']);

        return view('tally::purchase-orders.show', ['order' => $purchaseOrder]);
    }

    public function print(PurchaseOrder $purchaseOrder, TradingDocument $documents): View
    {
        return view('tally::documents.print', [
            'document' => $documents->purchaseOrder($purchaseOrder),
            'back' => tally_route('purchase-orders.show', $purchaseOrder),
        ]);
    }

    public function pdf(PurchaseOrder $purchaseOrder, TradingDocument $documents, DocumentPdf $pdf): \Symfony\Component\HttpFoundation\Response
    {
        return $pdf->trading($documents->purchaseOrder($purchaseOrder));
    }
}
