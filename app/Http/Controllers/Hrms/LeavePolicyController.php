<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Department;
use App\Domains\Hrms\Models\Designation;
use App\Domains\Hrms\Models\LeavePolicy;
use App\Domains\Hrms\Models\LeaveType;
use App\Domains\Hrms\Services\LeaveBalanceService;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeavePolicyController extends Controller
{
    public function __construct(protected LeaveBalanceService $balances) {}

    public function index(): View
    {
        $items = LeavePolicy::query()
            ->with(['leaveType', 'rules.department', 'rules.designation'])
            ->orderBy('name')
            ->paginate(20);

        return view('hrms.leave-policies.index', compact('items'));
    }

    public function create(): View
    {
        return view('hrms.leave-policies.form', $this->formData(new LeavePolicy([
            'accrual_frequency' => 'yearly',
            'allow_half_day' => true,
            'is_active' => true,
            'company_id' => auth()->user()?->company_id,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $policy = LeavePolicy::create($data);
        $this->syncRules($policy, $request);

        return $this->flashSuccess('Leave policy created.', 'hrms.leave-policies.index');
    }

    public function edit(LeavePolicy $leave_policy): View
    {
        $leave_policy->load('rules');

        return view('hrms.leave-policies.form', $this->formData($leave_policy));
    }

    public function update(Request $request, LeavePolicy $leave_policy): RedirectResponse
    {
        $leave_policy->update($this->validated($request));
        $this->syncRules($leave_policy, $request);

        return $this->flashSuccess('Leave policy updated.', 'hrms.leave-policies.index');
    }

    public function destroy(LeavePolicy $leave_policy): RedirectResponse
    {
        $leave_policy->delete();

        return $this->flashSuccess('Leave policy deleted.', 'hrms.leave-policies.index');
    }

    public function allocate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'company_id' => 'nullable|exists:companies,id',
        ]);
        $count = $this->balances->allocateCompanyYear((int) $data['year'], $data['company_id'] ?? null);

        return $this->flashSuccess("Allocated / refreshed {$count} leave balances for {$data['year']}.", 'hrms.leave-balances.index');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'name' => 'required|string|max:120',
            'annual_entitlement' => 'required|numeric|min:0',
            'accrual_frequency' => 'required|in:monthly,yearly',
            'carry_forward_max' => 'nullable|numeric|min:0',
            'allow_encashment' => 'boolean',
            'encashment_max' => 'nullable|numeric|min:0',
            'allow_half_day' => 'boolean',
            'allow_short_leave' => 'boolean',
            'short_leave_hours' => 'nullable|numeric|min:0.5|max:8',
            'is_active' => 'boolean',
        ]) + [
            'allow_encashment' => $request->boolean('allow_encashment'),
            'allow_half_day' => $request->boolean('allow_half_day'),
            'allow_short_leave' => $request->boolean('allow_short_leave'),
            'is_active' => $request->boolean('is_active'),
            'carry_forward_max' => $request->input('carry_forward_max', 0),
        ];
    }

    protected function syncRules(LeavePolicy $policy, Request $request): void
    {
        $policy->rules()->delete();
        $deptIds = array_filter((array) $request->input('department_ids', []));
        $desigIds = array_filter((array) $request->input('designation_ids', []));
        $category = $request->input('employee_category');

        if ($deptIds === [] && $desigIds === [] && ! $category) {
            return;
        }

        if ($deptIds === [] && $desigIds === []) {
            $policy->rules()->create([
                'employee_category' => $category ?: null,
            ]);

            return;
        }

        foreach ($deptIds ?: [null] as $deptId) {
            foreach ($desigIds ?: [null] as $desigId) {
                $policy->rules()->create([
                    'department_id' => $deptId,
                    'designation_id' => $desigId,
                    'employee_category' => $category ?: null,
                ]);
            }
        }
    }

    protected function formData(LeavePolicy $item): array
    {
        return [
            'item' => $item,
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
            'companies' => Company::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'designations' => Designation::orderBy('name')->get(),
        ];
    }
}
