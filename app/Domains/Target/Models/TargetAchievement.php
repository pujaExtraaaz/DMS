<?php

namespace App\Domains\Target\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TargetAchievement extends Model
{
    protected $fillable = [
        'party_target_id',
        'target_period_id',
        'achieved_amount',
        'achievement_percent',
        'is_final',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'achieved_amount' => 'decimal:2',
            'achievement_percent' => 'decimal:2',
            'is_final' => 'boolean',
            'calculated_at' => 'datetime',
        ];
    }

    public function partyTarget(): BelongsTo
    {
        return $this->belongsTo(PartyTarget::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TargetPeriod::class, 'target_period_id');
    }
}
