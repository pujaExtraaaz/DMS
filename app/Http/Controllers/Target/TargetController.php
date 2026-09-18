<?php

namespace App\Http\Controllers\Target;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Customer;
use App\Domains\Target\Models\TargetPeriod;
use App\Domains\Target\Services\TargetService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TargetController extends Controller
{
    public function __construct(
        protected TargetService $targetService,
    ) {}

    public function index(Request $request): View
    {
        $periods = TargetPeriod::query()
            ->withCount('targets')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('starts_on')
            ->paginate(15)
            ->withQueryString();

        return view('targets.index', compact('periods'));
    }

    public function createPeriod(): View
    {
        return view('targets.period-form', [
            'item' => new TargetPeriod([
                'period_type' => 'monthly',
                'starts_on' => now()->startOfMonth()->toDateString(),
                'ends_on' => now()->endOfMonth()->toDateString(),
            ]),
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'period_type' => 'required|in:monthly,quarterly,annual',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
        ]);

        $period = $this->targetService->createPeriod($validated);

        return $this->flashSuccess('Target period created.', 'targets.periods.show', ['target_period' => $period]);
    }

    public function showPeriod(TargetPeriod $target_period): View
    {
        $target_period->load(['targets.customer', 'targets.salesperson', 'targets.brand', 'targets.achievement']);

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
