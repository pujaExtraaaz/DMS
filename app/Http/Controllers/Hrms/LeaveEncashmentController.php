<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\LeaveBalance;
use App\Domains\Hrms\Models\LeaveEncashment;
use App\Domains\Hrms\Models\LeavePolicy;
use App\Domains\Hrms\Models\LeaveType;
use App\Domains\Hrms\Models\SalaryStructure;
use App\Domains\Hrms\Services\LeaveBalanceService;
use App\Http\Controllers\Controller;
use App\Support\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeaveEncashmentController extends Controller
{
    public function __construct(
        protected LeaveBalanceService $balances,
        protected AuditLogService $auditLogService
    ) {}

    public function index(): View
    {
        $items = LeaveEncashment::query()
            ->with(['employee', 'leaveType', 'approver'])
            ->latest()
            ->paginate(20);

        return view('hrms.leave-encashments.index', compact('items'));
    }

    public function create(): View
    {
        return view('hrms.leave-encashments.form', [
            'item' => new LeaveEncashment(['year' => (int) now()->year]),
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer|min:2020|max:2100',
            'days' => 'required|numeric|min:0.5',
            'amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $policy = $this->balances->resolvePolicy($employee, (int) $data['leave_type_id']);
        if (! $policy || ! $policy->allow_encashment) {
            return back()->withInput()->withErrors(['leave_type_id' => 'Encashment is not allowed for this leave type / policy.']);
        }

        $max = $policy->encashment_max !== null ? (float) $policy->encashment_max : PHP_FLOAT_MAX;
        if ((float) $data['days'] > $max) {
            return back()->withInput()->withErrors(['days' => "Encashment cannot exceed {$max} days."]);
        }

        $balance = LeaveBalance::query()
            ->where('employee_id', $data['employee_id'])
            ->where('leave_type_id', $data['leave_type_id'])
            ->where('year', $data['year'])
            ->first();

        if (! $balance || (float) $balance->closing_balance < (float) $data['days']) {
            return back()->withInput()->withErrors(['days' => 'Insufficient leave balance for encashment.']);
        }

        $data['status'] = 'pending';
        if (! isset($data['amount']) || (float) $data['amount'] <= 0) {
            $data['amount'] = $this->estimateAmount((int) $data['employee_id'], (float) $data['days']);
        }
        LeaveEncashment::create($data);

        return $this->flashSuccess('Leave encashment request submitted.', 'hrms.leave-encashments.index');
    }

    public function approve(LeaveEncashment $leave_encashment): RedirectResponse
    {
        if ($leave_encashment->status !== 'pending') {
            return $this->flashError('Only pending encashment can be approved.');
        }

        DB::transaction(function () use ($leave_encashment) {
            $balance = LeaveBalance::query()->firstOrCreate(
                [
                    'employee_id' => $leave_encashment->employee_id,
                    'leave_type_id' => $leave_encashment->leave_type_id,
                    'year' => $leave_encashment->year,
                ],
                ['opening_balance' => 0, 'used_balance' => 0, 'closing_balance' => 0]
            );

            $balance->encashed_balance = (float) ($balance->encashed_balance ?? 0) + (float) $leave_encashment->days;
            $this->balances->recalculateClosing($balance);

            $amount = (float) ($leave_encashment->amount ?? 0);
            if ($amount <= 0) {
                $amount = $this->estimateAmount((int) $leave_encashment->employee_id, (float) $leave_encashment->days);
            }

            $leave_encashment->update([
                'status' => 'approved',
                'amount' => $amount,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            $this->auditLogService->record($leave_encashment, 'approved');
        });

        return $this->flashSuccess('Encashment approved.', 'hrms.leave-encashments.index');
    }

    protected function estimateAmount(int $employeeId, float $days): float
    {
        $structure = SalaryStructure::query()
            ->with('lines.payHead')
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->latest('effective_from')
            ->first();

        $basic = (float) ($structure?->basic ?? 0);
        if ($structure && $structure->lines->isNotEmpty()) {
            $basicLine = $structure->lines->first(fn ($l) => strtoupper((string) ($l->payHead?->code ?? '')) === 'BASIC');
            if ($basicLine) {
                $basic = (float) $basicLine->amount;
            }
        }

        return round(($basic / 30) * $days, 2);
    }

    public function reject(LeaveEncashment $leave_encashment): RedirectResponse
    {
        if ($leave_encashment->status !== 'pending') {
            return $this->flashError('Only pending encashment can be rejected.');
        }
        $leave_encashment->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $this->flashSuccess('Encashment rejected.', 'hrms.leave-encashments.index');
    }
}
