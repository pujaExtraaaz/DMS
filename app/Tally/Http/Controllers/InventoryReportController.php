<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Inventory\InventoryReports;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    public function summary(Request $request, WorkspaceContext $context, InventoryReports $reports): View
    {
        return $this->render($request, $context, $reports, 'summary', 'Stock summary');
    }

    public function ledger(Request $request, WorkspaceContext $context, InventoryReports $reports): View
    {
        return $this->render($request, $context, $reports, 'ledger', 'Stock ledger');
    }

    public function godowns(Request $request, WorkspaceContext $context, InventoryReports $reports): View
    {
        return $this->render($request, $context, $reports, 'godowns', 'Godown-wise stock');
    }

    public function movements(Request $request, WorkspaceContext $context, InventoryReports $reports): View
    {
        return $this->render($request, $context, $reports, 'movements', 'Stock movement report');
    }

    public function lowStock(Request $request, WorkspaceContext $context, InventoryReports $reports): View
    {
        return $this->render($request, $context, $reports, 'low', 'Low stock');
    }

    private function render(Request $request, WorkspaceContext $context, InventoryReports $reports, string $kind, string $title): View
    {
        if (! $context->company() || ! $context->branch() || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', [
                'title' => $title,
                'message' => 'Select a company, branch, and financial year before opening stock reports.',
            ]);
        }

        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        $filters = $this->filters($request, $company, $branch, $year);
        $productId = $filters['product_id'] !== '' ? (int) $filters['product_id'] : null;
        $godownId = $filters['godown_id'] !== '' ? (int) $filters['godown_id'] : null;
        $report = match ($kind) {
            'ledger' => $reports->ledger($company, $year, $filters['branch_id'], $productId, $godownId, $filters['from'], $filters['to']),
            'godowns' => $reports->godowns($company, $year, $filters['branch_id'], $filters['from'], $filters['to'], $productId, $godownId, $filters['q']),
            'movements' => $reports->movements($company, $year, $filters['branch_id'], $filters['from'], $filters['to'], $productId, $godownId, $filters['q']),
            'low' => $reports->lowStock($company, $year, $filters['branch_id'], $filters['to'], $productId, $filters['q']),
            default => $reports->summary($company, $year, $filters['branch_id'], $filters['from'], $filters['to'], $productId, $godownId, $filters['q']),
        };

        return view('tally::reports.stock', [
            'title' => $title,
            'kind' => $kind,
            'company' => $company,
            'year' => $year,
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
            'products' => $company->products()->orderBy('name')->get(),
            'godowns' => $company->godowns()->orderBy('name')->get(),
            'report' => $report,
            'filters' => $filters,
        ]);
    }

    /**
     * @return array{q: string, from: string, to: string, branch_id: ?int, product_id: string, godown_id: string}
     */
    private function filters(Request $request, Company $company, Branch $branch, FinancialYear $year): array
    {
        $start = $year->start_date->toDateString();
        $end = $year->end_date->toDateString();
        $from = $this->date($request->input('from')) ?? $start;
        $to = $this->date($request->input('to')) ?? $this->defaultTo($year);
        $from = min(max($from, $start), $end);
        $to = min(max($to, $start), $end);

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $branchId = (string) $request->input('branch_id', $branch->id);
        $resolved = $branchId === 'all'
            ? null
            : ($company->branches()->whereKey($branchId)->exists() ? (int) $branchId : $branch->id);
        $productId = (string) $request->input('product_id', '');
        $godownId = (string) $request->input('godown_id', '');

        if ($productId !== '' && ! $company->products()->whereKey($productId)->exists()) {
            $productId = '';
        }

        if ($godownId !== '' && ! $company->godowns()->whereKey($godownId)->exists()) {
            $godownId = '';
        }

        return [
            'q' => trim($request->string('q')->toString()),
            'from' => $from,
            'to' => $to,
            'branch_id' => $resolved,
            'product_id' => $productId,
            'godown_id' => $godownId,
        ];
    }

    private function defaultTo(FinancialYear $year): string
    {
        $today = now()->toDateString();
        $start = $year->start_date->toDateString();
        $end = $year->end_date->toDateString();

        return ($today >= $start && $today <= $end) ? $today : $end;
    }

    private function date(mixed $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
