<?php

namespace App\Domains\Hrms\Models;

use App\Domains\Organization\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = ['company_id', 'name', 'code', 'default_days', 'is_paid', 'is_active'];

    protected function casts(): array
    {
        return ['is_paid' => 'boolean', 'is_active' => 'boolean'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function requests(): HasMany { return $this->hasMany(LeaveRequest::class); }
}
