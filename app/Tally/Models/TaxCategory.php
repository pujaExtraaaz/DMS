<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Database\Factories\TaxCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxCategory extends AccountingModel
{
    /** @use HasFactory<TaxCategoryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
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

    public function rates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }

    public function canBeDeleted(): bool
    {
        return ! $this->rates()->exists();
    }
}
