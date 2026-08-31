<?php

namespace App\Domains\Logistics\Models;

use App\Domains\Sales\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadSheetItem extends Model
{
    protected $fillable = [
        'load_sheet_id',
        'invoice_id',
        'loaded_quantity',
        'loaded_value',
    ];

    protected function casts(): array
    {
        return [
            'loaded_quantity' => 'decimal:4',
            'loaded_value' => 'decimal:2',
        ];
    }

    public function loadSheet(): BelongsTo
    {
        return $this->belongsTo(LoadSheet::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
