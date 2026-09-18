<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Designation;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesignationController extends Controller
{
    public function index(Request $request): View
    {
        $items = Designation::query()
            ->with('company')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('hrms.designations.index', compact('items'));
    }

    public function create(): View
    {
        return view('hrms.designations.form', [
            'item' => new Designation(['is_active' => true]),
            'companies' => Company::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Designation::create($this->validated($request));

        return $this->flashSuccess('Designation created.', 'hrms.designations.index');
    }

    public function edit(Designation $designation): View
    {
        return view('hrms.designations.form', [
            'item' => $designation,
            'companies' => Company::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Designation $designation): RedirectResponse
    {
        $designation->update($this->validated($request));

        return $this->flashSuccess('Designation updated.', 'hrms.designations.index');
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        $designation->delete();

        return $this->flashSuccess('Designation deleted.', 'hrms.designations.index');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30',
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['company_id'] = $data['company_id'] ?? auth()->user()?->company_id;

        return $data;
    }
}
