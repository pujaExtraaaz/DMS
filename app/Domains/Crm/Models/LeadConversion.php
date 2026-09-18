<?php

namespace App\Domains\Crm\Models;

use App\Domains\Master\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadConversion extends Model
{
    protected $fillable = ['lead_id', 'customer_id', 'converted_by', 'converted_at', 'notes'];

    protected function casts(): array
    {
        return ['converted_at' => 'datetime'];
    }

    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function converter(): BelongsTo { return $this->belongsTo(User::class, 'converted_by'); }
}
