<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\LeaveType;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function index(): View
    {
        $items = LeaveType::query()->latest()->paginate(15);

        return view('hrms.leave-types.index', compact('items'));
    }

    public function create(): View
    {
        return view('hrms.leave-types.form', [
            'item' => new LeaveType(['is_active' => true, 'is_paid' => true, 'default_days' => 0]),
            'companies' => Company::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LeaveType::create($this->validated($request));

        return $this->flashSuccess('Leave type created.', 'hrms.leave-types.index');
    }

    public function edit(LeaveType $leave_type): View
    {
        return view('hrms.leave-types.form', [
            'item' => $leave_type,
            'companies' => Company::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, LeaveType $leave_type): RedirectResponse
    {
        $leave_type->update($this->validated($request));

        return $this->flashSuccess('Leave type updated.', 'hrms.leave-types.index');
    }

    public function destroy(LeaveType $leave_type): RedirectResponse
    {
        $leave_type->delete();

        return $this->flashSuccess('Leave type deleted.', 'hrms.leave-types.index');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30',
            'default_days' => 'nullable|integer|min:0',
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
        ]);
        $data['is_paid'] = $request->boolean('is_paid', true);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['company_id'] = $data['company_id'] ?? auth()->user()?->company_id;

        return $data;
    }
}
