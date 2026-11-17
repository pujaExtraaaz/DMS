<?php

namespace App\Domains\Organization\Models;

use App\Support\NameCase;
use Illuminate\Database\Eloquent\Model;

class WarehouseType extends Model
{
    protected $fillable = [
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = filled($value) ? NameCase::format(trim((string) $value)) : null;
    }
}
