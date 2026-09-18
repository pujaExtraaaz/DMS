<?php

namespace App\Domains\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialYear extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'starts_on',
        'ends_on',
        'is_closed',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_closed' => 'boolean',
            'is_current' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function containsDate($date): bool
    {
        $d = \Illuminate\Support\Carbon::parse($date)->toDateString();

        return $d >= $this->starts_on->toDateString() && $d <= $this->ends_on->toDateString();
    }
}
