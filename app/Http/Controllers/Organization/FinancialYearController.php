<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinancialYearController extends Controller
{
    public function index(Request $request): View
    {
        $items = FinancialYear::query()->with('company')->latest()->paginate(15)->withQueryString();
        return view('organization.financial-years.index', ['items' => $items, 'search' => $request->string('search')]);
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

    /** Make the given FY the current period (Tally F2 equivalent). */
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

    /** Lock the FY so no back-dated postings are allowed. */
    public function close(FinancialYear $financial_year): RedirectResponse
    {
        $financial_year->update(['is_closed' => true, 'is_current' => false]);

        Cache::forget('current_fy_pill');

        return $this->flashSuccess("Financial year {$financial_year->name} closed.", 'organization.financial-years.index');
    }

    /** Re-open a previously closed FY. */
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
            'name' => 'required|string|max:50',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after:starts_on',
            'is_closed' => 'boolean',
            'is_current' => 'boolean',
        ]);
        $data['is_closed'] = $request->boolean('is_closed');
        $data['is_current'] = $request->boolean('is_current');
        return $data;
    }
}
