<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\PartyDirectory;
use Tally\Models\SalesOrder;
use Tally\Selling\SalesOrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalesOrderController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', ['title' => 'Sales Orders', 'message' => 'Select a company and financial year.']);
        }

        $orders = SalesOrder::query()
            ->with('customer')
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('order_date')
            ->paginate(25)
            ->withQueryString();

        return view('tally::sales-orders.index', ['company' => $company, 'orders' => $orders, 'status' => $request->query('status')]);
    }

    public function report(Request $request, WorkspaceContext $context): View
    {
        return $this->index($request, $context);
    }

    public function create(WorkspaceContext $context, PartyDirectory $parties): View
    {
        return $this->form($context, $parties, null);
    }

    public function store(Request $request, WorkspaceContext $context, SalesOrderService $orders): RedirectResponse
    {
        $order = $orders->save($context->company(), $context->branch(), $context->financialYear(), $request->user(), $request->all());

        return redirect()->route('books.tally.sales-orders.show', $order)->with('status', 'Sales order saved. It does not move stock or post accounts.');
    }

    public function show(WorkspaceContext $context, SalesOrder $salesOrder): View
    {
        abort_unless($salesOrder->company_id === $context->company()?->id, 404);
        $salesOrder->load(['customer', 'lines.product']);

        return view('tally::sales-orders.show', ['order' => $salesOrder]);
    }

    public function edit(WorkspaceContext $context, PartyDirectory $parties, SalesOrder $salesOrder): View
    {
        abort_unless($salesOrder->company_id === $context->company()?->id, 404);

        return $this->form($context, $parties, $salesOrder);
    }

    public function update(Request $request, WorkspaceContext $context, SalesOrderService $orders, SalesOrder $salesOrder): RedirectResponse
    {
        abort_unless($salesOrder->company_id === $context->company()?->id, 404);
        $orders->save($context->company(), $context->branch(), $context->financialYear(), $request->user(), $request->all(), $salesOrder);

        return redirect()->route('books.tally.sales-orders.show', $salesOrder)->with('status', 'Sales order updated.');
    }

    public function status(Request $request, WorkspaceContext $context, SalesOrder $salesOrder): RedirectResponse
    {
        abort_unless($salesOrder->company_id === $context->company()?->id, 404);
        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'fulfilled', 'cancelled'])]]);
        if ($data['status'] === 'fulfilled') {
            return back()->with('error', 'Fulfil the order from a sales invoice. The pending quantity is updated when the invoice is posted.');
        }

        if ($data['status'] === 'cancelled') {
            app(SalesOrderService::class)->cancel($salesOrder);

            return back()->with('status', 'Sales order cancelled. Pending quantity is released.');
        }

        $salesOrder->update(['status' => $data['status']]);

        return back()->with('status', 'Sales order marked '.$data['status'].'.');
    }

    public function destroy(WorkspaceContext $context, SalesOrder $salesOrder): RedirectResponse
    {
        abort_unless($salesOrder->company_id === $context->company()?->id, 404);

        if ($salesOrder->status === 'fulfilled') {
            return back()->with('error', 'A fulfilled sales order cannot be deleted.');
        }

        $salesOrder->delete();

        return redirect()->route('books.tally.sales-orders.index')->with('status', 'Sales order deleted.');
    }

    public function print(WorkspaceContext $context, SalesOrder $salesOrder): View
    {
        abort_unless($salesOrder->company_id === $context->company()?->id, 404);
        $salesOrder->load(['customer', 'lines.product', 'company']);

        return view('tally::sales-orders.print', ['order' => $salesOrder]);
    }

    public function pdf(WorkspaceContext $context, SalesOrder $salesOrder): Response
    {
        abort_unless($salesOrder->company_id === $context->company()?->id, 404);
        $salesOrder->load(['customer', 'lines.product', 'company']);

        return Pdf::loadView('sales-orders.print', ['order' => $salesOrder])->download($salesOrder->number.'.pdf');
    }

    private function form(WorkspaceContext $context, PartyDirectory $parties, ?SalesOrder $order): View
    {
        $company = $context->company();

        if (! $company || ! $context->branch() || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', ['title' => 'Sales order', 'message' => 'Select a company, branch, and financial year.']);
        }

        $order?->load('lines');

        return view('tally::sales-orders.form', [
            'company' => $company,
            'year' => $context->financialYear(),
            'order' => $order,
            'customers' => $parties->options($company, InvoiceKind::Sales),
            'products' => $company->products()->where('is_active', true)->orderBy('name')->get(),
            'taxRates' => $company->taxRates()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
