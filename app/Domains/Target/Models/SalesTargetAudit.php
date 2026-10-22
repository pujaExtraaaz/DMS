<?php

namespace App\Domains\Target\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTargetAudit extends Model
{
    protected $table = 'sales_target_audits';

    protected $fillable = [
        'sales_target_id',
        'user_id',
        'action',
        'reason',
        'old_values',
        'new_values',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(SalesTarget::class, 'sales_target_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

