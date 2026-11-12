<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UomMapping extends Model
{
    protected $fillable = [
        'imported_uom',
        'uom_id',
    ];

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }
}

