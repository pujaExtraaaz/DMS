<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Inventory\StockMovementService;
use Tally\Inventory\StockMovementType;
use Tally\Models\StockMovement;
use Tally\Support\Queries\DateRange;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request, WorkspaceContext $context, StockMovementService $stock): View
    {
        $company = $context->company();

        if (! $company || ! $context->branch() || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', [
                'title' => 'Stock movements',
                'message' => 'Select a company, branch, and financial year before viewing stock movements.',
            ]);
        }

        $productId = $request->integer('product_id');
        $godownId = $request->integer('godown_id');
        $type = $request->string('movement_type')->toString();
        $from = $this->date($request->input('from'));
        $to = $this->date($request->input('to'));

        $movements = StockMovement::query()
            ->with(['product', 'godown', 'branch', 'reference'])
            ->where('company_id', $company->id)
            ->where('financial_year_id', $context->financialYearId())
            ->when($productId > 0, fn ($query) => $query->where('product_id', $productId))
            ->when($godownId > 0, fn ($query) => $query->where('godown_id', $godownId))
            ->when(in_array($type, array_column(StockMovementType::cases(), 'value'), true), fn ($query) => $query->where('movement_type', $type))
            ->tap(fn ($query) => DateRange::apply($query, 'movement_date', $from, $to))
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $balance = null;

        if ($productId > 0) {
            $product = $company->products()->whereKey($productId)->first();

            if ($product) {
                $godown = $godownId > 0 ? $company->godowns()->whereKey($godownId)->first() : null;
                $balance = $stock->quantity($product, $godown);
            }
        }

        return view('tally::stock.movements', [
            'company' => $company,
            'year' => $context->financialYear(),
            'movements' => $movements,
            'products' => $company->products()->orderBy('name')->get(),
            'godowns' => $company->godowns()->orderBy('name')->get(),
            'types' => StockMovementType::cases(),
            'balance' => $balance,
            'filters' => [
                'product_id' => $productId ?: '',
                'godown_id' => $godownId ?: '',
                'movement_type' => $type,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    private function date(mixed $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
