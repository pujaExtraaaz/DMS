<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Department;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $items = Department::query()
            ->with('company')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('hrms.departments.index', compact('items'));
    }

    public function create(): View
    {
        return view('hrms.departments.form', [
            'item' => new Department(['is_active' => true]),
            'companies' => Company::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Department::create($this->validated($request));

        return $this->flashSuccess('Department created.', 'hrms.departments.index');
    }

    public function edit(Department $department): View
    {
        return view('hrms.departments.form', [
            'item' => $department,
            'companies' => Company::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validated($request));

        return $this->flashSuccess('Department updated.', 'hrms.departments.index');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return $this->flashSuccess('Department deleted.', 'hrms.departments.index');
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
