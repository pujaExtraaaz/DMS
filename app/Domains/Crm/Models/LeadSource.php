<?php

namespace App\Domains\Crm\Models;

use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadSource extends Model
{
    use HasUppercaseNames;

    protected $fillable = ['name', 'code', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
