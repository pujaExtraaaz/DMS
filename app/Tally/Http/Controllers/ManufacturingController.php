<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Manufacturing\ManufacturingReport;
use Tally\Manufacturing\ManufacturingService;
use Tally\Models\ManufacturingOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ManufacturingController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', [
                'title' => 'Manufacturing',
                'message' => 'Select a company, branch, and financial year before manufacturing.',
            ]);
        }

        $orders = ManufacturingOrder::query()
            ->with(['bill.finishedProduct', 'sourceGodown', 'destinationGodown'])
            ->where('company_id', $company->id)
            ->where('financial_year_id', $context->financialYear()->id)
            ->orderByDesc('manufactured_on')
            ->orderByDesc('id')
            ->paginate(25);

        return view('tally::manufacturing.orders.index', [
            'company' => $company,
            'orders' => $orders,
        ]);
    }

    public function create(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company || ! $context->branch() || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', [
                'title' => 'Manufacturing journal',
                'message' => 'Select a company, branch, and financial year before manufacturing.',
            ]);
        }

        return view('tally::manufacturing.orders.create', [
            'company' => $company,
            'year' => $context->financialYear(),
            'boms' => $company->billsOfMaterials()->with(['lines.product', 'finishedProduct'])->where('is_active', true)->orderBy('name')->get(),
            'godowns' => $company->godowns()->where('is_active', true)->orderBy('name')->get(),
            'ledgers' => $company->ledgers()->where('is_active', true)->orderBy('name')->get(),
            'tracked' => $company->products()->where('track_batch', true)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, ManufacturingService $service): RedirectResponse
    {
        $order = $service->produce(
            $context->company(),
            $context->branch(),
            $context->financialYear(),
            $request->user(),
            $request->all(),
        );

        return redirect()->route('books.tally.manufacturing.show', $order)->with('status', 'Manufacturing posted. Stock and the cost journal are updated.');
    }

    public function cancel(ManufacturingOrder $manufacturing, ManufacturingService $service): RedirectResponse
    {
        $service->cancel($manufacturing);

        return redirect()->route('books.tally.manufacturing.show', $manufacturing)->with('status', 'Manufacturing cancelled. Stock and the cost journal were reversed.');
    }

    public function show(ManufacturingOrder $manufacturing): View
    {
        $manufacturing->load(['bill.finishedProduct', 'bill.lines.product', 'sourceGodown', 'destinationGodown', 'voucher', 'movements.product', 'movements.batch']);

        return view('tally::manufacturing.orders.show', ['order' => $manufacturing]);
    }

    public function production(WorkspaceContext $context, ManufacturingReport $reports): View
    {
        return $this->report($context, 'Production', $reports->production($context->company(), $context->financialYear(), $context->branch()?->id));
    }

    public function consumption(WorkspaceContext $context, ManufacturingReport $reports): View
    {
        return $this->report($context, 'Raw material consumption', $reports->consumption($context->company(), $context->financialYear(), $context->branch()?->id));
    }

    /**
     * @param  array{rows: list<array<string, string>>}  $report
     */
    private function report(WorkspaceContext $context, string $title, array $report): View
    {
        if (! $context->company() || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', [
                'title' => $title,
                'message' => 'Select a company and financial year before opening this report.',
            ]);
        }

        return view('tally::manufacturing.report', [
            'title' => $title,
            'company' => $context->company(),
            'report' => $report,
        ]);
    }
}
