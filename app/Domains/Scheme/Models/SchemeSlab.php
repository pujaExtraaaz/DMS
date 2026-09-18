<?php

namespace App\Domains\Scheme\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemeSlab extends Model
{
    protected $fillable = [
        'scheme_id',
        'from_value',
        'to_value',
        'benefit_percent',
        'benefit_amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'from_value' => 'decimal:2',
            'to_value' => 'decimal:2',
            'benefit_percent' => 'decimal:2',
            'benefit_amount' => 'decimal:2',
        ];
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }
}
