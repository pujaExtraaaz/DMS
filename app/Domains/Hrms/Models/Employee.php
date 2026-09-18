<?php

namespace App\Domains\Hrms\Models;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use App\Support\Concerns\ScopeByBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use ScopeByBranch;

    protected $fillable = [
        'company_id', 'branch_id', 'user_id', 'department_id', 'designation_id', 'manager_id',
        'employee_code', 'name', 'email', 'phone', 'joining_date', 'date_of_birth',
        'is_salesperson', 'status', 'address',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'date_of_birth' => 'date',
            'is_salesperson' => 'boolean',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function designation(): BelongsTo { return $this->belongsTo(Designation::class); }
    public function manager(): BelongsTo { return $this->belongsTo(self::class, 'manager_id'); }
    public function directReports(): HasMany { return $this->hasMany(self::class, 'manager_id'); }
    public function documents(): HasMany { return $this->hasMany(EmployeeDocument::class); }
    public function attendances(): HasMany { return $this->hasMany(Attendance::class); }
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }
    public function leaveBalances(): HasMany { return $this->hasMany(LeaveBalance::class); }
    public function salaryStructure(): HasOne { return $this->hasOne(SalaryStructure::class)->where('is_active', true)->latestOfMany(); }
    public function expenseClaims(): HasMany { return $this->hasMany(ExpenseClaim::class); }
    public function exitRecord(): HasOne { return $this->hasOne(EmployeeExit::class); }
}
