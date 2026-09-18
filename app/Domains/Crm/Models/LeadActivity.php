<?php

namespace App\Domains\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    protected $fillable = ['lead_id', 'user_id', 'activity_type', 'body', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
