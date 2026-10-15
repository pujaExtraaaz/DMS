<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use App\Domains\Sync\Support\SyncNames;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\Employee as BooksEmployee;

class EmployeeSync
{
    public const KEY = 'employee';

    public function __construct(private readonly BranchSync $branches) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Employee) {
            $this->push($model);
        }
    }

    public function push(Employee $employee): void
    {
        if (! $employee->company_id) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $employee->company_id);
        if (! $company) {
            return;
        }

        $employee->loadMissing(['branch', 'designation']);
        $branchId = null;
        if ($employee->branch) {
            $this->branches->push($employee->branch);
            $branchId = SyncLinks::acctId(BranchSync::KEY, $employee->branch)
                ?: BooksCompany::branchId((int) $employee->company_id, (int) $employee->branch_id);
        } else {
            $branchId = BooksCompany::branchId((int) $employee->company_id);
        }

        $books = BooksEmployee::query()->find(SyncLinks::acctId(self::KEY, $employee));
        $code = SyncNames::unique(
            BooksEmployee::query()->where('company_id', $company->id),
            'employee_code',
            SyncNames::code($employee->employee_code ?: ('E'.$employee->id), 30),
            $books?->id,
            30
        );

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'name' => $employee->name,
            'employee_code' => $code,
            'designation' => $employee->designation?->name,
            'joining_date' => $employee->joining_date?->toDateString(),
        ];

        $books ? $books->update($attributes) : $books = BooksEmployee::query()->create($attributes);
        SyncLinks::store(self::KEY, $employee, $books);
    }

    public function remove(Model $source): void
    {
        if (! $source instanceof Employee) {
            return;
        }
        SyncLinks::forget(self::KEY, $source);
    }
}
