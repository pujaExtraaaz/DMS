<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Inventory\AdvancedInventoryReport;
use Tally\Inventory\InventoryReconciliation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdvancedInventoryController extends Controller
{
    public function valuation(WorkspaceContext $context, AdvancedInventoryReport $reports, InventoryReconciliation $reconciliation): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return $this->page($context, 'Stock valuation', ['rows' => []]);
        }

        return $this->page($context, 'Stock valuation', $reports->valuation($company, $year), [
            'reconciliation' => $reconciliation->compare($company, $year),
        ]);
    }

    public function expiry(Request $request, WorkspaceContext $context, AdvancedInventoryReport $reports): View
    {
        $days = max(1, min(365, (int) $request->query('days', 30)));
        $asOf = $context->financialYear()?->end_date->toDateString() ?? date('Y-m-d');

        return $this->page($context, 'Expiry', $reports->expiry($context->company(), $asOf, $days), ['days' => $days]);
    }

    public function limits(WorkspaceContext $context, AdvancedInventoryReport $reports): View
    {
        return $this->page($context, 'Reorder and stock limits', $reports->limits($context->company()));
    }

    public function history(Request $request, WorkspaceContext $context, AdvancedInventoryReport $reports): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Batch and serial history',
                'message' => 'Select a company before opening stock history.',
            ]);
        }

        $productId = (int) $request->query('product_id', 0) ?: null;

        return view('tally::inventory.history', [
            'title' => 'Batch and serial history',
            'company' => $company,
            'products' => $company->products()->orderBy('name')->get(),
            'batches' => $reports->batches($company),
            'serials' => $reports->serials($company),
            'report' => $reports->history(
                $company,
                $productId,
                (int) $request->query('batch_id', 0) ?: null,
                (int) $request->query('serial_id', 0) ?: null,
            ),
            'filters' => $request->only(['product_id', 'batch_id', 'serial_id']),
        ]);
    }

    /**
     * @param  array{rows: list<array<string, string>>}  $report
     * @param  array<string, mixed>  $extra
     */
    private function page(WorkspaceContext $context, string $title, array $report, array $extra = []): View
    {
        if (! $context->company() || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', [
                'title' => $title,
                'message' => 'Select a company and financial year before opening this report.',
            ]);
        }

        return view('tally::inventory.report', [
            'title' => $title,
            'company' => $context->company(),
            'report' => $report,
        ] + $extra);
    }
}
