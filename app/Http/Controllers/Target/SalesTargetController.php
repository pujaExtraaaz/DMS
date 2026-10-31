<?php

namespace App\Http\Controllers\Target;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Target\Models\SalesTarget;
use App\Domains\Target\Models\SalesTargetAchievement;
use App\Domains\Target\Models\SalesTargetAlert;
use App\Domains\Target\Models\SalesTargetAudit;
use App\Domains\Target\Models\SalesTargetItem;
use App\Domains\Target\Models\SalesTeam;
use App\Domains\Target\Services\SalesTargetCalculationService;
use App\Exports\SalesTargetsExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Traits\SortableAndSearchable;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesTargetController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected SalesTargetCalculationService $calculationService
    ) {}

    /**
     * Target Dashboard
     */
    public function dashboard(Request $request): View
    {
        $query = SalesTarget::with([
            'salesperson', 'salesTeam', 'branch', 'brand', 'product', 'category', 'achievement', 'customer'
        ]);

        // Filters
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('financial_year_id')) {
            $query->where('financial_year_id', $request->financial_year_id);
        }
        if ($request->filled('period_type')) {
            $query->where('period_type', $request->period_type);
        }
        if ($request->filled('owner_type')) {
            $query->where('owner_type', $request->owner_type);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('salesperson_id')) {
            $query->where('salesperson_id', $request->salesperson_id);
        }
        if ($request->filled('sales_team_id')) {
            $query->where('sales_team_id', $request->sales_team_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('target_type')) {
            $query->where('target_type', $request->target_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default active
            $query->whereIn('status', ['active', 'completed']);
        }

        // Period dates filter
        if ($request->filled('date_from')) {
            $query->where('starts_on', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('ends_on', '<=', $request->date_to);
        }

        $allTargets = $query->get();

        // Target progress status breakdown
        $notStartedCount = $allTargets->filter(fn ($t) => $t->progress_status === 'Not Started')->count();
        $inProgressCount = $allTargets->filter(fn ($t) => $t->progress_status === 'In Progress')->count();
        $achievedCount = $allTargets->filter(fn ($t) => $t->progress_status === 'Achieved')->count();
        $periodExpiredCount = $allTargets->filter(fn ($t) => $t->progress_status === 'Period Expired')->count();

        // Filter by calculated progress status if requested
        if ($request->filled('progress_status')) {
            $allTargets = $allTargets->filter(fn ($t) => $t->progress_status === $request->progress_status);
        }

        // Calculate KPI totals (respecting all active filters)
        $totalAssigned = (float) $allTargets->sum('target_amount');
        $actualAchieved = (float) $allTargets->sum(fn ($t) => $t->achievement?->achieved_amount ?? 0);
        $pendingTarget = max(0, $totalAssigned - $actualAchieved);
        $totalShortfall = $pendingTarget;
        $achievementPercent = $totalAssigned > 0 ? round(($actualAchieved / $totalAssigned) * 100, 1) : 0;

        $totalTargetQty = (float) $allTargets->sum('target_quantity');
        $totalAchievedQty = (float) $allTargets->sum(fn ($t) => $t->achievement?->achieved_quantity ?? 0);

        $activeCount = $allTargets->where('status', 'active')->count();
        $atRiskCount = $allTargets->filter(fn ($t) => ($t->achievement?->achievement_percent ?? 0) < 50 && $t->status === 'active')->count();

        // MTD & YTD figures
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        $mtdInvoices = Invoice::whereNotIn('status', ['cancelled', 'draft', 'void'])
            ->whereBetween('invoice_date', [$startOfMonth, $endOfMonth])
            ->sum('grand_total');

        $currentFy = FinancialYear::where('is_current', true)->first();
        $fyStart = $currentFy?->starts_on?->toDateString() ?? $now->copy()->startOfYear()->toDateString();
        $fyEnd = $currentFy?->ends_on?->toDateString() ?? $now->copy()->endOfYear()->toDateString();

        $ytdInvoices = Invoice::whereNotIn('status', ['cancelled', 'draft', 'void'])
            ->whereBetween('invoice_date', [$fyStart, $fyEnd])
            ->sum('grand_total');

        // Brand-wise performance breakdown
        $brands = Brand::orderBy('name')->get();
        $brandPerformance = [];
        foreach ($brands as $brand) {
            $brandTargets = $allTargets->filter(fn ($t) => (int) $t->brand_id === (int) $brand->id);
            if ($brandTargets->isNotEmpty()) {
                $bTarget = (float) $brandTargets->sum('target_amount');
                $bAch = (float) $brandTargets->sum(fn ($t) => $t->achievement?->achieved_amount ?? 0);
                $brandPerformance[] = [
                    'brand' => $brand->name,
                    'target' => $bTarget,
                    'achieved' => $bAch,
                    'percent' => $bTarget > 0 ? round(($bAch / $bTarget) * 100, 1) : 0,
                ];
            }
        }

        // Salesman Rankings
        $salesmanPerformance = [];
        $salespeople = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['salesperson', 'sales_person', 'sales-executive']))
            ->orWhereHas('employee', fn ($q) => $q->where('is_salesperson', true))
            ->orderBy('name')
            ->get();

        if ($salespeople->isEmpty()) {
            $salespeople = User::orderBy('name')->take(20)->get();
        }

        foreach ($salespeople as $sp) {
            $spTargets = $allTargets->filter(fn ($t) => (int) $t->salesperson_id === (int) $sp->id);
            if ($spTargets->isNotEmpty()) {
                $spTarget = (float) $spTargets->sum('target_amount');
                $spAch = (float) $spTargets->sum(fn ($t) => $t->achievement?->achieved_amount ?? 0);
                $salesmanPerformance[] = [
                    'salesperson' => $sp->name,
                    'target' => $spTarget,
                    'achieved' => $spAch,
                    'pending' => max(0, $spTarget - $spAch),
                    'percent' => $spTarget > 0 ? round(($spAch / $spTarget) * 100, 1) : 0,
                ];
            }
        }
        usort($salesmanPerformance, fn ($a, $b) => $b['percent'] <=> $a['percent']);

        // Monthly Trend
        $monthlyTrend = [];
        for ($m = 1; $m <= 12; $m++) {
            $date = Carbon::create($now->year, $m, 1);
            $mStart = $date->copy()->startOfMonth()->toDateString();
            $mEnd = $date->copy()->endOfMonth()->toDateString();

            $monthInvoiced = (float) Invoice::whereNotIn('status', ['cancelled', 'draft', 'void'])
                ->whereBetween('invoice_date', [$mStart, $mEnd])
                ->sum('grand_total');

            $monthlyTrend[] = [
                'month' => $date->format('M'),
                'amount' => $monthInvoiced,
            ];
        }

        // Master lists for filter dropdowns
        $companies = Company::orderBy('name')->get();
        $teams = SalesTeam::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $financialYears = FinancialYear::orderByDesc('starts_on')->get();
        $categories = Category::orderBy('name')->get();
        $products = Product::orderBy('name')->take(100)->get();
        $parties = Customer::orderBy('name')->get(['id', 'name', 'code', 'trade_name']);

        // Party-wise targets performance list
        $partyTargets = $allTargets->filter(fn ($t) => $t->owner_type === 'party');

        // Recent alerts
        $recentAlerts = SalesTargetAlert::with('target.salesperson')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('sales-targets.dashboard', compact(
            'totalAssigned', 'actualAchieved', 'pendingTarget', 'totalShortfall', 'achievementPercent',
            'totalTargetQty', 'totalAchievedQty', 'activeCount', 'achievedCount', 'atRiskCount',
            'notStartedCount', 'inProgressCount', 'periodExpiredCount',
            'mtdInvoices', 'ytdInvoices', 'brandPerformance', 'salesmanPerformance',
            'monthlyTrend', 'brands', 'salespeople', 'teams', 'branches', 'companies',
            'financialYears', 'categories', 'products', 'recentAlerts', 'allTargets',
            'partyTargets', 'parties'
        ));
    }

    /**
     * Target List
     */
    public function index(Request $request): View
    {
        $query = SalesTarget::with([
            'salesperson', 'salesTeam', 'branch', 'brand', 'product', 'category', 'achievement', 'items', 'customer'
        ]);

        // Search
        $this->applySearch($query, $request->input('search'), [
            'name', 'target_type', 'owner_type', 'status', 'notes'
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->orWhereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('progress_status')) {
            $query->progressStatus($request->progress_status);
        }
        if ($request->filled('target_type')) {
            $query->where('target_type', $request->target_type);
        }
        if ($request->filled('owner_type')) {
            $query->where('owner_type', $request->owner_type);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('salesperson_id')) {
            $query->where('salesperson_id', $request->salesperson_id);
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('financial_year_id')) {
            $query->where('financial_year_id', $request->financial_year_id);
        }

        [$sort, $direction] = $this->applySorting($query, $request, [
            'name' => 'name',
            'starts_on' => 'starts_on',
            'ends_on' => 'ends_on',
            'target_amount' => 'target_amount',
            'status' => 'status',
            'created_at' => 'created_at',
        ], 'created_at', 'desc');

        $targets = $query->paginate(15)->withQueryString();

        // Summary counts
        $totalTargetsCount = SalesTarget::count();
        $activeTargetsCount = SalesTarget::where('status', 'active')->count();
        $pendingApprovalCount = SalesTarget::where('status', 'pending_approval')->count();
        $completedTargetsCount = SalesTarget::where('status', 'completed')->count();

        // Progress status breakdown counts
        $allTargetsForCounts = SalesTarget::with('achievement')->get();
        $notStartedCount = $allTargetsForCounts->filter(fn ($t) => $t->progress_status === 'Not Started')->count();
        $inProgressCount = $allTargetsForCounts->filter(fn ($t) => $t->progress_status === 'In Progress')->count();
        $achievedCount = $allTargetsForCounts->filter(fn ($t) => $t->progress_status === 'Achieved')->count();
        $periodExpiredCount = $allTargetsForCounts->filter(fn ($t) => $t->progress_status === 'Period Expired')->count();

        $companies = Company::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $salespeople = User::orderBy('name')->get();
        $teams = SalesTeam::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $financialYears = FinancialYear::orderByDesc('starts_on')->get();
        $parties = Customer::orderBy('name')->get(['id', 'name', 'code', 'trade_name']);

        return view('sales-targets.index', compact(
            'targets', 'sort', 'direction', 'totalTargetsCount', 'activeTargetsCount',
            'pendingApprovalCount', 'completedTargetsCount',
            'notStartedCount', 'inProgressCount', 'achievedCount', 'periodExpiredCount',
            'brands', 'salespeople', 'teams', 'branches', 'companies', 'financialYears', 'parties'
        ));
    }

    /**
     * Create Target Form
     */
    public function create(Request $request): View
    {
        $salespeople = User::orderBy('name')->get();
        $teams = SalesTeam::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $companies = Company::orderBy('name')->get();
        $financialYears = FinancialYear::orderByDesc('starts_on')->get();
        $currentFy = FinancialYear::where('is_current', true)->first();

        $brands = Brand::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $subCategories = SubCategory::orderBy('name')->get();
        $parties = Customer::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'trade_name']);

        // Optional copy from existing target
        $copyTarget = null;
        if ($request->filled('copy_from')) {
            $copyTarget = SalesTarget::with('items')->find($request->copy_from);
        }

        return view('sales-targets.form', compact(
            'salespeople', 'teams', 'branches', 'companies', 'financialYears',
            'currentFy', 'brands', 'products', 'categories', 'subCategories', 'copyTarget', 'parties'
        ));
    }

    /**
     * Store New Target
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_type' => 'required|in:value,product,sku,brand,category,combined',
            'owner_type' => 'required|in:individual,team,branch,party',
            'customer_id' => 'required_if:owner_type,party|nullable|exists:customers,id',
            'salesperson_id' => 'required_if:owner_type,individual|nullable|exists:users,id',
            'sales_team_id' => 'required_if:owner_type,team|nullable|exists:sales_teams,id',
            'branch_id' => 'required_if:owner_type,branch|nullable|exists:branches,id',
            'company_id' => 'nullable|exists:companies,id',
            'financial_year_id' => 'nullable|exists:financial_years,id',
            'period_type' => 'required|in:monthly,quarterly,half_yearly,annual,custom',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
            'target_amount' => 'required|numeric|gt:0',
            'target_quantity' => 'nullable|numeric|min:0',
            'brand_id' => 'nullable|exists:brands,id',
            'product_id' => 'nullable|exists:products,id',
            'sku' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'status' => 'required|in:draft,pending_approval,active,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
            'items' => 'nullable|array',
            'items.*.target_type' => 'required_with:items|string',
            'items.*.brand_id' => 'nullable|exists:brands,id',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.sku' => 'nullable|string',
            'items.*.category_id' => 'nullable|exists:categories,id',
            'items.*.sub_category_id' => 'nullable|exists:sub_categories,id',
            'items.*.target_amount' => 'nullable|numeric|min:0',
            'items.*.target_quantity' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        // Prevent accidental duplicates
        $duplicate = SalesTarget::where('owner_type', $validated['owner_type'])
            ->when($validated['owner_type'] === 'individual', fn ($q) => $q->where('salesperson_id', $validated['salesperson_id']))
            ->when($validated['owner_type'] === 'team', fn ($q) => $q->where('sales_team_id', $validated['sales_team_id']))
            ->when($validated['owner_type'] === 'branch', fn ($q) => $q->where('branch_id', $validated['branch_id']))
            ->when($validated['owner_type'] === 'party', fn ($q) => $q->where('customer_id', $validated['customer_id']))
            ->where('target_type', $validated['target_type'])
            ->whereDate('starts_on', $validated['starts_on'])
            ->whereDate('ends_on', $validated['ends_on'])
            ->whereIn('status', ['active', 'pending_approval'])
            ->exists();

        if ($duplicate && !$request->boolean('allow_duplicate')) {
            return back()->withInput()->with('error', 'A matching target already exists for this owner and period. Check the existing target or confirm if a separate target is intentionally intended.');
        }

        $target = DB::transaction(function () use ($validated, $request) {
            $validated['created_by'] = auth()->id();
            if ($validated['status'] === 'active') {
                $validated['approved_by'] = auth()->id();
                $validated['approved_at'] = now();
            }

            $target = SalesTarget::create($validated);

            // Store criteria line items if provided
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $itemData) {
                    if (!empty($itemData['target_amount']) || !empty($itemData['target_quantity']) || !empty($itemData['product_id']) || !empty($itemData['brand_id'])) {
                        $target->items()->create([
                            'target_type' => $itemData['target_type'] ?? 'product',
                            'brand_id' => $itemData['brand_id'] ?? null,
                            'product_id' => $itemData['product_id'] ?? null,
                            'sku' => $itemData['sku'] ?? null,
                            'category_id' => $itemData['category_id'] ?? null,
                            'sub_category_id' => $itemData['sub_category_id'] ?? null,
                            'target_amount' => (float) ($itemData['target_amount'] ?? 0),
                            'target_quantity' => (float) ($itemData['target_quantity'] ?? 0),
                            'notes' => $itemData['notes'] ?? null,
                        ]);
                    }
                }
            }

            // Create Audit Log
            SalesTargetAudit::create([
                'sales_target_id' => $target->id,
                'user_id' => auth()->id(),
                'action' => 'created',
                'reason' => 'Target initialized',
                'new_values' => $target->toArray(),
            ]);

            // Calculate initial achievement from real invoice database
            $this->calculationService->calculateTarget($target);

            return $target;
        });

        return redirect()->route('sales-targets.show', $target)
            ->with('status', 'Target created successfully and synced with sales invoices.');
    }

    /**
     * Show Target Details & Drilldown
     */
    public function show(SalesTarget $salesTarget): View
    {
        $salesTarget->load([
            'salesperson', 'salesTeam.members', 'branch', 'company', 'financialYear',
            'brand', 'product', 'category', 'subCategory', 'creator', 'approver',
            'items.brand', 'items.product', 'items.category', 'items.subCategory',
            'achievement', 'audits.user', 'alerts', 'customer'
        ]);

        // Drill-down: Fetch contributing invoices directly from database
        $contributingInvoicesQuery = Invoice::with(['customer', 'salesperson', 'items.product'])
            ->whereNotIn('status', ['cancelled', 'draft', 'void'])
            ->whereBetween('invoice_date', [$salesTarget->starts_on->toDateString(), $salesTarget->ends_on->toDateString()]);

        if ($salesTarget->owner_type === 'party' || $salesTarget->customer_id) {
            $contributingInvoicesQuery->where('customer_id', $salesTarget->customer_id);
        } else {
            $salespersonIds = $this->calculationService->resolveSalespersonIds($salesTarget);
            if ($salespersonIds->isNotEmpty()) {
                $contributingInvoicesQuery->whereIn('salesperson_id', $salespersonIds);
            }
        }

        $invoices = $contributingInvoicesQuery->orderByDesc('invoice_date')->paginate(10);

        return view('sales-targets.show', compact('salesTarget', 'invoices'));
    }

    /**
     * Edit Target Form
     */
    public function edit(SalesTarget $salesTarget): View
    {
        $salesTarget->load(['items', 'customer']);
        $salespeople = User::orderBy('name')->get();
        $teams = SalesTeam::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $companies = Company::orderBy('name')->get();
        $financialYears = FinancialYear::orderByDesc('starts_on')->get();
        $currentFy = FinancialYear::where('is_current', true)->first();

        $brands = Brand::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $subCategories = SubCategory::orderBy('name')->get();
        $parties = Customer::orderBy('name')->get(['id', 'name', 'code', 'trade_name']);

        return view('sales-targets.form', compact(
            'salesTarget', 'salespeople', 'teams', 'branches', 'companies',
            'financialYears', 'currentFy', 'brands', 'products', 'categories', 'subCategories', 'parties'
        ));
    }

    /**
     * Update / Revise Target
     */
    public function update(Request $request, SalesTarget $salesTarget): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_type' => 'required|in:value,product,sku,brand,category,combined',
            'owner_type' => 'required|in:individual,team,branch,party',
            'customer_id' => 'required_if:owner_type,party|nullable|exists:customers,id',
            'salesperson_id' => 'required_if:owner_type,individual|nullable|exists:users,id',
            'sales_team_id' => 'required_if:owner_type,team|nullable|exists:sales_teams,id',
            'branch_id' => 'required_if:owner_type,branch|nullable|exists:branches,id',
            'company_id' => 'nullable|exists:companies,id',
            'financial_year_id' => 'nullable|exists:financial_years,id',
            'period_type' => 'required|in:monthly,quarterly,half_yearly,annual,custom',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
            'target_amount' => 'required|numeric|gt:0',
            'target_quantity' => 'nullable|numeric|min:0',
            'brand_id' => 'nullable|exists:brands,id',
            'product_id' => 'nullable|exists:products,id',
            'sku' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'status' => 'required|in:draft,pending_approval,active,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
            'revision_reason' => 'required|string|min:5|max:500',
            'items' => 'nullable|array',
        ]);

        DB::transaction(function () use ($salesTarget, $validated, $request) {
            $oldValues = $salesTarget->toArray();

            // Increment revision number and record reason
            $validated['revision_number'] = $salesTarget->revision_number + 1;
            $validated['revised_from_id'] = $salesTarget->id;

            $salesTarget->update($validated);

            // Re-sync items if provided
            if ($request->has('items') && is_array($request->items)) {
                $salesTarget->items()->delete();
                foreach ($request->items as $itemData) {
                    if (!empty($itemData['target_amount']) || !empty($itemData['target_quantity']) || !empty($itemData['product_id']) || !empty($itemData['brand_id'])) {
                        $salesTarget->items()->create([
                            'target_type' => $itemData['target_type'] ?? 'product',
                            'brand_id' => $itemData['brand_id'] ?? null,
                            'product_id' => $itemData['product_id'] ?? null,
                            'sku' => $itemData['sku'] ?? null,
                            'category_id' => $itemData['category_id'] ?? null,
                            'sub_category_id' => $itemData['sub_category_id'] ?? null,
                            'target_amount' => (float) ($itemData['target_amount'] ?? 0),
                            'target_quantity' => (float) ($itemData['target_quantity'] ?? 0),
                            'notes' => $itemData['notes'] ?? null,
                        ]);
                    }
                }
            }

            // Audit
            SalesTargetAudit::create([
                'sales_target_id' => $salesTarget->id,
                'user_id' => auth()->id(),
                'action' => 'revised',
                'reason' => $validated['revision_reason'],
                'old_values' => $oldValues,
                'new_values' => $salesTarget->fresh()->toArray(),
            ]);

            // Recalculate
            $this->calculationService->calculateTarget($salesTarget);
        });

        return redirect()->route('sales-targets.show', $salesTarget)
            ->with('status', 'Target revised and saved successfully with full audit trail.');
    }

    /**
     * Approve Target
     */
    public function approve(SalesTarget $salesTarget): RedirectResponse
    {
        $salesTarget->update([
            'status' => 'active',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        SalesTargetAudit::create([
            'sales_target_id' => $salesTarget->id,
            'user_id' => auth()->id(),
            'action' => 'approved',
            'reason' => 'Target approved by authorized manager',
            'new_values' => ['status' => 'active', 'approved_by' => auth()->id(), 'approved_at' => now()],
        ]);

        $this->calculationService->calculateTarget($salesTarget);

        return back()->with('status', "Target '{$salesTarget->name}' has been approved and activated.");
    }

    /**
     * Cancel Target
     */
    public function cancel(Request $request, SalesTarget $salesTarget): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|min:3|max:500']);

        $salesTarget->update(['status' => 'cancelled']);

        SalesTargetAudit::create([
            'sales_target_id' => $salesTarget->id,
            'user_id' => auth()->id(),
            'action' => 'cancelled',
            'reason' => $request->input('reason'),
            'new_values' => ['status' => 'cancelled'],
        ]);

        return back()->with('status', "Target '{$salesTarget->name}' has been cancelled.");
    }

    /**
     * Carry Forward Unachieved Balance
     */
    public function carryForward(Request $request, SalesTarget $salesTarget): RedirectResponse
    {
        $request->validate([
            'next_starts_on' => 'required|date',
            'next_ends_on' => 'required|date|after_or_equal:next_starts_on',
            'authorization_reason' => 'required|string|min:5|max:500',
        ]);

        $achievement = $salesTarget->achievement;
        $unachievedAmount = $achievement ? (float) $achievement->pending_amount : (float) $salesTarget->target_amount;

        if ($unachievedAmount <= 0) {
            return back()->with('error', 'This target has already been fully achieved. No balance to carry forward.');
        }

        $newTarget = DB::transaction(function () use ($salesTarget, $unachievedAmount, $request) {
            $newTarget = SalesTarget::create([
                'name' => $salesTarget->name . ' (Carried Forward)',
                'target_type' => $salesTarget->target_type,
                'owner_type' => $salesTarget->owner_type,
                'customer_id' => $salesTarget->customer_id,
                'salesperson_id' => $salesTarget->salesperson_id,
                'sales_team_id' => $salesTarget->sales_team_id,
                'branch_id' => $salesTarget->branch_id,
                'company_id' => $salesTarget->company_id,
                'financial_year_id' => $salesTarget->financial_year_id,
                'period_type' => $salesTarget->period_type,
                'starts_on' => $request->next_starts_on,
                'ends_on' => $request->next_ends_on,
                'target_amount' => $unachievedAmount,
                'target_quantity' => $salesTarget->achievement?->pending_quantity ?? 0,
                'brand_id' => $salesTarget->brand_id,
                'product_id' => $salesTarget->product_id,
                'sku' => $salesTarget->sku,
                'category_id' => $salesTarget->category_id,
                'sub_category_id' => $salesTarget->sub_category_id,
                'status' => 'active',
                'created_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'carried_forward_from_id' => $salesTarget->id,
                'carried_forward_amount' => $unachievedAmount,
                'notes' => 'Carried forward from Target #' . $salesTarget->id . ': ' . $request->authorization_reason,
            ]);

            // Audit on old target
            SalesTargetAudit::create([
                'sales_target_id' => $salesTarget->id,
                'user_id' => auth()->id(),
                'action' => 'carry_forward',
                'reason' => 'Unachieved balance ₹ ' . number_format($unachievedAmount, 2) . ' carried forward to Target #' . $newTarget->id . '. Reason: ' . $request->authorization_reason,
            ]);

            // Audit on new target
            SalesTargetAudit::create([
                'sales_target_id' => $newTarget->id,
                'user_id' => auth()->id(),
                'action' => 'created',
                'reason' => 'Created via authorized carry-forward from Target #' . $salesTarget->id,
            ]);

            $this->calculationService->calculateTarget($newTarget);

            return $newTarget;
        });

        return redirect()->route('sales-targets.show', $newTarget)
            ->with('status', 'Target carried forward successfully as new active target.');
    }

    /**
     * Salesman Performance
     */
    public function salesmanPerformance(Request $request): View
    {
        $salespeople = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['salesperson', 'sales_person', 'sales-executive']))
            ->orWhereHas('employee', fn ($q) => $q->where('is_salesperson', true))
            ->orderBy('name')
            ->get();

        if ($salespeople->isEmpty()) {
            $salespeople = User::orderBy('name')->take(30)->get();
        }

        $performanceData = [];
        foreach ($salespeople as $sp) {
            $targets = SalesTarget::with('achievement')
                ->where('salesperson_id', $sp->id)
                ->when($request->filled('financial_year_id'), fn ($q) => $q->where('financial_year_id', $request->financial_year_id))
                ->when($request->filled('date_from'), fn ($q) => $q->where('starts_on', '>=', $request->date_from))
                ->when($request->filled('date_to'), fn ($q) => $q->where('ends_on', '<=', $request->date_to))
                ->whereIn('status', ['active', 'completed'])
                ->get();

            $totalTgt = (float) $targets->sum('target_amount');
            $totalAch = (float) $targets->sum(fn ($t) => $t->achievement?->achieved_amount ?? 0);
            $pending = max(0, $totalTgt - $totalAch);
            $percent = $totalTgt > 0 ? round(($totalAch / $totalTgt) * 100, 1) : 0;

            $performanceData[] = [
                'user' => $sp,
                'targets_count' => $targets->count(),
                'target_amount' => $totalTgt,
                'achieved_amount' => $totalAch,
                'pending_amount' => $pending,
                'achievement_percent' => $percent,
                'achieved_count' => $targets->filter(fn ($t) => $t->achievement?->is_achieved)->count(),
            ];
        }

        usort($performanceData, fn ($a, $b) => $b['achievement_percent'] <=> $a['achievement_percent']);

        $financialYears = FinancialYear::orderByDesc('starts_on')->get();

        return view('sales-targets.salesman-performance', compact('performanceData', 'financialYears'));
    }

    /**
     * Team & Branch Performance
     */
    public function teamBranchPerformance(Request $request): View
    {
        $teams = SalesTeam::with(['members', 'leader'])->where('is_active', true)->get();
        $branches = Branch::orderBy('name')->get();

        $teamData = [];
        foreach ($teams as $team) {
            $targets = SalesTarget::with('achievement')
                ->where('owner_type', 'team')
                ->where('sales_team_id', $team->id)
                ->when($request->filled('financial_year_id'), fn ($q) => $q->where('financial_year_id', $request->financial_year_id))
                ->get();

            $tgtAmt = (float) $targets->sum('target_amount');
            $achAmt = (float) $targets->sum(fn ($t) => $t->achievement?->achieved_amount ?? 0);
            $teamData[] = [
                'team' => $team,
                'target_amount' => $tgtAmt,
                'achieved_amount' => $achAmt,
                'pending_amount' => max(0, $tgtAmt - $achAmt),
                'percent' => $tgtAmt > 0 ? round(($achAmt / $tgtAmt) * 100, 1) : 0,
                'members_count' => $team->members->count(),
            ];
        }

        $branchData = [];
        foreach ($branches as $branch) {
            $targets = SalesTarget::with('achievement')
                ->where('owner_type', 'branch')
                ->where('branch_id', $branch->id)
                ->when($request->filled('financial_year_id'), fn ($q) => $q->where('financial_year_id', $request->financial_year_id))
                ->get();

            $tgtAmt = (float) $targets->sum('target_amount');
            $achAmt = (float) $targets->sum(fn ($t) => $t->achievement?->achieved_amount ?? 0);
            $branchData[] = [
                'branch' => $branch,
                'target_amount' => $tgtAmt,
                'achieved_amount' => $achAmt,
                'pending_amount' => max(0, $tgtAmt - $achAmt),
                'percent' => $tgtAmt > 0 ? round(($achAmt / $tgtAmt) * 100, 1) : 0,
            ];
        }

        $financialYears = FinancialYear::orderByDesc('starts_on')->get();

        return view('sales-targets.team-branch-performance', compact('teamData', 'branchData', 'financialYears'));
    }

    /**
     * Target Alerts
     */
    public function alerts(Request $request): View
    {
        $query = SalesTargetAlert::with(['target.salesperson', 'user'])
            ->when($request->filled('alert_type'), fn ($q) => $q->where('alert_type', $request->alert_type))
            ->when($request->filled('is_read'), fn ($q) => $q->where('is_read', $request->boolean('is_read')))
            ->orderByDesc('created_at');

        $alerts = $query->paginate(20)->withQueryString();
        $unreadCount = SalesTargetAlert::where('is_read', false)->count();

        return view('sales-targets.alerts', compact('alerts', 'unreadCount'));
    }

    public function markAlertRead(SalesTargetAlert $alert): RedirectResponse
    {
        $alert->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return back()->with('status', 'Alert marked as read.');
    }

    public function markAllAlertsRead(): RedirectResponse
    {
        SalesTargetAlert::where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return back()->with('status', 'All alerts marked as read.');
    }

    /**
     * Target Reports & Excel Export
     */
    public function reports(Request $request)
    {
        $query = SalesTarget::with([
            'salesperson', 'salesTeam', 'branch', 'brand', 'product', 'category', 'achievement', 'customer'
        ]);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('owner_type')) {
            $query->where('owner_type', $request->owner_type);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('salesperson_id')) {
            $query->where('salesperson_id', $request->salesperson_id);
        }
        if ($request->filled('target_type')) {
            $query->where('target_type', $request->target_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('progress_status')) {
            $query->progressStatus($request->progress_status);
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('financial_year_id')) {
            $query->where('financial_year_id', $request->financial_year_id);
        }
        if ($request->filled('period_type')) {
            $query->where('period_type', $request->period_type);
        }
        if ($request->filled('date_from')) {
            $query->where('starts_on', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('ends_on', '<=', $request->date_to);
        }

        // Handle Excel / CSV export
        $exportFormat = strtolower((string) $request->input('export'));
        if ($exportFormat === 'xlsx') {
            $exportData = $query->get();
            return Excel::download(new SalesTargetsExport($exportData), 'sales-targets-report-' . now()->format('Y-m-d') . '.xlsx');
        }
        if ($exportFormat === 'csv') {
            $exportData = $query->get();
            return Excel::download(new SalesTargetsExport($exportData), 'sales-targets-report-' . now()->format('Y-m-d') . '.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        $targets = $query->paginate(20)->withQueryString();

        $companies = Company::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $salespeople = User::orderBy('name')->get();
        $teams = SalesTeam::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $financialYears = FinancialYear::orderByDesc('starts_on')->get();
        $parties = Customer::orderBy('name')->get(['id', 'name', 'code', 'trade_name']);

        return view('sales-targets.reports', compact(
            'targets', 'brands', 'salespeople', 'teams', 'branches', 'companies', 'financialYears', 'parties'
        ));
    }

    /**
     * Manual Trigger to Recalculate Achievements
     */
    public function recalculate(Request $request): RedirectResponse
    {
        $count = $this->calculationService->recalculateAll(true);

        return back()->with('status', "Successfully recalculated {$count} targets with zero drift against sales invoices.");
    }
}

