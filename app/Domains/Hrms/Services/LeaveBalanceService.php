<?php

namespace App\Domains\Hrms\Services;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\LeaveBalance;
use App\Domains\Hrms\Models\LeavePolicy;
use App\Domains\Hrms\Models\LeavePolicyRule;
use Illuminate\Support\Collection;

class LeaveBalanceService
{
    public function resolvePolicy(Employee $employee, int $leaveTypeId): ?LeavePolicy
    {
        $policies = LeavePolicy::query()
            ->where('leave_type_id', $leaveTypeId)
            ->where('is_active', true)
            ->where(function ($q) use ($employee) {
                $q->whereNull('company_id');
                if ($employee->company_id) {
                    $q->orWhere('company_id', $employee->company_id);
                }
            })
            ->with('rules')
            ->get();

        if ($policies->isEmpty()) {
            return null;
        }

        $scored = $policies->map(function (LeavePolicy $policy) use ($employee) {
            $rules = $policy->rules;
            if ($rules->isEmpty()) {
                return ['policy' => $policy, 'score' => 1];
            }

            $best = 0;
            foreach ($rules as $rule) {
                /** @var LeavePolicyRule $rule */
                $score = 0;
                if ($rule->department_id && (int) $rule->department_id === (int) $employee->department_id) {
                    $score += 2;
                } elseif ($rule->department_id) {
                    continue;
                }
                if ($rule->designation_id && (int) $rule->designation_id === (int) $employee->designation_id) {
                    $score += 2;
                } elseif ($rule->designation_id) {
                    continue;
                }
                if ($rule->employee_category) {
                    $category = $employee->is_salesperson ? 'salesperson' : 'staff';
                    if ($rule->employee_category !== $category) {
                        continue;
                    }
                    $score += 1;
                }
                $best = max($best, $score + 1);
            }

            return ['policy' => $policy, 'score' => $best];
        })->filter(fn ($row) => $row['score'] > 0)->sortByDesc('score');

        return $scored->first()['policy'] ?? $policies->first();
    }

    public function allocateForEmployee(Employee $employee, int $year, ?int $leaveTypeId = null): Collection
    {
        $query = LeavePolicy::query()->where('is_active', true);
        if ($leaveTypeId) {
            $query->where('leave_type_id', $leaveTypeId);
        }

        $created = collect();
        foreach ($query->get() as $policy) {
            $matched = $this->resolvePolicy($employee, $policy->leave_type_id);
            if (! $matched || $matched->id !== $policy->id) {
                continue;
            }

            $previous = LeaveBalance::query()
                ->where('employee_id', $employee->id)
                ->where('leave_type_id', $policy->leave_type_id)
                ->where('year', $year - 1)
                ->first();

            $carry = 0;
            if ($previous && (float) $policy->carry_forward_max > 0) {
                $carry = min((float) $previous->closing_balance, (float) $policy->carry_forward_max);
            }

            $accrued = (float) $policy->annual_entitlement;
            if ($policy->accrual_frequency === 'monthly') {
                $monthsElapsed = $year < (int) now()->year ? 12 : (int) now()->month;
                $accrued = round(((float) $policy->annual_entitlement / 12) * $monthsElapsed, 2);
            }

            $opening = $carry;
            $balance = LeaveBalance::query()->updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $policy->leave_type_id,
                    'year' => $year,
                ],
                [
                    'opening_balance' => $opening,
                    'carried_forward' => $carry,
                    'accrued_balance' => $accrued,
                ]
            );

            $used = (float) $balance->used_balance;
            $encashed = (float) ($balance->encashed_balance ?? 0);
            $balance->closing_balance = round($opening + $accrued - $used - $encashed, 2);
            $balance->save();
            $created->push($balance);
        }

        return $created;
    }

    public function allocateCompanyYear(int $year, ?int $companyId = null): int
    {
        $employees = Employee::query()
            ->where('status', 'active')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get();

        $count = 0;
        foreach ($employees as $employee) {
            $count += $this->allocateForEmployee($employee, $year)->count();
        }

        return $count;
    }

    public function recalculateClosing(LeaveBalance $balance): LeaveBalance
    {
        $opening = (float) $balance->opening_balance;
        $accrued = (float) ($balance->accrued_balance ?? 0);
        $used = (float) $balance->used_balance;
        $encashed = (float) ($balance->encashed_balance ?? 0);
        $balance->closing_balance = round($opening + $accrued - $used - $encashed, 2);
        $balance->save();

        return $balance;
    }
}
