<?php

namespace App\Models;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Support\Traits\HasUppercaseNames;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasUppercaseNames, Notifiable;

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
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

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branch_access')
            ->withPivot(['is_default'])
            ->withTimestamps();
    }

    public function allowedBranchIds(): array
    {
        $ids = $this->branches()->pluck('branches.id')->all();

        if ($ids === [] && $this->branch_id) {
            return [(int) $this->branch_id];
        }

        return array_map('intval', $ids);
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Domains\Hrms\Models\Employee::class);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(UserPreference::class);
    }

    public function getPreference(string $key, mixed $default = null): mixed
    {
        $pref = $this->preferences()->where('key', $key)->first();

        return $pref !== null ? $pref->value : $default;
    }

    public function setPreference(string $key, mixed $value): void
    {
        $this->preferences()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
