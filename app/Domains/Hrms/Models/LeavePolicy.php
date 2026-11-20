<?php

namespace App\Domains\Hrms\Models;

use App\Domains\Organization\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeavePolicy extends Model
{
    protected $fillable = [
        'company_id', 'leave_type_id', 'name', 'annual_entitlement', 'accrual_frequency',
        'carry_forward_max', 'allow_encashment', 'encashment_max',
        'allow_half_day', 'allow_short_leave', 'short_leave_hours', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'annual_entitlement' => 'decimal:2',
            'carry_forward_max' => 'decimal:2',
            'encashment_max' => 'decimal:2',
            'short_leave_hours' => 'decimal:2',
            'allow_encashment' => 'boolean',
            'allow_half_day' => 'boolean',
            'allow_short_leave' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(LeavePolicyRule::class);
    }
}
