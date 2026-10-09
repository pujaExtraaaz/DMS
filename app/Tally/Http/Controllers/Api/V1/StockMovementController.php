<?php

namespace Tally\Http\Controllers\Api\V1;

use Tally\Models\Company;
use Tally\Models\StockMovement;
use Tally\Support\Queries\DateRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends ApiController
{
    public function index(Request $request, Company $company): JsonResponse
    {
        $query = $company->stockMovements()->with('integrationReference')->orderByDesc('movement_date')->orderByDesc('id');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        DateRange::apply(
            $query,
            'movement_date',
            $request->filled('from') ? $request->date('from')->toDateString() : null,
            $request->filled('to') ? $request->date('to')->toDateString() : null,
        );

        return $this->page($query->paginate($this->perPage($request)), fn (StockMovement $movement) => $this->transform($movement));
    }

    public function show(Company $company, StockMovement $stockMovement): JsonResponse
    {
        $stockMovement->load('integrationReference');

        return $this->data($this->transform($stockMovement));
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(StockMovement $movement): array
    {
        return $this->withIntegration($movement, [
            'id' => $movement->id,
            'company_id' => $movement->company_id,
            'branch_id' => $movement->branch_id,
            'financial_year_id' => $movement->financial_year_id,
            'product_id' => $movement->product_id,
            'godown_id' => $movement->godown_id,
            'quantity' => (string) $movement->quantity,
            'rate' => (string) $movement->rate,
            'value' => (string) $movement->value,
            'movement_type' => $movement->movement_type->value,
            'movement_date' => $movement->movement_date?->toDateString(),
            'is_reversal' => $movement->is_reversal,
        ]);
    }
}
