<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinancialYearController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = FinancialYear::query()->with('company');

        $this->applySearch($query, $request->input('search'), [
            'name',
        ], [
            'company' => ['name'],
        ]);

        [$sort, $direction] = $this->applySorting(
            $query,
            $request,
            [
                'starts_on' => 'starts_on',
                'name' => 'name',
                'company' => function ($q, $dir) {
                    $q->leftJoin('companies as c_fy', 'financial_years.company_id', '=', 'c_fy.id')
                        ->orderBy('c_fy.name', $dir)
                        ->select('financial_years.*');
                },
                'ends_on' => 'ends_on',
                'is_current' => 'is_current',
                'is_closed' => 'is_closed',
            ],
            'name',
            'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('organization.financial-years.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(): View
    {
        $defaultCompanyId = auth()->user()?->company_id ?? Company::defaultCompanyId();

        return view('organization.financial-years.form', [
            'item' => new FinancialYear(['company_id' => $defaultCompanyId]),
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            if (!empty($data['is_current'])) {
                FinancialYear::where('company_id', $data['company_id'])->update(['is_current' => false]);
            }
            FinancialYear::create($data);
        });
        return $this->flashSuccess('Financial year created successfully.', 'organization.financial-years.index');
    }

    public function edit(FinancialYear $financial_year): View
    {
        return view('organization.financial-years.form', [
            'item' => $financial_year,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, FinancialYear $financial_year): RedirectResponse
    {
        $data = $this->validated($request, $financial_year);
        DB::transaction(function () use ($data, $financial_year) {
            if (!empty($data['is_current'])) {
                FinancialYear::where('company_id', $data['company_id'])->where('id', '!=', $financial_year->id)->update(['is_current' => false]);
            }
            $financial_year->update($data);
        });
        return $this->flashSuccess('Financial year updated successfully.', 'organization.financial-years.index');
    }

    public function destroy(Request $request, FinancialYear $financial_year): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // Check user permissions before deleting
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) && ! $user->can('organization.manage') && ! $user->can('organization.edit')) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete financial year.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized to delete financial year.');
        }

        // Prevent deleting a financial year if it belongs to another company or unauthorized tenant
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id && (int) $financial_year->company_id !== (int) $user->company_id) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This financial year belongs to another company.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: This financial year belongs to another company.');
        }

        // Check for protected accounting records or dependency block reasons
        $blockReason = $financial_year->getDeleteBlockReason();
        if ($blockReason) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $blockReason,
                ], 422);
            }

            return back()->with('error', $blockReason);
        }

        $fyName = $financial_year->name;
        $companyId = $financial_year->company_id;
        $wasCurrent = $financial_year->is_current;

        DB::transaction(function () use ($financial_year, $companyId, $wasCurrent) {
            // Nullify unused document sequence and stock valuation references
            if (\Illuminate\Support\Facades\Schema::hasTable('document_sequences')) {
                DB::table('document_sequences')
                    ->where('financial_year_id', $financial_year->id)
                    ->update(['financial_year_id' => null]);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('stock_valuation_settings')) {
                DB::table('stock_valuation_settings')
                    ->where('financial_year_id', $financial_year->id)
                    ->update(['financial_year_id' => null]);
            }

            // If the deleted FY was current, transition current to the most recent open FY (or any recent FY) for the company
            if ($wasCurrent) {
                $nextCurrent = FinancialYear::where('company_id', $companyId)
                    ->where('id', '!=', $financial_year->id)
                    ->where('is_closed', false)
                    ->orderByDesc('starts_on')
                    ->first();

                if (! $nextCurrent) {
                    $nextCurrent = FinancialYear::where('company_id', $companyId)
                        ->where('id', '!=', $financial_year->id)
                        ->orderByDesc('starts_on')
                        ->first();
                }

                if ($nextCurrent) {
                    $nextCurrent->update(['is_current' => true]);
                }
            }

            $financial_year->delete();
        });

        Cache::forget('current_fy_pill');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Financial year '{$fyName}' deleted successfully.",
            ]);
        }

        return $this->flashSuccess("Financial year '{$fyName}' deleted successfully.", 'organization.financial-years.index')
            ->with('success', "Financial year '{$fyName}' deleted successfully.");
    }

    public function setCurrent(FinancialYear $financial_year): RedirectResponse
    {
        DB::transaction(function () use ($financial_year) {
            FinancialYear::where('company_id', $financial_year->company_id)
                ->where('id', '!=', $financial_year->id)
                ->update(['is_current' => false]);
            $financial_year->update(['is_current' => true, 'is_closed' => false]);
        });

        Cache::forget('current_fy_pill');

        return $this->flashSuccess("Current period switched to {$financial_year->name}.", 'organization.financial-years.index');
    }

    public function close(FinancialYear $financial_year): RedirectResponse
    {
        $financial_year->update(['is_closed' => true, 'is_current' => false]);

        Cache::forget('current_fy_pill');

        return $this->flashSuccess("Financial year {$financial_year->name} closed.", 'organization.financial-years.index');
    }

    public function reopen(FinancialYear $financial_year): RedirectResponse
    {
        $financial_year->update(['is_closed' => false]);

        Cache::forget('current_fy_pill');

        return $this->flashSuccess("Financial year {$financial_year->name} re-opened.", 'organization.financial-years.index');
    }

    protected function validated(Request $request, ?FinancialYear $fy = null): array
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'nullable|string|max:50',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after:starts_on',
            'is_closed' => 'boolean',
            'is_current' => 'boolean',
        ]);
        $data['is_closed'] = $request->boolean('is_closed');
        $data['is_current'] = $request->boolean('is_current');

        if (blank($data['name'] ?? null)) {
            $startYear = Carbon::parse($data['starts_on'])->format('Y');
            $endYear = Carbon::parse($data['ends_on'])->format('y');
            $data['name'] = $fy?->name ?: "{$startYear}-{$endYear}";
        }

        return $data;
    }
}