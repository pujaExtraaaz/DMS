<?php

namespace App\Http\Controllers\Target;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Target\Models\PartyTarget;
use App\Domains\Target\Models\TargetAchievement;
use App\Domains\Target\Models\TargetPeriod;
use App\Domains\Target\Services\TargetService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TargetController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected TargetService $targetService,
    ) {}

    public function index(Request $request): View
    {
        $query = TargetPeriod::query()
            ->with(['brand', 'product', 'supplier'])
            ->withCount('targets')
            ->withSum('targets as total_target_amount', 'amount')
            ->when($request->filled('target_id'), fn ($q) => $q->where('id', $request->target_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('period_type'), fn ($q) => $q->where('period_type', $request->period_type));

        $this->applySearch($query, $request->input('search'), [
            'name',
            'period_type',
            'status',
            'target_category',
        ]);

        // Calculate filtered summary stats directly from database records matching the current filters
        $filteredPeriodIds = (clone $query)->pluck('target_periods.id');
        $summaryTotalTargets = $filteredPeriodIds->count();
        $summaryAssignedCount = PartyTarget::whereIn('target_period_id', $filteredPeriodIds)->count();
        $summaryTotalAmount = (float) PartyTarget::whereIn('target_period_id', $filteredPeriodIds)->sum('amount');
        $summaryTotalAchieved = (float) TargetAchievement::whereIn('target_period_id', $filteredPeriodIds)->sum('achieved_amount');

        [$sort, $direction] = $this->applySorting(
            $query,
            $request,
            [
                'starts_on' => 'starts_on',
                'name' => 'name',
                'period_type' => 'period_type',
                'type' => 'period_type',
                'ends_on' => 'ends_on',
                'status' => 'status',
                'targets_count' => 'targets_count',
                'total_target_amount' => 'total_target_amount',
            ],
            'name',
            'asc'
        );

        $periods = $query->paginate(15)->withQueryString();

        // Selected target details (if selected by user)
        $selectedTarget = null;
        if ($request->filled('target_id')) {
            $selectedTarget = TargetPeriod::with([
                'brand',
                'product',
                'supplier',
                'targets.customer',
                'targets.salesperson',
                'targets.brand',
                'targets.achievement',
                'achievements',
            ])
                ->withCount('targets')
                ->withSum('targets as total_target_amount', 'amount')
                ->find($request->target_id);
        }

        // All targets list for the dynamic search dropdown
        $allTargetsList = TargetPeriod::query()
            ->with(['brand', 'product', 'supplier'])
            ->withCount('targets')
            ->withSum('targets as total_target_amount', 'amount')
            ->orderBy('name')
            ->get();

        return view('targets.index', compact(
            'periods',
            'sort',
            'direction',
            'summaryTotalTargets',
            'summaryAssignedCount',
            'summaryTotalAmount',
            'summaryTotalAchieved',
            'selectedTarget',
            'allTargetsList'
        ));
    }

    public function createPeriod(): View
    {
        return view('targets.period-form', [
            'item' => new TargetPeriod([
                'period_type' => 'monthly',
                'starts_on' => now()->startOfMonth()->toDateString(),
                'ends_on' => now()->endOfMonth()->toDateString(),
            ]),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Customer::query()
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereIn('party_type', [Customer::PARTY_TYPE_SUNDRY_CREDITORS, 'supplier', Customer::PARTY_TYPE_BOTH])
                      ->orWhereNull('party_type');
                })
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'period_type' => 'required|in:monthly,quarterly,annual',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
            'target_category' => 'required|string|in:brand,product,supplier,brands,products,suppliers',
            'brand_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($request->input('target_category'), ['brand', 'brands'])),
                'exists:brands,id',
            ],
            'product_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($request->input('target_category'), ['product', 'products'])),
                'exists:products,id',
            ],
            'supplier_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($request->input('target_category'), ['supplier', 'suppliers'])),
                'exists:customers,id',
            ],
        ], [
            'target_category.required' => 'Please select a Target Category (Brands, Products, or Suppliers).',
            'target_category.in' => 'The selected target category is invalid.',
            'brand_id.required' => 'Please select a Brand for this target period.',
            'brand_id.exists' => 'The selected brand does not exist in master records.',
            'product_id.required' => 'Please select a Product for this target period.',
            'product_id.exists' => 'The selected product does not exist in master records.',
            'supplier_id.required' => 'Please select a Supplier for this target period.',
            'supplier_id.exists' => 'The selected supplier does not exist in master records.',
        ]);

        $period = $this->targetService->createPeriod($validated);

        return $this->flashSuccess('Target period created.', 'targets.periods.show', ['target_period' => $period]);
    }

    public function editPeriod(TargetPeriod $target_period): View
    {
        return view('targets.period-form', [
            'item' => $target_period,
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Customer::query()
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereIn('party_type', [Customer::PARTY_TYPE_SUNDRY_CREDITORS, 'supplier', Customer::PARTY_TYPE_BOTH])
                      ->orWhereNull('party_type');
                })
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function updatePeriod(Request $request, TargetPeriod $target_period): RedirectResponse
    {
        if ($target_period->status === 'closed') {
            return $this->flashError('Cannot edit a closed target period.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'period_type' => 'required|in:monthly,quarterly,annual',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
            'target_category' => 'required|string|in:brand,product,supplier,brands,products,suppliers',
            'brand_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($request->input('target_category'), ['brand', 'brands'])),
                'exists:brands,id',
            ],
            'product_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($request->input('target_category'), ['product', 'products'])),
                'exists:products,id',
            ],
            'supplier_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($request->input('target_category'), ['supplier', 'suppliers'])),
                'exists:customers,id',
            ],
        ], [
            'target_category.required' => 'Please select a Target Category (Brands, Products, or Suppliers).',
            'target_category.in' => 'The selected target category is invalid.',
            'brand_id.required' => 'Please select a Brand for this target period.',
            'brand_id.exists' => 'The selected brand does not exist in master records.',
            'product_id.required' => 'Please select a Product for this target period.',
            'product_id.exists' => 'The selected product does not exist in master records.',
            'supplier_id.required' => 'Please select a Supplier for this target period.',
            'supplier_id.exists' => 'The selected supplier does not exist in master records.',
        ]);

        $this->targetService->updatePeriod($target_period, $validated);

        return $this->flashSuccess('Target period updated.', 'targets.periods.show', ['target_period' => $target_period]);
    }

    public function showPeriod(TargetPeriod $target_period): View
    {
        $target_period->load([
            'brand',
            'product',
            'supplier',
            'targets.customer',
            'targets.salesperson',
            'targets.brand',
            'targets.achievement',
        ]);

        return view('targets.show', ['period' => $target_period]);
    }

    public function createTarget(TargetPeriod $target_period): View
    {
        return view('targets.target-form', [
            'period' => $target_period,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'salespeople' => User::orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeTarget(Request $request, TargetPeriod $target_period): RedirectResponse
    {
        if ($target_period->status === 'closed') {
            return $this->flashError('Cannot add targets to a closed period.');
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'salesperson_id' => 'nullable|exists:users,id',
            'brand_id' => 'nullable|exists:brands,id',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $validated['target_period_id'] = $target_period->id;
        $this->targetService->createTarget($validated);

        return $this->flashSuccess('Party target added.', 'targets.periods.show', ['target_period' => $target_period]);
    }

    public function recalculate(TargetPeriod $target_period): RedirectResponse
    {
        if ($target_period->status === 'closed') {
            return $this->flashError('Closed periods are final and cannot be recalculated.');
        }

        $this->targetService->calculatePeriod($target_period, false);

        return $this->flashSuccess('Target achievements recalculated.');
    }

    public function close(TargetPeriod $target_period): RedirectResponse
    {
        if ($target_period->status === 'closed') {
            return $this->flashError('Period is already closed.');
        }

        $this->targetService->calculatePeriod($target_period, true);

        return $this->flashSuccess('Target period closed and achievements finalized.');
    }
}