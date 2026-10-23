<?php

namespace App\Domains\Target\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTargetAchievement extends Model
{
    protected $table = 'sales_target_achievements';

    protected $fillable = [
        'sales_target_id',
        'achieved_amount',
        'achieved_quantity',
        'pending_amount',
        'pending_quantity',
        'achievement_percent',
        'quantity_achievement_percent',
        'is_achieved',
        'invoices_count',
        'last_calculated_at',
        'breakdown_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'achieved_amount' => 'decimal:2',
            'achieved_quantity' => 'decimal:4',
            'pending_amount' => 'decimal:2',
            'pending_quantity' => 'decimal:4',
            'achievement_percent' => 'decimal:2',
            'quantity_achievement_percent' => 'decimal:2',
            'is_achieved' => 'boolean',
            'invoices_count' => 'integer',
            'last_calculated_at' => 'datetime',
            'breakdown_snapshot' => 'array',
        ];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(SalesTarget::class, 'sales_target_id');
    }
}

