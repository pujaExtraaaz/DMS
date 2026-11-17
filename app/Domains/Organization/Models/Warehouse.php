<?php

namespace App\Domains\Organization\Models;

use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasUppercaseNames, SoftDeletes;

    public const DEFAULT_WAREHOUSE_TYPES = [
        'Main Warehouse',
        'Transit',
        'Cold Storage',
        'Bonded',
        'Returns',
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'code',
        'warehouse_type',
        'address',
        'address_line_2',
        'city',
        'state',
        'country',
        'pincode',
        'contact_person',
        'contact_phone',
        'is_default',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Warehouse $warehouse) {
            if (blank($warehouse->code)) {
                $warehouse->code = \App\Support\CodeGenerator::forWarehouse($warehouse->company_id ? (int) $warehouse->company_id : null);
            }
        });
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
        return $this->getDeleteBlockReason() === null;
    }

    public function getDeleteBlockReason(): ?string
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('stock_levels')
            && \Illuminate\Support\Facades\DB::table('stock_levels')->where('warehouse_id', $this->id)->where('quantity', '!=', 0)->exists()) {
            return 'Cannot delete warehouse because it still holds stock.';
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('purchase_orders')
            && \Illuminate\Support\Facades\DB::table('purchase_orders')->where('warehouse_id', $this->id)->exists()) {
            return 'Cannot delete warehouse because purchase orders reference it.';
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('purchase_invoices')
            && \Illuminate\Support\Facades\DB::table('purchase_invoices')->where('warehouse_id', $this->id)->exists()) {
            return 'Cannot delete warehouse because purchase invoices reference it.';
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('stock_movements')
            && \Illuminate\Support\Facades\DB::table('stock_movements')->where('warehouse_id', $this->id)->exists()) {
            return 'Cannot delete warehouse because stock movements reference it.';
        }

        return null;
    }
}
