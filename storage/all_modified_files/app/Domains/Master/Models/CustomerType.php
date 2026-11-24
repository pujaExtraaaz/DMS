<?php

namespace App\Domains\Master\Models;

use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerType extends Model
{
    use HasUppercaseNames;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CustomerType $type) {
            if (blank($type->code)) {
                $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', \Illuminate\Support\Str::slug($type->name ?? 'TYPE', '_')));
                if (empty($base)) {
                    $base = 'TYPE';
                }
                $base = substr($base, 0, 15);
                $code = $base;
                $counter = 1;
                while (static::where('code', $code)->exists()) {
                    $suffix = (string) $counter;
                    $code = substr($base, 0, 20 - strlen($suffix)) . $suffix;
                    $counter++;
                }
                $type->code = $code;
            }
        });
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function priceMasters(): HasMany
    {
        return $this->hasMany(PriceMaster::class);
    }
}