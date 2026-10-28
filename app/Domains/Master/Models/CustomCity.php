<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;

class CustomCity extends Model
{
    protected $fillable = [
        'state',
        'name',
    ];
}
