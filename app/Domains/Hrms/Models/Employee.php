<?php

namespace App\Domains\Hrms\Models;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use App\Support\Concerns\ScopeByBranch;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasUppercaseNames, ScopeByBranch {
        HasUppercaseNames::isUppercaseNameAttribute as protected traitIsUppercaseNameAttribute;
    }

    protected $fillable = [
        'company_id', 'branch_id', 'user_id', 'department_id', 'designation_id', 'manager_id',
        'employee_code', 'name', 'email', 'personal_email', 'phone', 'contact_number', 'alternate_contact_number',
        'joining_date', 'date_of_birth', 'father_name', 'blood_group', 'aadhaar_number', 'pan_number',
        'is_salesperson', 'status', 'address', 'present_address', 'permanent_address',
        'emergency_contact_number', 'emergency_contact_relationship',
        'bank_account_holder', 'bank_name', 'bank_branch', 'bank_account_number', 'bank_account_type', 'bank_ifsc',
        'photo_path', 'cancelled_cheque_path',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'date_of_birth' => 'date',
            'is_salesperson' => 'boolean',
        ];
    }

    public function isUppercaseNameAttribute(string $key): bool
    {
        if (in_array($key, [
            'aadhaar_number', 'pan_number', 'bank_account_number', 'bank_ifsc',
            'photo_path', 'cancelled_cheque_path', 'blood_group',
            'personal_email', 'contact_number', 'alternate_contact_number', 'emergency_contact_number',
        ], true)) {
            return false;
        }

        return $this->traitIsUppercaseNameAttribute($key);
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class)->withTrashed(); }
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
    public function salaryStructures(): HasMany { return $this->hasMany(SalaryStructure::class); }
    public function expenseClaims(): HasMany { return $this->hasMany(ExpenseClaim::class); }
    public function exitRecord(): HasOne { return $this->hasOne(EmployeeExit::class); }
}
