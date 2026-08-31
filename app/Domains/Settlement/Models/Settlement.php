<?php

namespace App\Domains\Settlement\Models;

use App\Domains\Logistics\Models\LoadSheet;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Settlement extends Model
{
    protected $fillable = [
        'settlement_no',
        'load_sheet_id',
        'cash_collected',
        'upi_collected',
        'outstanding_amount',
        'status',
        'settled_by',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'cash_collected' => 'decimal:2',
            'upi_collected' => 'decimal:2',
            'outstanding_amount' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }

    public function loadSheet(): BelongsTo
    {
        return $this->belongsTo(LoadSheet::class);
    }

    public function settler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SettlementLine::class);
    }
}
