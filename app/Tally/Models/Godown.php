<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Database\Factories\GodownFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Godown extends AccountingModel
{
    /** @use HasFactory<GodownFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'address',
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

    public function canBeDeleted(): bool
    {
        return ! $this->hasMany(StockMovement::class)->exists()
            && ! $this->hasMany(StockTransactionLine::class)->exists()
            && ! $this->hasMany(InvoiceLine::class)->exists();
    }
}
