<?php

namespace App\Domains\Master\Models;

use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyContact extends Model
{
    use HasUppercaseNames;

    protected $fillable = [
        'customer_id',
        'designation_id',
        'level',
        'name',
        'role',
        'phone',
        'alternate_phone',
        'email',
        'location',
        'is_primary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }
}
