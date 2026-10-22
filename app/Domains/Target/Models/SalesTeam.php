<?php

namespace App\Domains\Target\Models;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesTeam extends Model
{
    use HasUppercaseNames;

    protected $table = 'sales_teams';

    protected $fillable = [
        'company_id',
        'branch_id',
        'leader_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sales_team_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function targets(): HasMany
    {
        return $this->hasMany(SalesTarget::class, 'sales_team_id');
    }
}

