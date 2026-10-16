<?php

namespace App\Domains\Organization\Models;

use App\Support\NameCase;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasUppercaseNames, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'address',
        'state',
        'pincode',
        'phone',
        'gstin',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function getNameAttribute($value): string
    {
        $val = NameCase::format($value);
        if ($this->trashed()) {
            return str_contains($val ?? '', '(ARCHIVED)') ? (string) $val : "{$val} (ARCHIVED)";
        }

        return (string) $val;
    }

    public function getDisplayNameAttribute(): string
    {
        $rawName = $this->getRawOriginal('name') ?? $this->attributes['name'] ?? '';

        return $this->trashed() ? "{$rawName} (Archived)" : (string) $rawName;
    }

    public function getIsArchivedAttribute(): bool
    {
        return $this->trashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class);
    }

    public function assignedUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'user_branch_access')
            ->withPivot(['is_default'])
            ->withTimestamps();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(\App\Domains\Purchasing\Models\PurchaseOrder::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(\App\Domains\Hrms\Models\Employee::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(\App\Domains\Master\Models\Customer::class);
    }

    public function canBeDeleted(): bool
    {
        return true;
    }

    public function getDeleteBlockReason(): ?string
    {
        return null;
    }

    public function hasLinkedRecordsInTable(string $table, string $column = 'branch_id'): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
            return false;
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn($table, $column)) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::table($table)->where($column, $this->id)->exists();
    }
}

