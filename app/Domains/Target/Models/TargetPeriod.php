<?php

namespace App\Domains\Target\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TargetPeriod extends Model
{
    protected $fillable = [
        'name',
        'period_type',
        'starts_on',
        'ends_on',
        'status',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function targets(): HasMany
    {
        return $this->hasMany(PartyTarget::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(TargetAchievement::class);
    }
}
