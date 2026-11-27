<?php

namespace App\Domains\Service\Models;

use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceSlaPolicy extends Model
{
    protected $fillable = [
        'company_id', 'priority', 'resolution_hours', 'escalate_after_hours', 'escalate_to_user_id', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function escalateTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalate_to_user_id');
    }
}
