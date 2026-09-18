<?php

namespace App\Http\Controllers\Inventory;

use App\Domains\Inventory\Models\StockValuationSetting;
use App\Domains\Inventory\Services\StockValuationService;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockValuationSettingController extends Controller
{
    public function __construct(protected StockValuationService $valuationService) {}

    public function index(): View
    {
        $items = StockValuationSetting::query()
            ->with(['company', 'financialYear'])
            ->latest()
            ->paginate(20);

        return view('inventory.valuation.index', [
            'items' => $items,
            'valuation' => $this->valuationService->inventoryValue(null, auth()->user()?->company_id),
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'financialYears' => FinancialYear::orderByDesc('starts_on')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'financial_year_id' => 'nullable|exists:financial_years,id',
            'method' => 'required|in:fifo,lifo',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        if ($data['is_active']) {
            StockValuationSetting::query()
                ->where('company_id', $data['company_id'])
                ->when($data['financial_year_id'] ?? null, fn ($q, $fy) => $q->where('financial_year_id', $fy))
                ->update(['is_active' => false]);
        }

        StockValuationSetting::create($data);

        return $this->flashSuccess('Valuation method saved.', 'inventory.valuation.index');
    }
}
