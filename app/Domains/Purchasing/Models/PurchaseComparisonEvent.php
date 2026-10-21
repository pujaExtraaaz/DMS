<?php

namespace App\Domains\Purchasing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseComparisonEvent extends Model
{
    protected $fillable = [
        'purchase_comparison_id',
        'user_id',
        'action',
        'note',
    ];

    public function comparison(): BelongsTo
    {
        return $this->belongsTo(PurchaseComparison::class, 'purchase_comparison_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
