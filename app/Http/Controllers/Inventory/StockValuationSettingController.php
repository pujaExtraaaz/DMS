<?php

namespace App\Http\Controllers\Inventory;

use App\Domains\Inventory\Models\StockValuationSetting;
use App\Domains\Inventory\Services\StockValuationService;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockValuationSettingController extends Controller
{
    use SortableAndSearchable;

    public function __construct(protected StockValuationService $valuationService) {}

    public function index(Request $request): View
    {
        $query = StockValuationSetting::query()
            ->with(['company', 'financialYear'])
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->method));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['method'],
            ['company' => ['name']]
        );

        $allowedSorts = [
            'method' => 'method',
            'is_active' => 'is_active',
            'created_at' => 'created_at',
            'company' => function ($q, $dir) {
                $q->join('companies', 'stock_valuation_settings.company_id', '=', 'companies.id')
                  ->orderBy('companies.name', $dir)
                  ->select('stock_valuation_settings.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'created_at',
            defaultDirection: 'desc'
        );

        $items = $query->paginate(20)->withQueryString();

        return view('inventory.valuation.index', [
            'items' => $items,
            'valuation' => $this->valuationService->inventoryValue(null, auth()->user()?->company_id),
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'financialYears' => FinancialYear::orderByDesc('starts_on')->get(),
            'filters' => $request->only(['search', 'company_id', 'method']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
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
