<?php

namespace App\Domains\Organization\Models;

use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasUppercaseNames, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'code',
        'address',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function getNameAttribute($value): string
    {
        if ($this->trashed()) {
            return str_contains($value ?? '', '(Archived)') ? (string) $value : "{$value} (Archived)";
        }

        return (string) $value;
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function canBeDeleted(): bool
    {
        return true;
    }

    public function getDeleteBlockReason(): ?string
    {
        return null;
    }
}
