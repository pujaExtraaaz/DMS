<?php

namespace App\Domains\Target\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTargetAlert extends Model
{
    protected $table = 'sales_target_alerts';

    protected $fillable = [
        'sales_target_id',
        'user_id',
        'alert_type',
        'threshold_percent',
        'title',
        'message',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'threshold_percent' => 'decimal:2',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
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

