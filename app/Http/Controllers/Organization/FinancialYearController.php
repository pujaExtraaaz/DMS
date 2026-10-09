<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
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
            'starts_on',
            'desc'
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
        return view('organization.financial-years.form', [
            'item' => new FinancialYear,
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

    public function destroy(FinancialYear $financial_year): RedirectResponse
    {
        $financial_year->delete();
        return $this->flashSuccess('Financial year deleted successfully.', 'organization.financial-years.index');
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