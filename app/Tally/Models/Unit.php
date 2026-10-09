<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends AccountingModel
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'symbol',
        'decimal_places',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function primaryProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'primary_unit_id');
    }

    public function alternateProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'alternate_unit_id');
    }

    public function label(): string
    {
        return $this->name.' ('.$this->symbol.')';
    }

    public function canBeDeleted(): bool
    {
        $primary = array_key_exists('primary_products_count', $this->getAttributes())
            ? (int) $this->primary_products_count === 0
            : ! $this->primaryProducts()->exists();
        $alternate = array_key_exists('alternate_products_count', $this->getAttributes())
            ? (int) $this->alternate_products_count === 0
            : ! $this->alternateProducts()->exists();

        return $primary && $alternate;
    }
}
